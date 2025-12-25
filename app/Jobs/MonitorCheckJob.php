<?php

namespace App\Jobs;

use App\Models\Monitor;
use App\Models\MonitorCheck;
use App\Models\MonitorAlert;
use App\Models\MonitorCommunicationPreference;
use App\Models\MonitoringService;
use App\Services\MonitorHttpService;
use App\Services\MonitorAlertService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class MonitorCheckJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 60; // Job timeout in seconds
    public $tries = 3; // Retry 3 times on failure
    public $backoff = [10, 30, 60]; // Wait 10s, 30s, 60s between retries

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $monitorId
    ) {
        // Set queue name for better organization
        $this->onQueue('monitor-checks');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Load monitor with relationships
            $monitor = Monitor::with(['monitoringService', 'user'])
                ->findOrFail($this->monitorId);

            // Skip if monitor is inactive
            if (!$monitor->is_active) {
                Log::info("Monitor {$monitor->id} is inactive, skipping check");
                return;
            }

            // Only process uptime monitoring for now
            if (!$monitor->monitoringService || $monitor->monitoringService->key !== 'uptime') {
                Log::info("Monitor {$monitor->id} is not an uptime monitor, skipping");
                return;
            }

            // Perform the HTTP check
            $result = $this->performHttpCheck($monitor);

            // Store the check result
            $check = MonitorCheck::create([
                'monitor_id' => $monitor->id,
                'status' => $result['status'],
                'response_time' => $result['response_time'],
                'status_code' => $result['status_code'],
                'error_message' => $result['error_message'],
                'checked_at' => now(),
            ]);

            // Update monitor's last checked time
            $monitor->update(['last_checked_at' => now()]);

            // Determine new status (with false positive prevention)
            $newStatus = $this->determineStatus($monitor, $result);

            // Only update status if it changed
            if ($monitor->status !== $newStatus) {
                $oldStatus = $monitor->status;
                $monitor->update(['status' => $newStatus]);

                // Send alerts if status changed to down or recovered
                if ($newStatus === 'down' && $oldStatus !== 'down') {
                    $this->sendDownAlert($monitor, $result);
                } elseif ($newStatus === 'up' && $oldStatus === 'down') {
                    $this->sendRecoveryAlert($monitor, $result);
                }
            }

            Log::info("Monitor check completed", [
                'monitor_id' => $monitor->id,
                'status' => $result['status'],
                'response_time' => $result['response_time'],
            ]);

        } catch (\Exception $e) {
            Log::error("Monitor check failed", [
                'monitor_id' => $this->monitorId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Re-throw to trigger retry mechanism
            throw $e;
        }
    }

    /**
     * Perform HTTP check with proper error handling and anti-blocking measures.
     */
    private function performHttpCheck(Monitor $monitor): array
    {
        $config = $monitor->service_config ?? [];
        $url = $monitor->url ?? ($config['url'] ?? null);
        $expectedStatusCode = $monitor->expected_status_code ?? ($config['expected_status_code'] ?? 200);
        $timeout = $monitor->timeout ?? 30;
        $keywordPresent = $config['keyword_present'] ?? null;
        $keywordAbsent = $config['keyword_absent'] ?? null;
        $checkSsl = $config['check_ssl'] ?? true;

        if (!$url) {
            return [
                'status' => 'down',
                'response_time' => null,
                'status_code' => null,
                'error_message' => 'No URL configured for monitor',
            ];
        }

        // Use the service class with anti-blocking measures
        return MonitorHttpService::performCheck(
            $url,
            $timeout,
            $expectedStatusCode,
            $keywordPresent,
            $keywordAbsent,
            $checkSsl
        );
    }

    /**
     * Determine monitor status with false positive prevention.
     * Requires 2 consecutive down checks before marking as down.
     */
    private function determineStatus(Monitor $monitor, array $currentResult): string
    {
        // If current check is up, mark as up immediately
        if ($currentResult['status'] === 'up') {
            return 'up';
        }

        // If current check is down, check recent history
        // Get last 3 checks
        $recentChecks = MonitorCheck::where('monitor_id', $monitor->id)
            ->orderBy('checked_at', 'desc')
            ->limit(3)
            ->get();

        // Count consecutive down checks
        $consecutiveDowns = 0;
        foreach ($recentChecks as $check) {
            if ($check->status === 'down') {
                $consecutiveDowns++;
            } else {
                break; // Stop counting if we hit an 'up' check
            }
        }

        // Require at least 2 consecutive down checks to mark as down (false positive prevention)
        if ($consecutiveDowns >= 2) {
            return 'down';
        }

        // If only 1 down check, keep current status (don't change to down yet)
        return $monitor->status === 'down' ? 'down' : 'unknown';
    }

    /**
     * Send alert when monitor goes down.
     */
    private function sendDownAlert(Monitor $monitor, array $result): void
    {
        $message = "Monitor '{$monitor->name}' is DOWN. ";
        if ($monitor->url) {
            $message .= "URL: {$monitor->url}. ";
        }
        if (!empty($result['error_message'])) {
            $message .= "Error: {$result['error_message']}";
        }

        MonitorAlertService::sendAlerts(
            $monitor,
            'down',
            $message,
            'down',
            $result['response_time'] ?? null,
            $result['status_code'] ?? null,
            $result['error_message'] ?? null
        );
    }

    /**
     * Send alert when monitor recovers.
     */
    private function sendRecoveryAlert(Monitor $monitor, array $result): void
    {
        $message = "Monitor '{$monitor->name}' is UP. ";
        if ($monitor->url) {
            $message .= "URL: {$monitor->url}. ";
        }
        $message .= "Response time: " . ($result['response_time'] ?? 0) . "ms";

        MonitorAlertService::sendAlerts(
            $monitor,
            'recovery',
            $message,
            'up',
            $result['response_time'] ?? null,
            $result['status_code'] ?? null,
            null
        );
    }
}
