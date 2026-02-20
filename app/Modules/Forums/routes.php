<?php

use App\Http\Controllers\API\ForumAPIController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Forums Module Routes
|--------------------------------------------------------------------------
*/

// API Routes
Route::prefix('api/v1')->middleware('installed')->group(function () {
    // Public forum routes
    Route::middleware('feature:forums')->group(function () {
        Route::get('/forums', [ForumAPIController::class, 'index']);
        Route::get('/forums/{id}', [ForumAPIController::class, 'show']);
        Route::get('/forums/{forumId}/replies', [ForumAPIController::class, 'replies']);
    });
    
    // Protected forum management routes
    Route::middleware(['auth:sanctum', 'feature:forums'])->group(function () {
        // Forum management
        Route::post('/forums', [ForumAPIController::class, 'store']);
        Route::put('/forums/{id}', [ForumAPIController::class, 'update']);
        Route::delete('/forums/{id}', [ForumAPIController::class, 'destroy']);
        Route::post('/forums/{forumId}/reply', [ForumAPIController::class, 'reply']);
        Route::put('/forum-replies/{replyId}', [ForumAPIController::class, 'updateReply']);
        Route::delete('/forum-replies/{replyId}', [ForumAPIController::class, 'deleteReply']);
    });
});
