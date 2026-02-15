<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\Frontend\ZenithaLmsEbookController;
use App\Http\Controllers\Frontend\ZenithaLmsBlogController;
use App\Http\Controllers\Frontend\ZenithaLmsPaymentController;

/*
|--------------------------------------------------------------------------
| ZenithaLMS Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// Homepage - Removed duplicate, handled in web.php

// Authentication Routes - Laravel Breeze handles this
// Auth::routes();

// ZenithaLMS: Course Routes
Route::get('/courses', function (Request $request) {
    // Build query builder
    $query = \App\Models\Course::with(['instructor', 'category'])
        ->where('is_published', 1);
    
    // Search filter (title/description)
    if ($request->filled('search')) {
        $searchTerm = $request->get('search');
        $query->where(function($q) use ($searchTerm) {
            $q->where('title', 'like', '%' . $searchTerm . '%')
              ->orWhere('description', 'like', '%' . $searchTerm . '%');
        });
    }
    
    // Category filter (accept slug OR id)
    if ($request->filled('category')) {
        $categoryValue = $request->get('category');
        $query->where(function($q) use ($categoryValue) {
            $q->whereHas('category', function($subQ) use ($categoryValue) {
                $subQ->where('slug', $categoryValue);
            })->orWhere('category_id', $categoryValue);
        });
    }
    
    // Level filter (exact match)
    if ($request->filled('level')) {
        $query->where('level', $request->get('level'));
    }
    
    // Price type filter
    if ($request->filled('price_type')) {
        $priceType = $request->get('price_type');
        if ($priceType === 'free') {
            $query->where(function($q) {
                $q->where('is_free', 1)->orWhere('price', 0);
            });
        } elseif ($priceType === 'paid') {
            $query->where('is_free', 0)->where('price', '>', 0);
        }
    }
    
    // Sort filter
    if ($request->filled('sort')) {
        $sort = $request->get('sort');
        switch ($sort) {
            case 'newest':
                $query->orderBy('created_at', 'desc');
                break;
            case 'price_asc':
                $query->orderBy('price', 'asc');
                break;
            case 'price_desc':
                $query->orderBy('price', 'desc');
                break;
            case 'featured':
                $query->orderBy('is_featured', 'desc')->orderBy('created_at', 'desc');
                break;
            default:
                $query->orderBy('created_at', 'desc');
        }
    } else {
        $query->orderBy('created_at', 'desc');
    }
    
    // Get categories for filter dropdown
    $categories = \App\Models\Category::orderBy('name')->get();
    
    // Paginate with query params preserved
    $courses = $query->paginate(12)->appends($request->query());
    
    return view('courses.index', compact('categories', 'courses'));
})->name('courses.index');

Route::get('/courses/{slug}', [App\Http\Controllers\Frontend\CourseController::class, 'show'])->name('courses.show');

// ZenithaLMS: Ebook Routes
Route::prefix('ebooks')->name('zenithalms.ebooks.')->group(function () {
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

// ZenithaLMS: Blog Routes
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

// Simple blog routes for now
Route::get('/blog', function () {
    $blogs = \App\Models\Blog::with(['user', 'category'])->where('status', 'published')->paginate(12);
    return view('blog.index', compact('blogs'));
});

// ZenithaLMS: Payment Routes - Temporarily commented out
/*
Route::prefix('payment')->name('zenithalms.payment.')->group(function () {
    Route::get('/checkout', [ZenithaLmsPaymentController::class, 'checkout'])->name('checkout');
    Route::post('/process', [ZenithaLmsPaymentController::class, 'processPayment'])->name('process');
    Route::get('/success/{orderId}', [ZenithaLmsPaymentController::class, 'success'])->name('success');
    Route::get('/failed/{orderId}', [ZenithaLmsPaymentController::class, 'failed'])->name('failed');
    Route::get('/history', [ZenithaLmsPaymentController::class, 'history'])->name('history');
    Route::get('/wallet', [ZenithaLmsPaymentController::class, 'wallet'])->name('wallet');
    Route::post('/add-funds', [ZenithaLmsPaymentController::class, 'addFunds'])->name('add-funds');
    Route::post('/apply-coupon', [ZenithaLmsPaymentController::class, 'applyCoupon'])->name('apply-coupon');
});
*/

