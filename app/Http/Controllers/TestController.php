<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class TestController extends Controller
{
    /**
     * Basic test endpoint - returns 200 OK
     */
    public function basic(): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'message' => 'Basic test endpoint is working',
            'timestamp' => now()->toIso8601String(),
            'method' => request()->method(),
        ], 200);
    }

    /**
     * Test endpoint that REQUIRES Basic Authentication
     * This endpoint will FAIL (401) without Basic Auth
     * This endpoint will SUCCEED (200) with correct Basic Auth
     */
    public function basicAuth(): JsonResponse
    {
        $username = null;
        $password = null;

        // Check if Authorization header is present (for HTTP Basic Auth)
        $authHeader = request()->header('Authorization');
        if ($authHeader && strpos($authHeader, 'Basic ') === 0) {
            $credentials = base64_decode(substr($authHeader, 6));
            [$username, $password] = explode(':', $credentials, 2);
        }

        // Also check PHP_AUTH_USER and PHP_AUTH_PW (for some server configurations)
        if (!$username) {
            $username = request()->header('PHP_AUTH_USER');
            $password = request()->header('PHP_AUTH_PW');
        }

        // REQUIRED: Must have Basic Auth
        if (!$username || !$password) {
            return response()->json([
                'status' => 'error',
                'message' => 'Basic Authentication REQUIRED',
                'error' => 'This endpoint requires Basic Authentication. Configure Basic Auth Username and Password in your monitor settings.',
                'hint' => 'Use username: testuser and password: testpass',
            ], 401);
        }

        // Expected credentials: testuser / testpass
        if ($username === 'testuser' && $password === 'testpass') {
            return response()->json([
                'status' => 'success',
                'message' => 'Basic Authentication successful - Monitor will pass',
                'authenticated_user' => $username,
                'timestamp' => now()->toIso8601String(),
                'method' => request()->method(),
            ], 200);
        }

        return response()->json([
            'status' => 'error',
            'message' => 'Invalid Basic Authentication credentials',
            'error' => 'Wrong username or password. Monitor will fail.',
            'hint' => 'Use username: testuser and password: testpass',
        ], 401);
    }

    /**
     * Test endpoint that REQUIRES custom headers
     * This endpoint will FAIL (403) without required custom headers
     * This endpoint will SUCCEED (200) with correct custom headers
     */
    public function customHeaders(): JsonResponse
    {
        $headers = request()->headers->all();
        $customHeaders = [];

        // Filter out standard headers and show only custom ones
        $standardHeaders = ['host', 'user-agent', 'accept', 'accept-language', 'accept-encoding', 'connection', 'content-type', 'content-length', 'authorization'];
        
        foreach ($headers as $key => $value) {
            $normalizedKey = strtolower(str_replace('HTTP_', '', $key));
            if (!in_array($normalizedKey, $standardHeaders) && !in_array($key, $standardHeaders)) {
                $customHeaders[$key] = is_array($value) ? $value[0] : $value;
            }
        }

        // REQUIRED: Must have X-API-Key header
        $apiKey = request()->header('X-API-Key');
        if (!$apiKey) {
            return response()->json([
                'status' => 'error',
                'message' => 'Custom Header REQUIRED',
                'error' => 'This endpoint requires X-API-Key header. Configure Custom Request Headers in your monitor settings.',
                'hint' => 'Add header: X-API-Key: test-key-123',
                'received_headers' => $customHeaders,
            ], 403);
        }

        // Check if API key is correct
        if ($apiKey === 'test-key-123') {
            return response()->json([
                'status' => 'success',
                'message' => 'Custom headers validated - Monitor will pass',
                'custom_headers' => $customHeaders,
                'validated_header' => 'X-API-Key',
                'timestamp' => now()->toIso8601String(),
                'method' => request()->method(),
            ], 200);
        }

        return response()->json([
            'status' => 'error',
            'message' => 'Invalid custom header value',
            'error' => 'X-API-Key value is incorrect. Monitor will fail.',
            'hint' => 'Use X-API-Key: test-key-123',
            'received_value' => $apiKey,
        ], 403);
    }

    /**
     * Test endpoint that REQUIRES cache buster parameter
     * This endpoint will FAIL (400) without cache buster
     * This endpoint will SUCCEED (200) with cache buster
     */
    public function cacheBuster(): JsonResponse
    {
        $queryParams = request()->query();
        $hasCacheBuster = false;
        $cacheBusterValue = null;

        // Check for common cache buster parameter names
        $cacheBusterParams = ['_', 'cache_buster', 'cb', 'nocache', 't', 'timestamp', 'v', 'version'];
        
        foreach ($cacheBusterParams as $param) {
            if (isset($queryParams[$param])) {
                $hasCacheBuster = true;
                $cacheBusterValue = $queryParams[$param];
                break;
            }
        }

        // REQUIRED: Must have cache buster
        if (!$hasCacheBuster) {
            return response()->json([
                'status' => 'error',
                'message' => 'Cache Buster REQUIRED',
                'error' => 'This endpoint requires a cache buster parameter. Enable Cache Buster in your monitor settings.',
                'hint' => 'Enable Cache Buster option in monitor advanced settings',
                'query_params_received' => $queryParams,
            ], 400);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Cache buster validated - Monitor will pass',
            'has_cache_buster' => true,
            'cache_buster_value' => $cacheBusterValue,
            'timestamp' => now()->toIso8601String(),
            'method' => request()->method(),
        ], 200);
    }

    /**
     * Test endpoint that returns different status codes based on query parameter
     */
    public function statusCode(): JsonResponse
    {
        $code = request()->query('code', 200);
        $code = (int)$code;

        // Validate status code range
        if ($code < 100 || $code > 599) {
            $code = 200;
        }

        $messages = [
            200 => 'OK',
            201 => 'Created',
            204 => 'No Content',
            301 => 'Moved Permanently',
            302 => 'Found',
            400 => 'Bad Request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not Found',
            500 => 'Internal Server Error',
            502 => 'Bad Gateway',
            503 => 'Service Unavailable',
            504 => 'Gateway Timeout',
        ];

        return response()->json([
            'status' => $code >= 200 && $code < 300 ? 'success' : 'error',
            'message' => $messages[$code] ?? 'Custom Status Code',
            'status_code' => $code,
            'timestamp' => now()->toIso8601String(),
            'method' => request()->method(),
        ], $code);
    }

    /**
     * Test endpoint that simulates slow response
     */
    public function slowResponse(): JsonResponse
    {
        $delay = (int)request()->query('delay', 3);
        $delay = min($delay, 10); // Max 10 seconds

        sleep($delay);

        return response()->json([
            'status' => 'success',
            'message' => 'Slow response test',
            'delay_seconds' => $delay,
            'timestamp' => now()->toIso8601String(),
            'method' => request()->method(),
        ], 200);
    }

    /**
     * Test endpoint that REQUIRES POST method (not GET)
     * This endpoint will FAIL (405) with GET method
     * This endpoint will SUCCEED (200) with POST method
     */
    public function methodTest(): JsonResponse
    {
        $method = request()->method();

        // REQUIRED: Must use POST method
        if ($method !== 'POST') {
            return response()->json([
                'status' => 'error',
                'message' => 'Wrong HTTP Method',
                'error' => 'This endpoint requires POST method. Configure Request Method = POST in your monitor settings.',
                'received_method' => $method,
                'required_method' => 'POST',
            ], 405);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Correct HTTP method used - Monitor will pass',
            'received_method' => $method,
            'timestamp' => now()->toIso8601String(),
        ], 200);
    }

    /**
     * Comprehensive test endpoint that REQUIRES all advanced options
     * This endpoint will FAIL if any required option is missing
     * This endpoint will SUCCEED only if all options are correctly configured
     */
    public function comprehensive(): JsonResponse
    {
        Log::info('Comprehensive test endpoint called', [
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'timestamp' => now()->toIso8601String(),
        ]);

        $errors = [];
        $method = request()->method();
        
        Log::debug('Comprehensive test - Request method check', [
            'method' => $method,
            'expected' => 'POST',
            'match' => $method === 'POST',
        ]);
        
        // REQUIRED: Must use POST method
        if ($method !== 'POST') {
            $errors[] = 'Request Method must be POST. Configure Request Method = POST in monitor settings.';
            Log::warning('Comprehensive test - Wrong HTTP method', ['method' => $method]);
        }

        // REQUIRED: Must have Basic Auth
        $username = null;
        $password = null;
        $authHeader = request()->header('Authorization');
        
        Log::debug('Comprehensive test - Basic Auth check', [
            'auth_header_present' => $authHeader !== null,
            'auth_header_preview' => $authHeader ? substr($authHeader, 0, 20) . '...' : null,
        ]);
        
        if ($authHeader && strpos($authHeader, 'Basic ') === 0) {
            $credentials = base64_decode(substr($authHeader, 6));
            [$username, $password] = explode(':', $credentials, 2);
            Log::debug('Comprehensive test - Basic Auth extracted', [
                'username' => $username,
                'password_provided' => $password !== null && $password !== '',
            ]);
        }

        if (!$username || !$password) {
            $errors[] = 'Basic Authentication REQUIRED. Configure Basic Auth Username and Password in monitor settings.';
            Log::warning('Comprehensive test - Basic Auth missing');
        } elseif ($username !== 'testuser' || $password !== 'testpass') {
            $errors[] = 'Invalid Basic Auth credentials. Use username: testuser, password: testpass';
            Log::warning('Comprehensive test - Invalid Basic Auth credentials', [
                'username' => $username,
                'password_match' => $password === 'testpass',
            ]);
        } else {
            Log::debug('Comprehensive test - Basic Auth validated successfully');
        }

        // REQUIRED: Must have X-API-Key header
        $apiKey = request()->header('X-API-Key');
        Log::debug('Comprehensive test - Custom header check', [
            'x_api_key_present' => $apiKey !== null,
            'x_api_key_value' => $apiKey,
        ]);
        
        // Note: Currently commented out but keeping for future use
        // if (!$apiKey) {
        //     $errors[] = 'Custom Header REQUIRED. Add X-API-Key header in Custom Request Headers.';
        // } elseif ($apiKey !== 'test-key-123') {
        //     $errors[] = 'Invalid X-API-Key value. Use X-API-Key: test-key-123';
        // }

        // REQUIRED: Must have cache buster
        $queryParams = request()->query();
        $hasCacheBuster = false;
        $cacheBusterValue = null;
        $cacheBusterParams = ['_', 'cache_buster', 'cb', '_cb', 'nocache', 't', 'timestamp', 'v', 'version'];
        
        Log::debug('Comprehensive test - Cache buster check', [
            'query_params' => $queryParams,
            'checking_params' => $cacheBusterParams,
        ]);
        
        foreach ($cacheBusterParams as $param) {
            if (isset($queryParams[$param])) {
                $hasCacheBuster = true;
                $cacheBusterValue = $queryParams[$param];
                Log::debug('Comprehensive test - Cache buster found', [
                    'param' => $param,
                    'value' => $cacheBusterValue,
                ]);
                break;
            }
        }

        if (!$hasCacheBuster) {
            $errors[] = 'Cache Buster REQUIRED. Enable Cache Buster in monitor settings.';
            Log::warning('Comprehensive test - Cache buster missing', [
                'query_params' => $queryParams,
            ]);
        } else {
            Log::debug('Comprehensive test - Cache buster validated', [
                'value' => $cacheBusterValue,
            ]);
        }

        // Log all headers for debugging
        Log::debug('Comprehensive test - All request headers', [
            'headers' => request()->headers->all(),
        ]);

        // If any errors, return failure
        if (!empty($errors)) {
            Log::warning('Comprehensive test - Validation failed', [
                'errors' => $errors,
                'method' => $method,
                'basic_auth_provided' => $username !== null,
                'api_key_provided' => $apiKey !== null,
                'cache_buster_detected' => $hasCacheBuster,
                'cache_buster_value' => $cacheBusterValue,
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Missing or incorrect advanced options',
                'errors' => $errors,
                'received' => [
                    'method' => $method,
                    'basic_auth_provided' => $username !== null,
                    'api_key_provided' => $apiKey !== null,
                    'cache_buster_detected' => $hasCacheBuster,
                    'cache_buster_value' => $cacheBusterValue,
                ],
                'hint' => 'Configure all advanced options correctly for monitor to pass',
            ], 400);
        }

        // All checks passed
        Log::info('Comprehensive test - All validations passed', [
            'method' => $method,
            'username' => $username,
            'api_key' => $apiKey,
            'cache_buster_value' => $cacheBusterValue,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'All advanced options validated - Monitor will pass',
            'method' => $method,
            'basic_auth' => [
                'validated' => true,
                'username' => $username,
            ],
            'custom_headers' => [
                'validated' => true,
                'X-API-Key' => $apiKey,
            ],
            'cache_buster' => [
                'validated' => true,
                'query_params' => $queryParams,
                'value' => $cacheBusterValue,
            ],
            'timestamp' => now()->toIso8601String(),
        ], 200);
    }

    /**
     * Test endpoint that returns error (for testing error handling)
     */
    public function error(): JsonResponse
    {
        return response()->json([
            'status' => 'error',
            'message' => 'This is a test error endpoint',
            'error_code' => 'TEST_ERROR',
            'timestamp' => now()->toIso8601String(),
        ], 500);
    }

    /**
     * Test endpoint that checks keyword presence
     */
    public function keywordTest(): JsonResponse
    {
        $keyword = request()->query('keyword', 'test');
        
        return response()->json([
            'status' => 'success',
            'message' => 'Keyword test endpoint',
            'keyword' => $keyword,
            'content' => "This response contains the keyword: {$keyword}",
            'timestamp' => now()->toIso8601String(),
        ], 200);
    }
}

