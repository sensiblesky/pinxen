<?php

use App\Http\Controllers\PanelController;
use Illuminate\Support\Facades\Route;

// Admin Panel Route - Only accessible by users with role 1
// Note: Force email verification and 2FA are applied globally via web.php, but panel routes need them too
Route::middleware(['auth', 'force.email.verification', 'force.2fa', 'admin'])->group(function () {
    Route::get('/panel', [PanelController::class, 'index'])->name('panel');
    
    // Users Management
    // IMPORTANT: Specific routes must come BEFORE resource routes to avoid route conflicts
    Route::get('panel/users/data', [\App\Http\Controllers\Panel\UserController::class, 'getUsersData'])->name('panel.users.data');
    Route::post('panel/users/{user}/toggle-status', [\App\Http\Controllers\Panel\UserController::class, 'toggleStatus'])->name('panel.users.toggle-status');
    Route::post('panel/users/{user}/restore', [\App\Http\Controllers\Panel\UserController::class, 'restore'])->name('panel.users.restore');
    Route::post('panel/users/{user}/update-language-timezone', [\App\Http\Controllers\Panel\UserController::class, 'updateLanguageTimezone'])->name('panel.users.update-language-timezone');
    Route::post('panel/users/{user}/assign-subscription', [\App\Http\Controllers\Panel\UserController::class, 'assignSubscription'])->name('panel.users.assign-subscription');
    Route::post('panel/users/{user}/subscriptions/{subscription}/update-status', [\App\Http\Controllers\Panel\UserController::class, 'updateSubscriptionStatus'])->name('panel.users.subscriptions.update-status');
    
    // System Configuration
    Route::get('panel/system-configuration', [\App\Http\Controllers\Panel\SystemConfigurationController::class, 'index'])->name('panel.system-configuration.index');
    Route::put('panel/system-configuration', [\App\Http\Controllers\Panel\SystemConfigurationController::class, 'update'])->name('panel.system-configuration.update');
    
    // Communication Channels
    Route::get('panel/comm-channels', [\App\Http\Controllers\Panel\CommChannelsController::class, 'index'])->name('panel.comm-channels.index');
    Route::put('panel/comm-channels', [\App\Http\Controllers\Panel\CommChannelsController::class, 'update'])->name('panel.comm-channels.update');
    
    // Payment Gateway
    Route::get('panel/payment-gateway', [\App\Http\Controllers\Panel\PaymentGatewayController::class, 'index'])->name('panel.payment-gateway.index');
    Route::put('panel/payment-gateway', [\App\Http\Controllers\Panel\PaymentGatewayController::class, 'update'])->name('panel.payment-gateway.update');
    
    // Auth & Single Sign On
    Route::get('panel/auth-sso', [\App\Http\Controllers\Panel\AuthSSOController::class, 'index'])->name('panel.auth-sso.index');
    Route::put('panel/auth-sso', [\App\Http\Controllers\Panel\AuthSSOController::class, 'update'])->name('panel.auth-sso.update');
    
    // Recaptcha
    Route::get('panel/recaptcha', [\App\Http\Controllers\Panel\RecaptchaController::class, 'index'])->name('panel.recaptcha.index');
    Route::put('panel/recaptcha', [\App\Http\Controllers\Panel\RecaptchaController::class, 'update'])->name('panel.recaptcha.update');
    
    // Storage
    Route::get('panel/storage', [\App\Http\Controllers\Panel\StorageController::class, 'index'])->name('panel.storage.index');
    Route::put('panel/storage', [\App\Http\Controllers\Panel\StorageController::class, 'update'])->name('panel.storage.update');
    Route::post('panel/storage/test-connection', [\App\Http\Controllers\Panel\StorageController::class, 'testConnection'])->name('panel.storage.test-connection');
    
    // Cache Management - Must be before resource routes to avoid conflicts
    Route::get('panel/cache-management', [\App\Http\Controllers\Panel\CacheManagementController::class, 'index'])->name('panel.cache-management.index');
    Route::post('panel/cache-management/clear-all', [\App\Http\Controllers\Panel\CacheManagementController::class, 'clearAll'])->name('panel.cache-management.clear-all');
    Route::post('panel/cache-management/clear-specific', [\App\Http\Controllers\Panel\CacheManagementController::class, 'clearSpecific'])->name('panel.cache-management.clear-specific');
    Route::post('panel/cache-management/optimize', [\App\Http\Controllers\Panel\CacheManagementController::class, 'optimize'])->name('panel.cache-management.optimize');
    Route::post('panel/cache-management/warmup', [\App\Http\Controllers\Panel\CacheManagementController::class, 'warmup'])->name('panel.cache-management.warmup');
    
    // FAQ Management
    Route::resource('panel/faqs', \App\Http\Controllers\Panel\FaqController::class)
        ->names([
            'index' => 'panel.faqs.index',
            'create' => 'panel.faqs.create',
            'store' => 'panel.faqs.store',
            'show' => 'panel.faqs.show',
            'edit' => 'panel.faqs.edit',
            'update' => 'panel.faqs.update',
            'destroy' => 'panel.faqs.destroy',
        ]);
    
    // Users Resource Route - Must be after specific routes
    Route::resource('panel/users', \App\Http\Controllers\Panel\UserController::class)
        ->except(['edit'])
        ->names([
            'index' => 'panel.users.index',
            'create' => 'panel.users.create',
            'store' => 'panel.users.store',
            'show' => 'panel.users.show',
            'update' => 'panel.users.update',
            'destroy' => 'panel.users.destroy',
        ]);
    
    // Subscription Plans Management
    Route::resource('panel/subscription-plans', \App\Http\Controllers\Panel\SubscriptionPlanController::class)
        ->except(['edit'])
        ->names([
            'index' => 'panel.subscription-plans.index',
            'create' => 'panel.subscription-plans.create',
            'store' => 'panel.subscription-plans.store',
            'show' => 'panel.subscription-plans.show',
            'update' => 'panel.subscription-plans.update',
            'destroy' => 'panel.subscription-plans.destroy',
        ]);
    
    // Plan Features Management
    Route::resource('panel/plan-features', \App\Http\Controllers\Panel\PlanFeatureController::class)
        ->names([
            'index' => 'panel.plan-features.index',
            'create' => 'panel.plan-features.create',
            'store' => 'panel.plan-features.store',
            'show' => 'panel.plan-features.show',
            'edit' => 'panel.plan-features.edit',
            'update' => 'panel.plan-features.update',
            'destroy' => 'panel.plan-features.destroy',
        ]);
    
    // Subscribers Management
    Route::get('panel/subscribers', [\App\Http\Controllers\Panel\SubscriberController::class, 'index'])->name('panel.subscribers.index');
    Route::get('panel/subscribers/data', [\App\Http\Controllers\Panel\SubscriberController::class, 'getSubscribersData'])->name('panel.subscribers.data');
    Route::post('panel/subscribers/{user}/subscriptions/{subscription}/update-status', [\App\Http\Controllers\Panel\SubscriberController::class, 'updateStatus'])->name('panel.subscribers.update-status');
    
    // Reports
    Route::get('panel/reports/subscriptions', [\App\Http\Controllers\Panel\ReportController::class, 'subscriptions'])->name('panel.reports.subscriptions');
    Route::get('panel/reports/subscriptions/data', [\App\Http\Controllers\Panel\ReportController::class, 'getSubscriptionsData'])->name('panel.reports.subscriptions.data');
    Route::get('panel/reports/subscriptions/export', [\App\Http\Controllers\Panel\ReportController::class, 'exportSubscriptions'])->name('panel.reports.subscriptions.export');
    Route::get('panel/reports/subscriptions/export-excel', [\App\Http\Controllers\Panel\ReportController::class, 'exportSubscriptionsExcel'])->name('panel.reports.subscriptions.export-excel');
    
    Route::get('panel/reports/users', [\App\Http\Controllers\Panel\ReportController::class, 'users'])->name('panel.reports.users');
    Route::get('panel/reports/users/data', [\App\Http\Controllers\Panel\ReportController::class, 'getUsersData'])->name('panel.reports.users.data');
    Route::get('panel/reports/users/export', [\App\Http\Controllers\Panel\ReportController::class, 'exportUsers'])->name('panel.reports.users.export');
    Route::get('panel/reports/users/export-excel', [\App\Http\Controllers\Panel\ReportController::class, 'exportUsersExcel'])->name('panel.reports.users.export-excel');
});

