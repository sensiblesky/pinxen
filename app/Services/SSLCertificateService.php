<?php

namespace App\Services;

use App\Models\ExternalApi;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class SSLCertificateService
{
    /**
     * Check SSL certificate using API.
     * 
     * @param string $domain
     * @return array|null Returns SSL certificate data or null on failure
     */
    public function checkSSLCertificate(string $domain): ?array
    {
        try {
            // Remove protocol and path if present
            $domain = preg_replace('#^https?://#', '', $domain);
            $domain = preg_replace('#/.*$#', '', $domain);
            $domain = trim($domain);

            // Get SSL API configuration (optional - can use direct API call)
            $api = ExternalApi::active()->byServiceType('ssl')->first();
            
            $url = 'https://ssl-checker.io/api/v1/check/' . urlencode($domain);
            $headers = [];

            // If API is configured, use it
            if ($api && $api->base_url && $api->endpoint) {
                $baseUrl = rtrim($api->base_url, '/');
                $endpoint = str_replace('{domain}', $domain, $api->endpoint);
                $url = $baseUrl . $endpoint;
                
                if ($api->headers) {
                    $headers = $api->headers;
                }
            }

            // Make API request with proper timeouts
            $response = Http::timeout(60) // 60 seconds for API call
                ->withHeaders($headers)
                ->withOptions([
                    'curl' => [
                        CURLOPT_CONNECTTIMEOUT => 15, // 15 seconds to establish connection
                        CURLOPT_TIMEOUT => 60, // Total timeout
                    ],
                ])
                ->get($url);

            if (!$response->successful()) {
                Log::warning('SSL API request failed', [
                    'domain' => $domain,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return null;
            }

            $data = $response->json();

            // Check if response is valid
            if (!isset($data['status']) || $data['status'] !== 'ok' || !isset($data['result'])) {
                Log::warning('Invalid SSL API response', [
                    'domain' => $domain,
                    'response' => $data,
                ]);
                return null;
            }

            $result = $data['result'];

            // Parse expiration date
            $expirationDate = null;
            if (isset($result['valid_till'])) {
                try {
                    $expirationDate = Carbon::parse($result['valid_till']);
                } catch (\Exception $e) {
                    Log::warning('Failed to parse SSL expiration date', [
                        'domain' => $domain,
                        'valid_till' => $result['valid_till'] ?? null,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Determine status
            $status = 'valid';
            if (isset($result['cert_exp']) && $result['cert_exp'] === true) {
                $status = 'expired';
            } elseif (isset($result['cert_valid']) && $result['cert_valid'] === false) {
                $status = 'invalid';
            } elseif (isset($result['days_left']) && $result['days_left'] <= 30) {
                $status = 'expiring_soon';
            }

            return [
                'status' => $status,
                'resolved_ip' => $result['resolved_ip'] ?? null,
                'issued_to' => $result['issued_to'] ?? null,
                'issuer_cn' => $result['issuer_cn'] ?? null,
                'cert_alg' => $result['cert_alg'] ?? null,
                'cert_valid' => $result['cert_valid'] ?? true,
                'cert_exp' => $result['cert_exp'] ?? false,
                'valid_from' => isset($result['valid_from']) ? Carbon::parse($result['valid_from']) : null,
                'valid_till' => $expirationDate,
                'validity_days' => $result['validity_days'] ?? null,
                'days_left' => $result['days_left'] ?? null,
                'hsts_header_enabled' => $result['hsts_header_enabled'] ?? false,
                'response_time_sec' => $data['response_time_sec'] ?? null,
                'raw_response' => $data,
                'expiration_date' => $expirationDate,
                'days_until_expiration' => $result['days_left'] ?? null,
            ];
        } catch (\Exception $e) {
            Log::error('SSL certificate check failed', [
                'domain' => $domain,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return null;
        }
    }
}

