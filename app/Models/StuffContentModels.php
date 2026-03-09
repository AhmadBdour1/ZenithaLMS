<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StuffUpdate extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'stuff_id',
        'title',
        'description',
        'version',
        'type', // 'feature', 'bug_fix', 'improvement', 'security', 'documentation'
        'status', // 'draft', 'published', 'scheduled'
        'published_at',
        'scheduled_at',
        'is_major',
        'is_critical',
        'download_url',
        'file_size',
        'checksum',
        'requirements',
        'compatibility',
        'breaking_changes',
        'migration_notes',
        'changelog',
        'release_notes',
        'metadata',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'scheduled_at' => 'datetime',
        'is_major' => 'boolean',
        'is_critical' => 'boolean',
        'file_size' => 'integer',
        'requirements' => 'array',
        'compatibility' => 'array',
        'breaking_changes' => 'array',
        'metadata' => 'array',
    ];

    // Relationships
    public function stuff()
    {
        return $this->belongsTo(Stuff::class);
    }

    // Scopes
    public function scopePublished($query)
    {
        return $query->where('status', 'published')
                    ->where('published_at', '<=', now());
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeScheduled($query)
    {
        return $query->where('status', 'scheduled');
    }

    public function scopeMajor($query)
    {
        return $query->where('is_major', true);
    }

    public function scopeCritical($query)
    {
        return $query->where('is_critical', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeLatest($query)
    {
        return $query->orderBy('published_at', 'desc');
    }

    // Methods
    public function publish()
    {
        $this->update([
            'status' => 'published',
            'published_at' => now(),
        ]);

        return $this;
    }

    public function schedule($publishAt)
    {
        $this->update([
            'status' => 'scheduled',
            'scheduled_at' => $publishAt,
        ]);

        return $this;
    }

    public function isPublished()
    {
        return $this->status === 'published' && $this->published_at->lte(now());
    }

    public function isScheduled()
    {
        return $this->status === 'scheduled' && $this->scheduled_at->gt(now());
    }

    public function isDraft()
    {
        return $this->status === 'draft';
    }

    public function getVersionText()
    {
        return 'v' . $this->version;
    }

    public function getTypeText()
    {
        return match ($this->type) {
            'feature' => 'New Features',
            'bug_fix' => 'Bug Fixes',
            'improvement' => 'Improvements',
            'security' => 'Security Update',
            'documentation' => 'Documentation',
            default => 'Update',
        };
    }

    public function getFormattedFileSize()
    {
        if (!$this->file_size) {
            return null;
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $size = $this->file_size;
        $unitIndex = 0;

        while ($size >= 1024 && $unitIndex < count($units) - 1) {
            $size /= 1024;
            $unitIndex++;
        }

        return round($size, 2) . ' ' . $units[$unitIndex];
    }

    public function getPublishedAtFormatted()
    {
        return $this->published_at ? $this->published_at->format('Y-m-d H:i:s') : null;
    }

    public function getScheduledAtFormatted()
    {
        return $this->scheduled_at ? $this->scheduled_at->format('Y-m-d H:i:s') : null;
    }
}

class StuffFaq extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'stuff_id',
        'question',
        'answer',
        'category', // 'general', 'technical', 'billing', 'installation', 'troubleshooting'
        'order',
        'is_published',
        'is_featured',
        'views',
        'helpful_count',
        'not_helpful_count',
        'metadata',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'is_featured' => 'boolean',
        'order' => 'integer',
        'views' => 'integer',
        'helpful_count' => 'integer',
        'not_helpful_count' => 'integer',
        'metadata' => 'array',
    ];

    // Relationships
    public function stuff()
    {
        return $this->belongsTo(Stuff::class);
    }

    public function helpfulVotes()
    {
        return $this->hasMany(StuffFaqVote::class);
    }

    // Scopes
    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order')->orderBy('created_at');
    }

    // Methods
    public function incrementView()
    {
        $this->increment('views');
    }

    public function markHelpful($userId)
    {
        $vote = $this->helpfulVotes()->where('user_id', $userId)->first();

        if (!$vote) {
            $this->helpfulVotes()->create([
                'user_id' => $userId,
                'is_helpful' => true,
            ]);
            $this->increment('helpful_count');
        }

        return $this->helpful_count;
    }

    public function markNotHelpful($userId)
    {
        $vote = $this->helpfulVotes()->where('user_id', $userId)->first();

        if (!$vote) {
            $this->helpfulVotes()->create([
                'user_id' => $userId,
                'is_helpful' => false,
            ]);
            $this->increment('not_helpful_count');
        }

        return $this->not_helpful_count;
    }

    public function getCategoryText()
    {
        return match ($this->category) {
            'general' => 'General',
            'technical' => 'Technical',
            'billing' => 'Billing',
            'installation' => 'Installation',
            'troubleshooting' => 'Troubleshooting',
            default => 'General',
        };
    }

    public function getHelpfulnessScore()
    {
        $total = $this->helpful_count + $this->not_helpful_count;
        
        if ($total === 0) {
            return 0;
        }

        return ($this->helpful_count / $total) * 100;
    }
}

