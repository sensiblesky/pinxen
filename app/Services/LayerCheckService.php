<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class LayerCheckService
{
    /**
     * Perform multi-layer checks for an uptime monitor.
     *
     * @param string $url
     * @param bool $checkSsl
     * @param int $timeout
     * @param string|null $keywordPresent
     * @param string|null $keywordAbsent
     * @return array
     */
    public static function performLayerChecks(
        string $url,
        bool $checkSsl = true,
        int $timeout = 30,
        ?string $keywordPresent = null,
        ?string $keywordAbsent = null
    ): array {
        $parsedUrl = parse_url($url);
        $host = $parsedUrl['host'] ?? '';
        $port = $parsedUrl['port'] ?? ($parsedUrl['scheme'] === 'https' ? 443 : 80);
        $path = $parsedUrl['path'] ?? '/';
        $scheme = $parsedUrl['scheme'] ?? 'http';

        $layers = [
            'dns' => self::checkDns($host),
            'tcp' => null,
            'tls' => null,
            'http' => null,
            'content' => null,
        ];

        // Only check TCP if DNS succeeded
        if ($layers['dns']['status'] === 'success') {
            $layers['tcp'] = self::checkTcp($host, $port, $timeout);
            
            // Only check TLS if TCP succeeded and using HTTPS
            if ($layers['tcp']['status'] === 'success' && $scheme === 'https') {
                $layers['tls'] = self::checkTls($host, $port, $timeout, $checkSsl);
                
                // Only check HTTP if TLS succeeded (or if HTTP)
                if ($layers['tls']['status'] === 'success' || $scheme === 'http') {
                    $layers['http'] = self::checkHttp($url, $timeout, $checkSsl);
                    
                    // Only check content if HTTP succeeded
                    if ($layers['http']['status'] === 'success' && isset($layers['http']['body'])) {
                        $layers['content'] = self::checkContent(
                            $layers['http']['body'],
                            $keywordPresent,
                            $keywordAbsent
                        );
                    }
                }
            } elseif ($layers['tcp']['status'] === 'success' && $scheme === 'http') {
                // For HTTP, check directly after TCP
                $layers['http'] = self::checkHttp($url, $timeout, $checkSsl);
                
                if ($layers['http']['status'] === 'success' && isset($layers['http']['body'])) {
                    $layers['content'] = self::checkContent(
                        $layers['http']['body'],
                        $keywordPresent,
                        $keywordAbsent
                    );
                }
            }
        }

        return $layers;
    }

    /**
     * Check DNS resolution.
     */
    private static function checkDns(string $host): array
    {
        $startTime = microtime(true);
        
        try {
            $ip = gethostbyname($host);
            
            if ($ip === $host) {
                // DNS resolution failed (gethostbyname returns the hostname if it fails)
                return [
                    'status' => 'failed',
                    'message' => 'DNS resolution failed',
                    'latency_ms' => round((microtime(true) - $startTime) * 1000),
                ];
            }
            
            return [
                'status' => 'success',
                'message' => "Resolved to {$ip}",
                'ip' => $ip,
                'latency_ms' => round((microtime(true) - $startTime) * 1000),
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'failed',
                'message' => 'DNS resolution error: ' . $e->getMessage(),
                'latency_ms' => round((microtime(true) - $startTime) * 1000),
            ];
        }
    }

    /**
     * Check TCP connection.
     */
    private static function checkTcp(string $host, int $port, int $timeout): array
    {
        $startTime = microtime(true);
        $connectTimeout = min(10, max(2, $timeout / 3));
        
        try {
            $socket = @fsockopen($host, $port, $errno, $errstr, $connectTimeout);
            
            if (!$socket) {
                return [
                    'status' => 'failed',
                    'message' => "TCP connection refused (Error {$errno}: {$errstr})",
                    'latency_ms' => round((microtime(true) - $startTime) * 1000),
                ];
            }
            
            fclose($socket);
            
            return [
                'status' => 'success',
                'message' => "TCP connection established on port {$port}",
                'port' => $port,
                'latency_ms' => round((microtime(true) - $startTime) * 1000),
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'failed',
                'message' => 'TCP connection error: ' . $e->getMessage(),
                'latency_ms' => round((microtime(true) - $startTime) * 1000),
            ];
        }
    }

    /**
     * Check TLS/SSL handshake.
     */
    private static function checkTls(string $host, int $port, int $timeout, bool $checkSsl): array
    {
        $startTime = microtime(true);
        $connectTimeout = min(10, max(2, $timeout / 3));
        
        try {
            $context = stream_context_create([
                'ssl' => [
                    'verify_peer' => $checkSsl,
                    'verify_peer_name' => $checkSsl,
                    'allow_self_signed' => !$checkSsl,
                    'capture_peer_cert' => true,
                ],
            ]);
            
            $socket = @stream_socket_client(
                "ssl://{$host}:{$port}",
                $errno,
                $errstr,
                $connectTimeout,
                STREAM_CLIENT_CONNECT,
                $context
            );
            
            if (!$socket) {
                $errorMsg = "TLS handshake failed (Error {$errno}: {$errstr})";
                
                // Check for specific SSL errors
                if (stripos($errstr, 'certificate') !== false) {
                    if (stripos($errstr, 'expired') !== false) {
                        $errorMsg = 'TLS handshake failed: SSL certificate expired';
                    } elseif (stripos($errstr, 'self signed') !== false) {
                        $errorMsg = 'TLS handshake failed: Self-signed certificate';
                    } elseif (stripos($errstr, 'hostname') !== false) {
                        $errorMsg = 'TLS handshake failed: Certificate hostname mismatch';
                    } else {
                        $errorMsg = 'TLS handshake failed: Certificate verification error';
                    }
                }
                
                return [
                    'status' => 'failed',
                    'message' => $errorMsg,
                    'latency_ms' => round((microtime(true) - $startTime) * 1000),
                ];
            }
            
            // Get certificate info
            $cert = stream_context_get_params($socket)['options']['ssl']['peer_certificate'] ?? null;
            $certInfo = null;
            if ($cert) {
                $certInfo = openssl_x509_parse($cert);
            }
            
            fclose($socket);
            
            $message = "TLS handshake successful";
            if ($certInfo) {
                $validTo = $certInfo['validTo_time_t'] ?? null;
                if ($validTo && $validTo < time()) {
                    $message .= " (certificate expired)";
                } elseif ($validTo) {
                    $daysLeft = floor(($validTo - time()) / 86400);
                    $message .= " (expires in {$daysLeft} days)";
                }
            }
            
            return [
                'status' => 'success',
                'message' => $message,
                'latency_ms' => round((microtime(true) - $startTime) * 1000),
                'certificate' => $certInfo ? [
                    'subject' => $certInfo['subject']['CN'] ?? null,
                    'issuer' => $certInfo['issuer']['CN'] ?? null,
                    'valid_to' => $certInfo['validTo_time_t'] ?? null,
                ] : null,
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'failed',
                'message' => 'TLS handshake error: ' . $e->getMessage(),
                'latency_ms' => round((microtime(true) - $startTime) * 1000),
            ];
        }
    }

    /**
     * Check HTTP response.
     */
    private static function checkHttp(string $url, int $timeout, bool $checkSsl): array
    {
        $startTime = microtime(true);
        
        try {
            $response = \Illuminate\Support\Facades\Http::timeout($timeout)
                ->withOptions([
                    'verify' => $checkSsl,
                    'http_errors' => false,
                ])
                ->get($url);
            
            $responseTime = round((microtime(true) - $startTime) * 1000);
            
            return [
                'status' => 'success',
                'message' => "HTTP {$response->status()}",
                'status_code' => $response->status(),
                'body' => $response->body(),
                'latency_ms' => $responseTime,
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'failed',
                'message' => 'HTTP request failed: ' . $e->getMessage(),
                'latency_ms' => round((microtime(true) - $startTime) * 1000),
            ];
        }
    }

    /**
     * Check page content validation.
     */
    private static function checkContent(string $body, ?string $keywordPresent, ?string $keywordAbsent): array
    {
        $startTime = microtime(true);
        
        $errors = [];
        
        if ($keywordPresent && !empty($keywordPresent)) {
            if (stripos($body, $keywordPresent) === false) {
                $errors[] = "Required keyword '{$keywordPresent}' not found";
            }
        }
        
        if ($keywordAbsent && !empty($keywordAbsent)) {
            if (stripos($body, $keywordAbsent) !== false) {
                $errors[] = "Forbidden keyword '{$keywordAbsent}' found";
            }
        }
        
        if (empty($errors)) {
            return [
                'status' => 'success',
                'message' => 'Content validation passed',
                'latency_ms' => round((microtime(true) - $startTime) * 1000),
            ];
        }
        
        return [
            'status' => 'failed',
            'message' => implode('; ', $errors),
            'latency_ms' => round((microtime(true) - $startTime) * 1000),
        ];
    }
}

