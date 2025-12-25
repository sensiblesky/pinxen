<?php

namespace App\Console\Commands;

use App\Jobs\UptimeMonitorCheckJob;
use App\Jobs\SSLMonitorCheckJob;
use App\Jobs\DNSMonitorCheckJob;
use App\Models\UptimeMonitor;
use App\Models\SSLMonitor;
use App\Models\DNSMonitor;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class CheckAllMonitorsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'monitors:check-all 
                            {--limit=1000 : Maximum number of monitors per service to check in this run}
                            {--service= : Only check a specific service (uptime, dns, ssh, port, etc.)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check all monitors across all services that are due for checking based on their intervals';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $limit = (int) $this->option('limit');
        $serviceFilter = $this->option('service');
        $totalProcessed = 0;

        $this->info("Starting monitor check dispatch for all services...");

        // Define all available services
        $services = [
            'uptime' => [
                'name' => 'Uptime Monitoring',
                'model' => UptimeMonitor::class,
                'job' => UptimeMonitorCheckJob::class,
            ],
            'ssl' => [
                'name' => 'SSL Monitoring',
                'model' => SSLMonitor::class,
                'job' => SSLMonitorCheckJob::class,
            ],
            'dns' => [
                'name' => 'DNS Monitoring',
                'model' => DNSMonitor::class,
                'job' => DNSMonitorCheckJob::class,
            ],
            // Future services will be added here:
            // 'ssh' => [...],
            // 'port' => [...],
        ];

        // Note: Domain monitors are checked separately via domain-monitors:check command
        // because they don't use intervals - they check daily and send alerts based on expiration dates

        // Filter services if specific service requested
        if ($serviceFilter) {
            if (!isset($services[$serviceFilter])) {
                $this->error("Unknown service: {$serviceFilter}");
                $this->info("Available services: " . implode(', ', array_keys($services)));
                return Command::FAILURE;
            }
            $services = [$serviceFilter => $services[$serviceFilter]];
        }

        // Process each service
        foreach ($services as $serviceKey => $serviceConfig) {
            $this->info("\nProcessing {$serviceConfig['name']}...");
            
            $processed = $this->processService(
                $serviceKey,
                $serviceConfig['model'],
                $serviceConfig['job'],
                $limit
            );
            
            $totalProcessed += $processed;
            $this->info("Dispatched {$processed} check jobs for {$serviceConfig['name']}.");
        }

        $this->info("\n✅ Total: Dispatched {$totalProcessed} monitor check jobs across all services.");

        Log::info("Monitor check dispatch completed", [
            'total_processed' => $totalProcessed,
            'services_checked' => array_keys($services),
        ]);

        return Command::SUCCESS;
    }

    /**
     * Process monitors for a specific service.
     *
     * @param string $serviceKey
     * @param string $modelClass
     * @param string $jobClass
     * @param int $limit
     * @return int Number of jobs dispatched
     */
    private function processService(string $serviceKey, string $modelClass, string $jobClass, int $limit): int
    {
        $processed = 0;

        try {
            // Get monitors that are due for checking
            // A monitor is due if:
            // 1. It's active
            // 2. next_check_at is null OR next_check_at <= NOW()
            $dueMonitors = $modelClass::where('is_active', true)
                ->where(function($query) {
                    $query->whereNull('next_check_at')
                        ->orWhere('next_check_at', '<=', now());
                })
                ->orderBy('next_check_at', 'asc') // Check oldest first
                ->limit($limit)
                ->get();

            if ($dueMonitors->isEmpty()) {
                $this->line("  No {$serviceKey} monitors due for checking.");
                return 0;
            }

            $this->line("  Found {$dueMonitors->count()} {$serviceKey} monitors due for checking.");

            // Dispatch jobs in batches to avoid overwhelming the queue
            $batchSize = 100;
            $batches = $dueMonitors->chunk($batchSize);

            foreach ($batches as $batch) {
                foreach ($batch as $monitor) {
                    try {
                        $jobClass::dispatch($monitor->id);
                        $processed++;
                    } catch (\Exception $e) {
                        Log::error("Failed to dispatch check job for {$serviceKey} monitor {$monitor->id}", [
                            'error' => $e->getMessage(),
                            'service' => $serviceKey,
                        ]);
                        $this->error("  Failed to dispatch check for {$serviceKey} monitor {$monitor->id}: {$e->getMessage()}");
                    }
                }

                // Small delay between batches to avoid overwhelming the queue
                if ($batches->count() > 1) {
                    usleep(100000); // 0.1 second delay
                }
            }

        } catch (\Exception $e) {
            Log::error("Error processing {$serviceKey} monitors", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $this->error("  Error processing {$serviceKey} monitors: {$e->getMessage()}");
        }

        return $processed;
    }
}
