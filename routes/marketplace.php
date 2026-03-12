<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\MarketplaceController;

// Marketplace Routes
Route::prefix('marketplace')->group(function () {
    // Public routes
    Route::get('/', [MarketplaceController::class, 'index']);
    Route::get('/featured', [MarketplaceController::class, 'featured']);
    Route::get('/search', [MarketplaceController::class, 'search']);
    Route::get('/{slug}', [MarketplaceController::class, 'show']);
    
    // Protected routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/', [MarketplaceController::class, 'store']);
        Route::post('/{id}/purchase', [MarketplaceController::class, 'purchase']);
        Route::get('/download/{licenseKey}', [MarketplaceController::class, 'download']);
    });
});
