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
use App\Http\Controllers\API\ZenithaLmsApiController;
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

// Health check endpoint (always available)
Route::get('/health', function () {
    return response()->json([
        'status' => 'healthy',
        'timestamp' => now()->toISOString(),
        'version' => '1.0.0',
        'service' => 'ZenithaLMS API'
    ]);
});

// Apply installation check to all API routes except health
Route::middleware('installed')->group(function () {

// Public API routes
Route::prefix('v1')->group(function () {
    // Health check endpoint
    Route::get('/health', function () {
        return response()->json([
            'status' => 'ok'
        ]);
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
}); // Close the installed middleware group
