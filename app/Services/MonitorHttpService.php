<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MonitorHttpService
{
    /**
     * Realistic browser user agents to rotate through.
     * This helps prevent blocking by making requests look like real browsers.
     */
    private static array $userAgents = [
        // Chrome on Windows
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/119.0.0.0 Safari/537.36',
        // Chrome on macOS
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/119.0.0.0 Safari/537.36',
        // Firefox on Windows
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:121.0) Gecko/20100101 Firefox/121.0',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:120.0) Gecko/20100101 Firefox/120.0',
        // Firefox on macOS
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:121.0) Gecko/20100101 Firefox/121.0',
        // Safari on macOS
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.1 Safari/605.1.15',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Safari/605.1.15',
        // Edge on Windows
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36 Edg/120.0.0.0',
    ];

    /**
     * Get a random realistic user agent.
     */
    public static function getRandomUserAgent(): string
    {
        return self::$userAgents[array_rand(self::$userAgents)];
    }

    /**
     * Get realistic browser headers to mimic a real browser request.
     */
    public static function getRealisticHeaders(string $url): array
    {
        $userAgent = self::getRandomUserAgent();
        $parsedUrl = parse_url($url);
        $host = $parsedUrl['host'] ?? '';

        return [
            'User-Agent' => $userAgent,
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
            'Accept-Language' => 'en-US,en;q=0.9',
            'Accept-Encoding' => 'gzip, deflate, br',
            'DNT' => '1', // Do Not Track
            'Connection' => 'keep-alive',
            'Upgrade-Insecure-Requests' => '1',
            'Sec-Fetch-Dest' => 'document',
            'Sec-Fetch-Mode' => 'navigate',
            'Sec-Fetch-Site' => 'none',
            'Sec-Fetch-User' => '?1',
            'Cache-Control' => 'max-age=0',
            'Referer' => $parsedUrl['scheme'] . '://' . $host, // Refer to same domain
        ];
    }

    /**
     * Perform HTTP check with anti-blocking measures.
     */
    public static function performCheck(
        string $url,
        int $timeout = 30,
        int $expectedStatusCode = 200,
        ?string $keywordPresent = null,
        ?string $keywordAbsent = null,
        bool $checkSsl = true,
        string $requestMethod = 'GET',
        ?string $basicAuthUsername = null,
        ?string $basicAuthPassword = null,
        ?array $customHeaders = null,
        bool $cacheBuster = false
    ): array {
        $startTime = microtime(true);

        try {
            // Apply cache buster if enabled
            if ($cacheBuster) {
                $separator = strpos($url, '?') !== false ? '&' : '?';
                $url .= $separator . '_cb=' . time() . '_' . uniqid();
            }

            // Build headers
            $headers = self::getRealisticHeaders($url);
            
            // Merge custom headers if provided
            if ($customHeaders && is_array($customHeaders)) {
                $headers = array_merge($headers, $customHeaders);
            }

            // Calculate connection timeout (should be less than total timeout)
            $connectTimeout = min(15, max(5, $timeout / 3)); // 1/3 of total timeout, min 5s, max 15s
            
            // Build HTTP request with realistic browser headers
            $request = Http::timeout($timeout)
                ->withHeaders($headers)
                ->withOptions([
                    'verify' => $checkSsl, // SSL verification
                    'allow_redirects' => [
                        'max' => 5,
                        'strict' => false,
                        'referer' => true,
                        'protocols' => ['http', 'https'],
                        'track_redirects' => true,
                    ],
                    'http_errors' => false, // Don't throw on HTTP errors
                    'curl' => [
                        CURLOPT_FOLLOWLOCATION => true,
                        CURLOPT_MAXREDIRS => 5,
                        CURLOPT_AUTOREFERER => true,
                        CURLOPT_CONNECTTIMEOUT => $connectTimeout,
                        CURLOPT_TIMEOUT => $timeout,
                        CURLOPT_TIMEOUT_MS => $timeout * 1000, // Total timeout in milliseconds
                    ],
                ]);

            // Add basic auth if provided
            if ($basicAuthUsername && $basicAuthPassword) {
                $request = $request->withBasicAuth($basicAuthUsername, $basicAuthPassword);
            }

            // Add small random delay to avoid rate limiting (0-200ms)
            usleep(rand(0, 200000));

            // Perform the request with the specified method
            $method = strtoupper($requestMethod);
            $response = match($method) {
                'POST' => $request->post($url),
                'PUT' => $request->put($url),
                'PATCH' => $request->patch($url),
                'DELETE' => $request->delete($url),
                'HEAD' => $request->head($url),
                'OPTIONS' => $request->options($url),
                default => $request->get($url),
            };

            $responseTime = round((microtime(true) - $startTime) * 1000); // Convert to milliseconds

            // Check status code
            $statusCode = $response->status();
            $isStatusCodeValid = $statusCode === $expectedStatusCode;

            // Check keywords if configured
            $body = $response->body();
            $keywordPresentValid = true;
            $keywordAbsentValid = true;

            if ($keywordPresent && !empty($keywordPresent)) {
                $keywordPresentValid = stripos($body, $keywordPresent) !== false;
            }

            if ($keywordAbsent && !empty($keywordAbsent)) {
                $keywordAbsentValid = stripos($body, $keywordAbsent) === false;
            }

            // Determine if monitor is up
            $isUp = $isStatusCodeValid && $keywordPresentValid && $keywordAbsentValid;

            $errorMessage = null;
            if (!$isUp) {
                $errors = [];
                if (!$isStatusCodeValid) {
                    $errors[] = "Expected status code {$expectedStatusCode}, got {$statusCode}";
                }
                if (!$keywordPresentValid) {
                    $errors[] = "Required keyword '{$keywordPresent}' not found";
                }
                if (!$keywordAbsentValid) {
                    $errors[] = "Forbidden keyword '{$keywordAbsent}' found";
                }
                $errorMessage = implode('; ', $errors);
            }

            return [
                'status' => $isUp ? 'up' : 'down',
                'response_time' => $responseTime,
                'status_code' => $statusCode,
                'error_message' => $errorMessage,
            ];

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            // Connection timeout or refused
            $responseTime = round((microtime(true) - $startTime) * 1000);
            return [
                'status' => 'down',
                'response_time' => $responseTime,
                'status_code' => null,
                'error_message' => 'Connection failed: ' . $e->getMessage(),
            ];
        } catch (\Illuminate\Http\Client\RequestException $e) {
            // Request exception
            $responseTime = round((microtime(true) - $startTime) * 1000);
            return [
                'status' => 'down',
                'response_time' => $responseTime,
                'status_code' => $e->response->status() ?? null,
                'error_message' => 'Request failed: ' . $e->getMessage(),
            ];
        } catch (\Exception $e) {
            // Any other exception
            $responseTime = round((microtime(true) - $startTime) * 1000);
            return [
                'status' => 'down',
                'response_time' => $responseTime,
                'status_code' => null,
                'error_message' => 'Unexpected error: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Check if a URL should be monitored based on robots.txt.
     * This is optional but good practice.
     */
    public static function checkRobotsTxt(string $url): bool
    {
        try {
            $parsedUrl = parse_url($url);
            $baseUrl = $parsedUrl['scheme'] . '://' . $parsedUrl['host'];
            $robotsUrl = $baseUrl . '/robots.txt';

            $response = Http::timeout(5)->get($robotsUrl);

            if ($response->successful()) {
                $robotsContent = $response->body();
                // Simple check - if robots.txt disallows all, we might want to skip
                // For now, we'll just log it and continue
                if (stripos($robotsContent, 'User-agent: *') !== false && 
                    stripos($robotsContent, 'Disallow: /') !== false) {
                    Log::warning("Robots.txt disallows all for {$url}");
                    // We'll still check, but log the warning
                }
            }
        } catch (\Exception $e) {
            // If robots.txt doesn't exist or can't be fetched, continue
            // This is not critical
        }

        return true; // Always allow monitoring (you can change this logic)
    }
}


