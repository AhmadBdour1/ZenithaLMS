<?php

use App\Http\Controllers\API\AdminAPIController;
use App\Http\Controllers\API\InstructorAPIController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Dashboards Module Routes
|--------------------------------------------------------------------------
*/

// API Routes
Route::prefix('api/v1')->middleware('installed')->group(function () {
    // Protected dashboard routes
    Route::middleware(['auth:sanctum', 'feature:dashboards'])->group(function () {
        // Admin dashboard
        Route::prefix('admin')->group(function () {
            Route::get('/dashboard', [AdminAPIController::class, 'dashboard']);
        });
        
        // Instructor dashboard
        Route::prefix('instructor')->group(function () {
            Route::get('/dashboard', [InstructorAPIController::class, 'dashboard']);
        });
    });
});