class StuffFaqVote extends Model
{
    protected $fillable = [
        'faq_id',
        'user_id',
        'is_helpful',
    ];

    protected $casts = [
        'is_helpful' => 'boolean',
    ];

    public function faq()
    {
        return $this->belongsTo(StuffFaq::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

class StuffTutorial extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'stuff_id',
        'title',
        'description',
        'content',
        'type', // 'video', 'text', 'interactive', 'download'
        'level', // 'beginner', 'intermediate', 'advanced'
        'duration', // in minutes
        'video_url',
        'video_file',
        'thumbnail',
        'attachments',
        'order',
        'is_published',
        'is_featured',
        'views',
        'completion_count',
        'difficulty_score',
        'prerequisites',
        'learning_objectives',
        'tags',
        'metadata',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'is_featured' => 'boolean',
        'order' => 'integer',
        'duration' => 'integer',
        'views' => 'integer',
        'completion_count' => 'integer',
        'difficulty_score' => 'integer',
        'prerequisites' => 'array',
        'learning_objectives' => 'array',
        'attachments' => 'array',
        'tags' => 'array',
        'metadata' => 'array',
    ];

    // Relationships
    public function stuff()
    {
        return $this->belongsTo(Stuff::class);
    }

    public function completions()
    {
        return $this->hasMany(StuffTutorialCompletion::class);
    }

    // Scopes
    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByLevel($query, $level)
    {
        return $query->where('level', $level);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order')->orderBy('created_at');
    }

    // Methods
    public function incrementView()
    {
        $this->increment('views');
    }

    public function markCompleted($userId)
    {
        $completion = $this->completions()->where('user_id', $userId)->first();

        if (!$completion) {
            $this->completions()->create(['user_id' => $userId]);
            $this->increment('completion_count');
        }

        return $this;
    }

    public function getCompletionRate()
    {
        if ($this->views === 0) {
            return 0;
        }

        return ($this->completion_count / $this->views) * 100;
    }

    public function getDurationText()
    {
        if (!$this->duration) {
            return 'Unknown';
        }

        $hours = floor($this->duration / 60);
        $minutes = $this->duration % 60;

        if ($hours > 0) {
            return $hours . 'h ' . $minutes . 'm';
        } else {
            return $minutes . ' minutes';
        }
    }

    public function getTypeText()
    {
        return match ($this->type) {
            'video' => 'Video Tutorial',
            'text' => 'Text Tutorial',
            'interactive' => 'Interactive Tutorial',
            'download' => 'Downloadable Resource',
            default => 'Tutorial',
        };
    }

    public function getLevelText()
    {
        return match ($this->level) {
            'beginner' => 'Beginner',
            'intermediate' => 'Intermediate',
            'advanced' => 'Advanced',
            default => 'All Levels',
        };
    }

    public function getDifficultyStars()
    {
        $score = $this->difficulty_score ?: 1;
        $stars = [];

        for ($i = 1; $i <= 5; $i++) {
            $stars[] = $i <= $score ? 'filled' : 'empty';
        }

        return $stars;
    }

    public function isCompletedBy($userId)
    {
        return $this->completions()->where('user_id', $userId)->exists();
    }
}

class StuffTutorialCompletion extends Model
{
    protected $fillable = [
        'tutorial_id',
        'user_id',
        'completed_at',
        'progress', // 0-100
        'time_spent', // in minutes
        'notes',
    ];

    protected $casts = [
        'completed_at' => 'datetime',
        'progress' => 'integer',
        'time_spent' => 'integer',
    ];

    public function tutorial()
    {
        return $this->belongsTo(StuffTutorial::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getCompletedAtFormatted()
    {
        return $this->completed_at ? $this->completed_at->format('Y-m-d H:i:s') : null;
    }

    public function getTimeSpentText()
    {
        if (!$this->time_spent) {
            return 'Not tracked';
        }

        $hours = floor($this->time_spent / 60);
        $minutes = $this->time_spent % 60;

        if ($hours > 0) {
            return $hours . 'h ' . $minutes . 'm';
        } else {
            return $minutes . ' minutes';
        }
    }
}
