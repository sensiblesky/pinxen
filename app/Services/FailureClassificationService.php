<?php

namespace App\Services;

use Illuminate\Support\Str;

class FailureClassificationService
{
    /**
     * Classify failure type based on error message, status code, and response time.
     *
     * @param string|null $errorMessage
     * @param int|null $statusCode
     * @param int|null $responseTime
     * @param string $url
     * @return array ['type' => string, 'classification' => string, 'provider' => string|null]
     */
    public static function classifyFailure(
        ?string $errorMessage,
        ?int $statusCode,
        ?int $responseTime,
        string $url
    ): array {
        $errorMessage = $errorMessage ?? '';
        $errorLower = Str::lower($errorMessage);

        // 1. DNS Failure
        if (self::isDnsFailure($errorMessage, $errorLower)) {
            $provider = self::detectDnsProvider($url, $errorMessage);
            return [
                'type' => 'dns_failure',
                'classification' => 'DNS resolution failed' . ($provider ? " ({$provider})" : ''),
                'provider' => $provider,
            ];
        }

        // 2. SSL Certificate Issues
        if (self::isSslFailure($errorMessage, $errorLower)) {
            $sslType = self::getSslFailureType($errorMessage, $errorLower);
            return [
                'type' => 'ssl_failure',
                'classification' => $sslType,
                'provider' => null,
            ];
        }

        // 3. TCP Connection Refused
        if (self::isTcpConnectionRefused($errorMessage, $errorLower)) {
            return [
                'type' => 'tcp_connection_refused',
                'classification' => 'TCP connection refused',
                'provider' => null,
            ];
        }

        // 4. HTTP 5xx (Server Error)
        if ($statusCode && $statusCode >= 500 && $statusCode < 600) {
            return [
                'type' => 'http_5xx',
                'classification' => "HTTP {$statusCode} (Server Error)",
                'provider' => null,
            ];
        }

        // 5. CDN Edge Failure
        if (self::isCdnFailure($errorMessage, $errorLower, $statusCode)) {
            $cdnProvider = self::detectCdnProvider($url, $errorMessage);
            return [
                'type' => 'cdn_edge_failure',
                'classification' => 'CDN edge failure' . ($cdnProvider ? " ({$cdnProvider})" : ''),
                'provider' => $cdnProvider,
            ];
        }

        // 6. Firewall / Geo Block
        if (self::isFirewallBlock($errorMessage, $errorLower, $statusCode)) {
            return [
                'type' => 'firewall_geo_block',
                'classification' => 'Firewall / Geo block',
                'provider' => null,
            ];
        }

        // 7. Origin Server Slow but Reachable
        if (self::isSlowButReachable($responseTime, $statusCode, $errorMessage)) {
            return [
                'type' => 'origin_slow',
                'classification' => 'Origin server slow but reachable',
                'provider' => null,
            ];
        }

        // 8. HTTP 4xx (Client Error)
        if ($statusCode && $statusCode >= 400 && $statusCode < 500) {
            return [
                'type' => 'http_4xx',
                'classification' => "HTTP {$statusCode} (Client Error)",
                'provider' => null,
            ];
        }

        // 9. Timeout
        if (self::isTimeout($errorMessage, $errorLower, $responseTime)) {
            return [
                'type' => 'timeout',
                'classification' => 'Connection timeout',
                'provider' => null,
            ];
        }

        // Default: Unknown failure
        return [
            'type' => 'unknown',
            'classification' => 'Unknown failure',
            'provider' => null,
        ];
    }

