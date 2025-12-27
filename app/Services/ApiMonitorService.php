<?php

namespace App\Services;

use App\Models\ApiMonitor;
use App\Services\OAuth2Service;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Carbon\Carbon;

class ApiMonitorService
{
    /**
     * Check an API monitor endpoint.
     * Supports both single-step and multi-step (stateful) monitoring.
     *
     * @param ApiMonitor $monitor
     * @return array
     */
    public function check(ApiMonitor $monitor): array
    {
        // If stateful monitoring is enabled, execute multi-step flow
        if ($monitor->is_stateful && !empty($monitor->monitoring_steps)) {
            return $this->checkStateful($monitor);
        }

        // Otherwise, execute single-step check
        return $this->checkSingleStep($monitor);
    }

    /**
     * Execute a single-step API check.
     *
     * @param ApiMonitor $monitor
     * @return array
     */
    protected function checkSingleStep(ApiMonitor $monitor): array
    {
        // Check and refresh token if needed
        $refreshResult = $this->ensureValidToken($monitor);
        if (!$refreshResult['success'] && $refreshResult['should_abort']) {
            return [
                'status' => 'down',
                'response_time' => 0,
                'status_code' => null,
                'response_body' => null,
                'error_message' => 'Token refresh failed: ' . ($refreshResult['error'] ?? 'Unknown error'),
                'validation_errors' => [],
                'latency_exceeded' => false,
                'status_code_match' => false,
                'should_retry' => false,
                'retry_after' => null,
                'needs_re_auth' => true,
                'auth_error' => $refreshResult['error'] ?? 'Token refresh failed',
                'extracted_variables' => [],
            ];
        }

        $startTime = microtime(true);
        $url = $monitor->url;
        $method = strtoupper($monitor->request_method ?? 'GET');
        $timeout = $monitor->timeout ?? 30;
        $connectTimeout = min(10, $timeout);
        $checkSsl = $monitor->check_ssl ?? true;

        try {
            // Build HTTP request
            $http = Http::timeout($timeout)
                ->withOptions([
                    'curl' => [
                        CURLOPT_FOLLOWLOCATION => true,
                        CURLOPT_MAXREDIRS => 5,
                        CURLOPT_AUTOREFERER => true,
                        CURLOPT_CONNECTTIMEOUT => $connectTimeout,
                        CURLOPT_TIMEOUT => $timeout,
                        CURLOPT_TIMEOUT_MS => $timeout * 1000,
                        CURLOPT_SSL_VERIFYPEER => $checkSsl,
                        CURLOPT_SSL_VERIFYHOST => $checkSsl ? 2 : 0,
                    ],
                ]);

            // Add authentication
            $http = $this->addAuthentication($http, $monitor);

            // Add custom headers
            if ($monitor->request_headers && is_array($monitor->request_headers)) {
                foreach ($monitor->request_headers as $header) {
                    if (isset($header['name']) && isset($header['value'])) {
                        $http = $http->withHeader($header['name'], $header['value']);
                    }
                }
            }

            // Add content type
            if ($monitor->content_type) {
                $http = $http->withHeaders(['Content-Type' => $monitor->content_type]);
            }

            // Add request body for POST/PUT/PATCH
            $body = null;
            if (in_array($method, ['POST', 'PUT', 'PATCH']) && $monitor->request_body) {
                $body = $monitor->request_body;
            }

            // Capture request headers for logging/debugging
            $requestHeaders = [];
            if ($monitor->request_headers && is_array($monitor->request_headers)) {
                foreach ($monitor->request_headers as $header) {
                    if (isset($header['name']) && isset($header['value'])) {
                        $requestHeaders[$header['name']] = $header['value'];
                    }
                }
            }
            if ($monitor->content_type) {
                $requestHeaders['Content-Type'] = $monitor->content_type;
            }

            // Make the request
            $response = $http->send($method, $url, $body ? ['body' => $body] : []);

            $responseTime = round((microtime(true) - $startTime) * 1000);
            $statusCode = $response->status();
            $responseBody = $response->body();
            $responseHeaders = $response->headers();

            // Check status code
            $statusCodeMatch = ($statusCode == $monitor->expected_status_code);

            // Check latency
            $latencyExceeded = false;
            if ($monitor->max_latency_ms && $responseTime > $monitor->max_latency_ms) {
                $latencyExceeded = true;
            }

            // Validate response body if enabled
            $validationErrors = [];
            $shouldRetry = false;
            $retryAfter = null;
            $needsReAuth = false;
            $authError = null;
            
            if ($monitor->validate_response_body && $monitor->response_assertions) {
                $validationResult = $this->validateResponseBody($responseBody, $monitor->response_assertions, $monitor->content_type);
                $validationErrors = $validationResult['errors'] ?? [];
                $actions = $validationResult['actions'] ?? [];
                
                // Handle conditional actions
                foreach ($actions as $action) {
                    if ($action['type'] === 'retry') {
                        // Mark for retry (will be handled by the job)
                        $shouldRetry = true;
                        $retryAfter = $action['value'] ?? 5; // seconds
                    } elseif ($action['type'] === 're_auth') {
                        // Mark for re-authentication
                        $needsReAuth = true;
                        $authError = $action['message'] ?? 'Authentication token expired';
                    }
                }
            }

            // Determine overall status (include schema violations)
            $isUp = $statusCodeMatch && !$latencyExceeded && empty($validationErrors) && empty($schemaViolations);

            // Extract variables if rules are defined
            $extractedVariables = [];
            if (!empty($monitor->variable_extraction_rules)) {
                $extractedVariables = $this->extractVariables($responseBody, $monitor->variable_extraction_rules, $monitor->content_type);
            }

            // Validate schema drift if enabled
            $schemaViolations = [];
            if ($monitor->schema_drift_enabled && $monitor->schema_parsed) {
                $schemaService = new \App\Services\SchemaDriftService();
                $detectionRules = [
                    'detect_missing_fields' => $monitor->detect_missing_fields ?? true,
                    'detect_type_changes' => $monitor->detect_type_changes ?? true,
                    'detect_breaking_changes' => $monitor->detect_breaking_changes ?? true,
                    'detect_enum_violations' => $monitor->detect_enum_violations ?? true,
                ];
                
                // Extract path from URL
                $urlParts = parse_url($monitor->url);
                $path = $urlParts['path'] ?? '/';
                
                $schemaResult = $schemaService->validateResponse(
                    $responseBody,
                    $monitor->schema_parsed,
                    $path,
                    $monitor->request_method,
                    $detectionRules
                );
                
                if (!$schemaResult['valid']) {
                    $schemaViolations = $schemaResult['violations'];
                    // Update last validated timestamp
                    $monitor->update(['schema_last_validated_at' => now()]);
                }
            }

            // Extract and store JWT token if auto-auth is enabled and JWT path is configured
            if ($monitor->auto_auth_enabled && $monitor->auto_auth_type === 'jwt' && $monitor->jwt_token_path) {
                $oauth2Service = new OAuth2Service();
                $jwtData = $oauth2Service->extractJWTFromResponse($responseBody, $monitor->jwt_token_path);
                
                if ($jwtData && $jwtData['token']) {
                    $updateData = [
                        'current_access_token' => $jwtData['token'],
                        'token_refreshed_at' => now(),
                    ];
                    
                    if ($jwtData['expires_at']) {
                        $updateData['token_expires_at'] = $jwtData['expires_at'];
                    }
                    
                    // Update auth_token if using bearer
                    if ($monitor->auth_type === 'bearer') {
                        $updateData['auth_token'] = $jwtData['token'];
                    }
                    
                    $monitor->update($updateData);
                }
            }

            return [
                'status' => $isUp ? 'up' : 'down',
                'request_method' => $method,
                'request_url' => $url,
                'request_headers' => $requestHeaders,
                'request_body' => $body,
                'request_content_type' => $monitor->content_type,
                'response_time' => $responseTime,
                'status_code' => $statusCode,
                'response_body' => $responseBody,
                'response_headers' => $responseHeaders,
                'error_message' => null,
                'validation_errors' => $validationErrors,
                'schema_violations' => $schemaViolations,
                'latency_exceeded' => $latencyExceeded,
                'status_code_match' => $statusCodeMatch,
                'should_retry' => $shouldRetry,
                'retry_after' => $retryAfter,
                'needs_re_auth' => $needsReAuth,
                'auth_error' => $authError,
                'extracted_variables' => $extractedVariables,
            ];

        } catch (ConnectionException $e) {
            $responseTime = round((microtime(true) - $startTime) * 1000);
            $errorMessage = $e->getMessage();

            // Handle SSL errors
            if (stripos($errorMessage, 'SSL certificate') !== false || 
                stripos($errorMessage, 'cURL error 60') !== false) {
                if ($checkSsl) {
                    $errorMessage = 'SSL certificate verification failed. You can disable SSL verification in monitor settings if this is expected.';
                }
            }

            // Capture request details even on failure
            $requestHeaders = [];
            if ($monitor->request_headers && is_array($monitor->request_headers)) {
                foreach ($monitor->request_headers as $header) {
                    if (isset($header['name']) && isset($header['value'])) {
                        $requestHeaders[$header['name']] = $header['value'];
                    }
                }
            }
            if ($monitor->content_type) {
                $requestHeaders['Content-Type'] = $monitor->content_type;
            }

            return [
                'status' => 'down',
                'request_method' => $method,
                'request_url' => $url,
                'request_headers' => $requestHeaders,
                'request_body' => $monitor->request_body,
                'request_content_type' => $monitor->content_type,
                'response_time' => $responseTime,
                'status_code' => null,
                'response_body' => null,
                'response_headers' => [],
                'error_message' => 'Connection failed: ' . $errorMessage,
                'validation_errors' => [],
                'latency_exceeded' => false,
                'status_code_match' => false,
            ];

        } catch (RequestException $e) {
            $responseTime = round((microtime(true) - $startTime) * 1000);
            $statusCode = $e->response ? $e->response->status() : null;

            // Capture request details
            $requestHeaders = [];
            if ($monitor->request_headers && is_array($monitor->request_headers)) {
                foreach ($monitor->request_headers as $header) {
                    if (isset($header['name']) && isset($header['value'])) {
                        $requestHeaders[$header['name']] = $header['value'];
                    }
                }
            }
            if ($monitor->content_type) {
                $requestHeaders['Content-Type'] = $monitor->content_type;
            }

            return [
                'status' => 'down',
                'request_method' => $method,
                'request_url' => $url,
                'request_headers' => $requestHeaders,
                'request_body' => $monitor->request_body,
                'request_content_type' => $monitor->content_type,
                'response_time' => $responseTime,
                'status_code' => $statusCode,
                'response_body' => $e->response ? $e->response->body() : null,
                'response_headers' => $e->response ? $e->response->headers() : [],
                'error_message' => 'Request failed: ' . $e->getMessage(),
                'validation_errors' => [],
                'latency_exceeded' => false,
                'status_code_match' => false,
            ];

        } catch (\Exception $e) {
            $responseTime = round((microtime(true) - $startTime) * 1000);

            Log::error('API Monitor check failed', [
                'monitor_id' => $monitor->id,
                'url' => $url,
                'error' => $e->getMessage(),
            ]);

            // Capture request details
            $requestHeaders = [];
            if ($monitor->request_headers && is_array($monitor->request_headers)) {
                foreach ($monitor->request_headers as $header) {
                    if (isset($header['name']) && isset($header['value'])) {
                        $requestHeaders[$header['name']] = $header['value'];
                    }
                }
            }
            if ($monitor->content_type) {
                $requestHeaders['Content-Type'] = $monitor->content_type;
            }

            return [
                'status' => 'down',
                'request_method' => $method,
                'request_url' => $url,
                'request_headers' => $requestHeaders,
                'request_body' => $monitor->request_body,
                'request_content_type' => $monitor->content_type,
                'response_time' => $responseTime,
                'status_code' => null,
                'response_body' => null,
                'response_headers' => [],
                'error_message' => 'Unexpected error: ' . $e->getMessage(),
                'validation_errors' => [],
                'latency_exceeded' => false,
                'status_code_match' => false,
            ];
        }
    }

