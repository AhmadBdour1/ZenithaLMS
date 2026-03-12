<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\Course;
use App\Models\Lesson;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ZenithaLmsQuizController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth')->except(['index', 'show']);
    }

    /**
     * Display quiz listing
     */
    public function index(Request $request)
    {
        $query = Quiz::with(['course', 'lesson', 'createdBy'])
            ->where('is_published', true)
            ->orderBy('created_at', 'desc');

        // ZenithaLMS: Enhanced search
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('title', 'like', '%' . $searchTerm . '%')
                  ->orWhere('description', 'like', '%' . $searchTerm . '%');
            });
        }

        // ZenithaLMS: Filter by course
        if ($request->filled('course_id')) {
            $query->where('course_id', $request->course_id);
        }

        // ZenithaLMS: Filter by difficulty
        if ($request->filled('difficulty')) {
            $query->where('difficulty_level', $request->difficulty);
        }

        $quizzes = $query->paginate(12);
        $courses = Course::where('is_published', true)->get();

        return view('zenithalms.quiz.index', compact('quizzes', 'courses'));
    }

    /**
     * Display quiz details
     */
    public function show($id)
    {
        $quiz = Quiz::with(['course', 'lesson', 'questions', 'createdBy'])
            ->where('is_published', true)
            ->findOrFail($id);

        // ZenithaLMS: Check user attempts
        $userAttempts = Auth::check() ? 
            QuizAttempt::where('user_id', Auth::id())
                ->where('quiz_id', $quiz->id)
                ->orderBy('attempt_number', 'desc')
                ->get() : 
            collect();

        // ZenithaLMS: Check if user can attempt
        $canAttempt = Auth::check() && 
            $userAttempts->count() < $quiz->max_attempts &&
            $userAttempts->where('status', 'in_progress')->isEmpty();

        // ZenithaLMS: Get best attempt
        $bestAttempt = $userAttempts
            ->where('status', 'completed')
            ->sortByDesc('score')
            ->first();

        return view('zenithalms.quiz.show', compact(
            'quiz', 
            'userAttempts', 
            'canAttempt', 
            'bestAttempt'
        ));
    }

    /**
     * Start quiz attempt
     */
    public function start($quizId)
    {
        $user = Auth::user();
        $quiz = Quiz::findOrFail($quizId);

        // ZenithaLMS: Check if user can attempt
        $attemptCount = QuizAttempt::where('user_id', $user->id)
            ->where('quiz_id', $quiz->id)
            ->count();

        if ($attemptCount >= $quiz->max_attempts) {
            return back()->with('error', 'You have reached the maximum number of attempts');
        }

        // ZenithaLMS: Check if user has an in-progress attempt
        $inProgressAttempt = QuizAttempt::where('user_id', $user->id)
            ->where('quiz_id', $quiz->id)
            ->where('status', 'in_progress')
            ->first();

        if ($inProgressAttempt) {
            return redirect()->route('zenithalms.quiz.attempt', $inProgressAttempt->id);
        }

        // ZenithaLMS: Create new attempt
        $attemptNumber = $attemptCount + 1;
        $attempt = QuizAttempt::create([
            'user_id' => $user->id,
            'quiz_id' => $quiz->id,
            'attempt_number' => $attemptNumber,
            'status' => 'in_progress',
            'started_at' => now(),
            'answers' => [],
        ]);

        // ZenithaLMS: Load questions
        $questions = $quiz->questions()
            ->inRandomOrder($quiz->shuffle_questions)
            ->get();

        return view('zenithalms.quiz.attempt', compact('quiz', 'attempt', 'questions'));
    }

    /**
     * Display quiz attempt
     */
    public function attempt($attemptId)
    {
        $user = Auth::user();
        $attempt = QuizAttempt::with(['quiz', 'quiz.questions'])
            ->where('user_id', $user->id)
            ->findOrFail($attemptId);

        if ($attempt->status !== 'in_progress') {
            return redirect()->route('zenithalms.quiz.result', $attempt->id);
        }

        // ZenithaLMS: Check time limit
        if ($attempt->quiz->time_limit_minutes) {
            $timeElapsed = now()->diffInMinutes($attempt->started_at);
            if ($timeElapsed >= $attempt->quiz->time_limit_minutes) {
                $this->submitAttempt($attempt);
                return redirect()->route('zenithalms.quiz.result', $attempt->id);
            }
        }

        $questions = $attempt->quiz->questions()
            ->inRandomOrder($attempt->quiz->shuffle_questions)
            ->get();

        return view('zenithalms.quiz.attempt', compact('quiz', 'attempt', 'questions'));
    }

    /**
     * Submit quiz attempt
     */
    public function submit(Request $request, $attemptId)
    {
        $user = Auth::user();
        $attempt = QuizAttempt::with(['quiz', 'quiz.questions'])
            ->where('user_id', $user->id)
            ->findOrFail($attemptId);

        if ($attempt->status !== 'in_progress') {
            return back()->with('error', 'This attempt has already been submitted');
        }

        // ZenithaLMS: Calculate score
        $questions = $attempt->quiz->questions;
        $totalScore = 0;
        $correctAnswers = 0;
        $answers = [];

        foreach ($questions as $question) {
            $userAnswer = $request->input("question_{$question->id}");
            $isCorrect = $this->checkAnswer($question, $userAnswer);
            
            if ($isCorrect) {
                $correctAnswers++;
                $totalScore += $question->points;
            }

            $answers[$question->id] = [
                'answer' => $userAnswer,
                'correct' => $isCorrect,
                'points' => $isCorrect ? $question->points : 0,
            ];
        }

        // ZenithaLMS: Calculate percentage
        $totalPossiblePoints = $questions->sum('points');
        $percentage = $totalPossiblePoints > 0 ? ($totalScore / $totalPossiblePoints) * 100 : 0;

        // ZenithaLMS: Update attempt
        $attempt->update([
            'status' => 'completed',
            'score' => $totalScore,
            'percentage' => $percentage,
            'completed_at' => now(),
            'time_taken_minutes' => now()->diffInMinutes($attempt->started_at),
            'answers' => $answers,
        ]);

        // ZenithaLMS: Generate AI insights
        $this->generateAiInsights($attempt);

        return redirect()->route('zenithalms.quiz.result', $attempt->id);
    }

    /**
     * Display quiz result
     */
    public function result($attemptId)
    {
        $user = Auth::user();
        $attempt = QuizAttempt::with(['quiz', 'quiz.questions', 'quiz.course'])
            ->where('user_id', $user->id)
            ->findOrFail($attemptId);

        if ($attempt->status !== 'completed') {
            return redirect()->route('zenithalms.quiz.attempt', $attempt->id);
        }

        // ZenithaLMS: Calculate statistics
        $totalQuestions = $attempt->quiz->questions->count();
        $correctAnswers = collect($attempt->answers)->where('correct', true)->count();
        $wrongAnswers = $totalQuestions - $correctAnswers;
        $passed = $attempt->percentage >= $attempt->quiz->passing_score;

        // ZenithaLMS: Get user's attempts comparison
        $allAttempts = QuizAttempt::where('user_id', $user->id)
            ->where('quiz_id', $attempt->quiz_id)
            ->where('status', 'completed')
            ->orderBy('percentage', 'desc')
            ->get();

        return view('zenithalms.quiz.result', compact(
            'attempt', 
            'totalQuestions', 
            'correctAnswers', 
            'wrongAnswers', 
            'passed',
            'allAttempts'
        ));
    }

    /**
     * Display user's quiz history
     */
    public function history(Request $request)
    {
        $user = Auth::user();
        
        $query = QuizAttempt::with(['quiz', 'quiz.course'])
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc');

        // ZenithaLMS: Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // ZenithaLMS: Filter by quiz
        if ($request->filled('quiz_id')) {
            $query->where('quiz_id', $request->quiz_id);
        }

        $attempts = $query->paginate(20);
        $quizzes = Quiz::where('is_published', true)->get();

        return view('zenithalms.quiz.history', compact('attempts', 'quizzes'));
    }

    /**
     * ZenithaLMS: Admin methods
     */
    public function create()
    {
        $this->authorize('manage_quizzes');
        
        $courses = Course::where('is_published', true)->get();
        return view('zenithalms.quiz.create', compact('courses'));
    }

    public function store(Request $request)
    {
        $this->authorize('manage_quizzes');
        
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'course_id' => 'nullable|exists:courses,id',
            'lesson_id' => 'nullable|exists:lessons,id',
            'time_limit_minutes' => 'nullable|integer|min:1',
            'number_of_questions' => 'required|integer|min:1',
            'passing_score' => 'required|numeric|min:0|max:100',
            'max_attempts' => 'required|integer|min:1',
            'shuffle_questions' => 'boolean',
            'show_answers' => 'boolean',
            'difficulty_level' => 'required|in:easy,medium,hard',
        ]);

        $quizData = $request->except(['questions']);
        $quizData['created_by'] = Auth::id();
        $quizData['is_published'] = false;

        $quiz = Quiz::create($quizData);

        // ZenithaLMS: Create questions
        if ($request->has('questions')) {
            foreach ($request->questions as $questionData) {
                $quiz->questions()->create($questionData);
            }
        }

        return redirect()->route('zenithalms.quiz.show', $quiz->id)
            ->with('success', 'Quiz created successfully!');
    }

    public function edit($id)
    {
        $this->authorize('manage_quizzes');
        
        $quiz = Quiz::with(['questions'])->findOrFail($id);
        $courses = Course::where('is_published', true)->get();
        
        return view('zenithalms.quiz.edit', compact('quiz', 'courses'));
    }

    public function update(Request $request, $id)
    {
        $this->authorize('manage_quizzes');
        
        $quiz = Quiz::findOrFail($id);

        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'course_id' => 'nullable|exists:courses,id',
            'lesson_id' => 'nullable|exists:lessons,id',
            'time_limit_minutes' => 'nullable|integer|min:1',
            'number_of_questions' => 'required|integer|min:1',
            'passing_score' => 'required|numeric|min:0|max:100',
            'max_attempts' => 'required|integer|min:1',
            'shuffle_questions' => 'boolean',
            'show_answers' => 'boolean',
            'difficulty_level' => 'required|in:easy,medium,hard',
        ]);

        $quiz->update($request->except(['questions']));

        // ZenithaLMS: Update questions
        if ($request->has('questions')) {
            $quiz->questions()->delete();
            foreach ($request->questions as $questionData) {
                $quiz->questions()->create($questionData);
            }
        }

        return redirect()->route('zenithalms.quiz.show', $quiz->id)
            ->with('success', 'Quiz updated successfully!');
    }

    public function destroy($id)
    {
        $this->authorize('manage_quizzes');
        
        $quiz = Quiz::findOrFail($id);
        $quiz->delete();

        return redirect()->route('zenithalms.quiz.index')
            ->with('success', 'Quiz deleted successfully!');
    }

    /**
     * ZenithaLMS: AI-powered methods
     */
    private function checkAnswer($question, $userAnswer)
    {
        if ($question->question_type === 'multiple_choice') {
            return $userAnswer === $question->correct_answer;
        } elseif ($question->question_type === 'true_false') {
            return $userAnswer === $question->correct_answer;
        } elseif ($question->question_type === 'short_answer') {
            // ZenithaLMS: AI-powered answer checking for short answers
            return $this->aiCheckShortAnswer($question->correct_answer, $userAnswer);
        }
        
        return false;
    }

    private function aiCheckShortAnswer($correctAnswer, $userAnswer)
    {
        // ZenithaLMS: Simple AI-powered answer checking
        // In real implementation, this would use NLP/AI services
        $correctLower = strtolower(trim($correctAnswer));
        $userLower = strtolower(trim($userAnswer));
        
        // Exact match
        if ($correctLower === $userLower) {
            return true;
        }
        
        // Partial match (contains keywords)
        $correctWords = explode(' ', $correctLower);
        $userWords = explode(' ', $userLower);
        
        $matchingWords = array_intersect($correctWords, $userWords);
        $matchPercentage = count($matchingWords) / count($correctWords);
        
        return $matchPercentage >= 0.7; // 70% match threshold
    }

    private function generateAiInsights($attempt)
    {
        // ZenithaLMS: Generate AI-powered insights
        $insights = [
            'performance_level' => $this->getPerformanceLevel($attempt->percentage),
            'strength_areas' => $this->getStrengthAreas($attempt),
            'improvement_areas' => $this->getImprovementAreas($attempt),
            'learning_recommendations' => $this->getLearningRecommendations($attempt),
            'next_steps' => $this->getNextSteps($attempt),
        ];

        $attempt->update([
            'ai_insights' => $insights,
        ]);
    }

    private function getPerformanceLevel($percentage)
    {
        if ($percentage >= 90) return 'excellent';
        if ($percentage >= 80) return 'good';
        if ($percentage >= 70) return 'average';
        if ($percentage >= 60) return 'below_average';
        return 'poor';
    }

    private function getStrengthAreas($attempt)
    {
        $strengths = [];
        $answers = $attempt->answers ?? [];

        foreach ($answers as $questionId => $answerData) {
            if ($answerData['correct']) {
                $question = QuizQuestion::find($questionId);
                if ($question) {
                    $strengths[] = $question->category ?? 'general';
                }
            }
        }

        return array_unique($strengths);
    }

    private function getImprovementAreas($attempt)
    {
        $improvements = [];
        $answers = $attempt->answers ?? [];

        foreach ($answers as $questionId => $answerData) {
            if (!$answerData['correct']) {
                $question = QuizQuestion::find($questionId);
                if ($question) {
                    $improvements[] = $question->category ?? 'general';
                }
            }
        }

        return array_unique($improvements);
    }

    private function getLearningRecommendations($attempt)
    {
        $recommendations = [];
        $performanceLevel = $this->getPerformanceLevel($attempt->percentage);

        if ($performanceLevel === 'poor' || $performanceLevel === 'below_average') {
            $recommendations[] = 'Review basic concepts before attempting advanced topics';
            $recommendations[] = 'Consider taking prerequisite courses';
        } elseif ($performanceLevel === 'average') {
            $recommendations[] = 'Focus on weak areas identified in the analysis';
            $recommendations[] = 'Practice more questions to improve understanding';
        } elseif ($performanceLevel === 'good') {
            $recommendations[] = 'Challenge yourself with advanced topics';
            $recommendations[] = 'Try teaching concepts to reinforce learning';
        } else {
            $recommendations[] = 'Explore advanced topics and related subjects';
            $recommendations[] = 'Consider mentoring other students';
        }

        return $recommendations;
    }

    private function getNextSteps($attempt)
    {
        $nextSteps = [];
        
        if ($attempt->percentage >= $attempt->quiz->passing_score) {
            $nextSteps[] = 'You have passed this quiz!';
            $nextSteps[] = 'Move on to the next topic or course';
            
            if ($attempt->quiz->course) {
                $nextSteps[] = 'Continue with the next lesson in the course';
            }
        } else {
            $nextSteps[] = 'Review the quiz questions you got wrong';
            $nextSteps[] = 'Study the related materials and try again';
            
            if ($attempt->attempt_number < $attempt->quiz->max_attempts) {
                $nextSteps[] = 'You can attempt this quiz again';
            }
        }

        return $nextSteps;
    }
}
