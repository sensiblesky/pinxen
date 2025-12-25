<?php

namespace App\Services;

use App\Mail\MonitorAlertMail;
use App\Mail\DomainExpirationAlertMail;
use App\Mail\SSLAlertMail;
use App\Mail\DNSAlertMail;
use App\Models\Monitor;
use App\Models\MonitorAlert;
use App\Models\MonitorCommunicationPreference;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class MonitorAlertService
{
    /**
     * Send alerts for a monitor status change.
     * 
     * @param Monitor $monitor The monitor that changed status
     * @param string $alertType 'down', 'up', or 'recovery'
     * @param string $message Alert message
     * @param string $status Current monitor status ('up' or 'down')
     * @param int|null $responseTime Response time in milliseconds
     * @param int|null $statusCode HTTP status code (if applicable)
     * @param string|null $errorMessage Error message (if any)
     */
    public static function sendAlerts(
        Monitor $monitor,
        string $alertType,
        string $message,
        string $status,
        ?int $responseTime = null,
        ?int $statusCode = null,
        ?string $errorMessage = null
    ): void {
        // Get enabled communication preferences for this monitor
        $preferences = MonitorCommunicationPreference::where('monitor_id', $monitor->id)
            ->where('monitor_type', 'monitor')
            ->where('is_enabled', true)
            ->get();

        if ($preferences->isEmpty()) {
            Log::info("No communication preferences enabled for monitor", [
                'monitor_id' => $monitor->id,
                'monitor_name' => $monitor->name,
            ]);
            return;
        }

        foreach ($preferences as $pref) {
            try {
                // Create alert record
                $alert = MonitorAlert::create([
                    'monitor_id' => $monitor->id,
                    'alert_type' => $alertType,
                    'message' => $message,
                    'communication_channel' => $pref->communication_channel,
                    'status' => 'pending',
                ]);

                // Send based on channel
                switch ($pref->communication_channel) {
                    case 'email':
                        self::sendEmailAlert(
                            $monitor,
                            $alert,
                            $alertType,
                            $message,
                            $status,
                            $pref->channel_value, // Email address
                            $responseTime,
                            $statusCode,
                            $errorMessage
                        );
                        break;

                    case 'sms':
                        // TODO: Implement SMS sending
                        Log::warning("SMS alerts not yet implemented", [
                            'monitor_id' => $monitor->id,
                            'phone' => $pref->channel_value,
                        ]);
                        $alert->update([
                            'status' => 'pending',
                            'error_message' => 'SMS alerts not yet implemented',
                        ]);
                        break;

                    case 'whatsapp':
                        // TODO: Implement WhatsApp sending
                        Log::warning("WhatsApp alerts not yet implemented", [
                            'monitor_id' => $monitor->id,
                            'phone' => $pref->channel_value,
                        ]);
                        $alert->update([
                            'status' => 'pending',
                            'error_message' => 'WhatsApp alerts not yet implemented',
                        ]);
                        break;

                    case 'telegram':
                        // TODO: Implement Telegram sending
                        Log::warning("Telegram alerts not yet implemented", [
                            'monitor_id' => $monitor->id,
                            'chat_id' => $pref->channel_value,
                        ]);
                        $alert->update([
                            'status' => 'pending',
                            'error_message' => 'Telegram alerts not yet implemented',
                        ]);
                        break;

                    case 'discord':
                        // TODO: Implement Discord sending
                        Log::warning("Discord alerts not yet implemented", [
                            'monitor_id' => $monitor->id,
                            'webhook' => $pref->channel_value,
                        ]);
                        $alert->update([
                            'status' => 'pending',
                            'error_message' => 'Discord alerts not yet implemented',
                        ]);
                        break;

                    default:
                        Log::warning("Unknown communication channel", [
                            'monitor_id' => $monitor->id,
                            'channel' => $pref->communication_channel,
                        ]);
                        $alert->update([
                            'status' => 'failed',
                            'error_message' => "Unknown communication channel: {$pref->communication_channel}",
                        ]);
                        break;
                }
            } catch (\Exception $e) {
                Log::error("Failed to send alert", [
                    'monitor_id' => $monitor->id,
                    'channel' => $pref->communication_channel,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                // Update alert status if it was created
                if (isset($alert)) {
                    $alert->update([
                        'status' => 'failed',
                        'error_message' => $e->getMessage(),
                    ]);
                }
            }
        }
    }

    /**
     * Send email alert using database SMTP configuration.
     */
    private static function sendEmailAlert(
        Monitor $monitor,
        MonitorAlert $alert,
        string $alertType,
        string $message,
        string $status,
        string $emailAddress,
        ?int $responseTime = null,
        ?int $statusCode = null,
        ?string $errorMessage = null
    ): void {
        try {
            // Check if SMTP is configured
            if (!MailService::isSmtpConfigured()) {
                throw new \Exception('SMTP is not configured in system settings. Please configure SMTP in Communication Channels.');
            }

            // Get configured mailer from database
            $mailer = MailService::getConfiguredMailer();

            // Create and send email
            $mailable = new MonitorAlertMail(
                $monitor,
                $alert,
                $alertType,
                $message,
                $status,
                $responseTime,
                $statusCode,
                $errorMessage
            );

            // Send using the configured mailer
            $mailer->to($emailAddress)->send($mailable);

            // Update alert as sent
            $alert->update([
                'status' => 'sent',
                'sent_at' => now(),
            ]);

            Log::info("Monitor alert email sent successfully", [
                'monitor_id' => $monitor->id,
                'monitor_name' => $monitor->name,
                'alert_type' => $alertType,
                'email' => $emailAddress,
                'alert_id' => $alert->id,
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to send monitor alert email", [
                'monitor_id' => $monitor->id,
                'alert_id' => $alert->id,
                'email' => $emailAddress,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Update alert as failed
            $alert->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            throw $e; // Re-throw to be caught by outer try-catch
        }
    }

    /**
     * Send domain expiration alert.
     * 
     * @param \App\Models\DomainMonitor $monitor
     * @param \App\Models\DomainMonitorAlert $alert
     * @param string $channel
     */
    public function sendDomainAlert($monitor, $alert, string $channel): void
    {
        switch ($channel) {
            case 'email':
                $this->sendDomainEmailAlert($monitor, $alert);
                break;

            case 'sms':
                // TODO: Implement SMS sending
                Log::warning("SMS alerts not yet implemented for domain monitors");
                throw new \Exception('SMS alerts not yet implemented');

            case 'whatsapp':
                // TODO: Implement WhatsApp sending
                Log::warning("WhatsApp alerts not yet implemented for domain monitors");
                throw new \Exception('WhatsApp alerts not yet implemented');

            case 'telegram':
                // TODO: Implement Telegram sending
                Log::warning("Telegram alerts not yet implemented for domain monitors");
                throw new \Exception('Telegram alerts not yet implemented');

            case 'discord':
                // TODO: Implement Discord sending
                Log::warning("Discord alerts not yet implemented for domain monitors");
                throw new \Exception('Discord alerts not yet implemented');

            default:
                throw new \Exception("Unknown communication channel: {$channel}");
        }
    }

    /**
     * Send domain expiration email alert.
     */
    private function sendDomainEmailAlert($monitor, $alert): void
    {
        try {
            // Check if SMTP is configured
            if (!MailService::isSmtpConfigured()) {
                throw new \Exception('SMTP is not configured in system settings.');
            }

            // Get configured mailer from database
            $mailer = MailService::getConfiguredMailer();

            // Create email subject based on alert type
            $subject = match($alert->alert_type) {
                'expired' => "CRITICAL: Domain {$monitor->domain} Has Expired",
                '5_days' => "URGENT: Domain {$monitor->domain} Expiring in 5 Days",
                '30_days' => "Warning: Domain {$monitor->domain} Expiring in 30 Days",
                'daily' => "Reminder: Domain {$monitor->domain} Expiring Soon",
                default => "Domain Expiration Alert: {$monitor->domain}",
            };

            $user = $monitor->user;
            $emailAddress = $user->email;

            // Send email using Mail facade with configured mailer
            $mailer->to($emailAddress)->send(new DomainExpirationAlertMail($monitor, $alert, $subject));

            Log::info("Domain expiration alert email sent successfully", [
                'domain_monitor_id' => $monitor->id,
                'domain' => $monitor->domain,
                'alert_type' => $alert->alert_type,
                'email' => $emailAddress,
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to send domain expiration alert email", [
                'domain_monitor_id' => $monitor->id,
                'alert_id' => $alert->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Send SSL certificate alert.
     * 
     * @param \App\Models\SSLMonitor $monitor
     * @param \App\Models\SSLMonitorAlert $alert
     * @param string $channel
     */
    public function sendSSLAlert($monitor, $alert, string $channel): void
    {
        switch ($channel) {
            case 'email':
                $this->sendSSLEmailAlert($monitor, $alert);
                break;

            case 'sms':
                // TODO: Implement SMS sending
                Log::warning("SMS alerts not yet implemented for SSL monitors");
                throw new \Exception('SMS alerts not yet implemented');

            case 'whatsapp':
                // TODO: Implement WhatsApp sending
                Log::warning("WhatsApp alerts not yet implemented for SSL monitors");
                throw new \Exception('WhatsApp alerts not yet implemented');

            case 'telegram':
                // TODO: Implement Telegram sending
                Log::warning("Telegram alerts not yet implemented for SSL monitors");
                throw new \Exception('Telegram alerts not yet implemented');

            case 'discord':
                // TODO: Implement Discord sending
                Log::warning("Discord alerts not yet implemented for SSL monitors");
                throw new \Exception('Discord alerts not yet implemented');

            default:
                throw new \Exception("Unknown communication channel: {$channel}");
        }
    }

    /**
     * Send SSL certificate email alert.
     */
    private function sendSSLEmailAlert($monitor, $alert): void
    {
        try {
            // Check if SMTP is configured
            if (!MailService::isSmtpConfigured()) {
                throw new \Exception('SMTP is not configured in system settings.');
            }

            // Get configured mailer from database
            $mailer = MailService::getConfiguredMailer();

            // Create email subject based on alert type
            $subject = match($alert->alert_type) {
                'expired' => "CRITICAL: SSL Certificate for {$monitor->domain} Has Expired",
                'invalid' => "SSL Certificate for {$monitor->domain} Is Invalid",
                'expiring_soon' => "Warning: SSL Certificate for {$monitor->domain} Expiring Soon",
                'recovered' => "SSL Certificate for {$monitor->domain} Is Now Valid",
                default => "SSL Certificate Alert: {$monitor->domain}",
            };

            $user = $monitor->user;
            $emailAddress = $user->email;

            // Send email using Mail facade with configured mailer
            $mailer->to($emailAddress)->send(new SSLAlertMail($monitor, $alert, $subject));

            Log::info("SSL alert email sent successfully", [
                'ssl_monitor_id' => $monitor->id,
                'domain' => $monitor->domain,
                'alert_type' => $alert->alert_type,
                'email' => $emailAddress,
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to send SSL alert email", [
                'ssl_monitor_id' => $monitor->id,
                'alert_id' => $alert->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Send DNS change alert.
     * 
     * @param \App\Models\DNSMonitor $monitor
     * @param \App\Models\DNSMonitorAlert $alert
     * @param string $channel
     */
    public function sendDNSAlert($monitor, $alert, string $channel): void
    {
        switch ($channel) {
            case 'email':
                $this->sendDNSEmailAlert($monitor, $alert);
                break;

            case 'sms':
                // TODO: Implement SMS sending
                Log::warning("SMS alerts not yet implemented for DNS monitors");
                throw new \Exception('SMS alerts not yet implemented');

            case 'whatsapp':
                // TODO: Implement WhatsApp sending
                Log::warning("WhatsApp alerts not yet implemented for DNS monitors");
                throw new \Exception('WhatsApp alerts not yet implemented');

            case 'telegram':
                // TODO: Implement Telegram sending
                Log::warning("Telegram alerts not yet implemented for DNS monitors");
                throw new \Exception('Telegram alerts not yet implemented');

            case 'discord':
                // TODO: Implement Discord sending
                Log::warning("Discord alerts not yet implemented for DNS monitors");
                throw new \Exception('Discord alerts not yet implemented');

            default:
                throw new \Exception("Unknown communication channel: {$channel}");
        }
    }

    /**
     * Send DNS change email alert.
     */
    private function sendDNSEmailAlert($monitor, $alert): void
    {
        try {
            // Check if SMTP is configured
            if (!MailService::isSmtpConfigured()) {
                throw new \Exception('SMTP is not configured in system settings.');
            }

            // Get configured mailer from database
            $mailer = MailService::getConfiguredMailer();

            // Create email subject based on alert type
            $subject = match($alert->alert_type) {
                'changed' => "DNS Records Changed: {$monitor->domain}",
                'missing' => "DNS Records Missing: {$monitor->domain}",
                'error' => "DNS Check Error: {$monitor->domain}",
                'recovered' => "DNS Records Recovered: {$monitor->domain}",
                default => "DNS Alert: {$monitor->domain}",
            };

            $user = $monitor->user;
            $emailAddress = $user->email;

            // Send email using Mail facade with configured mailer
            $mailer->to($emailAddress)->send(new DNSAlertMail($monitor, $alert, $subject));

            Log::info("DNS alert email sent successfully", [
                'dns_monitor_id' => $monitor->id,
                'domain' => $monitor->domain,
                'alert_type' => $alert->alert_type,
                'email' => $emailAddress,
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to send DNS alert email", [
                'dns_monitor_id' => $monitor->id,
                'alert_id' => $alert->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}


