<?php

namespace App\Jobs;

use App\Models\SSLMonitor;
use App\Models\SSLMonitorCheck;
use App\Models\SSLMonitorAlert;
use App\Models\MonitorCommunicationPreference;
use App\Services\SSLCertificateService;
use App\Services\MonitorAlertService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SSLMonitorCheckJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300; // Job timeout in seconds (5 minutes for slow API)
    public $tries = 2; // Retry 2 times on failure
    public $backoff = [30, 60]; // Wait 30s, 60s between retries
    public $maxExceptions = 2; // Max exceptions before marking as failed

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $sslMonitorId
    ) {
        // Set queue name for better organization
        $this->onQueue('ssl-checks');
    }

    /**
     * Execute the job.
     */
    public function handle(SSLCertificateService $sslService, MonitorAlertService $alertService): void
    {
        $startTime = microtime(true);
        
        try {
            // Set maximum execution time to prevent job termination
            set_time_limit(300); // 5 minutes
            
            // Load monitor with relationships
            $monitor = SSLMonitor::with(['user', 'communicationPreferences'])->findOrFail($this->sslMonitorId);

            // Skip if monitor is inactive
            if (!$monitor->is_active) {
                Log::info("SSL monitor {$monitor->id} is inactive, skipping check");
                return;
            }

            // Check SSL certificate with timeout protection
            $result = $sslService->checkSSLCertificate($monitor->domain);

            if ($result) {
                // Store the check result
                $check = SSLMonitorCheck::create([
                    'ssl_monitor_id' => $monitor->id,
                    'status' => $result['status'],
                    'resolved_ip' => $result['resolved_ip'],
                    'issued_to' => $result['issued_to'],
                    'issuer_cn' => $result['issuer_cn'],
                    'cert_alg' => $result['cert_alg'],
                    'cert_valid' => $result['cert_valid'],
                    'cert_exp' => $result['cert_exp'],
                    'valid_from' => $result['valid_from'],
                    'valid_till' => $result['valid_till'],
                    'validity_days' => $result['validity_days'],
                    'days_left' => $result['days_left'],
                    'hsts_header_enabled' => $result['hsts_header_enabled'],
                    'response_time_sec' => $result['response_time_sec'],
                    'raw_response' => $result['raw_response'],
                    'checked_at' => now(),
                ]);

                // Update monitor with SSL data and calculate next check time
                $oldStatus = $monitor->status;
                $nextCheckAt = now()->addMinutes($monitor->check_interval);
                $monitor->update([
                    'status' => $result['status'],
                    'expiration_date' => $result['expiration_date'],
                    'days_until_expiration' => $result['days_until_expiration'],
                    'last_checked_at' => now(),
                    'next_check_at' => $nextCheckAt,
                ]);

                // Check if alerts need to be sent (non-blocking)
                try {
                    $this->checkAndSendAlerts($monitor, $result, $oldStatus, $alertService);
                } catch (\Exception $alertException) {
                    // Log alert failure but don't fail the job
                    Log::warning("Failed to send alert for SSL monitor {$monitor->id}", [
                        'error' => $alertException->getMessage(),
                    ]);
                }
            } else {
                // Update last checked time and next check time even if we couldn't get SSL data
                $nextCheckAt = now()->addMinutes($monitor->check_interval);
                $monitor->update([
                    'last_checked_at' => now(),
                    'next_check_at' => $nextCheckAt,
                ]);
                Log::warning("Could not determine SSL certificate status for domain: {$monitor->domain}");
            }
            
            $executionTime = round(microtime(true) - $startTime, 2);
            Log::info("SSL certificate check completed", [
                'ssl_monitor_id' => $this->sslMonitorId,
                'execution_time' => $executionTime . 's',
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // Monitor was deleted, just log and return (don't retry)
            Log::warning("SSL monitor not found", [
                'ssl_monitor_id' => $this->sslMonitorId,
            ]);
            return;
        } catch (\Exception $e) {
            $executionTime = round(microtime(true) - $startTime, 2);
            Log::error('SSL certificate check job failed', [
                'ssl_monitor_id' => $this->sslMonitorId,
                'error' => $e->getMessage(),
                'execution_time' => $executionTime . 's',
                'trace' => $e->getTraceAsString(),
            ]);
            
            // Update monitor but don't throw to prevent job termination
            try {
                $monitor = SSLMonitor::find($this->sslMonitorId);
                if ($monitor) {
                    $monitor->update([
                        'status' => 'error',
                        'last_checked_at' => now(),
                    ]);
                }
            } catch (\Exception $updateException) {
                Log::error("Failed to update SSL monitor after error", [
                    'ssl_monitor_id' => $this->sslMonitorId,
                    'error' => $updateException->getMessage(),
                ]);
            }
            
            // Only throw if we haven't exceeded max exceptions
            if ($this->attempts() < $this->tries) {
                throw $e; // Re-throw to trigger retry mechanism
            } else {
                // Max retries reached, fail gracefully
                Log::error("SSL certificate check failed after {$this->tries} attempts", [
                    'ssl_monitor_id' => $this->sslMonitorId,
                ]);
            }
        }
    }

    /**
     * Check if alerts need to be sent and send them.
     */
    private function checkAndSendAlerts(SSLMonitor $monitor, array $result, string $oldStatus, MonitorAlertService $alertService): void
    {
        $status = $result['status'];
        $daysLeft = $result['days_left'] ?? null;
        $expirationDate = $result['expiration_date'];

        // Check for invalid certificate alert
        if ($status === 'invalid' && $monitor->alert_invalid) {
            // Check if we already sent an invalid alert today
            $lastInvalidAlert = SSLMonitorAlert::where('ssl_monitor_id', $monitor->id)
                ->where('alert_type', 'invalid')
                ->whereDate('created_at', today())
                ->first();

            if (!$lastInvalidAlert) {
                $this->sendInvalidAlert($monitor, $result, $alertService);
            }
        }

        // Check for expired certificate alert
        if ($status === 'expired' && $monitor->alert_expired) {
            // Check if we already sent an expired alert today
            $lastExpiredAlert = SSLMonitorAlert::where('ssl_monitor_id', $monitor->id)
                ->where('alert_type', 'expired')
                ->whereDate('created_at', today())
                ->first();

            if (!$lastExpiredAlert) {
                $this->sendExpiredAlert($monitor, $result, $alertService);
            }
        }

        // Check for expiring soon alert (30 days or less)
        if ($status === 'expiring_soon' && $monitor->alert_expiring_soon && $daysLeft !== null && $daysLeft <= 30) {
            // Check if we already sent an expiring soon alert today
            $lastExpiringAlert = SSLMonitorAlert::where('ssl_monitor_id', $monitor->id)
                ->where('alert_type', 'expiring_soon')
                ->whereDate('created_at', today())
                ->first();

            if (!$lastExpiringAlert) {
                $this->sendExpiringSoonAlert($monitor, $result, $daysLeft, $alertService);
            }
        }

        // Check for recovery alert (certificate became valid after being invalid/expired)
        if ($status === 'valid' && in_array($oldStatus, ['invalid', 'expired', 'expiring_soon'])) {
            // Check if we already sent a recovery alert today
            $lastRecoveryAlert = SSLMonitorAlert::where('ssl_monitor_id', $monitor->id)
                ->where('alert_type', 'recovered')
                ->whereDate('created_at', today())
                ->first();

            if (!$lastRecoveryAlert) {
                $this->sendRecoveryAlert($monitor, $result, $alertService);
            }
        }
    }

    /**
     * Send invalid certificate alert.
     */
    private function sendInvalidAlert(SSLMonitor $monitor, array $result, MonitorAlertService $alertService): void
    {
        $message = "SSL certificate for {$monitor->domain} is invalid. Please check and renew your certificate.";

        $this->sendAlert($monitor, 'invalid', $message, $alertService);
    }

    /**
     * Send expired certificate alert.
     */
    private function sendExpiredAlert(SSLMonitor $monitor, array $result, MonitorAlertService $alertService): void
    {
        $message = "CRITICAL: SSL certificate for {$monitor->domain} has expired. Immediate action required!";

        $this->sendAlert($monitor, 'expired', $message, $alertService);
    }

    /**
     * Send expiring soon alert.
     */
    private function sendExpiringSoonAlert(SSLMonitor $monitor, array $result, int $daysLeft, MonitorAlertService $alertService): void
    {
        $expirationDate = $result['expiration_date'] ? $result['expiration_date']->format('M d, Y') : 'Unknown';
        $message = "SSL certificate for {$monitor->domain} will expire in {$daysLeft} days (on {$expirationDate}). Please renew your certificate soon.";

        $this->sendAlert($monitor, 'expiring_soon', $message, $alertService);
    }

    /**
     * Send recovery alert.
     */
    private function sendRecoveryAlert(SSLMonitor $monitor, array $result, MonitorAlertService $alertService): void
    {
        $message = "SSL certificate for {$monitor->domain} is now valid.";

        $this->sendAlert($monitor, 'recovered', $message, $alertService);
    }

    /**
     * Send alert to all configured communication channels.
     */
    private function sendAlert(SSLMonitor $monitor, string $alertType, string $message, MonitorAlertService $alertService): void
    {
        // Get communication preferences
        $channels = $monitor->communicationPreferences()
            ->where('is_enabled', true)
            ->where('monitor_type', 'ssl')
            ->pluck('communication_channel')
            ->toArray();

        if (empty($channels)) {
            Log::warning("No communication channels configured for SSL monitor {$monitor->id}");
            return;
        }

        // Send alert to each channel
        foreach ($channels as $channel) {
            try {
                // Create alert record
                $alert = SSLMonitorAlert::create([
                    'ssl_monitor_id' => $monitor->id,
                    'alert_type' => $alertType,
                    'message' => $message,
                    'communication_channel' => $channel,
                    'status' => 'pending',
                ]);

                // Send the alert
                $alertService->sendSSLAlert($monitor, $alert, $channel);

                // Update alert status
                $alert->update([
                    'status' => 'sent',
                    'sent_at' => now(),
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to send SSL alert', [
                    'ssl_monitor_id' => $monitor->id,
                    'alert_type' => $alertType,
                    'channel' => $channel,
                    'error' => $e->getMessage(),
                ]);

                // Update alert status to failed
                if (isset($alert)) {
                    $alert->update([
                        'status' => 'failed',
                        'error_message' => $e->getMessage(),
                    ]);
                }
            }
        }
    }
}
