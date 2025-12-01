<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRegistrationEnabled
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $registrationEnabled = Setting::get('user_registration_enabled', '1');
        
        if ($registrationEnabled !== '1') {
            // For GET requests (viewing the registration page), allow it to proceed
            // The controller will handle showing the error message
            if ($request->isMethod('GET')) {
                return $next($request);
            }
            
            // For POST requests (attempting to register), block it
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'User registration is currently disabled.',
                ], 403);
            }
            
            // Redirect to login page with error
            return redirect()->route('login')
                ->with('error', 'User registration is currently disabled. Please contact the administrator.');
        }
        
        return $next($request);
    }
}
