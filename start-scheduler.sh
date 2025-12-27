#!/bin/bash

# Laravel Scheduler Runner
# This script runs the Laravel scheduler in a loop
# Use this for development or if you can't use crontab

cd "$(dirname "$0")"

echo "Starting Laravel Scheduler..."
echo "Press Ctrl+C to stop"
echo ""

while true; do
    php artisan schedule:run
    sleep 60
done






