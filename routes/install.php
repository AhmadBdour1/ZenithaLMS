<?php

use App\Http\Controllers\InstallerController;
use Illuminate\Support\Facades\Route;

// Installer routes (must be defined before global middleware)
Route::prefix('install')->name('install.')->group(function () {
    Route::get('/', [InstallerController::class, 'index'])->name('index');
    Route::post('/check-db', [InstallerController::class, 'checkDatabase'])->name('check-db');
    Route::post('/run', [InstallerController::class, 'run'])->name('run');
});

// Single route for backward compatibility
Route::get('/install', [InstallerController::class, 'index'])->name('install');
