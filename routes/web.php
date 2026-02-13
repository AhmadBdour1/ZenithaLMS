<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

Route::get('/', function () {
    return view('zenithalms.homepage.index');
});

// Enhanced login page for development
Route::get('/login-enhanced', function () {
    return view('auth.login-enhanced');
})->name('login-enhanced');

// Unified dashboard entry point - using closure for now
Route::get('/dashboard', function () {
    $user = Auth::user();
    
    if (!$user) {
        return redirect()->route('login');
    }
    
    $role = $user->role_name;
    
    // Redirect based on role
    switch ($role) {
        case 'super_admin':
        case 'admin':
            return redirect()->route('zenithalms.dashboard.admin');
        case 'instructor':
            return redirect()->route('zenithalms.dashboard.instructor');
        case 'student':
            return redirect()->route('zenithalms.dashboard.student');
        case 'organization':
            return redirect()->route('zenithalms.dashboard.organization');
        default:
            // Default to student dashboard for unknown roles
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
