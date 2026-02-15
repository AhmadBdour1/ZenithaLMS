<?php

namespace App\Services;

use App\Models\Ebook;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class EbookService
{
    /**
     * Get paginated ebooks with filters
     */
    public function getEbooks(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $query = Ebook::with(['user', 'category'])
            ->where('is_published', true)
            ->orderBy('created_at', 'desc');

        // Apply filters
        if (!empty($filters['category'])) {
            $query->where('category_id', $filters['category']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', '%' . $search . '%')
                  ->orWhere('description', 'like', '%' . $search . '%')
                  ->orWhere('author_name', 'like', '%' . $search . '%');
            });
        }

        if (!empty($filters['author'])) {
            $query->where('author_id', $filters['author']);
        }

        if (isset($filters['is_free'])) {
            $query->where('is_free', $filters['is_free']);
        }

        return $query->paginate($perPage);
    }

    /**
     * Get ebook details with relationships
     */
    public function getEbookDetails(int $ebookId): ?Ebook
    {
        return Ebook::with(['category', 'author'])->find($ebookId);
    }

    /**
     * Create a new ebook
     */
    public function createEbook(array $data, User $author): Ebook
    {
        $ebookData = array_merge($data, [
            'author_id' => $author->id,
            'is_published' => false,
            'download_count' => 0,
        ]);

        return Ebook::create($ebookData);
    }

    /**
     * Update an ebook
     */
    public function updateEbook(int $ebookId, array $data): ?Ebook
    {
        $ebook = Ebook::find($ebookId);
        if (!$ebook) {
            return null;
        }

        $ebook->update($data);
        return $ebook->fresh();
    }

    /**
     * Delete an ebook
     */
    public function deleteEbook(int $ebookId): bool
    {
        $ebook = Ebook::find($ebookId);
        if (!$ebook) {
            return false;
        }

        return $ebook->delete();
    }

    /**
     * Check if user can access ebook
     */
    public function canUserAccessEbook(int $ebookId, User $user): bool
    {
        $ebook = Ebook::findOrFail($ebookId);

        // Free ebooks can be accessed by anyone
        if ($ebook->is_free) {
            return true;
        }

        // Admin can access any ebook
        if ($user->isAdmin()) {
            return true;
        }

        // Author can access their own ebook
        if ($ebook->author_id === $user->id) {
            return true;
        }

        // Check if user has purchased access
        return $ebook->accesses()->where('user_id', $user->id)->exists();
    }

    /**
     * Grant access to ebook for user
     */
    public function grantAccess(int $ebookId, User $user): array
    {
        $ebook = Ebook::findOrFail($ebookId);

        // Check if already has access
        if ($ebook->accesses()->where('user_id', $user->id)->exists()) {
            return [
                'success' => false,
                'message' => 'User already has access to this ebook',
            ];
        }

        // Create access record
        $access = $ebook->accesses()->create([
            'user_id' => $user->id,
            'download_count' => 0,
        ]);

        return [
            'success' => true,
            'message' => 'Access granted successfully',
            'access' => $access,
        ];
    }

    /**
     * Record ebook download
     */
    public function recordDownload(int $ebookId, User $user): array
    {
        $ebook = Ebook::findOrFail($ebookId);

        if (!$this->canUserAccessEbook($ebookId, $user)) {
            return [
                'success' => false,
                'message' => 'You do not have access to this ebook',
            ];
        }

        // Increment download count
        $ebook->increment('download_count');

        // Update user's download count
        $access = $ebook->accesses()->where('user_id', $user->id)->first();
        if ($access) {
            $access->increment('download_count');
        }

        return [
            'success' => true,
            'message' => 'Download recorded successfully',
            'download_count' => $ebook->download_count,
        ];
    }

    /**
     * Get user's ebooks
     */
    public function getUserEbooks(User $user, array $filters = []): Collection
    {
        $query = $user->ebookAccesses()->with('ebook');

        if (!empty($filters['is_free'])) {
            $query->whereHas('ebook', function ($q) use ($filters) {
                $q->where('is_free', $filters['is_free']);
            });
        }

        return $query->get()->pluck('ebook');
    }

    /**
     * Get author's ebooks
     */
    public function getAuthorEbooks(User $author, array $filters = []): Collection
    {
        $query = $author->authoredEbooks();

        if (isset($filters['is_published'])) {
            $query->where('is_published', $filters['is_published']);
        }

        return $query->get();
    }
}
