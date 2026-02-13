<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Forum;

class ForumPolicy
{
    /**
     * Determine whether the user can view any forums.
     */
    public function viewAny(User $user): bool
    {
        return true; // All authenticated users can view forums
    }

    /**
     * Determine whether the user can view the forum.
     */
    public function view(User $user, Forum $forum): bool
    {
        return true; // All authenticated users can view forums
    }

    /**
     * Determine whether the user can create forums.
     */
    public function create(User $user): bool
    {
        return in_array($user->role, ['admin', 'instructor', 'student']);
    }

    /**
     * Determine whether the user can update the forum.
     */
    public function update(User $user, Forum $forum): bool
    {
        // Admin can update any forum
        if ($user->role === 'admin') {
            return true;
        }

        // Instructors can update their own forums
        if ($user->role === 'instructor' && $forum->user_id === $user->id) {
            return true;
        }

        // Students can update their own forums
        if ($user->role === 'student' && $forum->user_id === $user->id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the forum.
     */
    public function delete(User $user, Forum $forum): bool
    {
        // Admin can delete any forum
        if ($user->role === 'admin') {
            return true;
        }

        // Instructors can delete their own forums
        if ($user->role === 'instructor' && $forum->user_id === $user->id) {
            return true;
        }

        // Students can delete their own forums (within time limit)
        if ($user->role === 'student' && $forum->user_id === $user->id) {
            // Allow deletion within 24 hours
            return $forum->created_at->diffInHours(now()) < 24;
        }

        return false;
    }

    /**
     * Determine whether the user can reply to the forum.
     */
    public function reply(User $user, Forum $forum): bool
    {
        // Check if forum is locked
        if ($forum->is_locked) {
            return false;
        }

        return in_array($user->role, ['admin', 'instructor', 'student']);
    }
}
