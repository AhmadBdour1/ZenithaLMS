<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuraStoreController;
use App\Http\Controllers\AdminQuickLoginController;

// Aura Store Routes
Route::prefix('aura/store')->group(function () {
    // Public routes
    Route::get('/products', [AuraStoreController::class, 'index']);
    Route::get('/products/{slug}', [AuraStoreController::class, 'show']);
    Route::get('/categories', [AuraStoreController::class, 'categories']);
    Route::get('/search', [AuraStoreController::class, 'search']);
    
    // Protected routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/cart', [AuraStoreController::class, 'cart']);
        Route::post('/cart/add', [AuraStoreController::class, 'addToCart']);
        Route::post('/checkout', [AuraStoreController::class, 'checkout']);
        Route::get('/orders', [AuraStoreController::class, 'orders']);
        Route::get('/orders/{id}', [AuraStoreController::class, 'order']);
        Route::get('/download/{slug}/{fileId}', [AuraStoreController::class, 'download']);
    });
});

// Admin Quick Login Routes
Route::prefix('admin/quick-login')->middleware(['web'])->group(function () {
    Route::get('/', [AdminQuickLoginController::class, 'showLoginForm']);
    Route::post('/login', [AdminQuickLoginController::class, 'login']);
    Route::get('/users', [AdminQuickLoginController::class, 'getUsers']);
    Route::get('/switch-back', [AdminQuickLoginController::class, 'switchBack']);
    Route::get('/check-session', [AdminQuickLoginController::class, 'checkSession']);
    Route::post('/extend-session', [AdminQuickLoginController::class, 'extendSession']);
    Route::get('/stats', [AdminQuickLoginController::class, 'getStats']);
    Route::delete('/revoke', [AdminQuickLoginController::class, 'revokeSession']);
});
