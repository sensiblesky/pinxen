<?php

namespace App\Http\Middleware;

use App\Models\ApiKey;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateApiKey
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = $request->header('X-API-Key') ?? $request->input('api_key');
        
        if (!$apiKey) {
            return response()->json([
                'success' => false,
                'message' => 'API key is required. Provide it via X-API-Key header or api_key parameter.'
            ], 401);
        }

        // Remove 'pk_' prefix if present (for backward compatibility)
        $apiKey = str_replace('pk_', '', $apiKey);
        $apiKey = 'pk_' . $apiKey;

        $key = ApiKey::where('key', $apiKey)
            ->where('is_active', true)
            ->first();

        if (!$key) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or inactive API key.'
            ], 401);
        }

        // Check if key is expired
        if ($key->expires_at && $key->expires_at->isPast()) {
            return response()->json([
                'success' => false,
                'message' => 'API key has expired.'
            ], 401);
        }

        // Check IP whitelist if configured
        $clientIp = $request->ip();
        if (!$key->isIpAllowed($clientIp)) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied from this IP address.'
            ], 403);
        }

        // Check scope (for server monitoring, we need at least 'create' or '*')
        if (!$key->hasAnyScope(['create', '*'])) {
            return response()->json([
                'success' => false,
                'message' => 'API key does not have required permissions. Server stats require "create" or "*" scope.'
            ], 403);
        }

        // Update last used timestamp
        $key->updateLastUsed();

        // Attach API key to request for use in controllers
        $request->merge(['api_key' => $key]);

        return $next($request);
    }
}
