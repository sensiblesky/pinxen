<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class IPGeolocationService
{
    /**
     * Get geolocation data for an IP address using ip-api.com
     * 
     * @param string $ipAddress
     * @return array|null Returns geolocation data or null on failure
     */
    public static function getLocation(string $ipAddress): ?array
    {
        try {
            // Skip local/private IPs
            if (self::isLocalIP($ipAddress)) {
                return [
                    'country' => 'Local',
                    'countryCode' => 'LOCAL',
                    'region' => 'Local Network',
                    'regionName' => 'Local Network',
                    'city' => 'Local',
                    'lat' => null,
                    'lon' => null,
                    'timezone' => config('app.timezone', 'UTC'),
                    'isp' => 'Local Network',
                    'org' => 'Local Network',
                ];
            }

            // Make API request to ip-api.com
            $response = Http::timeout(5)
                ->get("http://ip-api.com/json/{$ipAddress}");

            if (!$response->successful()) {
                Log::warning('IP geolocation API request failed', [
                    'ip' => $ipAddress,
                    'status' => $response->status(),
                ]);
                return null;
            }

            $data = $response->json();

            // Check if API returned success
            if (isset($data['status']) && $data['status'] === 'success') {
                return [
                    'country' => $data['country'] ?? 'Unknown',
                    'countryCode' => $data['countryCode'] ?? 'UN',
                    'region' => $data['region'] ?? 'Unknown',
                    'regionName' => $data['regionName'] ?? 'Unknown',
                    'city' => $data['city'] ?? 'Unknown',
                    'zip' => $data['zip'] ?? '',
                    'lat' => $data['lat'] ?? null,
                    'lon' => $data['lon'] ?? null,
                    'timezone' => $data['timezone'] ?? 'UTC',
                    'isp' => $data['isp'] ?? 'Unknown',
                    'org' => $data['org'] ?? 'Unknown',
                    'as' => $data['as'] ?? null,
                ];
            }

            // API returned failure
            Log::warning('IP geolocation API returned failure', [
                'ip' => $ipAddress,
                'message' => $data['message'] ?? 'Unknown error',
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('IP geolocation lookup failed', [
                'ip' => $ipAddress,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Format location string from geolocation data.
     * 
     * @param array|null $locationData
     * @return string
     */
    public static function formatLocation(?array $locationData): string
    {
        if (!$locationData) {
            return 'Unknown Location';
        }

        $parts = [];
        
        if (!empty($locationData['city'])) {
            $parts[] = $locationData['city'];
        }
        
        if (!empty($locationData['regionName'])) {
            $parts[] = $locationData['regionName'];
        }
        
        if (!empty($locationData['country'])) {
            $parts[] = $locationData['country'];
        }

        return !empty($parts) ? implode(', ', $parts) : 'Unknown Location';
    }

    /**
     * Check if IP address is local/private.
     * 
     * @param string $ipAddress
     * @return bool
     */
    private static function isLocalIP(string $ipAddress): bool
    {
        // Check for localhost
        if ($ipAddress === '127.0.0.1' || $ipAddress === '::1' || $ipAddress === 'localhost') {
            return true;
        }

        // Check for private IP ranges
        return !filter_var($ipAddress, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
    }
}




