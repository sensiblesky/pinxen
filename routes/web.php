<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
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