    /**
     * Check if error is DNS-related.
     */
    private static function isDnsFailure(string $errorMessage, string $errorLower): bool
    {
        $dnsKeywords = [
            'dns',
            'could not resolve host',
            'name resolution',
            'getaddrinfo failed',
            'name or service not known',
            'no such host',
            'host not found',
            'nxdomain',
            'dns_probe_finished',
        ];

        foreach ($dnsKeywords as $keyword) {
            if (Str::contains($errorLower, $keyword)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if error is SSL-related.
     */
    private static function isSslFailure(string $errorMessage, string $errorLower): bool
    {
        $sslKeywords = [
            'ssl certificate',
            'ssl handshake',
            'certificate verify failed',
            'certificate chain',
            'unable to get local issuer certificate',
            'curl error 60',
            'tls',
            'certificate expired',
            'certificate has expired',
            'self signed',
            'self-signed',
        ];

        foreach ($sslKeywords as $keyword) {
            if (Str::contains($errorLower, $keyword)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get specific SSL failure type.
     */
    private static function getSslFailureType(string $errorMessage, string $errorLower): string
    {
        if (Str::contains($errorLower, 'expired') || Str::contains($errorLower, 'has expired')) {
            return 'SSL certificate expired';
        }

        if (Str::contains($errorLower, 'handshake')) {
            return 'SSL handshake failure';
        }

        if (Str::contains($errorLower, 'self signed') || Str::contains($errorLower, 'self-signed')) {
            return 'Self-signed SSL certificate';
        }

        if (Str::contains($errorLower, 'hostname') || Str::contains($errorLower, "doesn't match")) {
            return 'SSL certificate hostname mismatch';
        }

        if (Str::contains($errorLower, 'chain') || Str::contains($errorLower, 'unable to get local issuer')) {
            return 'SSL certificate chain incomplete';
        }

        return 'SSL certificate verification failed';
    }

    /**
     * Check if error is TCP connection refused.
     */
    private static function isTcpConnectionRefused(string $errorMessage, string $errorLower): bool
    {
        $tcpKeywords = [
            'connection refused',
            'connection reset',
            'connection reset by peer',
            'connection timed out',
            'connect() failed',
            'errno 111',
            'errno 110',
            'refused',
            'reset',
        ];

        foreach ($tcpKeywords as $keyword) {
            if (Str::contains($errorLower, $keyword)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if error is CDN-related.
     */
    private static function isCdnFailure(string $errorMessage, string $errorLower, ?int $statusCode): bool
    {
        // Check for CDN-specific error codes
        if ($statusCode === 520 || $statusCode === 521 || $statusCode === 522 || 
            $statusCode === 523 || $statusCode === 524 || $statusCode === 525 || 
            $statusCode === 526 || $statusCode === 527) {
            return true;
        }

        $cdnKeywords = [
            'cloudflare',
            'cloudfront',
            'fastly',
            'cdn',
            'edge',
            'origin',
            'upstream',
        ];

        foreach ($cdnKeywords as $keyword) {
            if (Str::contains($errorLower, $keyword)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if error is firewall/geo block.
     */
    private static function isFirewallBlock(string $errorMessage, string $errorLower, ?int $statusCode): bool
    {
        // Common firewall/block status codes
        if ($statusCode === 403 || $statusCode === 451) {
            return true;
        }

        $firewallKeywords = [
            'access denied',
            'forbidden',
            'blocked',
            'geo',
            'country',
            'region',
            'ip blocked',
            'firewall',
            'waf',
            'cloudflare challenge',
            'captcha',
        ];

        foreach ($firewallKeywords as $keyword) {
            if (Str::contains($errorLower, $keyword)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if server is slow but reachable.
     */
    private static function isSlowButReachable(?int $responseTime, ?int $statusCode, string $errorMessage): bool
    {
        // If we got a status code (even if not 200), server is reachable
        if ($statusCode && $statusCode >= 200 && $statusCode < 500) {
            // If response time is very high (over 10 seconds), consider it slow
            if ($responseTime && $responseTime > 10000) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if error is timeout.
     */
    private static function isTimeout(string $errorMessage, string $errorLower, ?int $responseTime): bool
    {
        $timeoutKeywords = [
            'timeout',
            'timed out',
            'operation timed out',
            'connection timed out',
            'curl error 28',
        ];

        foreach ($timeoutKeywords as $keyword) {
            if (Str::contains($errorLower, $keyword)) {
                return true;
            }
        }

        // If response time is null and we have an error, likely timeout
        if ($responseTime === null && !empty($errorMessage)) {
            return true;
        }

        return false;
    }

    /**
     * Detect DNS provider from URL or error message.
     */
    private static function detectDnsProvider(string $url, string $errorMessage): ?string
    {
        $errorLower = Str::lower($errorMessage);
        $urlLower = Str::lower($url);

        // Check error message for provider names
        $providers = ['cloudflare', 'cloudflare dns', 'google dns', 'opendns', 'quad9', 'quad9 dns'];
        foreach ($providers as $provider) {
            if (Str::contains($errorLower, $provider)) {
                return ucfirst(str_replace(' dns', '', $provider));
            }
        }

        // Check URL for common DNS/CDN providers
        if (Str::contains($urlLower, 'cloudflare')) {
            return 'Cloudflare';
        }

        return null;
    }

    /**
     * Detect CDN provider from URL or error message.
     */
    private static function detectCdnProvider(string $url, string $errorMessage): ?string
    {
        $errorLower = Str::lower($errorMessage);
        $urlLower = Str::lower($url);

        $cdnProviders = [
            'cloudflare' => 'Cloudflare',
            'cloudfront' => 'CloudFront',
            'fastly' => 'Fastly',
            'akamai' => 'Akamai',
            'maxcdn' => 'MaxCDN',
            'keycdn' => 'KeyCDN',
            'bunnycdn' => 'BunnyCDN',
        ];

        foreach ($cdnProviders as $key => $name) {
            if (Str::contains($errorLower, $key) || Str::contains($urlLower, $key)) {
                return $name;
            }
        }

        return null;
    }
}

