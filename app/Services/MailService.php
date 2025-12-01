<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;

class MailService
{
    /**
     * Configure mail settings from database and return configured mailer.
     * 
     * @return \Illuminate\Mail\Mailer
     */
    public static function getConfiguredMailer()
    {
        $settings = Setting::getAllCached();
        
        // Get SMTP settings from database
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
     * 
     * @return bool
     */
    public static function isSmtpConfigured(): bool
    {
        $settings = Setting::getAllCached();
        
        return !empty($settings['smtp_host']) && 
               !empty($settings['smtp_username']) && 
               !empty($settings['smtp_port']);
    }
}



