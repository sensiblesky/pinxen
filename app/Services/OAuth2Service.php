<?php

namespace App\Services;

use App\Models\ApiMonitor;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class OAuth2Service
{
    /**
     * Refresh access token for an API monitor.
     *
     * @param ApiMonitor $monitor
     * @return array ['success' => bool, 'access_token' => string|null, 'expires_in' => int|null, 'refresh_token' => string|null, 'error' => string|null]
     */
    public function refreshToken(ApiMonitor $monitor): array
    {
        if (!$monitor->auto_auth_enabled || !$monitor->auto_auth_type) {
            return [
                'success' => false,
                'error' => 'Auto-auth is not enabled for this monitor',
            ];
        }

        try {
            switch ($monitor->auto_auth_type) {
                case 'oauth2_client_credentials':
                    return $this->getClientCredentialsToken($monitor);
                
                case 'oauth2_password':
                    return $this->getPasswordToken($monitor);
                
                case 'oauth2_refresh_token':
                    return $this->refreshTokenGrant($monitor);
                
                case 'jwt':
                    return $this->refreshJWTToken($monitor);
                
                default:
                    return [
                        'success' => false,
                        'error' => 'Unsupported auto-auth type: ' . $monitor->auto_auth_type,
                    ];
            }
        } catch (\Exception $e) {
            Log::error("Failed to refresh token for API monitor {$monitor->id}", [
                'error' => $e->getMessage(),
                'type' => $monitor->auto_auth_type,
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * OAuth2 Client Credentials flow.
     */
    protected function getClientCredentialsToken(ApiMonitor $monitor): array
    {
        $response = Http::asForm()
            ->withOptions([
                'curl' => [
                    CURLOPT_SSL_VERIFYPEER => $monitor->check_ssl ?? true,
                    CURLOPT_SSL_VERIFYHOST => ($monitor->check_ssl ?? true) ? 2 : 0,
                ],
            ])
            ->post($monitor->oauth2_token_url, [
                'grant_type' => 'client_credentials',
                'client_id' => $monitor->oauth2_client_id,
                'client_secret' => $monitor->oauth2_client_secret,
                'scope' => $monitor->oauth2_scope,
            ]);

        if (!$response->successful()) {
            return [
                'success' => false,
                'error' => 'Token request failed: ' . $response->body(),
            ];
        }

        $data = $response->json();
        
        return [
            'success' => true,
            'access_token' => $data['access_token'] ?? null,
            'expires_in' => $data['expires_in'] ?? null,
            'token_type' => $data['token_type'] ?? 'Bearer',
            'scope' => $data['scope'] ?? null,
        ];
    }

    /**
     * OAuth2 Password flow.
     */
    protected function getPasswordToken(ApiMonitor $monitor): array
    {
        $response = Http::asForm()
            ->withOptions([
                'curl' => [
                    CURLOPT_SSL_VERIFYPEER => $monitor->check_ssl ?? true,
                    CURLOPT_SSL_VERIFYHOST => ($monitor->check_ssl ?? true) ? 2 : 0,
                ],
            ])
            ->post($monitor->oauth2_token_url, [
                'grant_type' => 'password',
                'client_id' => $monitor->oauth2_client_id,
                'client_secret' => $monitor->oauth2_client_secret,
                'username' => $monitor->oauth2_username,
                'password' => $monitor->oauth2_password,
                'scope' => $monitor->oauth2_scope,
            ]);

        if (!$response->successful()) {
            return [
                'success' => false,
                'error' => 'Token request failed: ' . $response->body(),
            ];
        }

        $data = $response->json();
        
        return [
            'success' => true,
            'access_token' => $data['access_token'] ?? null,
            'expires_in' => $data['expires_in'] ?? null,
            'refresh_token' => $data['refresh_token'] ?? null,
            'token_type' => $data['token_type'] ?? 'Bearer',
            'scope' => $data['scope'] ?? null,
        ];
    }

    /**
     * OAuth2 Refresh Token flow.
     */
    protected function refreshTokenGrant(ApiMonitor $monitor): array
    {
        if (!$monitor->oauth2_refresh_token) {
            return [
                'success' => false,
                'error' => 'No refresh token available',
            ];
        }

        $response = Http::asForm()
            ->withOptions([
                'curl' => [
                    CURLOPT_SSL_VERIFYPEER => $monitor->check_ssl ?? true,
                    CURLOPT_SSL_VERIFYHOST => ($monitor->check_ssl ?? true) ? 2 : 0,
                ],
            ])
            ->post($monitor->oauth2_token_url, [
                'grant_type' => 'refresh_token',
                'client_id' => $monitor->oauth2_client_id,
                'client_secret' => $monitor->oauth2_client_secret,
                'refresh_token' => $monitor->oauth2_refresh_token,
                'scope' => $monitor->oauth2_scope,
            ]);

        if (!$response->successful()) {
            return [
                'success' => false,
                'error' => 'Token refresh failed: ' . $response->body(),
            ];
        }

        $data = $response->json();
        
        return [
            'success' => true,
            'access_token' => $data['access_token'] ?? null,
            'expires_in' => $data['expires_in'] ?? null,
            'refresh_token' => $data['refresh_token'] ?? $monitor->oauth2_refresh_token, // Use new or keep old
            'token_type' => $data['token_type'] ?? 'Bearer',
            'scope' => $data['scope'] ?? null,
        ];
    }

    /**
     * Refresh JWT token (extract from API response and decode expiration).
     */
    protected function refreshJWTToken(ApiMonitor $monitor): array
    {
        // For JWT, we typically need to make a request to get a new token
        // This depends on the API implementation
        // For now, we'll use the same client credentials flow but extract JWT from response
        
        $result = $this->getClientCredentialsToken($monitor);
        
        if ($result['success'] && $result['access_token']) {
            // Decode JWT to get expiration
            $jwtData = $this->decodeJWT($result['access_token']);
            if ($jwtData) {
                $result['expires_in'] = $jwtData['exp'] - time();
            }
        }
        
        return $result;
    }

    /**
     * Decode JWT token to extract expiration and other claims.
     *
     * @param string $token
     * @return array|null
     */
    public function decodeJWT(string $token): ?array
    {
        try {
            $parts = explode('.', $token);
            if (count($parts) !== 3) {
                return null;
            }

            $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);
            
            if (!$payload) {
                return null;
            }

            return [
                'exp' => $payload['exp'] ?? null,
                'iat' => $payload['iat'] ?? null,
                'nbf' => $payload['nbf'] ?? null,
                'iss' => $payload['iss'] ?? null,
                'sub' => $payload['sub'] ?? null,
                'aud' => $payload['aud'] ?? null,
                'jti' => $payload['jti'] ?? null,
            ];
        } catch (\Exception $e) {
            Log::warning("Failed to decode JWT token", ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Extract JWT token from API response and decode expiration.
     *
     * @param string $responseBody
     * @param string $tokenPath JSON path to token (e.g., $.access_token)
     * @return array|null ['token' => string, 'expires_at' => Carbon|null]
     */
    public function extractJWTFromResponse(string $responseBody, ?string $tokenPath = null): ?array
    {
        try {
            $data = json_decode($responseBody, true);
            if (!$data) {
                return null;
            }

            // Extract token using path or default
            $token = null;
            if ($tokenPath) {
                $token = $this->getValueByPath($data, $tokenPath);
            } else {
                // Try common paths
                $token = $data['access_token'] ?? $data['token'] ?? $data['jwt'] ?? null;
            }

            if (!$token) {
                return null;
            }

            // Decode JWT
            $jwtData = $this->decodeJWT($token);
            if (!$jwtData || !$jwtData['exp']) {
                return ['token' => $token, 'expires_at' => null];
            }

            return [
                'token' => $token,
                'expires_at' => Carbon::createFromTimestamp($jwtData['exp']),
            ];
        } catch (\Exception $e) {
            Log::warning("Failed to extract JWT from response", ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Get value from data by JSON path.
     */
    protected function getValueByPath($data, string $path)
    {
        if ($path === null || $path === '' || $path === '$') {
            return $data;
        }

        $path = ltrim($path, '$');
        $path = ltrim($path, '.');

        if (empty($path)) {
            return $data;
        }

        $parts = [];
        $current = '';
        $inBrackets = false;
        
        for ($i = 0; $i < strlen($path); $i++) {
            $char = $path[$i];
            
            if ($char === '[') {
                if (!empty($current)) {
                    $parts[] = $current;
                    $current = '';
                }
                $inBrackets = true;
                $current .= $char;
            } elseif ($char === ']') {
                $current .= $char;
                $parts[] = $current;
                $current = '';
                $inBrackets = false;
            } elseif ($char === '.' && !$inBrackets) {
                if (!empty($current)) {
                    $parts[] = $current;
                    $current = '';
                }
            } else {
                $current .= $char;
            }
        }
        
        if (!empty($current)) {
            $parts[] = $current;
        }

        $value = $data;
        
        foreach ($parts as $part) {
            if (preg_match('/^\[(\d+)\]$/', $part, $matches)) {
                $index = (int)$matches[1];
                if (is_array($value) && isset($value[$index])) {
                    $value = $value[$index];
                } else {
                    return null;
                }
            } else {
                if (is_array($value) && isset($value[$part])) {
                    $value = $value[$part];
                } elseif (is_object($value) && isset($value->$part)) {
                    $value = $value->$part;
                } else {
                    return null;
                }
            }
        }

        return $value;
    }
}

