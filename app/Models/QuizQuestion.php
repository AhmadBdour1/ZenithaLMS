<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class QuizQuestion extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'quiz_id',
        'question',
        'question_text',
        'question_type',
        'options',
        'correct_answer',
        'explanation',
        'points',
        'order',
        'category',
        'difficulty_level',
        'question_data',
    ];

    protected $casts = [
        'options' => 'array',
        'points' => 'integer',
        'order' => 'integer',
        'question_data' => 'array',
    ];

    /**
     * ZenithaLMS: Question Types
     */
    const TYPE_MULTIPLE_CHOICE = 'multiple_choice';
    const TYPE_TRUE_FALSE = 'true_false';
    const TYPE_SHORT_ANSWER = 'short_answer';
    const TYPE_ESSAY = 'essay';
    const TYPE_FILL_BLANK = 'fill_blank';
    const TYPE_MATCHING = 'matching';

    /**
     * ZenithaLMS: Difficulty Levels
     */
    const DIFFICULTY_EASY = 'easy';
    const DIFFICULTY_MEDIUM = 'medium';
    const DIFFICULTY_HARD = 'hard';

    /**
     * ZenithaLMS: Relationships
     */
    public function quiz()
    {
        return $this->belongsTo(Quiz::class);
    }

    public function attempts()
    {
        return $this->hasMany(QuizAttemptAnswer::class, 'question_id');
    }

    /**
     * ZenithaLMS: Scopes
     */
    public function scopeByType($query, $type)
    {
        return $query->where('question_type', $type);
    }

    public function scopeByDifficulty($query, $difficulty)
    {
        return $query->where('difficulty_level', $difficulty);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order');
    }

    /**
     * ZenithaLMS: Methods
     */
    public function getOptionsArray()
    {
        return $this->options ?? [];
    }

    public function getCorrectAnswer()
    {
        return $this->correct_answer;
    }

    public function isMultipleChoice()
    {
        return $this->question_type === self::TYPE_MULTIPLE_CHOICE;
    }

    public function isTrueFalse()
    {
        return $this->question_type === self::TYPE_TRUE_FALSE;
    }

    public function isShortAnswer()
    {
        return $this->question_type === self::TYPE_SHORT_ANSWER;
    }

    public function isEssay()
    {
        return $this->question_type === self::TYPE_ESSAY;
    }

    public function isFillBlank()
    {
        return $this->question_type === self::TYPE_FILL_BLANK;
    }

    public function isMatching()
    {
        return $this->question_type === self::TYPE_MATCHING;
    }

    /**
     * ZenithaLMS: AI-Powered Methods
     */
    public function generateAiAnalysis()
    {
        // ZenithaLMS: AI-powered question analysis
        $analysis = [
            'complexity_score' => $this->calculateComplexityScore(),
            'estimated_time' => $this->estimateAnswerTime(),
            'cognitive_level' => $this->determineCognitiveLevel(),
            'keywords' => $this->extractKeywords(),
            'success_rate' => $this->getHistoricalSuccessRate(),
            'improvement_suggestions' => $this->generateImprovementSuggestions(),
        ];

        $this->update([
            'question_data' => array_merge($this->question_data ?? [], [
                'ai_analysis' => $analysis,
                'ai_analyzed_at' => now()->toISOString(),
            ]),
        ]);

        return $analysis;
    }

    private function calculateComplexityScore()
    {
        // ZenithaLMS: Calculate question complexity
        $score = 0;
        
        // Base score by difficulty
        if ($this->difficulty_level === self::DIFFICULTY_EASY) {
            $score += 20;
        } elseif ($this->difficulty_level === self::DIFFICULTY_MEDIUM) {
            $score += 50;
        } elseif ($this->difficulty_level === self::DIFFICULTY_HARD) {
            $score += 80;
        }
        
        // Add points for question type complexity
        if ($this->isMultipleChoice()) {
            $score += 10;
        } elseif ($this->isShortAnswer()) {
            $score += 20;
        } elseif ($this->isEssay()) {
            $score += 30;
        } elseif ($this->isMatching()) {
            $score += 25;
        }
        
        // Add points for options count
        if ($this->isMultipleChoice()) {
            $optionsCount = count($this->getOptionsArray());
            if ($optionsCount > 4) {
                $score += 10;
            }
        }
        
        // Add points for question length
        $questionLength = strlen($this->question_text ?? '');
        if ($questionLength > 200) {
            $score += 10;
        }
        
        return min(100, $score);
    }

    private function estimateAnswerTime()
    {
        // ZenithaLMS: Estimate answer time in seconds
        $baseTime = 30; // Base 30 seconds
        
        if ($this->isMultipleChoice()) {
            $baseTime += 20;
        } elseif ($this->isTrueFalse()) {
            $baseTime += 10;
        } elseif ($this->isShortAnswer()) {
            $baseTime += 60;
        } elseif ($this->isEssay()) {
            $baseTime += 300; // 5 minutes for essay
        } elseif ($this->isMatching()) {
            $baseTime += 45;
        } elseif ($this->isFillBlank()) {
            $baseTime += 30;
        }
        
        // Adjust for difficulty
        if ($this->difficulty_level === self::DIFFICULTY_EASY) {
            $baseTime *= 0.8;
        } elseif ($this->difficulty_level === self::DIFFICULTY_HARD) {
            $baseTime *= 1.5;
        }
        
        return (int) $baseTime;
    }

    private function determineCognitiveLevel()
    {
        // ZenithaLMS: Determine cognitive level based on Bloom's taxonomy
        $question = strtolower($this->question_text ?? '');
        
        // Remember/Understand level
        if (preg_match('/\b(what|who|when|where|which|define|describe|identify|list|name|state)\b/', $question)) {
            return 'remember';
        }
        
        // Apply level
        if (preg_match('/\b(how|why|explain|demonstrate|apply|use|implement|solve)\b/', $question)) {
            return 'apply';
        }
        
        // Analyze level
        if (preg_match('/\b(analyze|compare|contrast|examine|investigate|categorize|differentiate)\b/', $question)) {
            return 'analyze';
        }
        
        // Evaluate level
        if (preg_match('/\b(evaluate|assess|judge|critique|justify|defend|argue)\b/', $question)) {
            return 'evaluate';
        }
        
        // Create level
        if (preg_match('/\b(create|design|develop|construct|build|formulate|generate)\b/', $question)) {
            return 'create';
        }
        
        return 'understand'; // Default
    }

    private function extractKeywords()
    {
        // ZenithaLMS: Extract important keywords from question
        $question = strtolower($this->question_text ?? '');
        $stopWords = ['the', 'a', 'an', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by', 'is', 'are', 'was', 'were', 'be', 'been', 'have', 'has', 'had', 'do', 'does', 'did', 'will', 'would', 'could', 'should'];
        
        $words = str_word_count($question, 1);
        $keywords = [];
        
        foreach ($words as $word) {
            if (!in_array($word, $stopWords) && strlen($word) > 2) {
                $keywords[] = $word;
            }
        }
        
        return array_unique(array_slice($keywords, 0, 10));
    }

    private function getHistoricalSuccessRate()
    {
        // ZenithaLMS: Get historical success rate for this question
        $totalAttempts = $this->attempts()->count();
        
        if ($totalAttempts === 0) {
            return 0.5; // Default 50% for new questions
        }
        
        $correctAttempts = $this->attempts()
            ->where('is_correct', true)
            ->count();
        
        return $correctAttempts / $totalAttempts;
    }

    private function generateImprovementSuggestions()
    {
        // ZenithaLMS: Generate AI-powered improvement suggestions
        $suggestions = [];
        
        $complexity = $this->calculateComplexityScore();
        $successRate = $this->getHistoricalSuccessRate();
        
        if ($successRate < 0.3) {
            $suggestions[] = 'Question seems too difficult - consider simplifying language or providing more context';
        } elseif ($successRate > 0.9) {
            $suggestions[] = 'Question might be too easy - consider adding complexity or higher-order thinking';
        }
        
        if ($complexity < 30) {
            $suggestions[] = 'Consider adding more depth to the question to challenge students';
        } elseif ($complexity > 80) {
            $suggestions[] = 'Consider breaking down complex question into simpler parts';
        }
        
        if ($this->isMultipleChoice() && count($this->getOptionsArray()) < 3) {
            $suggestions[] = 'Add more options to make multiple choice question more challenging';
        }
        
        if (empty($this->explanation)) {
            $suggestions[] = 'Add explanation to help students understand the correct answer';
        }
        
        return $suggestions;
    }

    /**
     * ZenithaLMS: Static Methods
     */
    public static function getQuestionTypes()
    {
        return [
            self::TYPE_MULTIPLE_CHOICE => 'Multiple Choice',
            self::TYPE_TRUE_FALSE => 'True/False',
            self::TYPE_SHORT_ANSWER => 'Short Answer',
            self::TYPE_ESSAY => 'Essay',
            self::TYPE_FILL_BLANK => 'Fill in the Blank',
            self::TYPE_MATCHING => 'Matching',
        ];
    }

    public static function getDifficultyLevels()
    {
        return [
            self::DIFFICULTY_EASY => 'Easy',
            self::DIFFICULTY_MEDIUM => 'Medium',
            self::DIFFICULTY_HARD => 'Hard',
        ];
    }

    public static function getCognitiveLevels()
    {
        return [
            'remember' => 'Remember',
            'understand' => 'Understand',
            'apply' => 'Apply',
            'analyze' => 'Analyze',
            'evaluate' => 'Evaluate',
            'create' => 'Create',
        ];
    }
}
