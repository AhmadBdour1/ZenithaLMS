<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Frontend\ZenithaLmsEbookController;
use App\Http\Controllers\Frontend\ZenithaLmsBlogController;
use App\Http\Controllers\Frontend\ZenithaLmsPaymentController;
use App\Http\Controllers\CourseBuilderController;

/*
|--------------------------------------------------------------------------
| ZenithaLMS Tenant Routes (Tenant-Specific)
|--------------------------------------------------------------------------
|
| These routes require tenant context and should only work within tenant domains.
| They include user-specific content, admin management, and tenant-scoped features.
|
*/

// ZenithaLMS: Course Builder (Tenant-Specific)
Route::prefix('courses/builder')->name('courses.builder.')->middleware(['auth', 'feature:course_builder_v2'])->group(function () {
    Route::get('/{course}', [CourseBuilderController::class, 'edit'])->name('edit');
    Route::post('/{course}/structure', [CourseBuilderController::class, 'updateStructure'])->name('structure');
    Route::post('/{course}/lesson', [CourseBuilderController::class, 'addLesson'])->name('lesson.add');
    Route::post('/{course}/quiz', [CourseBuilderController::class, 'addQuiz'])->name('quiz.add');
    Route::post('/{course}/{type}/{id}', [CourseBuilderController::class, 'updateItem'])->name('item.update');
    Route::delete('/{course}/{type}/{id}', [CourseBuilderController::class, 'deleteItem'])->name('item.delete');
});

// ZenithaLMS: Dashboard Routes (Tenant-Specific)
Route::prefix('dashboard')->name('zenithalms.tenant.dashboard.')->middleware('auth')->group(function () {
    Route::get('/student', function () {
        $user = auth()->user();
        $stats = [
            'enrolled_courses' => $user->enrolledCourses()->count(),
            'completed_courses' => $user->completedCourses()->count(),
            'progress_summary' => $user->getProgressStats(),
            'unread_notifications' => 0,
            'current_courses' => $user->enrolledCourses()->take(4)->get(),
        ];
        return view('zenithalms.dashboard.student', compact('user', 'stats'));
    })->name('student')->middleware('role:student');
    
    Route::get('/instructor', function () {
        $user = auth()->user();
        $stats = [
            'total_courses' => $user->courses()->count(),
            'active_courses' => $user->courses()->where('is_published', true)->count(),
            'total_students' => $user->courses()->withCount('enrollments')->get()->sum('enrollments_count'),
            'total_revenue' => 0, // TODO: Calculate from payments
            'recent_courses' => $user->courses()->latest()->take(4)->get(),
        ];
        return view('zenithalms.dashboard.instructor', compact('user', 'stats'));
    })->name('zenithalms.dashboard.instructor')->middleware('role:instructor');
    
    Route::get('/admin', function () {
        return view('zenithalms.dashboard.admin');
    })->name('admin')->middleware('role:admin');
    
    Route::get('/organization', function () {
        $user = auth()->user();
        $stats = [
            'total_users' => $user->organization->users()->count(),
            'total_courses' => $user->organization->courses()->count(),
            'active_courses' => $user->organization->courses()->where('is_published', true)->count(),
            'total_students' => $user->organization->courses()->withCount('enrollments')->get()->sum('enrollments_count'),
        ];
        return view('zenithalms.dashboard.organization', compact('user', 'stats'));
    })->name('organization')->middleware('role:organization');
});

// ZenithaLMS: Payment Routes (Tenant-Specific)
Route::prefix('payment')->name('zenithalms.tenant.payment.')->middleware('auth')->group(function () {
    Route::get('/checkout', [ZenithaLmsPaymentController::class, 'checkout'])->name('checkout');
    Route::post('/process', [ZenithaLmsPaymentController::class, 'processPayment'])->name('process');
    Route::get('/success/{orderId}', [ZenithaLmsPaymentController::class, 'success'])->name('success');
    Route::get('/failed/{orderId}', [ZenithaLmsPaymentController::class, 'failed'])->name('failed');
    Route::get('/history', [ZenithaLmsPaymentController::class, 'history'])->name('history');
    Route::get('/wallet', [ZenithaLmsPaymentController::class, 'wallet'])->name('wallet');
    Route::post('/add-funds', [ZenithaLmsPaymentController::class, 'addFunds'])->name('add-funds');
    Route::post('/apply-coupon', [ZenithaLmsPaymentController::class, 'applyCoupon'])->name('apply-coupon');
});

