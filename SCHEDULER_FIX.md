# Fix: Scheduler Not Running Automatically

## The Problem

The scheduler is configured in code but **not running automatically** because it's not in your crontab.

## Solution Options

### Option 1: Add to Crontab (Recommended for Production)

1. Open crontab:
```bash
crontab -e
```

2. Add this line (update the path):
```bash
* * * * * cd /Users/denicsann/Desktop/projects/pingxeno/pannel && php artisan schedule:run >> /dev/null 2>&1
```

3. Save and exit

4. Verify it's added:
```bash
crontab -l | grep schedule
```

### Option 2: Run Scheduler Manually (For Development)

Run this in a **separate terminal** (keep it running):

```bash
cd /Users/denicsann/Desktop/projects/pingxeno/pannel
./start-scheduler.sh
```

Or manually:
```bash
while true; do php artisan schedule:run; sleep 60; done
```

### Option 3: Use Laravel's Schedule Work (Laravel 11+)

```bash
php artisan schedule:work
```

This runs the scheduler continuously (similar to Option 2 but built-in).

## Verify It's Working

1. **Check if scheduler is running:**
```bash
ps aux | grep "schedule:run\|schedule:work"
```

2. **Test the command manually:**
```bash
php artisan monitors:check --limit=10
```

3. **Check monitor status:**
```bash
php artisan tinker
>>> $monitor = \App\Models\Monitor::first();
>>> echo "Last checked: " . ($monitor->last_checked_at ?? 'Never') . "\n";
>>> echo "Minutes since: " . ($monitor->last_checked_at ? $monitor->last_checked_at->diffInMinutes(now()) : 0) . "\n";
```

4. **Watch logs:**
```bash
tail -f storage/logs/laravel.log | grep -i "monitor check"
```

## Expected Behavior

- **Every minute**: Scheduler runs `monitors:check`
- **Command finds due monitors**: Where `(now - last_checked_at) >= check_interval`
- **Jobs dispatched**: Each due monitor gets a check job
- **Queue worker processes**: Your `queue:work` processes the jobs
- **Monitor checked**: Results saved, status updated

## Current Status

✅ **Query fixed**: Now correctly finds due monitors  
✅ **Command working**: `monitors:check` finds and dispatches jobs  
✅ **Scheduler configured**: In `routes/console.php`  
⚠️ **Scheduler not running**: Need to add to crontab or run manually

## Quick Start (Development)

**Terminal 1** - Queue Worker:
```bash
php artisan queue:work --queue=monitor-checks --tries=3 --timeout=60
```

**Terminal 2** - Scheduler:
```bash
php artisan schedule:work
```

Now monitors will be checked automatically every minute based on their interval!





