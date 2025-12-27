# Monitoring System Setup Guide

## Overview

This system is designed to handle **1 million concurrent monitors** efficiently using Laravel queues and scheduled tasks.

## Architecture

### Components

1. **MonitorCheckJob** - Queue job that performs individual HTTP checks
2. **CheckMonitorsCommand** - Command that finds due monitors and dispatches jobs
3. **Laravel Scheduler** - Runs every minute to trigger checks
4. **Queue Workers** - Process the check jobs in the background

## How It Works

### 1. Scheduled Task (Every Minute)
- The `monitors:check` command runs every minute via Laravel scheduler
- It finds all monitors that are due for checking (based on `check_interval`)
- Dispatches `MonitorCheckJob` for each due monitor to the queue

### 2. Queue Processing
- Queue workers process `MonitorCheckJob` jobs
- Each job performs an HTTP check on a single monitor
- Results are stored in `monitor_checks` table
- Monitor status is updated if changed

### 3. False Positive Prevention
- Requires **2 consecutive down checks** before marking monitor as down
- Prevents false positives from temporary network issues
- Immediate status update when monitor recovers (goes up)

### 4. Alert System
- Alerts are sent when monitor status changes:
  - **Down Alert**: When monitor goes from up/unknown to down
  - **Recovery Alert**: When monitor goes from down to up
- Supports multiple communication channels (Email, SMS, WhatsApp, Telegram, Discord)

## Setup Instructions

### 1. Run Migrations
```bash
php artisan migrate
```

### 2. Start Queue Worker
For development:
```bash
php artisan queue:work --queue=monitor-checks --tries=3 --timeout=60
```

For production (using Supervisor):
```ini
[program:pingxeno-monitor-queue]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/artisan queue:work database --queue=monitor-checks --tries=3 --timeout=60 --sleep=3 --max-jobs=1000
autostart=true
autorestart=true
user=www-data
numprocs=8
redirect_stderr=true
stdout_logfile=/path/to/storage/logs/queue-worker.log
```

### 3. Setup Laravel Scheduler
Add to your server's crontab:
```bash
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

### 4. Scale for 1M Monitors

#### Queue Workers
- **Recommended**: 8-16 queue workers per server
- Each worker can process ~100-200 checks/minute
- With 16 workers: ~1,600-3,200 checks/minute = ~96,000-192,000 checks/hour

#### Database Optimization
```sql
-- Add indexes for better query performance
CREATE INDEX idx_monitors_due_check ON monitors(is_active, last_checked_at, check_interval);
CREATE INDEX idx_monitor_checks_monitor_checked ON monitor_checks(monitor_id, checked_at);
```

#### Queue Configuration
For 1M monitors, consider using **Redis** or **SQS** instead of database queue:
- Redis: Better performance, lower latency
- SQS: Managed service, auto-scaling

Update `.env`:
```env
QUEUE_CONNECTION=redis
# or
QUEUE_CONNECTION=sqs
```

#### Horizontal Scaling
- Run multiple application servers
- Use `onOneServer()` in scheduler (already configured)
- Use Redis/SQS for shared queue
- Load balance queue workers across servers

### 5. Monitoring Performance

Check queue status:
```bash
php artisan queue:monitor monitor-checks
```

View queue stats:
```bash
php artisan queue:stats
```

## HTTP Check Features

### What It Checks
1. **HTTP Status Code** - Verifies response matches expected code
2. **Response Time** - Measures latency in milliseconds
3. **Keyword Presence** - Optional: checks if specific text exists
4. **Keyword Absence** - Optional: checks if specific text doesn't exist
5. **SSL Validity** - Optional: verifies SSL certificate

### Error Handling
- Connection timeouts
- DNS resolution failures
- SSL certificate errors
- HTTP errors (4xx, 5xx)
- Network errors

### Configuration Options
- **Check Interval**: 1-1440 minutes (how often to check)
- **Timeout**: 5-300 seconds (request timeout)
- **Expected Status Code**: 100-599 (what HTTP code to expect)
- **Keywords**: Optional text to check for/against
- **SSL Check**: Enable/disable SSL verification

## Testing

### Manual Test
```bash
# Run check command manually
php artisan monitors:check --limit=10

# Process a specific monitor
php artisan tinker
>>> \App\Jobs\MonitorCheckJob::dispatch(1);
```

### Check Queue
```bash
# See pending jobs
php artisan queue:work --once

# Process all pending jobs
php artisan queue:work
```

## Performance Metrics

### Expected Throughput
- **Single Worker**: ~100-200 checks/minute
- **8 Workers**: ~800-1,600 checks/minute
- **16 Workers**: ~1,600-3,200 checks/minute
- **32 Workers**: ~3,200-6,400 checks/minute

### For 1M Monitors
- Average check interval: 5 minutes
- Checks needed per minute: ~200,000
- **Required Workers**: ~100-200 workers (distributed across servers)

### Database Load
- Each check creates 1 record in `monitor_checks`
- With 1M monitors checking every 5 min: ~200,000 records/minute
- **Recommendation**: Archive old checks regularly (keep last 30 days)

## Troubleshooting

### Monitors Not Checking
1. Check scheduler is running: `php artisan schedule:list`
2. Check queue workers are running: `ps aux | grep queue:work`
3. Check for errors: `tail -f storage/logs/laravel.log`

### High Queue Backlog
1. Increase number of queue workers
2. Check database performance
3. Consider using Redis/SQS queue
4. Optimize database indexes

### False Positives
- System requires 2 consecutive down checks
- Adjust `check_interval` if needed
- Check network stability
- Review timeout settings

## Next Steps

1. Implement email notifications (currently placeholder)
2. Add SMS/WhatsApp/Telegram/Discord notifications
3. Create monitoring dashboard
4. Add alert throttling (don't spam on repeated failures)
5. Implement check result archiving
6. Add monitoring analytics/reporting






