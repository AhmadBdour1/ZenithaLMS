<?php

namespace App\Services;

use App\Models\User;
use App\Models\Course;
use App\Models\StudentProgress;
use App\Models\Enrollment;
use App\Models\AIAssistant;
use Carbon\Carbon;

class AdaptiveLearningService
{
    /**
     * Generate personalized learning path for a student
     */
    public function generateLearningPath(User $student, Course $course): array
    {
        $progress = StudentProgress::where('user_id', $student->id)
            ->where('course_id', $course->id)
            ->get();

        $learningStyle = $this->analyzeLearningStyle($student, $course);
        $skillLevel = $this->assessSkillLevel($student, $course);
        $weakAreas = $this->identifyWeakAreas($progress);
        $preferredPace = $this->calculatePreferredPace($student);

        return [
            'learning_style' => $learningStyle,
            'skill_level' => $skillLevel,
            'weak_areas' => $weakAreas,
            'preferred_pace' => $preferredPace,
            'recommendations' => $this->generateRecommendations($learningStyle, $skillLevel, $weakAreas),
            'adaptive_content' => $this->generateAdaptiveContent($learningStyle, $skillLevel),
            'next_steps' => $this->calculateNextSteps($progress, $skillLevel),
        ];
    }

    /**
     * Analyze student's learning style based on interaction patterns
     */
    private function analyzeLearningStyle(User $student, Course $course): array
    {
        $interactions = AIAssistant::where('user_id', $student->id)
            ->where('course_id', $course->id)
            ->get();

        $learningStyle = [
            'visual' => 0,
            'auditory' => 0,
            'kinesthetic' => 0,
            'reading' => 0,
        ];

        // Analyze interaction patterns
        foreach ($interactions as $interaction) {
            $conversation = json_decode($interaction->conversation_history, true);
            
            foreach ($conversation as $message) {
                if ($message['role'] === 'user') {
                    $text = strtolower($message['message']);
                    
                    // Visual indicators
                    if (preg_match('/see|look|watch|diagram|chart|image|visual/', $text)) {
                        $learningStyle['visual'] += 2;
                    }
                    
                    // Auditory indicators
                    if (preg_match('/listen|hear|explain|discuss|talk|audio/', $text)) {
                        $learningStyle['auditory'] += 2;
                    }
                    
                    // Kinesthetic indicators
                    if (preg_match('/practice|try|do|hands|experiment|build/', $text)) {
                        $learningStyle['kinesthetic'] += 2;
                    }
                    
                    // Reading indicators
                    if (preg_match('/read|text|write|notes|documentation/', $text)) {
                        $learningStyle['reading'] += 2;
                    }
                }
            }
        }

        // Normalize scores
        $total = array_sum($learningStyle);
        if ($total > 0) {
            foreach ($learningStyle as $key => $value) {
                $learningStyle[$key] = round(($value / $total) * 100, 1);
            }
        }

        arsort($learningStyle);
        return $learningStyle;
    }

    /**
     * Assess student's skill level in the course
     */
    private function assessSkillLevel(User $student, Course $course): string
    {
        $enrollment = Enrollment::where('user_id', $student->id)
            ->where('course_id', $course->id)
            ->first();

        if (!$enrollment) {
            return 'beginner';
        }

        $progressPercentage = $enrollment->progress_percentage;
        $averageScore = $this->calculateAverageScore($student, $course);
        $timeSpent = $this->calculateTimeSpent($student, $course);

        // Skill level assessment algorithm
        if ($progressPercentage < 20 && $averageScore < 60) {
            return 'beginner';
        } elseif ($progressPercentage < 60 && $averageScore < 75) {
            return 'intermediate';
        } elseif ($progressPercentage < 85 && $averageScore < 85) {
            return 'advanced';
        } else {
            return 'expert';
        }
    }

    /**
     * Identify weak areas based on progress data
     */
    private function identifyWeakAreas($progress): array
    {
        $weakAreas = [];
        
        foreach ($progress as $item) {
            if ($item->score < 70) {
                $weakAreas[] = [
                    'lesson_id' => $item->lesson_id,
                    'assessment_id' => $item->assessment_id,
                    'score' => $item->score,
                    'time_spent' => $item->time_spent_minutes,
                    'attempts' => $this->countAttempts($item),
                ];
            }
        }

        return $weakAreas;
    }

