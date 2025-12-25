<?php

namespace App\Console\Commands;

use App\Jobs\DomainExpirationCheckJob;
use App\Models\DomainMonitor;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckDomainMonitorsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'domain-monitors:check 
                            {--limit=100 : Maximum number of domain monitors to check in this run}
                            {--force : Force check even if recently checked}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check domain expiration dates for active domain monitors';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $limit = (int) $this->option('limit');
        $force = $this->option('force');

        $this->info("Starting domain expiration check dispatch...");

        try {
            // Get active domain monitors
            // For domain monitors, we check daily (not based on interval like uptime monitors)
            $query = DomainMonitor::where('is_active', true);

            if (!$force) {
                // Only check monitors that haven't been checked today
                $query->where(function($q) {
                    $q->whereNull('last_checked_at')
                      ->orWhereDate('last_checked_at', '<', now()->toDateString());
                });
            }

            $monitors = $query->orderBy('last_checked_at', 'asc')
                ->limit($limit)
                ->get();

            if ($monitors->isEmpty()) {
                $this->line("No domain monitors due for checking.");
                return Command::SUCCESS;
            }

            $this->line("Found {$monitors->count()} domain monitors to check.");

            $processed = 0;
            foreach ($monitors as $monitor) {
                try {
                    DomainExpirationCheckJob::dispatch($monitor->id);
                    $processed++;
                } catch (\Exception $e) {
                    Log::error("Failed to dispatch domain check job for monitor {$monitor->id}", [
                        'error' => $e->getMessage(),
                    ]);
                    $this->error("  Failed to dispatch check for domain monitor {$monitor->id}: {$e->getMessage()}");
                }
            }

            $this->info("✅ Dispatched {$processed} domain expiration check jobs.");

            Log::info("Domain monitor check dispatch completed", [
                'total_processed' => $processed,
            ]);

            return Command::SUCCESS;
        } catch (\Exception $e) {
            Log::error("Error in domain monitor check command", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $this->error("Error: {$e->getMessage()}");
            return Command::FAILURE;
        }
    }
}
