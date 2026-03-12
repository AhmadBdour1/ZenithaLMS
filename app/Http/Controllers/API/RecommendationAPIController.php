<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class RecommendationAPIController extends Controller
{
    /**
     * Get personalized recommendations for the user.
     */
    public function getRecommendations(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $recommendations = [];
        
        // Get user's enrolled courses to analyze interests
        $enrolledCourses = $user->courses()
            ->with('category')
            ->pluck('category_id')
            ->unique()
            ->toArray();

        // Get courses from same categories
        if (!empty($enrolledCourses)) {
            $categoryRecommendations = Course::where('is_published', true)
                ->whereIn('category_id', $enrolledCourses)
                ->whereNotIn('id', $user->courses()->pluck('course_id'))
                ->with('category')
                ->limit(5)
                ->get();

            foreach ($categoryRecommendations as $course) {
                $recommendations[] = [
                    'type' => 'course',
                    'id' => $course->id,
                    'title' => $course->title,
                    'description' => $course->description,
                    'category' => $course->category->name,
                    'price' => $course->price,
                    'thumbnail' => $course->thumbnail,
                    'url' => url("/courses/{$course->slug}"),
                    'reason' => 'Based on your course interests',
                    'confidence_score' => 0.8
                ];
            }
        }

        // Get popular courses
        $popularCourses = Course::where('is_published', true)
            ->withCount('enrollments')
            ->orderBy('enrollments_count', 'desc')
            ->whereNotIn('id', $user->courses()->pluck('course_id'))
            ->limit(3)
            ->get();

        foreach ($popularCourses as $course) {
            $recommendations[] = [
                'type' => 'course',
                'id' => $course->id,
                'title' => $course->title,
                'description' => $course->description,
                'category' => $course->category->name ?? 'Uncategorized',
                'price' => $course->price,
                'thumbnail' => $course->thumbnail,
                'url' => url("/courses/{$course->slug}"),
                'reason' => 'Popular with other students',
                'confidence_score' => 0.7
            ];
        }

        // Get recently added courses
        $recentCourses = Course::where('is_published', true)
            ->orderBy('created_at', 'desc')
            ->whereNotIn('id', $user->courses()->pluck('course_id'))
            ->limit(2)
            ->get();

        foreach ($recentCourses as $course) {
            $recommendations[] = [
                'type' => 'course',
                'id' => $course->id,
                'title' => $course->title,
                'description' => $course->description,
                'category' => $course->category->name ?? 'Uncategorized',
                'price' => $course->price,
                'thumbnail' => $course->thumbnail,
                'url' => url("/courses/{$course->slug}"),
                'reason' => 'Newly added course',
                'confidence_score' => 0.6
            ];
        }

        // Sort by score and limit
        $recommendations = collect($recommendations)
            ->sortByDesc('confidence_score')
            ->take(10)
            ->values();

        return response()->json([
            'recommendations' => $recommendations,
            'total_count' => $recommendations->count(),
            'user_id' => $user->id
        ]);
    }
}
