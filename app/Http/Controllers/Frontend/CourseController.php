<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Lesson;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CourseController extends Controller
{
    /**
     * Display a listing of courses.
     */
    public function index()
    {
        try {
            $courses = Course::with(['category', 'instructor'])
                ->withCount(['lessons', 'enrollments'])
                ->where('is_published', true)
                ->orderBy('created_at', 'desc')
                ->paginate(12);

            // Get categories for filter dropdown
            $categories = \App\Models\Category::where('is_active', true)
                ->orderBy('name')
                ->get();

            return view('courses.index', compact('courses', 'categories'));
        } catch (\Exception $e) {
            // Log error and return empty view
            \Log::error('Error loading courses: ' . $e->getMessage());
            return view('courses.index', [
                'courses' => collect([]),
                'categories' => collect([])
            ]);
        }
    }

    /**
     * Display the specified course.
     */
    public function show($slug)
    {
        try {
            // Fetch course with eager loading
            $course = Course::with(['category', 'instructor'])
                ->withCount(['lessons', 'enrollments'])
                ->where('slug', $slug)
                ->firstOrFail();

            // Get lessons for display
            $lessons = $course->lessons()
                ->select('id', 'title', 'description', 'sort_order', 'duration_minutes', 'is_published')
                ->where('is_published', true)
                ->orderBy('sort_order')
                ->get();

            // Check if user is enrolled
            $isEnrolled = false;
            $user = Auth::user();
            if ($user) {
                $isEnrolled = $course->enrollments()
                    ->where('user_id', $user->id)
                    ->where('status', 'active')
                    ->exists();
            }

            return view('courses.show', compact('course', 'lessons', 'isEnrolled'));

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // Course not found - return friendly 404
            abort(404, 'Course not found');
        } catch (\Exception $e) {
            // Any other error - return 404 to avoid 500s
            abort(404, 'Course not available');
        }
    }
}
