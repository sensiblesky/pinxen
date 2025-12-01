<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForceTwoFactorAuthentication
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if force 2FA is enabled
        $force2FA = Setting::get('force_two_factor_authentication', '0');
        
        if ($force2FA === '1' && $request->user()) {
            $user = $request->user();
            
            // Allow access to 2FA setup routes and logout
            if ($request->routeIs('account.security.two-factor*') || 
                $request->routeIs('two-factor.*') ||
                $request->routeIs('logout')) {
                return $next($request);
            }
            
            // Check if 2FA is enabled for the user
            if (!$user->two_factor_enabled) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'message' => 'Two-factor authentication is required. Please set it up to continue.',
                        'requires_2fa' => true,
                    ], 403);
                }
                
                return redirect()->route('account.security.two-factor')
                    ->with('error', 'Two-factor authentication is required for all accounts. Please set it up to continue.');
            }
        }
        
        return $next($request);
    }
}
