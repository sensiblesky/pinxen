<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Crypt;

class RecaptchaController extends Controller
{
    /**
     * Display the Recaptcha configuration page.
     */
    public function index(): View
    {
        $settings = Setting::getAllCached();
        
        // Decrypt sensitive fields for display (if they exist and are encrypted)
        $encryptedFields = [
            'google_v2_secret_key',
            'google_v3_secret_key',
            'hcaptcha_secret_key',
            'friendly_captcha_secret_key',
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
        
        return view('panel.recaptcha.index', compact('settings'));
    }

    /**
     * Update Recaptcha configuration.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            // Google V2
            'google_v2_site_key' => ['nullable', 'string', 'max:255'],
            'google_v2_secret_key' => ['nullable', 'string', 'max:255'],
            'google_v2_is_default' => ['nullable', 'boolean'],
            
            // Google V3
            'google_v3_site_key' => ['nullable', 'string', 'max:255'],
            'google_v3_secret_key' => ['nullable', 'string', 'max:255'],
            'google_v3_is_default' => ['nullable', 'boolean'],
            
            // Hcaptcha
            'hcaptcha_site_key' => ['nullable', 'string', 'max:255'],
            'hcaptcha_secret_key' => ['nullable', 'string', 'max:255'],
            'hcaptcha_is_default' => ['nullable', 'boolean'],
            
            // Friendly Captcha
            'friendly_captcha_site_key' => ['nullable', 'string', 'max:255'],
            'friendly_captcha_secret_key' => ['nullable', 'string', 'max:255'],
            'friendly_captcha_is_default' => ['nullable', 'boolean'],
        ]);

        // Save Google V2 settings
        Setting::set('google_v2_site_key', $validated['google_v2_site_key'] ?? '');
        if (!empty($validated['google_v2_secret_key'])) {
            Setting::set('google_v2_secret_key', Crypt::encryptString($validated['google_v2_secret_key']));
        }
        $googleV2IsDefault = $request->has('google_v2_is_default');
        Setting::set('google_v2_is_default', $googleV2IsDefault ? '1' : '0', 'boolean');

        // Save Google V3 settings
        Setting::set('google_v3_site_key', $validated['google_v3_site_key'] ?? '');
        if (!empty($validated['google_v3_secret_key'])) {
            Setting::set('google_v3_secret_key', Crypt::encryptString($validated['google_v3_secret_key']));
        }
        $googleV3IsDefault = $request->has('google_v3_is_default');
        Setting::set('google_v3_is_default', $googleV3IsDefault ? '1' : '0', 'boolean');

        // Save Hcaptcha settings
        Setting::set('hcaptcha_site_key', $validated['hcaptcha_site_key'] ?? '');
        if (!empty($validated['hcaptcha_secret_key'])) {
            Setting::set('hcaptcha_secret_key', Crypt::encryptString($validated['hcaptcha_secret_key']));
        }
        $hcaptchaIsDefault = $request->has('hcaptcha_is_default');
        Setting::set('hcaptcha_is_default', $hcaptchaIsDefault ? '1' : '0', 'boolean');

        // Save Friendly Captcha settings
        Setting::set('friendly_captcha_site_key', $validated['friendly_captcha_site_key'] ?? '');
        if (!empty($validated['friendly_captcha_secret_key'])) {
            Setting::set('friendly_captcha_secret_key', Crypt::encryptString($validated['friendly_captcha_secret_key']));
        }
        $friendlyCaptchaIsDefault = $request->has('friendly_captcha_is_default');
        Setting::set('friendly_captcha_is_default', $friendlyCaptchaIsDefault ? '1' : '0', 'boolean');

        // Ensure only one is set as default
        if ($googleV2IsDefault) {
            Setting::set('google_v3_is_default', '0', 'boolean');
            Setting::set('hcaptcha_is_default', '0', 'boolean');
            Setting::set('friendly_captcha_is_default', '0', 'boolean');
        } elseif ($googleV3IsDefault) {
            Setting::set('google_v2_is_default', '0', 'boolean');
            Setting::set('hcaptcha_is_default', '0', 'boolean');
            Setting::set('friendly_captcha_is_default', '0', 'boolean');
        } elseif ($hcaptchaIsDefault) {
            Setting::set('google_v2_is_default', '0', 'boolean');
            Setting::set('google_v3_is_default', '0', 'boolean');
            Setting::set('friendly_captcha_is_default', '0', 'boolean');
        } elseif ($friendlyCaptchaIsDefault) {
            Setting::set('google_v2_is_default', '0', 'boolean');
            Setting::set('google_v3_is_default', '0', 'boolean');
            Setting::set('hcaptcha_is_default', '0', 'boolean');
        }

        return redirect()->route('panel.recaptcha.index')
            ->with('success', 'Recaptcha configuration updated successfully.');
    }
}