    /**
     * Ensure the monitor has a valid token, refresh if needed.
     *
     * @param ApiMonitor $monitor
     * @return array ['success' => bool, 'should_abort' => bool, 'error' => string|null]
     */
    protected function ensureValidToken(ApiMonitor $monitor): array
    {
        if (!$monitor->auto_auth_enabled || !$monitor->auto_refresh_on_expiry) {
            return ['success' => true, 'should_abort' => false];
        }

        // Check if token is expired or about to expire
        if (!$monitor->isTokenExpired()) {
            return ['success' => true, 'should_abort' => false];
        }

        Log::info("Token expired or about to expire for API monitor {$monitor->id}, refreshing...");

        $oauth2Service = new OAuth2Service();
        $refreshResult = $oauth2Service->refreshToken($monitor);

        if (!$refreshResult['success']) {
            // If refresh fails and we should abort, return error
            // Otherwise, continue with the check (might be a temporary issue)
            return [
                'success' => false,
                'should_abort' => true, // Abort if refresh fails
                'error' => $refreshResult['error'] ?? 'Token refresh failed',
            ];
        }

        // Update monitor with new token
        $updateData = [
            'current_access_token' => $refreshResult['access_token'],
            'token_refreshed_at' => now(),
        ];

        // Calculate expiration time
        if ($refreshResult['expires_in']) {
            $updateData['token_expires_at'] = now()->addSeconds($refreshResult['expires_in']);
        } elseif ($monitor->auto_auth_type === 'jwt' && $refreshResult['access_token']) {
            // Decode JWT to get expiration
            $jwtData = $oauth2Service->decodeJWT($refreshResult['access_token']);
            if ($jwtData && isset($jwtData['exp'])) {
                $updateData['token_expires_at'] = Carbon::createFromTimestamp($jwtData['exp']);
            }
        }

        // Update refresh token if provided
        if (isset($refreshResult['refresh_token'])) {
            $updateData['oauth2_refresh_token'] = $refreshResult['refresh_token'];
        }

        // Update auth_token if using bearer auth
        if ($monitor->auth_type === 'bearer') {
            $updateData['auth_token'] = $refreshResult['access_token'];
        }

        $monitor->update($updateData);
        $monitor->refresh(); // Reload from database

        Log::info("Token refreshed successfully for API monitor {$monitor->id}");

        return ['success' => true, 'should_abort' => false];
    }

