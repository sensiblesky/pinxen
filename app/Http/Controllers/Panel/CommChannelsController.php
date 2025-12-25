<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Crypt;

class CommChannelsController extends Controller
{
    /**
     * Display the communication channels configuration page.
     */
    public function index(): View
    {
        $settings = Setting::getAllCached();
        
        // Decrypt sensitive fields for display (if they exist and are encrypted)
        $encryptedFields = ['smtp_password', 'sms_api_secret', 'whatsapp_api_secret', 'telegram_bot_token', 'discord_bot_token'];
        
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
        
        return view('panel.comm-channels.index', compact('settings'));
    }

    /**
     * Update communication channels configuration.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            // SMTP Settings
            'smtp_driver' => ['nullable', 'string', 'max:50'],
            'smtp_host' => ['nullable', 'string', 'max:255'],
            'smtp_port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'smtp_encryption' => ['nullable', 'string', 'in:tls,ssl,none'],
            'smtp_username' => ['nullable', 'string', 'max:255'],
            'smtp_password' => ['nullable', 'string', 'max:255'],
            'smtp_from_address' => ['nullable', 'email', 'max:255'],
            'smtp_from_name' => ['nullable', 'string', 'max:255'],
            
            // SMS Settings (placeholder for future implementation)
            'sms_provider' => ['nullable', 'string', 'max:50'],
            'sms_api_key' => ['nullable', 'string', 'max:255'],
            'sms_api_secret' => ['nullable', 'string', 'max:255'],
            'sms_from_number' => ['nullable', 'string', 'max:20'],
            
            // WhatsApp Settings (placeholder for future implementation)
            'whatsapp_provider' => ['nullable', 'string', 'max:50'],
            'whatsapp_api_key' => ['nullable', 'string', 'max:255'],
            'whatsapp_api_secret' => ['nullable', 'string', 'max:255'],
            'whatsapp_phone_number' => ['nullable', 'string', 'max:20'],
            
            // Telegram Settings (placeholder for future implementation)
            'telegram_bot_token' => ['nullable', 'string', 'max:255'],
            'telegram_chat_id' => ['nullable', 'string', 'max:255'],
            
            // Discord Settings (placeholder for future implementation)
            'discord_webhook_url' => ['nullable', 'url', 'max:500'],
            'discord_bot_token' => ['nullable', 'string', 'max:255'],
        ]);

        // Save SMTP settings
        Setting::set('smtp_driver', $validated['smtp_driver'] ?? '');
        Setting::set('smtp_host', $validated['smtp_host'] ?? '');
        Setting::set('smtp_port', $validated['smtp_port'] ?? '');
        Setting::set('smtp_encryption', $validated['smtp_encryption'] ?? 'tls');
        Setting::set('smtp_username', $validated['smtp_username'] ?? '');
        
        // Encrypt SMTP password before storing (only if provided)
        if (!empty($validated['smtp_password'])) {
            Setting::set('smtp_password', Crypt::encryptString($validated['smtp_password']));
        }
        // If password is empty, don't update it (keep existing)
        
        Setting::set('smtp_from_address', $validated['smtp_from_address'] ?? '');
        Setting::set('smtp_from_name', $validated['smtp_from_name'] ?? '');
        
        // Clear SMTP configuration cache so changes take effect immediately
        \App\Services\MailService::clearSmtpConfigCache();
        
        // Log SMTP settings save for debugging
        \Illuminate\Support\Facades\Log::info('SMTP settings saved', [
            'has_host' => !empty($validated['smtp_host']),
            'has_username' => !empty($validated['smtp_username']),
            'has_port' => !empty($validated['smtp_port']),
            'port' => $validated['smtp_port'] ?? null,
        ]);

        // Save SMS settings
        Setting::set('sms_provider', $validated['sms_provider'] ?? '');
        Setting::set('sms_api_key', $validated['sms_api_key'] ?? '');
        // Only update secret if provided
        if (!empty($validated['sms_api_secret'])) {
            Setting::set('sms_api_secret', Crypt::encryptString($validated['sms_api_secret']));
        }
        Setting::set('sms_from_number', $validated['sms_from_number'] ?? '');

        // Save WhatsApp settings
        Setting::set('whatsapp_provider', $validated['whatsapp_provider'] ?? '');
        Setting::set('whatsapp_api_key', $validated['whatsapp_api_key'] ?? '');
        // Only update secret if provided
        if (!empty($validated['whatsapp_api_secret'])) {
            Setting::set('whatsapp_api_secret', Crypt::encryptString($validated['whatsapp_api_secret']));
        }
        Setting::set('whatsapp_phone_number', $validated['whatsapp_phone_number'] ?? '');

        // Save Telegram settings
        // Only update token if provided
        if (!empty($validated['telegram_bot_token'])) {
            Setting::set('telegram_bot_token', Crypt::encryptString($validated['telegram_bot_token']));
        }
        Setting::set('telegram_chat_id', $validated['telegram_chat_id'] ?? '');

        // Save Discord settings
        Setting::set('discord_webhook_url', $validated['discord_webhook_url'] ?? '');
        // Only update token if provided
        if (!empty($validated['discord_bot_token'])) {
            Setting::set('discord_bot_token', Crypt::encryptString($validated['discord_bot_token']));
        }

        return redirect()->route('panel.comm-channels.index')
            ->with('success', 'Communication channels configuration updated successfully.');
    }
}