// ZenithaLMS: Ebook Management (Tenant-Specific)
Route::prefix('ebooks')->name('zenithalms.tenant.ebooks.')->middleware('feature:ebooks')->group(function () {
    // User ebook management
    Route::get('/my-ebooks', [ZenithaLmsEbookController::class, 'myEbooks'])->name('my-ebooks')->middleware('auth');
    Route::get('/create', [ZenithaLmsEbookController::class, 'create'])->name('create')->middleware('auth');
    Route::post('/', [ZenithaLmsEbookController::class, 'store'])->name('store')->middleware('auth');
    Route::put('/{id}', [ZenithaLmsEbookController::class, 'update'])->name('update')->middleware('auth');
    Route::delete('/{id}', [ZenithaLmsEbookController::class, 'destroy'])->name('destroy')->middleware('auth');
    
    // User interactions
    Route::post('/{ebookId}/favorite', [ZenithaLmsEbookController::class, 'addToFavorites'])->name('favorite')->middleware('auth');
    Route::delete('/{ebookId}/favorite', [ZenithaLmsEbookController::class, 'removeFromFavorites'])->name('unfavorite')->middleware('auth');
    Route::get('/{ebookId}/download', [ZenithaLmsEbookController::class, 'download'])->name('download')->middleware('auth');
    Route::get('/{ebookId}/read', [ZenithaLmsEbookController::class, 'read'])->name('read')->middleware('auth');
    
    // Admin ebook routes
    Route::prefix('admin')->name('admin.')->middleware(['auth', 'admin'])->group(function () {
        Route::get('/{id}', [ZenithaLmsEbookController::class, 'adminShow'])->name('show')->middleware('auth');
        Route::get('/{id}/edit', [ZenithaLmsEbookController::class, 'adminEdit'])->name('edit')->middleware('auth');
        Route::put('/{id}', [ZenithaLmsEbookController::class, 'adminUpdate'])->name('admin.update')->middleware('auth');
        Route::delete('/{id}', [ZenithaLmsEbookController::class, 'adminDestroy'])->name('admin.destroy')->middleware('auth');
        Route::post('/{id}/toggle-featured', [ZenithaLmsEbookController::class, 'toggleFeatured'])->name('toggle-featured');
    });
});

// ZenithaLMS: Blog Management (Tenant-Specific)
Route::prefix('blog')->name('zenithalms.tenant.blog.')->group(function () {
    // User blog management
    Route::get('/my-blogs', [ZenithaLmsBlogController::class, 'myBlogs'])->name('my-blogs')->middleware('auth');
    Route::get('/{id}/edit', [ZenithaLmsBlogController::class, 'edit'])->name('edit')->middleware('auth');
    Route::put('/{id}', [ZenithaLmsBlogController::class, 'update'])->name('update')->middleware('auth');
    Route::delete('/{id}', [ZenithaLmsBlogController::class, 'destroy'])->name('destroy')->middleware('auth');
    
    // Admin blog routes
    Route::prefix('admin')->name('admin.')->middleware(['auth', 'admin'])->group(function () {
        Route::get('/{id}', [ZenithaLmsBlogController::class, 'adminShow'])->name('show')->middleware('auth');
        Route::get('/{id}/edit', [ZenithaLmsBlogController::class, 'adminEdit'])->name('edit')->middleware('auth');
        Route::put('/{id}', [ZenithaLmsBlogController::class, 'adminUpdate'])->name('admin.update')->middleware('auth');
        Route::delete('/{id}', [ZenithaLmsBlogController::class, 'adminDestroy'])->name('admin.destroy')->middleware('auth');
    });
});

// ZenithaLMS: Search (Tenant-Specific)
Route::get('/search', [App\Http\Controllers\Frontend\ZenithaLmsSearchController::class, 'search'])->name('search');

// ZenithaLMS: Notifications (Tenant-Specific)
Route::prefix('notifications')->name('zenithalms.tenant.notifications.')->middleware('auth')->group(function () {
    Route::get('/', [App\Http\Controllers\Frontend\ZenithaLmsNotificationController::class, 'index'])->name('index');
    Route::post('/{id}/read', [App\Http\Controllers\Frontend\ZenithaLmsNotificationController::class, 'markAsRead'])->name('read');
    Route::post('/read-all', [App\Http\Controllers\Frontend\ZenithaLmsNotificationController::class, 'markAllAsRead'])->name('read-all');
});
