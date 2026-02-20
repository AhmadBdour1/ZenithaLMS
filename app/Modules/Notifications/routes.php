<?php

use App\Http\Controllers\API\NotificationAPIController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Notifications Module Routes
|--------------------------------------------------------------------------
*/

// API Routes
Route::prefix('api/v1')->middleware('installed')->group(function () {
    // Protected notification management routes
    Route::middleware(['auth:sanctum', 'feature:notifications'])->group(function () {
        // Notification management
        Route::get('/user/notifications', [NotificationAPIController::class, 'index']);
        Route::post('/notifications/{notificationId}/read', [NotificationAPIController::class, 'markAsRead']);
        Route::post('/notifications/mark-all-read', [NotificationAPIController::class, 'markAllAsRead']);
        Route::delete('/notifications/{notificationId}', [NotificationAPIController::class, 'destroy']);
        Route::get('/user/notifications/unread-count', [NotificationAPIController::class, 'unreadCount']);
        Route::get('/user/notifications/statistics', [NotificationAPIController::class, 'statistics']);
        Route::post('/notifications', [NotificationAPIController::class, 'store']);
    });
});
