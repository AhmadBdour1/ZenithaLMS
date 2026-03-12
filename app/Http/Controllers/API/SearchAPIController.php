<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Ebook;
use App\Models\Quiz;
use App\Models\Forum;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SearchAPIController extends Controller
{
    /**
     * Search across all content types.
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'q' => 'required|string|min:2|max:255',
            'type' => 'nullable|string|in:courses,ebooks,quizzes,forums,all',
            'limit' => 'nullable|integer|min:1|max:50'
        ]);

        $query = $request->get('q');
        $type = $request->get('type', 'all');
        $limit = $request->get('limit', 20);

        $results = [];
        $totalResults = 0;

        if ($type === 'all' || $type === 'courses') {
            $courses = Course::where('title', 'LIKE', "%{$query}%")
                ->orWhere('description', 'LIKE', "%{$query}%")
                ->orWhere('short_description', 'LIKE', "%{$query}%")
                ->where('is_published', true)
                ->limit($limit)
                ->get(['id', 'title', 'description', 'slug', 'price', 'thumbnail']);

            foreach ($courses as $course) {
                $results[] = [
                    'type' => 'course',
                    'id' => $course->id,
                    'title' => $course->title,
                    'description' => $course->description,
                    'slug' => $course->slug,
                    'price' => $course->price,
                    'thumbnail' => $course->thumbnail,
                    'url' => route('courses.show', $course->slug)
                ];
            }
            $totalResults += $courses->count();
        }

        if ($type === 'all' || $type === 'ebooks') {
            $ebooks = Ebook::where('title', 'LIKE', "%{$query}%")
                ->orWhere('description', 'LIKE', "%{$query}%")
                ->orWhere('author', 'LIKE', "%{$query}%")
                ->where('is_published', true)
                ->limit($limit)
                ->get(['id', 'title', 'description', 'author', 'price', 'cover_image']);

            foreach ($ebooks as $ebook) {
                $results[] = [
                    'type' => 'ebook',
                    'id' => $ebook->id,
                    'title' => $ebook->title,
                    'description' => $ebook->description,
                    'author' => $ebook->author,
                    'price' => $ebook->price,
                    'thumbnail' => $ebook->cover_image,
                    'url' => route('ebooks.show', $ebook->id)
                ];
            }
            $totalResults += $ebooks->count();
        }

        if ($type === 'all' || $type === 'quizzes') {
            $quizzes = Quiz::where('title', 'LIKE', "%{$query}%")
                ->orWhere('description', 'LIKE', "%{$query}%")
                ->where('is_published', true)
                ->limit($limit)
                ->get(['id', 'title', 'description', 'duration_minutes', 'questions_count']);

            foreach ($quizzes as $quiz) {
                $results[] = [
                    'type' => 'quiz',
                    'id' => $quiz->id,
                    'title' => $quiz->title,
                    'description' => $quiz->description,
                    'duration_minutes' => $quiz->duration_minutes,
                    'questions_count' => $quiz->questions_count,
                    'url' => route('quizzes.show', $quiz->id)
                ];
            }
            $totalResults += $quizzes->count();
        }

        if ($type === 'all' || $type === 'forums') {
            $forums = Forum::where('title', 'LIKE', "%{$query}%")
                ->orWhere('content', 'LIKE', "%{$query}%")
                ->limit($limit)
                ->get(['id', 'title', 'content', 'user_id', 'created_at']);

            foreach ($forums as $forum) {
                $results[] = [
                    'type' => 'forum',
                    'id' => $forum->id,
                    'title' => $forum->title,
                    'description' => $forum->content,
                    'user_id' => $forum->user_id,
                    'created_at' => $forum->created_at,
                    'url' => route('forums.show', $forum->id)
                ];
            }
            $totalResults += $forums->count();
        }

        return response()->json([
            'query' => $query,
            'type' => $type,
            'total_results' => $totalResults,
            'results' => $results,
            'limit' => $limit
        ]);
    }
}
