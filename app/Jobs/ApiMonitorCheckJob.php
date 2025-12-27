<?php

namespace App\Jobs;

use App\Models\ApiMonitor;
use App\Models\ApiMonitorCheck;
use App\Models\ApiMonitorAlert;
use App\Services\ApiMonitorService;
use App\Services\MailService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ApiMonitorCheckJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300;
    public $tries = 2;
    public $backoff = [30, 60];
    public $maxExceptions = 2;

    public function __construct(
        public int $apiMonitorId
    ) {
        $this->onQueue('monitor-checks');
    }

    public function handle(): void
    {
        $startTime = microtime(true);
        
        try {
            set_time_limit(300);
            
            $monitor = ApiMonitor::with('user')->findOrFail($this->apiMonitorId);

            if (!$monitor->is_active) {
                Log::info("API monitor {$monitor->id} is inactive, skipping check");
                return;
            }

            $service = new ApiMonitorService();
            $result = $service->check($monitor);

            // Discover dependencies from response
            $dependencyService = new \App\Services\DependencyDiscoveryService();
            $shouldSuppress = false;
            
            if ($result['response_body'] || $result['error_message']) {
                $discoveredDeps = $dependencyService->discoverDependencies(
                    $monitor,
                    $result['response_body'] ?? '',
                    $result['response_headers'] ?? [],
                    $result['error_message'] ?? null
                );
                
                if (!empty($discoveredDeps)) {
                    $dependencyService->saveDependencies($monitor, $discoveredDeps);
                }
            }

            // Check if alerts should be suppressed (parent dependency is down)
            $shouldSuppress = $dependencyService->shouldSuppressAlerts($monitor);
            if ($shouldSuppress) {
                Log::info("Suppressing alerts for API monitor {$monitor->id} because parent dependency is down");
            }

            // Handle retry action
            if (!empty($result['should_retry']) && $result['retry_after']) {
                Log::info("API monitor {$monitor->id} requested retry after {$result['retry_after']} seconds");
                // Dispatch a delayed job for retry
                self::dispatch($this->apiMonitorId)
                    ->delay(now()->addSeconds($result['retry_after']));
                return;
            }

            // Handle re-authentication action
            if (!empty($result['needs_re_auth'])) {
                // If auto-refresh is enabled, try to refresh token and retry
                if ($monitor->auto_auth_enabled && $monitor->retry_after_refresh && $monitor->auto_refresh_on_expiry) {
                    Log::info("API monitor {$monitor->id} auth failed, attempting auto-refresh and retry");
                    
                    $oauth2Service = new \App\Services\OAuth2Service();
                    $refreshResult = $oauth2Service->refreshToken($monitor);
                    
                    if ($refreshResult['success']) {
                        // Update monitor with new token
                        $updateData = [
                            'current_access_token' => $refreshResult['access_token'],
                            'token_refreshed_at' => now(),
                        ];
                        
                        if ($refreshResult['expires_in']) {
                            $updateData['token_expires_at'] = now()->addSeconds($refreshResult['expires_in']);
                        }
                        
                        if (isset($refreshResult['refresh_token'])) {
                            $updateData['oauth2_refresh_token'] = $refreshResult['refresh_token'];
                        }
                        
                        if ($monitor->auth_type === 'bearer') {
                            $updateData['auth_token'] = $refreshResult['access_token'];
                        }
                        
                        $monitor->update($updateData);
                        $monitor->refresh();
                        
                        // Retry the check with new token
                        Log::info("Token refreshed, retrying API check for monitor {$monitor->id}");
                        $service = new \App\Services\ApiMonitorService();
                        $result = $service->check($monitor);
                    } else {
                        // Refresh failed, create alert
                        Log::warning("API monitor {$monitor->id} token refresh failed: {$refreshResult['error']}");
                        ApiMonitorAlert::create([
                            'api_monitor_id' => $monitor->id,
                            'alert_type' => 'auth_failed',
                            'message' => 'Token refresh failed: ' . ($refreshResult['error'] ?? 'Unknown error'),
                            'is_sent' => false,
                        ]);
                        $result['status'] = 'down';
                        $result['error_message'] = 'Token refresh failed: ' . ($refreshResult['error'] ?? 'Unknown error');
                    }
                } else {
                    // Auto-refresh not enabled or retry disabled, create alert
                    Log::warning("API monitor {$monitor->id} needs re-authentication: {$result['auth_error']}");
                    ApiMonitorAlert::create([
                        'api_monitor_id' => $monitor->id,
                        'alert_type' => 'auth_failed',
                        'message' => $result['auth_error'] ?? 'Authentication token expired. Please update credentials.',
                        'is_sent' => false,
                    ]);
                    $result['status'] = 'down';
                    $result['error_message'] = $result['auth_error'] ?? 'Authentication required';
                }
            }

            // Store the check result (save full request/response for failed checks)
            $checkData = [
                'api_monitor_id' => $monitor->id,
                'status' => $result['status'],
                'response_time' => $result['response_time'],
                'status_code' => $result['status_code'],
                'response_body' => $result['response_body'] ? substr($result['response_body'], 0, 10000) : null, // Limit size
                'error_message' => $result['error_message'],
                'validation_errors' => $result['validation_errors'],
                'latency_exceeded' => $result['latency_exceeded'],
                'checked_at' => now(),
            ];

            // Store full request/response details for replay (especially on failures)
            if ($result['status'] === 'down' || isset($result['request_method'])) {
                $checkData['request_method'] = $result['request_method'] ?? $monitor->request_method;
                $checkData['request_url'] = $result['request_url'] ?? $monitor->url;
                $checkData['request_headers'] = $result['request_headers'] ?? [];
                $checkData['request_body'] = $result['request_body'] ? substr($result['request_body'], 0, 5000) : null; // Limit size
                $checkData['request_content_type'] = $result['request_content_type'] ?? $monitor->content_type;
                $checkData['response_headers'] = $result['response_headers'] ?? [];
            }

            $check = ApiMonitorCheck::create($checkData);

            // Update monitor
            $nextCheckAt = now()->addMinutes($monitor->check_interval);
            $monitor->update([
                'last_checked_at' => now(),
                'next_check_at' => $nextCheckAt,
            ]);

            // Determine new status
            $newStatus = $this->determineStatus($monitor, $result);

            if ($monitor->status !== $newStatus) {
                $oldStatus = $monitor->status;
                $monitor->update(['status' => $newStatus]);

                // Send alerts (unless suppressed due to parent dependency failure)
                try {
                    if (!$shouldSuppress) {
                        if ($newStatus === 'down' && $oldStatus !== 'down') {
                            $this->sendDownAlert($monitor, $result);
                        } elseif ($newStatus === 'up' && $oldStatus === 'down') {
                            $this->sendRecoveryAlert($monitor, $result);
                        } elseif ($result['latency_exceeded']) {
                            $this->sendLatencyAlert($monitor, $result);
                        } elseif (!empty($result['validation_errors'])) {
                            $this->sendValidationAlert($monitor, $result);
                        } elseif (!empty($result['schema_violations'])) {
                            $this->sendSchemaDriftAlert($monitor, $result);
                        } elseif (!$result['status_code_match']) {
                            $this->sendStatusCodeAlert($monitor, $result);
                        }
                    } else {
                        Log::info("Alerts suppressed for API monitor {$monitor->id} - parent dependency is down");
                    }
                } catch (\Exception $alertException) {
                    Log::warning("Failed to send alert for API monitor {$monitor->id}", [
                        'error' => $alertException->getMessage(),
                    ]);
                }
            }

            $executionTime = round(microtime(true) - $startTime, 2);
            Log::info("API monitor check completed", [
                'monitor_id' => $monitor->id,
                'status' => $result['status'],
                'response_time' => $result['response_time'],
                'execution_time' => $executionTime . 's',
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::warning("API monitor not found", [
                'monitor_id' => $this->apiMonitorId,
            ]);
            return;
        } catch (\Exception $e) {
            $executionTime = round(microtime(true) - $startTime, 2);
            Log::error("API monitor check failed", [
                'monitor_id' => $this->apiMonitorId,
                'error' => $e->getMessage(),
                'execution_time' => $executionTime . 's',
            ]);
            
            try {
                $monitor = ApiMonitor::find($this->apiMonitorId);
                if ($monitor) {
                    $monitor->update([
                        'status' => 'unknown',
                        'last_checked_at' => now(),
                    ]);
                }
            } catch (\Exception $updateException) {
                Log::error("Failed to update API monitor status after error", [
                    'monitor_id' => $this->apiMonitorId,
                    'error' => $updateException->getMessage(),
                ]);
            }
            
            throw $e;
        }
    }

    protected function determineStatus(ApiMonitor $monitor, array $result): string
    {
        if ($result['status'] === 'down') {
            return 'down';
        }

        // Check if latency exceeded
        if ($result['latency_exceeded']) {
            return 'down';
        }

        // Check if validation failed
        if (!empty($result['validation_errors'])) {
            return 'down';
        }

        // Check if status code doesn't match
        if (!$result['status_code_match']) {
            return 'down';
        }

        return 'up';
    }

    protected function sendDownAlert(ApiMonitor $monitor, array $result): void
    {
        $message = "API Monitor '{$monitor->name}' is DOWN\n\n";
        $message .= "URL: {$monitor->url}\n";
        $message .= "Status Code: " . ($result['status_code'] ?? 'N/A') . "\n";
        $message .= "Response Time: {$result['response_time']}ms\n";
        if ($result['error_message']) {
            $message .= "Error: {$result['error_message']}\n";
        }

        ApiMonitorAlert::create([
            'api_monitor_id' => $monitor->id,
            'alert_type' => 'down',
            'message' => $message,
            'is_sent' => false,
        ]);

        // TODO: Send via communication channels (email, SMS, etc.)
    }

    protected function sendRecoveryAlert(ApiMonitor $monitor, array $result): void
    {
        $message = "API Monitor '{$monitor->name}' is UP\n\n";
        $message .= "URL: {$monitor->url}\n";
        $message .= "Status Code: {$result['status_code']}\n";
        $message .= "Response Time: {$result['response_time']}ms\n";

        ApiMonitorAlert::create([
            'api_monitor_id' => $monitor->id,
            'alert_type' => 'up',
            'message' => $message,
            'is_sent' => false,
        ]);
    }

    protected function sendLatencyAlert(ApiMonitor $monitor, array $result): void
    {
        $message = "API Monitor '{$monitor->name}' latency exceeded threshold\n\n";
        $message .= "URL: {$monitor->url}\n";
        $message .= "Response Time: {$result['response_time']}ms\n";
        $message .= "Max Allowed: {$monitor->max_latency_ms}ms\n";

        ApiMonitorAlert::create([
            'api_monitor_id' => $monitor->id,
            'alert_type' => 'latency',
            'message' => $message,
            'is_sent' => false,
        ]);
    }

    protected function sendValidationAlert(ApiMonitor $monitor, array $result): void
    {
        $message = "API Monitor '{$monitor->name}' response validation failed\n\n";
        $message .= "URL: {$monitor->url}\n";
        $message .= "Validation Errors:\n";
        foreach ($result['validation_errors'] as $error) {
            $message .= "- {$error}\n";
        }

        ApiMonitorAlert::create([
            'api_monitor_id' => $monitor->id,
            'alert_type' => 'validation_failed',
            'message' => $message,
            'is_sent' => false,
        ]);
    }

    protected function sendStatusCodeAlert(ApiMonitor $monitor, array $result): void
    {
        $message = "API Monitor '{$monitor->name}' status code mismatch\n\n";
        $message .= "URL: {$monitor->url}\n";
        $message .= "Expected: {$monitor->expected_status_code}\n";
        $message .= "Received: {$result['status_code']}\n";

        ApiMonitorAlert::create([
            'api_monitor_id' => $monitor->id,
            'alert_type' => 'status_code_mismatch',
            'message' => $message,
            'is_sent' => false,
        ]);
    }

    protected function sendSchemaDriftAlert(ApiMonitor $monitor, array $result): void
    {
        $message = "API Monitor '{$monitor->name}' schema drift detected\n\n";
        $message .= "URL: {$monitor->url}\n";
        $message .= "Schema Violations:\n";
        foreach ($result['schema_violations'] as $violation) {
            $message .= "- {$violation}\n";
        }

        ApiMonitorAlert::create([
            'api_monitor_id' => $monitor->id,
            'alert_type' => 'validation_failed',
            'message' => $message,
            'is_sent' => false,
        ]);
    }
}
