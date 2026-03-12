<?php

use App\Http\Controllers\API\SearchAPIController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Search Module Routes
|--------------------------------------------------------------------------
*/

// API Routes
Route::prefix('api/v1')->middleware('installed')->group(function () {
    // Public search routes
    Route::middleware('feature:search')->group(function () {
        Route::get('/search', [SearchAPIController::class, 'search']);
    });
});
