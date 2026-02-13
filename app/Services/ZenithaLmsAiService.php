<?php

namespace App\Services;

use App\Models\User;
use App\Models\Course;
use App\Models\Quiz;
use App\Models\Forum;
use App\Models\Blog;
use App\Models\Assignment;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ZenithaLmsAiService
{
    /**
     * AI Service Configuration
     */
    private $apiKey;
    private $baseUrl;
    private $model;
    
    public function __construct()
    {
        $this->apiKey = config('zenithalms.ai.api_key');
        $this->baseUrl = config('zenithalms.ai.base_url', 'https://api.openai.com/v1');
        $this->model = config('zenithalms.ai.model', 'gpt-3.5-turbo');
    }
    
    /**
     * Generate AI-powered course recommendations
     */
    public function generateCourseRecommendations($userId, $limit = 10)
    {
        $user = User::findOrFail($userId);
        
        // Get user's learning history and preferences
        $enrollments = $user->enrollments()->with('course')->get();
        $quizAttempts = $user->quizAttempts()->with('quiz')->get();
        $forumPosts = $user->forumPosts()->get();
        
        // Build user profile
        $userProfile = [
            'completed_courses' => $enrollments->where('status', 'completed')->pluck('course.title')->toArray(),
            'current_courses' => $enrollments->where('status', 'active')->pluck('course.title')->toArray(),
            'quiz_performance' => $quizAttempts->map(function ($attempt) {
                return [
                    'quiz' => $attempt->quiz->title,
                    'score' => $attempt->percentage,
                    'difficulty' => $attempt->quiz->difficulty_level,
                ];
            })->toArray(),
            'interests' => $this->extractInterests($forumPosts),
            'skill_level' => $this->calculateSkillLevel($enrollments, $quizAttempts),
        ];
        
        // Generate AI recommendations
        $prompt = $this->buildRecommendationPrompt($userProfile);
        
        try {
            $response = $this->callAiApi([
                'model' => $this->model,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are an AI assistant for ZenithaLMS, an advanced learning management system. Provide personalized course recommendations based on user learning history and preferences.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'temperature' => 0.7,
                'max_tokens' => 1000,
            ]);
            
            return $this->parseRecommendations($response, $limit);
            
        } catch (\Exception $e) {
            Log::error('AI recommendation error: ' . $e->getMessage());
            return $this->getFallbackRecommendations($userId, $limit);
        }
    }
    
    /**
     * Analyze quiz performance and provide insights
     */
    public function analyzeQuizPerformance($quizAttemptId)
    {
        $attempt = \App\Models\QuizAttempt::with(['quiz', 'answers.question'])->findOrFail($quizAttemptId);
        
        // Build performance analysis
        $performanceData = [
            'quiz_title' => $attempt->quiz->title,
            'score' => $attempt->percentage,
            'time_taken' => $attempt->time_taken_seconds,
            'questions' => $attempt->answers->map(function ($answer) {
                return [
                    'question_text' => $answer->question->question_text,
                    'is_correct' => $answer->is_correct,
                    'question_type' => $answer->question->question_type,
                    'difficulty' => $answer->question->difficulty_level,
                    'points' => $answer->points_earned,
                ];
            })->toArray(),
            'user_previous_attempts' => $attempt->user->quizAttempts()
                ->where('quiz_id', $attempt->quiz_id)
                ->where('id', '<', $attempt->id)
                ->get()
                ->map(function ($prevAttempt) {
                    return [
                        'score' => $prevAttempt->percentage,
                        'time_taken' => $prevAttempt->time_taken_seconds,
                    ];
                })->toArray(),
        ];
        
        // Generate AI analysis
        $prompt = $this->buildQuizAnalysisPrompt($performanceData);
        
        try {
            $response = $this->callAiApi([
                'model' => $this->model,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are an AI assistant for ZenithaLMS. Analyze quiz performance and provide detailed insights, strengths, weaknesses, and improvement suggestions.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'temperature' => 0.3,
                'max_tokens' => 1500,
            ]);
            
            return $this->parseQuizAnalysis($response);
            
        } catch (\Exception $e) {
            Log::error('AI quiz analysis error: ' . $e->getMessage());
            return $this->getFallbackQuizAnalysis($attempt);
        }
    }
    
    /**
     * Generate AI-powered content summaries
     */
    public function generateContentSummary($content, $type = 'course', $maxLength = 200)
    {
        $prompt = $this->buildContentSummaryPrompt($content, $type, $maxLength);
        
        try {
            $response = $this->callAiApi([
                'model' => $this->model,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are an AI assistant for ZenithaLMS. Generate concise and informative summaries for educational content.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'temperature' => 0.5,
                'max_tokens' => 300,
            ]);
            
            return $this->parseContentSummary($response);
            
        } catch (\Exception $e) {
            Log::error('AI content summary error: ' . $e->getMessage());
            return $this->getFallbackContentSummary($content, $maxLength);
        }
    }
    
    /**
     * Analyze forum sentiment and engagement
     */
    public function analyzeForumPost($forumPostId)
    {
        $post = \App\Models\Forum::with(['user', 'replies.user'])->findOrFail($forumPostId);
        
        $forumData = [
            'title' => $post->title,
            'content' => $post->content,
            'author' => $post->user->name,
            'category' => $post->category->name ?? 'General',
            'replies' => $post->replies->map(function ($reply) {
                return [
                    'content' => $reply->content,
                    'author' => $reply->user->name,
                    'created_at' => $reply->created_at->format('Y-m-d H:i:s'),
                ];
            })->toArray(),
            'engagement_metrics' => [
                'view_count' => $post->view_count,
                'reply_count' => $post->reply_count,
                'like_count' => $post->like_count,
                'created_at' => $post->created_at->format('Y-m-d H:i:s'),
            ],
        ];
        
        $prompt = $this->buildForumAnalysisPrompt($forumData);
        
        try {
            $response = $this->callAiApi([
                'model' => $this->model,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are an AI assistant for ZenithaLMS. Analyze forum posts for sentiment, engagement potential, and provide insights.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'temperature' => 0.4,
                'max_tokens' => 800,
            ]);
            
            return $this->parseForumAnalysis($response);
            
        } catch (\Exception $e) {
            Log::error('AI forum analysis error: ' . $e->getMessage());
            return $this->getFallbackForumAnalysis($post);
        }
    }
    
    /**
     * Generate personalized learning path
     */
    public function generateLearningPath($userId, $goal = 'general')
    {
        $user = User::findOrFail($userId);
        
        $userData = [
            'current_skills' => $this->assessCurrentSkills($user),
            'learning_history' => $this->getLearningHistory($user),
            'preferences' => $this->getUserPreferences($user),
            'goal' => $goal,
            'time_commitment' => $this->estimateTimeCommitment($user),
        ];
        
        $prompt = $this->buildLearningPathPrompt($userData);
        
        try {
            $response = $this->callAiApi([
                'model' => $this->model,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are an AI assistant for ZenithaLMS. Generate personalized learning paths based on user skills, preferences, and goals.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'temperature' => 0.6,
                'max_tokens' => 2000,
            ]);
            
            return $this->parseLearningPath($response);
            
        } catch (\Exception $e) {
            Log::error('AI learning path error: ' . $e->getMessage());
            return $this->getFallbackLearningPath($user, $goal);
        }
    }
    
    /**
     * Call AI API
     */
    private function callAiApi($data)
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json',
        ])->post($this->baseUrl . '/chat/completions', $data);
        
        if (!$response->successful()) {
            throw new \Exception('AI API call failed: ' . $response->body());
        }
        
        return $response->json();
    }
    
    /**
     * Build recommendation prompt
     */
    private function buildRecommendationPrompt($userProfile)
    {
        return "Based on the following user profile, recommend 10 courses that would be most beneficial:

User Profile:
- Completed courses: " . implode(', ', $userProfile['completed_courses']) . "
- Current courses: " . implode(', ', $userProfile['current_courses']) . "
- Quiz performance: " . json_encode($userProfile['quiz_performance']) . "
- Interests: " . implode(', ', $userProfile['interests']) . "
- Skill level: " . $userProfile['skill_level'] . "

Please provide recommendations in JSON format with the following structure:
{
  \"recommendations\": [
    {
      \"course_title\": \"Course Name\",
      \"reason\": \"Why this course is recommended\",
      \"difficulty_level\": \"beginner|intermediate|advanced\",
      \"estimated_completion_time\": \"X hours\",
      \"prerequisites\": [\"Prerequisite 1\", \"Prerequisite 2\"],
      \"career_benefits\": \"How this helps career-wise\",
      \"confidence_score\": 0.85
    }
  ]
}";
    }
    
    /**
     * Build quiz analysis prompt
     */
    private function buildQuizAnalysisPrompt($performanceData)
    {
        return "Analyze the following quiz performance and provide detailed insights:

Quiz Performance Data:
- Quiz: " . $performanceData['quiz_title'] . "
- Score: " . $performanceData['score'] . "%
- Time taken: " . $performanceData['time_taken'] . " seconds
- Questions: " . json_encode($performanceData['questions']) . "
- Previous attempts: " . json_encode($performanceData['user_previous_attempts']) . "

Please provide analysis in JSON format with the following structure:
{
  \"overall_performance\": \"excellent|good|average|needs_improvement\",
  \"strengths\": [\"Strength 1\", \"Strength 2\"],
  \"weaknesses\": [\"Weakness 1\", \"Weakness 2\"],
  \"improvement_suggestions\": [\"Suggestion 1\", \"Suggestion 2\"],
  \"next_steps\": [\"Next step 1\", \"Next step 2\"],
  \"confidence_score\": 0.85
}";
    }
    
    /**
     * Build content summary prompt
     */
    private function buildContentSummaryPrompt($content, $type, $maxLength)
    {
        return "Generate a concise summary (max {$maxLength} characters) for the following {$type} content:

Content: {$content}

The summary should:
- Be informative and engaging
- Highlight key points
- Be suitable for display in course listings
- Maintain a professional tone

Please provide the summary directly without any additional formatting.";
    }
    
    /**
     * Build forum analysis prompt
     */
    private function buildForumAnalysisPrompt($forumData)
    {
        return "Analyze the following forum post for sentiment, engagement potential, and provide insights:

Forum Data:
- Title: " . $forumData['title'] . "
- Content: " . $forumData['content'] . "
- Author: " . $forumData['author'] . "
- Category: " . $forumData['category'] . "
- Replies: " . json_encode($forumData['replies']) . "
- Engagement metrics: " . json_encode($forumData['engagement_metrics']) . "

Please provide analysis in JSON format with the following structure:
{
  \"sentiment\": \"positive|neutral|negative\",
  \"sentiment_score\": 0.75,
  \"engagement_potential\": \"high|medium|low\",
  \"key_topics\": [\"Topic 1\", \"Topic 2\"],
  \"suggested_responses\": [\"Response suggestion 1\", \"Response suggestion 2\"],
  \"moderation_needed\": true|false,
  \"confidence_score\": 0.85
}";
    }
    
    /**
     * Build learning path prompt
     */
    private function buildLearningPathPrompt($userData)
    {
        return "Generate a personalized learning path based on the following user data:

User Data:
- Current skills: " . json_encode($userData['current_skills']) . "
- Learning history: " . json_encode($userData['learning_history']) . "
- Preferences: " . json_encode($userData['preferences']) . "
- Goal: " . $userData['goal'] . "
- Time commitment: " . $userData['time_commitment'] . " hours per week

Please provide a learning path in JSON format with the following structure:
{
  \"path_name\": \"Personalized Learning Path\",
  \"estimated_duration\": \"X weeks\",
  \"difficulty_progression\": \"beginner to advanced\",
  \"modules\": [
    {
      \"module_title\": \"Module Name\",
      \"description\": \"Module description\",
      \"courses\": [\"Course 1\", \"Course 2\"],
      \"estimated_time\": \"X hours\",
      \"prerequisites\": [\"Prerequisite 1\"],
      \"learning_objectives\": [\"Objective 1\", \"Objective 2\"]
    }
  ],
  \"milestones\": [
    {
      \"milestone\": \"Milestone description\",
      \"estimated_completion\": \"Week X\",
      \"skills_gained\": [\"Skill 1\", \"Skill 2\"]
    }
  ],
  \"success_metrics\": [\"Metric 1\", \"Metric 2\"]
}";
    }
    
    /**
     * Parse AI recommendations
     */
    private function parseRecommendations($response, $limit)
    {
        try {
            $content = $response['choices'][0]['message']['content'];
            $data = json_decode($content, true);
            
            if (isset($data['recommendations'])) {
                return array_slice($data['recommendations'], 0, $limit);
            }
        } catch (\Exception $e) {
            Log::error('Error parsing AI recommendations: ' . $e->getMessage());
        }
        
        return [];
    }
    
    /**
     * Parse quiz analysis
     */
    private function parseQuizAnalysis($response)
    {
        try {
            $content = $response['choices'][0]['message']['content'];
            return json_decode($content, true);
        } catch (\Exception $e) {
            Log::error('Error parsing AI quiz analysis: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Parse content summary
     */
    private function parseContentSummary($response)
    {
        try {
            return $response['choices'][0]['message']['content'];
        } catch (\Exception $e) {
            Log::error('Error parsing AI content summary: ' . $e->getMessage());
            return '';
        }
    }
    
    /**
     * Parse forum analysis
     */
    private function parseForumAnalysis($response)
    {
        try {
            $content = $response['choices'][0]['message']['content'];
            return json_decode($content, true);
        } catch (\Exception $e) {
            Log::error('Error parsing AI forum analysis: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Parse learning path
     */
    private function parseLearningPath($response)
    {
        try {
            $content = $response['choices'][0]['message']['content'];
            return json_decode($content, true);
        } catch (\Exception $e) {
            Log::error('Error parsing AI learning path: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Helper methods
     */
    private function extractInterests($forumPosts)
    {
        // Extract keywords from forum posts to identify interests
        $interests = [];
        foreach ($forumPosts as $post) {
            // Simple keyword extraction (can be enhanced with NLP)
            $words = explode(' ', strtolower($post->content));
            $interests = array_merge($interests, array_filter($words, function($word) {
                return strlen($word) > 4 && !in_array($word, ['that', 'this', 'with', 'from', 'they', 'have', 'been']);
            }));
        }
        return array_unique($interests);
    }
    
    private function calculateSkillLevel($enrollments, $quizAttempts)
    {
        // Calculate user's skill level based on performance
        $totalScore = $quizAttempts->sum('percentage');
        $attemptCount = $quizAttempts->count();
        
        if ($attemptCount === 0) {
            return 'beginner';
        }
        
        $avgScore = $totalScore / $attemptCount;
        
        if ($avgScore >= 85) {
            return 'advanced';
        } elseif ($avgScore >= 70) {
            return 'intermediate';
        } else {
            return 'beginner';
        }
    }
    
    private function assessCurrentSkills($user)
    {
        // Assess user's current skills based on completed courses and performance
        $completedCourses = $user->enrollments()->where('status', 'completed')->with('course')->get();
        $skills = [];
        
        foreach ($completedCourses as $enrollment) {
            $course = $enrollment->course;
            $skills[] = $course->title; // Can be enhanced with skill extraction
        }
        
        return $skills;
    }
    
    private function getLearningHistory($user)
    {
        // Get user's learning history
        return $user->enrollments()->with('course')->get()->map(function ($enrollment) {
            return [
                'course' => $enrollment->course->title,
                'status' => $enrollment->status,
                'progress' => $enrollment->progress_percentage,
                'completed_at' => $enrollment->completed_at?->format('Y-m-d'),
            ];
        })->toArray();
    }
    
    private function getUserPreferences($user)
    {
        // Get user's learning preferences
        return [
            'preferred_difficulty' => 'intermediate',
            'preferred_duration' => '4-6 weeks',
            'preferred_format' => 'mixed',
            'learning_style' => 'visual',
        ];
    }
    
    private function estimateTimeCommitment($user)
    {
        // Estimate user's time commitment based on activity
        $weeklyHours = 10; // Default estimation
        return $weeklyHours;
    }
    
    /**
     * Fallback methods when AI is unavailable
     */
    private function getFallbackRecommendations($userId, $limit)
    {
        // Return popular courses as fallback
        return Course::where('is_published', true)
            ->orderBy('enrollments_count', 'desc')
            ->take($limit)
            ->get()
            ->map(function ($course) {
                return [
                    'course_title' => $course->title,
                    'reason' => 'Popular course with high enrollment',
                    'difficulty_level' => $course->level ?? 'intermediate',
                    'estimated_completion_time' => $course->duration ?? '20 hours',
                    'prerequisites' => [],
                    'career_benefits' => 'Enhance your skills and knowledge',
                    'confidence_score' => 0.7,
                ];
            })->toArray();
    }
    
    private function getFallbackQuizAnalysis($attempt)
    {
        return [
            'overall_performance' => $attempt->percentage >= 70 ? 'good' : 'needs_improvement',
            'strengths' => $attempt->percentage >= 80 ? ['Good performance'] : [],
            'weaknesses' => $attempt->percentage < 60 ? ['Need more practice'] : [],
            'improvement_suggestions' => ['Review course materials', 'Practice similar questions'],
            'next_steps' => ['Continue learning', 'Take next quiz'],
            'confidence_score' => 0.6,
        ];
    }
    
    private function getFallbackContentSummary($content, $maxLength)
    {
        return substr(strip_tags($content), 0, $maxLength) . '...';
    }
    
    private function getFallbackForumAnalysis($post)
    {
        return [
            'sentiment' => 'neutral',
            'sentiment_score' => 0.5,
            'engagement_potential' => 'medium',
            'key_topics' => ['General discussion'],
            'suggested_responses' => ['Thank you for sharing'],
            'moderation_needed' => false,
            'confidence_score' => 0.5,
        ];
    }
    
    private function getFallbackLearningPath($user, $goal)
    {
        return [
            'path_name' => 'General Learning Path',
            'estimated_duration' => '8 weeks',
            'difficulty_progression' => 'beginner to intermediate',
            'modules' => [
                [
                    'module_title' => 'Introduction',
                    'description' => 'Basic concepts and fundamentals',
                    'courses' => ['Introduction Course'],
                    'estimated_time' => '10 hours',
                    'prerequisites' => [],
                    'learning_objectives' => ['Understand basics', 'Learn fundamentals'],
                ]
            ],
            'milestones' => [
                [
                    'milestone' => 'Complete introduction',
                    'estimated_completion' => 'Week 2',
                    'skills_gained' => ['Basic understanding'],
                ]
            ],
            'success_metrics' => ['Complete all modules', 'Pass final assessment'],
        ];
    }
}
