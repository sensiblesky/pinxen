<?php

use Illuminate\Support\Facades\Route;

// Client Routes - Only accessible by users with role 2 (Client)
// Note: Force email verification and 2FA are applied globally via web.php, but client routes need them too
Route::middleware(['auth', 'force.email.verification', 'force.2fa', 'client'])->group(function () {
    // Client Dashboard
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');
    
    // Add other client-specific routes here
    // Example:
    // Route::get('/client/something', [ClientController::class, 'index'])->name('client.something');
});

