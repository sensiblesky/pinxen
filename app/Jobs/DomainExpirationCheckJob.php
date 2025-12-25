<?php

namespace App\Jobs;

use App\Models\DomainMonitor;
use App\Models\DomainMonitorAlert;
use App\Models\MonitorCommunicationPreference;
use App\Services\DomainExpirationService;
use App\Services\MonitorAlertService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class DomainExpirationCheckJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300; // Job timeout in seconds (5 minutes for slow WHOIS)
    public $tries = 2; // Retry 2 times on failure
    public $backoff = [60, 120]; // Wait 60s, 120s between retries
    public $maxExceptions = 2; // Max exceptions before marking as failed

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $domainMonitorId
    ) {
        // Set queue name for better organization
        $this->onQueue('domain-checks');
    }

    /**
     * Execute the job.
     */
    public function handle(DomainExpirationService $domainService, MonitorAlertService $alertService): void
    {
        $startTime = microtime(true);
        
        try {
            // Set maximum execution time to prevent job termination
            set_time_limit(300); // 5 minutes
            
            // Load monitor with relationships
            $monitor = DomainMonitor::with(['user', 'communicationPreferences'])->findOrFail($this->domainMonitorId);

            // Skip if monitor is inactive
            if (!$monitor->is_active) {
                Log::info("Domain monitor {$monitor->id} is inactive, skipping check");
                return;
            }

            // Check domain expiration with timeout protection
            $result = $domainService->checkDomainExpiration($monitor->domain);

            if ($result) {
                // Update monitor with expiration data
                $monitor->update([
                    'expiration_date' => $result['expiration_date'],
                    'days_until_expiration' => $result['days_until_expiration'],
                    'last_checked_at' => now(),
                ]);

                // Check if alerts need to be sent (non-blocking)
                try {
                    $this->checkAndSendAlerts($monitor, $result, $alertService);
                } catch (\Exception $alertException) {
                    // Log alert failure but don't fail the job
                    Log::warning("Failed to send alert for domain monitor {$monitor->id}", [
                        'error' => $alertException->getMessage(),
                    ]);
                }
            } else {
                // Update last checked time even if we couldn't get expiration date
                $monitor->update(['last_checked_at' => now()]);
                Log::warning("Could not determine expiration date for domain: {$monitor->domain}");
            }
            
            $executionTime = round(microtime(true) - $startTime, 2);
            Log::info("Domain expiration check completed", [
                'domain_monitor_id' => $this->domainMonitorId,
                'execution_time' => $executionTime . 's',
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // Monitor was deleted, just log and return (don't retry)
            Log::warning("Domain monitor not found", [
                'domain_monitor_id' => $this->domainMonitorId,
            ]);
            return;
        } catch (\Exception $e) {
            $executionTime = round(microtime(true) - $startTime, 2);
            Log::error('Domain expiration check job failed', [
                'domain_monitor_id' => $this->domainMonitorId,
                'error' => $e->getMessage(),
                'execution_time' => $executionTime . 's',
                'trace' => $e->getTraceAsString(),
            ]);
            
            // Update monitor but don't throw to prevent job termination
            try {
                $monitor = DomainMonitor::find($this->domainMonitorId);
                if ($monitor) {
                    $monitor->update(['last_checked_at' => now()]);
                }
            } catch (\Exception $updateException) {
                Log::error("Failed to update domain monitor after error", [
                    'domain_monitor_id' => $this->domainMonitorId,
                    'error' => $updateException->getMessage(),
                ]);
            }
            
            // Only throw if we haven't exceeded max exceptions
            if ($this->attempts() < $this->tries) {
                throw $e; // Re-throw to trigger retry mechanism
            } else {
                // Max retries reached, fail gracefully
                Log::error("Domain expiration check failed after {$this->tries} attempts", [
                    'domain_monitor_id' => $this->domainMonitorId,
                ]);
            }
        }
    }

    /**
     * Check if alerts need to be sent and send them.
     */
    private function checkAndSendAlerts(DomainMonitor $monitor, array $result, MonitorAlertService $alertService): void
    {
        $daysUntilExpiration = $result['days_until_expiration'];
        $expirationDate = $result['expiration_date'];

        // Check if domain is expired
        if ($daysUntilExpiration < 0) {
            $this->sendExpiredAlert($monitor, $expirationDate, $alertService);
            return;
        }

        // Check for 30 days alert
        if ($monitor->alert_30_days && $daysUntilExpiration <= 30 && $daysUntilExpiration > 5) {
            // Check if we already sent a 30-day alert today
            $last30DayAlert = DomainMonitorAlert::where('domain_monitor_id', $monitor->id)
                ->where('alert_type', '30_days')
                ->whereDate('created_at', today())
                ->first();

            if (!$last30DayAlert) {
                $this->send30DayAlert($monitor, $expirationDate, $daysUntilExpiration, $alertService);
            }
        }

        // Check for 5 days alert
        if ($monitor->alert_5_days && $daysUntilExpiration <= 5 && $daysUntilExpiration > 0) {
            // Check if we already sent a 5-day alert today
            $last5DayAlert = DomainMonitorAlert::where('domain_monitor_id', $monitor->id)
                ->where('alert_type', '5_days')
                ->whereDate('created_at', today())
                ->first();

            if (!$last5DayAlert) {
                $this->send5DayAlert($monitor, $expirationDate, $daysUntilExpiration, $alertService);
            }
        }

        // Check for daily alerts when 30 days or less remain
        if ($monitor->alert_daily_under_30 && $daysUntilExpiration <= 30 && $daysUntilExpiration > 0) {
            // Check if we already sent a daily alert today
            $lastDailyAlert = DomainMonitorAlert::where('domain_monitor_id', $monitor->id)
                ->where('alert_type', 'daily')
                ->whereDate('created_at', today())
                ->first();

            if (!$lastDailyAlert) {
                $this->sendDailyAlert($monitor, $expirationDate, $daysUntilExpiration, $alertService);
            }
        }
    }

    /**
     * Send 30 days before expiration alert.
     */
    private function send30DayAlert(DomainMonitor $monitor, Carbon $expirationDate, int $daysUntilExpiration, MonitorAlertService $alertService): void
    {
        $message = "Domain {$monitor->domain} will expire in {$daysUntilExpiration} days (on {$expirationDate->format('M d, Y')}). Please renew your domain soon.";

        $this->sendAlert($monitor, '30_days', $message, $alertService);
    }

    /**
     * Send 5 days before expiration alert.
     */
    private function send5DayAlert(DomainMonitor $monitor, Carbon $expirationDate, int $daysUntilExpiration, MonitorAlertService $alertService): void
    {
        $message = "URGENT: Domain {$monitor->domain} will expire in {$daysUntilExpiration} days (on {$expirationDate->format('M d, Y')}). Please renew immediately to avoid service interruption.";

        $this->sendAlert($monitor, '5_days', $message, $alertService);
    }

    /**
     * Send daily alert when 30 days or less remain.
     */
    private function sendDailyAlert(DomainMonitor $monitor, Carbon $expirationDate, int $daysUntilExpiration, MonitorAlertService $alertService): void
    {
        $message = "Domain {$monitor->domain} will expire in {$daysUntilExpiration} days (on {$expirationDate->format('M d, Y')}). Please renew your domain.";

        $this->sendAlert($monitor, 'daily', $message, $alertService);
    }

    /**
     * Send expired domain alert.
     */
    private function sendExpiredAlert(DomainMonitor $monitor, Carbon $expirationDate, MonitorAlertService $alertService): void
    {
        $daysExpired = abs($monitor->days_until_expiration ?? 0);
        $message = "CRITICAL: Domain {$monitor->domain} has expired on {$expirationDate->format('M d, Y')} ({$daysExpired} days ago). Immediate action required!";

        $this->sendAlert($monitor, 'expired', $message, $alertService);
    }

    /**
     * Send alert to all configured communication channels.
     */
    private function sendAlert(DomainMonitor $monitor, string $alertType, string $message, MonitorAlertService $alertService): void
    {
        // Get communication preferences
        $channels = $monitor->communicationPreferences()
            ->where('is_enabled', true)
            ->where('monitor_type', 'domain')
            ->pluck('communication_channel')
            ->toArray();

        if (empty($channels)) {
            Log::warning("No communication channels configured for domain monitor {$monitor->id}");
            return;
        }

        // Send alert to each channel
        foreach ($channels as $channel) {
            try {
                // Create alert record
                $alert = DomainMonitorAlert::create([
                    'domain_monitor_id' => $monitor->id,
                    'alert_type' => $alertType,
                    'message' => $message,
                    'communication_channel' => $channel,
                    'status' => 'pending',
                ]);

                // Send the alert
                $alertService->sendDomainAlert($monitor, $alert, $channel);

                // Update alert status
                $alert->update([
                    'status' => 'sent',
                    'sent_at' => now(),
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to send domain alert', [
                    'domain_monitor_id' => $monitor->id,
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
