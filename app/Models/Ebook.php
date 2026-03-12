<?php

namespace App\Models;

use App\Services\MediaService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ebook extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'author_id',
        'category_id',
        'title',
        'slug',
        'description',
        'thumbnail',
        'file_path',
        'file_type',
        'file_size',
        'price',
        'is_free',
        'is_downloadable',
        'status',
        'download_count',
        'metadata',
        'ai_tags',
    ];

    protected $casts = [
        'is_free' => 'boolean',
        'is_downloadable' => 'boolean',
        'download_count' => 'integer',
        'price' => 'decimal:2',
        'metadata' => 'array',
        'ai_tags' => 'array',
    ];

    /**
     * ZenithaLMS: Relationships
     */
    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function favorites()
    {
        return $this->belongsToMany(User::class, 'ebook_favorites');
    }

    public function accessRecords()
    {
        return $this->hasMany(EbookAccess::class);
    }

    /**
     * Get ebook accesses (alias for accessRecords)
     */
    public function accesses()
    {
        return $this->hasMany(EbookAccess::class);
    }

    public function reviews()
    {
        return $this->hasMany(EbookReview::class);
    }

    /**
     * ZenithaLMS: Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeFree($query)
    {
        return $query->where('is_free', true);
    }

    public function scopePaid($query)
    {
        return $query->where('is_free', false);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * ZenithaLMS: Methods
     */
    public function getThumbnailUrlAttribute()
    {
        $mediaService = app(MediaService::class);
        return $mediaService->publicUrl($this->thumbnail, '/images/course-placeholder.png');
    }

    public function getFileUrlAttribute()
    {
        $mediaService = app(MediaService::class);
        return $mediaService->publicUrl($this->file_path);
    }

    public function getFileSizeFormattedAttribute()
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    public function getAverageRatingAttribute()
    {
        return $this->reviews()->avg('rating') ?? 0;
    }

    public function getTotalReviewsCount()
    {
        return $this->reviews()->count();
    }

    public function isFavoritedBy($userId)
    {
        return $this->favorites()->where('user_id', $userId)->exists();
    }

    public function canBeAccessedBy($userId)
    {
        // Defensive guard: Check if access_records table exists
        if (!\Illuminate\Support\Facades\Schema::hasTable('ebook_access')) {
            return $this->is_free ?? false;
        }
        
        if ($this->is_free) {
            return true;
        }

        try {
            return $this->accessRecords()
                ->where('user_id', $userId)
                ->where('access_until', '>', now())
                ->exists();
        } catch (\Exception $e) {
            // If relationship fails, deny access
            return false;
        }
    }

    public function incrementDownloadCount()
    {
        $this->increment('download_count');
    }

    /**
     * ZenithaLMS: AI-Powered Methods
     */
    public function generateAiTags()
    {
        // ZenithaLMS AI tag generation based on content analysis
        $tags = [];
        
        // Extract keywords from title and description
        $content = strtolower($this->title . ' ' . $this->description);
        
        // Common educational keywords
        $keywords = ['programming', 'web development', 'mobile', 'design', 'business', 'marketing', 'data science', 'ai', 'machine learning'];
        
        foreach ($keywords as $keyword) {
            if (strpos($content, $keyword) !== false) {
                $tags[] = $keyword;
            }
        }
        
        $this->ai_tags = $tags;
        $this->save();
        
        return $tags;
    }

    public function getSimilarEbooks($limit = 5)
    {
        // ZenithaLMS AI-powered similarity based on tags and category
        return Ebook::where('category_id', $this->category_id)
            ->where('id', '!=', $this->id)
            ->where('status', 'active')
            ->limit($limit)
            ->get();
    }

    public function getRecommendedEbooks($userId, $limit = 5)
    {
        // ZenithaLMS AI-powered recommendations based on user behavior
        $user = User::find($userId);
        
        if (!$user) {
            return $this->getSimilarEbooks($limit);
        }
        
        // Get user's favorite categories
        $favoriteCategories = $user->favoriteEbooks()
            ->with('category')
            ->get()
            ->pluck('category.id')
            ->unique();
        
        return Ebook::whereIn('category_id', $favoriteCategories)
            ->where('id', '!=', $this->id)
            ->where('status', 'active')
            ->limit($limit)
            ->get();
    }
}
