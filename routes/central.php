<?php

use App\Http\Controllers\Central\TenantRegistrationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Central Routes
|--------------------------------------------------------------------------
|
| Routes for the central application (platform management).
| These routes run in the central context, NOT within any tenant.
|
*/

// Landing page
Route::get('/', function () {
    return view('central.landing');
})->name('central.landing');

// Tenant registration/onboarding
Route::prefix('register')->name('tenant.')->group(function () {
    Route::get('/', [TenantRegistrationController::class, 'showRegistrationForm'])->name('register');
    Route::post('/', [TenantRegistrationController::class, 'register'])->name('store');
    Route::get('/success', [TenantRegistrationController::class, 'success'])->name('success');
});

// Platform information
Route::get('/pricing', function () {
    return view('central.pricing');
})->name('central.pricing');

Route::get('/about', function () {
    return view('central.about');
})->name('central.about');

Route::get('/contact', function () {
    return view('central.contact');
})->name('central.contact');

// Health check (public)
Route::get('/health', function () {
    return response()->json(['status' => 'ok', 'timestamp' => now()]);
})->name('central.health');
