<?php

use App\Http\Controllers\API\CourseAPIController;
use App\Http\Controllers\Frontend\CourseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Courses Module Routes
|--------------------------------------------------------------------------
*/

// API Routes
Route::prefix('api/v1')->middleware('installed')->group(function () {
    // Public course routes
    Route::middleware('feature:courses')->group(function () {
        Route::get('/courses', [CourseAPIController::class, 'index']);
        Route::get('/courses/{slug}', [CourseAPIController::class, 'show']);
    });
    
    // Protected course management routes
    Route::middleware(['auth:sanctum', 'feature:courses'])->group(function () {
        // Course management
        Route::post('/user/courses/{courseId}/enroll', [CourseAPIController::class, 'enroll']);
        Route::get('/user/courses', [CourseAPIController::class, 'myCourses']);
    });
});

// Web Routes
Route::middleware('feature:courses')->group(function () {
    Route::get('/courses', function (Request $request) {
        // Build query builder
        $query = \App\Models\Course::with(['instructor', 'category'])
            ->where('is_published', 1);
        
        // Apply filters
        if ($request->has('category')) {
            $query->where('category_id', $request->category);
        }
        
        if ($request->has('level')) {
            $query->where('level', $request->level);
        }
        
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', '%' . $search . '%')
                  ->orWhere('description', 'like', '%' . $search . '%');
            });
        }
        
        // Sort options
        $sort = $request->get('sort', 'created_at');
        $direction = $request->get('direction', 'desc');
        $query->orderBy($sort, $direction);
        
        $categories = \App\Models\Category::orderBy('name')->get();
        
        // Paginate with query params preserved
        $courses = $query->paginate(12)->appends($request->query());
        
        return view('courses.index', compact('categories', 'courses'));
    })->name('courses.index');

    Route::get('/courses/{slug}', [CourseController::class, 'show'])->name('courses.show');
});
