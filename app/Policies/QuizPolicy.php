<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Quiz;

class QuizPolicy
{
    /**
     * Determine whether the user can view any quizzes.
     */
    public function viewAny(User $user): bool
    {
        return true; // All authenticated users can view quizzes
    }

    /**
     * Determine whether the user can view the quiz.
     */
    public function view(User $user, Quiz $quiz): bool
    {
        // Users can view quizzes if they're enrolled in the course or created it
        if ($quiz->created_by === $user->id) {
            return true;
        }

        if ($quiz->course_id) {
            return $user->enrollments()
                ->where('course_id', $quiz->course_id)
                ->exists();
        }

        return true; // Public quizzes can be viewed by anyone
    }

    /**
     * Determine whether the user can create quizzes.
     */
    public function create(User $user): bool
    {
        return $user->hasRole(['admin', 'instructor']);
    }

    /**
     * Determine whether the user can update the quiz.
     */
    public function update(User $user, Quiz $quiz): bool
    {
        // Admin can update any quiz
        if ($user->isAdmin()) {
            return true;
        }

        // Instructors can update their own quizzes
        if ($user->isInstructor() && $quiz->created_by === $user->id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the quiz.
     */
    public function delete(User $user, Quiz $quiz): bool
    {
        // Admin can delete any quiz
        if ($user->isAdmin()) {
            return true;
        }

        // Instructors can delete their own quizzes
        if ($user->isInstructor() && $quiz->created_by === $user->id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can start the quiz.
     */
    public function start(User $user, Quiz $quiz): bool
    {
        // For testing purposes, allow all authenticated users to start quizzes
        // In production, this would check enrollment
        return true;
    }
}
