#!/bin/bash

# Stop Laravel Services Script
# This script stops the scheduler and queue workers

cd "$(dirname "$0")" || exit

# Stop Scheduler
if [ -f storage/pids/scheduler.pid ]; then
    SCHEDULER_PID=$(cat storage/pids/scheduler.pid)
    if ps -p $SCHEDULER_PID > /dev/null 2>&1; then
        kill $SCHEDULER_PID
        echo "Scheduler stopped (PID: $SCHEDULER_PID)"
        rm -f storage/pids/scheduler.pid
    else
        echo "Scheduler was not running"
        rm -f storage/pids/scheduler.pid
    fi
else
    echo "Scheduler PID file not found"
fi

# Stop Queue Worker
if [ -f storage/pids/queue-all.pid ]; then
    QUEUE_PID=$(cat storage/pids/queue-all.pid)
    if ps -p $QUEUE_PID > /dev/null 2>&1; then
        kill $QUEUE_PID
        echo "Queue worker stopped (PID: $QUEUE_PID)"
        rm -f storage/pids/queue-all.pid
    else
        echo "Queue worker was not running"
        rm -f storage/pids/queue-all.pid
    fi
else
    echo "Queue worker PID file not found"
fi

echo "All services stopped!"

