<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Course;

class CoursePolicy
{
    /**
     * Determine whether the user can view any courses.
     */
    public function viewAny(User $user): bool
    {
        return true; // All authenticated users can view courses
    }

    /**
     * Determine whether the user can view the course.
     */
    public function view(User $user, Course $course): bool
    {
        // Published courses can be viewed by anyone
        if ($course->is_published) {
            return true;
        }

        // Admin can view any course
        if ($user->isAdmin()) return true;

        // Instructor can view their own courses
        if ($course->instructor_id === $user->id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create courses.
     */
    public function create(User $user): bool
    {
        return $user->canCreateContent();
    }

    /**
     * Determine whether the user can update the course.
     */
    public function update(User $user, Course $course): bool
    {
        // Admin can update any course
        if ($user->isAdmin()) return true;

        // Instructor can update their own course
        if ($course->instructor_id === $user->id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the course.
     */
    public function delete(User $user, Course $course): bool
    {
        // Admin can delete any course
        if ($user->isAdmin()) return true;

        // Instructor can delete their own course
        if ($course->instructor_id === $user->id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage course media (thumbnail, preview video).
     */
    public function manageMedia(User $user, Course $course): bool
    {
        return $this->update($user, $course);
    }
}
