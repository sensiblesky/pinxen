# Start All Jobs and Scheduler - Single Command

This guide shows you how to start **all** monitoring services (scheduler + queue workers) with a **single command**.

## Quick Start

### Option 1: Use the Start Script (Recommended)

```bash
cd /Users/denicsann/Desktop/projects/pingxeno/pannel
./start-all.sh
```

This single command will:
- ✅ Start Laravel Scheduler (runs scheduled tasks every minute)
- ✅ Start Queue Worker for `monitor-checks` (uptime monitors)
- ✅ Start Queue Worker for `ssl-checks` (SSL certificate monitors)
- ✅ Start Queue Worker for `dns-checks` (DNS monitors)
- ✅ Start Queue Worker for `domain-checks` (domain expiration monitors)
- ✅ Start Queue Worker for `emails` (email notifications)

**Press `Ctrl+C` to stop all services gracefully.**

---

### Option 2: One-Liner Command

If you prefer a single command without a script:

```bash
cd /Users/denicsann/Desktop/projects/pingxeno/pannel && \
mkdir -p storage/pids && \
php artisan schedule:work > storage/pids/scheduler.log 2>&1 & \
php artisan queue:work --queue=monitor-checks --tries=3 --timeout=300 --sleep=3 --max-jobs=1000 > storage/pids/queue-monitor-checks.log 2>&1 & \
php artisan queue:work --queue=ssl-checks --tries=3 --timeout=300 --sleep=3 --max-jobs=1000 > storage/pids/queue-ssl-checks.log 2>&1 & \
php artisan queue:work --queue=dns-checks --tries=3 --timeout=300 --sleep=3 --max-jobs=1000 > storage/pids/queue-dns-checks.log 2>&1 & \
php artisan queue:work --queue=domain-checks --tries=3 --timeout=300 --sleep=3 --max-jobs=1000 > storage/pids/queue-domain-checks.log 2>&1 & \
php artisan queue:work --queue=emails --tries=3 --timeout=300 --sleep=3 --max-jobs=1000 > storage/pids/queue-emails.log 2>&1 & \
sleep 1 && \
echo "All services started! Check logs in storage/pids/" && \
ps aux | grep -E "schedule:work|queue:work" | grep -v grep
```

**Simpler version (all queues in one worker):**

```bash
cd /Users/denicsann/Desktop/projects/pingxeno/pannel && \
mkdir -p storage/pids && \
php artisan schedule:work > storage/pids/scheduler.log 2>&1 & \
php artisan queue:work --queue=monitor-checks,ssl-checks,dns-checks,domain-checks,emails --tries=3 --timeout=300 --sleep=3 --max-jobs=1000 > storage/pids/queue-all.log 2>&1 & \
sleep 1 && \
echo "All services started!" && \
ps aux | grep -E "schedule:work|queue:work" | grep -v grep
```

---

## What Gets Started

### 1. Laravel Scheduler
- **Command:** `php artisan schedule:work`
- **Purpose:** Runs scheduled tasks every minute
- **Tasks:**
  - `monitors:check-all` - Checks all monitors every minute
  - `domain-monitors:check` - Checks domain expiration daily
- **Log:** `storage/pids/scheduler.log`

### 2. Queue Workers (5 workers)

#### Monitor Checks Queue
- **Queue:** `monitor-checks`
- **Purpose:** Processes uptime monitor check jobs
- **Log:** `storage/pids/queue-monitor-checks.log`

#### SSL Checks Queue
- **Queue:** `ssl-checks`
- **Purpose:** Processes SSL certificate check jobs
- **Log:** `storage/pids/queue-ssl-checks.log`

#### DNS Checks Queue
- **Queue:** `dns-checks`
- **Purpose:** Processes DNS monitor check jobs
- **Log:** `storage/pids/queue-dns-checks.log`

#### Domain Checks Queue
- **Queue:** `domain-checks`
- **Purpose:** Processes domain expiration check jobs
- **Log:** `storage/pids/queue-domain-checks.log`

#### Emails Queue
- **Queue:** `emails`
- **Purpose:** Processes email notification jobs
- **Log:** `storage/pids/queue-emails.log`

---

## Monitoring Services

### Check if Services are Running

```bash
# Check scheduler
ps aux | grep "schedule:work"

# Check queue workers
ps aux | grep "queue:work"

# Check all at once
ps aux | grep -E "schedule:work|queue:work"
```

### View Logs

