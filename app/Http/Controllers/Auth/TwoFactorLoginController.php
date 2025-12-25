<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorLoginController extends Controller
{
    protected $google2fa;

    public function __construct()
    {
        $this->google2fa = new Google2FA();
    }

    /**
     * Show the two-factor authentication login form.
     */
    public function show(Request $request): View|RedirectResponse
    {
        // Check if user ID is stored in session
        if (!$request->session()->has('login.id')) {
            return redirect()->route('login')
                ->with('error', 'Please log in first.');
        }

        $userId = $request->session()->get('login.id');
        $user = User::find($userId);

        if (!$user || !$user->two_factor_enabled) {
            $request->session()->forget(['login.id', 'login.remember']);
            return redirect()->route('login')
                ->with('error', 'Two-factor authentication is not enabled for this account.');
        }

        return view('auth.two-factor-login', [
            'user' => $user,
        ]);
    }

    /**
     * Verify the two-factor authentication code and complete login.
     */
    public function verify(Request $request): RedirectResponse
    {
        // Check if user ID is stored in session
        if (!$request->session()->has('login.id')) {
            return redirect()->route('login')
                ->with('error', 'Session expired. Please log in again.');
        }

        $userId = $request->session()->get('login.id');
        $user = User::find($userId);

        if (!$user || !$user->two_factor_enabled) {
            $request->session()->forget(['login.id', 'login.remember']);
            return redirect()->route('login')
                ->with('error', 'Two-factor authentication is not enabled for this account.');
        }

        $validated = $request->validate([
            'code' => ['required', 'string', 'min:6', 'max:8'],
        ]);

        $code = strtoupper(trim($validated['code']));
        $valid = false;

        // Check if it's a recovery code (8 characters) or authenticator code (6 digits)
        if (strlen($code) === 8 && $user->two_factor_recovery_codes) {
            // Try recovery code first
            $recoveryCodes = json_decode(decrypt($user->two_factor_recovery_codes), true);
            if (is_array($recoveryCodes) && in_array($code, $recoveryCodes)) {
                // Remove used recovery code
                $recoveryCodes = array_values(array_diff($recoveryCodes, [$code]));
                $user->update([
                    'two_factor_recovery_codes' => encrypt(json_encode($recoveryCodes)),
                ]);
                $valid = true;
            }
        } else {
            // Verify the 2FA code (6 digits)
            if (!$user->two_factor_secret) {
                $request->session()->forget(['login.id', 'login.remember']);
                return redirect()->route('login')
                    ->with('error', 'Two-factor authentication is not properly configured.');
            }

            $secret = decrypt($user->two_factor_secret);
            $valid = $this->google2fa->verifyKey($secret, $code);
        }

        if (!$valid) {
            $errorMessage = strlen($code) === 8 
                ? 'The recovery code is invalid or has already been used.' 
                : 'The verification code is invalid. Please check your authenticator app or use a recovery code.';
            
            return redirect()->route('two-factor.login')
                ->withErrors(['code' => $errorMessage])
                ->withInput();
        }

        // Get remember me preference
        $remember = $request->session()->get('login.remember', false);

        // Clear login session data
        $request->session()->forget(['login.id', 'login.remember']);

        // Regenerate session
        $request->session()->regenerate();

        // Log the user in
        Auth::login($user, $remember);

        // Get session ID after login
        $sessionId = $request->session()->getId();

        // Log login activity
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
            return redirect()->intended(route('panel', absolute: false))
                ->with('success', 'Login successful!');
        } else {
            // Client - redirect to dashboard
            return redirect()->intended(route('dashboard', absolute: false))
                ->with('success', 'Login successful!');
        }
    }

    /**
     * Log login activity and send email alert.
     */
    private function logLoginActivity(Request $request, $user, $sessionId)
    {
        $userAgent = $request->userAgent();
        $deviceInfo = $this->parseUserAgent($userAgent);
        $ipAddress = $request->ip();

        // Create login activity record (location will be updated later by queue job)
        $loginActivity = \App\Models\LoginActivity::create([
            'user_id' => $user->id,
            'session_id' => $sessionId,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'device_type' => $deviceInfo['device_type'],
            'browser' => $deviceInfo['browser'],
            'platform' => $deviceInfo['platform'],
            'location' => null, // Will be updated by queue job
            'action' => 'login',
            'logged_in_at' => now(),
            'is_active' => true,
        ]);

        // Send login alert email via queue (non-blocking)
        // IP geolocation will be fetched in the queue job to avoid blocking login
        if ($user->email) {
            try {
                \App\Jobs\SendMailJob::dispatch(
                    $user->email,
                    '🔐 New Login Alert - ' . config('app.name'),
                    'emails.login-alert',
                    [],
                    \App\Mail\LoginAlertMail::class,
                    [
                        $user,
                        $ipAddress,
                        $userAgent,
                        $deviceInfo['device_type'],
                        $deviceInfo['browser'],
                        $deviceInfo['platform'],
                        null // Location will be fetched in the job
                    ]
                );
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Failed to queue login alert email', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
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

