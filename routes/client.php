<?php

use Illuminate\Support\Facades\Route;

// Client Routes - Only accessible by users with role 2 (Client)
// Note: Force email verification and 2FA are applied globally via web.php, but client routes need them too
Route::middleware(['auth', 'force.email.verification', 'force.2fa', 'client'])->group(function () {
    // Client Dashboard
    Route::get('/dashboard', function () {
        return view('client.dashboard');
    })->name('dashboard');
    
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
});

