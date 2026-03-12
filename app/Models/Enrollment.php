<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Enrollment extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'user_id',
        'course_id',
        'organization_id',
        'status',
        'progress_percentage',
        'enrolled_at',
        'completed_at',
        'last_accessed_at',
        'certificate_url',
        'final_score',
        'enrollment_data',
    ];
    
    protected $casts = [
        'progress_percentage' => 'decimal:2',
        'final_score' => 'decimal:2',
        'enrolled_at' => 'datetime',
        'completed_at' => 'datetime',
        'last_accessed_at' => 'datetime',
        'enrollment_data' => 'array',
    ];
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    public function course()
    {
        return $this->belongsTo(Course::class);
    }
    
    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }
    
    /**
     * Check if enrollment is active
     */
    public function isActive()
    {
        return $this->status === 'active';
    }
    
    /**
     * Check if enrollment is completed
     */
    public function isCompleted()
    {
        return $this->status === 'completed';
    }
    
    /**
     * Check if enrollment is dropped
     */
    public function isDropped()
    {
        return $this->status === 'dropped';
    }
    
    /**
     * Check if enrollment is suspended
     */
    public function isSuspended()
    {
        return $this->status === 'suspended';
    }
    
    /**
     * Get status description
     */
    public function getStatusDescription()
    {
        return match($this->status) {
            'active' => 'Active',
            'completed' => 'Completed',
            'dropped' => 'Dropped',
            'suspended' => 'Suspended',
            default => 'Unknown',
        };
    }
    
    /**
     * Get learning path from enrollment data
     */
    public function getLearningPath()
    {
        return $this->enrollment_data['learning_path'] ?? 'standard';
    }
    
    /**
     * Get preferred time from enrollment data
     */
    public function getPreferredTime()
    {
        return $this->enrollment_data['preferred_time'] ?? 'morning';
    }
    
    /**
     * Get study pace from enrollment data
     */
    public function getStudyPace()
    {
        return $this->enrollment_data['study_pace'] ?? 'normal';
    }
}
