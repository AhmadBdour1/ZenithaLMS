<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Quiz extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'title',
        'description',
        'course_id',
        'instructor_id',
        'time_limit_minutes',
        'passing_score',
        'max_attempts',
        'is_active',
        'is_published',
        'difficulty_level',
        'instructions',
        'settings',
    ];
    
    protected $casts = [
        'is_active' => 'boolean',
        'is_published' => 'boolean',
        'time_limit_minutes' => 'integer',
        'passing_score' => 'integer',
        'max_attempts' => 'integer',
        'settings' => 'array',
    ];
    
    public function course()
    {
        return $this->belongsTo(Course::class);
    }
    
    public function instructor()
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }
    
    public function questions()
    {
        return $this->hasMany(QuizQuestion::class);
    }
    
    public function attempts()
    {
        return $this->hasMany(QuizAttempt::class);
    }
}
