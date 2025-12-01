<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Crypt;

class AuthSSOController extends Controller
{
    /**
     * Display the Auth & SSO configuration page.
     */
    public function index(): View
    {
        $settings = Setting::getAllCached();
        
        // Decrypt sensitive fields for display (if they exist and are encrypted)
        $encryptedFields = [
            'google_client_secret',
            'linkedin_client_secret',
            'twitter_client_secret',
            'facebook_client_secret',
            'github_client_secret',
        ];
        
        foreach ($encryptedFields as $field) {
            if (isset($settings[$field]) && !empty($settings[$field])) {
                try {
                    $settings[$field] = Crypt::decryptString($settings[$field]);
                } catch (\Exception $e) {
                    // If decryption fails, it might not be encrypted yet, keep original value
                    $settings[$field] = $settings[$field];
                }
            } else {
                $settings[$field] = '';
            }
        }
        
        return view('panel.auth-sso.index', compact('settings'));
    }

    /**
     * Update Auth & SSO configuration.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            // Google OAuth
            'google_client_id' => ['nullable', 'string', 'max:255'],
            'google_client_secret' => ['nullable', 'string', 'max:255'],
            'google_redirect_url' => ['nullable', 'url', 'max:500'],
            
            // LinkedIn OAuth
            'linkedin_client_id' => ['nullable', 'string', 'max:255'],
            'linkedin_client_secret' => ['nullable', 'string', 'max:255'],
            'linkedin_redirect_url' => ['nullable', 'url', 'max:500'],
            
            // Twitter OAuth
            'twitter_client_id' => ['nullable', 'string', 'max:255'],
            'twitter_client_secret' => ['nullable', 'string', 'max:255'],
            'twitter_redirect_url' => ['nullable', 'url', 'max:500'],
            
            // Facebook OAuth
            'facebook_client_id' => ['nullable', 'string', 'max:255'],
            'facebook_client_secret' => ['nullable', 'string', 'max:255'],
            'facebook_redirect_url' => ['nullable', 'url', 'max:500'],
            
            // GitHub OAuth
            'github_client_id' => ['nullable', 'string', 'max:255'],
            'github_client_secret' => ['nullable', 'string', 'max:255'],
            'github_redirect_url' => ['nullable', 'url', 'max:500'],
            
            // Authentication Settings
            'user_registration_enabled' => ['nullable', 'boolean'],
            'user_login_enabled' => ['nullable', 'boolean'],
            'force_email_verification' => ['nullable', 'boolean'],
            'force_two_factor_authentication' => ['nullable', 'boolean'],
        ]);

        // Save Google OAuth settings
        Setting::set('google_client_id', $validated['google_client_id'] ?? '');
        if (!empty($validated['google_client_secret'])) {
            Setting::set('google_client_secret', Crypt::encryptString($validated['google_client_secret']));
        }
        Setting::set('google_redirect_url', $validated['google_redirect_url'] ?? '');

        // Save LinkedIn OAuth settings
        Setting::set('linkedin_client_id', $validated['linkedin_client_id'] ?? '');
        if (!empty($validated['linkedin_client_secret'])) {
            Setting::set('linkedin_client_secret', Crypt::encryptString($validated['linkedin_client_secret']));
        }
        Setting::set('linkedin_redirect_url', $validated['linkedin_redirect_url'] ?? '');

        // Save Twitter OAuth settings
        Setting::set('twitter_client_id', $validated['twitter_client_id'] ?? '');
        if (!empty($validated['twitter_client_secret'])) {
            Setting::set('twitter_client_secret', Crypt::encryptString($validated['twitter_client_secret']));
        }
        Setting::set('twitter_redirect_url', $validated['twitter_redirect_url'] ?? '');

        // Save Facebook OAuth settings
        Setting::set('facebook_client_id', $validated['facebook_client_id'] ?? '');
        if (!empty($validated['facebook_client_secret'])) {
            Setting::set('facebook_client_secret', Crypt::encryptString($validated['facebook_client_secret']));
        }
        Setting::set('facebook_redirect_url', $validated['facebook_redirect_url'] ?? '');

        // Save GitHub OAuth settings
        Setting::set('github_client_id', $validated['github_client_id'] ?? '');
        if (!empty($validated['github_client_secret'])) {
            Setting::set('github_client_secret', Crypt::encryptString($validated['github_client_secret']));
        }
        Setting::set('github_redirect_url', $validated['github_redirect_url'] ?? '');

        // Save Authentication Settings
        $userRegistrationEnabled = $request->has('user_registration_enabled');
        $userLoginEnabled = $request->has('user_login_enabled');
        $forceEmailVerification = $request->has('force_email_verification');
        $forceTwoFactorAuth = $request->has('force_two_factor_authentication');
        
        Setting::set('user_registration_enabled', $userRegistrationEnabled ? '1' : '0', 'boolean');
        Setting::set('user_login_enabled', $userLoginEnabled ? '1' : '0', 'boolean');
        Setting::set('force_email_verification', $forceEmailVerification ? '1' : '0', 'boolean');
        Setting::set('force_two_factor_authentication', $forceTwoFactorAuth ? '1' : '0', 'boolean');

        return redirect()->route('panel.auth-sso.index')
            ->with('success', 'Auth & SSO configuration updated successfully.');
    }
}
