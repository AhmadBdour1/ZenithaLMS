<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AIAssistant extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'a_i_assistants';

    protected $fillable = [
        'name',
        'description',
        'type', // 'tutor', 'assistant', 'grader', 'content_generator'
        'model_name', // 'gpt-4', 'claude', etc.
        'model_version',
        'configuration', // JSON settings for the AI model
        'capabilities', // JSON array of capabilities
        'status', // 'active', 'inactive', 'training', 'maintenance'
        'user_id', // Owner/creator of the assistant
        'course_id', // Optional course association
        'api_usage_limit',
        'api_usage_current',
        'last_used_at',
        'is_public', // Whether other users can access this assistant
        'metadata',
    ];

    protected $casts = [
        'configuration' => 'array',
        'capabilities' => 'array',
        'metadata' => 'array',
        'is_public' => 'boolean',
        'last_used_at' => 'datetime',
        'api_usage_limit' => 'integer',
        'api_usage_current' => 'integer',
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

    public function conversations()
    {
        return $this->hasMany(AIConversation::class);
    }

    public function usageLogs()
    {
        return $this->hasMany(AIUsageLog::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId)
                    ->orWhere('is_public', true);
    }

    // Methods
    public function isAvailable()
    {
        return $this->status === 'active' && 
               (!$this->api_usage_limit || $this->api_usage_current < $this->api_usage_limit);
    }

    public function hasCapability($capability)
    {
        return in_array($capability, $this->capabilities ?? []);
    }

    public function incrementUsage($amount = 1)
    {
        $this->increment('api_usage_current', $amount);
        $this->update(['last_used_at' => now()]);
    }

    public function resetUsage()
    {
        $this->update(['api_usage_current' => 0]);
    }

    public function getUsagePercentage()
    {
        if (!$this->api_usage_limit) {
            return 0;
        }
        
        return min(100, ($this->api_usage_current / $this->api_usage_limit) * 100);
    }

    public function canBeUsedBy($user)
    {
        return $this->is_public || $this->user_id === $user->id;
    }

    // Static methods
    public static function getAvailableTypes()
    {
        return [
            'tutor' => 'Personal Tutor',
            'assistant' => 'Study Assistant',
            'grader' => 'Auto Grader',
            'content_generator' => 'Content Generator',
            'translator' => 'Language Translator',
            'summarizer' => 'Content Summarizer',
        ];
    }

    public static function getAvailableCapabilities()
    {
        return [
            'text_generation' => 'Text Generation',
            'question_answering' => 'Question Answering',
            'content_creation' => 'Content Creation',
            'grading' => 'Automatic Grading',
            'translation' => 'Language Translation',
            'summarization' => 'Text Summarization',
            'code_generation' => 'Code Generation',
            'data_analysis' => 'Data Analysis',
        ];
    }
}
