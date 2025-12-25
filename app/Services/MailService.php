<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class MailService
{
    /**
     * Cache key for SMTP configuration check
     */
    const SMTP_CONFIG_CACHE_KEY = 'mail_smtp_configured';
    
    /**
     * Cache duration for SMTP config check (1 hour)
     */
    const SMTP_CONFIG_CACHE_DURATION = 3600;

    /**
     * Configure mail settings from database and return configured mailer.
     * Settings are cached server-side (not browser storage) for performance.
     * 
     * @return \Illuminate\Mail\Mailer
     */
    public static function getConfiguredMailer()
    {
        // Get settings from cache (server-side, secure, not browser storage)
        // Cache is stored on server filesystem/Redis, never exposed to browser
        $settings = Setting::getAllCached();
        
        // Get SMTP settings from cached database values
        $smtpDriver = $settings['smtp_driver'] ?? 'smtp';
        $smtpHost = $settings['smtp_host'] ?? '';
        $smtpPort = $settings['smtp_port'] ?? 587;
        $smtpEncryption = $settings['smtp_encryption'] ?? 'tls';
        $smtpUsername = $settings['smtp_username'] ?? '';
        $smtpPassword = '';
        $smtpFromAddress = $settings['smtp_from_address'] ?? config('mail.from.address');
        $smtpFromName = $settings['smtp_from_name'] ?? config('mail.from.name');
        
        // Decrypt password if it exists
        if (!empty($settings['smtp_password'])) {
            try {
                $smtpPassword = Crypt::decryptString($settings['smtp_password']);
            } catch (\Exception $e) {
                // If decryption fails, try using it as plain text (for backward compatibility)
                $smtpPassword = $settings['smtp_password'];
            }
        }
        
        // If no SMTP settings are configured, use default mailer
        if (empty($smtpHost) || empty($smtpUsername)) {
            return Mail::mailer();
        }
        
        // Configure a custom mailer with database settings
        Config::set('mail.mailers.database_smtp', [
            'transport' => 'smtp',
            'host' => $smtpHost,
            'port' => $smtpPort,
            'encryption' => $smtpEncryption === 'none' ? null : $smtpEncryption,
            'username' => $smtpUsername,
            'password' => $smtpPassword,
            'timeout' => null,
            'local_domain' => parse_url(config('app.url', 'http://localhost'), PHP_URL_HOST),
        ]);
        
        // Set from address and name
        Config::set('mail.from', [
            'address' => $smtpFromAddress,
            'name' => $smtpFromName,
        ]);
        
        // Return the configured mailer
        return Mail::mailer('database_smtp');
    }
    
    /**
     * Check if SMTP is properly configured in database.
     * Uses server-side cache (not browser storage) to avoid DB queries.
     * Cache is secure and stored on server only.
     * 
     * @return bool
     */
    public static function isSmtpConfigured(): bool
    {
        // Use cache to avoid DB query on every check
        // Cache is server-side (filesystem/Redis), never exposed to browser
        return Cache::remember(self::SMTP_CONFIG_CACHE_KEY, self::SMTP_CONFIG_CACHE_DURATION, function () {
            $settings = Setting::getAllCached();
            
            $isConfigured = !empty($settings['smtp_host']) && 
                           !empty($settings['smtp_username']) && 
                           !empty($settings['smtp_port']);
            
            // Log for debugging if not configured (only once per cache period)
            if (!$isConfigured) {
                $smtpKeys = array_filter(array_keys($settings), function($key) {
                    return strpos($key, 'smtp_') === 0;
                });
                Log::debug('SMTP not configured - checking settings', [
                    'has_smtp_host' => !empty($settings['smtp_host']),
                    'has_smtp_username' => !empty($settings['smtp_username']),
                    'has_smtp_port' => !empty($settings['smtp_port']),
                    'smtp_keys_found' => $smtpKeys,
                    'smtp_host_value' => !empty($settings['smtp_host']) ? '***' : null,
                    'smtp_username_value' => !empty($settings['smtp_username']) ? '***' : null,
                ]);
            }
            
            return $isConfigured;
        });
    }
    
    /**
     * Clear SMTP configuration cache.
     * Call this when SMTP settings are updated.
     */
    public static function clearSmtpConfigCache(): void
    {
        Cache::forget(self::SMTP_CONFIG_CACHE_KEY);
        // Also clear the main settings cache
        Setting::clearCache();
        
        // Force clear Laravel's application cache as well
        try {
            \Artisan::call('cache:clear');
        } catch (\Exception $e) {
            // Ignore if artisan is not available
        }
    }
}



