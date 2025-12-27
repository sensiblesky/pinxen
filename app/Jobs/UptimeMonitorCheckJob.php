<?php

namespace App\Jobs;

use App\Models\UptimeMonitor;
use App\Models\UptimeMonitorCheck;
use App\Models\UptimeMonitorAlert;
use App\Services\MonitorHttpService;
use App\Services\MailService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class UptimeMonitorCheckJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300; // Job timeout in seconds (5 minutes for slow requests)
    public $tries = 2; // Retry 2 times on failure (reduced to fail faster)
    public $backoff = [30, 60]; // Wait 30s, 60s between retries
    public $maxExceptions = 2; // Max exceptions before marking as failed

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $uptimeMonitorId
    ) {
        // Set queue name for better organization
        $this->onQueue('monitor-checks');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $startTime = microtime(true);
        
        try {
            // Set maximum execution time to prevent job termination
            set_time_limit(300); // 5 minutes
            
            // Load monitor with relationships
            $monitor = UptimeMonitor::with('user')->findOrFail($this->uptimeMonitorId);

            // Skip if monitor is inactive
            if (!$monitor->is_active) {
                Log::info("Uptime monitor {$monitor->id} is inactive, skipping check");
                return;
            }

            // Skip if monitor is in maintenance period
            if ($monitor->isInMaintenancePeriod()) {
                Log::info("Uptime monitor {$monitor->id} is in maintenance period, skipping check");
                return;
            }

            // Perform the HTTP check with timeout protection
            $result = $this->performHttpCheck($monitor);

            // Store the check result
            $check = UptimeMonitorCheck::create([
                'uptime_monitor_id' => $monitor->id,
                'status' => $result['status'],
                'response_time' => $result['response_time'],
                'status_code' => $result['status_code'],
                'error_message' => $result['error_message'],
                'failure_type' => $result['failure_type'] ?? null,
                'failure_classification' => $result['failure_classification'] ?? null,
                'layer_checks' => $result['layer_checks'] ?? null,
                'probe_results' => $result['probe_results'] ?? null,
                'is_confirmed' => $result['is_confirmed'] ?? false,
                'probes_failed' => $result['probes_failed'] ?? 0,
                'probes_total' => $result['probes_total'] ?? 1,
                'checked_at' => now(),
            ]);

            // Update monitor's last checked time and calculate next check time
            $nextCheckAt = now()->addMinutes($monitor->check_interval);
            $monitor->update([
                'last_checked_at' => now(),
                'next_check_at' => $nextCheckAt,
            ]);

            // Determine new status (with false positive prevention)
            $newStatus = $this->determineStatus($monitor, $result);

            // Only update status if it changed
            if ($monitor->status !== $newStatus) {
                $oldStatus = $monitor->status;
                $monitor->update(['status' => $newStatus]);

                // Send alerts if status changed to down or recovered (non-blocking)
                try {
                    if ($newStatus === 'down' && $oldStatus !== 'down') {
                        $this->sendDownAlert($monitor, $result);
                    } elseif ($newStatus === 'up' && $oldStatus === 'down') {
                        $this->sendRecoveryAlert($monitor, $result);
                    }
                } catch (\Exception $alertException) {
                    // Log alert failure but don't fail the job
                    Log::warning("Failed to send alert for uptime monitor {$monitor->id}", [
                        'error' => $alertException->getMessage(),
                    ]);
                }
            }

            $executionTime = round(microtime(true) - $startTime, 2);
            Log::info("Uptime monitor check completed", [
                'monitor_id' => $monitor->id,
                'status' => $result['status'],
                'response_time' => $result['response_time'],
                'execution_time' => $executionTime . 's',
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // Monitor was deleted, just log and return (don't retry)
            Log::warning("Uptime monitor not found", [
                'monitor_id' => $this->uptimeMonitorId,
            ]);
            return;
        } catch (\Exception $e) {
            $executionTime = round(microtime(true) - $startTime, 2);
            Log::error("Uptime monitor check failed", [
                'monitor_id' => $this->uptimeMonitorId,
                'error' => $e->getMessage(),
                'execution_time' => $executionTime . 's',
                'trace' => $e->getTraceAsString(),
            ]);
            
            // Update monitor status to error but don't throw to prevent job termination
            try {
                $monitor = UptimeMonitor::find($this->uptimeMonitorId);
                if ($monitor) {
                    $monitor->update([
                        'status' => 'error',
                        'last_checked_at' => now(),
                    ]);
                }
            } catch (\Exception $updateException) {
                Log::error("Failed to update monitor status after error", [
                    'monitor_id' => $this->uptimeMonitorId,
                    'error' => $updateException->getMessage(),
                ]);
            }
            
            // Only throw if we haven't exceeded max exceptions
            if ($this->attempts() < $this->tries) {
                throw $e; // Re-throw to trigger retry mechanism
            } else {
                // Max retries reached, fail gracefully
                Log::error("Uptime monitor check failed after {$this->tries} attempts", [
                    'monitor_id' => $this->uptimeMonitorId,
                ]);
            }
        }
    }

    /**
     * Perform HTTP check for uptime monitor.
     * Uses multi-probe confirmation logic if enabled.
     */
    private function performHttpCheck(UptimeMonitor $monitor): array
    {
        try {
            // Ensure timeout doesn't exceed job timeout
            $timeout = min($monitor->timeout ?? 30, 120); // Max 2 minutes for HTTP request
            
            // Use multi-probe confirmation if enabled
            if ($monitor->confirmation_enabled) {
                return \App\Services\MultiProbeService::performMultiProbeCheck(
                    $monitor->url,
                    $monitor->confirmation_probes ?? 3,
                    $monitor->confirmation_threshold ?? 2,
                    $timeout,
                    $monitor->expected_status_code,
                    $monitor->check_ssl,
                    $monitor->confirmation_retry_delay ?? 5,
                    $monitor->confirmation_max_retries ?? 3
                );
            }
            
            // Standard single check
            $result = MonitorHttpService::performCheck(
                $monitor->url,
                $timeout,
                $monitor->expected_status_code,
                $monitor->keyword_present,
                $monitor->keyword_absent,
                $monitor->check_ssl,
                $monitor->request_method ?? 'GET',
                $monitor->basic_auth_username,
                $monitor->basic_auth_password,
                $monitor->custom_headers,
                $monitor->cache_buster ?? false
            );
            
            // Add layer checks for single probe
            if ($result['status'] === 'down' || true) { // Always perform layer checks
                try {
                    $layerChecks = \App\Services\LayerCheckService::performLayerChecks(
                        $monitor->url,
                        $monitor->check_ssl,
                        $timeout,
                        $monitor->keyword_present,
                        $monitor->keyword_absent
                    );
                    $result['layer_checks'] = $layerChecks;
                } catch (\Exception $layerException) {
                    // Ignore layer check errors
                }
            }
            
            return $result;
        } catch (\Exception $e) {
            // Return error result instead of throwing
            Log::error("HTTP check failed for uptime monitor", [
                'monitor_id' => $monitor->id,
                'url' => $monitor->url,
                'error' => $e->getMessage(),
            ]);
            
            return [
                'status' => 'down',
                'response_time' => 0,
                'status_code' => null,
                'error_message' => 'Check failed: ' . $e->getMessage(),
                'is_confirmed' => false,
                'probes_total' => 1,
                'probes_failed' => 1,
            ];
        }
    }

    /**
     * Determine monitor status with false positive prevention.
     * Requires 2 consecutive "down" checks before marking as down.
     */
    private function determineStatus(UptimeMonitor $monitor, array $result): string
    {
        // If current check is "up", mark as up immediately
        if ($result['status'] === 'up') {
            return 'up';
        }

        // If current check is "down", check previous status
        // Get the last 2 checks
        $recentChecks = UptimeMonitorCheck::where('uptime_monitor_id', $monitor->id)
            ->orderBy('checked_at', 'desc')
            ->limit(2)
            ->pluck('status')
            ->toArray();

        // If we have 2 consecutive "down" checks, mark as down
        if (count($recentChecks) >= 2 && $recentChecks[0] === 'down' && $recentChecks[1] === 'down') {
            return 'down';
        }

        // Otherwise, keep current status (don't change to down on first failure)
        return $monitor->status === 'unknown' ? 'down' : $monitor->status;
    }

    /**
     * Send alert when monitor goes down.
     */
    private function sendDownAlert(UptimeMonitor $monitor, array $result): void
    {
        // Build alert message with failure classification
        $message = "Website DOWN";
        
        // Add failure classification if available
        if (!empty($result['failure_classification'])) {
            $message .= " — {$result['failure_classification']}";
        } elseif ($result['error_message']) {
            $message .= " — {$result['error_message']}";
        } elseif ($result['status_code']) {
            $message .= " — HTTP {$result['status_code']}";
        }
        
        $message .= " ({$monitor->name})";

        $details = [
            'response_time' => $result['response_time'],
            'status_code' => $result['status_code'],
            'error_message' => $result['error_message'],
            'failure_type' => $result['failure_type'] ?? null,
            'failure_classification' => $result['failure_classification'] ?? null,
        ];

        try {
            // Create alert record
            $alert = UptimeMonitorAlert::create([
                'uptime_monitor_id' => $monitor->id,
                'alert_type' => 'down',
                'message' => $message,
                'communication_channel' => 'email',
                'status' => 'pending',
            ]);

            // Send email alert
            if ($monitor->user && $monitor->user->email) {
                $this->sendEmailAlert($monitor, $alert, 'down', $message, $details);
            }

        } catch (\Exception $e) {
            Log::error("Failed to send down alert for uptime monitor {$monitor->id}", [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send alert when monitor recovers.
     */
    private function sendRecoveryAlert(UptimeMonitor $monitor, array $result): void
    {
        $message = "Monitor {$monitor->name} ({$monitor->url}) has RECOVERED and is now UP. ";
        $message .= "Response time: {$result['response_time']}ms";

        $details = [
            'response_time' => $result['response_time'],
            'status_code' => $result['status_code'],
        ];

        try {
            $alert = UptimeMonitorAlert::create([
                'uptime_monitor_id' => $monitor->id,
                'alert_type' => 'recovery',
                'message' => $message,
                'communication_channel' => 'email',
                'status' => 'pending',
            ]);

            if ($monitor->user && $monitor->user->email) {
                $this->sendEmailAlert($monitor, $alert, 'recovery', $message, $details);
            }

        } catch (\Exception $e) {
            Log::error("Failed to send recovery alert for uptime monitor {$monitor->id}", [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send email alert using database SMTP configuration.
     */
    private function sendEmailAlert(
        UptimeMonitor $monitor,
        UptimeMonitorAlert $alert,
        string $alertType,
        string $message,
        array $details
    ): void {
        try {
            // Check if SMTP is configured
            if (!MailService::isSmtpConfigured()) {
                throw new \Exception('SMTP is not configured in system settings.');
            }

            $subject = match($alertType) {
                'down' => "Monitor Alert: {$monitor->name} is DOWN",
                'recovery' => "Monitor Alert: {$monitor->name} Recovered",
                default => "Monitor Alert: {$monitor->name}",
            };
            
            // Create a simple monitor object for the email template
            $monitorForEmail = (object) [
                'name' => $monitor->name,
                'url' => $monitor->url,
                'check_interval' => $monitor->check_interval,
            ];
            
            // Ensure mailer is configured, then use it
            MailService::getConfiguredMailer(); // This sets up the mailer config
            // Send email using Mail facade with explicit mailer name
            Mail::mailer('database_smtp')->send('emails.monitor-alert', [
                'monitor' => $monitorForEmail,
                'alertType' => $alertType,
                'message' => $message,
                'status' => $alertType === 'down' ? 'down' : 'up',
                'responseTime' => $details['response_time'] ?? null,
                'statusCode' => $details['status_code'] ?? null,
                'errorMessage' => $details['error_message'] ?? null,
            ], function ($m) use ($monitor, $subject) {
                $m->to($monitor->user->email)->subject($subject);
            });

            // Update alert as sent
            $alert->update([
                'status' => 'sent',
                'sent_at' => now(),
            ]);

            Log::info("Uptime monitor alert email sent successfully", [
                'monitor_id' => $monitor->id,
                'alert_id' => $alert->id,
                'email' => $monitor->user->email,
            ]);

        } catch (\Exception $e) {
            Log::error("Failed to send uptime monitor alert email", [
                'monitor_id' => $monitor->id,
                'alert_id' => $alert->id,
                'error' => $e->getMessage(),
            ]);

            // Update alert as failed
            $alert->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);
        }
    }
}
