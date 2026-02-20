<?php

use App\Http\Controllers\API\VirtualClassAPIController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| VirtualClasses Module Routes
|--------------------------------------------------------------------------
*/

// API Routes
Route::prefix('api/v1')->middleware('installed')->group(function () {
    // Public virtual class routes
    Route::middleware('feature:virtual-classes')->group(function () {
        Route::get('/virtual-classes', [VirtualClassAPIController::class, 'index']);
        Route::get('/virtual-classes/{id}', [VirtualClassAPIController::class, 'show']);
    });
    
    // Protected virtual class management routes
    Route::middleware(['auth:sanctum', 'feature:virtual-classes'])->group(function () {
        // Virtual class management
        Route::post('/virtual-classes', [VirtualClassAPIController::class, 'store']);
        Route::put('/virtual-classes/{id}', [VirtualClassAPIController::class, 'update']);
        Route::delete('/virtual-classes/{id}', [VirtualClassAPIController::class, 'destroy']);
        Route::post('/virtual-classes/{classId}/join', [VirtualClassAPIController::class, 'join']);
        Route::post('/virtual-classes/{classId}/leave', [VirtualClassAPIController::class, 'leave']);
        Route::get('/user/virtual-classes', [VirtualClassAPIController::class, 'myClasses']);
    });
});