    /**
     * Add authentication to HTTP request.
     *
     * @param \Illuminate\Http\Client\PendingRequest $http
     * @param ApiMonitor $monitor
     * @param bool $useAutoToken Use auto-refreshed token if available
     * @return \Illuminate\Http\Client\PendingRequest
     */
    protected function addAuthentication($http, ApiMonitor $monitor, bool $useAutoToken = false)
    {
        // Use auto-refreshed token if available and enabled
        if ($useAutoToken && $monitor->auto_auth_enabled && $monitor->current_access_token) {
            switch ($monitor->auth_type) {
                case 'bearer':
                    $http = $http->withToken($monitor->current_access_token);
                    break;
                
                case 'apikey':
                    if ($monitor->auth_header_name) {
                        $http = $http->withHeader($monitor->auth_header_name, $monitor->current_access_token);
                    }
                    break;
            }
            return $http;
        }

        // Fall back to regular auth
        switch ($monitor->auth_type) {
            case 'bearer':
                if ($monitor->auth_token) {
                    $http = $http->withToken($monitor->auth_token);
                }
                break;

            case 'basic':
                if ($monitor->auth_username && $monitor->auth_password) {
                    $http = $http->withBasicAuth($monitor->auth_username, $monitor->auth_password);
                }
                break;

            case 'apikey':
                if ($monitor->auth_token && $monitor->auth_header_name) {
                    $http = $http->withHeader($monitor->auth_header_name, $monitor->auth_token);
                }
                break;
        }

        return $http;
    }

