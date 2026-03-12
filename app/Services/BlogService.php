<?php

namespace App\Services;

use App\Models\Blog;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class BlogService
{
    /**
     * Get paginated blogs with filters
     */
    public function getBlogs(array $filters = [], int $perPage = 12): LengthAwarePaginator
    {
        $query = Blog::with(['user', 'category', 'tags'])
            ->published()
            ->orderBy('is_featured', 'desc')
            ->orderBy('created_at', 'desc');

        // Apply filters
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', '%' . $search . '%')
                  ->orWhere('excerpt', 'like', '%' . $search . '%')
                  ->orWhere('content', 'like', '%' . $search . '%');
            });
        }

        if (!empty($filters['category'])) {
            $query->where('category_id', $filters['category']);
        }

        if (!empty($filters['tag'])) {
            $query->whereHas('tags', function ($q) use ($filters) {
                $q->where('name', $filters['tag']);
            });
        }

        if (!empty($filters['author'])) {
            $query->where('user_id', $filters['author']);
        }

        if (isset($filters['is_featured'])) {
            $query->where('is_featured', $filters['is_featured']);
        }

        return $query->paginate($perPage);
    }

    /**
     * Get blog details with relationships
     */
    public function getBlogDetails(int $blogId): ?Blog
    {
        return Blog::with(['user', 'category', 'tags'])->find($blogId);
    }

    /**
     * Create a new blog
     */
    public function createBlog(array $data, User $author): Blog
    {
        $blogData = array_merge($data, [
            'user_id' => $author->id,
            'is_published' => false,
            'is_featured' => false,
        ]);

        $blog = Blog::create($blogData);

        // Sync tags if provided
        if (!empty($data['tags'])) {
            $blog->tags()->sync($data['tags']);
        }

        return $blog;
    }

    /**
     * Update a blog
     */
    public function updateBlog(int $blogId, array $data): ?Blog
    {
        $blog = Blog::find($blogId);
        if (!$blog) {
            return null;
        }

        $blog->update($data);

        // Sync tags if provided
        if (isset($data['tags'])) {
            $blog->tags()->sync($data['tags']);
        }

        return $blog->fresh();
    }

    /**
     * Delete a blog
     */
    public function deleteBlog(int $blogId): bool
    {
        $blog = Blog::find($blogId);
        if (!$blog) {
            return false;
        }

        return $blog->delete();
    }

    /**
     * Toggle blog featured status
     */
    public function toggleFeatured(int $blogId): array
    {
        $blog = Blog::findOrFail($blogId);
        $blog->is_featured = !$blog->is_featured;
        $blog->save();

        return [
            'success' => true,
            'is_featured' => $blog->is_featured,
            'message' => $blog->is_featured ? 'Blog marked as featured' : 'Blog removed from featured',
        ];
    }

    /**
     * Toggle blog publish status
     */
    public function togglePublished(int $blogId): array
    {
        $blog = Blog::findOrFail($blogId);
        $blog->is_published = !$blog->is_published;
        $blog->save();

        return [
            'success' => true,
            'is_published' => $blog->is_published,
            'message' => $blog->is_published ? 'Blog published successfully' : 'Blog unpublished',
        ];
    }

    /**
     * Get user's blogs
     */
    public function getUserBlogs(User $user, array $filters = []): LengthAwarePaginator
    {
        $query = $user->blogs()->with(['category', 'tags'])
            ->orderBy('created_at', 'desc');

        // Apply filters
        if (!empty($filters['status'])) {
            if ($filters['status'] === 'published') {
                $query->where('is_published', true);
            } elseif ($filters['status'] === 'draft') {
                $query->where('is_published', false);
            }
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', '%' . $search . '%')
                  ->orWhere('excerpt', 'like', '%' . $search . '%')
                  ->orWhere('content', 'like', '%' . $search . '%');
            });
        }

        return $query->paginate(20);
    }

    /**
     * Get featured blogs
     */
    public function getFeaturedBlogs(int $limit = 5): Collection
    {
        return Blog::with(['user', 'category'])
            ->published()
            ->featured()
            ->orderBy('created_at', 'desc')
            ->take($limit)
            ->get();
    }

    /**
     * Get popular blogs (by view count)
     */
    public function getPopularBlogs(int $limit = 10): Collection
    {
        return Blog::with(['user', 'category'])
            ->published()
            ->orderBy('view_count', 'desc')
            ->take($limit)
            ->get();
    }

    /**
     * Increment blog view count
     */
    public function incrementViewCount(int $blogId): void
    {
        Blog::where('id', $blogId)->increment('view_count');
    }

    /**
     * Get recent blogs for admin
     */
    public function getRecentBlogs(int $limit = 20): Collection
    {
        return Blog::with(['user', 'category'])
            ->orderBy('created_at', 'desc')
            ->take($limit)
            ->get();
    }
}
