<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Blog;

class BlogPolicy
{
    /**
     * Determine whether the user can view any blogs.
     */
    public function viewAny(User $user): bool
    {
        return true; // All authenticated users can view blogs
    }

    /**
     * Determine whether the user can view the blog.
     */
    public function view(User $user, Blog $blog): bool
    {
        // Published blogs can be viewed by anyone
        if ($blog->is_published) {
            return true;
        }

        // Admin can view any blog
        if ($user->isAdmin()) return true;

        // Author can view their own blogs
        if ($blog->user_id === $user->id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create blogs.
     */
    public function create(User $user): bool
    {
        return $user->canCreateContent();
    }

    /**
     * Determine whether the user can update the blog.
     */
    public function update(User $user, Blog $blog): bool
    {
        // Admin can update any blog
        if ($user->isAdmin()) return true;

        // Author can update their own blog
        if ($blog->user_id === $user->id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the blog.
     */
    public function delete(User $user, Blog $blog): bool
    {
        // Admin can delete any blog
        if ($user->isAdmin()) return true;

        // Author can delete their own blog
        if ($blog->user_id === $user->id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage blog images (featured image).
     */
    public function manageImages(User $user, Blog $blog): bool
    {
        return $this->update($user, $blog);
    }

    /**
     * Determine whether the user can manage blogs (admin function).
     */
    public function manage_blogs(User $user): bool
    {
        return $user->isAdminLevel();
    }
}