    /**
     * Validate response body against assertions.
     * Supports: JSON path assertions, regex, conditional logic
     *
     * @param string $responseBody
     * @param array $assertions
     * @param string $contentType
     * @return array Array of validation errors and action results
     */
    protected function validateResponseBody(string $responseBody, array $assertions, string $contentType = 'application/json'): array
    {
        $errors = [];
        $actions = []; // For conditional actions (retry, re-auth, etc.)

        if (empty($assertions)) {
            return ['errors' => $errors, 'actions' => $actions];
        }

        // Parse response based on content type
        $data = null;
        if (stripos($contentType, 'json') !== false) {
            $data = json_decode($responseBody, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $errors[] = 'Invalid JSON response: ' . json_last_error_msg();
                return ['errors' => $errors, 'actions' => $actions];
            }
        } elseif (stripos($contentType, 'xml') !== false) {
            libxml_use_internal_errors(true);
            $data = simplexml_load_string($responseBody);
            if ($data === false) {
                $errors[] = 'Invalid XML response';
                return ['errors' => $errors, 'actions' => $actions];
            }
            $data = json_decode(json_encode($data), true); // Convert to array
        } else {
            // For text/plain or other types, treat as string
            $data = $responseBody;
        }

        // Validate each assertion
        foreach ($assertions as $index => $assertion) {
            if (!isset($assertion['type'])) {
                continue;
            }

            $type = $assertion['type'] ?? 'json_path';
            $path = $assertion['path'] ?? null;
            $operator = $assertion['operator'] ?? 'equals';
            $expectedValue = $assertion['value'] ?? null;
            $condition = $assertion['condition'] ?? null; // For conditional assertions
            $action = $assertion['action'] ?? null; // Action to take if condition is met

            try {
                // Handle conditional assertions (if-then logic)
                if ($type === 'conditional' || $condition) {
                    $conditionData = $type === 'conditional' ? $assertion : ['condition' => $condition, 'action' => $action];
                    $conditionMet = $this->evaluateCondition($data, $conditionData['condition'] ?? [], $contentType);
                    
                    if ($conditionMet && ($conditionData['action'] ?? null)) {
                        // Execute action
                        $actions[] = [
                            'type' => $conditionData['action']['type'] ?? 'none',
                            'value' => $conditionData['action']['value'] ?? null,
                            'message' => $conditionData['action']['message'] ?? null,
                        ];
                        
                        // If action is retry or re-auth, skip further validation
                        if (in_array($conditionData['action']['type'] ?? '', ['retry', 're_auth'])) {
                            continue;
                        }
                    }
                    
                    // If condition is not met, skip this assertion
                    if (!$conditionMet) {
                        continue;
                    }
                    
                    // For conditional type, we're done after evaluating the condition
                    if ($type === 'conditional') {
                        continue;
                    }
                }

                // Handle different assertion types
                switch ($type) {
                    case 'json_path':
                    case 'jsonpath':
                        $actualValue = $this->getValueByPath($data, $path);
                        $isValid = $this->compareValues($actualValue, $operator, $expectedValue);
                        
                        if (!$isValid) {
                            $errors[] = sprintf(
                                'Assertion %d failed: Path "%s" expected %s %s, but got %s',
                                $index + 1,
                                $path ?? 'root',
                                $operator,
                                is_string($expectedValue) ? $expectedValue : json_encode($expectedValue),
                                is_string($actualValue) ? $actualValue : json_encode($actualValue)
                            );
                        }
                        break;

                    case 'regex':
                        // Regex on entire response or specific path
                        $textToMatch = $path ? $this->getValueByPath($data, $path) : $responseBody;
                        if (!is_string($textToMatch)) {
                            $textToMatch = json_encode($textToMatch);
                        }
                        
                        $pattern = $expectedValue ?? $assertion['pattern'] ?? null;
                        if ($pattern && preg_match($pattern, $textToMatch) !== 1) {
                            $errors[] = sprintf(
                                'Assertion %d failed: Regex pattern "%s" did not match in path "%s"',
                                $index + 1,
                                $pattern,
                                $path ?? 'response body'
                            );
                        }
                        break;

                    case 'status_code':
                        // This is handled separately in the check method
                        break;

                    default:
                        // Default to JSON path assertion
                        $actualValue = $this->getValueByPath($data, $path);
                        $isValid = $this->compareValues($actualValue, $operator, $expectedValue);
                        
                        if (!$isValid) {
                            $errors[] = sprintf(
                                'Assertion %d failed: Expected %s %s %s, but got %s',
                                $index + 1,
                                $path ?? 'root',
                                $operator,
                                is_string($expectedValue) ? $expectedValue : json_encode($expectedValue),
                                is_string($actualValue) ? $actualValue : json_encode($actualValue)
                            );
                        }
                }
            } catch (\Exception $e) {
                $errors[] = sprintf('Assertion %d error: %s', $index + 1, $e->getMessage());
            }
        }

        return ['errors' => $errors, 'actions' => $actions];
    }

