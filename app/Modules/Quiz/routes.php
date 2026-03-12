<?php

use App\Http\Controllers\API\QuizAPIController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Quiz Module Routes
|--------------------------------------------------------------------------
*/

// API Routes
Route::prefix('api/v1')->middleware('installed')->group(function () {
    // Public quiz routes
    Route::middleware('feature:quizzes')->group(function () {
        Route::get('/quizzes', [QuizAPIController::class, 'index']);
        Route::get('/quizzes/{id}', [QuizAPIController::class, 'show']);
    });
    
    // Protected quiz management routes
    Route::middleware(['auth:sanctum', 'feature:quizzes'])->group(function () {
        // Quiz management
        Route::post('/quizzes/{quizId}/start', [QuizAPIController::class, 'start']);
        Route::post('/quiz-attempts/{attemptId}/submit', [QuizAPIController::class, 'submit']);
        Route::get('/user/quiz-attempts', [QuizAPIController::class, 'myAttempts']);
    });
});
