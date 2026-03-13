<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\Quiz;
use App\Services\FeatureFlagService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class CourseBuilderController extends Controller
{
    public function edit(Course $course)
    {
        // Check if course builder v2 is enabled
        if (!FeatureFlagService::isEnabled('course_builder_v2', false)) {
            return redirect()->route('courses.edit', $course);
        }

        // Load course content
        $lessons = $course->lessons()->orderBy('sort_order')->get();
        $quizzes = $course->quizzes()->orderBy('sort_order')->get();
        
        // Combine all curriculum items
        $curriculumItems = [];
        
        // Add lessons
        foreach ($lessons as $lesson) {
            $curriculumItems[] = [
                'id' => $lesson->id,
                'type' => 'lesson',
                'title' => $lesson->title,
                'sort_order' => $lesson->sort_order,
                'is_published' => $lesson->is_published,
                'is_free' => $lesson->is_free,
                'duration_minutes' => $lesson->duration_minutes,
                'created_at' => $lesson->created_at,
                'updated_at' => $lesson->updated_at,
            ];
        }
        
        // Add quizzes
        foreach ($quizzes as $quiz) {
            $curriculumItems[] = [
                'id' => $quiz->id,
                'type' => 'quiz',
                'title' => $quiz->title,
                'sort_order' => $quiz->sort_order,
                'is_published' => $quiz->is_published,
                'time_limit_minutes' => $quiz->time_limit_minutes,
                'passing_score' => $quiz->passing_score,
                'max_attempts' => $quiz->max_attempts,
                'created_at' => $quiz->created_at,
                'updated_at' => $quiz->updated_at,
            ];
        }
        
        // Sort by sort_order
        usort($curriculumItems, function ($a, $b) {
            return $a['sort_order'] <=> $b['sort_order'];
        });
        
        return view('courses.builder', compact('course', 'curriculumItems'));
    }
    
    public function updateStructure(Request $request, Course $course): JsonResponse
    {
        $structure = $request->input('structure', []);
        
        // Update sort_order for all items
        foreach ($structure as $item) {
            if ($item['type'] === 'lesson') {
                Lesson::where('id', $item['id'])->update(['sort_order' => $item['sort_order']]);
            } elseif ($item['type'] === 'quiz') {
                Quiz::where('id', $item['id'])->update(['sort_order' => $item['sort_order']]);
            }
        }
        
        return response()->json(['success' => true]);
    }
    
    public function addLesson(Request $request, Course $course): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'type' => 'required|in:video,text,quiz,assignment,live',
            'duration_minutes' => 'nullable|integer|min:0',
            'is_free' => 'boolean',
            'is_published' => 'boolean',
        ]);
        
        // Get next sort_order
        $maxSortOrder = $course->lessons()->max('sort_order') ?? 0;
        
        $lesson = Lesson::create([
            'course_id' => $course->id,
            'title' => $validated['title'],
            'slug' => str()->slug($validated['title']),
            'type' => $validated['type'],
            'duration_minutes' => $validated['duration_minutes'] ?? 0,
            'is_free' => $validated['is_free'] ?? false,
            'is_published' => $validated['is_published'] ?? false,
            'sort_order' => $maxSortOrder + 1,
        ]);
        
        return response()->json([
            'success' => true,
            'item' => [
                'id' => $lesson->id,
                'type' => 'lesson',
                'title' => $lesson->title,
                'sort_order' => $lesson->sort_order,
                'is_published' => $lesson->is_published,
                'is_free' => $lesson->is_free,
                'duration_minutes' => $lesson->duration_minutes,
                'created_at' => $lesson->created_at,
                'updated_at' => $lesson->updated_at,
            ]
        ]);
    }
    
    public function addQuiz(Request $request, Course $course): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'time_limit_minutes' => 'nullable|integer|min:0',
            'passing_score' => 'nullable|integer|min:0|max:100',
            'max_attempts' => 'nullable|integer|min:1',
            'is_published' => 'boolean',
        ]);
        
        // Get next sort_order
        $maxSortOrder = $course->quizzes()->max('sort_order') ?? 0;
        
        $quiz = Quiz::create([
            'title' => $validated['title'],
            'course_id' => $course->id,
            'instructor_id' => auth()->id(),
            'time_limit_minutes' => $validated['time_limit_minutes'] ?? 60,
            'passing_score' => $validated['passing_score'] ?? 70,
            'max_attempts' => $validated['max_attempts'] ?? 3,
            'is_published' => $validated['is_published'] ?? false,
            'sort_order' => $maxSortOrder + 1,
        ]);
        
        return response()->json([
            'success' => true,
            'item' => [
                'id' => $quiz->id,
                'type' => 'quiz',
                'title' => $quiz->title,
                'sort_order' => $quiz->sort_order,
                'is_published' => $quiz->is_published,
                'time_limit_minutes' => $quiz->time_limit_minutes,
                'passing_score' => $quiz->passing_score,
                'max_attempts' => $quiz->max_attempts,
                'created_at' => $quiz->created_at,
                'updated_at' => $quiz->updated_at,
            ]
        ]);
    }
    
    public function updateItem(Request $request, Course $course, string $type, int $id): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'is_published' => 'boolean',
        ]);
        
        if ($type === 'lesson') {
            $item = Lesson::findOrFail($id);
            $item->update([
                'title' => $validated['title'],
                'is_published' => $validated['is_published'],
            ]);
        } elseif ($type === 'quiz') {
            $item = Quiz::findOrFail($id);
            $item->update([
                'title' => $validated['title'],
                'is_published' => $validated['is_published'],
            ]);
        }
        
        return response()->json(['success' => true]);
    }
    
    public function deleteItem(Course $course, string $type, int $id): JsonResponse
    {
        if ($type === 'lesson') {
            Lesson::findOrFail($id)->delete();
        } elseif ($type === 'quiz') {
            Quiz::findOrFail($id)->delete();
        }
        
        return response()->json(['success' => true]);
    }
}
