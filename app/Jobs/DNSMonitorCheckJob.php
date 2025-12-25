<?php

namespace App\Jobs;

use App\Models\DNSMonitor;
use App\Models\DNSMonitorCheck;
use App\Models\DNSMonitorAlert;
use App\Models\MonitorCommunicationPreference;
use App\Services\DNSCheckService;
use App\Services\MonitorAlertService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class DNSMonitorCheckJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300; // Job timeout in seconds (5 minutes for slow DNS)
    public $tries = 2; // Retry 2 times on failure
    public $backoff = [30, 60]; // Wait 30s, 60s between retries
    public $maxExceptions = 2; // Max exceptions before marking as failed

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $dnsMonitorId
    ) {
        // Set queue name for better organization
        $this->onQueue('dns-checks');
    }

    /**
     * Execute the job.
     */
    public function handle(DNSCheckService $dnsService, MonitorAlertService $alertService): void
    {
        $startTime = microtime(true);
        
        try {
            // Set maximum execution time to prevent job termination
            set_time_limit(300); // 5 minutes
            
            // Load monitor with relationships
            $monitor = DNSMonitor::with(['user', 'communicationPreferences'])->findOrFail($this->dnsMonitorId);

            // Skip if monitor is inactive
            if (!$monitor->is_active) {
                Log::info("DNS monitor {$monitor->id} is inactive, skipping check");
                return;
            }

            // Check DNS records with timeout protection
            $dnsResults = $dnsService->checkDNSRecords($monitor->domain, $monitor->record_types);

            if ($dnsResults) {
                $hasAnyChanges = false;
                $hasAnyMissing = false;
                $overallStatus = 'healthy';

                // Process each record type
                foreach ($monitor->record_types as $recordType) {
                    try {
                        $currentRecords = $dnsResults[$recordType] ?? [];
                        
                        // Get previous check for this record type
                        $previousCheck = $monitor->getLatestCheckForType($recordType);
                        $previousRecords = $previousCheck ? ($previousCheck->records ?? []) : [];

                        // Compare records to detect changes
                        $comparison = $dnsService->compareDNSRecords($currentRecords, $previousRecords);
                        
                        $hasChanges = $comparison['has_changes'];
                        $isMissing = empty($currentRecords) && !empty($previousRecords);

                        if ($hasChanges) {
                            $hasAnyChanges = true;
                            $overallStatus = 'changed';
                        }

                        if ($isMissing) {
                            $hasAnyMissing = true;
                            if ($overallStatus === 'healthy') {
                                $overallStatus = 'missing';
                            }
                        }

                        // Store check result
                        DNSMonitorCheck::create([
                            'dns_monitor_id' => $monitor->id,
                            'record_type' => $recordType,
                            'records' => $currentRecords,
                            'previous_records' => $previousRecords,
                            'has_changes' => $hasChanges,
                            'is_missing' => $isMissing,
                            'raw_response' => $dnsResults,
                            'checked_at' => now(),
                        ]);

                        // Send alerts if needed (non-blocking)
                        try {
                            if ($hasChanges && $monitor->alert_on_change) {
                                $this->sendChangeAlert($monitor, $recordType, $comparison, $alertService);
                            }

                            if ($isMissing && $monitor->alert_on_missing) {
                                $this->sendMissingAlert($monitor, $recordType, $alertService);
                            }
                        } catch (\Exception $alertException) {
                            Log::warning("Failed to send alert for DNS monitor {$monitor->id}", [
                                'record_type' => $recordType,
                                'error' => $alertException->getMessage(),
                            ]);
                        }
                    } catch (\Exception $recordException) {
                        // Log error for this record type but continue with others
                        Log::warning("Failed to process DNS record type {$recordType}", [
                            'dns_monitor_id' => $monitor->id,
                            'error' => $recordException->getMessage(),
                        ]);
                    }
                }

                // Update monitor status and calculate next check time
                $oldStatus = $monitor->status;
                $nextCheckAt = now()->addMinutes($monitor->check_interval);
                $monitor->update([
                    'status' => $overallStatus,
                    'last_checked_at' => now(),
                    'next_check_at' => $nextCheckAt,
                ]);

                // Send recovery alert if status improved (non-blocking)
                try {
                    if ($oldStatus !== 'healthy' && $overallStatus === 'healthy') {
                        $this->sendRecoveryAlert($monitor, $alertService);
                    }
                } catch (\Exception $alertException) {
                    Log::warning("Failed to send recovery alert for DNS monitor {$monitor->id}", [
                        'error' => $alertException->getMessage(),
                    ]);
                }
            } else {
                // Update last checked time and next check time even if we couldn't get DNS data
                $nextCheckAt = now()->addMinutes($monitor->check_interval);
                $monitor->update([
                    'status' => 'error',
                    'last_checked_at' => now(),
                    'next_check_at' => $nextCheckAt,
                ]);
                Log::warning("Could not determine DNS records for domain: {$monitor->domain}");
            }
            
            $executionTime = round(microtime(true) - $startTime, 2);
            Log::info("DNS check completed", [
                'dns_monitor_id' => $this->dnsMonitorId,
                'execution_time' => $executionTime . 's',
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // Monitor was deleted, just log and return (don't retry)
            Log::warning("DNS monitor not found", [
                'dns_monitor_id' => $this->dnsMonitorId,
            ]);
            return;
        } catch (\Exception $e) {
            $executionTime = round(microtime(true) - $startTime, 2);
            Log::error('DNS check job failed', [
                'dns_monitor_id' => $this->dnsMonitorId,
                'error' => $e->getMessage(),
                'execution_time' => $executionTime . 's',
                'trace' => $e->getTraceAsString(),
            ]);
            
            // Update monitor but don't throw to prevent job termination
            try {
                $monitor = DNSMonitor::find($this->dnsMonitorId);
                if ($monitor) {
                    $monitor->update([
                        'status' => 'error',
                        'last_checked_at' => now(),
                    ]);
                }
            } catch (\Exception $updateException) {
                Log::error("Failed to update DNS monitor after error", [
                    'dns_monitor_id' => $this->dnsMonitorId,
                    'error' => $updateException->getMessage(),
                ]);
            }
            
            // Only throw if we haven't exceeded max exceptions
            if ($this->attempts() < $this->tries) {
                throw $e; // Re-throw to trigger retry mechanism
            } else {
                // Max retries reached, fail gracefully
                Log::error("DNS check failed after {$this->tries} attempts", [
                    'dns_monitor_id' => $this->dnsMonitorId,
                ]);
            }
        }
    }

    /**
     * Send alert for DNS record changes.
     */
    private function sendChangeAlert(DNSMonitor $monitor, string $recordType, array $comparison, MonitorAlertService $alertService): void
    {
        $changes = [];
        if (!empty($comparison['added'])) {
            $changes[] = 'Added: ' . count($comparison['added']) . ' record(s)';
        }
        if (!empty($comparison['removed'])) {
            $changes[] = 'Removed: ' . count($comparison['removed']) . ' record(s)';
        }
        if (!empty($comparison['modified'])) {
            $changes[] = 'Modified: ' . count($comparison['modified']) . ' record(s)';
        }

        $message = "DNS records for {$monitor->domain} ({$recordType}) have changed. " . implode(', ', $changes);

        $this->sendAlert($monitor, 'changed', $recordType, $message, $comparison, $alertService);
    }

    /**
     * Send alert for missing DNS records.
     */
    private function sendMissingAlert(DNSMonitor $monitor, string $recordType, MonitorAlertService $alertService): void
    {
        $message = "DNS records for {$monitor->domain} ({$recordType}) are missing.";

        $this->sendAlert($monitor, 'missing', $recordType, $message, null, $alertService);
    }

    /**
     * Send recovery alert.
     */
    private function sendRecoveryAlert(DNSMonitor $monitor, MonitorAlertService $alertService): void
    {
        $message = "DNS records for {$monitor->domain} are now healthy.";

        $this->sendAlert($monitor, 'recovered', null, $message, null, $alertService);
    }

    /**
     * Send alert to all configured communication channels.
     */
    private function sendAlert(DNSMonitor $monitor, string $alertType, ?string $recordType, string $message, ?array $changedRecords, MonitorAlertService $alertService): void
    {
        // Get communication preferences
        $channels = $monitor->communicationPreferences()
            ->where('is_enabled', true)
            ->where('monitor_type', 'dns')
            ->pluck('communication_channel')
            ->toArray();

        if (empty($channels)) {
            Log::warning("No communication channels configured for DNS monitor {$monitor->id}");
            return;
        }

        // Send alert to each channel
        foreach ($channels as $channel) {
            try {
                // Create alert record
                $alert = DNSMonitorAlert::create([
                    'dns_monitor_id' => $monitor->id,
                    'alert_type' => $alertType,
                    'record_type' => $recordType,
                    'message' => $message,
                    'changed_records' => $changedRecords,
                    'communication_channel' => $channel,
                    'status' => 'pending',
                ]);

                // Send the alert
                $alertService->sendDNSAlert($monitor, $alert, $channel);

                // Update alert status
                $alert->update([
                    'status' => 'sent',
                    'sent_at' => now(),
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to send DNS alert', [
                    'dns_monitor_id' => $monitor->id,
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
