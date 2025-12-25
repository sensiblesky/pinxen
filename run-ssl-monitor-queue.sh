#!/bin/bash

# SSL Monitor Queue Worker
# This script starts the queue worker for SSL monitor checks

echo "Starting SSL Monitor Queue Worker..."
echo "Press Ctrl+C to stop"
echo ""

php artisan queue:work --queue=ssl-checks --tries=3 --timeout=60 --sleep=3
