<?php

namespace App\Console\Commands;

use App\Jobs\MonitorCheckJob;
use App\Models\Monitor;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckMonitorsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'monitors:check {--limit=1000 : Maximum number of monitors to check in this run}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check all monitors that are due for checking and dispatch check jobs';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $limit = (int) $this->option('limit');
        $processed = 0;

        $this->info("Starting monitor check dispatch...");

        // Get monitors that are due for checking
        // A monitor is due if:
        // 1. It's active
        // 2. It's an uptime monitor (for now)
        // 3. last_checked_at is null OR (now - last_checked_at) >= check_interval minutes

        // Get monitors that are due for checking
        // A monitor is due if last_checked_at is null OR enough time has passed
        // Using DB::raw to ensure proper column references
        $dueMonitors = Monitor::where('is_active', true)
            ->whereHas('monitoringService', function($query) {
                $query->where('key', 'uptime');
            })
            ->where(function($query) {
                $query->whereNull('last_checked_at')
                    ->orWhereRaw('TIMESTAMPDIFF(MINUTE, `monitors`.`last_checked_at`, NOW()) >= `monitors`.`check_interval`');
            })
            ->orderBy('last_checked_at', 'asc') // Check oldest first
            ->limit($limit)
            ->pluck('id');

        $totalDue = $dueMonitors->count();

        if ($totalDue === 0) {
            $this->info("No monitors due for checking.");
            return Command::SUCCESS;
        }

        $this->info("Found {$totalDue} monitors due for checking. Dispatching jobs...");

        // Dispatch jobs in batches to avoid overwhelming the queue
        $batchSize = 100;
        $batches = $dueMonitors->chunk($batchSize);

        foreach ($batches as $batch) {
            foreach ($batch as $monitorId) {
                try {
                    MonitorCheckJob::dispatch($monitorId);
                    $processed++;
                } catch (\Exception $e) {
                    Log::error("Failed to dispatch check job for monitor {$monitorId}", [
                        'error' => $e->getMessage(),
                    ]);
                    $this->error("Failed to dispatch check for monitor {$monitorId}: {$e->getMessage()}");
                }
            }

            // Small delay between batches to avoid overwhelming the queue
            if ($batches->count() > 1) {
                usleep(100000); // 0.1 second delay
            }
        }

        $this->info("Dispatched {$processed} monitor check jobs.");

        Log::info("Monitor check dispatch completed", [
            'total_due' => $totalDue,
            'processed' => $processed,
        ]);

        return Command::SUCCESS;
    }
}
