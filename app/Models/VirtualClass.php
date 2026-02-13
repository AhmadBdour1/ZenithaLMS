<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class VirtualClass extends Model
{
    use HasFactory, SoftDeletes;
    
    protected $fillable = [
        'title',
        'description',
        'instructor_id',
        'course_id',
        'scheduled_at',
        'duration_minutes',
        'meeting_link',
        'meeting_id',
        'meeting_password',
        'max_participants',
        'current_participants',
        'status',
        'is_active',
        'is_recurring',
        'recurrence_pattern',
        'settings',
    ];
    
    protected $casts = [
        'scheduled_at' => 'datetime',
        'duration_minutes' => 'integer',
        'max_participants' => 'integer',
        'is_active' => 'boolean',
        'is_recurring' => 'boolean',
        'recurrence_pattern' => 'array',
        'settings' => 'array',
    ];
    
    public function instructor()
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }
    
    public function course()
    {
        return $this->belongsTo(Course::class);
    }
    
    public function participants()
    {
        return $this->hasMany(VirtualClassParticipant::class);
    }
}
