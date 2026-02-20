<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        then: function () {
            Route::middleware('web')
                ->group(base_path('routes/install.php'));
                
            Route::middleware('web')
                ->prefix('admin')
                ->name('admin.')
                ->group(base_path('routes/admin.php'));
                
            Route::middleware('web')
                ->group(base_path('routes/zenithalms.php'));
        },
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => \App\Http\Middleware\ZenithaLmsRoleMiddleware::class,
            'organization' => \App\Http\Middleware\ZenithaLmsOrganizationMiddleware::class,
            'sanitize' => \App\Http\Middleware\SanitizeInput::class,
            'security.headers' => \App\Http\Middleware\SecurityHeaders::class,
            'api.rate_limit' => \App\Http\Middleware\ApiRateLimiting::class,
            'api.cache' => \App\Http\Middleware\CacheMiddleware::class,
            'role.check' => \App\Http\Middleware\RoleMiddleware::class,
            'installed' => \App\Http\Middleware\EnsureInstalled::class,
            'feature' => \App\Http\Middleware\EnsureFeatureEnabled::class,
        ]);
        
        // Apply installation check to all web routes except installer
        $middleware->group('web', [
            \App\Http\Middleware\EnsureInstalled::class,
            \Illuminate\Cookie\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ]);
        
        // Apply caching to specific API routes (using route middleware)
        // We'll apply caching selectively in the routes file
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })
    ->create();