// ZenithaLMS: Dashboard Routes
Route::prefix('dashboard')->name('zenithalms.dashboard.')->middleware('auth')->group(function () {
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
            'my_courses' => \App\Models\Course::where('instructor_id', $user->id)->count(),
            'total_enrollments' => \App\Models\Enrollment::whereHas('course', function($query) use ($user) {
                return $query->where('instructor_id', $user->id);
            })->count(),
            'earnings' => 0,
            'pending_qa' => 0,
        ];
        return view('zenithalms.dashboard.instructor', compact('user', 'stats'));
    })->name('instructor')->middleware('role:instructor');
    
    Route::get('/admin', function () {
        $user = auth()->user();
        $stats = [
            'total_users' => \App\Models\User::count(),
            'total_courses' => \App\Models\Course::count(),
            'total_enrollments' => \App\Models\Enrollment::count(),
            'total_revenue' => 0,
            'recent_registrations' => \App\Models\User::latest()->take(5)->get(),
        ];
        return view('zenithalms.dashboard.admin', compact('user', 'stats'));
    })->name('admin')->middleware('role:admin');
    
    Route::get('/organization', function () {
        $user = auth()->user();
        $stats = [
            'members_count' => \App\Models\User::where('organization_id', $user->organization_id)->count(),
            'assigned_courses' => \App\Models\Course::where('organization_id', $user->organization_id)->count(),
            'progress_overview' => [
                'total_enrollments' => \App\Models\Enrollment::whereHas('user', function($query) use ($user) {
                    return $query->where('organization_id', $user->organization_id);
                })->count(),
                'completed_courses' => \App\Models\Enrollment::where('status', 'completed')
                    ->whereHas('user', function($query) use ($user) {
                        return $query->where('organization_id', $user->organization_id);
                    })->count(),
            ],
        ];
        return view('zenithalms.dashboard.organization', compact('user', 'stats'));
    })->name('organization')->middleware('role:organization_admin');
});

// ZenithaLMS: AI Assistant Routes
Route::get('/ai/assistant', function () {
    return view('zenithalms.ai.assistant');
})->name('ai.assistant');

// ZenithaLMS: API Routes
Route::prefix('api/v1')->name('zenithalms.api.')->group(function () {
    // Ebooks API - Documentation only (moved to api.php)
    // Route::get('/ebooks', function () {
    //     return response()->json([
    //         'message' => 'ZenithaLMS Ebooks API',
    //         'version' => '1.0.0',
    //         'endpoints' => [
    //             'GET /api/v1/ebooks - List all ebooks',
    //             'GET /api/v1/ebooks/{id} - Get ebook details',
    //             'POST /api/v1/ebooks - Create ebook (auth required)',
    //             'PUT /api/v1/ebooks/{id} - Update ebook (auth required)',
    //             'DELETE /api/v1/ebooks/{id} - Delete ebook (auth required)',
    //         ]
    //     ]);
    // });
    
    // Blog API
    Route::get('/blogs', function () {
        return response()->json([
            'message' => 'ZenithaLMS Blog API',
            'version' => '1.0.0',
            'endpoints' => [
                'GET /api/v1/blogs - List all blogs',
                'GET /api/v1/blogs/{id} - Get blog details',
                'POST /api/v1/blogs - Create blog (auth required)',
                'PUT /api/v1/blogs/{id} - Update blog (auth required)',
                'DELETE /api/v1/blogs/{id} - Delete blog (auth required)',
            ]
        ]);
    });
    
    // Search API
    Route::get('/search', function (Request $request) {
        $query = $request->get('q');
        
        if (!$query) {
            return response()->json([
                'message' => 'Search query is required',
                'results' => []
            ]);
        }
        
        // ZenithaLMS: AI-powered search
        $results = [];
        
        // Search in ebooks
        $ebookResults = \App\Models\Ebook::where('status', 'active')
            ->where(function ($q) use ($query) {
                $q->where('title', 'like', '%' . $query . '%')
                  ->orWhere('description', 'like', '%' . $query . '%')
                  ->orWhereJsonContains('ai_tags', $query);
            })
            ->limit(5)
            ->get();
            
        foreach ($ebookResults as $ebook) {
            $results[] = [
                'type' => 'ebook',
                'id' => $ebook->id,
                'title' => $ebook->title,
                'description' => $ebook->excerpt,
                'url' => route('zenithalms.ebooks.show', $ebook->slug),
                'thumbnail' => $ebook->getThumbnailUrl(),
                'price' => $ebook->price,
                'is_free' => $ebook->is_free,
            ];
        }
        
        // Search in blogs
        $blogResults = \App\Models\Blog::where('status', 'published')
            ->where(function ($q) use ($query) {
                $q->where('title', 'like', '%' . $query . '%')
                  ->orWhere('excerpt', 'like', '%' . $query . '%')
                  ->orWhere('content', 'like', '%' . $query . '%');
            })
            ->limit(5)
            ->get();
            
        foreach ($blogResults as $blog) {
            $results[] = [
                'type' => 'blog',
                'id' => $blog->id,
                'title' => $blog->title,
                'description' => $blog->excerpt,
                'url' => route('zenithalms.blog.show', $blog->slug),
                'featured_image' => $blog->getFeaturedImageUrl(),
                'reading_time' => $blog->reading_time,
            ];
        }
        
        return response()->json([
            'query' => $query,
            'total_results' => count($results),
            'results' => $results,
        ]);
    });
    
    // Recommendations API
    Route::get('/recommendations', function (Request $request) {
        $user = auth()->user();
        
        if (!$user) {
            return response()->json([
                'message' => 'Authentication required',
                'recommendations' => []
            ]);
        }
        
        $type = $request->get('type', 'courses');
        $limit = $request->get('limit', 5);
        
        $recommendations = [];
        
        if ($type === 'courses') {
            $recommendations = $user->getRecommendedCourses($limit);
        } elseif ($type === 'ebooks') {
            // Get user's favorite categories
            $favoriteCategories = $user->favoriteEbooks()
                ->with('category')
                ->get()
                ->pluck('category.id')
                ->unique();
            
            $recommendations = \App\Models\Ebook::whereIn('category_id', $favoriteCategories)
                ->where('status', 'active')
                ->limit($limit)
                ->get();
        }
        
        return response()->json([
            'type' => $type,
            'recommendations' => $recommendations->map(function ($item) {
                return [
                    'id' => $item->id,
                    'title' => $item->title,
                    'description' => $item->description ?? $item->excerpt,
                    'url' => $type === 'courses' ? 
                        route('zenithalms.courses.show', $item->slug) : 
                        route('zenithalms.ebooks.show', $item->slug),
                    'thumbnail' => $type === 'courses' ? 
                        $item->getThumbnailUrl() : 
                        $item->getThumbnailUrl(),
                    'price' => $item->price,
                    'is_free' => $item->is_free ?? false,
                ];
            }),
        ]);
    });
});

