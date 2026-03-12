<?php

use App\Http\Controllers\API\EbookAPIController;
use App\Http\Controllers\API\EbookDownloadController;
use App\Http\Controllers\Frontend\ZenithaLmsEbookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Ebooks Module Routes
|--------------------------------------------------------------------------
*/

// API Routes
Route::prefix('api/v1')->middleware('installed')->group(function () {
    // Public ebook routes
    Route::middleware('feature:ebooks')->group(function () {
        Route::get('/ebooks', [EbookAPIController::class, 'index']);
        Route::get('/ebooks/{id}', [EbookAPIController::class, 'show']);
        
        // Protected ebook download route
        Route::middleware('auth:sanctum')->get('/ebooks/{id}/download', [EbookDownloadController::class, 'download']);
    });
});

// Web Routes
Route::prefix('ebooks')->name('zenithalms.ebooks.')->middleware('feature:ebooks')->group(function () {
    Route::get('/', [ZenithaLmsEbookController::class, 'index'])->name('index');
    Route::get('/{slug}', [ZenithaLmsEbookController::class, 'show'])->name('show');
    Route::get('/my-ebooks', [ZenithaLmsEbookController::class, 'myEbooks'])->name('my-ebooks');
    Route::get('/download/{ebookId}', [ZenithaLmsEbookController::class, 'download'])->name('download');
    Route::get('/read/{ebookId}', [ZenithaLmsEbookController::class, 'read'])->name('read');
    Route::post('/favorites/{ebookId}', [ZenithaLmsEbookController::class, 'addToFavorites'])->name('favorites.add');
    Route::delete('/favorites/{ebookId}', [ZenithaLmsEbookController::class, 'removeFromFavorites'])->name('favorites.remove');
    Route::get('/search', [ZenithaLmsEbookController::class, 'search'])->name('search');
    Route::get('/recommendations', [ZenithaLmsEbookController::class, 'recommendations'])->name('recommendations');
    
    // Admin routes
    Route::middleware(['auth', 'role:admin'])->group(function () {
        Route::get('/admin', [ZenithaLmsEbookController::class, 'adminIndex'])->name('admin.index');
        Route::get('/admin/{id}', [ZenithaLmsEbookController::class, 'adminShow'])->name('admin.show');
        Route::get('/admin/{id}/edit', [ZenithaLmsEbookController::class, 'adminEdit'])->name('admin.edit');
        Route::put('/admin/{id}', [ZenithaLmsEbookController::class, 'adminUpdate'])->name('admin.update');
        Route::delete('/admin/{id}', [ZenithaLmsEbookController::class, 'adminDestroy'])->name('admin.destroy');
        Route::post('/admin/{id}/toggle-featured', [ZenithaLmsEbookController::class, 'toggleFeatured'])->name('admin.toggle-featured');
    });
    
    // Instructor/Organization routes
    Route::middleware(['auth', 'role:instructor,organization_admin'])->group(function () {
        Route::get('/create', [ZenithaLmsEbookController::class, 'create'])->name('create');
        Route::post('/', [ZenithaLmsEbookController::class, 'store'])->name('store');
        Route::get('/{id}/edit', [ZenithaLmsEbookController::class, 'edit'])->name('edit');
        Route::put('/{id}', [ZenithaLmsEbookController::class, 'update'])->name('update');
        Route::delete('/{id}', [ZenithaLmsEbookController::class, 'destroy'])->name('destroy');
    });
});
