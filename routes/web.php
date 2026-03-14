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

// Unified dashboard entry point - using controller
Route::get('/dashboard', [DashboardController::class, 'index'])
    ->name('dashboard')
    ->middleware('auth');

// Also add a simple dashboard route for compatibility with existing navigation links
Route::get('/dashboard/simple', function () {
    return redirect()->route('dashboard');
})->name('dashboard.simple');

// Public profile route - accessible to all, redirects to login if not authenticated
Route::get('/profile', function () {
    if (!Auth::check()) {
        return redirect()->route('login');
    }
    return view('profile.simple');
})->name('profile.public');

Route::middleware('auth')->group(function () {
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Admin dashboard route for admin users
Route::get('/admin', function () {
    $user = Auth::user();
    
    if (!$user || !$user->isAdmin()) {
        return redirect()->route('dashboard');
    }
    
    return view('admin.central', compact('user'));
})
    ->name('admin')
    ->middleware('auth');

require __DIR__.'/auth.php';

// ZenithaLMS routes are now properly organized:
// - Central routes: routes/zenithalms-central.php (public catalog)
// - Tenant routes: routes/tenant.php → zenithalms-tenant.php (tenant-specific)
// - API routes: routes/api.php (API endpoints)
