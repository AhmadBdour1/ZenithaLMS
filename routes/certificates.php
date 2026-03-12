<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\CertificateController;

// Certificate Routes
Route::prefix('certificates')->group(function () {
    // Public routes
    Route::get('/verify/{code}', [CertificateController::class, 'verify']);
    
    // Protected routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/', [CertificateController::class, 'index']);
        Route::get('/{id}', [CertificateController::class, 'show']);
        Route::post('/', [CertificateController::class, 'store']);
        Route::post('/{id}/issue', [CertificateController::class, 'issue']);
        Route::post('/{id}/revoke', [CertificateController::class, 'revoke']);
        Route::put('/{id}', [CertificateController::class, 'update']);
        Route::delete('/{id}', [CertificateController::class, 'destroy']);
        Route::post('/{id}/share', [CertificateController::class, 'share']);
        Route::get('/{id}/download', [CertificateController::class, 'download']);
        Route::get('/templates', [CertificateController::class, 'templates']);
    });
});
