<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

/*
|--------------------------------------------------------------------------
| Tenant Routes
|--------------------------------------------------------------------------
|
| Here you can register the tenant routes for your application.
| These routes are loaded by the TenancyServiceProvider and are
| automatically scoped to the current tenant.
|
| All routes here run in TENANT context with tenant database.
|
*/

Route::middleware([
    'web',
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
])->group(function () {
    
    // Tenant welcome/dashboard home
    Route::get('/', function () {
        if (auth()->check()) {
            return redirect('/dashboard');
        }
        return redirect('/login');
    });
    
    // Include all tenant-specific route files
    require __DIR__.'/auth.php';
    require __DIR__.'/profile.php';
    require __DIR__.'/zenithalms.php';
    require __DIR__.'/admin.php';
    require __DIR__.'/certificates.php';
    require __DIR__.'/payments.php';
    require __DIR__.'/marketplace.php';
    require __DIR__.'/affiliate.php';
    require __DIR__.'/stuff.php';
    require __DIR__.'/aura.php';
    require __DIR__.'/aura-builder.php';
});
