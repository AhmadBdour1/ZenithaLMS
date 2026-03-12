<?php

use App\Http\Controllers\Frontend\ZenithaLmsBlogController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Blog Module Routes
|--------------------------------------------------------------------------
*/

// Web Routes
Route::middleware('feature:blog')->group(function () {
    Route::prefix('blog')->name('zenithalms.blog.')->group(function () {
        Route::get('/', [ZenithaLmsBlogController::class, 'index'])->name('index');
        Route::get('/{slug}', [ZenithaLmsBlogController::class, 'show'])->name('show');
        Route::get('/my-blogs', [ZenithaLmsBlogController::class, 'myBlogs'])->name('my-blogs');
        Route::get('/search', [ZenithaLmsBlogController::class, 'search'])->name('search');
        Route::get('/recommendations', [ZenithaLmsBlogController::class, 'recommendations'])->name('recommendations');
        
        // Admin routes
        Route::middleware(['auth', 'role:admin'])->group(function () {
            Route::get('/admin', [ZenithaLmsBlogController::class, 'adminIndex'])->name('admin.index');
            Route::get('/admin/{id}', [ZenithaLmsBlogController::class, 'adminShow'])->name('admin.show');
            Route::get('/admin/{id}/edit', [ZenithaLmsBlogController::class, 'adminEdit'])->name('admin.edit');
            Route::put('/admin/{id}', [ZenithaLmsBlogController::class, 'adminUpdate'])->name('admin.update');
            Route::delete('/admin/{id}', [ZenithaLmsBlogController::class, 'adminDestroy'])->name('admin.destroy');
        });
        
        // Author routes
        Route::middleware(['auth', 'role:instructor,admin'])->group(function () {
            Route::get('/create', [ZenithaLmsBlogController::class, 'create'])->name('create');
            Route::post('/', [ZenithaLmsBlogController::class, 'store'])->name('store');
            Route::get('/{id}/edit', [ZenithaLmsBlogController::class, 'edit'])->name('edit');
            Route::put('/{id}', [ZenithaLmsBlogController::class, 'update'])->name('update');
            Route::delete('/{id}', [ZenithaLmsBlogController::class, 'destroy'])->name('destroy');
        });
    });
});
