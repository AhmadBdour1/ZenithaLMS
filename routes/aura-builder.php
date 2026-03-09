<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuraPageBuilderController;

// Aura Page Builder Routes
Route::prefix('aura/builder')->middleware('auth:sanctum')->group(function () {
    // Pages
    Route::get('/pages', [AuraPageBuilderController::class, 'index']);
    Route::post('/pages', [AuraPageBuilderController::class, 'store']);
    Route::get('/pages/{id}', [AuraPageBuilderController::class, 'show']);
    Route::put('/pages/{id}', [AuraPageBuilderController::class, 'update']);
    Route::delete('/pages/{id}', [AuraPageBuilderController::class, 'destroy']);
    Route::post('/pages/{id}/duplicate', [AuraPageBuilderController::class, 'duplicate']);
    Route::post('/pages/{id}/publish', [AuraPageBuilderController::class, 'publish']);
    Route::post('/pages/{id}/unpublish', [AuraPageBuilderController::class, 'unpublish']);
    Route::post('/pages/{id}/schedule', [AuraPageBuilderController::class, 'schedule']);
    Route::get('/pages/{id}/preview', [AuraPageBuilderController::class, 'preview']);
    Route::get('/pages/{id}/revisions', [AuraPageBuilderController::class, 'revisions']);
    Route::post('/pages/{id}/restore-revision/{revisionId}', [AuraPageBuilderController::class, 'restoreRevision']);
    Route::get('/pages/{id}/analytics', [AuraPageBuilderController::class, 'analytics']);
    
    // Templates
    Route::get('/templates', [AuraPageBuilderController::class, 'templates']);
    
    // Blocks
    Route::get('/blocks', [AuraPageBuilderController::class, 'blocks']);
});

// Public Page Routes (for displaying pages)
Route::prefix('aura')->group(function () {
    Route::get('/{slug}', function ($slug) {
        $page = \App\Models\AuraPage::where('slug', $slug)
            ->published()
            ->visible()
            ->firstOrFail();
        
        $page->incrementViewCount();
        
        return response($page->getRenderedContent());
    })->name('aura.pages.show');
});
