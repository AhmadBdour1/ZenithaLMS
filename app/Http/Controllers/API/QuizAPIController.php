<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Http\Requests\StoreQuizRequest;
use App\Models\Course;
use Illuminate\Support\Facades\Validator;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class QuizAPIController extends Controller
{
    use AuthorizesRequests;
    /**
     * Display a listing of quizzes.
     */
    public function index(Request $request)
    {
        $query = Quiz::orderBy('created_at', 'desc');

        // Apply filters
        if ($request->course_id) {
            $query->where('course_id', $request->course_id);
        }
        
        if ($request->difficulty) {
            $query->where('difficulty', $request->difficulty);
        }

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('title', 'like', '%' . $request->search . '%')
                  ->orWhere('description', 'like', '%' . $request->search . '%');
            });
        }

        $quizzes = $query->paginate(12);

        return response()->json([
            'data' => $quizzes->items(),
            'pagination' => [
                'current_page' => $quizzes->currentPage(),
                'last_page' => $quizzes->lastPage(),
                'per_page' => $quizzes->perPage(),
                'total' => $quizzes->total(),
            ]
        ]);
    }

    /**
     * Display the specified quiz.
     */
    public function show($id)
    {
        $quiz = Quiz::findOrFail($id);

        return response()->json([
            'id' => $quiz->id,
            'title' => $quiz->title,
            'description' => $quiz->description,
            'course_id' => $quiz->course_id,
            'duration_minutes' => $quiz->duration_minutes,
            'passing_score' => $quiz->passing_score,
            'max_attempts' => $quiz->max_attempts,
            'difficulty' => $quiz->difficulty,
            'is_published' => $quiz->is_published,
            'created_at' => $quiz->created_at,
            'updated_at' => $quiz->updated_at,
        ]);
    }

    /**
     * Start a quiz attempt.
     */
    public function start(Request $request, $quizId)
    {
        $user = $request->user();
        $quiz = Quiz::findOrFail($quizId);
        
        // Authorize quiz start
        $this->authorize('start', $quiz);
        
        // Check if user has attempts remaining (only if max_attempts is set and > 0)
        $maxAttempts = $quiz->max_attempts;
        if ($maxAttempts && $maxAttempts > 0) {
            $attemptCount = QuizAttempt::where('user_id', $user->id)
                ->where('quiz_id', $quizId)
                ->where('status', 'completed')
                ->count();

            if ($attemptCount >= $maxAttempts) {
                return response()->json([
                    'message' => 'No attempts remaining'
                ], 403);
            }
        }

        // Check if there's an ongoing attempt
        $ongoingAttempt = QuizAttempt::where('user_id', $user->id)
            ->where('quiz_id', $quizId)
            ->where('status', 'in_progress')
            ->first();

        if ($ongoingAttempt) {
            return response()->json([
                'message' => 'Quiz already in progress',
                'attempt_id' => $ongoingAttempt->id,
                'quiz' => $quiz->load('questions.options'),
                'time_limit' => $quiz->time_limit_minutes,
                'started_at' => $ongoingAttempt->started_at,
            ]);
        }

        // Create new attempt
        $attempt = QuizAttempt::create([
            'user_id' => $user->id,
            'quiz_id' => $quiz->id,
            'started_at' => now(),
            'status' => 'in_progress',
        ]);

        return response()->json([
            'attempt_id' => $attempt->id,
            'quiz' => $quiz->load('questions.options'),
            'time_limit' => $quiz->time_limit_minutes,
            'started_at' => $attempt->started_at,
        ]);
    }

    /**
     * Submit quiz attempt.
     */
    public function submit(Request $request, $attemptId)
    {
        $user = $request->user();
        
        $validator = Validator::make($request->all(), [
            'answers' => 'required|array',
            'answers.*' => 'required|integer|exists:question_options,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $attempt = QuizAttempt::where('id', $attemptId)
            ->where('user_id', $user->id)
            ->where('status', 'in_progress')
            ->firstOrFail();

        // Calculate score
        $quiz = $attempt->quiz;
        $totalQuestions = $quiz->questions->count();
        $correctAnswers = 0;

        foreach ($request->answers as $questionId => $optionId) {
            $question = $quiz->questions->find($questionId);
            if ($question && $question->correct_option_id == $optionId) {
                $correctAnswers++;
            }
        }

        $score = ($correctAnswers / $totalQuestions) * 100;
        $passed = $score >= $quiz->passing_score;

        // Update attempt
        $attempt->update([
            'answers' => $request->answers,
            'score' => $score,
            'passed' => $passed,
            'completed_at' => now(),
            'status' => 'completed',
        ]);

        return response()->json([
            'message' => 'Quiz submitted successfully',
            'attempt' => $attempt,
            'score' => $score,
            'correct_answers' => $correctAnswers,
            'total_questions' => $totalQuestions,
            'passed' => $passed,
        ]);
    }

    /**
     * Get user's quiz attempts.
     */
    public function myAttempts(Request $request)
    {
        $user = $request->user();

        $attempts = QuizAttempt::with(['quiz.course'])
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate(12);

        return response()->json([
            'data' => $attempts->items(),
            'pagination' => [
                'current_page' => $attempts->currentPage(),
                'last_page' => $attempts->lastPage(),
                'per_page' => $attempts->perPage(),
                'total' => $attempts->total(),
            ]
        ]);
    }

    /**
     * Store a newly created quiz.
     */
    public function store(StoreQuizRequest $request)
    {
        $user = $request->user();
        
        // Authorize quiz creation
        $this->authorize('create', Quiz::class);
        
        // Create quiz
        $quiz = Quiz::create([
            'title' => $request->title,
            'description' => $request->description,
            'course_id' => $request->course_id,
            'duration_minutes' => $request->duration_minutes,
            'attempts_allowed' => $request->attempts_allowed,
            'passing_score' => $request->passing_score,
            'is_active' => $request->boolean('is_active', true),
            'created_by' => $user->id,
        ]);

        // Create questions (simplified - in real app would be more complex)
        foreach ($request->questions as $questionData) {
            $question = $quiz->questions()->create([
                'question' => $questionData['question'],
                'type' => $questionData['type'],
                'points' => $questionData['points'],
            ]);

            // Add options for multiple choice questions
            if ($questionData['type'] === 'multiple_choice' && isset($questionData['options'])) {
                foreach ($questionData['options'] as $optionData) {
                    $question->options()->create([
                        'option' => $optionData['option'],
                        'is_correct' => $optionData['is_correct'],
                    ]);
                }
            }
        }

        return response()->json([
            'message' => 'Quiz created successfully',
            'quiz' => $quiz->load('questions.options'),
        ], 201);
    }

    /**
     * Update the specified quiz.
     */
    public function update(Request $request, $id)
    {
        $quiz = Quiz::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'duration_minutes' => 'sometimes|integer|min:1',
            'passing_score' => 'sometimes|integer|min:0|max:100',
            'max_attempts' => 'sometimes|integer|min:1',
            'difficulty' => 'sometimes|in:easy,medium,hard',
            'is_published' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $quiz->update($request->all());

        return response()->json([
            'message' => 'Quiz updated successfully',
            'quiz' => $quiz
        ]);
    }

    /**
     * Remove the specified quiz.
     */
    public function destroy(Request $request, $id)
    {
        $quiz = Quiz::findOrFail($id);

        // Check if user has permission (simplified - in real app, check role)
        if ($request->user()->id !== $quiz->course->instructor_id) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }

        $quiz->delete();

        return response()->json([
            'message' => 'Quiz deleted successfully'
        ]);
    }
}
