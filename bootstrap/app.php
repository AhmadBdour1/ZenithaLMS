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
        then: function () {
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
        ]);
        
        // Apply security middleware to all API routes
        $middleware->group('api', [
            \App\Http\Middleware\SanitizeInput::class,
            \App\Http\Middleware\SecurityHeaders::class,
            \App\Http\Middleware\ApiRateLimiting::class,
        ]);
        
        // Apply caching to specific API routes (using route middleware)
        // We'll apply caching selectively in the routes file
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
