<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Services\DashboardRedirectService;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

Route::get('/', function () {
    // Redirect unauthenticated users to login, authenticated to dashboard
    if (Auth::check()) {
        return redirect('/dashboard');
    }
    return redirect('/login');
});

// Enhanced login page for development
Route::get('/login-enhanced', function () {
    return view('auth.login-enhanced');
})->name('login-enhanced');

// Quick Access page for demo accounts
Route::get('/quick-access', function () {
    return view('quick-access');
})->name('quick-access');

// Unified dashboard entry point - using centralized service
Route::get('/dashboard', function () {
    $user = Auth::user();
    
    if (!$user) {
        return redirect()->route('login');
    }
    
    // Get dashboard route for user's role, with fallback
    try {
        $dashboardRoute = DashboardRedirectService::getDashboardRouteForUser($user);
        return redirect()->route($dashboardRoute);
    } catch (\Exception $e) {
        // Fallback: if role not recognized, redirect to student dashboard
        \Log::warning("Unknown role for user {$user->id}: {$user->role?->name}");
        return redirect()->route('zenithalms.dashboard.student');
    }
})
    ->name('dashboard')
    ->middleware('auth');

// Also add a simple dashboard route for compatibility with existing navigation links
Route::get('/dashboard/simple', function () {
    return redirect()->route('dashboard');
})->name('dashboard.simple');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

// ZenithaLMS: Main LMS routes are loaded from routes/zenithalms.php via bootstrap/app.php
// This keeps web.php clean for basic auth and core routes only
