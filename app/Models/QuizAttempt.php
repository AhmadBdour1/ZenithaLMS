<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class QuizAttempt extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'quiz_id',
        'attempt_number',
        'status',
        'score',
        'percentage',
        'started_at',
        'completed_at',
        'time_taken_minutes',
        'answers',
        'ai_insights',
    ];

    protected $casts = [
        'score' => 'decimal:2',
        'percentage' => 'decimal:2',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'time_taken_minutes' => 'integer',
        'answers' => 'array',
        'ai_insights' => 'array',
    ];

    /**
     * ZenithaLMS: Attempt Status Constants
     */
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_COMPLETED = 'completed';
    const STATUS_PASSED = 'passed';
    const STATUS_FAILED = 'failed';
    const STATUS_ABANDONED = 'abandoned';

    /**
     * ZenithaLMS: Relationships
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function quiz()
    {
        return $this->belongsTo(Quiz::class);
    }

    public function answers()
    {
        return $this->hasMany(QuizAttemptAnswer::class);
    }

    /**
     * ZenithaLMS: Scopes
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByQuiz($query, $quizId)
    {
        return $query->where('quiz_id', $quizId);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopePassed($query)
    {
        return $query->where('status', self::STATUS_PASSED);
    }

    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * ZenithaLMS: Methods
     */
    public function isCompleted()
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isInProgress()
    {
        return $this->status === self::STATUS_IN_PROGRESS;
    }

    public function isPassed()
    {
        return $this->status === self::STATUS_PASSED || 
               ($this->isCompleted() && $this->percentage >= $this->quiz->passing_score);
    }

    public function isFailed()
    {
        return $this->status === self::STATUS_FAILED || 
               ($this->isCompleted() && $this->percentage < $this->quiz->passing_score);
    }

    public function getFormattedScore()
    {
        return number_format($this->score, 2);
    }

    public function getFormattedPercentage()
    {
        return number_format($this->percentage, 1) . '%';
    }

    public function getTimeTakenFormatted()
    {
        if ($this->time_taken_minutes < 60) {
            return $this->time_taken_minutes . ' min';
        } else {
            $hours = floor($this->time_taken_minutes / 60);
            $minutes = $this->time_taken_minutes % 60;
            return $hours . 'h ' . $minutes . 'm';
        }
    }

    public function getCorrectAnswersCount()
    {
        return $this->answers()->where('is_correct', true)->count();
    }

    public function getIncorrectAnswersCount()
    {
        return $this->answers()->where('is_correct', false)->count();
    }

    public function getTotalQuestionsCount()
    {
        return $this->answers()->count();
    }

    public function getAccuracy()
    {
        $total = $this->getTotalQuestionsCount();
        if ($total === 0) {
            return 0;
        }
        
        return ($this->getCorrectAnswersCount() / $total) * 100;
    }

    /**
     * ZenithaLMS: AI-Powered Methods
     */
    public function generateAiInsights()
    {
        // ZenithaLMS: Generate AI-powered insights for the attempt
        $insights = [
            'performance_level' => $this->determinePerformanceLevel(),
            'strength_areas' => $this->identifyStrengthAreas(),
            'improvement_areas' => $this->identifyImprovementAreas(),
            'learning_recommendations' => $this->generateLearningRecommendations(),
            'next_steps' => $this->generateNextSteps(),
            'time_analysis' => $this->analyzeTimeUsage(),
            'question_analysis' => $this->analyzeQuestionPerformance(),
            'progress_trend' => $this->calculateProgressTrend(),
        ];

        $this->update([
            'ai_insights' => $insights,
        ]);

        return $insights;
    }

    private function determinePerformanceLevel()
    {
        $percentage = $this->percentage;
        
        if ($percentage >= 90) {
            return 'excellent';
        } elseif ($percentage >= 80) {
            return 'good';
        } elseif ($percentage >= 70) {
            return 'average';
        } elseif ($percentage >= 60) {
            return 'below_average';
        }
        
        return 'poor';
    }

    private function identifyStrengthAreas()
    {
        $strengths = [];
        $answers = $this->answers()->with('question')->get();
        
        foreach ($answers as $answer) {
            if ($answer->is_correct) {
                $category = $answer->question->category ?? 'general';
                if (!isset($strengths[$category])) {
                    $strengths[$category] = 0;
                }
                $strengths[$category]++;
            }
        }
        
        arsort($strengths);
        return array_keys($strengths);
    }

    private function identifyImprovementAreas()
    {
        $improvements = [];
        $answers = $this->answers()->with('question')->get();
        
        foreach ($answers as $answer) {
            if (!$answer->is_correct) {
                $category = $answer->question->category ?? 'general';
                if (!isset($improvements[$category])) {
                    $improvements[$category] = 0;
                }
                $improvements[$category]++;
            }
        }
        
        arsort($improvements);
        return array_keys($improvements);
    }

    private function generateLearningRecommendations()
    {
        $recommendations = [];
        $performanceLevel = $this->determinePerformanceLevel();
        
        if ($performanceLevel === 'poor' || $performanceLevel === 'below_average') {
            $recommendations[] = 'Review basic concepts and fundamentals';
            $recommendations[] = 'Practice with easier questions first';
            $recommendations[] = 'Consider taking prerequisite courses';
            $recommendations[] = 'Study the explanations for incorrect answers';
        } elseif ($performanceLevel === 'average') {
            $recommendations[] = 'Focus on weak areas identified in the analysis';
            $recommendations[] = 'Practice more questions to improve understanding';
            $recommendations[] = 'Review related course materials';
        } elseif ($performanceLevel === 'good') {
            $recommendations[] = 'Challenge yourself with advanced topics';
            $recommendations[] = 'Try teaching concepts to reinforce learning';
            $recommendations[] = 'Explore related subjects';
        } else { // excellent
            $recommendations[] = 'Move on to advanced topics and challenges';
            $recommendations[] = 'Consider mentoring other students';
            $recommendations[] = 'Explore specialized areas of interest';
        }
        
        return $recommendations;
    }

    private function generateNextSteps()
    {
        $nextSteps = [];
        
        if ($this->isPassed()) {
            $nextSteps[] = 'Congratulations! You passed this quiz';
            $nextSteps[] = 'Review the feedback and explanations';
            
            if ($this->attempt_number < $this->quiz->max_attempts) {
                $nextSteps[] = 'Try again to improve your score';
            }
            
            $nextSteps[] = 'Move on to the next topic or course';
            
            if ($this->quiz->course) {
                $nextSteps[] = 'Continue with the next lesson in the course';
            }
        } else {
            $nextSteps[] = 'Review the questions you got wrong';
            $nextSteps[] = 'Study the related course materials';
            
            if ($this->attempt_number < $this->quiz->max_attempts) {
                $nextSteps[] = 'Try the quiz again after studying';
            } else {
                $nextSteps[] = 'Consult with your instructor for additional help';
            }
        }
        
        return $nextSteps;
    }

    private function analyzeTimeUsage()
    {
        $timeAnalysis = [
            'total_time' => $this->time_taken_minutes,
            'average_per_question' => 0,
            'time_efficiency' => 'normal',
            'suggestions' => [],
        ];
        
        $totalQuestions = $this->getTotalQuestionsCount();
        if ($totalQuestions > 0) {
            $timeAnalysis['average_per_question'] = $this->time_taken_minutes / $totalQuestions;
        }
        
        // Analyze time efficiency
        $expectedTime = $this->quiz->time_limit_minutes ?? ($totalQuestions * 2); // 2 minutes per question as default
        
        if ($this->time_taken_minutes > $expectedTime * 1.5) {
            $timeAnalysis['time_efficiency'] = 'slow';
            $timeAnalysis['suggestions'][] = 'Try to answer questions more efficiently';
            $timeAnalysis['suggestions'][] = 'Practice time management during quizzes';
        } elseif ($this->time_taken_minutes < $expectedTime * 0.5) {
            $timeAnalysis['time_efficiency'] = 'fast';
            $timeAnalysis['suggestions'][] = 'Take more time to read questions carefully';
            $timeAnalysis['suggestions'][] = 'Double-check your answers before submitting';
        }
        
        return $timeAnalysis;
    }

    private function analyzeQuestionPerformance()
    {
        $questionAnalysis = [
            'correct_by_type' => [],
            'correct_by_difficulty' => [],
            'problematic_questions' => [],
        ];
        
        $answers = $this->answers()->with('question')->get();
        
        // Analyze by question type
        $typePerformance = [];
        foreach ($answers as $answer) {
            $type = $answer->question->question_type;
            if (!isset($typePerformance[$type])) {
                $typePerformance[$type] = ['correct' => 0, 'total' => 0];
            }
            $typePerformance[$type]['total']++;
            if ($answer->is_correct) {
                $typePerformance[$type]['correct']++;
            }
        }
        
        foreach ($typePerformance as $type => $data) {
            $questionAnalysis['correct_by_type'][$type] = [
                'correct' => $data['correct'],
                'total' => $data['total'],
                'percentage' => $data['total'] > 0 ? ($data['correct'] / $data['total']) * 100 : 0,
            ];
        }
        
        // Analyze by difficulty
        $difficultyPerformance = [];
        foreach ($answers as $answer) {
            $difficulty = $answer->question->difficulty_level;
            if (!isset($difficultyPerformance[$difficulty])) {
                $difficultyPerformance[$difficulty] = ['correct' => 0, 'total' => 0];
            }
            $difficultyPerformance[$difficulty]['total']++;
            if ($answer->is_correct) {
                $difficultyPerformance[$difficulty]['correct']++;
            }
        }
        
        foreach ($difficultyPerformance as $difficulty => $data) {
            $questionAnalysis['correct_by_difficulty'][$difficulty] = [
                'correct' => $data['correct'],
                'total' => $data['total'],
                'percentage' => $data['total'] > 0 ? ($data['correct'] / $data['total']) * 100 : 0,
            ];
        }
        
        // Identify problematic questions (most incorrect)
        $incorrectQuestions = $answers->where('is_correct', false)->sortByDesc('question.points')->take(5);
        foreach ($incorrectQuestions as $answer) {
            $questionAnalysis['problematic_questions'][] = [
                'question_id' => $answer->question_id,
                'question_text' => $answer->question->question_text,
                'points' => $answer->question->points,
                'explanation' => $answer->question->explanation,
            ];
        }
        
        return $questionAnalysis;
    }

    private function calculateProgressTrend()
    {
        // ZenithaLMS: Calculate progress trend compared to previous attempts
        $previousAttempts = QuizAttempt::where('user_id', $this->user_id)
            ->where('quiz_id', $this->quiz_id)
            ->where('attempt_number', '<', $this->attempt_number)
            ->where('status', self::STATUS_COMPLETED)
            ->orderBy('attempt_number', 'desc')
            ->limit(3)
            ->get();
        
        if ($previousAttempts->isEmpty()) {
            return [
                'trend' => 'no_data',
                'improvement' => 0,
                'average_improvement' => 0,
            ];
        }
        
        $previousScores = $previousAttempts->pluck('percentage')->toArray();
        $currentScore = $this->percentage;
        
        if (count($previousScores) === 1) {
            $improvement = $currentScore - $previousScores[0];
            $trend = $improvement > 0 ? 'improving' : ($improvement < 0 ? 'declining' : 'stable');
        } else {
            $averagePrevious = array_sum($previousScores) / count($previousScores);
            $improvement = $currentScore - $averagePrevious;
            $trend = $improvement > 0 ? 'improving' : ($improvement < 0 ? 'declining' : 'stable');
        }
        
        return [
            'trend' => $trend,
            'improvement' => $improvement,
            'average_improvement' => $improvement / max(1, count($previousScores)),
        ];
    }

    /**
     * ZenithaLMS: Static Methods
     */
    public static function getStatuses()
    {
        return [
            self::STATUS_IN_PROGRESS => 'In Progress',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_PASSED => 'Passed',
            self::STATUS_FAILED => 'Failed',
            self::STATUS_ABANDONED => 'Abandoned',
        ];
    }

    public static function getPerformanceLevels()
    {
        return [
            'excellent' => 'Excellent',
            'good' => 'Good',
            'average' => 'Average',
            'below_average' => 'Below Average',
            'poor' => 'Poor',
        ];
    }
}
