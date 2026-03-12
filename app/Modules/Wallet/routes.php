<?php

use App\Http\Controllers\API\WalletAPIController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Wallet Module Routes
|--------------------------------------------------------------------------
*/

// API Routes
Route::prefix('api/v1')->middleware('installed')->group(function () {
    // Protected wallet management routes
    Route::middleware(['auth:sanctum', 'feature:wallet'])->group(function () {
        // Wallet management
        Route::get('/user/wallet', [WalletAPIController::class, 'index']);
        Route::post('/wallet/add-funds', [WalletAPIController::class, 'addFunds']);
        Route::get('/wallet/transactions', [WalletAPIController::class, 'transactions']);
        Route::get('/wallet/statistics', [WalletAPIController::class, 'statistics']);
        Route::post('/wallet/withdraw', [WalletAPIController::class, 'withdraw']);
        Route::post('/wallet/transfer', [WalletAPIController::class, 'transfer']);
    });
});
