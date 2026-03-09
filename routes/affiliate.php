<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AffiliateController;

// Affiliate Routes
Route::prefix('affiliate')->group(function () {
    // Public routes
    Route::get('/track', [AffiliateController::class, 'track']);
    Route::get('/landing/{slug}', [AffiliateController::class, 'landing']);
    
    // Protected routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/dashboard', [AffiliateController::class, 'dashboard']);
        Route::post('/apply', [AffiliateController::class, 'apply']);
        Route::get('/stats', [AffiliateController::class, 'stats']);
        Route::get('/commissions', [AffiliateController::class, 'commissions']);
        Route::post('/payout/request', [AffiliateController::class, 'requestPayout']);
        Route::get('/marketing-materials', [AffiliateController::class, 'marketingMaterials']);
    });
});
