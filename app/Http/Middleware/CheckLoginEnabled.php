<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckLoginEnabled
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $loginEnabled = Setting::get('user_login_enabled', '1');
        
        if ($loginEnabled !== '1') {
            // For GET requests (viewing the login page), allow it to proceed
            // The controller will handle showing the error message
            if ($request->isMethod('GET')) {
                return $next($request);
            }
            
            // For POST requests (attempting to login), block it
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'User login is currently disabled.',
                ], 403);
            }
            
            // Redirect to login page with error (not back, to avoid loop)
            return redirect()->route('login')
                ->with('error', 'User login is currently disabled. Please contact the administrator.');
        }
        
        return $next($request);
    }
}
