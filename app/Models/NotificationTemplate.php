<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class NotificationTemplate extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'type',
        'channel',
        'subject_template',
        'content_template',
        'variables',
        'default_values',
        'is_active',
        'is_default',
        'template_data',
        'ai_optimization',
    ];

    protected $casts = [
        'variables' => 'array',
        'default_values' => 'array',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'template_data' => 'array',
        'ai_optimization' => 'array',
    ];

    /**
     * ZenithaLMS: Notification Types
     */
    const TYPE_INFO = 'info';
    const TYPE_SUCCESS = 'success';
    const TYPE_WARNING = 'warning';
    const TYPE_ERROR = 'error';
    const TYPE_PROMOTION = 'promotion';
    const TYPE_REMINDER = 'reminder';
    const TYPE_ALERT = 'alert';

    /**
     * ZenithaLMS: Notification Channels
     */
    const CHANNEL_EMAIL = 'email';
    const CHANNEL_PUSH = 'push';
    const CHANNEL_SMS = 'sms';
    const CHANNEL_IN_APP = 'in_app';
    const CHANNEL_WEBHOOK = 'webhook';

    /**
     * ZenithaLMS: Relationships
     */
    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    /**
     * ZenithaLMS: Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByChannel($query, $channel)
    {
        return $query->where('channel', $channel);
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    public function scopeForChannel($query, $channel)
    {
        return $query->where('channel', $channel)->where('is_active', true);
    }

    /**
     * ZenithaLMS: Methods
     */
    public function isActive()
    {
        return $this->is_active;
    }

    public function isDefault()
    {
        return $this->is_default;
    }

    public function getType()
    {
        return $this->type;
    }

    public function getChannel()
    {
        return $this->channel;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getSubjectTemplate()
    {
        return $this->subject_template;
    }

    public function getContentTemplate()
    {
        return $this->content_template;
    }

    public function getVariables()
    {
        return $this->variables ?? [];
    }

    public function getDefaultValues()
    {
        return $this->default_values ?? [];
    }

    public function getTemplateData()
    {
        return $this->template_data ?? [];
    }

    public function getAiOptimization()
    {
        return $this->ai_optimization ?? [];
    }

    /**
     * ZenithaLMS: Template Processing Methods
     */
    public function renderSubject($data = [])
    {
        $template = $this->subject_template;
        
        // Replace variables in template
        foreach ($data as $key => $value) {
            $template = str_replace('{' . $key . '}', $value, $template);
        }
        
        return $template;
    }

    public function renderContent($data = [])
    {
        $template = $this->content_template;
        
        // Replace variables in template
        foreach ($data as $key => $value) {
            $template = str_replace('{' . $key . '}', $value, $template);
        }
        
        return $template;
    }

    public function render($data = [])
    {
        $subject = $this->renderSubject($data);
        $content = $this->renderContent($data);
        
        return [
            'subject' => $subject,
            'content' => $content,
        ];
    }

    public function validateTemplateData($data = [])
    {
        $variables = $this->getVariables();
        
        foreach ($variables as $variable) {
            if (!isset($data[$variable])) {
                return false;
            }
        }
        
        return true;
    }

    public function getRequiredVariables()
    {
        $subjectTemplate = $this->subject_template;
        $contentTemplate = $this->content_template;
        
        $subjectVars = $this->extractVariables($subjectTemplate);
        $contentVars = $this->extractVariables($contentTemplate);
        
        return array_unique(array_merge($subjectVars, $contentVars));
    }

    public function extractVariables($template)
    {
        // ZenithaLMS: Extract variables from template string
        preg_match_all('/\{([^}]+)\}/', $template, $matches);
        
        return $matches[1] ?? [];
    }

    /**
     * ZenithaLMS: AI-Powered Methods
     */
    public function generateAiOptimization()
    {
        // ZenithaLMS: Generate AI-powered template optimization
        $optimization = [
            'open_rate' => $this->calculateOpenRate(),
            'click_through_rate' => $this->calculateClickThroughRate(),
            'engagement_score' => $this->calculateEngagementScore(),
            'conversion_rate' => $this->calculateConversionRate(),
            'subject_performance' => $this->analyzeSubjectPerformance(),
            'content_performance' => $this->analyzeContentPerformance(),
            'personalization_suggestions' => $this->generatePersonalizationSuggestions(),
            'a_b_testing_score' => $this->calculateABTestingScore(),
            'mobile_optimization' => $this->calculateMobileOptimization(),
        ];

        $this->update([
            'ai_optimization' => array_merge($this->getAiOptimization(), [
                'ai_optimized_at' => now()->toISOString(),
                'optimization_version' => '1.0',
            ]),
        ]);

        return $optimization;
    }

    private function calculateOpenRate()
    {
        // ZenithaLMS: Calculate open rate for this template
        // This would be based on analytics data in a real implementation
        return 0.75; // Placeholder
    }

    private function calculateClickThroughRate()
    {
        // ZenithaLMS: Calculate click-through rate for this template
        // This would be based on analytics data in a real implementation
        return 0.25; // Placeholder
    }

    private function calculateEngagementScore()
    {
        // ZenithaLMS: Calculate engagement score for this template
        $score = 0.5; // Base score
        
        $subjectTemplate = $this->subject_template;
        $contentTemplate = $this->content_template;
        
        // Check for personalization
        $personalizationCount = count($this->getVariables());
        if ($personalizationCount > 5) {
            $score += 0.2;
        }
        
        // Check for clear call-to-action
        if (preg_match('/\b(click|visit|join|start|begin|go to|check out|learn more)\b/i', $contentTemplate)) {
            $score += 0.2;
        }
        
        // Check for urgency indicators
        if (preg_match('/\b(urgent|immediate|asap|now)\b/i', $subjectTemplate)) {
            $score += 0.1;
        }
        
        return min(1.0, $score);
    }

    private function calculateConversionRate()
    {
        // ZenithaLMS: Calculate conversion rate for this template
        // This would be based on analytics data in a real implementation
        return 0.15; // Placeholder
    }

    private function analyzeSubjectPerformance()
    {
        // ZenithaLMS: Analyze subject line performance
        $subjectTemplate = $this->subject_template;
        
        $score = 0.5; // Base score
        
        // Check for personalization
        $personalizationCount = count($this->getVariables());
        if ($personalizationCount > 3) {
            $score += 0.2;
        }
        
        // Check for clarity
        if (strlen($subjectTemplate) < 50) {
            $score += 0.1;
        } elseif (strlen($subjectTemplate) > 100) {
            $score -= 0.1;
        }
        
        // Check for urgency indicators
        if (preg_match('/\b(urgent|important|critical|alert)\b/i', $subjectTemplate)) {
            $score += 0.2;
        }
        
        // Check for personalization
        if (preg_match('/\{[^}]+\}/', $subjectTemplate)) {
            $score += 0.1;
        }
        
        return min(1.0, $score);
    }

    private function analyzeContentPerformance()
    {
        // ZenithaLMS: Analyze content performance
        $contentTemplate = $this->content_template;
        
        $score = 0.5; // Base score
        
        // Check for readability
        $wordCount = str_word_count($contentTemplate);
        $sentenceCount = preg_match('/[.!?]+/', $contentTemplate);
        
        $avgSentenceLength = $wordCount > 0 ? $wordCount / $sentenceCount : 0;
        
        if ($avgSentenceLength >= 15 && $avgSentenceLength <= 25) {
            $score += 0.1;
        } elseif ($avgSentenceLength < 10 || $avgSentenceLength > 30) {
            $score -= 0.1;
        }
        
        // Check for structure
        if (preg_match('/\n\n+/', $contentTemplate)) {
            $score += 0.1;
        }
        
        // Check for action words
        $actionWords = ['click', 'visit', 'join', 'start', 'begin', 'go to', 'check out', 'learn more'];
        foreach ($actionWords as $word) {
            if (preg_match('/\b' . $word . '\b/i', $contentTemplate)) {
                $score += 0.05;
            }
        }
        
        return min(1.0, $score);
    }

    private function generatePersonalizationSuggestions()
    {
        // ZenithaLMS: Generate AI-powered personalization suggestions
        $suggestions = [];
        
        $subjectTemplate = $this->subject_template;
        $contentTemplate = $content_template;
        
        // Suggest personalization for subject
        if (!preg_match('/\{[^}]+\}/', $subjectTemplate)) {
            $suggestions[] = 'Consider adding personalization variables like {name} to make the subject more personal';
        }
        
        // Suggest personalization for content
        if (!preg_match('/\{[^}]+\}/', $contentTemplate)) {
            $suggestions[] = 'Consider adding personalization variables like {course_name} to make the content more relevant';
        }
        
        // Suggest action-oriented content
        if (!preg_match('/\b(click|visit|join|start|begin|go to|check out|learn more)\b/i', $contentTemplate)) {
            $suggestions[] = 'Consider adding action-oriented language to encourage engagement';
        }
        
        // Suggest urgency indicators
        if (!preg_match('/\b(urgent|important|critical|alert)\b/i', $subjectTemplate)) {
            $suggestions[] = 'Consider adding urgency indicators if time-sensitive';
        }
        
        return $suggestions;
    }

    private function calculateABTestingScore()
    {
        // ZenithaLMS: Calculate A/B testing score
        $score = 0.5; // Base score
        
        // Check for clear subject line
        $subjectTemplate = $this->subject_template;
        if (strlen($subjectTemplate) < 20) {
            $score -= 0.1;
        } elseif (strlen($subjectTemplate) > 60) {
            $score -= 0.1;
        }
        
        // Check for clear content
        $contentTemplate = $this->content_template;
        if (strlen($contentTemplate) < 50) {
            $score -= 0.1;
        }
        
        // Check for clear call-to-action
        if (!preg_match('/\b(click|visit|join|start|begin|go to|check out|learn more)\b/i', $contentTemplate)) {
            $score -= 0.1;
        }
        
        return min(1.0, $score);
    }

    private function calculateMobileOptimization()
    {
        // ZenithaLMS: Calculate mobile optimization score
        $score = 0.5; // Base score
        
        $subjectTemplate = $this->subject_template;
        $contentTemplate = $this->content_template;
        
        // Check for mobile-friendly subject
        if (strlen($subjectTemplate) > 40) {
            $score -= 0.1;
        }
        
        // Check for mobile-friendly content
        if (strlen($contentTemplate) > 200) {
            $score -= 0.1;
        }
        
        // Check for mobile-unfriendly elements
        if (preg_match('/\b(table|iframe|embed|object)\b/i', $contentTemplate)) {
            $score -= 0.1;
        }
        
        return min(1.0, $score);
    }

    /**
     * ZenithaLMS: Static Methods
     */
    public static function getTypes()
    {
        return [
            self::TYPE_INFO => 'Information',
            self::TYPE_SUCCESS => 'Success',
            self::TYPE_WARNING => 'Warning',
            self::TYPE_ERROR => 'Error',
            self::TYPE_PROMOTION => 'Promotion',
            self::TYPE_REMINDER => 'Reminder',
            self::TYPE_ALERT => 'Alert',
        ];
    }

    public static function getChannels()
    {
        return [
            self::CHANNEL_EMAIL => 'Email',
            self::CHANNEL_PUSH => 'Push Notification',
            self::CHANNEL_SMS => 'SMS',
            self::CHANNEL_IN_APP => 'In-App',
            self::CHANNEL_WEBHOOK => 'Webhook',
        ];
    }

    public static function getDefaultTemplate($type, $channel)
    {
        return self::where('type', $type)
            ->where('channel', $channel)
            ->where('is_default', true)
            ->first();
    }

    public static function createDefaultTemplates()
    {
        $templates = [
            // Welcome email template
                [
                    'name' => 'Welcome Email',
                    'type' => self::TYPE_INFO,
                    'channel' => self::CHANNEL_EMAIL,
                    'subject_template' => 'Welcome to {site_name}, {user_name}!',
                    'content_template' => 'Thank you for joining {site_name}. We\'re excited to have you with us!',
                    'variables' => ['site_name', 'user_name'],
                    'default_values' => ['ZenithaLMS', 'Student'],
                    'is_active' => true,
                    'is_default' => true,
                ],
                
                // Course enrollment confirmation
                [
                    'name' => 'Course Enrollment',
                    'type' => self::TYPE_SUCCESS,
                    'channel' => self::CHANNEL_EMAIL,
                    'subject_template' => 'Course Enrolled: {course_title}',
                    'content_template' => 'You have successfully enrolled in {course_title}. Course starts on {start_date}.',
                    'variables' => ['course_title', 'start_date'],
                    'default_values' => ['Course Started', 'January 1, 2026'],
                    'is_active' => true,
                    'is_default' => false,
                ],
                
                // Quiz reminder
                [
                    'name' => 'Quiz Reminder',
                    'type' => self::TYPE_REMINDER,
                    'channel' => self::CHANNEL_EMAIL,
                    'subject_template' => 'Quiz Reminder: {quiz_title}',
                    'content_template' => 'Your quiz "{quiz_title}" is scheduled for {quiz_date}. Don\'t forget to prepare!',
                    'variables' => ['quiz_title', 'quiz_date'],
                    'default_values' => ['Quiz Title', 'January 1, 2026'],
                    'is_active' => true,
                    'is_default' => false,
                ],
                
                // Assignment due reminder
                [
                    'name' => 'Assignment Due Reminder',
                    'type' => self::TYPE_WARNING,
                    'channel' => self::CHANNEL_EMAIL,
                    'subject_template' => 'Assignment Due Soon: {assignment_title}',
                    'content_template' => 'Your assignment "{assignment_title}" is due on {due_date}. Please submit it before the deadline.',
                    'variables' => ['assignment_title', 'due_date'],
                    'default_values' => ['Assignment Title', 'December 31, 2025'],
                    'is_active' => true,
                    'is_default' => false,
                ],
                
                // System alert
                [
                    'name' => 'System Alert',
                    'type' => self::TYPE_ALERT,
                    'channel' => self::CHANNEL_IN_APP,
                    'subject_template' => 'System Alert: {alert_title}',
                    'content_template' => '{alert_message}',
                    'variables' => ['alert_title', 'alert_message'],
                    'default_values' => ['System Alert', 'System message'],
                    'is_active' => true,
                    'is_default' => false,
                ],
            ];
        
        foreach ($templates as $template) {
            $existing = self::where('name', $template['name'])
                ->where('type', $template['type'])
                ->where('channel', $template['channel'])
                ->first();
            
            if (!$existing) {
                self::create($template);
            }
        }
    }

    public static function getTemplateVariables($templateId)
    {
        $template = self::findOrFail($templateId);
        return $template->getVariables();
    }

    public static function validateTemplate($data)
    {
        $required = ['name', 'type', 'channel', 'subject_template', 'content_template'];
        
        foreach ($required as $field) {
            if (!isset($data[$field])) {
                return false;
            }
        }
        
        return true;
    }
}