    /**
     * Calculate preferred learning pace
     */
    private function calculatePreferredPace(User $student): string
    {
        $recentProgress = StudentProgress::where('user_id', $student->id)
            ->where('created_at', '>=', Carbon::now()->subDays(30))
            ->get();

        if ($recentProgress->isEmpty()) {
            return 'normal';
        }

        $totalTime = $recentProgress->sum('time_spent_minutes');
        $completedLessons = $recentProgress->where('status', 'completed')->count();
        
        if ($completedLessons > 0) {
            $avgTimePerLesson = $totalTime / $completedLessons;
            
            if ($avgTimePerLesson < 30) {
                return 'fast';
            } elseif ($avgTimePerLesson > 90) {
                return 'slow';
            }
        }

        return 'normal';
    }

    /**
     * Generate personalized recommendations
     */
    private function generateRecommendations(array $learningStyle, string $skillLevel, array $weakAreas): array
    {
        $recommendations = [];

        // Learning style recommendations
        $primaryStyle = array_key_first($learningStyle);
        
        switch ($primaryStyle) {
            case 'visual':
                $recommendations[] = [
                    'type' => 'content',
                    'title' => 'Visual Learning Materials',
                    'description' => 'We recommend more videos, diagrams, and visual explanations',
                    'priority' => 'high',
                ];
                break;
            case 'auditory':
                $recommendations[] = [
                    'type' => 'content',
                    'title' => 'Audio Learning Materials',
                    'description' => 'Try podcasts, audio explanations, and discussion groups',
                    'priority' => 'high',
                ];
                break;
            case 'kinesthetic':
                $recommendations[] = [
                    'type' => 'content',
                    'title' => 'Hands-on Practice',
                    'description' => 'Focus on practical exercises and real-world projects',
                    'priority' => 'high',
                ];
                break;
            case 'reading':
                $recommendations[] = [
                    'type' => 'content',
                    'title' => 'Reading Materials',
                    'description' => 'Comprehensive text documentation and written explanations',
                    'priority' => 'high',
                ];
                break;
        }

        // Skill level recommendations
        switch ($skillLevel) {
            case 'beginner':
                $recommendations[] = [
                    'type' => 'pace',
                    'title' => 'Start with Basics',
                    'description' => 'Focus on fundamental concepts before advancing',
                    'priority' => 'high',
                ];
                break;
            case 'intermediate':
                $recommendations[] = [
                    'type' => 'challenge',
                    'title' => 'Intermediate Challenges',
                    'description' => 'Ready for more complex problems and projects',
                    'priority' => 'medium',
                ];
                break;
            case 'advanced':
                $recommendations[] = [
                    'type' => 'challenge',
                    'title' => 'Advanced Topics',
                    'description' => 'Explore advanced concepts and expert-level content',
                    'priority' => 'medium',
                ];
                break;
        }

        // Weak area recommendations
        foreach ($weakAreas as $area) {
            $recommendations[] = [
                'type' => 'remediation',
                'title' => 'Review Needed',
                'description' => "Spend more time on Lesson {$area['lesson_id']} - Score: {$area['score']}%",
                'priority' => 'high',
            ];
        }

        return $recommendations;
    }

    /**
     * Generate adaptive content based on learning style and skill level
     */
    private function generateAdaptiveContent(array $learningStyle, string $skillLevel): array
    {
        $content = [];

        $primaryStyle = array_key_first($learningStyle);
        
        // Content type recommendations
        switch ($primaryStyle) {
            case 'visual':
                $content['preferred_types'] = ['video', 'diagram', 'infographic', 'screenshot'];
                break;
            case 'auditory':
                $content['preferred_types'] = ['audio', 'podcast', 'discussion', 'explanation'];
                break;
            case 'kinesthetic':
                $content['preferred_types'] = ['exercise', 'project', 'simulation', 'hands-on'];
                break;
            case 'reading':
                $content['preferred_types'] = ['text', 'documentation', 'article', 'notes'];
                break;
        }

        // Difficulty level
        switch ($skillLevel) {
            case 'beginner':
                $content['difficulty'] = 'easy';
                $content['complexity'] = 'low';
                break;
            case 'intermediate':
                $content['difficulty'] = 'medium';
                $content['complexity'] = 'moderate';
                break;
            case 'advanced':
                $content['difficulty'] = 'hard';
                $content['complexity'] = 'high';
                break;
            case 'expert':
                $content['difficulty'] = 'expert';
                $content['complexity'] = 'very_high';
                break;
        }

        return $content;
    }

