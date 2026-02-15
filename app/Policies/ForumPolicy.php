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
        return $user->hasRole(['admin', 'instructor', 'student']);
    }

    /**
     * Determine whether the user can update the forum.
     */
    public function update(User $user, Forum $forum): bool
    {
        // Admin can update any forum
        if ($user->isAdmin()) {
            return true;
        }

        // Instructors can update their own forums
        if ($user->isInstructor() && $forum->user_id === $user->id) {
            return true;
        }

        // Students can update their own forums
        if ($user->isStudent() && $forum->user_id === $user->id) {
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
        if ($user->isAdmin()) {
            return true;
        }

        // Instructors can delete their own forums
        if ($user->isInstructor() && $forum->user_id === $user->id) {
            return true;
        }

        // Students can delete their own forums (within time limit)
        if ($user->isStudent() && $forum->user_id === $user->id) {
            // Allow deletion within 24 hours
            return $forum->created_at->diffInHours(now()) < 24;
        }

        return false;
    }

    /**
     * Determine whether the user can reply to forums.
     */
    public function reply(User $user): bool
    {
        // Only authenticated users can reply
        if (!$user) {
            return false;
        }

        return $user->hasRole(['admin', 'instructor', 'student']);
    }
}
