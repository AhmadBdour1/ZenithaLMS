<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Frontend\CourseController;
use App\Http\Controllers\Frontend\ZenithaLmsEbookController;
use App\Http\Controllers\Frontend\ZenithaLmsBlogController;

/*
|--------------------------------------------------------------------------
| ZenithaLMS Central Routes (Public-Facing)
|--------------------------------------------------------------------------
|
| These routes are publicly accessible and should work on the central domain.
| They include course catalog, blog, ebooks, and other public content.
|
*/

// ZenithaLMS: Courses Routes (Public Catalog)
Route::prefix('courses')->name('zenithalms.courses.')->group(function () {
    Route::get('/', [CourseController::class, 'index'])->name('index');
    Route::get('/{slug}', [CourseController::class, 'show'])->name('show');
});

// ZenithaLMS: Ebook Routes (Public Catalog)
Route::prefix('ebooks')->name('zenithalms.ebooks.')->group(function () {
    Route::get('/', [ZenithaLmsEbookController::class, 'index'])->name('index');
    Route::get('/{slug}', [ZenithaLmsEbookController::class, 'show'])->name('show');
    
    // AI-powered recommendations (public)
    Route::get('/recommendations', [ZenithaLmsEbookController::class, 'recommendations'])->name('recommendations');
});

// ZenithaLMS: Blog Routes (Public Blog)
Route::prefix('blog')->name('zenithalms.blog.')->group(function () {
    Route::get('/', [ZenithaLmsBlogController::class, 'index'])->name('index');
    Route::get('/{slug}', [ZenithaLmsBlogController::class, 'show'])->name('show');
});

// ZenithaLMS: AI Assistant (Public Demo)
Route::get('/ai/assistant', function () {
    return view('zenithalms.ai.assistant');
})->name('ai.assistant');

// ZenithaLMS: Virtual Class Routes (Public Catalog)
Route::prefix('virtual-class')->name('zenithalms.virtual-class.')->group(function () {
    Route::get('/', [App\Http\Controllers\Frontend\ZenithaLmsVirtualClassController::class, 'index'])->name('index');
    Route::get('/{id}', [App\Http\Controllers\Frontend\ZenithaLmsVirtualClassController::class, 'show'])->name('show');
});
