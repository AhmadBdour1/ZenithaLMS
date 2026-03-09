<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AIConversation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'course_id',
        'lesson_id',
        'ai_assistant_id',
        'session_id',
        'type', // 'tutor', 'content_generator', 'analyzer', 'translator'
        'conversation_history', // JSON array of messages
        'context_data', // JSON array of course/lesson context
        'message_count',
        'satisfaction_score',
        'last_activity_at',
        'is_active',
        'ai_settings', // JSON array of AI configuration
    ];

    protected $casts = [
        'conversation_history' => 'array',
        'context_data' => 'array',
        'ai_settings' => 'array',
        'message_count' => 'integer',
        'satisfaction_score' => 'float',
        'last_activity_at' => 'datetime',
        'is_active' => 'boolean',
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

    public function lesson()
    {
        return $this->belongsTo(Lesson::class);
    }

    public function aiAssistant()
    {
        return $this->belongsTo(AIAssistant::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    // Methods
    public function addMessage($role, $content, $metadata = [])
    {
        $history = $this->conversation_history ?? [];
        $history[] = [
            'role' => $role, // 'user' or 'assistant'
            'content' => $content,
            'timestamp' => now()->toISOString(),
            'metadata' => $metadata,
        ];
        
        $this->update([
            'conversation_history' => $history,
            'message_count' => count($history),
            'last_activity_at' => now(),
        ]);
    }

    public function getLastMessage()
    {
        $history = $this->conversation_history ?? [];
        return end($history) ?: null;
    }

    public function getMessageCount()
    {
        return count($this->conversation_history ?? []);
    }

    public function markAsCompleted()
    {
        $this->update(['is_active' => false]);
    }

    public function rateSatisfaction($score)
    {
        $this->update(['satisfaction_score' => $score]);
    }
}
