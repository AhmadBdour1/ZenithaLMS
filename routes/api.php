<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\CourseAPIController;
use App\Http\Controllers\API\EbookAPIController;
use App\Http\Controllers\API\QuizAPIController;
use App\Http\Controllers\API\ForumAPIController;
use App\Http\Controllers\API\VirtualClassAPIController;
use App\Http\Controllers\API\NotificationAPIController;
use App\Http\Controllers\API\WalletAPIController;
use App\Http\Controllers\API\SearchAPIController;
use App\Http\Controllers\API\RecommendationAPIController;
use App\Http\Controllers\API\AdminAPIController;
use App\Http\Controllers\API\InstructorAPIController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| ZenithaLMS API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Health check endpoint
Route::get('/health', function () {
    return response()->json([
        'status' => 'healthy',
        'timestamp' => now()->toISOString(),
        'version' => '1.0.0',
        'service' => 'ZenithaLMS API'
    ]);
});

// Public API routes
Route::prefix('v1')->group(function () {
    // Versioned health check endpoint
    Route::get('/health', function () {
        return response()->json([
            'status' => 'healthy',
            'timestamp' => now()->toISOString(),
            'version' => '1.0.0',
            'service' => 'ZenithaLMS API'
        ]);
    });

    // Authentication routes
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    
    // Public course routes
    Route::get('/courses', [CourseAPIController::class, 'index']);
    Route::get('/courses/{slug}', [CourseAPIController::class, 'show']);
    
    // Search endpoint
    Route::get('/search', [SearchAPIController::class, 'search']);
    
    // Public ebook routes
    Route::get('/ebooks', [EbookAPIController::class, 'index']);
    Route::get('/ebooks/{id}', [EbookAPIController::class, 'show']);
    
    // Public quiz routes
    Route::get('/quizzes', [QuizAPIController::class, 'index']);
    Route::get('/quizzes/{id}', [QuizAPIController::class, 'show']);
    
    // Public forum routes
    Route::get('/forums', [ForumAPIController::class, 'index']);
    Route::get('/forums/{id}', [ForumAPIController::class, 'show']);
    Route::get('/forums/{forumId}/replies', [ForumAPIController::class, 'replies']);
    
    // Public virtual class routes
    Route::get('/virtual-classes', [VirtualClassAPIController::class, 'index']);
    Route::get('/virtual-classes/{id}', [VirtualClassAPIController::class, 'show']);
    
    // Protected routes (require authentication)
    Route::middleware('auth:sanctum')->group(function () {
        // User profile
        Route::get('/user/profile', [AuthController::class, 'profile']);
        Route::post('/logout', [AuthController::class, 'logout']);
        
        // User recommendations
        Route::get('/user/recommendations', [RecommendationAPIController::class, 'getRecommendations']);
        
        // Course management
        Route::post('/user/courses/{courseId}/enroll', [CourseAPIController::class, 'enroll']);
        Route::get('/user/courses', [CourseAPIController::class, 'myCourses']);
        
        // Quiz management
        Route::post('/quizzes/{quizId}/start', [QuizAPIController::class, 'start']);
        Route::post('/quiz-attempts/{attemptId}/submit', [QuizAPIController::class, 'submit']);
        Route::get('/user/quiz-attempts', [QuizAPIController::class, 'myAttempts']);
        
        // Forum management
        Route::post('/forums', [ForumAPIController::class, 'store']);
        Route::put('/forums/{id}', [ForumAPIController::class, 'update']);
        Route::delete('/forums/{id}', [ForumAPIController::class, 'destroy']);
        Route::post('/forums/{forumId}/reply', [ForumAPIController::class, 'reply']);
        Route::put('/forum-replies/{replyId}', [ForumAPIController::class, 'updateReply']);
        Route::delete('/forum-replies/{replyId}', [ForumAPIController::class, 'deleteReply']);
        
        // Virtual class management
        Route::post('/virtual-classes', [VirtualClassAPIController::class, 'store']);
        Route::put('/virtual-classes/{id}', [VirtualClassAPIController::class, 'update']);
        Route::delete('/virtual-classes/{id}', [VirtualClassAPIController::class, 'destroy']);
        Route::post('/virtual-classes/{classId}/join', [VirtualClassAPIController::class, 'join']);
        Route::post('/virtual-classes/{classId}/leave', [VirtualClassAPIController::class, 'leave']);
        Route::get('/user/virtual-classes', [VirtualClassAPIController::class, 'myClasses']);
        
        // Notification management
        Route::get('/user/notifications', [NotificationAPIController::class, 'index']);
        Route::post('/notifications/{notificationId}/read', [NotificationAPIController::class, 'markAsRead']);
        Route::post('/notifications/mark-all-read', [NotificationAPIController::class, 'markAllAsRead']);
        Route::delete('/notifications/{notificationId}', [NotificationAPIController::class, 'destroy']);
        Route::get('/user/notifications/unread-count', [NotificationAPIController::class, 'unreadCount']);
        Route::get('/user/notifications/statistics', [NotificationAPIController::class, 'statistics']);
        Route::post('/notifications', [NotificationAPIController::class, 'store']);
        
        // Wallet management
        Route::get('/user/wallet', [WalletAPIController::class, 'index']);
        Route::post('/wallet/add-funds', [WalletAPIController::class, 'addFunds']);
        Route::get('/wallet/transactions', [WalletAPIController::class, 'transactions']);
        Route::get('/wallet/statistics', [WalletAPIController::class, 'statistics']);
        Route::post('/wallet/withdraw', [WalletAPIController::class, 'withdraw']);
        Route::post('/wallet/transfer', [WalletAPIController::class, 'transfer']);
        
        // Admin routes
        Route::prefix('admin')->group(function () {
            Route::get('/dashboard', [AdminAPIController::class, 'dashboard']);
        });
        
        // Instructor routes
        Route::prefix('instructor')->group(function () {
            Route::get('/dashboard', [InstructorAPIController::class, 'dashboard']);
        });
    });
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
});
