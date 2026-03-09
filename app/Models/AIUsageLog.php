<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AIUsageLog extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'ai_assistant_id',
        'user_id',
        'conversation_id',
        'action', // 'query', 'generation', 'analysis', 'translation'
        'input_tokens',
        'output_tokens',
        'processing_time_ms',
        'cost',
        'model_used',
        'request_data', // JSON array of request details
        'response_data', // JSON array of response details
        'error_message',
        'status', // 'success', 'failed', 'timeout'
        'created_at',
    ];

    protected $casts = [
        'input_tokens' => 'integer',
        'output_tokens' => 'integer',
        'processing_time_ms' => 'integer',
        'cost' => 'decimal:10,6',
        'request_data' => 'array',
        'response_data' => 'array',
        'created_at' => 'datetime',
    ];

    // Relationships
    public function aiAssistant()
    {
        return $this->belongsTo(AIAssistant::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function conversation()
    {
        return $this->belongsTo(AIConversation::class);
    }

    // Scopes
    public function scopeSuccessful($query)
    {
        return $query->where('status', 'success');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeByAction($query, $action)
    {
        return $query->where('action', $action);
    }

    public function scopeForAssistant($query, $assistantId)
    {
        return $query->where('ai_assistant_id', $assistantId);
    }

    // Methods
    public function getTotalTokens()
    {
        return $this->input_tokens + $this->output_tokens;
    }

    public function getTokensPerSecond()
    {
        if ($this->processing_time_ms <= 0) {
            return 0;
        }
        
        $totalTokens = $this->getTotalTokens();
        $seconds = $this->processing_time_ms / 1000;
        
        return $totalTokens / $seconds;
    }

    public function isSuccessful()
    {
        return $this->status === 'success';
    }

    public function hasError()
    {
        return !empty($this->error_message);
    }

    // Static methods
    public static function logUsage($assistantId, $userId, $action, $tokens, $processingTime, $cost = 0, $status = 'success', $error = null)
    {
        return static::create([
            'ai_assistant_id' => $assistantId,
            'user_id' => $userId,
            'action' => $action,
            'input_tokens' => $tokens['input'] ?? 0,
            'output_tokens' => $tokens['output'] ?? 0,
            'processing_time_ms' => $processingTime,
            'cost' => $cost,
            'status' => $status,
            'error_message' => $error,
        ]);
    }

    public static function getUsageStats($assistantId, $startDate, $endDate)
    {
        return static::where('ai_assistant_id', $assistantId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->successful()
            ->selectRaw('
                COUNT(*) as total_requests,
                SUM(input_tokens) as total_input_tokens,
                SUM(output_tokens) as total_output_tokens,
                AVG(processing_time_ms) as avg_processing_time,
                SUM(cost) as total_cost
            ')
            ->first();
    }
}
