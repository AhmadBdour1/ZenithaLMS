<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\StuffController;

// Stuff API Routes
Route::prefix('stuff')->group(function () {
    
    // Public routes
    Route::get('/', [StuffController::class, 'index']);
    Route::get('/search', [StuffController::class, 'search']);
    Route::get('/categories', [StuffController::class, 'categories']);
    Route::get('/featured', [StuffController::class, 'featured']);
    Route::get('/popular', [StuffController::class, 'popular']);
    Route::get('/trending', [StuffController::class, 'trending']);
    Route::get('/new', [StuffController::class, 'new']);
    Route::get('/best-sellers', [StuffController::class, 'bestSellers']);
    Route::get('/on-sale', [StuffController::class, 'onSale']);
    Route::get('/free', [StuffController::class, 'free']);
    Route::get('/{id}', [StuffController::class, 'show']);
    Route::get('/{id}/reviews', [StuffController::class, 'reviews']);
    
    // Protected routes
    Route::middleware('auth:sanctum')->group(function () {
        
        // Stuff management
        Route::post('/', [StuffController::class, 'store']);
        Route::put('/{id}', [StuffController::class, 'update']);
        Route::delete('/{id}', [StuffController::class, 'destroy']);
        
        // Purchase and download
        Route::post('/{id}/purchase', [StuffController::class, 'purchase']);
        Route::get('/{id}/download', [StuffController::class, 'download']);
        
        // User stuff
        Route::get('/my/purchases', [StuffController::class, 'purchases']);
        Route::get('/my/licenses', [StuffController::class, 'licenses']);
        
        // Reviews
        Route::post('/{id}/reviews', [StuffController::class, 'addReview']);
        Route::post('/{id}/reviews/{reviewId}/helpful', [StuffController::class, 'markReviewHelpful']);
    });
});