```bash
# Scheduler logs
tail -f storage/pids/scheduler.log

# Queue worker logs
tail -f storage/pids/queue-monitor-checks.log
tail -f storage/pids/queue-ssl-checks.log
tail -f storage/pids/queue-dns-checks.log
tail -f storage/pids/queue-domain-checks.log
tail -f storage/pids/queue-emails.log

# View all logs at once
tail -f storage/pids/*.log
```

### Stop All Services

If you used the `start-all.sh` script, press `Ctrl+C` to stop all services gracefully.

To stop manually:

```bash
# Kill scheduler
pkill -f "schedule:work"

# Kill all queue workers
pkill -f "queue:work"
```

---

## Production Setup

For production, use **Supervisor** or **systemd** to keep services running automatically. See the production setup guide for details.

### Quick Supervisor Setup

Create `/etc/supervisor/conf.d/pingxeno.conf`:

```ini
[program:pingxeno-scheduler]
command=php /Users/denicsann/Desktop/projects/pingxeno/pannel/artisan schedule:work
directory=/Users/denicsann/Desktop/projects/pingxeno/pannel
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/Users/denicsann/Desktop/projects/pingxeno/pannel/storage/logs/scheduler.log

[program:pingxeno-queue-monitor-checks]
command=php /Users/denicsann/Desktop/projects/pingxeno/pannel/artisan queue:work --queue=monitor-checks --tries=3 --timeout=300 --sleep=3 --max-jobs=1000
directory=/Users/denicsann/Desktop/projects/pingxeno/pannel
autostart=true
autorestart=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/Users/denicsann/Desktop/projects/pingxeno/pannel/storage/logs/queue-monitor-checks.log

[program:pingxeno-queue-ssl-checks]
command=php /Users/denicsann/Desktop/projects/pingxeno/pannel/artisan queue:work --queue=ssl-checks --tries=3 --timeout=300 --sleep=3 --max-jobs=1000
directory=/Users/denicsann/Desktop/projects/pingxeno/pannel
autostart=true
autorestart=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/Users/denicsann/Desktop/projects/pingxeno/pannel/storage/logs/queue-ssl-checks.log

[program:pingxeno-queue-dns-checks]
command=php /Users/denicsann/Desktop/projects/pingxeno/pannel/artisan queue:work --queue=dns-checks --tries=3 --timeout=300 --sleep=3 --max-jobs=1000
directory=/Users/denicsann/Desktop/projects/pingxeno/pannel
autostart=true
autorestart=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/Users/denicsann/Desktop/projects/pingxeno/pannel/storage/logs/queue-dns-checks.log

[program:pingxeno-queue-domain-checks]
command=php /Users/denicsann/Desktop/projects/pingxeno/pannel/artisan queue:work --queue=domain-checks --tries=3 --timeout=300 --sleep=3 --max-jobs=1000
directory=/Users/denicsann/Desktop/projects/pingxeno/pannel
autostart=true
autorestart=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/Users/denicsann/Desktop/projects/pingxeno/pannel/storage/logs/queue-domain-checks.log

[program:pingxeno-queue-emails]
command=php /Users/denicsann/Desktop/projects/pingxeno/pannel/artisan queue:work --queue=emails --tries=3 --timeout=300 --sleep=3 --max-jobs=1000
directory=/Users/denicsann/Desktop/projects/pingxeno/pannel
autostart=true
autorestart=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/Users/denicsann/Desktop/projects/pingxeno/pannel/storage/logs/queue-emails.log
```

Then reload supervisor:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start all
```

---

## Troubleshooting

### Services Not Starting

1. **Check PHP is available:**
   ```bash
   php -v
   ```

2. **Check Laravel is working:**
   ```bash
   php artisan --version
   ```

3. **Check database connection:**
   ```bash
   php artisan tinker
   >>> DB::connection()->getPdo();
   ```

### Jobs Not Processing

1. **Check queue workers are running:**
   ```bash
   ps aux | grep queue:work
   ```

2. **Check for failed jobs:**
   ```bash
   php artisan queue:failed
   ```

3. **Check queue status:**
   ```bash
   php artisan queue:monitor
   ```

### Scheduler Not Running

1. **Test scheduler manually:**
   ```bash
   php artisan schedule:run
   ```

2. **Check scheduled tasks:**
   ```bash
   php artisan schedule:list
   ```

3. **Check scheduler logs:**
   ```bash
   tail -f storage/pids/scheduler.log
   ```

---

## Summary

**Single Command to Start Everything:**
```bash
./start-all.sh
```

That's it! One command starts all services needed for monitoring.

