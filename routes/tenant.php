<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Tenant Routes
|--------------------------------------------------------------------------
|
| In single-domain deployment, tenant initialization is already handled
| globally by InitializeSingleDomainTenancy in the web middleware group.
| These routes must NOT use InitializeTenancyByDomain.
|
*/

Route::group(function () {
    require __DIR__ . '/profile.php';
    require __DIR__ . '/zenithalms-tenant.php';
    require __DIR__ . '/admin.php';
    require __DIR__ . '/certificates.php';
    require __DIR__ . '/payments.php';
    require __DIR__ . '/marketplace.php';
    require __DIR__ . '/affiliate.php';
    require __DIR__ . '/stuff.php';
    require __DIR__ . '/aura.php';
    require __DIR__ . '/aura-builder.php';
});