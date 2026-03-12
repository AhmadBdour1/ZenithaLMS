<?php

namespace App\Services;

use App\Models\Course;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class CourseService
{
    /**
     * Get paginated courses with filters
     */
    public function getCourses(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Course::with(['instructor:id,name,email', 'category:id,name'])
            ->withCount(['enrollments', 'lessons'])
            ->orderBy('created_at', 'desc');

        // Apply filters
        if (!empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (!empty($filters['instructor_id'])) {
            $query->where('instructor_id', $filters['instructor_id']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', '%' . $search . '%')
                  ->orWhere('description', 'like', '%' . $search . '%');
            });
        }

        if (isset($filters['is_published'])) {
            $query->where('is_published', $filters['is_published']);
        }

        if (!empty($filters['level'])) {
            $query->where('level', $filters['level']);
        }

        return $query->paginate($perPage);
    }

    /**
     * Get course details with relationships
     */
    public function getCourseDetails(int $courseId): ?Course
    {
        return Course::with([
            'instructor:id,name,email,bio',
            'category:id,name',
            'lessons' => function ($query) {
                $query->select('id', 'course_id', 'title', 'content', 'sort_order', 'duration_minutes')
                      ->orderBy('sort_order');
            },
            'enrollments' => function ($query) {
                $query->select('id', 'course_id', 'user_id', 'created_at')
                      ->with('user:id,name,email')
                      ->latest()
                      ->take(10);
            },
        ])->find($courseId);
    }

    /**
     * Get course by slug
     */
    public function getCourseBySlug(string $slug): ?Course
    {
        return Course::with([
            'instructor:id,name,email,bio',
            'category:id,name',
            'lessons' => function ($query) {
                $query->select('id', 'course_id', 'title', 'content', 'sort_order', 'duration_minutes')
                      ->orderBy('sort_order');
            },
            'enrollments' => function ($query) {
                $query->select('id', 'course_id', 'user_id', 'created_at')
                      ->with('user:id,name,email')
                      ->latest()
                      ->take(10);
            },
        ])->where('slug', $slug)->first();
    }

    /**
     * Create a new course
     */
    public function createCourse(array $data, User $instructor): Course
    {
        $courseData = array_merge($data, [
            'instructor_id' => $instructor->id,
            'is_published' => false,
        ]);

        return Course::create($courseData);
    }

    /**
     * Update a course
     */
    public function updateCourse(int $courseId, array $data): ?Course
    {
        $course = Course::find($courseId);
        if (!$course) {
            return null;
        }

        $course->update($data);
        return $course->fresh();
    }

    /**
     * Delete a course
     */
    public function deleteCourse(int $courseId): bool
    {
        $course = Course::find($courseId);
        if (!$course) {
            return false;
        }

        return $course->delete();
    }

    /**
     * Enroll user in course
     */
    public function enrollUser(int $courseId, User $user): array
    {
        $course = Course::findOrFail($courseId);

        // Check if already enrolled
        if ($user->enrollments()->where('course_id', $courseId)->exists()) {
            return [
                'success' => false,
                'message' => 'Already enrolled in this course',
            ];
        }

        // Create enrollment
        $enrollment = $user->enrollments()->create([
            'course_id' => $courseId,
            'organization_id' => $course->organization_id ?? $user->organization_id ?? 1,
            'status' => 'active',
            'progress_percentage' => 0,
            'enrolled_at' => now(),
        ]);

        return [
            'success' => true,
            'message' => 'Successfully enrolled in course',
            'enrollment' => $enrollment,
        ];
    }

    /**
     * Get user's enrolled courses
     */
    public function getUserCourses(User $user, array $filters = []): \Illuminate\Support\Collection
    {
        $query = $user->enrollments()->with('course');

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->get()->pluck('course');
    }

    /**
     * Get instructor's courses
     */
    public function getInstructorCourses(User $instructor, array $filters = []): Collection
    {
        $query = $instructor->courses();

        if (isset($filters['is_published'])) {
            $query->where('is_published', $filters['is_published']);
        }

        return $query->get();
    }
}
