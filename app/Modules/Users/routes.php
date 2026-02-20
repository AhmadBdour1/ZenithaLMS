<?php

use App\Http\Controllers\API\RecommendationAPIController;
use App\Http\Controllers\API\ZenithaLmsApiController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Users Module Routes
|--------------------------------------------------------------------------
*/

// API Routes
Route::prefix('api/v1')->middleware('installed')->group(function () {
    // Protected user routes
    Route::middleware(['auth:sanctum', 'feature:users'])->group(function () {
        // User profile and recommendations
        Route::get('/user/profile', [ZenithaLmsApiController::class, 'profile']);
        Route::get('/user/recommendations', [RecommendationAPIController::class, 'getRecommendations']);
    });
});
