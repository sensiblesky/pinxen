#!/bin/bash

# Start Laravel Services Script
# This script starts the scheduler and queue workers in the background

cd "$(dirname "$0")" || exit

# Create necessary directories
mkdir -p storage/pids
mkdir -p storage/logs

# Check if services are already running
if [ -f storage/pids/scheduler.pid ]; then
    if ps -p $(cat storage/pids/scheduler.pid) > /dev/null 2>&1; then
        echo "Scheduler is already running (PID: $(cat storage/pids/scheduler.pid))"
    else
        rm -f storage/pids/scheduler.pid
    fi
fi

if [ -f storage/pids/queue-all.pid ]; then
    if ps -p $(cat storage/pids/queue-all.pid) > /dev/null 2>&1; then
        echo "Queue worker is already running (PID: $(cat storage/pids/queue-all.pid))"
    else
        rm -f storage/pids/queue-all.pid
    fi
fi

# Start Scheduler
if [ ! -f storage/pids/scheduler.pid ]; then
    nohup php artisan schedule:work > storage/pids/scheduler.log 2>&1 &
    SCHEDULER_PID=$!
    echo $SCHEDULER_PID > storage/pids/scheduler.pid
    echo "Scheduler started (PID: $SCHEDULER_PID)"
else
    echo "Scheduler already running"
fi

# Start Queue Worker
if [ ! -f storage/pids/queue-all.pid ]; then
    nohup php artisan queue:work --queue=monitor-checks,ssl-checks,dns-checks,domain-checks,emails --tries=3 --timeout=300 --sleep=3 --max-jobs=1000 > storage/pids/queue-all.log 2>&1 &
    QUEUE_PID=$!
    echo $QUEUE_PID > storage/pids/queue-all.pid
    echo "Queue worker started (PID: $QUEUE_PID)"
else
    echo "Queue worker already running"
fi

echo "All services started successfully!"
echo "Check logs:"
echo "  - Scheduler: tail -f storage/pids/scheduler.log"
echo "  - Queue: tail -f storage/pids/queue-all.log"

