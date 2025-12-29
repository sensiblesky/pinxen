<?php

use Illuminate\Support\Facades\Route;

// Client Routes - Only accessible by users with role 2 (Client)
// Note: Force email verification and 2FA are applied globally via web.php, but client routes need them too
Route::middleware(['auth', 'force.email.verification', 'force.2fa', 'client'])->group(function () {
    // Client Dashboard
    Route::get('/dashboard', [\App\Http\Controllers\DashboardController::class, 'index'])->name('dashboard');
    
    // Subscriptions
    Route::get('/subscriptions', [\App\Http\Controllers\SubscriptionController::class, 'index'])->name('subscriptions.index');
    Route::get('/subscriptions/my-subscription', [\App\Http\Controllers\SubscriptionController::class, 'show'])->name('subscriptions.show');
    Route::get('/subscriptions/my-subscription/data', [\App\Http\Controllers\SubscriptionController::class, 'getSubscriptionHistoryData'])->name('subscriptions.history.data');
    Route::post('/subscriptions/{subscriptionPlan}/subscribe', [\App\Http\Controllers\SubscriptionController::class, 'subscribe'])->name('subscriptions.subscribe');
    
    // Payment
    Route::get('/subscriptions/{subscriptionPlan}/payment', [\App\Http\Controllers\PaymentController::class, 'show'])->name('subscriptions.payment');
    Route::post('/subscriptions/{subscriptionPlan}/payment/process', [\App\Http\Controllers\PaymentController::class, 'process'])->name('subscriptions.payment.process');
    Route::get('/subscriptions/{subscriptionPlan}/payment/{payment}/success', [\App\Http\Controllers\PaymentController::class, 'success'])->name('subscriptions.payment.success');
    Route::get('/subscriptions/{subscriptionPlan}/payment/{payment}/cancel', [\App\Http\Controllers\PaymentController::class, 'cancel'])->name('subscriptions.payment.cancel');
    Route::get('/subscriptions/payment/{payment}/status', [\App\Http\Controllers\PaymentController::class, 'status'])->name('subscriptions.payment.status');
    
    // Uptime Monitors
    Route::resource('uptime-monitors', \App\Http\Controllers\UptimeMonitorController::class)->parameters([
        'uptime-monitors' => 'uptimeMonitor'
    ]);
    Route::get('/uptime-monitors/{uptimeMonitor}/chart-data', [\App\Http\Controllers\UptimeMonitorController::class, 'getChartData'])->name('uptime-monitors.chart-data');
    Route::get('/uptime-monitors/{uptimeMonitor}/checks-data', [\App\Http\Controllers\UptimeMonitorController::class, 'getChecksData'])->name('uptime-monitors.checks-data');
    Route::get('/uptime-monitors/{uptimeMonitor}/alerts-data', [\App\Http\Controllers\UptimeMonitorController::class, 'getAlertsData'])->name('uptime-monitors.alerts-data');
    
    // Domain Monitors
    Route::resource('domain-monitors', \App\Http\Controllers\DomainMonitorController::class)->parameters([
        'domain-monitors' => 'domainMonitor'
    ]);
    Route::post('/domain-monitors/{domainMonitor}/recheck', [\App\Http\Controllers\DomainMonitorController::class, 'recheck'])->name('domain-monitors.recheck');
    
    // SSL Monitors
    Route::resource('ssl-monitors', \App\Http\Controllers\SSLMonitorController::class)->parameters([
        'ssl-monitors' => 'sslMonitor'
    ]);
    Route::post('/ssl-monitors/{sslMonitor}/recheck', [\App\Http\Controllers\SSLMonitorController::class, 'recheck'])->name('ssl-monitors.recheck');
    
    // DNS Monitors
    Route::resource('dns-monitors', \App\Http\Controllers\DNSMonitorController::class)->parameters([
        'dns-monitors' => 'dnsMonitor'
    ]);
    Route::post('/dns-monitors/{dnsMonitor}/recheck', [\App\Http\Controllers\DNSMonitorController::class, 'recheck'])->name('dns-monitors.recheck');
    
    // API Monitors
    Route::resource('api-monitors', \App\Http\Controllers\ApiMonitorController::class)->parameters([
        'api-monitors' => 'apiMonitor'
    ]);
    Route::get('/api-monitors/{apiMonitor}/checks-data', [\App\Http\Controllers\ApiMonitorController::class, 'getChecksData'])->name('api-monitors.checks-data');
    Route::get('/api-monitors/{apiMonitor}/alerts-data', [\App\Http\Controllers\ApiMonitorController::class, 'getAlertsData'])->name('api-monitors.alerts-data');
    Route::post('/api-monitors/{apiMonitor}/test-now', [\App\Http\Controllers\ApiMonitorController::class, 'testNow'])->name('api-monitors.test-now');
    Route::get('/api-monitors/{apiMonitor}/chart-data', [\App\Http\Controllers\ApiMonitorController::class, 'getChartDataApi'])->name('api-monitors.chart-data');
    Route::post('/api-monitors/{apiMonitor}/duplicate', [\App\Http\Controllers\ApiMonitorController::class, 'duplicate'])->name('api-monitors.duplicate');
    Route::post('/api-monitors/bulk-action', [\App\Http\Controllers\ApiMonitorController::class, 'bulkAction'])->name('api-monitors.bulk-action');
    Route::get('/api-monitors/{apiMonitor}/export-checks', [\App\Http\Controllers\ApiMonitorController::class, 'exportChecks'])->name('api-monitors.export-checks');
    Route::get('/api-monitors/{apiMonitor}/export-alerts', [\App\Http\Controllers\ApiMonitorController::class, 'exportAlerts'])->name('api-monitors.export-alerts');
    
    // Dependency Management
    Route::post('/api-monitors/dependencies/{dependency}/confirm', [\App\Http\Controllers\ApiMonitorController::class, 'confirmDependency'])->name('api-monitors.dependencies.confirm');
    Route::delete('/api-monitors/dependencies/{dependency}', [\App\Http\Controllers\ApiMonitorController::class, 'deleteDependency'])->name('api-monitors.dependencies.delete');
    Route::post('/api-monitors/dependencies/{dependency}/toggle-suppress', [\App\Http\Controllers\ApiMonitorController::class, 'toggleSuppressAlerts'])->name('api-monitors.dependencies.toggle-suppress');
    
    // Replay Failed Requests
    Route::post('/api-monitors/checks/{check}/replay', [\App\Http\Controllers\ApiMonitorController::class, 'replayCheck'])->name('api-monitors.checks.replay');
    Route::get('/api-monitors/checks/{check}/details', [\App\Http\Controllers\ApiMonitorController::class, 'getCheckDetails'])->name('api-monitors.checks.details');
    
    // API Keys (Developer Options)
    Route::resource('api-keys', \App\Http\Controllers\ApiKeyController::class)->parameters([
        'api-keys' => 'apiKey'
    ]);
    Route::post('/api-keys/{apiKey}/regenerate', [\App\Http\Controllers\ApiKeyController::class, 'regenerate'])->name('api-keys.regenerate');
    Route::post('/api-keys/{apiKey}/toggle', [\App\Http\Controllers\ApiKeyController::class, 'toggle'])->name('api-keys.toggle');
    
    // Servers (Server Monitoring)
    Route::resource('servers', \App\Http\Controllers\ServerController::class)->parameters([
        'servers' => 'server'
    ]);
    Route::post('/servers/{server}/regenerate-key', [\App\Http\Controllers\ServerController::class, 'regenerateKey'])->name('servers.regenerate-key');
    Route::get('/servers/{server}/disk-data', [\App\Http\Controllers\ServerController::class, 'getDiskData'])->name('servers.disk-data');
    Route::get('/servers/{server}/network-data', [\App\Http\Controllers\ServerController::class, 'getNetworkData'])->name('servers.network-data');
    Route::get('/servers/{server}/processes-data', [\App\Http\Controllers\ServerController::class, 'getProcessesData'])->name('servers.processes-data');
    
    // Agent download and installation routes
    Route::get('/agents/{server}/download/{os}/{arch?}', [\App\Http\Controllers\AgentController::class, 'download'])->name('agents.download');
    Route::get('/agents/{server}/install-script/{os}/{arch?}', [\App\Http\Controllers\AgentController::class, 'installScript'])->name('agents.install-script');
    Route::get('/agents/{server}/install-oneliner/{os}/{arch?}', [\App\Http\Controllers\AgentController::class, 'installScriptOneLiner'])->name('agents.install-oneliner');
    Route::post('/servers/{server}/install-via-ssh', [\App\Http\Controllers\ServerController::class, 'installViaSSH'])->name('servers.install-via-ssh');
    Route::post('/servers/{server}/test-ssh', [\App\Http\Controllers\ServerController::class, 'testSSH'])->name('servers.test-ssh');
});

