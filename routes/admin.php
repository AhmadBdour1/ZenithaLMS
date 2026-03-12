<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminManagementController;
use App\Http\Controllers\Admin\AdminUserManagementController;
use App\Http\Controllers\Admin\AdminExportController;
use App\Http\Controllers\Admin\AdminQuickLoginController;
use App\Http\Controllers\Admin\AdminImpersonationBarController;
use App\Http\Controllers\Admin\AdminRefundController;
use App\Http\Controllers\Admin\AdminPermissionsController;
use App\Http\Controllers\Admin\AdminLanguageController;
use App\Http\Controllers\Admin\AdminEntitiesController;
use App\Http\Controllers\Admin\AdminToolsController;
use Illuminate\Http\Request;

// Admin Dashboard Routes
Route::prefix('admin')->middleware(['auth', 'admin'])->group(function () {
    
    // Dashboard
    Route::get('/dashboard', [AdminDashboardController::class, 'index']);
    
    // User Management
    Route::prefix('users')->group(function () {
        Route::get('/', [AdminManagementController::class, 'getUsers']);
        Route::post('/', [AdminManagementController::class, 'createUser']);
        Route::put('/{id}', [AdminManagementController::class, 'updateUser']);
        Route::delete('/{id}', [AdminManagementController::class, 'deleteUser']);
        Route::post('/bulk-action', [AdminManagementController::class, 'bulkUserAction']);
        
        // Enhanced User Management
        Route::get('/{id}/details', [AdminUserManagementController::class, 'getUserDetails']);
        Route::get('/{id}/permissions', [AdminUserManagementController::class, 'getUserPermissions']);
        Route::post('/{id}/roles', [AdminUserManagementController::class, 'assignRole']);
        Route::delete('/{id}/roles', [AdminUserManagementController::class, 'removeRole']);
        Route::post('/{id}/permissions', [AdminUserManagementController::class, 'assignPermissions']);
        Route::post('/{id}/password-reset', [AdminUserManagementController::class, 'sendPasswordReset']);
        Route::post('/{id}/impersonate', [AdminUserManagementController::class, 'impersonate']);
        Route::post('/stop-impersonate', [AdminUserManagementController::class, 'stopImpersonate']);
        Route::get('/available-roles-permissions', [AdminUserManagementController::class, 'getAvailableRolesAndPermissions']);
    });

    // Export Management
    Route::prefix('export')->group(function () {
        Route::post('/{entity}', [AdminExportController::class, 'exportEntity']);
        Route::get('/download/{filename}', [AdminExportController::class, 'downloadExport'])->name('admin.export.download');
        Route::get('/{entity}/templates', [AdminExportController::class, 'getExportTemplates']);
        Route::post('/{entity}/template', [AdminExportController::class, 'exportUsingTemplate']);
        Route::get('/history', [AdminExportController::class, 'getExportHistory']);
        Route::delete('/{filename}', [AdminExportController::class, 'deleteExport']);
    });

    // Quick Login Management
    Route::prefix('quick-login')->group(function () {
        Route::get('/search', [AdminQuickLoginController::class, 'searchEntities']);
        Route::post('/login', [AdminQuickLoginController::class, 'quickLogin'])->name('admin.quick-login.login');
        Route::post('/stop', [AdminQuickLoginController::class, 'stopImpersonation']);
        Route::get('/status', [AdminQuickLoginController::class, 'getImpersonationStatus']);
        Route::post('/extend', [AdminQuickLoginController::class, 'extendImpersonation']);
        Route::get('/history', [AdminQuickLoginController::class, 'getImpersonationHistory']);
    });

    // Impersonation Bar Management
    Route::prefix('impersonation')->group(function () {
        Route::get('/bar', [AdminImpersonationBarController::class, 'getImpersonationBar']);
        Route::get('/notification', [AdminImpersonationBarController::class, 'showNotification']);
        Route::get('/admin-warning', [AdminImpersonationBarController::class, 'getAdminWarning']);
        Route::get('/quick-actions', [AdminImpersonationBarController::class, 'getQuickActions']);
        Route::get('/stats', [AdminImpersonationBarController::class, 'getImpersonationStats']);
    });
    
    // Course Management
    Route::get('/courses', [AdminManagementController::class, 'getCourses']);
    Route::get('/courses/create', [AdminManagementController::class, 'createCourse']);
    Route::put('/courses/{id}/status', [AdminManagementController::class, 'updateCourseStatus']);
    Route::post('/courses/bulk-action', [AdminManagementController::class, 'bulkCourseAction']);
    
    // Category Management
    Route::get('/categories', [AdminManagementController::class, 'getCategories']);
    Route::post('/categories', [AdminManagementController::class, 'createCategory']);
    Route::put('/categories/{id}', [AdminManagementController::class, 'updateCategory']);
    Route::delete('/categories/{id}', [AdminManagementController::class, 'deleteCategory']);
    
    // Lesson Management
    Route::get('/lessons', [AdminManagementController::class, 'getLessons']);
    Route::get('/lessons/create', [AdminManagementController::class, 'createLesson']);
    Route::post('/lessons', [AdminManagementController::class, 'storeLesson']);
    Route::put('/lessons/{id}', [AdminManagementController::class, 'updateLesson']);
    Route::delete('/lessons/{id}', [AdminManagementController::class, 'deleteLesson']);
    
    // Quiz Management
    Route::get('/quizzes', [AdminManagementController::class, 'getQuizzes']);
    Route::get('/quizzes/create', [AdminManagementController::class, 'createQuiz']);
    Route::post('/quizzes', [AdminManagementController::class, 'storeQuiz']);
    Route::put('/quizzes/{id}', [AdminManagementController::class, 'updateQuiz']);
    Route::delete('/quizzes/{id}', [AdminManagementController::class, 'deleteQuiz']);
    
    // Role Management
    Route::get('/roles', [AdminPermissionsController::class, 'index']);
    Route::post('/roles', [AdminPermissionsController::class, 'storeRole']);
    Route::put('/roles/{id}', [AdminPermissionsController::class, 'updateRole']);
    Route::delete('/roles/{id}', [AdminPermissionsController::class, 'deleteRole']);
    
    // Activity Management
    Route::get('/activity', [AdminManagementController::class, 'getActivity']);
    
    // Settings Management
    Route::prefix('settings')->group(function () {
        Route::get('/general', [AdminManagementController::class, 'getGeneralSettings']);
        Route::put('/general', [AdminManagementController::class, 'updateGeneralSettings']);
        Route::get('/email', [AdminManagementController::class, 'getEmailSettings']);
        Route::put('/email', [AdminManagementController::class, 'updateEmailSettings']);
        Route::get('/security', [AdminManagementController::class, 'getSecuritySettings']);
        Route::put('/security', [AdminManagementController::class, 'updateSecuritySettings']);
    });
    
    // Reports Management
    Route::prefix('reports')->group(function () {
        Route::get('/users', [AdminManagementController::class, 'getUserReports']);
        Route::get('/revenue', [AdminManagementController::class, 'getRevenueReports']);
        Route::get('/activity', [AdminManagementController::class, 'getActivityReports']);
    });
    
    // Subscription Management
    Route::get('/subscriptions', [AdminManagementController::class, 'getSubscriptions']);
    Route::put('/subscriptions/{id}', [AdminManagementController::class, 'updateSubscription']);
    Route::post('/subscriptions/{id}/cancel', [AdminManagementController::class, 'cancelSubscription']);
    
    // Marketplace Management
    Route::get('/marketplace/products', [AdminManagementController::class, 'getMarketplaceProducts']);
    Route::put('/marketplace/products/{id}/status', [AdminManagementController::class, 'updateProductStatus']);
    Route::get('/marketplace/orders', [AdminManagementController::class, 'getMarketplaceOrders']);
    Route::put('/marketplace/orders/{id}/status', [AdminManagementController::class, 'updateOrderStatus']);
    
    // System Settings
    Route::get('/settings', [AdminManagementController::class, 'getSystemSettings']);
    Route::put('/settings', [AdminManagementController::class, 'updateSystemSettings']);
    
    // Analytics
    Route::get('/analytics', [AdminManagementController::class, 'getAnalytics']);
    
    // System Tools
    Route::prefix('tools')->group(function () {
        // Cache Management
        Route::post('/cache/clear', [AdminToolsController::class, 'clearCache']);
        Route::post('/system/optimize', [AdminToolsController::class, 'optimizeSystem']);
        
        // Backup & Restore
        Route::post('/backup', [AdminToolsController::class, 'backupSystem']);
        Route::get('/backup/download/{filename}', function ($filename) {
            $filePath = storage_path('backups/' . $filename);
            if (file_exists($filePath)) {
                return response()->download($filePath);
            }
            return response()->json(['error' => 'Backup not found'], 404);
        })->name('admin.download-backup');
        
        // Logs
        Route::get('/logs', [AdminToolsController::class, 'getSystemLogs']);
        Route::post('/logs/clear', [AdminToolsController::class, 'clearSystemLogs']);
        
        // Data Management
        Route::post('/data/export', [AdminToolsController::class, 'exportData']);
        Route::get('/data/export/download/{filename}', function ($filename) {
            $filePath = storage_path('exports/' . $filename);
            if (file_exists($filePath)) {
                return response()->download($filePath);
            }
            return response()->json(['error' => 'Export not found'], 404);
        })->name('admin.download-export');
        Route::post('/data/import', [AdminToolsController::class, 'importData']);
        Route::post('/data/cleanup', [AdminToolsController::class, 'cleanupData']);
        
        // Maintenance
        Route::post('/maintenance/toggle', [AdminToolsController::class, 'toggleMaintenance']);
        Route::post('/maintenance/migrate', [AdminToolsController::class, 'runMigrations']);
        Route::post('/maintenance/test-email', [AdminToolsController::class, 'sendTestEmail']);
        
        // Monitoring
        Route::get('/status', [AdminToolsController::class, 'getSystemStatus']);
        Route::get('/performance', [AdminToolsController::class, 'getPerformanceMetrics']);
    });
    
    // Quick Stats API
    Route::prefix('stats')->group(function () {
        Route::get('/overview', function () {
            return response()->json([
                'success' => true,
                'data' => [
                    'users' => \App\Models\User::count(),
                    'courses' => \App\Models\Course::count(),
                    'subscriptions' => \App\Models\Subscription::count(),
                    'orders' => \App\Models\AuraOrder::count(),
                    'revenue' => \App\Models\AuraOrder::where('status', 'completed')->sum('total_amount'),
                ]
            ]);
        });
        
        Route::get('/users', function () {
            $now = now();
            return response()->json([
                'success' => true,
                'data' => [
                    'total' => \App\Models\User::count(),
                    'today' => \App\Models\User::whereDate('created_at', $now->toDateString())->count(),
                    'this_week' => \App\Models\User::whereBetween('created_at', [$now->startOfWeek(), $now->endOfWeek()])->count(),
                    'this_month' => \App\Models\User::whereMonth('created_at', $now->month)->whereYear('created_at', $now->year)->count(),
                    'active' => \App\Models\User::where('status', 'active')->count(),
                    'by_role' => [
                        'admin' => \App\Models\User::where('role', 'admin')->count(),
                        'instructor' => \App\Models\User::where('role', 'instructor')->count(),
                        'student' => \App\Models\User::where('role', 'student')->count(),
                        'vendor' => \App\Models\User::where('role', 'vendor')->count(),
                    ],
                ]
            ]);
        });
        
        Route::get('/courses', function () {
            $now = now();
            return response()->json([
                'success' => true,
                'data' => [
                    'total' => \App\Models\Course::count(),
                    'published' => \App\Models\Course::where('status', 'published')->count(),
                    'draft' => \App\Models\Course::where('status', 'draft')->count(),
                    'today' => \App\Models\Course::whereDate('created_at', $now->toDateString())->count(),
                    'this_week' => \App\Models\Course::whereBetween('created_at', [$now->startOfWeek(), $now->endOfWeek()])->count(),
                    'this_month' => \App\Models\Course::whereMonth('created_at', $now->month)->whereYear('created_at', $now->year)->count(),
                    'by_level' => [
                        'beginner' => \App\Models\Course::where('level', 'beginner')->count(),
                        'intermediate' => \App\Models\Course::where('level', 'intermediate')->count(),
                        'advanced' => \App\Models\Course::where('level', 'advanced')->count(),
                        'all_levels' => \App\Models\Course::where('level', 'all_levels')->count(),
                    ],
                ]
            ]);
        });
        
        Route::get('/subscriptions', function () {
            $now = now();
            return response()->json([
                'success' => true,
                'data' => [
                    'total' => \App\Models\Subscription::count(),
                    'active' => \App\Models\Subscription::where('status', 'active')->count(),
                    'trialing' => \App\Models\Subscription::where('status', 'trialing')->count(),
                    'canceled' => \App\Models\Subscription::where('status', 'canceled')->count(),
                    'expired' => \App\Models\Subscription::where('status', 'expired')->count(),
                    'today' => \App\Models\Subscription::whereDate('created_at', $now->toDateString())->count(),
                    'this_week' => \App\Models\Subscription::whereBetween('created_at', [$now->startOfWeek(), $now->endOfWeek()])->count(),
                    'this_month' => \App\Models\Subscription::whereMonth('created_at', $now->month)->whereYear('created_at', $now->year)->count(),
                    'revenue' => \App\Models\Subscription::active()->sum('price'),
                ]
            ]);
        });
        
        Route::get('/marketplace', function () {
            $now = now();
            return response()->json([
                'success' => true,
                'data' => [
                    'products' => [
                        'total' => \App\Models\AuraProduct::count(),
                        'active' => \App\Models\AuraProduct::where('status', 'active')->count(),
                        'inactive' => \App\Models\AuraProduct::where('status', 'inactive')->count(),
                        'today' => \App\Models\AuraProduct::whereDate('created_at', $now->toDateString())->count(),
                        'this_week' => \App\Models\AuraProduct::whereBetween('created_at', [$now->startOfWeek(), $now->endOfWeek()])->count(),
                        'this_month' => \App\Models\AuraProduct::whereMonth('created_at', $now->month)->whereYear('created_at', $now->year)->count(),
                    ],
                    'orders' => [
                        'total' => \App\Models\AuraOrder::count(),
                        'pending' => \App\Models\AuraOrder::where('status', 'pending')->count(),
                        'processing' => \App\Models\AuraOrder::where('status', 'processing')->count(),
                        'completed' => \App\Models\AuraOrder::where('status', 'completed')->count(),
                        'cancelled' => \App\Models\AuraOrder::where('status', 'cancelled')->count(),
                        'today' => \App\Models\AuraOrder::whereDate('created_at', $now->toDateString())->count(),
                        'this_week' => \App\Models\AuraOrder::whereBetween('created_at', [$now->startOfWeek(), $now->endOfWeek()])->count(),
                        'this_month' => \App\Models\AuraOrder::whereMonth('created_at', $now->month)->whereYear('created_at', $now->year)->count(),
                        'revenue' => \App\Models\AuraOrder::where('status', 'completed')->sum('total_amount'),
                    ],
                ]
            ]);
        });
        
        Route::get('/revenue', function () {
            $now = now();
            return response()->json([
                'success' => true,
                'data' => [
                    'total' => \App\Models\AuraOrder::where('status', 'completed')->sum('total_amount') + \App\Models\Subscription::active()->sum('price'),
                    'today' => \App\Models\AuraOrder::where('status', 'completed')->whereDate('created_at', $now->toDateString())->sum('total_amount'),
                    'this_week' => \App\Models\AuraOrder::where('status', 'completed')->whereBetween('created_at', [$now->startOfWeek(), $now->endOfWeek()])->sum('total_amount'),
                    'this_month' => \App\Models\AuraOrder::where('status', 'completed')->whereMonth('created_at', $now->month)->whereYear('created_at', $now->year)->sum('total_amount'),
                    'by_source' => [
                        'courses' => \DB::table('course_user')->sum('price'),
                        'subscriptions' => \App\Models\Subscription::active()->sum('price'),
                        'marketplace' => \App\Models\AuraOrder::where('status', 'completed')->sum('total_amount'),
                    ],
                ]
            ]);
        });
    });
    
    // Charts API
    Route::prefix('charts')->group(function () {
        Route::get('/user-registrations', function (Request $request) {
            $period = $request->get('period', '30days');
            $days = $period === '7days' ? 7 : ($period === '30days' ? 30 : 365);
            
            $data = [];
            for ($i = $days - 1; $i >= 0; $i--) {
                $date = now()->subDays($i);
                $data[] = [
                    'date' => $date->format('Y-m-d'),
                    'count' => \App\Models\User::whereDate('created_at', $date->toDateString())->count(),
                ];
            }
            
            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        });
        
        Route::get('/course-enrollments', function (Request $request) {
            $period = $request->get('period', '30days');
            $days = $period === '7days' ? 7 : ($period === '30days' ? 30 : 365);
            
            $data = [];
            for ($i = $days - 1; $i >= 0; $i--) {
                $date = now()->subDays($i);
                $data[] = [
                    'date' => $date->format('Y-m-d'),
                    'count' => \DB::table('course_user')->whereDate('created_at', $date->toDateString())->count(),
                ];
            }
            
            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        });
        
        Route::get('/revenue', function (Request $request) {
            $period = $request->get('period', '30days');
            $days = $period === '7days' ? 7 : ($period === '30days' ? 30 : 365);
            
            $data = [];
            for ($i = $days - 1; $i >= 0; $i--) {
                $date = now()->subDays($i);
                $revenue = \App\Models\AuraOrder::where('status', 'completed')
                    ->whereDate('created_at', $date->toDateString())
                    ->sum('total_amount');
                $data[] = [
                    'date' => $date->format('Y-m-d'),
                    'revenue' => $revenue,
                ];
            }
            
            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        });
        
        Route::get('/subscriptions', function (Request $request) {
            $period = $request->get('period', '30days');
            $days = $period === '7days' ? 7 : ($period === '30days' ? 30 : 365);
            
            $data = [];
            for ($i = $days - 1; $i >= 0; $i--) {
                $date = now()->subDays($i);
                $data[] = [
                    'date' => $date->format('Y-m-d'),
                    'new' => \App\Models\Subscription::whereDate('created_at', $date->toDateString())->count(),
                    'canceled' => \App\Models\Subscription::whereDate('canceled_at', $date->toDateString())->count(),
                ];
            }
            
            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        });
    });
    
    // Search API
    Route::prefix('search')->group(function () {
        Route::get('/users', function (Request $request) {
            $query = $request->get('q');
            $users = \App\Models\User::where('name', 'like', '%' . $query . '%')
                ->orWhere('email', 'like', '%' . $query . '%')
                ->take(10)
                ->get(['id', 'name', 'email', 'role', 'status']);
            
            return response()->json([
                'success' => true,
                'data' => $users,
            ]);
        });
        
        Route::get('/courses', function (Request $request) {
            $query = $request->get('q');
            $courses = \App\Models\Course::where('title', 'like', '%' . $query . '%')
                ->orWhere('description', 'like', '%' . $query . '%')
                ->take(10)
                ->get(['id', 'title', 'status', 'instructor_id']);
            
            return response()->json([
                'success' => true,
                'data' => $courses,
            ]);
        });
        
        Route::get('/orders', function (Request $request) {
            $query = $request->get('q');
            $orders = \App\Models\AuraOrder::where('order_number', 'like', '%' . $query . '%')
                ->orWhereHas('user', function ($q) use ($query) {
                    $q->where('name', 'like', '%' . $query . '%')
                      ->orWhere('email', 'like', '%' . $query . '%');
                })
                ->take(10)
                ->get(['id', 'order_number', 'total_amount', 'status', 'user_id']);
            
            return response()->json([
                'success' => true,
                'data' => $orders,
            ]);
        });
        
        Route::get('/subscriptions', function (Request $request) {
            $query = $request->get('q');
            $subscriptions = \App\Models\Subscription::whereHas('user', function ($q) use ($query) {
                    $q->where('name', 'like', '%' . $query . '%')
                      ->orWhere('email', 'like', '%' . $query . '%');
                })
                ->orWhereHas('plan', function ($q) use ($query) {
                    $q->where('name', 'like', '%' . $query . '%');
                })
                ->take(10)
                ->get(['id', 'user_id', 'plan_id', 'status', 'price']);
            
            return response()->json([
                'success' => true,
                'data' => $subscriptions,
            ]);
        });
    });
    
    // Notifications
    Route::prefix('notifications')->group(function () {
        Route::get('/', function () {
            $notifications = [
                [
                    'id' => 1,
                    'type' => 'warning',
                    'title' => 'High CPU Usage',
                    'message' => 'Server CPU usage is above 80%',
                    'timestamp' => now()->subMinutes(15),
                    'read' => false,
                ],
                [
                    'id' => 2,
                    'type' => 'info',
                    'title' => 'New User Registration',
                    'message' => '15 new users registered today',
                    'timestamp' => now()->subHours(2),
                    'read' => true,
                ],
                [
                    'id' => 3,
                    'type' => 'success',
                    'title' => 'Backup Completed',
                    'message' => 'System backup completed successfully',
                    'timestamp' => now()->subHours(6),
                    'read' => true,
                ],
            ];
            
            return response()->json([
                'success' => true,
                'data' => $notifications,
            ]);
        });
        
        Route::post('/{id}/read', function ($id) {
            return response()->json([
                'success' => true,
                'message' => 'Notification marked as read',
            ]);
        });
        
        Route::post('/read-all', function () {
            return response()->json([
                'success' => true,
                'message' => 'All notifications marked as read',
            ]);
        });
    });

    // Permissions Management
    Route::prefix('permissions')->group(function () {
        // Dashboard
        Route::get('/dashboard', [AdminPermissionsController::class, 'dashboard']);
        
        // Permissions
        Route::get('/', [AdminPermissionsController::class, 'getPermissions']);
        Route::post('/', [AdminPermissionsController::class, 'createPermission']);
        Route::put('/{id}', [AdminPermissionsController::class, 'updatePermission']);
        Route::delete('/{id}', [AdminPermissionsController::class, 'deletePermission']);
        Route::post('/bulk-action', [AdminPermissionsController::class, 'bulkPermissionAction']);
        
        // Roles
        Route::get('/roles', [AdminPermissionsController::class, 'getRoles']);
        Route::post('/roles', [AdminPermissionsController::class, 'createRole']);
        Route::put('/roles/{id}', [AdminPermissionsController::class, 'updateRole']);
        Route::delete('/roles/{id}', [AdminPermissionsController::class, 'deleteRole']);
        Route::post('/roles/bulk-action', [AdminPermissionsController::class, 'bulkRoleAction']);
        
        // Role-Permission Management
        Route::post('/roles/{roleId}/permissions', [AdminPermissionsController::class, 'assignPermissionsToRole']);
        Route::delete('/roles/{roleId}/permissions', [AdminPermissionsController::class, 'removePermissionsFromRole']);
        
        // User-Permission Management
        Route::get('/users', [AdminPermissionsController::class, 'getUsersWithPermissions']);
        Route::post('/users/{userId}/roles', [AdminPermissionsController::class, 'assignRoleToUser']);
        Route::delete('/users/{userId}/roles', [AdminPermissionsController::class, 'removeRoleFromUser']);
        Route::post('/users/{userId}/permissions', [AdminPermissionsController::class, 'assignPermissionToUser']);
        Route::delete('/users/{userId}/permissions', [AdminPermissionsController::class, 'removePermissionFromUser']);
        Route::get('/users/{userId}/check', [AdminPermissionsController::class, 'checkUserPermissions']);
        
        // Options
        Route::get('/options/permissions', [AdminPermissionsController::class, 'getPermissionOptions']);
        Route::get('/options/roles', [AdminPermissionsController::class, 'getRoleOptions']);
        
        // System Generation
        Route::post('/generate-system', [AdminPermissionsController::class, 'generateSystemPermissions']);
    });

    // Entities Management
    Route::prefix('entities')->group(function () {
        // Dashboard
        Route::get('/dashboard', [AdminEntitiesController::class, 'dashboard']);
        
        // Entity Data
        Route::get('/{entity}/data', [AdminEntitiesController::class, 'getEntityData']);
        Route::get('/{entity}/stats', [AdminEntitiesController::class, 'getEntityStats']);
        
        // Export Data
        Route::post('/{entity}/export', [AdminEntitiesController::class, 'exportEntityData']);
        Route::get('/download/{filename}', [AdminEntitiesController::class, 'downloadExport'])->name('admin.entities.download');
        
        // User Management
        Route::post('/{entity}/users', [AdminEntitiesController::class, 'addUserToEntity']);
    });
});
