<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Notification extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'title',
        'message',
        'type',
        'channel',
        'is_read',
        'notification_data',
        'ai_priority',
        'sent_at',
        'read_at',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'notification_data' => 'array',
        'ai_priority' => 'array',
        'sent_at' => 'datetime',
        'read_at' => 'datetime',
    ];

    /**
     * ZenithaLMS: Notification Types
     */
    const TYPE_COURSE = 'course';
    const TYPE_PAYMENT = 'payment';
    const TYPE_SYSTEM = 'system';
    const TYPE_ACHIEVEMENT = 'achievement';
    const TYPE_REMINDER = 'reminder';
    const TYPE_PROMOTION = 'promotion';

    /**
     * ZenithaLMS: Notification Channels
     */
    const CHANNEL_EMAIL = 'email';
    const CHANNEL_PUSH = 'push';
    const CHANNEL_SMS = 'sms';
    const CHANNEL_IN_APP = 'in_app';

    /**
     * ZenithaLMS: Relationships
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * ZenithaLMS: Scopes
     */
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    public function scopeRead($query)
    {
        return $query->where('is_read', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByChannel($query, $channel)
    {
        return $query->where('channel', $channel);
    }

    public function scopeHighPriority($query)
    {
        return $query->where('ai_priority.priority_level', 'high');
    }

    public function scopeRecent($query, $hours = 24)
    {
        return $query->where('created_at', '>=', now()->subHours($hours));
    }

    /**
     * ZenithaLMS: Methods
     */
    public function markAsRead()
    {
        $this->is_read = true;
        $this->read_at = now();
        $this->save();
    }

    public function markAsUnread()
    {
        $this->is_read = false;
        $this->read_at = null;
        $this->save();
    }

    public function getFormattedMessageAttribute()
    {
        // ZenithaLMS: Parse message with variables
        $message = $this->message;
        
        if ($this->notification_data) {
            foreach ($this->notification_data as $key => $value) {
                $message = str_replace('{' . $key . '}', $value, $message);
            }
        }
        
        return $message;
    }

    public function getPriorityLevel()
    {
        return $this->ai_priority['priority_level'] ?? 'medium';
    }

    public function getPriorityScore()
    {
        return $this->ai_priority['priority_score'] ?? 50;
    }

    public function getActionUrl()
    {
        return $this->notification_data['action_url'] ?? null;
    }

    public function getActionButton()
    {
        return $this->notification_data['action_button'] ?? null;
    }

    /**
     * ZenithaLMS: AI-Powered Methods
     */
    public function calculateAiPriority()
    {
        $score = 50; // Base score
        
        // ZenithaLMS AI priority calculation
        $factors = [
            'type' => $this->getTypePriorityScore($this->type),
            'user_activity' => $this->getUserActivityScore(),
            'time_sensitivity' => $this->getTimeSensitivityScore(),
            'user_preferences' => $this->getUserPreferenceScore(),
        ];
        
        foreach ($factors as $factor => $factorScore) {
            $score += $factorScore;
        }
        
        $score = max(0, min(100, $score)); // Clamp between 0-100
        
        $this->ai_priority = [
            'priority_score' => $score,
            'priority_level' => $this->getPriorityLevelFromScore($score),
            'factors' => $factors,
            'calculated_at' => now()->toISOString(),
        ];
        
        $this->save();
        
        return $this->ai_priority;
    }

    private function getTypePriorityScore($type)
    {
        $typeScores = [
            self::TYPE_PAYMENT => 30,
            self::TYPE_ACHIEVEMENT => 20,
            self::TYPE_COURSE => 15,
            self::TYPE_REMINDER => 10,
            self::TYPE_SYSTEM => 25,
            self::TYPE_PROMOTION => 5,
        ];
        
        return $typeScores[$type] ?? 0;
    }

    private function getUserActivityScore()
    {
        if (!$this->user) {
            return 0;
        }
        
        $lastLogin = $this->user->last_login_at;
        if (!$lastLogin) {
            return 20; // High priority for inactive users
        }
        
        $daysSinceLogin = $lastLogin->diffInDays(now());
        
        if ($daysSinceLogin > 30) {
            return 20;
        } elseif ($daysSinceLogin > 7) {
            return 10;
        } elseif ($daysSinceLogin > 1) {
            return 5;
        }
        
        return 0;
    }

    private function getTimeSensitivityScore()
    {
        if (!$this->notification_data) {
            return 0;
        }
        
        $urgency = $this->notification_data['urgency'] ?? 'normal';
        
        $urgencyScores = [
            'urgent' => 20,
            'high' => 15,
            'normal' => 5,
            'low' => 0,
        ];
        
        return $urgencyScores[$urgency] ?? 0;
    }

    private function getUserPreferenceScore()
    {
        if (!$this->user) {
            return 0;
        }
        
        $preferences = $this->user->preferences ?? [];
        $notificationPreferences = $preferences['notifications'] ?? [];
        
        $typePreference = $notificationPreferences[$this->type] ?? 'medium';
        
        $preferenceScores = [
            'high' => 10,
            'medium' => 5,
            'low' => 0,
        ];
        
        return $preferenceScores[$typePreference] ?? 0;
    }

    private function getPriorityLevelFromScore($score)
    {
        if ($score >= 80) {
            return 'critical';
        } elseif ($score >= 60) {
            return 'high';
        } elseif ($score >= 40) {
            return 'medium';
        } elseif ($score >= 20) {
            return 'low';
        }
        
        return 'minimal';
    }

    /**
     * ZenithaLMS: Factory Methods
     */
    public static function createNotification($userId, $title, $message, $type, $channel = self::CHANNEL_IN_APP, $data = [])
    {
        $notification = self::create([
            'user_id' => $userId,
            'title' => $title,
            'message' => $message,
            'type' => $type,
            'channel' => $channel,
            'notification_data' => $data,
        ]);
        
        // Calculate AI priority
        $notification->calculateAiPriority();
        
        return $notification;
    }

    public static function sendCourseNotification($userId, $courseTitle, $action, $data = [])
    {
        $title = "Course {$action}";
        $message = "Your course '{$courseTitle}' has been {$action}";
        
        return self::createNotification(
            $userId,
            $title,
            $message,
            self::TYPE_COURSE,
            self::CHANNEL_IN_APP,
            array_merge($data, ['course_title' => $courseTitle, 'action' => $action])
        );
    }

    public static function sendPaymentNotification($userId, $amount, $status, $data = [])
    {
        $title = "Payment {$status}";
        $message = "Your payment of {$amount} has been {$status}";
        
        return self::createNotification(
            $userId,
            $title,
            $message,
            self::TYPE_PAYMENT,
            self::CHANNEL_IN_APP,
            array_merge($data, ['amount' => $amount, 'status' => $status])
        );
    }

    public static function sendAchievementNotification($userId, $achievementTitle, $data = [])
    {
        $title = "Achievement Unlocked!";
        $message = "Congratulations! You've unlocked: {$achievementTitle}";
        
        return self::createNotification(
            $userId,
            $title,
            $message,
            self::TYPE_ACHIEVEMENT,
            self::CHANNEL_IN_APP,
            array_merge($data, ['achievement_title' => $achievementTitle])
        );
    }
}
