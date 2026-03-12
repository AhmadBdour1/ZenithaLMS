<?php

use App\Http\Controllers\API\AuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Auth Module Routes
|--------------------------------------------------------------------------
*/

// API Routes
Route::prefix('api/v1')->middleware('installed')->group(function () {
    // Public authentication routes
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
});
