# Monitor Scheduler Setup Commands

## Queue Worker Commands

### Start Queue Worker
Process monitor check jobs from the queue:
```bash
php artisan queue:work --queue=monitor-checks
```

### Start Queue Worker for SSL Monitors
SSL monitors use a separate queue. Process SSL check jobs:
```bash
php artisan queue:work --queue=ssl-checks
```

### Start Queue Worker for All Queues
Process all monitor queues (uptime, SSL, domain, etc.):
```bash
php artisan queue:work --queue=monitor-checks,ssl-checks,domain-checks
```

### Start Queue Worker (Production - with supervisor)
For production environments, use supervisor or systemd to keep the worker running:
```bash
php artisan queue:work --queue=monitor-checks --tries=3 --timeout=60
```

### Check Queue Status
```bash
php artisan queue:work --queue=monitor-checks --once
```

### View Failed Jobs
```bash
php artisan queue:failed
```

### Retry Failed Jobs
```bash
# Retry all failed jobs
php artisan queue:retry all

# Retry specific job
php artisan queue:retry {job-id}
```

### Clear Failed Jobs
```bash
php artisan queue:flush
```

---

## Scheduler Commands

### Production - Add to Crontab
Add this line to your server's crontab to run the scheduler every minute:
```bash
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

**To edit crontab:**
```bash
crontab -e
```

**Replace `/path-to-project` with your actual project path**, for example:
```bash
* * * * * cd /Users/denicsann/Desktop/projects/pingxeno/pannel && php artisan schedule:run >> /dev/null 2>&1
```

### Development - Run Manually
For development/testing, run the scheduler manually:
```bash
php artisan schedule:work
```

This will run the scheduler continuously and execute scheduled tasks.

### List Scheduled Tasks
View all scheduled tasks:
```bash
php artisan schedule:list
```

---

## Testing Commands

### Test the Main Command
Run the monitor check command manually:
```bash
php artisan monitors:check-all
```

### Test Specific Service
Test only uptime monitors:
```bash
php artisan monitors:check-all --service=uptime
```

Test only SSL monitors:
```bash
php artisan monitors:check-all --service=ssl
```

### Test with Limit
Limit the number of monitors checked (useful for testing):
```bash
php artisan monitors:check-all --limit=10
```

### Test with Verbose Output
See detailed output:
```bash
php artisan monitors:check-all -v
```

---

## Complete Setup Workflow

### 1. Run Migrations
```bash
php artisan migrate
```

### 2. Start Queue Worker (Terminal 1)
For all monitors (uptime, SSL, etc.):
```bash
php artisan queue:work --queue=monitor-checks,ssl-checks
```

Or start separate workers for each queue:
```bash
# Terminal 1 - Uptime monitors
php artisan queue:work --queue=monitor-checks

# Terminal 2 - SSL monitors
php artisan queue:work --queue=ssl-checks
```

### 3. Start Scheduler (Terminal 2)
```bash
php artisan schedule:work
```

Or for production, add to crontab:
```bash
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

### 4. Test the System
```bash
php artisan monitors:check-all --limit=5
```

---

## Production Deployment

### Step 1: Configure Queue Connection
Make sure your `.env` has:
```env
QUEUE_CONNECTION=database
# or
QUEUE_CONNECTION=redis
```

### Step 2: Setup Supervisor (Recommended)
Create supervisor config file: `/etc/supervisor/conf.d/laravel-worker.conf`

```ini
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path-to-project/artisan queue:work --queue=monitor-checks --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/path-to-project/storage/logs/worker.log
stopwaitsecs=3600
```

Then:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start laravel-worker:*
```

### Step 3: Setup Crontab
```bash
crontab -e
```

Add:
```bash
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

---

## Troubleshooting

### Check if Queue is Processing
```bash
php artisan queue:work --queue=monitor-checks --once
```

### Check Scheduler Status
```bash
php artisan schedule:list
```

### View Logs
```bash
tail -f storage/logs/laravel.log
```

### Check Database for Jobs
```bash
php artisan tinker
>>> DB::table('jobs')->count();
>>> DB::table('failed_jobs')->count();
```

### Clear All Jobs
```bash
php artisan queue:clear
```

---

## Command Options Reference

### monitors:check-all
```bash
php artisan monitors:check-all [options]

Options:
  --limit[=LIMIT]      Maximum number of monitors per service to check [default: "1000"]
  --service[=SERVICE]   Only check a specific service (uptime, dns, ssh, port, etc.)
  -h, --help           Display help
  -v, --verbose        Increase verbosity
```

### queue:work
```bash
php artisan queue:work [options]

Options:
  --queue=QUEUE        Queue name (e.g., monitor-checks)
  --tries=TRIES        Number of retry attempts [default: 3]
  --timeout=TIMEOUT    Job timeout in seconds [default: 60]
  --once               Process only one job
  --stop-when-empty    Stop when queue is empty
```

---

## Quick Reference

| Task | Command |
|------|---------|
| Start queue worker | `php artisan queue:work --queue=monitor-checks` |
| Start scheduler (dev) | `php artisan schedule:work` |
| Add scheduler to crontab | `* * * * * cd /path && php artisan schedule:run >> /dev/null 2>&1` |
| Test monitors | `php artisan monitors:check-all` |
| Test specific service | `php artisan monitors:check-all --service=uptime` |
| Test with limit | `php artisan monitors:check-all --limit=10` |
| View failed jobs | `php artisan queue:failed` |
| Retry failed jobs | `php artisan queue:retry all` |
| View scheduled tasks | `php artisan schedule:list` |
| Check logs | `tail -f storage/logs/laravel.log` |

---

## Notes

- **Queue Worker**: Must be running continuously to process jobs
- **Scheduler**: Runs every minute to dispatch new check jobs
- **Both are required**: Queue worker processes jobs, scheduler creates them
- **Production**: Use supervisor/systemd for queue worker, crontab for scheduler
- **Development**: Can run both manually in separate terminals


