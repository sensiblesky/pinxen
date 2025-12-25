<?php

namespace App\Services;

use App\Models\ExternalApi;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Crypt;
use Carbon\Carbon;

class DomainExpirationService
{
    /**
     * Check domain expiration date using WHOIS API or command.
     * 
     * @param string $domain
     * @return array|null Returns ['expiration_date' => Carbon, 'days_until_expiration' => int] or null on failure
     */
    public function checkDomainExpiration(string $domain): ?array
    {
        try {
            // Remove protocol and path if present
            $domain = preg_replace('#^https?://#', '', $domain);
            $domain = preg_replace('#/.*$#', '', $domain);
            $domain = trim($domain);

            // First, try to use stored API credentials
            $expirationDate = $this->getExpirationDateFromApi($domain);
            
            // Fallback to whois command if API is not available
            if (!$expirationDate) {
                $expirationDate = $this->getExpirationDateFromWhois($domain);
            }
            
            if ($expirationDate) {
                $daysUntilExpiration = now()->diffInDays($expirationDate, false);
                
                return [
                    'expiration_date' => $expirationDate,
                    'days_until_expiration' => $daysUntilExpiration,
                ];
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Domain expiration check failed', [
                'domain' => $domain,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Get expiration date from stored API credentials.
     * 
     * @param string $domain
     * @return Carbon|null
     */
    private function getExpirationDateFromApi(string $domain): ?Carbon
    {
        try {
            // Get active WHOIS API configuration
            $api = ExternalApi::getForService('whois');
            
            if (!$api) {
                Log::debug('No WHOIS API configured, falling back to whois command');
                return null;
            }

            // Decrypt API key if needed
            $apiKey = null;
            if ($api->api_key) {
                try {
                    $apiKey = Crypt::decryptString($api->api_key);
                } catch (\Exception $e) {
                    Log::warning('Failed to decrypt API key', [
                        'api_id' => $api->id,
                        'error' => $e->getMessage(),
                    ]);
                    return null;
                }
            }

            // Build URL
            $baseUrl = rtrim($api->base_url ?? 'https://api.apilayer.com', '/');
            $endpoint = $api->endpoint ?? '/whois/query';
            $url = $baseUrl . $endpoint . '?domain=' . urlencode($domain);

            // Prepare headers
            $headers = $api->headers ?? [];
            
            // If headers contain apikey placeholder or if api_key is set, use it
            if ($apiKey) {
                // Check if headers already have apikey
                if (isset($headers['apikey'])) {
                    $headers['apikey'] = $apiKey;
                } else {
                    // Add apikey to headers
                    $headers['apikey'] = $apiKey;
                }
            }

            // Make API request with proper timeouts
            $response = Http::timeout(120) // 120 seconds for WHOIS API (can be slow)
                ->withHeaders($headers)
                ->withOptions([
                    'curl' => [
                        CURLOPT_CONNECTTIMEOUT => 20, // 20 seconds to establish connection
                        CURLOPT_TIMEOUT => 120, // Total timeout
                    ],
                ])
                ->get($url);

            if (!$response->successful()) {
                Log::warning('WHOIS API request failed', [
                    'domain' => $domain,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return null;
            }

            $data = $response->json();

            // Parse response based on apilayer format
            if (isset($data['result']['expiration_date'])) {
                $expirationDateString = $data['result']['expiration_date'];
                
                // Parse the date (format: "2028-09-14 04:00:00")
                try {
                    $expirationDate = Carbon::createFromFormat('Y-m-d H:i:s', $expirationDateString);
                    return $expirationDate->startOfDay();
                } catch (\Exception $e) {
                    // Try alternative format
                    try {
                        $expirationDate = Carbon::parse($expirationDateString);
                        return $expirationDate->startOfDay();
                    } catch (\Exception $e2) {
                        Log::warning('Failed to parse expiration date from API response', [
                            'domain' => $domain,
                            'date_string' => $expirationDateString,
                            'error' => $e2->getMessage(),
                        ]);
                        return null;
                    }
                }
            }

            Log::warning('No expiration date found in API response', [
                'domain' => $domain,
                'response' => $data,
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('WHOIS API lookup failed', [
                'domain' => $domain,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return null;
        }
    }

    /**
     * Get expiration date from WHOIS command.
     * 
     * @param string $domain
     * @return Carbon|null
     */
    private function getExpirationDateFromWhois(string $domain): ?Carbon
    {
        try {
            // Check if whois command is available
            $whoisCommand = $this->findWhoisCommand();
            
            if (!$whoisCommand) {
                Log::warning('WHOIS command not found. Domain expiration checking may not work.');
                return null;
            }

            // Execute whois command
            $output = [];
            $returnVar = 0;
            exec("{$whoisCommand} {$domain} 2>&1", $output, $returnVar);

            if ($returnVar !== 0) {
                Log::warning('WHOIS command failed', [
                    'domain' => $domain,
                    'return_var' => $returnVar,
                    'output' => implode("\n", $output),
                ]);
                return null;
            }

            $whoisData = implode("\n", $output);

            // Parse expiration date from WHOIS data
            // Different registrars use different formats
            $patterns = [
                // Generic patterns
                '/expir(?:es?|ation|y date)[\s:]+([0-9]{4}-[0-9]{2}-[0-9]{2})/i',
                '/expir(?:es?|ation|y date)[\s:]+([0-9]{2}\/[0-9]{2}\/[0-9]{4})/i',
                '/expir(?:es?|ation|y date)[\s:]+([0-9]{2}-[0-9]{2}-[0-9]{4})/i',
                '/Registry Expiry Date:[\s]+([0-9]{4}-[0-9]{2}-[0-9]{2})/i',
                '/Expiration Date:[\s]+([0-9]{4}-[0-9]{2}-[0-9]{2})/i',
                '/Expires On:[\s]+([0-9]{4}-[0-9]{2}-[0-9]{2})/i',
                '/paid-till:[\s]+([0-9]{4}-[0-9]{2}-[0-9]{2})/i',
                '/expires:[\s]+([0-9]{4}-[0-9]{2}-[0-9]{2})/i',
            ];

            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $whoisData, $matches)) {
                    try {
                        $dateString = $matches[1];
                        
                        // Try to parse the date
                        if (strpos($dateString, '/') !== false) {
                            // Format: MM/DD/YYYY
                            $date = Carbon::createFromFormat('m/d/Y', $dateString);
                        } elseif (strpos($dateString, '-') !== false) {
                            // Format: YYYY-MM-DD
                            $date = Carbon::createFromFormat('Y-m-d', $dateString);
                        } else {
                            continue;
                        }

                        if ($date && $date->isValid()) {
                            return $date->startOfDay();
                        }
                    } catch (\Exception $e) {
                        Log::debug('Failed to parse expiration date', [
                            'domain' => $domain,
                            'date_string' => $dateString ?? null,
                            'error' => $e->getMessage(),
                        ]);
                        continue;
                    }
                }
            }

            Log::warning('Could not find expiration date in WHOIS data', [
                'domain' => $domain,
                'whois_preview' => substr($whoisData, 0, 500),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('WHOIS lookup failed', [
                'domain' => $domain,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Find the whois command path.
     * 
     * @return string|null
     */
    private function findWhoisCommand(): ?string
    {
        // Common paths for whois command
        $possiblePaths = [
            '/usr/bin/whois',
            '/usr/local/bin/whois',
            'whois', // In PATH
        ];

        foreach ($possiblePaths as $path) {
            $output = [];
            $returnVar = 0;
            exec("which {$path} 2>&1", $output, $returnVar);
            
            if ($returnVar === 0 && !empty($output)) {
                return trim($output[0]);
            }
        }

        // Try direct execution
        $output = [];
        $returnVar = 0;
        exec("whois --version 2>&1", $output, $returnVar);
        
        if ($returnVar === 0) {
            return 'whois';
        }

        return null;
    }
}

