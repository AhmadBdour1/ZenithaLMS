<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class QuizAttemptAnswer extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'quiz_attempt_id',
        'question_id',
        'answer',
        'is_correct',
        'points_earned',
        'answer_data',
        'time_taken_seconds',
        'attempt_number',
        'feedback',
        'ai_analysis',
    ];

    protected $casts = [
        'is_correct' => 'boolean',
        'points_earned' => 'decimal:2',
        'answer_data' => 'array',
        'time_taken_seconds' => 'integer',
        'attempt_number' => 'integer',
        'ai_analysis' => 'array',
    ];

    /**
     * ZenithaLMS: Relationships
     */
    public function quizAttempt()
    {
        return $this->belongsTo(QuizAttempt::class);
    }

    public function question()
    {
        return $this->belongsTo(QuizQuestion::class);
    }

    /**
     * ZenithaLMS: Scopes
     */
    public function scopeCorrect($query)
    {
        return $query->where('is_correct', true);
    }

    public function scopeIncorrect($query)
    {
        return $query->where('is_correct', false);
    }

    public function scopeByAttempt($query, $attemptId)
    {
        return $query->where('quiz_attempt_id', $attemptId);
    }

    public function scopeByQuestion($query, $questionId)
    {
        return $query->where('question_id', $questionId);
    }

    /**
     * ZenithaLMS: Methods
     */
    public function isCorrect()
    {
        return $this->is_correct;
    }

    public function isIncorrect()
    {
        return !$this->is_correct;
    }

    public function getFormattedPointsEarned()
    {
        return number_format($this->points_earned, 2);
    }

    public function getTimeTakenFormatted()
    {
        $seconds = $this->time_taken_seconds;
        
        if ($seconds < 60) {
            return $seconds . 's';
        } elseif ($seconds < 3600) {
            $minutes = floor($seconds / 60);
            $remainingSeconds = $seconds % 60;
            return $minutes . 'm ' . $remainingSeconds . 's';
        } else {
            $hours = floor($seconds / 3600);
            $minutes = floor(($seconds % 3600) / 60);
            return $hours . 'h ' . $minutes . 'm';
        }
    }

    public function getAnswer()
    {
        return $this->answer;
    }

    public function getAnswerData()
    {
        return $this->answer_data ?? [];
    }

    public function getAiAnalysis()
    {
        return $this->ai_analysis ?? [];
    }

    public function getFeedback()
    {
        return $this->feedback;
    }

    public function getCorrectAnswer()
    {
        return $this->question->correct_answer;
    }

    public function getQuestionType()
    {
        return $this->question->question_type;
    }

    public function isMultipleChoice()
    {
        return $this->question->question_type === QuizQuestion::TYPE_MULTIPLE_CHOICE;
    }

    public function isTrueFalse()
    {
        return $this->question->question_type === QuizQuestion::TYPE_TRUE_FALSE;
    }

    public function isShortAnswer()
    {
        return $this->question->question_type === QuizQuestion::TYPE_SHORT_ANSWER;
    }

    public function isEssay()
    {
        return $this->question->question_type === QuizQuestion::TYPE_ESSAY;
    }

    public function getOptions()
    {
        return $this->question->getOptionsArray();
    }

    public function getQuestionText()
    {
        return $this->question->question_text;
    }

    public function getQuestionPoints()
    {
        return $this->question->points;
    }

    public function getDifficultyLevel()
    {
        return $this->question->difficulty_level;
    }

    /**
     * ZenithaLMS: AI-Powered Methods
     */
    public function generateAiAnalysis()
    {
        // ZenithaLMS: Generate AI-powered answer analysis
        $analysis = [
            'answer_quality' => $this->analyzeAnswerQuality(),
            'response_time' => $this->analyzeResponseTime(),
            'confidence_level' => $this->analyzeConfidenceLevel(),
            'learning_insights' => $this->generateLearningInsights(),
            'improvement_suggestions' => $this->generateImprovementSuggestions(),
            'mastery_indicators' => $this->analyzeMasteryIndicators(),
        ];

        $this->update([
            'ai_analysis' => array_merge($this->getAiAnalysis(), [
                'answer_analysis' => $analysis,
                'ai_analyzed_at' => now()->toISOString(),
            ]),
        ]);

        return $analysis;
    }

    private function analyzeAnswerQuality()
    {
        // ZenithaLMS: Analyze answer quality
        $quality = 0.5; // Base quality
        $factors = [];

        $question = $this->question;
        $answer = $this->answer;
        $correctAnswer = $this->getCorrectAnswer();

        // Check if answer is correct
        if ($this->is_correct) {
            $quality += 0.3;
            $factors[] = 'Correct answer';
        }

        // Check answer completeness
        if ($this->isMultipleChoice() || $this->isTrueFalse()) {
            // Multiple choice answers are complete by nature
            $quality += 0.1;
            $factors[] = 'Complete selection';
        } elseif ($this->isShortAnswer() || $this->isEssay()) {
            $answerLength = strlen($answer);
            
            if ($answerLength > 50) {
                $quality += 0.1;
                $factors[] = 'Adequate length';
            } elseif ($answerLength > 200) {
                $quality += 0.2;
                $factors[] = 'Detailed answer';
            }
        }

        // Check for relevant keywords (for text answers)
        if ($this->isShortAnswer() || $this->isEssay()) {
            $keywords = $this->extractKeywords($question->question_text ?? '');
            $answerKeywords = $this->extractKeywords($answer);
            
            $keywordMatch = count(array_intersect($keywords, $answerKeywords));
            $totalKeywords = count($keywords);
            
            if ($totalKeywords > 0) {
                $keywordRatio = $keywordMatch / $totalKeywords;
                $quality += $keywordRatio * 0.2;
                $factors[] = 'Keyword relevance';
            }
        }

        // Check for explanation (for essay answers)
        if ($this->isEssay()) {
            if (preg_match('/\b(because|since|therefore|however|although)\b/i', $answer)) {
                $quality += 0.1;
                $factors[] = 'Logical reasoning';
            }
        }

        return [
            'score' => min(1.0, $quality),
            'factors' => $factors,
            'assessment' => $quality >= 0.8 ? 'High quality' : ($quality >= 0.6 ? 'Good quality' : 'Needs improvement'),
        ];
    }

    private function analyzeResponseTime()
    {
        // ZenithaLMS: Analyze response time
        $timeTaken = $this->time_taken_seconds;
        $question = $this->question;
        
        $analysis = [
            'time_taken' => $timeTaken,
            'time_formatted' => $this->getTimeTakenFormatted(),
            'is_fast' => false,
            'is_slow' => false,
            'time_efficiency' => 'normal',
        ];

        // Estimate expected time based on question type and difficulty
        $expectedTime = $this->estimateExpectedTime($question);
        
        if ($timeTaken < $expectedTime * 0.5) {
            $analysis['is_fast'] = true;
            $analysis['time_efficiency'] = 'fast';
        } elseif ($timeTaken > $expectedTime * 2) {
            $analysis['is_slow'] = true;
            $analysis['time_efficiency'] = 'slow';
        }

        return $analysis;
    }

    private function estimateExpectedTime($question)
    {
        // ZenithaLMS: Estimate expected response time
        $baseTime = 30; // Base 30 seconds
        
        // Adjust by question type
        if ($question->isMultipleChoice()) {
            $baseTime = 15;
        } elseif ($question->isTrueFalse()) {
            $baseTime = 10;
        } elseif ($question->isShortAnswer()) {
            $baseTime = 60;
        } elseif ($question->isEssay()) {
            $baseTime = 180; // 3 minutes for essays
        }

        // Adjust by difficulty
        $difficulty = $question->difficulty_level;
        if ($difficulty === QuizQuestion::DIFFICULTY_EASY) {
            $baseTime *= 0.8;
        } elseif ($difficulty === QuizQuestion::DIFFICULTY_HARD) {
            $baseTime *= 1.5;
        }

        // Adjust by points
        $points = $question->points;
        if ($points > 5) {
            $baseTime *= 1.2;
        } elseif ($points > 10) {
            $baseTime *= 1.5;
        }

        return $baseTime;
    }

    private function analyzeConfidenceLevel()
    {
        // ZenithaLMS: Analyze confidence level
        $confidence = 0.7; // Base confidence
        
        $answer = $this->answer;
        $question = $this->question;

        // Adjust confidence based on answer length
        if ($this->isShortAnswer() || $this->isEssay()) {
            $answerLength = strlen($answer);
            
            if ($answerLength > 100) {
                $confidence += 0.1;
            } elseif ($answerLength > 300) {
                $confidence += 0.2;
            }
        }

        // Adjust confidence based on correctness
        if ($this->is_correct) {
            $confidence += 0.1;
        }

        // Adjust confidence based on question difficulty
        if ($question->difficulty_level === QuizQuestion::DIFFICULTY_EASY) {
            $confidence += 0.1;
        } elseif ($question->difficulty_level === QuizQuestion::DIFFICULTY_HARD) {
            $confidence -= 0.1;
        }

        return min(1.0, max(0, $confidence));
    }

    private function generateLearningInsights()
    {
        // ZenithaLMS: Generate learning insights
        $insights = [];
        
        $question = $this->question;
        $isCorrect = $this->is_correct;

        if (!$isCorrect) {
            $insights[] = 'Review the question and related materials';
            $insights[] = 'Focus on understanding the core concept';
            
            if ($question->explanation) {
                $insights[] = 'Read the explanation for better understanding';
            }
        } else {
            $insights[] = 'Good understanding of this concept';
            
            if ($this->time_taken_seconds < $this->estimateExpectedTime($question) * 0.5) {
                $insights[] = 'Quick and accurate response';
            }
        }

        // Category-specific insights
        if ($question->category) {
            $insights[] = 'Strength in ' . $question->category;
        }

        // Difficulty-based insights
        if ($question->difficulty_level === QuizQuestion::DIFFICULTY_HARD && $isCorrect) {
            $insights[] = 'Excellent performance on difficult question';
        } elseif ($question->difficulty_level === QuizQuestion::DIFFICULTY_EASY && !$isCorrect) {
            $insights[] = 'Review basic concepts in this area';
        }

        return $insights;
    }

    private function generateImprovementSuggestions()
    {
        // ZenithaLMS: Generate improvement suggestions
        $suggestions = [];
        
        $question = $this->question;
        $answer = $this->answer;
        $isCorrect = $this->is_correct;

        if (!$isCorrect) {
            $suggestions[] = 'Read the question carefully and understand what is being asked';
            
            if ($question->explanation) {
                $suggestions[] = 'Review the explanation to understand the correct answer';
            }
            
            if ($this->isMultipleChoice()) {
                $suggestions[] = 'Eliminate incorrect options systematically';
            } elseif ($this->isShortAnswer() || $this->isEssay()) {
                $suggestions[] = 'Provide more detailed and specific answers';
                $suggestions[] = 'Use proper grammar and complete sentences';
            }
        }

        // Time-based suggestions
        if ($this->time_taken_seconds > $this->estimateExpectedTime($question) * 2) {
            $suggestions[] = 'Practice answering questions more quickly';
            $suggestions[] = 'Focus on key points rather than elaboration';
        }

        // Quality-based suggestions
        $qualityAnalysis = $this->analyzeAnswerQuality();
        if ($qualityAnalysis['score'] < 0.6) {
            $suggestions[] = 'Provide more complete and thoughtful answers';
            $suggestions[] = 'Include relevant details and examples';
        }

        return $suggestions;
    }

    private function analyzeMasteryIndicators()
    {
        // ZenithaLMS: Analyze mastery indicators
        $indicators = [];
        
        $question = $this->question;
        $isCorrect = $this->is_correct;
        $timeTaken = $this->time_taken_seconds;
        $expectedTime = $this->estimateExpectedTime($question);

        // Speed indicator
        if ($isCorrect && $timeTaken < $expectedTime * 0.7) {
            $indicators[] = 'quick_learner';
        }

        // Accuracy indicator
        if ($isCorrect) {
            $indicators[] = 'accurate';
        } else {
            $indicators[] = 'needs_improvement';
        }

        // Confidence indicator
        $confidence = $this->analyzeConfidenceLevel();
        if ($confidence > 0.8) {
            $indicators[] = 'confident';
        } elseif ($confidence < 0.5) {
            $indicators[] = 'uncertain';
        }

        // Difficulty mastery
        if ($question->difficulty_level === QuizQuestion::DIFFICULTY_HARD && $isCorrect) {
            $indicators[] = 'advanced_mastery';
        }

        return $indicators;
    }

    private function extractKeywords($text)
    {
        // ZenithaLMS: Extract important keywords from text
        $stopWords = ['the', 'a', 'an', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by', 'is', 'are', 'was', 'were', 'be', 'been', 'have', 'has', 'do', 'does', 'will', 'would', 'could', 'should'];
        
        $words = str_word_count(strtolower($text), 1);
        $keywords = [];

        foreach ($words as $word) {
            if (!in_array($word, $stopWords) && strlen($word) > 2) {
                $keywords[] = $word;
            }
        }

        return array_unique(array_slice($keywords, 0, 10));
    }

    /**
     * ZenithaLMS: Static Methods
     */
    public static function getResponseTimeEfficiencies()
    {
        return [
            'fast' => 'Fast Response',
            'normal' => 'Normal Response',
            'slow' => 'Slow Response',
        ];
    }

    public static function getConfidenceLevels()
    {
        return [
            'high' => 'High Confidence',
            'medium' => 'Medium Confidence',
            'low' => 'Low Confidence',
        ];
    }

    public static function getMasteryIndicators()
    {
        return [
            'quick_learner' => 'Quick Learner',
            'accurate' => 'Accurate',
            'confident' => 'Confident',
            'uncertain' => 'Uncertain',
            'needs_improvement' => 'Needs Improvement',
            'advanced_mastery' => 'Advanced Mastery',
        ];
    }
}
