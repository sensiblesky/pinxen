<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForceEmailVerification
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if force email verification is enabled
        $forceEmailVerification = Setting::get('force_email_verification', '0');
        
        if ($forceEmailVerification === '1' && $request->user()) {
            $user = $request->user();
            
            // Allow access to email verification routes and logout
            $allowedRoutes = [
                'email.verification.*',
                'verify-email*',
                'verification.*',
                'logout',
                'account.security.two-factor*', // Allow 2FA setup even if email not verified
            ];
            
            $isAllowedRoute = false;
            foreach ($allowedRoutes as $routePattern) {
                if ($request->routeIs($routePattern)) {
                    $isAllowedRoute = true;
                    break;
                }
            }
            
            if ($isAllowedRoute) {
                return $next($request);
            }
            
            // Check if email is verified
            if (!$user->email_verified_at) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'message' => 'Please verify your email address to continue.',
                        'requires_verification' => true,
                    ], 403);
                }
                
                return redirect()->route('email.verification.show')
                    ->with('error', 'Please verify your email address to continue.');
            }
        }
        
        return $next($request);
    }
}
