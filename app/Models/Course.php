<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Course extends Model
{
    use HasFactory;
    protected $fillable = [
        'organization_id',
        'instructor_id',
        'category_id',
        'title',
        'slug',
        'description',
        'content',
        'thumbnail',
        'preview_video',
        'price',
        'is_free',
        'level',
        'language',
        'duration_minutes',
        'requirements',
        'what_you_will_learn',
        'target_audience',
        'is_published',
        'is_featured',
        'sort_order',
        'settings',
    ];

    protected $casts = [
        'requirements' => 'array',
        'what_you_will_learn' => 'array',
        'target_audience' => 'array',
        'settings' => 'array',
        'is_free' => 'boolean',
        'is_published' => 'boolean',
        'is_featured' => 'boolean',
        'price' => 'decimal:2',
        'duration_minutes' => 'integer',
        'sort_order' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relationships with eager loading by default
     */
    protected $with = ['category', 'instructor'];

    /**
     * Relationships
     */
    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function instructor()
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function lessons()
    {
        return $this->hasMany(Lesson::class)->orderBy('sort_order');
    }

    public function publishedLessons()
    {
        return $this->hasMany(Lesson::class)->where('is_published', true)->orderBy('sort_order');
    }

    /**
     * Get enrolled students
     */
    public function enrolledStudents()
    {
        return $this->belongsToMany(User::class, 'enrollments')
            ->withPivot(['status', 'progress_percentage', 'enrolled_at', 'completed_at'])
            ->wherePivot('status', 'active');
    }

    /**
     * Get completed students
     */
    public function completedStudents()
    {
        return $this->belongsToMany(User::class, 'enrollments')
            ->withPivot(['status', 'progress_percentage', 'enrolled_at', 'completed_at'])
            ->wherePivot('status', 'completed');
    }

    public function assessments()
    {
        return $this->hasMany(Assessment::class);
    }

    public function enrollments()
    {
        return $this->hasMany(Enrollment::class);
    }

    public function activeEnrollments()
    {
        return $this->hasMany(Enrollment::class)->where('status', 'active');
    }

    public function completedEnrollments()
    {
        return $this->hasMany(Enrollment::class)->where('status', 'completed');
    }

    public function studentProgress()
    {
        return $this->hasMany(StudentProgress::class);
    }

    public function adaptivePaths()
    {
        return $this->hasMany(AdaptivePath::class);
    }

    public function aiAssistants()
    {
        return $this->hasMany(AIAssistant::class);
    }

    /**
     * Get total duration of all lessons (optimized)
     */
    public function getTotalDurationAttribute()
    {
        return $this->publishedLessons()->sum('duration_minutes');
    }

    /**
     * Get average completion rate (optimized)
     */
    public function getAverageCompletionRateAttribute()
    {
        // Use cached value or calculate
        return Cache::remember(
            "course_completion_rate_{$this->id}",
            300, // 5 minutes
            function () {
                $totalEnrollments = $this->enrollments()->count();
                if ($totalEnrollments === 0) {
                    return 0;
                }

                $totalProgress = $this->enrollments()->sum('progress_percentage');
                return round($totalProgress / $totalEnrollments, 2);
            }
        );
    }

    /**
     * Get number of active students (optimized)
     */
    public function getActiveStudentsCountAttribute()
    {
        return Cache::remember(
            "course_active_students_{$this->id}",
            300, // 5 minutes
            function () {
                return $this->activeEnrollments()->count();
            }
        );
    }

    /**
     * Check if user is enrolled (optimized)
     */
    public function isUserEnrolled($userId)
    {
        return Cache::remember(
            "course_user_enrolled_{$this->id}_{$userId}",
            300, // 5 minutes
            function () use ($userId) {
                return $this->enrollments()->where('user_id', $userId)->where('status', 'active')->exists();
            }
        );
    }

    /**
     * Get user progress in course (optimized)
     */
    public function getUserProgress($userId)
    {
        return Cache::remember(
            "course_user_progress_{$this->id}_{$userId}",
            300, // 5 minutes
            function () use ($userId) {
                return $this->enrollments()->where('user_id', $userId)->first();
            }
        );
    }

    /**
     * Get next lesson for user
     */
    public function getNextLessonForUser($userId)
    {
        $completedLessons = $this->studentProgress()
            ->where('user_id', $userId)
            ->where('status', 'completed')
            ->pluck('lesson_id')
            ->toArray();

        return $this->publishedLessons()
            ->whereNotIn('id', $completedLessons)
            ->first();
    }
}