    /**
     * Calculate next steps in learning path
     */
    private function calculateNextSteps($progress, string $skillLevel): array
    {
        $nextSteps = [];

        // Find current position
        $completedLessons = $progress->where('status', 'completed')->pluck('lesson_id');
        $inProgressLessons = $progress->where('status', 'in_progress')->pluck('lesson_id');
        
        // Determine next lesson
        if ($inProgressLessons->isNotEmpty()) {
            $nextSteps[] = [
                'type' => 'continue',
                'lesson_id' => $inProgressLessons->first(),
                'action' => 'Continue current lesson',
                'priority' => 'high',
            ];
        } else {
            $nextSteps[] = [
                'type' => 'start',
                'lesson_id' => $this->getNextLessonId($completedLessons),
                'action' => 'Start next lesson',
                'priority' => 'high',
            ];
        }

        // Review recommendations
        $lowScores = $progress->where('score', '<', 70);
        if ($lowScores->isNotEmpty()) {
            $nextSteps[] = [
                'type' => 'review',
                'lesson_id' => $lowScores->first()->lesson_id,
                'action' => 'Review previous lesson',
                'priority' => 'medium',
            ];
        }

        // Practice recommendations
        if ($skillLevel === 'intermediate' || $skillLevel === 'advanced') {
            $nextSteps[] = [
                'type' => 'practice',
                'action' => 'Complete practice exercises',
                'priority' => 'medium',
            ];
        }

        return $nextSteps;
    }

    /**
     * Update learning path based on new progress data
     */
    public function updateLearningPath(User $student, Course $course): array
    {
        $learningPath = $this->generateLearningPath($student, $course);
        
        // Store updated learning path
        $this->storeLearningPath($student, $course, $learningPath);
        
        // Trigger AI assistant with new recommendations
        $this->notifyAIAssistant($student, $course, $learningPath);
        
        return $learningPath;
    }

    /**
     * Store learning path in database or cache
     */
    private function storeLearningPath(User $student, Course $course, array $learningPath): void
    {
        // Store in cache for quick access
        $cacheKey = "learning_path_{$student->id}_{$course->id}";
        cache()->put($cacheKey, $learningPath, now()->addHours(24));
    }

    /**
     * Notify AI assistant about updated learning path
     */
    private function notifyAIAssistant(User $student, Course $course, array $learningPath): void
    {
        $recommendations = $learningPath['recommendations'];
        $nextSteps = $learningPath['next_steps'];
        
        $message = "I've updated your learning path based on your recent progress. ";
        
        if (!empty($recommendations)) {
            $message .= "Here are my recommendations: ";
            foreach ($recommendations as $rec) {
                $message .= "{$rec['title']}: {$rec['description']}. ";
            }
        }
        
        if (!empty($nextSteps)) {
            $message .= "Next steps: ";
            foreach ($nextSteps as $step) {
                $message .= "{$step['action']}. ";
            }
        }

        // Create AI assistant notification
        AIAssistant::create([
            'user_id' => $student->id,
            'course_id' => $course->id,
            'session_id' => 'adaptive_learning_' . uniqid(),
            'type' => 'notification',
            'conversation_history' => json_encode([
                [
                    'role' => 'assistant',
                    'message' => $message,
                    'timestamp' => now()->toISOString()
                ]
            ]),
            'context_data' => json_encode([
                'learning_path_updated' => true,
                'recommendations' => $recommendations,
                'next_steps' => $nextSteps
            ]),
            'message_count' => 1,
            'last_activity_at' => now(),
            'is_active' => true,
        ]);
    }

    // Helper methods
    private function calculateAverageScore(User $student, Course $course): float
    {
        return StudentProgress::where('user_id', $student->id)
            ->where('course_id', $course->id)
            ->whereNotNull('score')
            ->avg('score') ?: 0;
    }

    private function calculateTimeSpent(User $student, Course $course): int
    {
        return StudentProgress::where('user_id', $student->id)
            ->where('course_id', $course->id)
            ->sum('time_spent_minutes') ?: 0;
    }

    private function countAttempts($item): int
    {
        // This would be implemented based on your assessment tracking
        return 1;
    }

    private function getNextLessonId($completedLessons): int
    {
        // This would be implemented based on your course structure
        return ($completedLessons->max() ?? 0) + 1;
    }
}
