<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            Route::middleware('web')
                ->group(base_path('routes/panel.php'));
            
            Route::middleware('web')
                ->group(base_path('routes/client.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin' => \App\Http\Middleware\CheckAdminRole::class,
            'client' => \App\Http\Middleware\CheckClientRole::class,
            'registration.enabled' => \App\Http\Middleware\CheckRegistrationEnabled::class,
            'login.enabled' => \App\Http\Middleware\CheckLoginEnabled::class,
            'force.email.verification' => \App\Http\Middleware\ForceEmailVerification::class,
            'force.2fa' => \App\Http\Middleware\ForceTwoFactorAuthentication::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
