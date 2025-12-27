<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Test endpoints for uptime monitor functionality testing
Route::prefix('test')->name('test.')->group(function () {
    Route::get('/', function () {
        return response()->json([
            'message' => 'Uptime Monitor Test Endpoints',
            'endpoints' => [
                'basic' => route('test.basic'),
                'basic-auth' => route('test.basic-auth'),
                'custom-headers' => route('test.custom-headers'),
                'cache-buster' => route('test.cache-buster'),
                'status-code' => route('test.status-code', ['code' => 200]),
                'slow-response' => route('test.slow-response', ['delay' => 3]),
                'method-test' => route('test.method-test'),
                'comprehensive' => route('test.comprehensive'),
                'error' => route('test.error'),
                'keyword-test' => route('test.keyword-test', ['keyword' => 'test']),
            ],
            'usage' => [
                'basic' => 'GET - Simple test endpoint',
                'basic-auth' => 'GET - Requires Basic Auth (username: testuser, password: testpass)',
                'custom-headers' => 'GET - Returns all custom headers sent',
                'cache-buster' => 'GET - Checks for cache buster parameters',
                'status-code' => 'GET ?code=XXX - Returns specified status code (100-599)',
                'slow-response' => 'GET ?delay=X - Simulates slow response (max 10 seconds)',
                'method-test' => 'Any method - Returns the HTTP method used',
                'comprehensive' => 'GET - Tests all features at once',
                'error' => 'GET - Returns 500 error for testing error handling',
                'keyword-test' => 'GET ?keyword=XXX - Returns response containing keyword',
            ],
        ], 200);
    })->name('index');
    
    Route::get('/basic', [\App\Http\Controllers\TestController::class, 'basic'])->name('basic');
    Route::get('/basic-auth', [\App\Http\Controllers\TestController::class, 'basicAuth'])->name('basic-auth');
    Route::get('/custom-headers', [\App\Http\Controllers\TestController::class, 'customHeaders'])->name('custom-headers');
    Route::get('/cache-buster', [\App\Http\Controllers\TestController::class, 'cacheBuster'])->name('cache-buster');
    Route::get('/status-code', [\App\Http\Controllers\TestController::class, 'statusCode'])->name('status-code');
    Route::get('/slow-response', [\App\Http\Controllers\TestController::class, 'slowResponse'])->name('slow-response');
    Route::post('/method-test', [\App\Http\Controllers\TestController::class, 'methodTest'])->name('method-test');
    Route::get('/method-test', function() {
        return response()->json([
            'status' => 'error',
            'message' => 'This endpoint requires POST method',
            'error' => 'Configure Request Method = POST in your monitor settings',
        ], 405);
    });
    Route::post('/comprehensive', [\App\Http\Controllers\TestController::class, 'comprehensive'])->name('comprehensive');
    Route::get('/comprehensive', function() {
        return response()->json([
            'status' => 'error',
            'message' => 'This endpoint requires POST method and all advanced options',
            'error' => 'Configure Request Method = POST, Basic Auth, Custom Headers, and Cache Buster',
        ], 405);
    });
    Route::get('/error', [\App\Http\Controllers\TestController::class, 'error'])->name('error');
    Route::get('/keyword-test', [\App\Http\Controllers\TestController::class, 'keywordTest'])->name('keyword-test');
});

// Shared routes accessible by both admin and client
Route::middleware(['auth', 'force.email.verification', 'force.2fa'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::delete('/profile/sessions/{sessionId}', [ProfileController::class, 'terminateSession'])->name('profile.sessions.terminate');
    
    // Account Security - Change Password
    Route::get('/account/security/password', [\App\Http\Controllers\Account\SecurityController::class, 'showPassword'])->name('account.security.password');
    Route::post('/account/security/password', [\App\Http\Controllers\Account\SecurityController::class, 'updatePassword'])->name('account.security.password.update');
    
    // Support & Help
    Route::get('/support', [\App\Http\Controllers\SupportController::class, 'index'])->name('support.index');
});

// Email Verification and 2FA routes (excluded from force middleware to allow access)
Route::middleware('auth')->group(function () {
    // Email Verification with OTP
    Route::get('/verify-email-otp', [\App\Http\Controllers\Auth\EmailVerificationOTPController::class, 'show'])->name('email.verification.show');
    Route::post('/verify-email-otp/send', [\App\Http\Controllers\Auth\EmailVerificationOTPController::class, 'sendOTP'])->name('email.verification.send');
    Route::post('/verify-email-otp/verify', [\App\Http\Controllers\Auth\EmailVerificationOTPController::class, 'verifyOTP'])->name('email.verification.verify');
    
    // Account Security - Two-Factor Authentication
    Route::get('/account/security/two-factor', [\App\Http\Controllers\Account\SecurityController::class, 'showTwoFactor'])->name('account.security.two-factor');
    Route::post('/account/security/two-factor/enable', [\App\Http\Controllers\Account\SecurityController::class, 'enableTwoFactor'])->name('account.security.two-factor.enable');
    Route::post('/account/security/two-factor/disable', [\App\Http\Controllers\Account\SecurityController::class, 'disableTwoFactor'])->name('account.security.two-factor.disable');
    Route::post('/account/security/two-factor/regenerate-recovery-codes', [\App\Http\Controllers\Account\SecurityController::class, 'regenerateRecoveryCodes'])->name('account.security.two-factor.regenerate-recovery-codes');
    
    // Secure image serving - Available to all authenticated users (not just admins)
    Route::get('panel/images/avatar/{encryptedPath}', [\App\Http\Controllers\Panel\SecureImageController::class, 'serveAvatar'])
        ->where('encryptedPath', '.*')
        ->name('panel.images.avatar');
});

require __DIR__.'/auth.php';

// API Routes (for Linux agent)
Route::prefix('api/v1')->middleware(['api', \App\Http\Middleware\AuthenticateApiKey::class])->group(function () {
    Route::post('/server-stats', [\App\Http\Controllers\Api\ServerStatsController::class, 'store'])->name('api.server-stats.store');
});
