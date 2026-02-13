<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\User;
use App\Models\Ebook;
use App\Models\Blog;
use App\Models\Quiz;
use App\Models\Forum;
use App\Models\VirtualClass;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ZenithaLmsApiController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    /**
     * Get API user profile
     */
    public function profile()
    {
        $user = Auth::user();
        
        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'avatar' => $user->avatar ?? null,
                    'role' => $user->role->name ?? 'user',
                    'created_at' => $user->created_at->format('Y-m-d H:i:s'),
                ],
                'stats' => [
                    'courses_enrolled' => $user->enrollments()->count(),
                    'courses_completed' => $user->enrollments()->where('status', 'completed')->count(),
                    'quizzes_taken' => $user->quizAttempts()->count(),
                    'certificates_earned' => $user->certificates()->count(),
                ],
            ],
        ]);
    }

    /**
     * Get user's courses
     */
    public function courses(Request $request)
    {
        $user = Auth::user();
        
        $query = $user->enrollments()->with(['course.instructor', 'course.category']);
        
        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        // Filter by category
        if ($request->filled('category_id')) {
            $query->whereHas('course', function ($q) use ($request) {
                $q->where('category_id', $request->category_id);
            });
        }
        
        // Search
        if ($request->filled('search')) {
            $query->whereHas('course', function ($q) use ($request) {
                $q->where('title', 'like', '%' . $request->search . '%')
                  ->orWhere('description', 'like', '%' . $request->search . '%');
            });
        }
        
        $enrollments = $query->paginate(10);
        
        return response()->json([
            'success' => true,
            'data' => $enrollments->map(function ($enrollment) {
                return [
                    'id' => $enrollment->course->id,
                    'title' => $enrollment->course->title,
                    'description' => $enrollment->course->description,
                    'thumbnail' => $enrollment->course->getThumbnailUrl(),
                    'instructor' => $enrollment->course->instructor->name,
                    'category' => $enrollment->course->category->name,
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
     * Get course details
     */
    public function course($id)
    {
        $user = Auth::user();
        
        $course = Course::with(['instructor', 'category', 'lessons'])
            ->where('is_published', true)
            ->findOrFail($id);
        
        // Check if user has access
        $enrollment = $user->enrollments()
            ->where('course_id', $course->id)
            ->first();
        
        if (!$enrollment) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have access to this course',
            ], 403);
        }
        
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
                    'duration' => $course->duration,
                    'level' => $course->level,
                    'language' => $course->language,
                    'created_at' => $course->created_at->format('Y-m-d H:i:s'),
                ],
                'enrollment' => [
                    'status' => $enrollment->status,
                    'progress' => $enrollment->progress_percentage,
                    'enrolled_at' => $enrollment->created_at->format('Y-m-d H:i:s'),
                    'completed_at' => $enrollment->completed_at?->format('Y-m-d H:i:s'),
                ],
                'lessons' => $course->lessons->map(function ($lesson) {
                    return [
                        'id' => $lesson->id,
                        'title' => $lesson->title,
                        'description' => $lesson->description,
                        'duration' => $lesson->duration,
                        'order' => $lesson->order,
                        'is_free' => $lesson->is_free,
                    ];
                }),
            ],
        ]);
    }

    /**
     * Get user's ebooks
     */
    public function ebooks(Request $request)
    {
        $user = Auth::user();
        
        $query = Ebook::with(['category', 'author']);
        
        // Filter by access
        if ($request->filled('access_type')) {
            if ($request->access_type === 'purchased') {
                $query->whereHas('accesses', function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                });
            } elseif ($request->access_type === 'favorites') {
                $query->whereHas('favorites', function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                });
            }
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
        
        $ebooks = $query->paginate(10);
        
        return response()->json([
            'success' => true,
            'data' => $ebooks->map(function ($ebook) {
                return [
                    'id' => $ebook->id,
                    'title' => $ebook->title,
                    'description' => $ebook->description,
                    'thumbnail' => $ebook->getThumbnailUrl(),
                    'author' => $ebook->author->name,
                    'category' => $ebook->category->name,
                    'price' => $ebook->price,
                    'is_free' => $ebook->is_free,
                    'rating' => $ebook->average_rating,
                    'downloads' => $ebook->download_count,
                    'created_at' => $ebook->created_at->format('Y-m-d H:i:s'),
                    'is_purchased' => $ebook->accesses()->where('user_id', Auth::id())->exists(),
                    'is_favorite' => $ebook->favorites()->where('user_id', Auth::id())->exists(),
                ];
            }),
            'pagination' => [
                'current_page' => $ebooks->currentPage(),
                'last_page' => $ebooks->lastPage(),
                'per_page' => $ebooks->perPage(),
                'total' => $ebooks->total(),
            ],
        ]);
    }

    /**
     * Get ebook details
     */
    public function ebook($id)
    {
        $user = Auth::user();
        
        $ebook = Ebook::with(['category', 'author'])->findOrFail($id);
        
        // Check if user has access
        $hasAccess = $ebook->is_free || 
                   $ebook->accesses()->where('user_id', $user->id)->exists();
        
        if (!$hasAccess) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have access to this ebook',
            ], 403);
        }
        
        return response()->json([
            'success' => true,
            'data' => [
                'ebook' => [
                    'id' => $ebook->id,
                    'title' => $ebook->title,
                    'description' => $ebook->description,
                    'thumbnail' => $ebook->getThumbnailUrl(),
                    'author' => $ebook->author->name,
                    'category' => $ebook->category->name,
                    'price' => $ebook->price,
                    'is_free' => $ebook->is_free,
                    'rating' => $ebook->average_rating,
                    'downloads' => $ebook->download_count,
                    'file_url' => $ebook->file_url,
                    'created_at' => $ebook->created_at->format('Y-m-d H:i:s'),
                ],
                'user_data' => [
                    'is_purchased' => $ebook->accesses()->where('user_id', $user->id)->exists(),
                    'is_favorite' => $ebook->favorites()->where('user_id', $user->id)->exists(),
                    'download_count' => $ebook->accesses()->where('user_id', $user->id)->sum('download_count'),
                ],
            ],
        ]);
    }

    /**
     * Get user's quizzes
     */
    public function quizzes(Request $request)
    {
        $user = Auth::user();
        
        $query = Quiz::with(['course', 'questions']);
        
        // Filter by status
        if ($request->filled('status')) {
            if ($request->status === 'completed') {
                $query->whereHas('attempts', function ($q) use ($user) {
                    $q->where('user_id', $user->id)->where('status', 'completed');
                });
            } elseif ($request->status === 'available') {
                $query->whereDoesntHave('attempts', function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                });
            }
        }
        
        // Filter by course
        if ($request->filled('course_id')) {
            $query->where('course_id', $request->course_id);
        }
        
        // Search
        if ($request->filled('search')) {
            $query->where('title', 'like', '%' . $request->search . '%')
                  ->orWhere('description', 'like', '%' . $request->search . '%');
        }
        
        $quizzes = $query->paginate(10);
        
        return response()->json([
            'success' => true,
            'data' => $quizzes->map(function ($quiz) use ($user) {
                $attempt = $quiz->attempts()->where('user_id', $user->id)->first();
                
                return [
                    'id' => $quiz->id,
                    'title' => $quiz->title,
                    'description' => $quiz->description,
                    'course' => $quiz->course->title,
                    'questions_count' => $quiz->questions->count(),
                    'duration' => $quiz->time_limit_minutes,
                    'passing_score' => $quiz->passing_score,
                    'max_attempts' => $quiz->max_attempts,
                    'created_at' => $quiz->created_at->format('Y-m-d H:i:s'),
                    'user_attempt' => $attempt ? [
                        'id' => $attempt->id,
                        'status' => $attempt->status,
                        'score' => $attempt->score,
                        'percentage' => $attempt->percentage,
                        'attempt_number' => $attempt->attempt_number,
                        'completed_at' => $attempt->completed_at?->format('Y-m-d H:i:s'),
                    ] : null,
                ];
            }),
            'pagination' => [
                'current_page' => $quizzes->currentPage(),
                'last_page' => $quizzes->lastPage(),
                'per_page' => $quizzes->perPage(),
                'total' => $quizzes->total(),
            ],
        ]);
    }

    /**
     * Get quiz details
     */
    public function quiz($id)
    {
        $user = Auth::user();
        
        $quiz = Quiz::with(['course', 'questions'])->findOrFail($id);
        
        // Check if user has access
        if (!$quiz->course) {
            return response()->json([
                'success' => false,
                'message' => 'Quiz not found',
            ], 404);
        }
        
        $enrollment = $user->enrollments()
            ->where('course_id', $quiz->course->id)
            ->first();
        
        if (!$enrollment) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have access to this quiz',
            ], 403);
        }
        
        return response()->json([
            'success' => true,
            'data' => [
                'quiz' => [
                    'id' => $quiz->id,
                    'title' => $quiz->title,
                    'description' => $quiz->description,
                    'course' => $quiz->course->title,
                    'questions' => $quiz->questions->map(function ($question) {
                        return [
                            'id' => $question->id,
                            'question_text' => $question->question_text,
                            'type' => $question->question_type,
                            'options' => $question->options,
                            'points' => $question->points,
                            'order' => $question->order,
                        ];
                    }),
                    'duration' => $quiz->time_limit_minutes,
                    'passing_score' => $quiz->passing_score,
                    'max_attempts' => $quiz->max_attempts,
                    'created_at' => $quiz->created_at->format('Y-m-d H:i:s'),
                ],
                'user_data' => [
                    'attempts_count' => $quiz->attempts()->where('user_id', $user->id)->count(),
                    'best_attempt' => $quiz->attempts()->where('user_id', $user->id)
                        ->orderBy('percentage', 'desc')
                        ->first(),
                ],
            ],
        ]);
    }

    /**
     * Get forum posts
     */
    public function forum(Request $request)
    {
        $query = Forum::with(['user', 'category', 'replies']);
        
        // Filter by category
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }
        
        // Search
        if ($request->filled('search')) {
            $query->where('title', 'like', '%' . $request->search . '%')
                  ->orWhere('content', 'like', '%' . $request->search . '%');
        }
        
        $posts = $query->orderBy('created_at', 'desc')->paginate(10);
        
        return response()->json([
            'success' => true,
            'data' => $posts->map(function ($post) {
                return [
                    'id' => $post->id,
                    'title' => $post->title,
                    'content' => $post->content,
                    'author' => $post->user->name,
                    'category' => $post->category->name,
                    'reply_count' => $post->reply_count,
                    'view_count' => $post->view_count,
                    'like_count' => $post->like_count,
                    'created_at' => $post->created_at->format('Y-m-d H:i:s'),
                    'is_liked' => $post->likes()->where('user_id', Auth::id())->exists(),
                ];
            }),
            'pagination' => [
                'current_page' => $posts->currentPage(),
                'last_page' => $posts->lastPage(),
                'per_page' => $posts->perPage(),
                'total' => $posts->total(),
            ],
        ]);
    }

    /**
     * Get forum post details
     */
    public function forumPost($id)
    {
        $post = Forum::with(['user', 'category', 'replies.user'])->findOrFail($id);
        
        return response()->json([
            'success' => true,
            'data' => [
                'post' => [
                    'id' => $post->id,
                    'title' => $post->title,
                    'content' => $post->content,
                    'author' => $post->user->name,
                    'category' => $post->category->name,
                    'reply_count' => $post->reply_count,
                    'view_count' => $post->view_count,
                    'like_count' => $post->like_count,
                    'created_at' => $post->created_at->format('Y-m-d H:i:s'),
                    'is_liked' => $post->likes()->where('user_id', Auth::id())->exists(),
                ],
                'replies' => $post->replies->map(function ($reply) {
                    return [
                        'id' => $reply->id,
                        'content' => $reply->content,
                        'author' => $reply->user->name,
                        'like_count' => $reply->like_count,
                        'created_at' => $reply->created_at->format('Y-m-d H:i:s'),
                        'is_liked' => $reply->likes()->where('user_id', Auth::id())->exists(),
                    ];
                }),
            ],
        ]);
    }

    /**
     * Get virtual classes
     */
    public function virtualClasses(Request $request)
    {
        $user = Auth::user();
        
        $query = VirtualClass::with(['instructor', 'course']);
        
        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        // Filter by course
        if ($request->filled('course_id')) {
            $query->where('course_id', $request->course_id);
        }
        
        // Search
        if ($request->filled('search')) {
            $query->where('title', 'like', '%' . $request->search . '%')
                  ->orWhere('description', 'like', '%' . $request->search . '%');
        }
        
        $classes = $query->orderBy('scheduled_at', 'desc')->paginate(10);
        
        return response()->json([
            'success' => true,
            'data' => $classes->map(function ($class) {
                $participant = $class->participants()->where('user_id', $user->id)->first();
                
                return [
                    'id' => $class->id,
                    'title' => $class->title,
                    'description' => $class->description,
                    'instructor' => $class->instructor->name,
                    'course' => $class->course->title,
                    'status' => $class->status,
                    'scheduled_at' => $class->scheduled_at->format('Y-m-d H:i:s'),
                    'duration' => $class->duration_minutes,
                    'max_participants' => $class->max_participants,
                    'current_participants' => $class->current_participants,
                    'platform' => $class->platform,
                    'user_participation' => $participant ? [
                        'status' => $participant->status,
                        'joined_at' => $participant->joined_at?->format('Y-m-d H:i:s'),
                        'attendance_duration' => $participant->attendance_duration_minutes,
                    ] : null,
                ];
            }),
            'pagination' => [
                'current_page' => $classes->currentPage(),
                'last_page' => $classes->lastPage(),
                'per_page' => $classes->perPage(),
                'total' => $classes->total(),
            ],
        ]);
    }

    /**
     * Get virtual class details
     */
    public function virtualClass($id)
    {
        $user = Auth::user();
        
        $class = VirtualClass::with(['instructor', 'course', 'participants.user'])->findOrFail($id);
        
        return response()->json([
            'success' => true,
            'data' => [
                'class' => [
                    'id' => $class->id,
                    'title' => $class->title,
                    'description' => $class->description,
                    'instructor' => $class->instructor->name,
                    'course' => $class->course->title,
                    'status' => $class->status,
                    'scheduled_at' => $class->scheduled_at->format('Y-m-d H:i:s'),
                    'duration' => $class->duration_minutes,
                    'max_participants' => $class->max_participants,
                    'current_participants' => $class->current_participants,
                    'platform' => $class->platform,
                    'meeting_id' => $class->meeting_id,
                    'password' => $class->password,
                    'created_at' => $class->created_at->format('Y-m-d H:i:s'),
                ],
                'user_participation' => $class->participants()->where('user_id', $user->id)->first(),
                'participants' => $class->participants->map(function ($participant) {
                    return [
                        'id' => $participant->id,
                        'user' => $participant->user->name,
                        'status' => $participant->status,
                        'joined_at' => $participant->joined_at?->format('Y-m-d H:i:s'),
                        'attendance_duration' => $participant->attendance_duration_minutes,
                    ];
                }),
            ],
        ]);
    }

    /**
     * Get user's certificates
     */
    public function certificates(Request $request)
    {
        $user = Auth::user();
        
        $query = $user->certificates()->with(['course', 'template']);
        
        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        // Filter by course
        if ($request->filled('course_id')) {
            $query->where('course_id', $request->course_id);
        }
        
        $certificates = $query->orderBy('created_at', 'desc')->paginate(10);
        
        return response()->json([
            'success' => true,
            'data' => $certificates->map(function ($certificate) {
                return [
                    'id' => $certificate->id,
                    'certificate_number' => $certificate->certificate_number,
                    'title' => $certificate->title,
                    'course' => $certificate->course->title,
                    'issued_at' => $certificate->issued_at->format('Y-m-d H:i:s'),
                    'verification_code' => $certificate->verification_code,
                    'status' => $certificate->status,
                    'view_count' => $certificate->view_count,
                    'download_count' => $certificate->download_count,
                    'created_at' => $certificate->created_at->format('Y-m-d H:i:s'),
                ];
            }),
            'pagination' => [
                'current_page' => $certificates->currentPage(),
                'last_page' => $certificates->lastPage(),
                'per_page' => $certificates->perPage(),
                'total' => $certificates->total(),
            ],
        ]);
    }

    /**
     * Get certificate details
     */
    public function certificate($id)
    {
        $user = Auth::user();
        
        $certificate = $user->certificates()->with(['course', 'template'])->findOrFail($id);
        
        return response()->json([
            'success' => true,
            'data' => [
                'certificate' => [
                    'id' => $certificate->id,
                    'certificate_number' => $certificate->certificate_number,
                    'title' => $certificate->title,
                    'description' => $certificate->description,
                    'course' => $certificate->course->title,
                    'instructor' => $certificate->course->instructor->name,
                    'issued_at' => $certificate->issued_at->format('Y-m-d H:i:s'),
                    'verification_code' => $certificate->verification_code,
                    'status' => $certificate->status,
                    'view_count' => $certificate->view_count,
                    'download_count' => $certificate->download_count,
                    'created_at' => $certificate->created_at->format('Y-m-d H:i:s'),
                ],
                'template' => $certificate->template ? [
                    'name' => $certificate->template->name,
                    'preview_image' => $certificate->template->preview_image,
                ] : null,
            ],
        ]);
    }

    /**
     * Get notifications
     */
    public function notifications(Request $request)
    {
        $user = Auth::user();
        
        $query = $user->notifications();
        
        // Filter by status
        if ($request->filled('status')) {
            if ($request->status === 'unread') {
                $query->where('is_read', false);
            } elseif ($request->status === 'read') {
                $query->where('is_read', true);
            }
        }
        
        // Filter by type
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        
        $notifications = $query->orderBy('created_at', 'desc')->paginate(20);
        
        return response()->json([
            'success' => true,
            'data' => $notifications->map(function ($notification) {
                return [
                    'id' => $notification->id,
                    'title' => $notification->title,
                    'message' => $notification->message,
                    'type' => $notification->type,
                    'channel' => $notification->channel,
                    'is_read' => $notification->is_read,
                    'read_at' => $notification->read_at?->format('Y-m-d H:i:s'),
                    'created_at' => $notification->created_at->format('Y-m-d H:i:s'),
                    'action_url' => $notification->notification_data['action_url'] ?? null,
                    'action_button' => $notification->notification_data['action_button'] ?? null,
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
     * Mark notification as read
     */
    public function markNotificationAsRead($id)
    {
        $user = Auth::user();
        
        $notification = $user->notifications()->findOrFail($id);
        
        if (!$notification->is_read) {
            $notification->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Notification marked as read',
        ]);
    }

    /**
     * Mark all notifications as read
     */
    public function markAllNotificationsAsRead()
    {
        $user = Auth::user();
        
        $user->notifications()->where('is_read', false)->update([
            'is_read' => true,
            'read_at' => now(),
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'All notifications marked as read',
        ]);
    }

    /**
     * Get dashboard stats
     */
    public function dashboard()
    {
        $user = Auth::user();
        
        return response()->json([
            'success' => true,
            'data' => [
                'stats' => [
                    'courses_enrolled' => $user->enrollments()->count(),
                    'courses_completed' => $user->enrollments()->where('status', 'completed')->count(),
                    'quizzes_taken' => $user->quizAttempts()->count(),
                    'certificates_earned' => $user->certificates()->count(),
                    'virtual_classes_attended' => $user->virtualClassParticipants()->count(),
                ],
                'recent_activity' => [
                    'recent_courses' => $user->enrollments()
                        ->with('course')
                        ->orderBy('created_at', 'desc')
                        ->take(3)
                        ->get()
                        ->map(function ($enrollment) {
                            return [
                                'id' => $enrollment->course->id,
                                'title' => $enrollment->course->title,
                                'thumbnail' => $enrollment->course->getThumbnailUrl(),
                                'progress' => $enrollment->progress_percentage,
                                'enrolled_at' => $enrollment->created_at->format('Y-m-d H:i:s'),
                            ];
                        }),
                    'recent_quizzes' => $user->quizAttempts()
                        ->with('quiz')
                        ->orderBy('created_at', 'desc')
                        ->take(3)
                        ->get()
                        ->map(function ($attempt) {
                            return [
                                'id' => $attempt->quiz->id,
                                'title' => $attempt->quiz->title,
                                'score' => $attempt->score,
                                'percentage' => $attempt->percentage,
                                'completed_at' => $attempt->completed_at->format('Y-m-d H:i:s'),
                            ];
                        }),
                    'upcoming_classes' => $user->virtualClassParticipants()
                        ->with('virtualClass')
                        ->whereHas('virtualClass', function ($q) {
                            $q->where('scheduled_at', '>', now());
                        })
                        ->orderBy('created_at', 'desc')
                        ->take(3)
                        ->get()
                        ->map(function ($participant) {
                            return [
                                'id' => $participant->virtualClass->id,
                                'title' => $participant->virtualClass->title,
                                'scheduled_at' => $participant->virtualClass->scheduled_at->format('Y-m-d H:i:s'),
                                'duration' => $participant->virtualClass->duration_minutes,
                            ];
                        }),
                ],
            ],
        ]);
    }

    /**
     * Search across all content
     */
    public function search(Request $request)
    {
        $query = $request->get('q');
        $type = $request->get('type', 'all');
        
        if (!$query) {
            return response()->json([
                'success' => false,
                'message' => 'Search query is required',
            ], 400);
        }
        
        $results = [];
        
        if ($type === 'all' || $type === 'courses') {
            $courses = Course::where('is_published', true)
                ->where('title', 'like', '%' . $query . '%')
                ->orWhere('description', 'like', '%' . $query . '%')
                ->take(5)
                ->get();
                
            $results['courses'] = $courses->map(function ($course) {
                return [
                    'id' => $course->id,
                    'title' => $course->title,
                    'description' => $course->description,
                    'thumbnail' => $course->getThumbnailUrl(),
                    'type' => 'course',
                ];
            });
        }
        
        if ($type === 'all' || $type === 'ebooks') {
            $ebooks = Ebook::where('title', 'like', '%' . $query . '%')
                ->orWhere('description', 'like', '%' . $query . '%')
                ->take(5)
                ->get();
                
            $results['ebooks'] = $ebooks->map(function ($ebook) {
                return [
                    'id' => $ebook->id,
                    'title' => $ebook->title,
                    'description' => $ebook->description,
                    'thumbnail' => $ebook->getThumbnailUrl(),
                    'type' => 'ebook',
                ];
            });
        }
        
        if ($type === 'all' || $type === 'quizzes') {
            $quizzes = Quiz::where('title', 'like', '%' . $query . '%')
                ->orWhere('description', 'like', '%' . $query . '%')
                ->take(5)
                ->get();
                
            $results['quizzes'] = $quizzes->map(function ($quiz) {
                return [
                    'id' => $quiz->id,
                    'title' => $quiz->title,
                    'description' => $quiz->description,
                    'type' => 'quiz',
                ];
            });
        }
        
        return response()->json([
            'success' => true,
            'data' => $results,
        ]);
    }
}
