<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\LoginActivity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        // Double-check login is enabled (middleware should handle this, but this is a backup)
        $loginEnabled = \App\Models\Setting::get('user_login_enabled', '1');
        $isDisabled = $loginEnabled !== '1';
        
        if ($isDisabled) {
            return view('auth.pages.login', [
                'loginDisabled' => true,
            ])->with('error', 'User login is currently disabled. Please contact the administrator.');
        }
        
        return view('auth.pages.login', [
            'loginDisabled' => false,
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        // Double-check login is enabled
        $loginEnabled = \App\Models\Setting::get('user_login_enabled', '1');
        
        if ($loginEnabled !== '1') {
            return redirect()->back()
                ->with('error', 'User login is currently disabled. Please contact the administrator.');
        }
        
        $request->authenticate();

        $user = Auth::user();
        
        // Check if user has 2FA enabled
        if ($user->two_factor_enabled) {
            // Store user ID in session for 2FA verification
            $request->session()->put('login.id', $user->id);
            $request->session()->put('login.remember', $request->boolean('remember'));
            
            // Logout the user temporarily until 2FA is verified
            Auth::logout();
            
            // Redirect to 2FA verification page
            return redirect()->route('two-factor.login');
        }

        $request->session()->regenerate();
        
        // Get session ID after regeneration
        $sessionId = $request->session()->getId();

        // Log login activity and bind session
        $this->logLoginActivity($request, $user, $sessionId);

        // Check if force email verification is enabled and user email is not verified
        $forceEmailVerification = \App\Models\Setting::get('force_email_verification', '0');
        if ($forceEmailVerification === '1' && !$user->email_verified_at) {
            return redirect()->route('email.verification.show')
                ->with('error', 'Please verify your email address to continue.');
        }

        // Redirect based on user role
        if ($user->role == 1) {
            // Admin - redirect to panel
            return redirect()->intended(route('panel', absolute: false));
        } else {
            // Client - redirect to dashboard
            return redirect()->intended(route('dashboard', absolute: false));
        }
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $user = Auth::user();
        $sessionId = $request->session()->getId();
        
        // Log logout activity and mark session as inactive
        if ($user) {
            $this->logLogoutActivity($request, $user, $sessionId);
        }

        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    /**
     * Log login activity.
     */
    private function logLoginActivity(Request $request, $user, $sessionId)
    {
        $userAgent = $request->userAgent();
        $deviceInfo = $this->parseUserAgent($userAgent);

        LoginActivity::create([
            'user_id' => $user->id,
            'session_id' => $sessionId,
            'ip_address' => $request->ip(),
            'user_agent' => $userAgent,
            'device_type' => $deviceInfo['device_type'],
            'browser' => $deviceInfo['browser'],
            'platform' => $deviceInfo['platform'],
            'location' => null, // Can be enhanced with IP geolocation service
            'action' => 'login',
            'logged_in_at' => now(),
            'is_active' => true,
        ]);
    }

    /**
     * Log logout activity.
     */
    private function logLogoutActivity(Request $request, $user, $sessionId)
    {
        // Update the login activity for this specific session
        $activeSession = LoginActivity::where('user_id', $user->id)
            ->where('session_id', $sessionId)
            ->where('is_active', true)
            ->whereNotNull('logged_in_at')
            ->whereNull('logged_out_at')
            ->first();

        if ($activeSession) {
            $activeSession->update([
                'logged_out_at' => now(),
                'is_active' => false,
                'action' => 'logout',
            ]);
        } else {
            // Fallback: Update the most recent active session if session_id doesn't match
            $activeSession = LoginActivity::where('user_id', $user->id)
                ->where('is_active', true)
                ->whereNotNull('logged_in_at')
                ->whereNull('logged_out_at')
                ->where('ip_address', $request->ip())
                ->latest('logged_in_at')
                ->first();

            if ($activeSession) {
                $activeSession->update([
                    'logged_out_at' => now(),
                    'is_active' => false,
                    'action' => 'logout',
                ]);
            }
        }
    }

    /**
     * Parse user agent to extract device information.
     */
    private function parseUserAgent($userAgent)
    {
        $deviceType = 'desktop';
        $browser = 'Unknown';
        $platform = 'Unknown';

        // Detect device type
        if (preg_match('/mobile|android|iphone|ipad|ipod|blackberry|iemobile|opera mini/i', $userAgent)) {
            $deviceType = 'mobile';
        } elseif (preg_match('/tablet|ipad|playbook|silk/i', $userAgent)) {
            $deviceType = 'tablet';
        }

        // Detect browser
        if (preg_match('/chrome/i', $userAgent) && !preg_match('/edg/i', $userAgent)) {
            $browser = 'Chrome';
        } elseif (preg_match('/firefox/i', $userAgent)) {
            $browser = 'Firefox';
        } elseif (preg_match('/safari/i', $userAgent) && !preg_match('/chrome/i', $userAgent)) {
            $browser = 'Safari';
        } elseif (preg_match('/edg/i', $userAgent)) {
            $browser = 'Edge';
        } elseif (preg_match('/opera|opr/i', $userAgent)) {
            $browser = 'Opera';
        }

        // Detect platform
        if (preg_match('/windows/i', $userAgent)) {
            $platform = 'Windows';
        } elseif (preg_match('/macintosh|mac os x/i', $userAgent)) {
            $platform = 'macOS';
        } elseif (preg_match('/linux/i', $userAgent)) {
            $platform = 'Linux';
        } elseif (preg_match('/android/i', $userAgent)) {
            $platform = 'Android';
        } elseif (preg_match('/iphone|ipad|ipod/i', $userAgent)) {
            $platform = 'iOS';
        }

        return [
            'device_type' => $deviceType,
            'browser' => $browser,
            'platform' => $platform,
        ];
    }
}

