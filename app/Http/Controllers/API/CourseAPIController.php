<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Enrollment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;

class CourseAPIController extends Controller
{
    /**
     * Display a listing of courses.
     */
    public function index(Request $request)
    {
        $query = Course::with(['instructor:id,name,email', 'category:id,name'])
            ->withCount(['enrollments', 'lessons'])
            ->orderBy('created_at', 'desc');

        // Apply filters
        if ($request->category_id) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->instructor_id) {
            $query->where('instructor_id', $request->instructor_id);
        }

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('title', 'LIKE', '%' . $request->search . '%')
                  ->orWhere('description', 'LIKE', '%' . $request->search . '%');
            });
        }

        // Apply price filter
        if ($request->min_price) {
            $query->where('price', '>=', $request->min_price);
        }

        if ($request->max_price) {
            $query->where('price', '<=', $request->max_price);
        }

        // Pagination with optimized page size
        $perPage = min($request->input('per_page', 15), 50);
        $courses = $query->paginate($perPage);

        return response()->json([
            'data' => $courses->items(),
            'pagination' => [
                'current_page' => $courses->currentPage(),
                'last_page' => $courses->lastPage(),
                'per_page' => $courses->perPage(),
                'total' => $courses->total(),
                'has_more' => $courses->hasMorePages(),
            ]
        ]);
    }

    /**
     * Display the specified course.
     */
    public function show($slug)
    {
        // Use cache for course details
        $cacheKey = "course_show:{$slug}";
        
        $course = Cache::remember($cacheKey, 3600, function () use ($slug) {
            return Course::with([
                'instructor:id,name,email,bio',
                'category:id,name',
                'lessons' => function ($query) {
                    $query->select('id', 'course_id', 'title', 'content', 'order', 'duration_minutes')
                          ->orderBy('order');
                },
                'enrollments' => function ($query) {
                    $query->select('id', 'course_id', 'user_id', 'created_at')
                          ->with('user:id,name,email')
                          ->latest()
                          ->take(10);
                }
            ])->where('slug', $slug)
              ->firstOrFail();
        });

        return response()->json([
            'course' => $course,
            'stats' => [
                'total_enrollments' => $course->enrollments_count,
                'total_lessons' => $course->lessons_count,
                'total_duration' => $course->lessons->sum('duration_minutes'),
                'recent_enrollments' => $course->enrollments->take(5),
            ]
        ]);
    }

    /**
     * Enroll user in course
     */
    public function enroll(Request $request, $courseId)
    {
        $user = $request->user();
        
        $course = Course::findOrFail($courseId);

        // Check if already enrolled
        $existingEnrollment = Enrollment::where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->first();

        if ($existingEnrollment) {
            return response()->json([
                'message' => 'Already enrolled in this course',
                'enrollment' => $existingEnrollment
            ], 422);
        }

        // Create enrollment
        $enrollment = Enrollment::create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'organization_id' => $course->organization_id ?? 1, // Default to org 1 if null
            'status' => 'active',
            'progress_percentage' => 0.0,
            'enrolled_at' => now(),
        ]);

        return response()->json([
            'message' => 'Enrolled successfully',
            'enrollment' => $enrollment
        ]);
    }

    /**
     * Get user's enrolled courses
     */
    public function myCourses(Request $request)
    {
        $user = $request->user();

        // Try to get from cache first
        $enrollments = CacheService::cacheUserCourses($user->id);

        return response()->json([
    'data' => $enrollments->map(function ($enrollment) use ($user) {
        return [
            'id' => $enrollment->course->id,
            'title' => $enrollment->course->title,
            'slug' => $enrollment->course->slug,
            'description' => $enrollment->course->description,
            'thumbnail' => $enrollment->course->thumbnail,
            'progress_percentage' => $enrollment->progress_percentage,
            'status' => $enrollment->status,
            'enrolled_at' => $enrollment->enrolled_at,
            'completed_at' => $enrollment->completed_at,
            'instructor' => $enrollment->course->instructor,
            'category' => $enrollment->course->category,
            'lessons_count' => $enrollment->course->lessons->count(),
            'completed_lessons' => $enrollment->course->lessons->filter(function ($lesson) use ($user) {
                return $lesson->isCompletedByUser($user->id);
            })->count(),
        ];
    }),
    'pagination' => [
        'current_page' => $enrollments->currentPage(),
        'last_page' => $enrollments->lastPage(),
        'per_page' => $enrollments->perPage(),
        'total' => $enrollments->total(),
    ]
]);

    }
}
