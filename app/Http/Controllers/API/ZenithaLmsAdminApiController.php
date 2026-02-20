<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\User;
use App\Models\Quiz;
use App\Models\Forum;
use App\Models\Blog;
use App\Models\Notification;
use App\Models\Assignment;
use App\Models\VirtualClass;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ZenithaLmsAdminApiController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
        $this->middleware('role:admin');
    }

    /**
     * Get dashboard statistics
     */
    public function dashboard()
    {
        $stats = [
            'users' => [
                'total' => User::count(),
                'active' => User::where('last_login_at', '>=', now()->subDays(30))->count(),
                'new' => User::where('created_at', '>=', now()->subDays(30))->count(),
            ],
            'courses' => [
                'total' => Course::count(),
                'published' => Course::where('is_published', true)->count(),
                'draft' => Course::where('is_published', false)->count(),
                'new' => Course::where('created_at', '>=', now()->subDays(30))->count(),
            ],
            'enrollments' => [
                'total' => \App\Models\Enrollment::count(),
                'active' => \App\Models\Enrollment::where('status', 'active')->count(),
                'completed' => \App\Models\Enrollment::where('status', 'completed')->count(),
                'new' => \App\Models\Enrollment::where('created_at', '>=', now()->subDays(30))->count(),
            ],
            'revenue' => [
                'total' => \App\Models\Payment::where('status', 'completed')->sum('amount'),
                'this_month' => \App\Models\Payment::where('status', 'completed')
                    ->where('created_at', '>=', now()->startOfMonth())->sum('amount'),
                'last_month' => \App\Models\Payment::where('status', 'completed')
                    ->where('created_at', '>=', now()->subMonth()->startOfMonth())
                    ->where('created_at', '<', now()->startOfMonth())->sum('amount'),
            ],
            'quizzes' => [
                'total' => Quiz::count(),
                'active' => Quiz::where('is_active', true)->count(),
                'attempts' => \App\Models\QuizAttempt::count(),
                'new' => Quiz::where('created_at', '>=', now()->subDays(30))->count(),
            ],
            'forum' => [
                'total' => Forum::count(),
                'active' => Forum::where('status', 'active')->count(),
                'posts' => Forum::count(),
                'replies' => \App\Models\ForumReply::count(),
            ],
            'virtual_classes' => [
                'total' => VirtualClass::count(),
                'scheduled' => VirtualClass::where('status', 'scheduled')->count(),
                'live' => VirtualClass::where('status', 'live')->count(),
                'completed' => VirtualClass::where('status', 'ended')->count(),
            ],
        ];
        
        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Get users list with filtering
     */
    public function users(Request $request)
    {
        $query = User::query();
        
        // Filter by role
        if ($request->filled('role')) {
            $query->whereHas('role', function ($q) use ($request) {
                $q->where('name', $request->role);
            });
        }
        
        // Filter by status
        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->where('last_login_at', '>=', now()->subDays(30));
            } elseif ($request->status === 'inactive') {
                $query->where('last_login_at', '<', now()->subDays(30));
            }
        }
        
        // Search
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%');
        }
        
        $users = $query->with(['role', 'enrollments.course'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);
        
        return response()->json([
            'success' => true,
            'data' => $users->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role_name ?? 'student',
                    'avatar' => $user->avatar_url,
                    'status' => $user->last_login_at && $user->last_login_at->diffInDays(now()) < 30 ? 'active' : 'inactive',
                    'created_at' => $user->created_at->format('Y-m-d H:i:s'),
                    'last_login_at' => $user->last_login_at?->format('Y-m-d H:i:s'),
                    'enrollments_count' => $user->enrollments()->count(),
                ];
            }),
            'pagination' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
            ],
        ]);
    }

    /**
     * Get user details
     */
    public function user($id)
    {
        $user = User::with(['role', 'enrollments.course', 'certificates.course'])
            ->findOrFail($id);
        
        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role_name ?? 'student',
                    'avatar' => $user->avatar_url,
                    'status' => $user->last_login_at && $user->last_login_at->diffInDays(now()) < 30 ? 'active' : 'inactive',
                    'created_at' => $user->created_at->format('Y-m-d H:i:s'),
                    'last_login_at' => $user->last_login_at?->format('Y-m-d H:i:s'),
                    'email_verified_at' => $user->email_verified_at?->format('Y-m-d H:i:s'),
                ],
                'enrollments' => $user->enrollments->map(function ($enrollment) {
                    return [
                        'id' => $enrollment->id,
                        'course' => [
                            'id' => $enrollment->course->id,
                            'title' => $enrollment->course->title,
                            'thumbnail' => $enrollment->course->getThumbnailUrl(),
                        ],
                        'status' => $enrollment->status,
                        'progress' => $enrollment->progress_percentage,
                        'enrolled_at' => $enrollment->created_at->format('Y-m-d H:i:s'),
                        'completed_at' => $enrollment->completed_at?->format('Y-m-d H:i:s'),
                    ];
                }),
                'quiz_attempts' => [], // TODO: Implement quiz attempts relationship
                'certificates' => $user->certificates->map(function ($certificate) {
                    return [
                        'id' => $certificate->id,
                        'certificate_number' => $certificate->certificate_number,
                        'title' => $certificate->title,
                        'course' => [
                            'id' => $certificate->course->id,
                            'title' => $certificate->course->title,
                        ],
                        'issued_at' => $certificate->issued_at->format('Y-m-d H:i:s'),
                    ];
                }),
            ],
        ]);
    }

    /**
     * Get courses list with filtering
     */
    public function courses(Request $request)
    {
        $query = Course::with(['instructor', 'category']);
        
        // Filter by status
        if ($request->filled('status')) {
            if ($request->status === 'published') {
                $query->where('is_published', true);
            } elseif ($request->status === 'draft') {
                $query->where('is_published', false);
            }
        }
        
        // Filter by instructor
        if ($request->filled('instructor_id')) {
            $query->where('instructor_id', $request->instructor_id);
        }
        
        // Filter by category
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }
        
        // Search
        if ($request->filled('search')) {
            $query->where('title', 'like', '%' . $request->search . '%')
                  ->orWhere('description', 'like', '%' . $request->search . '%');
        }
        
        $courses = $query->orderBy('created_at', 'desc')->paginate(20);
        
        return response()->json([
            'success' => true,
            'data' => $courses->map(function ($course) {
                return [
                    'id' => $course->id,
                    'title' => $course->title,
                    'description' => $course->description,
                    'thumbnail' => $course->getThumbnailUrl(),
                    'instructor' => $course->instructor->name,
                    'category' => $course->category->name,
                    'price' => $course->price,
                    'is_free' => $course->is_free,
                    'is_published' => $course->is_published,
                    'duration' => $course->duration,
                    'level' => $course->level,
                    'language' => $course->language,
                    'enrollments_count' => $course->enrollments()->count(),
                    'created_at' => $course->created_at->format('Y-m-d H:i:s'),
                ];
            }),
            'pagination' => [
                'current_page' => $courses->currentPage(),
                'last_page' => $courses->lastPage(),
                'per_page' => $courses->perPage(),
                'total' => $courses->total(),
            ],
        ]);
    }

    /**
     * Get course details
     */
    public function course($id)
    {
        $course = Course::with(['instructor', 'category', 'lessons', 'enrollments.user'])
            ->findOrFail($id);
        
        return response()->json([
            'success' => true,
            'data' => [
                'course' => [
                    'id' => $course->id,
                    'title' => $course->title,
                    'description' => $course->description,
                    'thumbnail' => $course->getThumbnailUrl(),
                    'instructor' => $course->instructor->name,
                    'category' => $course->category->name,
                    'price' => $course->price,
                    'is_free' => $course->is_free,
                    'is_published' => $course->is_published,
                    'duration' => $course->duration,
                    'level' => $course->level,
                    'language' => $course->language,
                    'created_at' => $course->created_at->format('Y-m-d H:i:s'),
                ],
                'lessons' => $course->lessons->map(function ($lesson) {
                    return [
                        'id' => $lesson->id,
                        'title' => $lesson->title,
                        'description' => $lesson->description,
                        'duration' => $lesson->duration,
                        'order' => $lesson->order,
                        'is_free' => $lesson->is_free,
                        'created_at' => $lesson->created_at->format('Y-m-d H:i:s'),
                    ];
                }),
                'enrollments' => $course->enrollments->map(function ($enrollment) {
                    return [
                        'id' => $enrollment->id,
                        'user' => [
                            'id' => $enrollment->user->id,
                            'name' => $enrollment->user->name,
                            'email' => $enrollment->user->email,
                        ],
                        'status' => $enrollment->status,
                        'progress' => $enrollment->progress_percentage,
                        'enrolled_at' => $enrollment->created_at->format('Y-m-d H:i:s'),
                        'completed_at' => $enrollment->completed_at?->format('Y-m-d H:i:s'),
                    ];
                }),
            ],
        ]);
    }

    /**
     * Get enrollments statistics
     */
    public function enrollments(Request $request)
    {
        $query = \App\Models\Enrollment::with(['user', 'course']);
        
        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        // Filter by course
        if ($request->filled('course_id')) {
            $query->where('course_id', $request->course_id);
        }
        
        // Filter by date range
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('created_at', [
                $request->start_date . ' 00:00:00',
                $request->end_date . ' 23:59:59'
            ]);
        }
        
        $enrollments = $query->orderBy('created_at', 'desc')->paginate(20);
        
        return response()->json([
            'success' => true,
            'data' => $enrollments->map(function ($enrollment) {
                return [
                    'id' => $enrollment->id,
                    'user' => [
                        'id' => $enrollment->user->id,
                        'name' => $enrollment->user->name,
                        'email' => $enrollment->user->email,
                    ],
                    'course' => [
                        'id' => $enrollment->course->id,
                        'title' => $enrollment->course->title,
                        'thumbnail' => $enrollment->course->getThumbnailUrl(),
                    ],
                    'status' => $enrollment->status,
                    'progress' => $enrollment->progress_percentage,
                    'enrolled_at' => $enrollment->created_at->format('Y-m-d H:i:s'),
                    'completed_at' => $enrollment->completed_at?->format('Y-m-d H:i:s'),
                ];
            }),
            'pagination' => [
                'current_page' => $enrollments->currentPage(),
                'last_page' => $enrollments->lastPage(),
                'per_page' => $enrollments->perPage(),
                'total' => $enrollments->total(),
            ],
        ]);
    }

    /**
     * Get payments list with filtering
     */
    public function payments(Request $request)
    {
        $query = \App\Models\Payment::with(['user', 'course', 'paymentGateway']);
        
        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        // Filter by type
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        
        // Filter by gateway
        if ($request->filled('gateway_id')) {
            $query->where('payment_gateway_id', $request->gateway_id);
        }
        
        // Filter by date range
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('created_at', [
                $request->start_date . ' 00:00:00',
                $request->end_date . ' 23:59:59'
            ]);
        }
        
        $payments = $query->orderBy('created_at', 'desc')->paginate(20);
        
        return response()->json([
            'success' => true,
            'data' => $payments->map(function ($payment) {
                return [
                    'id' => $payment->id,
                    'transaction_id' => $payment->transaction_id,
                    'user' => [
                        'id' => $payment->user->id,
                        'name' => $payment->user->name,
                        'email' => $payment->user->email,
                    ],
                    'type' => $payment->type,
                    'amount' => $payment->amount,
                    'currency' => $payment->currency,
                    'status' => $payment->status,
                    'gateway' => $payment->paymentGateway->name ?? 'Unknown',
                    'course' => $payment->course ? [
                        'id' => $payment->course->id,
                        'title' => $payment->course->title,
                        'thumbnail' => $payment->course->getThumbnailUrl(),
                    ] : null,
                    'created_at' => $payment->created_at->format('Y-m-d H:i:s'),
                    'completed_at' => $payment->completed_at?->format('Y-m-d H:i:s'),
                    'failed_at' => $payment->failed_at?->format('Y-m-d H:i:s'),
                ];
            }),
            'pagination' => [
                'current_page' => $payments->currentPage(),
                'last_page' => $payments->lastPage(),
                'per_page' => $payments->perPage(),
                'total' => $payments->total(),
            ],
        ]);
    }

    /**
     * Get payment details
     */
    public function payment($id)
    {
        $payment = \App\Models\Payment::with(['user', 'course', 'paymentGateway'])
            ->findOrFail($id);
        
        return response()->json([
            'success' => true,
            'data' => [
                'payment' => [
                    'id' => $payment->id,
                    'transaction_id' => $payment->transaction_id,
                    'user' => [
                        'id' => $payment->user->id,
                        'name' => $payment->user->name,
                        'email' => $payment->user->email,
                    ],
                    'type' => $payment->type,
                    'amount' => $payment->amount,
                    'currency' => $payment->currency,
                    'status' => $payment->status,
                    'gateway' => $payment->paymentGateway->name ?? 'Unknown',
                    'gateway_transaction_id' => $payment->gateway_transaction_id,
                    'gateway_response' => $payment->gateway_response,
                    'course' => $payment->course ? [
                        'id' => $payment->course->id,
                        'title' => $payment->course->title,
                        'thumbnail' => $payment->course->getThumbnailUrl(),
                        'price' => $payment->course->price,
                        'is_free' => $payment->course->is_free,
                    ] : null,
                    'payment_data' => $payment->payment_data,
                    'created_at' => $payment->created_at->format('Y-m-d H:i:s'),
                    'completed_at' => $payment->completed_at?->format('Y-m-d H:i:s'),
                    'failed_at' => $payment->failed_at?->format('Y-m-d H:i:s'),
                ],
            ],
        ]);
    }

    /**
     * Get notifications list with filtering
     */
    public function notifications(Request $request)
    {
        $query = Notification::query();
        
        // Filter by type
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        
        // Filter by channel
        if ($request->filled('channel')) {
            $query->where('channel', $request->channel);
        }
        
        // Filter by read status
        if ($request->filled('is_read')) {
            $query->where('is_read', $request->boolean('is_read'));
        }
        
        // Filter by date range
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('created_at', [
                $request->start_date . ' 00:00:00',
                $request->end_date . ' 23:59:59'
            ]);
        }
        
        $notifications = $query->orderBy('created_at', 'desc')->paginate(20);
        
        return response()->json([
            'success' => true,
            'data' => $notifications->map(function ($notification) {
                return [
                    'id' => $notification->id,
                    'user' => [
                        'id' => $notification->user->id,
                        'name' => $notification->user->name,
                        'email' => $notification->user->email,
                    ],
                    'title' => $notification->title,
                    'message' => $notification->message,
                    'type' => $notification->type,
                    'channel' => $notification->channel,
                    'is_read' => $notification->is_read,
                    'read_at' => $notification->read_at?->format('Y-m-d H:i:s'),
                    'created_at' => $notification->created_at->format('Y-m-d H:i:s'),
                    'notification_data' => $notification->notification_data,
                ];
            }),
            'pagination' => [
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
                'per_page' => $notifications->perPage(),
                'total' => $notifications->total(),
            ],
        ]);
    }

    /**
     * Send notification to users
     */
    public function sendNotification(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'type' => 'required|in:' . implode(',', array_keys(Notification::getTypes())),
            'channel' => 'required|in:' . implode(',', array_keys(Notification::getChannels())),
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id',
            'action_url' => 'nullable|url',
            'action_button' => 'nullable|string|max:50',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }
        
        $notifications = [];
        
        foreach ($request->user_ids as $userId) {
            $notification = Notification::create([
                'user_id' => $userId,
                'title' => $request->title,
                'message' => $request->message,
                'type' => $request->type,
                'channel' => $request->channel,
                'notification_data' => [
                    'action_url' => $request->action_url,
                    'action_button' => $request->action_button,
                ],
            ]);
            
            $notifications[] = [
                'id' => $notification->id,
                'user_id' => $userId,
                'title' => $notification->title,
                'message' => $notification->message,
                'type' => $notification->type,
                'channel' => $notification->channel,
                'created_at' => $notification->created_at->format('Y-m-d H:i:s'),
            ];
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Notifications sent successfully',
            'data' => $notifications,
        ]);
    }

    /**
     * Get system settings
     */
    public function settings()
    {
        $settings = [
            'site' => [
                'name' => config('app.name'),
                'url' => config('app.url'),
                'timezone' => config('app.timezone'),
                'locale' => config('app.locale'),
            ],
            'features' => [
                'multi_tenant' => true,
                'ai_features' => true,
                'virtual_reality' => true,
                'blockchain_certificates' => true,
                'real_time_notifications' => true,
                'advanced_analytics' => true,
            ],
            'limits' => [
                'max_users' => 1000,
                'max_courses' => 500,
                'max_storage' => '100GB',
                'max_bandwidth' => '1TB',
            ],
        ];
        
        return response()->json([
            'success' => true,
            'data' => $settings,
        ]);
    }

    /**
     * Update system settings
     */
    public function updateSettings(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'site_name' => 'required|string|max:255',
            'site_url' => 'required|url',
            'timezone' => 'required|string',
            'locale' => 'required|string',
            'max_users' => 'required|integer|min:1',
            'max_courses' => 'required|integer|min:1',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }
        
        // Update settings in .env file (simplified)
        $envContent = file_get_contents(base_path('.env'));
        
        $envContent = preg_replace('/^APP_NAME=.*/m', 'APP_NAME="' . $request->site_name . '"', $envContent);
        $envContent = preg_replace('/^APP_URL=.*/m', 'APP_URL="' . $request->site_url . '"', $envContent);
        $envContent = preg_replace('/^APP_TIMEZONE=.*/m', 'APP_TIMEZONE="' . $request->timezone . '"', $envContent);
        $envContent = preg_replace('/^APP_LOCALE=.*/m', 'APP_LOCALE="' . $request->locale . '"', $envContent);
        
        file_put_contents(base_path('.env'), $envContent);
        
        return response()->json([
            'success' => true,
            'message' => 'Settings updated successfully',
        ]);
    }

    /**
     * Get system logs
     */
    public function logs(Request $request)
    {
        $query = \App\Models\SystemLog::query();
        
        // Filter by level
        if ($request->filled('level')) {
            $query->where('level', $request->level);
        }
        
        // Filter by date range
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('created_at', [
                $request->start_date . ' 00:00:00',
                $request->end_date . ' 23:59:59'
            ]);
        }
        
        $logs = $query->orderBy('created_at', 'desc')->paginate(50);
        
        return response()->json([
            'success' => true,
            'data' => $logs->map(function ($log) {
                return [
                    'id' => $log->id,
                    'level' => $log->level,
                    'message' => $log->message,
                    'context' => $log->context,
                    'user' => $log->user ? [
                        'id' => $log->user->id,
                        'name' => $log->user->name,
                        'email' => $log->user->email,
                    ] : null,
                    'created_at' => $log->created_at->format('Y-m-d H:i:s'),
                ];
            }),
            'pagination' => [
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'per_page' => $logs->perPage(),
                'total' => $logs->total(),
            ],
        ]);
    }

    /**
     * Get analytics data
     */
    public function analytics(Request $request)
    {
        $startDate = $request->get('start_date', now()->subDays(30)->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));
        
        $analytics = [
            'users' => [
                'new_users' => User::whereBetween('created_at', [$startDate, $endDate])->count(),
                'active_users' => User::where('last_login_at', '>=', now()->subDays(30))->count(),
                'total_users' => User::count(),
                'user_growth' => $this->calculateGrowthRate('users', $startDate, $endDate),
            ],
            'courses' => [
                'new_courses' => Course::whereBetween('created_at', [$startDate, $endDate])->count(),
                'published_courses' => Course::where('is_published', true)->count(),
                'total_courses' => Course::count(),
                'course_growth' => $this->calculateGrowthRate('courses', $startDate, $endDate),
            ],
            'enrollments' => [
                'new_enrollments' => \App\Models\Enrollment::whereBetween('created_at', [$startDate, $endDate])->count(),
                'active_enrollments' => \App\Models\Enrollment::where('status', 'active')->count(),
                'completed_enrollments' => \App\Models\Enrollment::where('status', 'completed')->count(),
                'total_enrollments' => \App\Models\Enrollment::count(),
                'completion_rate' => $this->calculateCompletionRate(),
            ],
            'revenue' => [
                'total_revenue' => \App\Models\Payment::where('status', 'completed')->sum('amount'),
                'monthly_revenue' => \App\Models\Payment::where('status', 'completed')
                    ->where('created_at', '>=', now()->startOfMonth())->sum('amount'),
                'revenue_growth' => $this->calculateGrowthRate('payments', $startDate, $endDate),
            ],
            'engagement' => [
                'quiz_attempts' => \App\Models\QuizAttempt::whereBetween('created_at', [$startDate, $endDate])->count(),
                'forum_posts' => Forum::whereBetween('created_at', [$startDate, $endDate])->count(),
                'forum_replies' => \App\Models\ForumReply::whereBetween('created_at', [$startDate, $endDate])->count(),
                'virtual_classes' => VirtualClass::whereBetween('created_at', [$startDate, $endDate])->count(),
            ],
        ];
        
        return response()->json([
            'success' => true,
            'data' => $analytics,
        ]);
    }

    /**
     * Calculate growth rate
     */
    private function calculateGrowthRate($model, $startDate, $endDate)
    {
        $previousPeriod = \App\Models\User::whereBetween('created_at', [
            now()->subDays(60)->format('Y-m-d'),
            now()->subDays(31)->format('Y-m-d')
        ])->count();
        
        $currentPeriod = \App\Models\User::whereBetween('created_at', [$startDate, $endDate])->count();
        
        if ($previousPeriod === 0) {
            return $currentPeriod > 0 ? 100 : 0;
        }
        
        return round((($currentPeriod - $previousPeriod) / $previousPeriod) * 100, 2);
    }

    /**
     * Calculate completion rate
     */
    private function calculateCompletionRate()
    {
        $total = \App\Models\Enrollment::count();
        $completed = \App\Models\Enrollment::where('status', 'completed')->count();
        
        if ($total === 0) {
            return 0;
        }
        
        return round(($completed / $total) * 100, 2);
    }
}
