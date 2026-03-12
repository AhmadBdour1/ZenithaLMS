<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ForumReplyLike extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'forum_reply_id',
        'user_id',
        'liked_at',
    ];

    protected $casts = [
        'liked_at' => 'datetime',
    ];

    /**
     * ZenithaLMS: Relationships
     */
    public function forumReply()
    {
        return $this->belongsTo(ForumReply::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * ZenithaLMS: Scopes
     */
    public function scopeByReply($query, $replyId)
    {
        return $query->where('forum_reply_id', $replyId);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeRecent($query, $hours = 24)
    {
        return $query->where('liked_at', '>=', now()->subHours($hours));
    }

    /**
     * ZenithaLMS: Methods
     */
    public function getLikedAtFormatted()
    {
        return $this->liked_at ? $this->liked_at->format('M d, Y g:i A') : 'Not liked';
    }

    public function getUser()
    {
        return $this->user->name ?? 'Unknown User';
    }

    public function getReplyContent()
    {
        return $this->forumReply->getExcerpt(50);
    }

    public function getReplyTitle()
    {
        return $forumReply->forum->title ?? 'Unknown Reply';
    }

    /**
     * ZenithaLMS: AI-Powered Methods
     */
    public function generateAiAnalysis()
    {
        // ZenithaLMS: Generate AI-powered like analysis
        $analysis = [
            'engagement_pattern' => $this->analyzeEngagementPattern(),
            'user_interaction_type' => $this->analyzeUserInteractionType(),
            'community_value' => $this->analyzeCommunityValue(),
            'sentiment_correlation' => $this->analyzeSentimentCorrelation(),
            'timing_analysis' => $this->analyzeTimingAnalysis(),
            'influence_score' => $this->calculateInfluenceScore(),
        ];

        $this->update([
            'ai_analysis' => $analysis,
            'ai_analyzed_at' => now()->toISOString(),
        ]);

        return $analysis;
    }

    private function analyzeEngagementPattern()
    {
        // ZenithaLMS: Analyze user's engagement pattern
        $user = $this->user;
        $reply = $this->forumReply;
        
        $pattern = [
            'frequency' => $this->calculateLikeFrequency($user),
            'timing' => $this->analyzeLikeTiming($reply),
            'consistency' => $this->calculateLikeConsistency($user),
            'diversity' => $this->calculateLikeDiversity($user),
        ];

        // Determine engagement level
        if ($pattern['frequency'] > 0.8 && $pattern['consistency'] > 0.7) {
            $pattern['level'] = 'high';
        } elseif ($pattern['frequency'] > 0.5 && $pattern['consistency'] > 0.5) {
            $pattern['level'] = 'medium';
        } else {
            $pattern['level'] = 'low';
        }

        return $pattern;
    }

    private function calculateLikeFrequency($user)
    {
        // ZenithaLMS: Calculate how frequently user likes content
        $totalLikes = ForumReplyLike::where('user_id', $user->id)->count();
        $totalReplies = ForumReply::count();
        
        if ($totalReplies === 0) {
            return 0;
        }
        
        return $totalLikes / $totalReplies;
    }

    private function analyzeLikeTiming($reply)
    {
        // ZenithaLMS: Analyze timing of the like
        $replyCreatedAt = $reply->created_at;
        $likedAt = $this->liked_at;
        
        if (!$likedAt) {
            return 'not_liked';
        }
        
        $timeDiff = $likedAt->diffInHours($replyCreatedAt);
        
        if ($timeDiff < 1) {
            return 'immediate';
        } elseif ($timeDiff < 24) {
            return 'same_day';
        } elseif ($timeDiff < 168) {
            return 'same_week';
        } else {
            return 'delayed';
        }
    }

    private function calculateLikeConsistency($user)
    {
        // ZenithaLMS: Calculate consistency of user's likes
        $likes = ForumReplyLike::where('user_id', $user->id)
            ->orderBy('liked_at', 'asc')
            ->get();
        
        if ($likes->count() < 2) {
            return 0.5; // Not enough data
        }
        
        $intervals = [];
        for ($i = 1; $i < $likes->count(); $i++) {
            $intervals[] = $likes[$i]->liked_at->diffInHours($likes[$i-1]->liked_at);
        }
        
        $avgInterval = array_sum($intervals) / count($intervals);
        
        // Consistency score (lower intervals = more consistent)
        if ($avgInterval < 24) {
            return 0.8;
        } elseif ($avgInterval < 72) {
            return 0.6;
        } elseif ($avgInterval < 168) {
            return 0.4;
        } else {
            return 0.2;
        }
    }

    private function calculateLikeDiversity($user)
    {
        // ZenithaLMS: Calculate diversity of user's likes
        $likedCategories = ForumReplyLike::where('user_id', $user->id)
            ->with('forumReply.forum.category')
            ->get()
            ->pluck('forumReply.forum.category')
            ->unique()
            ->count();
        
        $totalCategories = Forum::distinct('category')->count();
        
        if ($totalCategories === 0) {
            return 0;
        }
        
        return $likedCategories / $totalCategories;
    }

    private function analyzeUserInteractionType()
    {
        // ZenithaLMS: Analyze user's interaction type
        $user = $this->user;
        
        $type = [
            'primary' => 'passive_reader',
            'interaction_style' => 'selective',
            'engagement_depth' => 'surface',
        ];

        // Analyze user's overall forum activity
        $userPosts = Forum::where('user_id', $user->id)->count();
        $userReplies = ForumReply::where('user_id', $user->id)->count();
        $userLikes = ForumReplyLike::where('user_id', $user->id)->count();

        $totalInteractions = $userPosts + $userReplies + $userLikes;
        
        if ($totalInteractions === 0) {
            return $type;
        }

        // Determine interaction style
        $likeRatio = $userLikes / $totalInteractions;
        $replyRatio = $userReplies / $totalInteractions;
        $postRatio = $userPosts / $totalInteractions;

        if ($likeRatio > 0.7) {
            $type['interaction_style'] = 'supportive';
            $type['engagement_depth'] = 'surface';
        } elseif ($replyRatio > 0.5) {
            $type['interaction_style'] = 'participative';
            $type['engagement_depth'] = 'moderate';
        } elseif ($postRatio > 0.3) {
            $type['primary'] = 'content_creator';
            $type['engagement_depth'] = 'deep';
        }

        return $type;
    }

    private function analyzeCommunityValue()
    {
        // ZenithaLMS: Analyze community value of the like
        $reply = $this->forumReply;
        $forum = $reply->forum;
        
        $value = [
            'quality_score' => $this->calculateQualityScore($reply),
            'relevance_score' => $this->calculateRelevanceScore($forum),
            'engagement_potential' => $this->calculateEngagementPotential($reply),
            'community_impact' => $this->calculateCommunityImpact($reply),
        ];

        // Calculate overall value score
        $value['overall_score'] = (
            $value['quality_score'] * 0.3 +
            $value['relevance_score'] * 0.3 +
            $value['engagement_potential'] * 0.2 +
            $value['community_impact'] * 0.2
        );

        return $value;
    }

    private function calculateQualityScore($reply)
    {
        // ZenithaLMS: Calculate quality score of the reply
        $score = 0.5; // Base score
        
        $content = $reply->content;
        $contentLength = strlen($content);
        
        // Length factor
        if ($contentLength > 50) {
            $score += 0.1;
        } elseif ($contentLength > 200) {
            $score += 0.2;
        }
        
        // Structure factor
        if (preg_match('/\n\n/', $content)) {
            $score += 0.1;
        }
        
        // Detail factor
        if (preg_match('/\b(because|since|therefore|however|although|in addition)\b/i', $content)) {
            $score += 0.1;
        }
        
        // Professionalism factor
        if (preg_match('/\b(I think|I believe|In my opinion)\b/i', $content)) {
            $score += 0.1;
        }
        
        return min(1.0, $score);
    }

    private function calculateRelevanceScore($forum)
    {
        // ZenithaLMS: Calculate relevance score of the forum
        $score = 0.5; // Base score
        
        // Recency factor
        $daysSinceCreated = $forum->created_at->diffInDays(now());
        if ($daysSinceCreated < 7) {
            $score += 0.2;
        } elseif ($daysSinceCreated < 30) {
            $score += 0.1;
        }
        
        // Activity factor
        $replyCount = $forum->reply_count;
        if ($replyCount > 10) {
            $score += 0.2;
        } elseif ($replyCount > 5) {
            $score += 0.1;
        }
        
        // View factor
        $viewCount = $forum->view_count;
        if ($viewCount > 100) {
            $score += 0.1;
        } elseif ($viewCount > 50) {
            $score += 0.05;
        }
        
        return min(1.0, $score);
    }

    private function calculateEngagementPotential($reply)
    {
        // ZenithaLMS: Calculate engagement potential of the reply
        $score = 0.5; // Base score
        
        // Question factor
        if (preg_match('/\b(\?|what|how|why|when|where)\b/i', $reply->content)) {
            $score += 0.2;
        }
        
        // Call to action factor
        if (preg_match('/\b(what do you think|how about|try this|consider)\b/i', $reply->content)) {
            $score += 0.2;
        }
        
        // Resource sharing factor
        if (preg_match('/\b(http|www\.|link|check out)\b/i', $reply->content)) {
            $score += 0.1;
        }
        
        return min(1.0, $score);
    }

    private function calculateCommunityImpact($reply)
    {
        // ZenithaLMS: Calculate community impact of the reply
        $score = 0.5; // Base score
        
        // Helpfulness factor
        if (preg_match('/\b(helpful|useful\s+information|good\s+point|great\s+idea)\b/i', $reply->content)) {
            $score += 0.2;
        }
        
        // Encouragement factor
        if (preg_match('/\b(good\s+job|well\s+done|keep\s+up|great\s+work)\b/i', $reply->content)) {
            $score += 0.2;
        }
        
        // Solution factor
        if (preg_match('/\b(solution|answer|fix|resolve|solve)\b/i', $reply->content)) {
            $score += 0.1;
        }
        
        return min(1.0, $score);
    }

    private function analyzeSentimentCorrelation()
    {
        // ZenithaLMS: Analyze correlation between like and content sentiment
        $reply = $this->forumReply;
        $replySentiment = $reply->ai_sentiment['sentiment'] ?? 'neutral';
        
        $correlation = [
            'sentiment_match' => 'unknown',
            'correlation_strength' => 0.5,
            'analysis' => 'No sentiment data available',
        ];

        // If we have sentiment data for the reply
        if ($replySentiment !== 'neutral') {
            // Analyze if the like aligns with the sentiment
            $likeSentiment = $this->predictLikeSentiment($reply);
            
            if ($likeSentiment === $replySentiment) {
                $correlation['sentiment_match'] = 'aligned';
                $correlation['correlation_strength'] = 0.8;
                $correlation['analysis'] = 'Like sentiment matches content sentiment';
            } else {
                $correlation['sentiment_match'] = 'misaligned';
                $correlation['correlation_strength'] = 0.3;
                $correlation['analysis'] = 'Like sentiment differs from content sentiment';
            }
        }

        return $correlation;
    }

    private function predictLikeSentiment($reply)
    {
        // ZenithaLMS: Predict sentiment of the like based on content
        $content = strtolower($reply->content);
        
        $positiveWords = ['great', 'excellent', 'helpful', 'useful', 'good', 'amazing', 'wonderful', 'perfect', 'brilliant', 'outstanding'];
        $negativeWords = ['bad', 'terrible', 'awful', 'horrible', 'useless', 'poor', 'wrong', 'incorrect', 'disappointing'];
        
        $positiveCount = 0;
        $negativeCount = 0;
        
        foreach ($positiveWords as $word) {
            $positiveCount += substr_count($content, $word);
        }
        
        foreach ($negativeWords as $word) {
            $negativeCount += substr_count($content, $word);
        }
        
        if ($positiveCount > $negativeCount) {
            return 'positive';
        } elseif ($negativeCount > $positiveCount) {
            return 'negative';
        }
        
        return 'neutral';
    }

    private function analyzeTimingAnalysis()
    {
        // ZenithaLMS: Analyze timing of the like
        $reply = $this->forumReply;
        $replyCreatedAt = $reply->created_at;
        $likedAt = $this->liked_at;
        
        $analysis = [
            'reply_age_at_like' => $likedAt ? $likedAt->diffInHours($replyCreatedAt) : null,
            'peak_engagement_time' => $this->calculatePeakEngagementTime($reply),
            'engagement_velocity' => $this->calculateEngagementVelocity($reply),
            'optimal_timing' => false,
        ];

        // Determine if timing was optimal
        $replyAge = $replyCreatedAt->diffInHours(now());
        $likeAge = $analysis['reply_age_at_like'];
        
        if ($likeAge !== null) {
            // Optimal timing is within first 24 hours for active discussions
            if ($replyAge < 24 && $likeAge < 6) {
                $analysis['optimal_timing'] = true;
                $analysis['timing_quality'] = 'excellent';
            } elseif ($replyAge < 24 && $likeAge < 12) {
                $analysis['optimal_timing'] = true;
                $analysis['timing_quality'] = 'good';
            } elseif ($replyAge < 72) {
                $analysis['optimal_timing'] = false;
                $analysis['timing_quality'] = 'fair';
            } else {
                $analysis['optimal_timing'] = false;
                $analysis['timing_quality'] = 'poor';
            }
        }

        return $analysis;
    }

    private function calculatePeakEngagementTime($reply)
    {
        // ZenithaLMS: Calculate peak engagement time for the reply
        $likes = ForumReplyLike::where('forum_reply_id', $reply->id)
            ->orderBy('liked_at', 'asc')
            ->get();
        
        if ($likes->count() === 0) {
            return null;
        }
        
        // Find the time with most likes in a 24-hour window
        $peakTime = null;
        $maxLikes = 0;
        
        foreach ($likes as $like) {
            $windowStart = $like->liked_at->copy()->subHours(12);
            $windowEnd = $like->liked_at->copy()->addHours(12);
            
            $windowLikes = $likes->filter(function ($like) use ($windowStart, $windowEnd) {
                return $like->liked_at >= $windowStart && $like->liked_at <= $windowEnd;
            })->count();
            
            if ($windowLikes > $maxLikes) {
                $maxLikes = $windowLikes;
                $peakTime = $like->liked_at;
            }
        }
        
        return $peakTime;
    }

    private function calculateEngagementVelocity($reply)
    {
        // ZenithaLMS: Calculate engagement velocity
        $likes = ForumReplyLike::where('forum_reply_id', $reply->id)->get();
        
        if ($likes->count() < 2) {
            return 0;
        }
        
        $firstLike = $likes->first();
        $lastLike = $likes->last();
        
        $timeDiff = $firstLike->liked_at->diffInHours($lastLike->liked_at);
        $totalTime = $firstLike->liked_at->diffInHours($reply->created_at);
        
        if ($totalTime === 0) {
            return 0;
        }
        
        return $likes->count() / $totalTime;
    }

    private function calculateInfluenceScore()
    {
        // ZenithaLMS: Calculate influence score of the like
        $score = 0.5; // Base score
        
        $analysis = $this->ai_analysis ?? [];
        
        // Add community value score
        if (isset($analysis['community_value'])) {
            $score += $analysis['community_value']['overall_score'] * 0.3;
        }
        
        // Add engagement pattern score
        if (isset($analysis['engagement_pattern'])) {
            $pattern = $analysis['engagement_pattern'];
            
            if ($pattern['level'] === 'high') {
                $score += 0.2;
            } elseif ($pattern['level'] === 'medium') {
                $score += 0.1;
            }
        }
        
        // Add timing analysis score
        if (isset($analysis['timing_analysis'])) {
            $timing = $analysis['timing_analysis'];
            
            if ($timing['optimal_timing']) {
                $score += 0.1;
            }
        }
        
        return min(1.0, $score);
    }

    /**
     * ZenithaLMS: Static Methods
     */
    public static function getEngagementLevels()
    {
        return [
            'high' => 'High Engagement',
            'medium' => 'Medium Engagement',
            'low' => 'Low Engagement',
        ];
    }

    public static function getInteractionStyles()
    {
        return [
            'supportive' => 'Supportive',
            'participative' => 'Participative',
            'selective' => 'Selective',
            'passive_reader' => 'Passive Reader',
            'content_creator' => 'Content Creator',
        ];
    }

    public static function getEngagementDepths()
    {
        return [
            'surface' => 'Surface',
            'moderate' => 'Moderate',
            'deep' => 'Deep',
        ];
    }

    public static function getTimingQualities()
    {
        return [
            'excellent' => 'Excellent Timing',
            'good' => 'Good Timing',
            'fair' => 'Fair Timing',
            'poor' => 'Poor Timing',
        ];
    }
}
