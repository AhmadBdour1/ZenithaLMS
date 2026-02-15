<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Ebook;

class EbookPolicy
{
    /**
     * Determine whether the user can view any ebooks.
     */
    public function viewAny(User $user): bool
    {
        return true; // All authenticated users can view ebooks
    }

    /**
     * Determine whether the user can view the ebook.
     */
    public function view(User $user, Ebook $ebook): bool
    {
        // Published ebooks can be viewed by anyone
        if ($ebook->is_published) {
            return true;
        }

        // Admin can view any ebook
        if ($user->isAdmin()) return true;

        // Author can view their own ebooks
        if ($ebook->author_id === $user->id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create ebooks.
     */
    public function create(User $user): bool
    {
        return $user->canCreateContent();
    }

    /**
     * Determine whether the user can update the ebook.
     */
    public function update(User $user, Ebook $ebook): bool
    {
        // Admin can update any ebook
        if ($user->isAdmin()) return true;

        // Author can update their own ebook
        if ($ebook->author_id === $user->id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the ebook.
     */
    public function delete(User $user, Ebook $ebook): bool
    {
        // Admin can delete any ebook
        if ($user->isAdmin()) return true;

        // Author can delete their own ebook
        if ($ebook->author_id === $user->id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can download the ebook.
     */
    public function download(User $user, Ebook $ebook): bool
    {
        // Free ebooks can be downloaded by anyone
        if ($ebook->is_free) {
            return true;
        }

        // Admin can download any ebook
        if ($user->isAdmin()) return true;

        // Author can download their own ebook
        if ($ebook->author_id === $user->id) {
            return true;
        }

        // Check if user has purchased access
        return $ebook->accesses()->where('user_id', $user->id)->exists();
    }

    /**
     * Determine whether the user can manage ebook media.
     */
    public function manageMedia(User $user, Ebook $ebook): bool
    {
        return $this->update($user, $ebook);
    }
}
