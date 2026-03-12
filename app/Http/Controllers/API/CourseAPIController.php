<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\CourseEnrollRequest;
use App\Services\CourseService;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class CourseAPIController extends Controller
{
    private CourseService $courseService;
    private PaymentService $paymentService;

    public function __construct(CourseService $courseService, PaymentService $paymentService)
    {
        $this->courseService = $courseService;
        $this->paymentService = $paymentService;
    }

    /**
     * Display a listing of courses.
     */
    public function index(Request $request): JsonResponse
    {
        $filters = [
            'category_id' => $request->category_id,
            'instructor_id' => $request->instructor_id,
            'search' => $request->search,
            'is_published' => $request->is_published ?? true,
            'level' => $request->level,
        ];

        // Apply price filters
        if ($request->min_price) {
            $filters['min_price'] = $request->min_price;
        }

        if ($request->max_price) {
            $filters['max_price'] = $request->max_price;
        }

        $perPage = min($request->input('per_page', 15), 50);
        $courses = $this->courseService->getCourses($filters, $perPage);

        return response()->json([
            'success' => true,
            'data' => $courses->items(),
            'pagination' => [
                'current_page' => $courses->currentPage(),
                'last_page' => $courses->lastPage(),
                'per_page' => $courses->perPage(),
                'total' => $courses->total(),
            ],
        ]);
    }
        /**
     * Display the specified course.
     */
    public function show($slug): JsonResponse
    {
        $course = $this->courseService->getCourseBySlug($slug);
        
        if (!$course) {
            return response()->json([
                'success' => false,
                'message' => 'Course not found'
            ], 404);
        }

        // Transform course data for API response
        return response()->json([
            'id' => $course->id,
            'title' => $course->title,
            'description' => $course->description ?? '',
            'price' => (float) $course->price,
            'thumbnail' => $course->thumbnail,
            'is_free' => (bool) $course->is_free,
            'is_published' => (bool) $course->is_published,
            'level' => $course->level,
            'category' => $course->category,
            'instructor' => $course->instructor,
            'lessons' => $course->lessons->map(function ($lesson) {
                return [
                    'id' => $lesson->id,
                    'course_id' => $lesson->course_id,
                    'title' => $lesson->title,
                    'content' => $lesson->content,
                    'order' => (int) $lesson->sort_order,
                    'duration_minutes' => $lesson->duration_minutes,
                ];
            }),
            'enrollments_count' => $course->enrollments_count ?? $course->enrollments->count(),
            'created_at' => $course->created_at?->toISOString(),
            'updated_at' => $course->updated_at?->toISOString(),
            'stats' => [
                'total_enrollments' => $course->enrollments_count ?? $course->enrollments->count(),
                'total_lessons' => $course->lessons_count ?? $course->lessons->count(),
                'total_duration' => $course->lessons->sum('duration_minutes'),
                'recent_enrollments' => $course->enrollments->take(5),
            ]
        ]);
    }

    /**
     * Enroll user in course
     */
    public function enroll(CourseEnrollRequest $request, $courseId): JsonResponse
    {
        $user = $request->user();
        
        $paymentData = [
            'payment_method' => $request->payment_method ?? 'wallet',
            'payment_details' => $request->payment_details ?? [],
            'coupon_code' => $request->coupon_code,
        ];

        $result = $this->paymentService->processCoursePayment($user, $courseId, $paymentData);

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'enrollment' => $result['enrollment'] ?? null,
                'payment' => $result['payment'] ?? null,
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => $result['message'],
        ], 422);
    }

    /**
     * Get user's enrolled courses
     */
    public function myCourses(Request $request): JsonResponse
    {
        $user = $request->user();
        $filters = $request->only(['status']);

        $courses = $this->courseService->getUserCourses($user, $filters);

        return response()->json([
            'data' => $courses->map(function ($course) use ($user) {
                $enrollment = $user->enrollments()->where('course_id', $course->id)->first();
                return [
                    'id' => $course->id,
                    'title' => $course->title,
                    'slug' => $course->slug,
                    'description' => $course->description,
                    'thumbnail' => $course->thumbnail,
                    'progress_percentage' => $enrollment?->progress_percentage ?? 0,
                    'status' => $enrollment?->status ?? 'active',
                    'enrolled_at' => $enrollment?->enrolled_at,
                    'completed_at' => $enrollment?->completed_at,
                    'instructor' => $course->instructor,
                    'category' => $course->category,
                    'lessons_count' => $course->lessons->count(),
                    'completed_lessons' => $course->lessons->filter(function ($lesson) use ($user) {
                        return $lesson->isCompletedByUser($user->id);
                    })->count(),
                ];
            }),
            'pagination' => [
                'current_page' => 1,
                'last_page' => 1,
                'per_page' => $courses->count(),
                'total' => $courses->count(),
            ],
        ]);
    }
}
