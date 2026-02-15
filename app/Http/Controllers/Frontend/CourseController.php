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
