<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AssignmentSubmission extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'assignment_id',
        'user_id',
        'status',
        'content',
        'files',
        'score',
        'feedback',
        'graded_by',
        'graded_at',
        'submitted_at',
        'submission_data',
        'ai_analysis',
        'is_late',
        'late_penalty',
    ];

    protected $casts = [
        'score' => 'decimal:2',
        'files' => 'array',
        'graded_at' => 'datetime',
        'submitted_at' => 'datetime',
        'submission_data' => 'array',
        'ai_analysis' => 'array',
        'is_late' => 'boolean',
        'late_penalty' => 'decimal:2',
    ];

    /**
     * ZenithaLMS: Submission Status Constants
     */
    const STATUS_DRAFT = 'draft';
    const STATUS_SUBMITTED = 'submitted';
    const STATUS_GRADED = 'graded';
    const STATUS_RETURNED = 'returned';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';

    /**
     * ZenithaLMS: Relationships
     */
    public function assignment()
    {
        return $this->belongsTo(Assignment::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function grader()
    {
        return $this->belongsTo(User::class, 'graded_by');
    }

    /**
     * ZenithaLMS: Scopes
     */
    public function scopeByAssignment($query, $assignmentId)
    {
        return $query->where('assignment_id', $assignmentId);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeSubmitted($query)
    {
        return $query->where('status', self::STATUS_SUBMITTED);
    }

    public function scopeGraded($query)
    {
        return $query->where('status', self::STATUS_GRADED);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_SUBMITTED);
    }

    /**
     * ZenithaLMS: Methods
     */
    public function isSubmitted()
    {
        return $this->status === self::STATUS_SUBMITTED;
    }

    public function isGraded()
    {
        return $this->status === self::STATUS_GRADED;
    }

    public function isDraft()
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function getFormattedScore()
    {
        return number_format($this->score, 2);
    }

    public function getFiles()
    {
        return $this->files ?? [];
    }

    public function getSubmissionData()
    {
        return $this->submission_data ?? [];
    }

    public function getAiAnalysis()
    {
        return $this->ai_analysis ?? [];
    }

    public function getGraderName()
    {
        return $this->grader ? $this->grader->name : 'Not graded';
    }

    public function getSubmittedAtFormatted()
    {
        return $this->submitted_at ? $this->submitted_at->format('M d, Y g:i A') : 'Not submitted';
    }

    public function getGradedAtFormatted()
    {
        return $this->graded_at ? $this->graded_at->format('M d, Y g:i A') : 'Not graded';
    }

    public function getFinalScore()
    {
        $finalScore = $this->score;
        
        if ($this->is_late && $this->late_penalty) {
            $finalScore = $finalScore * (1 - $this->late_penalty / 100);
        }
        
        return $finalScore;
    }

    public function getFinalScoreFormatted()
    {
        return number_format($this->getFinalScore(), 2);
    }

    public function getGradeLetter()
    {
        $score = $this->getFinalScore();
        $maxScore = $this->assignment->max_points;
        
        $percentage = ($score / $maxScore) * 100;
        
        if ($percentage >= 90) return 'A';
        if ($percentage >= 80) return 'B';
        if ($percentage >= 70) return 'C';
        if ($percentage >= 60) return 'D';
        
        return 'F';
    }

    /**
     * ZenithaLMS: AI-Powered Methods
     */
    public function generateAiGrading()
    {
        // ZenithaLMS: Generate AI-powered grading
        $grading = [
            'auto_score' => $this->calculateAutoScore(),
            'plagiarism_check' => $this->checkPlagiarism(),
            'content_analysis' => $this->analyzeContent(),
            'improvement_suggestions' => $this->generateImprovementSuggestions(),
            'confidence_score' => $this->calculateGradingConfidence(),
            'grading_time' => $this->estimateGradingTime(),
        ];

        $this->update([
            'ai_analysis' => array_merge($this->getAiAnalysis(), [
                'ai_grading' => $grading,
                'ai_graded_at' => now()->toISOString(),
            ]),
        ]);

        return $grading;
    }

    private function calculateAutoScore()
    {
        // ZenithaLMS: Calculate automatic score based on submission
        $score = 0;
        $maxScore = $this->assignment->max_points;
        
        // Base score for submission
        $score += $maxScore * 0.3; // 30% for submission
        
        // Content quality analysis
        $contentQuality = $this->analyzeContentQuality();
        $score += $maxScore * $contentQuality * 0.4; // 40% for content
        
        // Technical accuracy
        $technicalAccuracy = $this->analyzeTechnicalAccuracy();
        $score += $maxScore * $technicalAccuracy * 0.2; // 20% for technical
        
        // Completeness
        $completeness = $this->analyzeCompleteness();
        $score += $maxScore * $completeness * 0.1; // 10% for completeness
        
        return min($maxScore, $score);
    }

    private function analyzeContentQuality()
    {
        // ZenithaLMS: Analyze content quality
        $quality = 0.5; // Base quality
        
        $content = $this->content ?? '';
        $submissionData = $this->getSubmissionData();
        
        // Check word count
        $wordCount = str_word_count($content);
        if ($wordCount > 500) {
            $quality += 0.2;
        } elseif ($wordCount > 200) {
            $quality += 0.1;
        }
        
        // Check structure
        if (preg_match('/\b(introduction|body|conclusion)\b/i', $content)) {
            $quality += 0.1;
        }
        
        // Check grammar (simplified)
        $sentences = preg_split('/[.!?]+/', $content);
        $avgSentenceLength = count($sentences) > 0 ? array_sum(array_map('str_word_count', $sentences)) / count($sentences) : 0;
        
        if ($avgSentenceLength >= 10 && $avgSentenceLength <= 20) {
            $quality += 0.1;
        }
        
        // Check for citations/references
        if (preg_match('/\b(references|cited|source|url)\b/i', $content)) {
            $quality += 0.1;
        }
        
        return min(1.0, $quality);
    }

    private function analyzeTechnicalAccuracy()
    {
        // ZenithaLMS: Analyze technical accuracy
        $accuracy = 0.5; // Base accuracy
        
        $assignment = $this->assignment;
        $submissionData = $this->getSubmissionData();
        
        // Check if submission follows requirements
        if ($assignment->type === Assignment::TYPE_CODE) {
            $code = $submissionData['code'] ?? '';
            $language = $submissionData['language'] ?? '';
            
            // Basic syntax check
            if ($this->checkCodeSyntax($code, $language)) {
                $accuracy += 0.2;
            }
            
            // Check for proper structure
            if ($this->checkCodeStructure($code, $language)) {
                $accuracy += 0.2;
            }
        } elseif ($assignment->type === Assignment::TYPE_ESSAY) {
            $content = $this->content ?? '';
            
            // Check for thesis statement
            if (preg_match('/\b(thesis|main point|argument)\b/i', $content)) {
                $accuracy += 0.2;
            }
            
            // Check for supporting evidence
            if (preg_match('/\b(evidence|example|support|proof)\b/i', $content)) {
                $accuracy += 0.2;
            }
        }
        
        return min(1.0, $accuracy);
    }

    private function analyzeCompleteness()
    {
        // ZenithaLMS: Analyze submission completeness
        $completeness = 0.5; // Base completeness
        
        $assignment = $this->assignment;
        $submissionData = $this->getSubmissionData();
        
        // Check if all required parts are included
        if ($assignment->type === Assignment::TYPE_FILE_UPLOAD) {
            $files = $this->getFiles();
            if (count($files) > 0) {
                $completeness += 0.3;
            }
        } elseif ($assignment->type === Assignment::TYPE_TEXT_SUBMISSION) {
            $content = $this->content ?? '';
            if (strlen($content) > 100) {
                $completeness += 0.3;
            }
        }
        
        // Check for additional requirements
        if ($assignment->instructions) {
            $instructions = strtolower($assignment->instructions);
            $content = strtolower($this->content ?? '');
            
            $requiredWords = ['include', 'must', 'required', 'should'];
            foreach ($requiredWords as $word) {
                if (strpos($instructions, $word) !== false && strpos($content, $word) !== false) {
                    $completeness += 0.1;
                }
            }
        }
        
        return min(1.0, $completeness);
    }

    private function checkPlagiarism()
    {
        // ZenithaLMS: Check for plagiarism
        $plagiarismRisk = 'low';
        $score = 20;
        
        $content = strtolower($this->content ?? '');
        
        // Check for common phrases
        $commonPhrases = ['in conclusion', 'in summary', 'it is clear that', 'as we can see', 'in today\'s world'];
        $commonPhraseCount = 0;
        
        foreach ($commonPhrases as $phrase) {
            $commonPhraseCount += substr_count($content, $phrase);
        }
        
        if ($commonPhraseCount > 5) {
            $score += 30;
            $plagiarismRisk = 'medium';
        }
        
        // Check for repetitive content
        $words = str_word_count($content);
        $uniqueWords = count(array_unique(str_word_count($content, 1)));
        
        if ($words > 0 && ($uniqueWords / $words) < 0.5) {
            $score += 25;
            $plagiarismRisk = 'high';
        }
        
        return [
            'risk_level' => $plagiarismRisk,
            'risk_score' => $score,
            'common_phrases' => $commonPhraseCount,
            'uniqueness_ratio' => $words > 0 ? ($uniqueWords / $words) : 0,
        ];
    }

    private function analyzeContent()
    {
        // ZenithaLMS: Analyze content characteristics
        $content = $this->content ?? '';
        
        $analysis = [
            'word_count' => str_word_count($content),
            'character_count' => strlen($content),
            'sentence_count' => count(preg_split('/[.!?]+/', $content)),
            'paragraph_count' => count(preg_split('/\n\n+/', $content)),
            'avg_sentence_length' => $this->calculateAvgSentenceLength($content),
            'readability_score' => $this->calculateReadabilityScore($content),
            'complexity_level' => $this->determineComplexityLevel($content),
        ];
        
        return $analysis;
    }

    private function generateImprovementSuggestions()
    {
        // ZenithaLMS: Generate AI-powered improvement suggestions
        $suggestions = [];
        
        $grading = $this->ai_analysis['ai_grading'] ?? [];
        $contentAnalysis = $grading['content_analysis'] ?? [];
        $plagiarismCheck = $grading['plagiarism_check'] ?? [];
        
        // Content quality suggestions
        if ($grading['auto_score'] < 70) {
            $suggestions[] = 'Add more detail and structure to your submission';
            $suggestions[] = 'Include specific examples and evidence to support your points';
        }
        
        // Technical accuracy suggestions
        if ($grading['technical_accuracy'] < 70) {
            $suggestions[] = 'Review the technical requirements and ensure accuracy';
            $suggestions[] = 'Double-check your work for any technical errors';
        }
        
        // Completeness suggestions
        if ($grading['completeness'] < 70) {
            $suggestions[] = 'Ensure all required components are included in your submission';
            $suggestions[] = 'Review the assignment instructions carefully';
        }
        
        // Plagiarism suggestions
        if ($plagiarismCheck['risk_level'] === 'high') {
            $suggestions[] = 'Ensure your work is original and properly cited';
            $suggestions[] = 'Add your own analysis and insights to demonstrate understanding';
        }
        
        // Readability suggestions
        if ($contentAnalysis['readability_score'] < 60) {
            $suggestions[] = 'Improve sentence structure and readability';
            $suggestions[] = 'Break down long paragraphs for better readability';
        }
        
        return $suggestions;
    }

    private function calculateGradingConfidence()
    {
        // ZenithaLMS: Calculate confidence in AI grading
        $confidence = 0.7; // Base confidence
        
        $grading = $this->ai_analysis['ai_grading'] ?? [];
        
        // Adjust confidence based on content quality
        if ($grading['content_analysis']['readability_score'] > 70) {
            $confidence += 0.1;
        }
        
        // Adjust confidence based on plagiarism risk
        $plagiarismRisk = $grading['plagiarism_check']['risk_level'] ?? 'low';
        if ($plagiarismRisk === 'low') {
            $confidence += 0.1;
        } elseif ($plagiarismRisk === 'high') {
            $confidence -= 0.2;
        }
        
        // Adjust confidence based on completeness
        if ($grading['completeness'] > 0.8) {
            $confidence += 0.1;
        }
        
        return min(1.0, max(0, $confidence));
    }

    private function estimateGradingTime()
    {
        // ZenithaLMS: Estimate grading time in seconds
        $baseTime = 30; // Base 30 seconds
        
        $assignment = $this->assignment;
        
        // Adjust by assignment type
        if ($assignment->type === Assignment::TYPE_ESSAY) {
            $baseTime = 120; // 2 minutes for essays
        } elseif ($assignment->type === Assignment::TYPE_PROJECT) {
            $baseTime = 180; // 3 minutes for projects
        } elseif ($assignment->type === Assignment::TYPE_CODE) {
            $baseTime = 90; // 1.5 minutes for code
        }
        
        // Adjust by content length
        $contentLength = strlen($this->content ?? '');
        if ($contentLength > 2000) {
            $baseTime *= 1.5;
        } elseif ($contentLength > 1000) {
            $baseTime *= 1.2;
        }
        
        return (int) $baseTime;
    }

    private function checkCodeSyntax($code, $language)
    {
        // ZenithaLMS: Basic syntax checking for different languages
        switch ($language) {
            case 'php':
                return $this->checkPhpSyntax($code);
            case 'javascript':
                return $this->checkJavaScriptSyntax($code);
            case 'python':
                return $this->checkPythonSyntax($code);
            default:
                return true; // Assume correct for unknown languages
        }
    }

    private function checkPhpSyntax($code)
    {
        // ZenithaLMS: Basic PHP syntax check
        $openTags = substr_count($code, '<?php');
        $closeTags = substr_count($code, '?>');
        
        return $openTags === $closeTags;
    }

    private function checkJavaScriptSyntax($code)
    {
        // ZenithaLMS: Basic JavaScript syntax check
        $openBraces = substr_count($code, '{');
        $closeBraces = substr_count($code, '}');
        $openParens = substr_count($code, '(');
        $closeParens = substr_count($code, ')');
        
        return $openBraces === $closeBraces && $openParens === $closeParens;
    }

    private function checkPythonSyntax($code)
    {
        // ZenithaLMS: Basic Python syntax check
        $lines = explode("\n", $code);
        foreach ($lines as $line) {
            if (trim($line) && !preg_match('/^[a-zA-Z_]/', trim($line))) {
                return false;
            }
        }
        return true;
    }

    private function checkCodeStructure($code, $language)
    {
        // ZenithaLMS: Basic structure checking
        switch ($language) {
            case 'php':
                return $this->checkPhpStructure($code);
            case 'javascript':
                return $this->checkJavaScriptStructure($code);
            case 'python':
                return $this->checkPythonStructure($code);
            default:
                return true;
        }
    }

    private function checkPhpStructure($code)
    {
        // ZenithaLMS: Basic PHP structure check
        return preg_match('/\b(function|class|if|for|while|foreach)\b/', $code);
    }

    private function checkJavaScriptStructure($code)
    {
        // ZenithaLMS: Basic JavaScript structure check
        return preg_match('/\b(function|const|let|var|if|for|while|class)\b/', $code);
    }

    private function checkPythonStructure($code)
    {
        // ZenithaLMS: Basic Python structure check
        return preg_match('/\b(def|class|if|for|while|import|from)\b/', $code);
    }

    private function calculateAvgSentenceLength($content)
    {
        $sentences = preg_split('/[.!?]+/', $content);
        if (count($sentences) === 0) {
            return 0;
        }
        
        $totalWords = 0;
        foreach ($sentences as $sentence) {
            $totalWords += str_word_count($sentence);
        }
        
        return $totalWords / count($sentences);
    }

    private function calculateReadabilityScore($content)
    {
        // ZenithaLMS: Calculate readability score
        $score = 50;
        
        $avgSentenceLength = $this->calculateAvgSentenceLength($content);
        
        // Ideal sentence length is 15-20 words
        if ($avgSentenceLength >= 15 && $avgSentenceLength <= 20) {
            $score += 20;
        } elseif ($avgSentenceLength >= 10 && $avgSentenceLength <= 25) {
            $score += 10;
        } else {
            $score -= 10;
        }
        
        // Check for complex words
        $words = str_word_count($content);
        $complexWords = 0;
        
        foreach (str_word_count($content, 1) as $word) {
            if (strlen($word) > 6) {
                $complexWords++;
            }
        }
        
        $complexWordRatio = $words > 0 ? $complexWords / $words : 0;
        
        if ($complexWordRatio < 0.1) {
            $score += 15;
        } elseif ($complexWordRatio > 0.3) {
            $score -= 15;
        }
        
        return min(100, max(0, $score));
    }

    private function determineComplexityLevel($content)
    {
        // ZenithaLMS: Determine content complexity level
        $score = 0;
        
        // Check for complex vocabulary
        $complexWords = ['analysis', 'implementation', 'methodology', 'architecture', 'algorithm', 'optimization'];
        foreach ($complexWords as $word) {
            if (strpos(strtolower($content), $word) !== false) {
                $score++;
            }
        }
        
        // Check for sentence complexity
        $sentences = preg_split('/[.!?]+/', $content);
        $complexSentences = 0;
        
        foreach ($sentences as $sentence) {
            if (str_word_count($sentence) > 20) {
                $complexSentences++;
            }
        }
        
        $complexSentenceRatio = count($sentences) > 0 ? $complexSentences / count($sentences) : 0;
        
        if ($score > 3 || $complexSentenceRatio > 0.3) {
            return 'high';
        } elseif ($score > 1 || $complexSentenceRatio > 0.15) {
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
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_SUBMITTED => 'Submitted',
            self::STATUS_GRADED => 'Graded',
            self::STATUS_RETURNED => 'Returned',
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_REJECTED => 'Rejected',
        ];
    }

    public static function getGradeLetters()
    {
        return [
            'A' => 'Excellent',
            'B' => 'Good',
            'C' => 'Average',
            'D' => 'Below Average',
            'F' => 'Fail',
        ];
    }
}
