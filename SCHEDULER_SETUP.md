# Laravel Scheduler Setup

## The Issue
The scheduler needs to be added to your server's crontab to run automatically every minute.

## Setup Instructions

### 1. Add to Crontab
Run this command to edit your crontab:
```bash
crontab -e
```

Then add this line (replace `/path/to/pannel` with your actual project path):
```bash
* * * * * cd /Users/denicsann/Desktop/projects/pingxeno/pannel && php artisan schedule:run >> /dev/null 2>&1
```

### 2. Verify It's Running
```bash
# Check if crontab entry exists
crontab -l | grep schedule

# Test scheduler manually
php artisan schedule:run

# See what's scheduled
php artisan schedule:list
```

### 3. For Development (Alternative)
If you don't want to use crontab during development, you can run the scheduler manually in a separate terminal:
```bash
# Run scheduler in a loop (checks every minute)
while true; do php artisan schedule:run; sleep 60; done
```

Or use Laravel's built-in scheduler watcher (if available):
```bash
php artisan schedule:work
```

## How It Works

1. **Crontab runs every minute**: `* * * * *` means "every minute"
2. **Calls Laravel scheduler**: `php artisan schedule:run`
3. **Laravel checks due tasks**: Looks at `routes/console.php` for scheduled commands
4. **Runs `monitors:check`**: Finds monitors due for checking
5. **Dispatches jobs**: Sends `MonitorCheckJob` to queue
6. **Queue worker processes**: Your `queue:work` command processes the jobs

## Current Status

✅ **Scheduler configured**: `routes/console.php` has the schedule
✅ **Query fixed**: Now correctly finds due monitors
✅ **Command working**: `monitors:check` finds and dispatches jobs

⚠️ **Need crontab**: Add the crontab entry for automatic execution

## Testing

### Manual Test
```bash
# Run the check command manually
php artisan monitors:check --limit=10

# Check if monitors are being found
php artisan tinker
>>> \App\Models\Monitor::where('is_active', true)->whereHas('monitoringService', fn($q) => $q->where('key', 'uptime'))->where(function($query) { $query->whereNull('last_checked_at')->orWhereRaw('TIMESTAMPDIFF(MINUTE, monitors.last_checked_at, NOW()) >= monitors.check_interval'); })->count();
```

### Verify Scheduler
```bash
# See when next run is
php artisan schedule:list

# Run scheduler manually
php artisan schedule:run
```





