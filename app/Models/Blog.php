<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Blog extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'category_id',
        'title',
        'slug',
        'excerpt',
        'content',
        'featured_image',
        'status',
        'is_featured',
        'view_count',
        'seo_data',
        'ai_summary',
    ];

    protected $casts = [
        'is_featured' => 'boolean',
        'view_count' => 'integer',
        'seo_data' => 'array',
        'ai_summary' => 'array',
    ];

    /**
     * ZenithaLMS: Relationships
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function comments()
    {
        return $this->hasMany(BlogComment::class);
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'blog_tags');
    }

    /**
     * ZenithaLMS: Scopes
     */
    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    /**
     * ZenithaLMS: Methods
     */
    public function getFeaturedImageUrlAttribute()
    {
        return $this->featured_image ? asset('storage/' . $this->featured_image) : null;
    }

    public function getReadingTimeAttribute()
    {
        // ZenithaLMS: Calculate reading time based on word count
        $wordCount = str_word_count(strip_tags($this->content));
        $readingTime = ceil($wordCount / 200); // Average reading speed: 200 words per minute
        
        return $readingTime . ' min read';
    }

    public function incrementViewCount()
    {
        $this->increment('view_count');
    }

    public function getExcerptAttribute($value)
    {
        return $value ?: $this->generateExcerpt();
    }

    private function generateExcerpt()
    {
        $content = strip_tags($this->content);
        return substr($content, 0, 150) . '...';
    }

    /**
     * ZenithaLMS: SEO Methods
     */
    public function getMetaTitleAttribute()
    {
        return $this->seo_data['meta_title'] ?? $this->title;
    }

    public function getMetaDescriptionAttribute()
    {
        return $this->seo_data['meta_description'] ?? $this->excerpt;
    }

    public function getMetaKeywordsAttribute()
    {
        return $this->seo_data['meta_keywords'] ?? '';
    }

    /**
     * ZenithaLMS: AI-Powered Methods
     */
    public function generateAiSummary()
    {
        // ZenithaLMS AI-powered content summary
        $content = strip_tags($this->content);
        $sentences = preg_split('/(?<=[.?!])\s+/', $content, -1, PREG_SPLIT_NO_EMPTY);
        
        // Get first 3 sentences as summary
        $summary = implode(' ', array_slice($sentences, 0, 3));
        
        $this->ai_summary = [
            'summary' => $summary,
            'word_count' => str_word_count($content),
            'reading_time' => $this->reading_time,
            'sentiment' => $this->analyzeSentiment($content),
            'key_topics' => $this->extractKeyTopics($content),
        ];
        
        $this->save();
        
        return $this->ai_summary;
    }

    private function analyzeSentiment($content)
    {
        // ZenithaLMS: Simple sentiment analysis
        $positiveWords = ['good', 'great', 'excellent', 'amazing', 'wonderful', 'fantastic'];
        $negativeWords = ['bad', 'terrible', 'awful', 'horrible', 'disappointing'];
        
        $words = str_word_count(strtolower($content));
        $positiveCount = 0;
        $negativeCount = 0;
        
        foreach ($positiveWords as $word) {
            $positiveCount += substr_count(strtolower($content), $word);
        }
        
        foreach ($negativeWords as $word) {
            $negativeCount += substr_count(strtolower($content), $word);
        }
        
        if ($positiveCount > $negativeCount) {
            return 'positive';
        } elseif ($negativeCount > $positiveCount) {
            return 'negative';
        }
        
        return 'neutral';
    }

    private function extractKeyTopics($content)
    {
        // ZenithaLMS: Extract key topics from content
        $topics = [];
        
        // Common educational topics
        $topicKeywords = [
            'programming' => ['programming', 'coding', 'software', 'development'],
            'design' => ['design', 'ui', 'ux', 'creative', 'art'],
            'business' => ['business', 'marketing', 'sales', 'finance'],
            'technology' => ['technology', 'tech', 'digital', 'innovation'],
        ];
        
        foreach ($topicKeywords as $topic => $keywords) {
            foreach ($keywords as $keyword) {
                if (strpos(strtolower($content), $keyword) !== false) {
                    $topics[] = $topic;
                    break;
                }
            }
        }
        
        return array_unique($topics);
    }

    public function getRelatedPosts($limit = 5)
    {
        // ZenithaLMS AI-powered related posts
        return Blog::where('category_id', $this->category_id)
            ->where('id', '!=', $this->id)
            ->where('status', 'published')
            ->limit($limit)
            ->get();
    }
}
