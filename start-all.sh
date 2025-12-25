#!/bin/bash

# PingXeno - Start All Jobs and Scheduler
# This script starts the Laravel scheduler and all queue workers
# Run this single command to start everything needed for monitoring

cd "$(dirname "$0")"

# Colors for output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}  PingXeno - Starting All Services${NC}"
echo -e "${GREEN}========================================${NC}"
echo ""

# Check if PHP is available
if ! command -v php &> /dev/null; then
    echo -e "${RED}Error: PHP is not installed or not in PATH${NC}"
    exit 1
fi

# Create logs directory if it doesn't exist
mkdir -p storage/logs

# PID file directory
PID_DIR="storage/pids"
mkdir -p "$PID_DIR"

# Function to cleanup on exit
cleanup() {
    echo ""
    echo -e "${YELLOW}Shutting down all services...${NC}"
    
    # Kill scheduler
    if [ -f "$PID_DIR/scheduler.pid" ]; then
        kill $(cat "$PID_DIR/scheduler.pid") 2>/dev/null
        rm -f "$PID_DIR/scheduler.pid"
    fi
    
    # Kill all queue workers
    if [ -f "$PID_DIR/queue-workers.pid" ]; then
        while read pid; do
            kill $pid 2>/dev/null
        done < "$PID_DIR/queue-workers.pid"
        rm -f "$PID_DIR/queue-workers.pid"
    fi
    
    echo -e "${GREEN}All services stopped.${NC}"
    exit 0
}

# Trap Ctrl+C
trap cleanup SIGINT SIGTERM

echo -e "${YELLOW}Starting Laravel Scheduler...${NC}"
php artisan schedule:work > "$PID_DIR/scheduler.log" 2>&1 &
SCHEDULER_PID=$!
echo $SCHEDULER_PID > "$PID_DIR/scheduler.pid"
echo -e "${GREEN}✓ Scheduler started (PID: $SCHEDULER_PID)${NC}"
echo ""

echo -e "${YELLOW}Starting Queue Workers...${NC}"

# Queue configuration
QUEUES=(
    "monitor-checks"
    "ssl-checks"
    "dns-checks"
    "domain-checks"
    "emails"
)

QUEUE_PIDS=()

# Start queue workers for each queue
for queue in "${QUEUES[@]}"; do
    echo -e "  Starting worker for queue: ${YELLOW}$queue${NC}"
    php artisan queue:work \
        --queue="$queue" \
        --tries=3 \
        --timeout=300 \
        --sleep=3 \
        --max-jobs=1000 \
        > "$PID_DIR/queue-$queue.log" 2>&1 &
    
    QUEUE_PID=$!
    QUEUE_PIDS+=($QUEUE_PID)
    echo $QUEUE_PID >> "$PID_DIR/queue-workers.pid"
    echo -e "  ${GREEN}✓ Queue worker started for '$queue' (PID: $QUEUE_PID)${NC}"
done

echo ""
echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}  All Services Started Successfully!${NC}"
echo -e "${GREEN}========================================${NC}"
echo ""
echo -e "Scheduler PID: ${YELLOW}$SCHEDULER_PID${NC}"
echo -e "Queue Workers: ${YELLOW}${#QUEUE_PIDS[@]}${NC} workers running"
echo ""
echo -e "Logs location:"
echo -e "  - Scheduler: ${YELLOW}storage/pids/scheduler.log${NC}"
for queue in "${QUEUES[@]}"; do
    echo -e "  - Queue $queue: ${YELLOW}storage/pids/queue-$queue.log${NC}"
done
echo ""
echo -e "${YELLOW}Press Ctrl+C to stop all services${NC}"
echo ""

# Wait for all background processes
wait

