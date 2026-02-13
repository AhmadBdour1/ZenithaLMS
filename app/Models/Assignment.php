<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Assignment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title',
        'description',
        'course_id',
        'lesson_id',
        'instructor_id',
        'type',
        'max_points',
        'due_date',
        'submission_deadline',
        'allow_late_submissions',
        'late_submission_penalty',
        'auto_grade',
        'assignment_data',
        'instructions',
        'rubric',
        'resources',
        'is_published',
        'published_at',
    ];

    protected $casts = [
        'max_points' => 'decimal:2',
        'due_date' => 'datetime',
        'submission_deadline' => 'datetime',
        'allow_late_submissions' => 'boolean',
        'late_submission_penalty' => 'decimal:2',
        'auto_grade' => 'boolean',
        'assignment_data' => 'array',
        'rubric' => 'array',
        'resources' => 'array',
        'is_published' => 'boolean',
        'published_at' => 'datetime',
    ];

    /**
     * ZenithaLMS: Assignment Types
     */
    const TYPE_ESSAY = 'essay';
    const TYPE_PROJECT = 'project';
    const TYPE_PRESENTATION = 'presentation';
    const TYPE_CODE = 'code';
    const TYPE_QUIZ = 'quiz';
    const TYPE_FILE_UPLOAD = 'file_upload';
    const TYPE_TEXT_SUBMISSION = 'text_submission';
    const TYPE_MULTIMEDIA = 'multimedia';

    /**
     * ZenithaLMS: Relationships
     */
    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function lesson()
    {
        return $this->belongsTo(Lesson::class);
    }

    public function instructor()
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    public function submissions()
    {
        return $this->hasMany(AssignmentSubmission::class);
    }

    /**
     * ZenithaLMS: Scopes
     */
    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public function scopeDraft($query)
    {
        return $query->where('is_published', false);
    }

    public function scopeByCourse($query, $courseId)
    {
        return $query->where('course_id', $courseId);
    }

    public function scopeByInstructor($query, $instructorId)
    {
        return $query->where('instructor_id', $instructorId);
    }

    public function scopeDueSoon($query, $days = 7)
    {
        return $query->where('due_date', '<=', now()->addDays($days))
                    ->where('due_date', '>', now());
    }

    public function scopeOverdue($query)
    {
        return $query->where('due_date', '<', now());
    }

    /**
     * ZenithaLMS: Methods
     */
    public function isPublished()
    {
        return $this->is_published;
    }

    public function isDraft()
    {
        return !$this->is_published;
    }

    public function isOverdue()
    {
        return $this->due_date && $this->due_date->isPast();
    }

    public function isDueSoon()
    {
        return $this->due_date && $this->due_date->diffInDays(now()) <= 7;
    }

    public function getDueDateFormatted()
    {
        return $this->due_date ? $this->due_date->format('M d, Y g:i A') : 'No due date';
    }

    public function getSubmissionDeadlineFormatted()
    {
        return $this->submission_deadline ? $this->submission_deadline->format('M d, Y g:i A') : 'No deadline';
    }

    public function getRemainingDays()
    {
        if (!$this->due_date) {
            return null;
        }
        
        $days = $this->due_date->diffInDays(now(), false);
        
        if ($days > 0) {
            return $days . ' days remaining';
        } elseif ($days === 0) {
            return 'Due today';
        } else {
            return abs($days) . ' days overdue';
        }
    }

    public function getSubmissionsCount()
    {
        return $this->submissions()->count();
    }

    public function getGradedSubmissionsCount()
    {
        return $this->submissions()->whereNotNull('graded_at')->count();
    }

    public function getAverageScore()
    {
        $gradedSubmissions = $this->submissions()->whereNotNull('score')->get();
        
        if ($gradedSubmissions->isEmpty()) {
            return 0;
        }
        
        return $gradedSubmissions->avg('score');
    }

    public function getAssignmentData()
    {
        return $this->assignment_data ?? [];
    }

    public function getRubric()
    {
        return $this->rubric ?? [];
    }

    public function getResources()
    {
        return $this->resources ?? [];
    }

    /**
     * ZenithaLMS: AI-Powered Methods
     */
    public function generateAiAnalysis()
    {
        // ZenithaLMS: Generate AI-powered assignment analysis
        $analysis = [
            'difficulty_level' => $this->calculateDifficultyLevel(),
            'estimated_completion_time' => $this->estimateCompletionTime(),
            'required_skills' => $this->identifyRequiredSkills(),
            'learning_objectives' => $this->extractLearningObjectives(),
            'plagiarism_risk' => $this->assessPlagiarismRisk(),
            'grading_complexity' => $this->assessGradingComplexity(),
            'student_engagement_prediction' => $this->predictStudentEngagement(),
            'improvement_suggestions' => $this->generateImprovementSuggestions(),
        ];

        $this->update([
            'assignment_data' => array_merge($this->getAssignmentData(), [
                'ai_analysis' => $analysis,
                'ai_analyzed_at' => now()->toISOString(),
            ]),
        ]);

        return $analysis;
    }

    private function calculateDifficultyLevel()
    {
        // ZenithaLMS: Calculate assignment difficulty
        $score = 0;
        
        // Base score by type
        if ($this->type === self::TYPE_ESSAY) {
            $score += 60;
        } elseif ($this->type === self::TYPE_PROJECT) {
            $score += 80;
        } elseif ($this->type === self::TYPE_PRESENTATION) {
            $score += 70;
        } elseif ($this->type === self::TYPE_CODE) {
            $score += 75;
        } elseif ($this->type === self::TYPE_QUIZ) {
            $score += 40;
        } elseif ($this->type === self::TYPE_FILE_UPLOAD) {
            $score += 50;
        } elseif ($this->type === self::TYPE_TEXT_SUBMISSION) {
            $score += 45;
        } elseif ($this->type === self::TYPE_MULTIMEDIA) {
            $score += 65;
        }
        
        // Add points for max points
        if ($this->max_points > 50) {
            $score += 15;
        } elseif ($this->max_points > 25) {
            $score += 10;
        } elseif ($this->max_points > 10) {
            $score += 5;
        }
        
        // Add points for description length
        $descriptionLength = strlen($this->description ?? '');
        if ($descriptionLength > 500) {
            $score += 10;
        } elseif ($descriptionLength > 200) {
            $score += 5;
        }
        
        // Add points for rubric complexity
        $rubric = $this->getRubric();
        if (count($rubric) > 5) {
            $score += 10;
        } elseif (count($rubric) > 3) {
            $score += 5;
        }
        
        // Add points for resources
        $resources = $this->getResources();
        if (count($resources) > 3) {
            $score += 5;
        }
        
        if ($score >= 80) {
            return 'hard';
        } elseif ($score >= 60) {
            return 'medium';
        }
        
        return 'easy';
    }

    private function estimateCompletionTime()
    {
        // ZenithaLMS: Estimate completion time in hours
        $baseTime = 2; // Base 2 hours
        
        // Adjust by type
        if ($this->type === self::TYPE_ESSAY) {
            $baseTime = 4;
        } elseif ($this->type === self::TYPE_PROJECT) {
            $baseTime = 8;
        } elseif ($this->type === self::TYPE_PRESENTATION) {
            $baseTime = 6;
        } elseif ($this->type === self::TYPE_CODE) {
            $baseTime = 5;
        } elseif ($this->type === self::TYPE_QUIZ) {
            $baseTime = 1;
        } elseif ($this->type === self::TYPE_FILE_UPLOAD) {
            $baseTime = 2;
        } elseif ($this->type === self::TYPE_TEXT_SUBMISSION) {
            $baseTime = 1.5;
        } elseif ($this->type === self::TYPE_MULTIMEDIA) {
            $baseTime = 3;
        }
        
        // Adjust by max points
        if ($this->max_points > 50) {
            $baseTime *= 1.5;
        } elseif ($this->max_points > 25) {
            $baseTime *= 1.2;
        }
        
        // Adjust by description length
        $descriptionLength = strlen($this->description ?? '');
        if ($descriptionLength > 500) {
            $baseTime *= 1.3;
        }
        
        return round($baseTime, 1);
    }

    private function identifyRequiredSkills()
    {
        // ZenithaLMS: Identify required skills from description
        $description = strtolower($this->description ?? '');
        $skills = [];
        
        // Technical skills
        $technicalSkills = [
            'programming' => ['programming', 'coding', 'code', 'software', 'development', 'algorithm'],
            'writing' => ['writing', 'essay', 'report', 'article', 'content', 'text'],
            'research' => ['research', 'analysis', 'investigation', 'study', 'explore'],
            'presentation' => ['presentation', 'speaking', 'communicating', 'present'],
            'design' => ['design', 'creative', 'visual', 'graphics', 'art'],
            'mathematics' => ['math', 'calculation', 'statistics', 'numbers', 'formula'],
            'critical_thinking' => ['critical', 'thinking', 'analyze', 'evaluate', 'assess'],
            'collaboration' => ['collaborate', 'teamwork', 'group', 'cooperation'],
        ];
        
        foreach ($technicalSkills as $skill => $keywords) {
            foreach ($keywords as $keyword) {
                if (strpos($description, $keyword) !== false) {
                    $skills[] = $skill;
                    break;
                }
            }
        }
        
        return array_unique($skills);
    }

    private function extractLearningObjectives()
    {
        // ZenithaLMS: Extract learning objectives from description
        $description = $this->description ?? '';
        $objectives = [];
        
        // Look for objective patterns
        $patterns = [
            '/students will be able to (.*?)[\.\n]/i',
            '/learn to (.*?)[\.\n]/i',
            '/understand (.*?)[\.\n]/i',
            '/demonstrate (.*?)[\.\n]/i',
            '/apply (.*?)[\.\n]/i',
            '/analyze (.*?)[\.\n]/i',
            '/evaluate (.*?)[\.\n]/i',
            '/create (.*?)[\.\n]/i',
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $description, $matches)) {
                foreach ($matches[1] as $match) {
                    $objectives[] = trim($match);
                }
            }
        }
        
        return array_unique($objectives);
    }

    private function assessPlagiarismRisk()
    {
        // ZenithaLMS: Assess plagiarism risk
        $risk = 'low';
        $score = 20;
        
        // Higher risk for essay assignments
        if ($this->type === self::TYPE_ESSAY) {
            $score += 30;
        }
        
        // Higher risk for text submissions
        if ($this->type === self::TYPE_TEXT_SUBMISSION) {
            $score += 25;
        }
        
        // Lower risk for code assignments
        if ($this->type === self::TYPE_CODE) {
            $score -= 10;
        }
        
        // Lower risk for presentations
        if ($this->type === self::TYPE_PRESENTATION) {
            $score -= 5;
        }
        
        // Higher risk if no specific instructions
        if (strlen($this->instructions ?? '') < 100) {
            $score += 20;
        }
        
        // Lower risk if detailed rubric
        if (count($this->getRubric()) > 3) {
            $score -= 10;
        }
        
        if ($score >= 70) {
            $risk = 'high';
        } elseif ($score >= 50) {
            $risk = 'medium';
        }
        
        return [
            'risk_level' => $risk,
            'risk_score' => $score,
            'factors' => [
                'assignment_type' => $this->type,
                'instruction_detail' => strlen($this->instructions ?? ''),
                'rubric_detail' => count($this->getRubric()),
            ],
        ];
    }

    private function assessGradingComplexity()
    {
        // ZenithaLMS: Assess grading complexity
        $complexity = 'low';
        $score = 20;
        
        // Base score by type
        if ($this->type === self::TYPE_ESSAY) {
            $score += 40;
        } elseif ($this->type === self::TYPE_PROJECT) {
            $score += 50;
        } elseif ($this->type === self::TYPE_PRESENTATION) {
            $score += 35;
        } elseif ($this->type === self::TYPE_CODE) {
            $score += 30;
        } elseif ($this->type === self::TYPE_QUIZ) {
            $score -= 10;
        }
        
        // Add points for rubric complexity
        $rubric = $this->getRubric();
        if (count($rubric) > 5) {
            $score += 20;
        } elseif (count($rubric) > 3) {
            $score += 10;
        }
        
        // Add points for max points
        if ($this->max_points > 50) {
            $score += 15;
        } elseif ($this->max_points > 25) {
            $score += 8;
        }
        
        // Add points for auto-grading
        if (!$this->auto_grade) {
            $score += 25;
        }
        
        if ($score >= 70) {
            $complexity = 'high';
        } elseif ($score >= 50) {
            $complexity = 'medium';
        }
        
        return [
            'complexity_level' => $complexity,
            'complexity_score' => $score,
            'estimated_grading_time' => $this->estimateGradingTime($score),
        ];
    }

    private function estimateGradingTime($complexityScore)
    {
        // ZenithaLMS: Estimate grading time per submission in minutes
        $baseTime = 5;
        
        if ($complexityScore >= 70) {
            $baseTime = 15;
        } elseif ($complexityScore >= 50) {
            $baseTime = 10;
        }
        
        return $baseTime;
    }

    private function predictStudentEngagement()
    {
        // ZenithaLMS: Predict student engagement
        $engagement = 'medium';
        $score = 50;
        
        // Base score by type
        if ($this->type === self::TYPE_PROJECT) {
            $score += 20;
        } elseif ($this->type === self::TYPE_PRESENTATION) {
            $score += 15;
        } elseif ($this->type === self::TYPE_CODE) {
            $score += 10;
        } elseif ($this->type === self::TYPE_ESSAY) {
            $score -= 10;
        } elseif ($this->type === self::TYPE_QUIZ) {
            $score -= 5;
        }
        
        // Adjust by difficulty
        $difficulty = $this->calculateDifficultyLevel();
        if ($difficulty === 'easy') {
            $score += 15;
        } elseif ($difficulty === 'hard') {
            $score -= 15;
        }
        
        // Adjust by max points
        if ($this->max_points > 50) {
            $score += 10;
        }
        
        // Adjust by due date
        if ($this->due_date) {
            $daysUntilDue = $this->due_date->diffInDays(now());
            if ($daysUntilDue > 14) {
                $score -= 10;
            } elseif ($daysUntilDue < 7) {
                $score += 10;
            }
        }
        
        if ($score >= 70) {
            $engagement = 'high';
        } elseif ($score >= 50) {
            $engagement = 'medium';
        } else {
            $engagement = 'low';
        }
        
        return [
            'engagement_level' => $engagement,
            'engagement_score' => $score,
            'predicted_completion_rate' => $this->predictCompletionRate($score),
        ];
    }

    private function predictCompletionRate($engagementScore)
    {
        // ZenithaLMS: Predict completion rate based on engagement
        if ($engagementScore >= 70) {
            return 85;
        } elseif ($engagementScore >= 50) {
            return 70;
        } elseif ($engagementScore >= 30) {
            return 55;
        }
        
        return 40;
    }

    private function generateImprovementSuggestions()
    {
        // ZenithaLMS: Generate improvement suggestions
        $suggestions = [];
        
        $difficulty = $this->calculateDifficultyLevel();
        $gradingComplexity = $this->assessGradingComplexity();
        $plagiarismRisk = $this->assessPlagiarismRisk();
        
        if ($difficulty === 'hard') {
            $suggestions[] = 'Consider breaking down the assignment into smaller, manageable parts';
            $suggestions[] = 'Provide more detailed instructions and examples';
            $suggestions[] = 'Offer additional resources or scaffolding';
        }
        
        if ($gradingComplexity['complexity_score'] > 70) {
            $suggestions[] = 'Consider using a more detailed rubric for consistent grading';
            $suggestions[] = 'Break down grading into smaller, focused criteria';
            $suggestions[] = 'Consider peer review components to reduce grading load';
        }
        
        if ($plagiarismRisk['risk_score'] > 60) {
            $suggestions[] = 'Add specific, unique requirements to reduce plagiarism risk';
            $suggestions[] = 'Include personal reflection components';
            $suggestions[] = 'Use plagiarism detection tools';
        }
        
        if (strlen($this->instructions ?? '') < 200) {
            $suggestions[] = 'Provide more detailed instructions to guide students';
            $suggestions[] = 'Include examples of successful submissions';
        }
        
        if (count($this->getRubric()) < 3) {
            $suggestions[] = 'Create a more detailed rubric for clearer expectations';
            $suggestions[] = 'Include specific criteria for each grade level';
        }
        
        return $suggestions;
    }

    /**
     * ZenithaLMS: Static Methods
     */
    public static function getTypes()
    {
        return [
            self::TYPE_ESSAY => 'Essay',
            self::TYPE_PROJECT => 'Project',
            self::TYPE_PRESENTATION => 'Presentation',
            self::TYPE_CODE => 'Code',
            self::TYPE_QUIZ => 'Quiz',
            self::TYPE_FILE_UPLOAD => 'File Upload',
            self::TYPE_TEXT_SUBMISSION => 'Text Submission',
            self::TYPE_MULTIMEDIA => 'Multimedia',
        ];
    }

    public static function getDifficultyLevels()
    {
        return [
            'easy' => 'Easy',
            'medium' => 'Medium',
            'hard' => 'Hard',
        ];
    }
}
