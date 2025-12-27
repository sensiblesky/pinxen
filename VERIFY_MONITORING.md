# How to Verify Monitoring is Working

## Quick Verification

### 1. Check if Monitor is Being Checked
```bash
php artisan tinker
>>> $monitor = \App\Models\Monitor::first();
>>> echo "Last Checked: " . ($monitor->last_checked_at ?? 'Never') . "\n";
>>> echo "Status: " . $monitor->status . "\n";
>>> echo "Total Checks: " . $monitor->checks()->count() . "\n";
```

### 2. Check Queue Status
```bash
# See pending jobs
php artisan queue:monitor monitor-checks

# Or check database
php artisan tinker
>>> DB::table('jobs')->where('queue', 'monitor-checks')->count();
```

### 3. Manually Trigger Check
```bash
# Run the check command manually
php artisan monitors:check --limit=10

# Or dispatch a job for specific monitor
php artisan tinker
>>> \App\Jobs\MonitorCheckJob::dispatch(1);
```

### 4. Check Logs
```bash
# Watch logs in real-time
tail -f storage/logs/laravel.log | grep -i monitor

# Or check recent monitor activity
tail -n 100 storage/logs/laravel.log | grep "Monitor check"
```

## Queue Worker Commands

### Start Queue Worker (Development)
```bash
# Basic (silent)
php artisan queue:work --queue=monitor-checks --tries=3 --timeout=60

# Verbose (shows job processing)
php artisan queue:work --queue=monitor-checks --tries=3 --timeout=60 --verbose

# Process one job and exit (for testing)
php artisan queue:work --queue=monitor-checks --once --verbose
```

### Check if Queue Worker is Running
```bash
ps aux | grep "queue:work"
```

## Expected Behavior

1. **Every Minute**: Scheduler runs `monitors:check` command
2. **Command Finds Due Monitors**: Monitors where `last_checked_at` is NULL or older than `check_interval`
3. **Jobs Dispatched**: Each due monitor gets a `MonitorCheckJob` dispatched to queue
4. **Queue Worker Processes**: Queue worker picks up jobs and performs HTTP checks
5. **Results Stored**: Check results saved to `monitor_checks` table
6. **Status Updated**: Monitor status updated if changed (with false positive prevention)

## Troubleshooting

### Jobs Not Processing
1. Check queue worker is running: `ps aux | grep queue:work`
2. Check for failed jobs: `php artisan queue:failed`
3. Check logs: `tail -f storage/logs/laravel.log`

### Monitor Not Being Checked
1. Verify monitor has `monitoring_service_id` set
2. Verify monitor is active: `is_active = 1`
3. Verify monitor service key is 'uptime'
4. Check if monitor is due: `last_checked_at` is NULL or older than `check_interval`

### Scheduler Not Running
1. Verify crontab is set up: `crontab -l`
2. Test scheduler manually: `php artisan schedule:run`
3. Check scheduler list: `php artisan schedule:list`