    /**
     * Evaluate a condition for conditional assertions.
     *
     * @param mixed $data
     * @param array $condition
     * @param string $contentType
     * @return bool
     */
    protected function evaluateCondition($data, array $condition, string $contentType): bool
    {
        $path = $condition['path'] ?? null;
        $operator = $condition['operator'] ?? 'equals';
        $value = $condition['value'] ?? null;

        try {
            $actualValue = $path ? $this->getValueByPath($data, $path) : $data;
            return $this->compareValues($actualValue, $operator, $value);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get value from data by path (JSON path or dot notation).
     * Supports: $.data.user.active, $.data[0].id, $.meta.version, etc.
     *
     * @param mixed $data
     * @param string|null $path
     * @return mixed
     */
    protected function getValueByPath($data, ?string $path)
    {
        if ($path === null || $path === '' || $path === '$') {
            return $data;
        }

        // Remove $ prefix if present (JSON path notation)
        $path = ltrim($path, '$');
        $path = ltrim($path, '.');

        if (empty($path)) {
            return $data;
        }

        // Handle array access with indices like data[0].id or users[0].name
        // Split by . but preserve array indices
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
            // Handle array index like [0] or [1]
            if (preg_match('/^\[(\d+)\]$/', $part, $matches)) {
                $index = (int)$matches[1];
                if (is_array($value) && isset($value[$index])) {
                    $value = $value[$index];
                } else {
                    throw new \Exception("Array index [{$index}] not found in path '{$path}'");
                }
            } else {
                // Handle object key
                if (is_array($value) && isset($value[$part])) {
                    $value = $value[$part];
                } elseif (is_object($value) && isset($value->$part)) {
                    $value = $value->$part;
                } else {
                    throw new \Exception("Path '{$part}' not found in response (full path: \${$path})");
                }
            }
        }

        return $value;
    }

    /**
     * Compare values based on operator.
     *
     * @param mixed $actual
     * @param string $operator
     * @param mixed $expected
     * @return bool
     */
    protected function compareValues($actual, string $operator, $expected): bool
    {
        switch ($operator) {
            case 'equals':
            case '==':
                return $actual == $expected;

            case 'not_equals':
            case '!=':
                return $actual != $expected;

            case 'contains':
                return is_string($actual) && stripos($actual, $expected) !== false;

            case 'not_contains':
                return is_string($actual) && stripos($actual, $expected) === false;

            case 'greater_than':
            case '>':
                return is_numeric($actual) && is_numeric($expected) && $actual > $expected;

            case 'less_than':
            case '<':
                return is_numeric($actual) && is_numeric($expected) && $actual < $expected;

            case 'greater_than_or_equal':
            case '>=':
                return is_numeric($actual) && is_numeric($expected) && $actual >= $expected;

            case 'less_than_or_equal':
            case '<=':
                return is_numeric($actual) && is_numeric($expected) && $actual <= $expected;

            case 'exists':
                return $actual !== null;

            case 'not_exists':
                return $actual === null;

            case 'regex':
                return is_string($actual) && preg_match($expected, $actual) === 1;

            default:
                return false;
        }
    }

    /**
     * Execute a stateful (multi-step) API check.
     * Executes steps in sequence, extracting and using variables between steps.
     *
     * @param ApiMonitor $monitor
     * @return array
     */
    protected function checkStateful(ApiMonitor $monitor): array
    {
        $overallStartTime = microtime(true);
        $steps = $monitor->monitoring_steps ?? [];
        $variables = []; // Store extracted variables
        $stepResults = [];
        $allValidationErrors = [];
        $totalResponseTime = 0;
        $overallStatus = 'up';

        // Sort steps by step number
        usort($steps, function($a, $b) {
            return ($a['step'] ?? 0) <=> ($b['step'] ?? 0);
        });

        foreach ($steps as $stepIndex => $step) {
            $stepNumber = $step['step'] ?? ($stepIndex + 1);
            $stepName = $step['name'] ?? "Step {$stepNumber}";
            
            try {
                // Substitute variables in URL, headers, and body
                $stepUrl = $this->substituteVariables($step['url'] ?? $monitor->url, $variables);
                $stepMethod = strtoupper($step['method'] ?? $monitor->request_method ?? 'GET');
                $stepHeaders = $this->substituteVariablesInArray($step['headers'] ?? $monitor->request_headers ?? [], $variables);
                $stepBody = $this->substituteVariables($step['body'] ?? $monitor->request_body ?? null, $variables);
                $stepTimeout = $step['timeout'] ?? $monitor->timeout ?? 30;
                $stepCheckSsl = $step['check_ssl'] ?? $monitor->check_ssl ?? true;

                // Execute the step
                $stepResult = $this->executeStep(
                    $monitor,
                    $stepUrl,
                    $stepMethod,
                    $stepHeaders,
                    $stepBody,
                    $stepTimeout,
                    $stepCheckSsl,
                    $step['expected_status_code'] ?? $monitor->expected_status_code ?? 200,
                    $step['response_assertions'] ?? $monitor->response_assertions ?? [],
                    $monitor->content_type
                );

                $stepResults[] = [
                    'step' => $stepNumber,
                    'name' => $stepName,
                    'status' => $stepResult['status'],
                    'response_time' => $stepResult['response_time'],
                    'status_code' => $stepResult['status_code'],
                    'error_message' => $stepResult['error_message'],
                    'validation_errors' => $stepResult['validation_errors'] ?? [],
                ];

                $totalResponseTime += $stepResult['response_time'];

                // If step failed, mark overall as down
                if ($stepResult['status'] === 'down') {
                    $overallStatus = 'down';
                    // Optionally continue or break on first failure
                    if ($step['break_on_failure'] ?? false) {
                        break;
                    }
                }

                // Extract variables from this step's response
                if (!empty($step['extract_variables']) && $stepResult['status'] === 'up') {
                    $extracted = $this->extractVariables(
                        $stepResult['response_body'] ?? '',
                        $step['extract_variables'],
                        $monitor->content_type
                    );
                    $variables = array_merge($variables, $extracted);
                }

                // Collect validation errors
                if (!empty($stepResult['validation_errors'])) {
                    $allValidationErrors = array_merge($allValidationErrors, $stepResult['validation_errors']);
                }

            } catch (\Exception $e) {
                Log::error("Error executing step {$stepNumber} for API monitor {$monitor->id}", [
                    'error' => $e->getMessage(),
                    'step' => $step,
                ]);

                $stepResults[] = [
                    'step' => $stepNumber,
                    'name' => $stepName,
                    'status' => 'down',
                    'response_time' => 0,
                    'status_code' => null,
                    'error_message' => 'Step execution failed: ' . $e->getMessage(),
                    'validation_errors' => [],
                ];

                $overallStatus = 'down';
                
                if ($step['break_on_failure'] ?? false) {
                    break;
                }
            }
        }

        $overallResponseTime = round((microtime(true) - $overallStartTime) * 1000);

        return [
            'status' => $overallStatus,
            'response_time' => $overallResponseTime,
            'status_code' => $stepResults[count($stepResults) - 1]['status_code'] ?? null,
            'response_body' => json_encode([
                'steps' => $stepResults,
                'variables' => $variables,
            ], JSON_PRETTY_PRINT),
            'error_message' => $overallStatus === 'down' ? 'One or more steps failed' : null,
            'validation_errors' => $allValidationErrors,
            'latency_exceeded' => $monitor->max_latency_ms && $overallResponseTime > $monitor->max_latency_ms,
            'status_code_match' => $overallStatus === 'up',
            'should_retry' => false,
            'retry_after' => null,
            'needs_re_auth' => false,
            'auth_error' => null,
            'extracted_variables' => $variables,
            'step_results' => $stepResults,
        ];
    }

    /**
     * Execute a single step in a multi-step flow.
     *
     * @param ApiMonitor $monitor
     * @param string $url
     * @param string $method
     * @param array $headers
     * @param mixed $body
     * @param int $timeout
     * @param bool $checkSsl
     * @param int $expectedStatusCode
     * @param array $assertions
     * @param string $contentType
     * @return array
     */
    protected function executeStep(
        ApiMonitor $monitor,
        string $url,
        string $method,
        array $headers,
        $body,
        int $timeout,
        bool $checkSsl,
        int $expectedStatusCode,
        array $assertions,
        string $contentType
    ): array {
        $startTime = microtime(true);
        $connectTimeout = min(10, $timeout);

        try {
            // Build HTTP request
            $http = Http::timeout($timeout)
                ->withOptions([
                    'curl' => [
                        CURLOPT_FOLLOWLOCATION => true,
                        CURLOPT_MAXREDIRS => 5,
                        CURLOPT_AUTOREFERER => true,
                        CURLOPT_CONNECTTIMEOUT => $connectTimeout,
                        CURLOPT_TIMEOUT => $timeout,
                        CURLOPT_TIMEOUT_MS => $timeout * 1000,
                        CURLOPT_SSL_VERIFYPEER => $checkSsl,
                        CURLOPT_SSL_VERIFYHOST => $checkSsl ? 2 : 0,
                    ],
                ]);

            // Add authentication (use monitor's auth settings)
            $http = $this->addAuthentication($http, $monitor);

            // Add custom headers
            if (is_array($headers)) {
                foreach ($headers as $header) {
                    if (isset($header['name']) && isset($header['value'])) {
                        $http = $http->withHeader($header['name'], $header['value']);
                    }
                }
            }

            // Add content type
            if ($contentType) {
                $http = $http->withHeaders(['Content-Type' => $contentType]);
            }

            // Make the request
            $requestBody = null;
            if (in_array($method, ['POST', 'PUT', 'PATCH']) && $body) {
                $requestBody = is_string($body) ? $body : json_encode($body);
            }

            $response = $http->send($method, $url, $requestBody ? ['body' => $requestBody] : []);

            $responseTime = round((microtime(true) - $startTime) * 1000);
            $statusCode = $response->status();
            $responseBody = $response->body();

            // Check status code
            $statusCodeMatch = ($statusCode == $expectedStatusCode);

            // Validate response body if assertions are provided
            $validationErrors = [];
            if (!empty($assertions)) {
                $validationResult = $this->validateResponseBody($responseBody, $assertions, $contentType);
                $validationErrors = $validationResult['errors'] ?? [];
            }

            // Determine status
            $isUp = $statusCodeMatch && empty($validationErrors);

            return [
                'status' => $isUp ? 'up' : 'down',
                'response_time' => $responseTime,
                'status_code' => $statusCode,
                'response_body' => $responseBody,
                'error_message' => null,
                'validation_errors' => $validationErrors,
                'status_code_match' => $statusCodeMatch,
            ];

        } catch (\Exception $e) {
            $responseTime = round((microtime(true) - $startTime) * 1000);
            return [
                'status' => 'down',
                'response_time' => $responseTime,
                'status_code' => null,
                'response_body' => null,
                'error_message' => 'Request failed: ' . $e->getMessage(),
                'validation_errors' => [],
                'status_code_match' => false,
            ];
        }
    }

    /**
     * Extract variables from a response using extraction rules.
     *
     * @param string $responseBody
     * @param array $extractionRules
     * @param string $contentType
     * @return array Extracted variables as key-value pairs
     */
    protected function extractVariables(string $responseBody, array $extractionRules, string $contentType = 'application/json'): array
    {
        $variables = [];

        // Parse response based on content type
        $data = null;
        if (stripos($contentType, 'json') !== false) {
            $data = json_decode($responseBody, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::warning('Failed to parse JSON for variable extraction', [
                    'error' => json_last_error_msg(),
                ]);
                return $variables;
            }
        } elseif (stripos($contentType, 'xml') !== false) {
            libxml_use_internal_errors(true);
            $data = simplexml_load_string($responseBody);
            if ($data === false) {
                return $variables;
            }
            $data = json_decode(json_encode($data), true);
        } else {
            // For text/plain, treat as string
            $data = $responseBody;
        }

        // Extract each variable
        foreach ($extractionRules as $rule) {
            $variableName = $rule['name'] ?? null;
            $path = $rule['path'] ?? null;

            if (!$variableName) {
                continue;
            }

            try {
                if ($path) {
                    $value = $this->getValueByPath($data, $path);
                    $variables[$variableName] = $value;
                } else {
                    // If no path, use the entire response
                    $variables[$variableName] = $data;
                }
            } catch (\Exception $e) {
                Log::warning("Failed to extract variable '{$variableName}' from path '{$path}'", [
                    'error' => $e->getMessage(),
                ]);
                // Continue with other variables
            }
        }

        return $variables;
    }

    /**
     * Substitute variables in a string using {{variable_name}} syntax.
     *
     * @param string|null $text
     * @param array $variables
     * @return string|null
     */
    protected function substituteVariables(?string $text, array $variables): ?string
    {
        if (!$text) {
            return $text;
        }

        foreach ($variables as $name => $value) {
            $placeholder = '{{' . $name . '}}';
            $text = str_replace($placeholder, (string)$value, $text);
        }

        return $text;
    }

    /**
     * Substitute variables in an array (for headers, etc.).
     *
     * @param array $data
     * @param array $variables
     * @return array
     */
    protected function substituteVariablesInArray(array $data, array $variables): array
    {
        $result = [];

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $result[$key] = $this->substituteVariablesInArray($value, $variables);
            } elseif (is_string($value)) {
                $result[$key] = $this->substituteVariables($value, $variables);
            } else {
                $result[$key] = $value;
            }
        }

        return $result;
    }
}