// ZenithaLMS: System Routes
Route::prefix('system')->name('zenithalms.system.')->middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/analytics', function () {
        return view('zenithalms.system.analytics');
    })->name('analytics');
    
    Route::get('/settings', function () {
        return view('zenithaLms.system.settings');
    })->name('settings');
    
    Route::get('/users', function () {
        return view('zenithalms.system.users');
    })->name('users');
    
    Route::get('/reports', function () {
        return view('zenithalms.system.reports');
    })->name('reports');
});

// ZenithaLMS: Utility Routes
Route::get('/sitemap.xml', function () {
    return response()->view('zenithalms.sitemap.index')->header('Content-Type', 'application/xml');
});

Route::get('/sitemap-ebooks.xml', function () {
    return response()->view('zenithalms.sitemap.ebooks')->header('Content-Type', 'text/xml');
});

Route::get('/sitemap-blogs.xml', function () {
    return response()->view('zenithalms.sitemap.blogs')->header('Content-Type', 'text/xml');
});

Route::get('/robots.txt', function () {
    $content = "User-agent: *\n";
    $content .= "Allow: /\n";
    $content .= "Disallow: /admin/\n";
    $content .= "Disallow: /system/\n";
    $content .= "Disallow: /api/\n";
    $content .= "Sitemap: " . url('/sitemap.xml') . "\n";
    
    return response($content)->header('Content-Type', 'text/plain');
});

// ZenithaLMS: Webhook Routes
Route::post('/webhooks/payment-gateway/{gateway}', function ($gateway) {
    // ZenithaLMS: Handle payment gateway webhooks
    return response()->json(['message' => 'Webhook received']);
});

Route::post('/webhooks/email-service', function () {
    // ZenithaLMS: Handle email service webhooks
    return response()->json(['message' => 'Webhook received']);
});

// ZenithaLMS: Development Routes (only in local environment)
if (app()->environment('local')) {
    Route::get('/dev/test', function () {
        return view('zenithalms.dev.test');
    })->name('dev.test');
    
    Route::get('/dev/email', function () {
        return view('zenithalms.dev.email');
    })->name('dev.email');
    
    Route::get('/dev/ai', function () {
        return view('zenithalms.dev.ai');
    })->name('dev.ai');
}
