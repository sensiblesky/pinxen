# Monitor Scheduler System Guide

## Overview

The monitoring system uses a **queue-based architecture** with a **cron scheduler** to check monitors based on their individual intervals. This allows the system to handle millions of monitors efficiently.

## Architecture

### 1. Main Scheduler Command
**`CheckAllMonitorsCommand`** (`php artisan monitors:check-all`)
- Runs every minute via Laravel scheduler
- Checks all service types (uptime, DNS, SSH, port, etc.)
- Finds monitors that are due for checking based on their `check_interval`
- Dispatches service-specific jobs to the queue

### 2. Service-Specific Jobs
Each service type has its own job:
- **`UptimeMonitorCheckJob`** - Checks HTTP/HTTPS uptime
- **`DnsMonitorCheckJob`** - (Future) Checks DNS records
- **`SshMonitorCheckJob`** - (Future) Checks SSH availability
- **`PortMonitorCheckJob`** - (Future) Checks port availability

### 3. Queue System
- All check jobs are dispatched to the `monitor-checks` queue
- Jobs are processed asynchronously
- Supports retries (3 attempts with exponential backoff)

## How It Works

### Step 1: Scheduler Runs Every Minute
```php
Schedule::command('monitors:check-all')
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground()
    ->onOneServer();
```

### Step 2: Command Finds Due Monitors
For each service type, the command:
1. Queries monitors where:
   - `is_active = true`
   - `last_checked_at IS NULL` OR `TIMESTAMPDIFF(MINUTE, last_checked_at, NOW()) >= check_interval`
2. Orders by `last_checked_at ASC` (oldest first)
3. Limits to prevent overwhelming the queue

### Step 3: Jobs Are Dispatched
- Each due monitor gets a job dispatched to the queue
- Jobs are batched (100 at a time) to avoid overwhelming the queue
- Small delay (0.1s) between batches

### Step 4: Jobs Execute
Each job:
1. Loads the monitor
2. Performs the check (HTTP, DNS, SSH, etc.)
3. Records the result in the service-specific checks table
4. Updates monitor status (with false positive prevention)
5. Sends alerts if status changed

## Adding New Service Types

### 1. Create the Service Table
```bash
php artisan make:migration create_monitors_service_dns_table
```

### 2. Create the Model
```bash
php artisan make:model DnsMonitor
```

### 3. Create the Check Job
```bash
php artisan make:job DnsMonitorCheckJob
```

### 4. Register in CheckAllMonitorsCommand
Add to the `$services` array:
```php
'dns' => [
    'name' => 'DNS Monitoring',
    'model' => DnsMonitor::class,
    'job' => DnsMonitorCheckJob::class,
],
```

## Configuration

### Queue Configuration
Make sure your `.env` has:
```env
QUEUE_CONNECTION=database  # or redis, sqs, etc.
```

### Running the Queue Worker
```bash
php artisan queue:work --queue=monitor-checks
```

Or use supervisor/systemd for production.

### Running the Scheduler
Add to crontab:
```bash
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

Or use Laravel's scheduler:
```bash
php artisan schedule:work
```

## Monitoring Interval Logic

Each monitor has its own `check_interval` (in minutes). The system checks:
- If `last_checked_at` is NULL → monitor is due
- If `TIMESTAMPDIFF(MINUTE, last_checked_at, NOW()) >= check_interval` → monitor is due

**Example:**
- Monitor A: `check_interval = 5` minutes, `last_checked_at = 10:00`
- Current time: `10:04` → Not due (only 4 minutes passed)
- Current time: `10:05` → Due (5 minutes passed)

## Performance Considerations

### Batch Processing
- Monitors are processed in batches of 100
- Small delay (0.1s) between batches prevents queue overload

### Queue Limits
- Default limit: 1000 monitors per service per run
- Adjustable via `--limit` option

### Parallel Processing
- Multiple queue workers can process jobs simultaneously
- Each job is independent and can run in parallel

## Testing

### Test the Command
```bash
php artisan monitors:check-all
```

### Test Specific Service
```bash
php artisan monitors:check-all --service=uptime
```

### Test with Limit
```bash
php artisan monitors:check-all --limit=10
```

### Check Queue Status
```bash
php artisan queue:work --queue=monitor-checks --once
```

## Troubleshooting

### Monitors Not Being Checked
1. Check if scheduler is running: `php artisan schedule:list`
2. Check if queue worker is running: `php artisan queue:work`
3. Check monitor `is_active` status
4. Check `last_checked_at` and `check_interval` values

### Jobs Failing
1. Check logs: `storage/logs/laravel.log`
2. Check failed jobs: `php artisan queue:failed`
3. Retry failed jobs: `php artisan queue:retry all`

### Performance Issues
1. Increase queue workers
2. Use Redis queue driver
3. Adjust batch size in command
4. Add more servers (horizontal scaling)

## Future Enhancements

- [ ] Priority queues (critical monitors checked first)
- [ ] Dynamic interval adjustment based on status
- [ ] Distributed checking across multiple servers
- [ ] Real-time status updates via WebSockets
- [ ] Advanced alerting (SMS, WhatsApp, Telegram, Discord)






