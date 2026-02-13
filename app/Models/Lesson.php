<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Lesson extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id',
        'title',
        'slug',
        'description',
        'content',
        'video_url',
        'video_file',
        'type',
        'duration_minutes',
        'is_free',
        'is_published',
        'sort_order',
        'settings',
    ];

    protected $casts = [
        'is_free' => 'boolean',
        'is_published' => 'boolean',
        'duration_minutes' => 'integer',
        'sort_order' => 'integer',
        'settings' => 'array',
    ];

    /**
     * Relationships for ZenithaLMS
     */
    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function studentProgress()
    {
        return $this->hasMany(StudentProgress::class);
    }

    public function assessments()
    {
        return $this->hasMany(Assessment::class);
    }

    public function aiAssistants()
    {
        return $this->hasMany(AIAssistant::class);
    }

    /**
     * Get published lessons
     */
    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    /**
     * Get free lessons
     */
    public function scopeFree($query)
    {
        return $query->where('is_free', true);
    }

    /**
     * Get lessons by type
     */
    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Get next lesson in course
     */
    public function getNextLessonAttribute()
    {
        return $this->course->lessons()
            ->where('sort_order', '>', $this->sort_order)
            ->published()
            ->orderBy('sort_order')
            ->first();
    }

    /**
     * Get previous lesson in course
     */
    public function getPreviousLessonAttribute()
    {
        return $this->course->lessons()
            ->where('sort_order', '<', $this->sort_order)
            ->published()
            ->orderBy('sort_order', 'desc')
            ->first();
    }

    /**
     * Check if user has completed this lesson
     */
    public function isCompletedByUser($userId)
    {
        return $this->studentProgress()
            ->where('user_id', $userId)
            ->where('status', 'completed')
            ->exists();
    }

    /**
     * Get user progress for this lesson
     */
    public function getUserProgress($userId)
    {
        return $this->studentProgress()
            ->where('user_id', $userId)
            ->first();
    }

    /**
     * Get completion rate for this lesson
     */
    public function getCompletionRateAttribute()
    {
        $totalStudents = $this->course->enrollments()->count();
        if ($totalStudents === 0) {
            return 0;
        }

        $completedStudents = $this->studentProgress()
            ->where('status', 'completed')
            ->count();

        return round(($completedStudents / $totalStudents) * 100, 2);
    }

    /**
     * Get average time spent on this lesson
     */
    public function getAverageTimeSpentAttribute()
    {
        return $this->studentProgress()
            ->whereNotNull('time_spent_minutes')
            ->avg('time_spent_minutes') ?? 0;
    }

    /**
     * Get video thumbnail URL
     */
    public function getVideoThumbnailUrlAttribute()
    {
        if ($this->video_url) {
            // Extract YouTube video ID and generate thumbnail URL
            if (str_contains($this->video_url, 'youtube.com')) {
                $videoId = $this->extractYouTubeVideoId($this->video_url);
                return "https://img.youtube.com/vi/{$videoId}/maxresdefault.jpg";
            }
        }

        return null;
    }

    /**
     * Extract YouTube video ID from URL
     */
    private function extractYouTubeVideoId($url)
    {
        preg_match('/youtube\.com\/watch\?v=([^&]+)/', $url, $matches);
        return $matches[1] ?? null;
    }

    /**
     * Check if lesson is accessible to user
     */
    public function isAccessibleByUser($userId)
    {
        // Free lessons are accessible to all enrolled students
        if ($this->is_free) {
            return $this->course->isUserEnrolled($userId);
        }

        // Paid lessons require enrollment
        return $this->course->isUserEnrolled($userId);
    }
}
