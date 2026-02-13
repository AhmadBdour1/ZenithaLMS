<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Category;
use App\Models\User;
use App\Models\Enrollment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Carbon\Carbon;

class CourseController extends Controller
{
    /**
     * Display a listing of courses.
     */
    public function index(Request $request)
    {
        $query = Course::with(['instructor', 'category', 'enrollments'])
            ->where('is_published', true)
            ->orderBy('created_at', 'desc');

        // Filter by category
        if ($request->category) {
            $query->where('category_id', $request->category);
        }

        // Filter by level
        if ($request->level) {
            $query->where('level', $request->level);
        }

        // Filter by price
        if ($request->price_type === 'free') {
            $query->where('is_free', true);
        } elseif ($request->price_type === 'paid') {
            $query->where('is_free', false);
        }

        // Search
        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('title', 'like', '%' . $request->search . '%')
                  ->orWhere('description', 'like', '%' . $request->search . '%')
                  ->orWhere('what_you_will_learn', 'like', '%' . $request->search . '%');
            });
        }

        $courses = $query->paginate(12);
        $categories = Category::where('is_active', true)->get();
        
        return view('courses.index', compact('courses', 'categories'));
    }

    /**
     * Display the specified course.
     */
    public function show($slug)
    {
        // Find course by slug
        $course = Course::with(['instructor', 'category', 'lessons', 'assessments'])
            ->where('slug', $slug)
            ->where('is_published', true)
            ->firstOrFail();

        // Get similar courses
        $similarCourses = Course::with(['instructor', 'category'])
            ->where('category_id', $course->category_id)
            ->where('id', '!=', $course->id)
            ->where('is_published', true)
            ->take(3)
            ->get();

        // Check if user is enrolled
        $isEnrolled = false;
        $userProgress = null;
        if (auth()->check()) {
            $enrollment = Enrollment::where('user_id', auth()->id())
                ->where('course_id', $course->id)
                ->first();
            $isEnrolled = $enrollment !== null;
            if ($isEnrolled) {
                $userProgress = $enrollment->progress;
            }
        }

        // Get course stats
        $stats = [
            'total_students' => $course->enrollments()->count(),
            'average_rating' => 0, // No rating system yet
            'total_reviews' => 0, // No review system yet
            'completion_rate' => $course->enrollments()->where('status', 'completed')->count() / max($course->enrollments()->count(), 1) * 100,
        ];

        return view('courses.show', compact('course', 'similarCourses', 'isEnrolled', 'userProgress', 'stats'));
    }

    /**
     * Show the form for creating a new course.
     */
    public function create()
    {
        $categories = Category::where('is_active', true)->get();
        $instructors = User::where('role_id', 2)->where('is_active', true)->get(); // Instructor role
        
        return view('courses.create', compact('categories', 'instructors'));
    }

    /**
     * Store a newly created course.
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'category_id' => 'required|exists:categories,id',
            'instructor_id' => 'required|exists:users,id',
            'level' => 'required|in:beginner,intermediate,advanced,expert',
            'language' => 'required|string|max:10',
            'duration' => 'required|integer|min:1',
            'price' => 'required|numeric|min:0',
            'thumbnail' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'preview_video' => 'nullable|string|max:255',
            'requirements' => 'nullable|string',
            'what_you_will_learn' => 'nullable|string',
            'target_audience' => 'nullable|string',
            'is_free' => 'boolean',
            'is_featured' => 'boolean',
        ]);

        $courseData = $request->except(['thumbnail', 'is_free', 'is_featured']);
        $courseData['slug'] = Str::slug($request->title);
        $courseData['is_free'] = $request->boolean('is_free');
        $courseData['is_featured'] = $request->boolean('is_featured');
        $courseData['is_published'] = false; // Draft by default
        $courseData['sort_order'] = 0;

        // Handle thumbnail upload
        if ($request->hasFile('thumbnail')) {
            $thumbnail = $request->file('thumbnail');
            $thumbnailPath = $thumbnail->store('courses/thumbnails', 'public');
            $courseData['thumbnail'] = $thumbnailPath;
        }

        $course = Course::create($courseData);

        return redirect()->route('courses.edit', $course)
            ->with('success', 'Course created successfully! Add your lessons and content.');
    }

    /**
     * Show the form for editing the specified course.
     */
    public function edit(Course $course)
    {
        $this->authorize('update', $course);
        
        $categories = Category::where('is_active', true)->get();
        $instructors = User::where('role_id', 2)->where('is_active', true)->get();
        
        return view('courses.edit', compact('course', 'categories', 'instructors'));
    }

    /**
     * Update the specified course.
     */
    public function update(Request $request, Course $course)
    {
        $this->authorize('update', $course);

        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'category_id' => 'required|exists:categories,id',
            'instructor_id' => 'required|exists:users,id',
            'level' => 'required|in:beginner,intermediate,advanced,expert',
            'language' => 'required|string|max:10',
            'duration' => 'required|integer|min:1',
            'price' => 'required|numeric|min:0',
            'thumbnail' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'preview_video' => 'nullable|string|max:255',
            'requirements' => 'nullable|string',
            'what_you_will_learn' => 'nullable|string',
            'target_audience' => 'nullable|string',
            'is_free' => 'boolean',
            'is_featured' => 'boolean',
            'is_published' => 'boolean',
        ]);

        $courseData = $request->except(['thumbnail']);
        $courseData['slug'] = Str::slug($request->title);
        $courseData['is_free'] = $request->boolean('is_free');
        $courseData['is_featured'] = $request->boolean('is_featured');

        // Handle thumbnail upload
        if ($request->hasFile('thumbnail')) {
            // Delete old thumbnail
            if ($course->thumbnail) {
                Storage::disk('public')->delete($course->thumbnail);
            }
            
            $thumbnail = $request->file('thumbnail');
            $thumbnailPath = $thumbnail->store('courses/thumbnails', 'public');
            $courseData['thumbnail'] = $thumbnailPath;
        }

        $course->update($courseData);

        return redirect()->route('courses.edit', $course)
            ->with('success', 'Course updated successfully!');
    }

    /**
     * Remove the specified course.
     */
    public function destroy(Course $course)
    {
        $this->authorize('delete', $course);

        // Delete thumbnail
        if ($course->thumbnail) {
            Storage::disk('public')->delete($course->thumbnail);
        }

        $course->delete();

        return redirect()->route('courses.index')
            ->with('success', 'Course deleted successfully!');
    }

    /**
     * Publish course
     */
    public function publish(Course $course)
    {
        $this->authorize('update', $course);

        // Validate course has minimum requirements
        if ($course->lessons()->count() < 1) {
            return redirect()->back()
                ->with('error', 'Course must have at least 1 lesson to be published.');
        }

        $course->update(['is_published' => true]);

        return redirect()->route('courses.show', $course->slug)
            ->with('success', 'Course published successfully!');
    }

    /**
     * Unpublish course
     */
    public function unpublish(Course $course)
    {
        $this->authorize('update', $course);

        $course->update(['is_published' => false]);

        return redirect()->route('courses.show', $course->slug)
            ->with('success', 'Course unpublished successfully!');
    }

    /**
     * Duplicate course
     */
    public function duplicate(Course $course)
    {
        $this->authorize('view', $course);

        $newCourse = $course->replicate();
        $newCourse->title = $course->title . ' (Copy)';
        $newCourse->slug = Str::slug($newCourse->title);
        $newCourse->is_published = false;
        $newCourse->is_featured = false;
        $newCourse->save();

        // Duplicate lessons
        foreach ($course->lessons as $lesson) {
            $newLesson = $lesson->replicate();
            $newLesson->course_id = $newCourse->id;
            $newLesson->save();
        }

        return redirect()->route('courses.edit', $newCourse)
            ->with('success', 'Course duplicated successfully!');
    }

    /**
     * API: Get course statistics
     */
    public function stats(Course $course)
    {
        $this->authorize('view', $course);

        $stats = [
            'total_lessons' => $course->lessons()->count(),
            'total_assessments' => $course->assessments()->count(),
            'total_enrollments' => $course->enrollments()->count(),
            'active_enrollments' => $course->enrollments()->where('status', 'active')->count(),
            'completed_enrollments' => $course->enrollments()->where('status', 'completed')->count(),
            'average_progress' => $course->enrollments()->avg('progress') ?? 0,
            'average_rating' => $course->enrollments()->avg('rating') ?? 0,
            'total_revenue' => $course->enrollments()->where('is_paid', true)->sum('amount_paid'),
            'completion_rate' => $course->enrollments()->where('status', 'completed')->count() / max($course->enrollments()->count(), 1) * 100,
        ];

        return response()->json($stats);
    }

    /**
     * API: Get course content for AI analysis
     */
    public function contentForAI(Course $course)
    {
        $this->authorize('view', $course);

        $content = [
            'course' => [
                'title' => $course->title,
                'description' => $course->description,
                'level' => $course->level,
                'duration' => $course->duration,
                'what_you_will_learn' => $course->what_you_will_learn,
                'requirements' => $course->requirements,
                'target_audience' => $course->target_audience,
            ],
            'lessons' => $course->lessons()->select('id', 'title', 'content', 'duration')->get(),
            'assessments' => $course->assessments()->select('id', 'title', 'questions', 'passing_score')->get(),
        ];

        return response()->json($content);
    }
}
