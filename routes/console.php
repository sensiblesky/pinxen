<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule all monitor checks to run every minute
// This command checks all service types (uptime, DNS, SSH, port, etc.)
// Each monitor is checked based on its individual check_interval
Schedule::command('monitors:check-all')
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground()
    ->onOneServer(); // If using multiple servers, only run on one

// Schedule domain expiration checks to run daily
// Domain monitors check expiration dates and send alerts based on alert intervals
Schedule::command('domain-monitors:check')
    ->daily()
    ->withoutOverlapping()
    ->runInBackground()
    ->onOneServer();
