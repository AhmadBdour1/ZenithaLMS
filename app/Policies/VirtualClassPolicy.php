<?php

namespace App\Policies;

use App\Models\User;
use App\Models\VirtualClass;

class VirtualClassPolicy
{
    /**
     * Determine whether the user can view any virtual classes.
     */
    public function viewAny(User $user): bool
    {
        return true; // All authenticated users can view virtual classes
    }

    /**
     * Determine whether the user can view the virtual class.
     */
    public function view(User $user, VirtualClass $class): bool
    {
        // Admin can view any class
        if ($user->role === 'admin') {
            return true;
        }

        // Instructor can view their own classes
        if ($user->role === 'instructor' && $class->instructor_id === $user->id) {
            return true;
        }

        // Students can view classes they're enrolled in
        if ($user->role === 'student' && $class->course_id) {
            return $user->enrollments()
                ->where('course_id', $class->course_id)
                ->exists();
        }

        return false;
    }

    /**
     * Determine whether the user can create virtual classes.
     */
    public function create(User $user): bool
    {
        return in_array($user->role, ['admin', 'instructor']);
    }

    /**
     * Determine whether the user can update the virtual class.
     */
    public function update(User $user, VirtualClass $class): bool
    {
        // Admin can update any class
        if ($user->role === 'admin') {
            return true;
        }

        // Instructors can update their own classes
        if ($user->role === 'instructor' && $class->instructor_id === $user->id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the virtual class.
     */
    public function delete(User $user, VirtualClass $class): bool
    {
        // Admin can delete any class
        if ($user->role === 'admin') {
            return true;
        }

        // Instructors can delete their own classes
        if ($user->role === 'instructor' && $class->instructor_id === $user->id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can join the virtual class.
     */
    public function join(User $user, VirtualClass $class): bool
    {
        // Admin can join any class
        if ($user->role === 'admin') {
            return true;
        }

        // Instructor can join their own classes
        if ($user->role === 'instructor' && $class->instructor_id === $user->id) {
            return true;
        }

        // Students can join classes they're enrolled in
        if ($user->role === 'student' && $class->course_id) {
            return $user->enrollments()
                ->where('course_id', $class->course_id)
                ->exists();
        }

        return false;
    }
}
