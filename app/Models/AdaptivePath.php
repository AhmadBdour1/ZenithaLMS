<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AdaptivePath extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'course_id',
        'name',
        'description',
        'learning_style', // 'visual', 'auditory', 'kinesthetic', 'reading'
        'difficulty_level', // 'beginner', 'intermediate', 'advanced'
        'pace', // 'slow', 'normal', 'fast'
        'preferences', // JSON array of learning preferences
        'progress_data', // JSON array of progress tracking
        'recommendations', // JSON array of AI recommendations
        'status', // 'active', 'paused', 'completed', 'archived'
        'completion_percentage',
        'estimated_completion_time',
        'actual_time_spent',
        'last_activity_at',
        'metadata',
    ];

    protected $casts = [
        'preferences' => 'array',
        'progress_data' => 'array',
        'recommendations' => 'array',
        'completion_percentage' => 'float',
        'estimated_completion_time' => 'integer', // in minutes
        'actual_time_spent' => 'integer', // in minutes
        'last_activity_at' => 'datetime',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function pathNodes()
    {
        return $this->hasMany(AdaptivePathNode::class);
    }

    public function progressUpdates()
    {
        return $this->hasMany(AdaptivePathProgress::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByLearningStyle($query, $style)
    {
        return $query->where('learning_style', $style);
    }

    // Methods
    public function updateProgress($percentage, $timeSpent = 0)
    {
        $this->update([
            'completion_percentage' => $percentage,
            'actual_time_spent' => $this->actual_time_spent + $timeSpent,
            'last_activity_at' => now(),
        ]);

        if ($percentage >= 100) {
            $this->markAsCompleted();
        }
    }

    public function markAsCompleted()
    {
        $this->update([
            'status' => 'completed',
            'completion_percentage' => 100,
        ]);
    }

    public function pause()
    {
        $this->update(['status' => 'paused']);
    }

    public function resume()
    {
        $this->update(['status' => 'active']);
    }

    public function addRecommendation($recommendation)
    {
        $recommendations = $this->recommendations ?? [];
        $recommendations[] = $recommendation;
        $this->update(['recommendations' => $recommendations]);
    }

    public function getNextNode()
    {
        return $this->pathNodes()
                   ->where('is_completed', false)
                   ->orderBy('order')
                   ->first();
    }

    public function getCompletedNodesCount()
    {
        return $this->pathNodes()->where('is_completed', true)->count();
    }

    public function getTotalNodesCount()
    {
        return $this->pathNodes()->count();
    }

    public function getTimeRemaining()
    {
        if ($this->completion_percentage >= 100) {
            return 0;
        }

        $remainingPercentage = 100 - $this->completion_percentage;
        if ($this->actual_time_spent > 0 && $this->completion_percentage > 0) {
            $averageTimePerPercentage = $this->actual_time_spent / $this->completion_percentage;
            return (int) ($remainingPercentage * $averageTimePerPercentage);
        }

        return $this->estimated_completion_time - $this->actual_time_spent;
    }

    // Static methods
    public static function getLearningStyles()
    {
        return [
            'visual' => 'Visual Learner',
            'auditory' => 'Auditory Learner',
            'kinesthetic' => 'Kinesthetic Learner',
            'reading' => 'Reading/Writing Learner',
        ];
    }

    public static function getDifficultyLevels()
    {
        return [
            'beginner' => 'Beginner',
            'intermediate' => 'Intermediate',
            'advanced' => 'Advanced',
        ];
    }

    public static function getPaceOptions()
    {
        return [
            'slow' => 'Slow Pace',
            'normal' => 'Normal Pace',
            'fast' => 'Fast Pace',
        ];
    }
}
