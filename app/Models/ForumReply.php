<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ForumReply extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'forum_id',
        'user_id',
        'content',
        'status',
        'like_count',
        'reply_data',
        'ai_sentiment',
    ];

    protected $casts = [
        'like_count' => 'integer',
        'reply_data' => 'array',
        'ai_sentiment' => 'array',
    ];

    /**
     * ZenithaLMS: Reply Status Constants
     */
    const STATUS_ACTIVE = 'active';
    const STATUS_PENDING = 'pending';
    const STATUS_HIDDEN = 'hidden';
    const STATUS_DELETED = 'deleted';

    /**
     * ZenithaLMS: Relationships
     */
    public function forum()
    {
        return $this->belongsTo(Forum::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function likes()
    {
        return $this->hasMany(ForumReplyLike::class);
    }

    /**
     * ZenithaLMS: Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeHidden($query)
    {
        return $query->where('status', self::STATUS_HIDDEN);
    }

    public function scopeRecent($query, $hours = 24)
    {
        return $query->where('created_at', '>=', now()->subHours($hours));
    }

    /**
     * ZenithaLMS: Methods
     */
    public function isActive()
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isPending()
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isHidden()
    {
        return $this->status === self::STATUS_HIDDEN;
    }

    public function getFormattedContent()
    {
        return nl2br(e($this->content));
    }

    public function getExcerpt($length = 100)
    {
        return Str::limit(strip_tags($this->content), $length);
    }

    public function isLikedBy($userId)
    {
        return $this->likes()->where('user_id', $userId)->exists();
    }

    public function incrementLikeCount()
    {
        $this->increment('like_count');
    }

    public function decrementLikeCount()
    {
        $this->decrement('like_count');
    }

    /**
     * ZenithaLMS: AI-Powered Methods
     */
    public function generateAiSentiment()
    {
        // ZenithaLMS: Generate AI sentiment analysis for reply
        $content = $this->content;
        
        $sentiment = $this->analyzeSentiment($content);
        $keywords = $this->extractKeywords($content);
        $emotionalTone = $this->analyzeEmotionalTone($content);
        $language = $this->detectLanguage($content);
        $complexity = $this->analyzeComplexity($content);
        
        $sentimentData = [
            'sentiment' => $sentiment,
            'confidence' => $this->calculateSentimentConfidence($content, $sentiment),
            'keywords' => $keywords,
            'emotional_tone' => $emotionalTone,
            'language' => $language,
            'complexity' => $complexity,
            'analyzed_at' => now()->toISOString(),
        ];

        $this->update([
            'ai_sentiment' => $sentimentData,
        ]);

        return $sentimentData;
    }

    private function analyzeSentiment($content)
    {
        // ZenithaLMS: Enhanced sentiment analysis
        $positiveWords = ['good', 'great', 'excellent', 'amazing', 'wonderful', 'fantastic', 'helpful', 'useful', 'perfect', 'love', 'awesome', 'brilliant', 'outstanding', 'thank', 'thanks', 'appreciate', 'agree', 'yes', 'correct', 'right', 'solution', 'fixed', 'resolved'];
        $negativeWords = ['bad', 'terrible', 'awful', 'horrible', 'disappointing', 'useless', 'hate', 'worst', 'poor', 'problem', 'issue', 'bug', 'error', 'wrong', 'incorrect', 'fail', 'failed', 'broken', 'not working', 'doesn\'t work'];
        $neutralWords = ['question', 'information', 'help', 'need', 'want', 'looking', 'searching', 'find', 'check', 'verify', 'maybe', 'perhaps', 'possibly', 'could be', 'might be'];
        
        $contentLower = strtolower($content);
        $positiveCount = 0;
        $negativeCount = 0;
        $neutralCount = 0;

        foreach ($positiveWords as $word) {
            $positiveCount += substr_count($contentLower, $word);
        }

        foreach ($negativeWords as $word) {
            $negativeCount += substr_count($contentLower, $word);
        }

        foreach ($neutralWords as $word) {
            $neutralCount += substr_count($contentLower, $word);
        }

        $totalWords = str_word_count($content);
        
        if ($positiveCount > $negativeCount && $positiveCount > $neutralCount) {
            return 'positive';
        } elseif ($negativeCount > $positiveCount && $negativeCount > $neutralCount) {
            return 'negative';
        } elseif ($neutralCount > $positiveCount && $neutralCount > $negativeCount) {
            return 'neutral';
        } elseif ($positiveCount === $negativeCount) {
            return 'neutral';
        }

        return 'neutral';
    }

    private function calculateSentimentConfidence($content, $sentiment)
    {
        // ZenithaLMS: Calculate confidence score for sentiment analysis
        $totalWords = str_word_count($content);
        
        if ($totalWords === 0) {
            return 0;
        }

        $sentimentWords = [
            'positive' => ['good', 'great', 'excellent', 'amazing', 'wonderful', 'fantastic', 'helpful', 'useful'],
            'negative' => ['bad', 'terrible', 'awful', 'horrible', 'disappointing', 'useless', 'problem', 'issue'],
            'neutral' => ['question', 'information', 'help', 'need', 'want', 'looking', 'searching'],
        ];

        $relevantWords = $sentimentWords[$sentiment] ?? [];
        $relevantCount = 0;

        foreach ($relevantWords as $word) {
            $relevantCount += substr_count(strtolower($content), $word);
        }

        $confidence = ($relevantCount / $totalWords) * 100;
        return min(100, max(0, $confidence));
    }

    private function extractKeywords($content)
    {
        // ZenithaLMS: Extract important keywords from content
        $stopWords = ['the', 'a', 'an', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by', 'is', 'are', 'was', 'were', 'be', 'been', 'have', 'has', 'had', 'do', 'does', 'did', 'will', 'would', 'could', 'should'];
        
        $words = str_word_count(strtolower($content), 1);
        $keywords = [];

        foreach ($words as $word) {
            if (!in_array($word, $stopWords) && strlen($word) > 2) {
                $keywords[] = $word;
            }
        }

        return array_unique(array_slice($keywords, 0, 10));
    }

    private function analyzeEmotionalTone($content)
    {
        // ZenithaLMS: Analyze emotional tone
        $emotionalWords = [
            'excited' => ['excited', 'thrilled', 'enthusiastic', 'passionate', 'eager', 'happy', 'joyful', 'delighted'],
            'frustrated' => ['frustrated', 'annoyed', 'irritated', 'upset', 'angry', 'mad', 'disappointed', 'confused'],
            'concerned' => ['concerned', 'worried', 'anxious', 'nervous', 'stressed', 'troubled', 'unsure', 'doubtful'],
            'calm' => ['calm', 'relaxed', 'peaceful', 'serene', 'composed', 'tranquil', 'quiet', 'gentle'],
            'professional' => ['professional', 'formal', 'polite', 'respectful', 'courteous', 'appropriate', 'proper'],
        ];

        $contentLower = strtolower($content);
        $toneScores = [];

        foreach ($emotionalWords as $tone => $words) {
            $score = 0;
            foreach ($words as $word) {
                $score += substr_count($contentLower, $word);
            }
            $toneScores[$tone] = $score;
        }

        arsort($toneScores);
        
        return key($toneScores) ?: 'neutral';
    }

    private function detectLanguage($content)
    {
        // ZenithaLMS: Simple language detection
        $arabicChars = 'ابجدهوزحطيكلمنسععفصقرشتثخذضعضظغععفقكلمنوهي';
        $contentSample = substr($content, 0, 100);
        
        $arabicCount = 0;
        for ($i = 0; $i < strlen($contentSample); $i++) {
            if (strpos($arabicChars, $contentSample[$i]) !== false) {
                $arabicCount++;
            }
        }

        if ($arabicCount > 20) {
            return 'ar';
        }

        return 'en';
    }

    private function analyzeComplexity($content)
    {
        // ZenithaLMS: Analyze content complexity
        $wordCount = str_word_count($content);
        $sentenceCount = preg_match_all('/[.!?]+/', $content);
        $avgWordsPerSentence = $sentenceCount > 0 ? $wordCount / $sentenceCount : $wordCount;

        if ($avgWordsPerSentence > 20) {
            return 'high';
        } elseif ($avgWordsPerSentence > 10) {
            return 'medium';
        }

        return 'low';
    }

    /**
     * ZenithaLMS: Static Methods
     */
    public static function getStatuses()
    {
        return [
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_PENDING => 'Pending',
            self::STATUS_HIDDEN => 'Hidden',
            self::STATUS_DELETED => 'Deleted',
        ];
    }

    public static function getEmotionalTones()
    {
        return [
            'excited' => 'Excited',
            'frustrated' => 'Frustrated',
            'concerned' => 'Concerned',
            'calm' => 'Calm',
            'professional' => 'Professional',
            'neutral' => 'Neutral',
        ];
    }

    public static function getComplexityLevels()
    {
        return [
            'low' => 'Low',
            'medium' => 'Medium',
            'high' => 'High',
        ];
    }
}
