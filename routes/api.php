<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\CourseAPIController;
use App\Http\Controllers\API\EbookAPIController;
use App\Http\Controllers\API\EbookDownloadController;
use App\Http\Controllers\API\QuizAPIController;
use App\Http\Controllers\API\ForumAPIController;
use App\Http\Controllers\API\VirtualClassAPIController;
use App\Http\Controllers\API\ZenithaLmsPaymentController;
use App\Http\Controllers\API\WalletAPIController;
use App\Http\Controllers\API\SearchAPIController;
use App\Http\Controllers\API\RecommendationAPIController;
use App\Http\Controllers\API\AdminAPIController;
use App\Http\Controllers\API\InstructorAPIController;
use App\Http\Controllers\API\NotificationAPIController;
use App\Http\Controllers\API\ZenithaLmsApiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Health check endpoint (always available)
Route::get('/health', function () {
    return response()->json([
        'status' => 'healthy',
        'timestamp' => now()->toISOString(),
        'version' => '1.0.0',
        'service' => 'ZenithaLMS API'
    ]);
});

// Public API routes (outside installation check)
Route::prefix('v1')->group(function () {
    // Health check endpoint
    Route::get('/health', function () {
        return response()->json([
            'status' => 'ok'
        ]);
    });
});

// Apply installation check to all API routes except health
Route::middleware('installed')->group(function () {

    // Define rate limiting for financial operations
    RateLimiter::for('financial', function (Request $request) {
        return Limit::perMinute(10)->by($request->user()?->id ?? $request->ip());
    });

    // Define rate limiting for sensitive operations
    RateLimiter::for('sensitive', function (Request $request) {
        return Limit::perMinute(5)->by($request->user()?->id ?? $request->ip());
    });

    // API documentation
    Route::get('/docs', function () {
        return response()->json([
            'name' => 'ZenithaLMS API',
            'version' => '1.0.0',
            'description' => 'RESTful API for ZenithaLMS Learning Management System',
            'endpoints' => [
                'Authentication' => [
                    'POST /api/v1/register - Register new user',
                    'POST /api/v1/login - User login',
                    'GET /api/v1/user/profile - Get user profile (auth required)',
                    'POST /api/v1/logout - User logout (auth required)',
                ],
                'Courses' => [
                    'GET /api/v1/courses - List all courses',
                    'GET /api/v1/courses/{slug} - Get course details',
                    'POST /api/v1/user/courses/{courseId}/enroll - Enroll in course (auth required)',
                    'GET /api/v1/user/courses - Get user enrolled courses (auth required)',
                ],
            ],
            'base_url' => url('/api'),
        ]);
    }); // Close the installed middleware group

    // Public API routes
    Route::prefix('v1')->group(function () {
        // Authentication routes
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/login', [AuthController::class, 'login']);
        
        // Public course routes
        Route::get('/courses', [CourseAPIController::class, 'index']);
        Route::get('/courses/{slug}', [CourseAPIController::class, 'show']);
        
        // Search functionality
        Route::get('/search', [SearchAPIController::class, 'search']);
    });

    // Protected API routes with authentication
    Route::middleware(['auth:sanctum', 'installed'])->prefix('v1')->group(function () {
        
        // User routes
        Route::get('/user/profile', [AuthController::class, 'profile']);
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/user/courses', [CourseAPIController::class, 'userCourses']);
        Route::post('/user/courses/{courseId}/enroll', [CourseAPIController::class, 'enroll']);
        
        // Payment routes with rate limiting
        Route::middleware(['throttle:financial'])->group(function () {
            Route::post('/payments/process', [ZenithaLmsPaymentController::class, 'processPayment']);
            Route::post('/payments/add-funds', [ZenithaLmsPaymentController::class, 'addFunds']);
            Route::post('/payments/apply-coupon', [ZenithaLmsPaymentController::class, 'applyCoupon']);
        });
        
        // Other payment routes (less restrictive)
        Route::get('/payments', [ZenithaLmsPaymentController::class, 'index']);
        Route::get('/payments/{id}', [ZenithaLmsPaymentController::class, 'show']);
        Route::get('/payments/gateways', [ZenithaLmsPaymentController::class, 'paymentGateways']);
        Route::get('/payments/methods', [ZenithaLmsPaymentController::class, 'paymentMethods']);
        Route::get('/payments/statistics', [ZenithaLmsPaymentController::class, 'statistics']);
        Route::get('/payments/wallet', [ZenithaLmsPaymentController::class, 'wallet']);
        Route::get('/payments/wallet/transactions', [ZenithaLmsPaymentController::class, 'walletTransactions']);
        
        // Ebook API routes
        Route::prefix('ebooks')->name('api.ebooks.')->group(function () {
            Route::get('/', [EbookAPIController::class, 'index'])->name('index')->middleware('feature:ebooks');
            Route::get('/{id}', [EbookAPIController::class, 'show'])->name('show')->middleware('feature:ebooks');
            Route::post('/', [EbookAPIController::class, 'store'])->name('store')->middleware('feature:ebooks');
            Route::put('/{id}', [EbookAPIController::class, 'update'])->name('update')->middleware('feature:ebooks');
            Route::delete('/{id}', [EbookAPIController::class, 'destroy'])->name('destroy')->middleware('feature:ebooks');
            Route::get('/{id}/download', [EbookDownloadController::class, 'download'])->name('download')->middleware('feature:ebooks');
        });
        
        // Quiz API routes
        Route::prefix('quizzes')->group(function () {
            Route::get('/', [QuizAPIController::class, 'index']);
            Route::get('/{id}', [QuizAPIController::class, 'show']);
            Route::post('/{id}/start', [QuizAPIController::class, 'start']);
            Route::post('/{id}/submit', [QuizAPIController::class, 'submit']);
        });
        
        // Forum API routes
        Route::prefix('forums')->group(function () {
            Route::get('/', [ForumAPIController::class, 'index']);
            Route::get('/{id}', [ForumAPIController::class, 'show']);
            Route::post('/{id}/reply', [ForumAPIController::class, 'reply']);
        });
        
        // Virtual Class API routes
        Route::prefix('virtual-classes')->group(function () {
            Route::get('/', [VirtualClassAPIController::class, 'index']);
            Route::get('/{id}', [VirtualClassAPIController::class, 'show']);
            Route::post('/{id}/join', [VirtualClassAPIController::class, 'join']);
        });
        
        // Notification routes
        Route::prefix('notifications')->group(function () {
            Route::get('/', [AuthController::class, 'notifications']);
            Route::post('/{id}/read', [AuthController::class, 'markAsRead']);
        });
        
        // Wallet routes
        Route::prefix('wallet')->group(function () {
            Route::get('/', [WalletAPIController::class, 'balance']);
            Route::post('/add-funds', [WalletAPIController::class, 'addFunds']);
        });
        
        // Recommendation routes
        Route::prefix('recommendations')->group(function () {
            Route::get('/', [RecommendationAPIController::class, 'getRecommendations']);
        });
        
        // Admin routes
        Route::prefix('admin')->middleware(['admin'])->group(function () {
            Route::get('/dashboard', [AdminAPIController::class, 'dashboard']);
        });
        
        // Instructor routes
        Route::prefix('instructor')->middleware(['instructor'])->group(function () {
            Route::get('/dashboard', [InstructorAPIController::class, 'dashboard']);
        });
        
        // Sensitive operations with stricter rate limiting
        Route::middleware(['throttle:sensitive'])->group(function () {
            Route::post('/account/delete', function () {
                // Account deletion endpoint
            });
            Route::post('/password/reset', function () {
                // Password reset endpoint
            });
        });
    });
});
