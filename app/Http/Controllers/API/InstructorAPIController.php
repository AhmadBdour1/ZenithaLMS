<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\User;
use App\Models\Enrollment;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class InstructorAPIController extends Controller
{
    /**
     * Get instructor dashboard statistics.
     */
    public function dashboard(Request $request): JsonResponse
    {
        $instructor = $request->user();
        
        $stats = [
            'total_courses' => Course::where('instructor_id', $instructor->id)->count(),
            'published_courses' => Course::where('instructor_id', $instructor->id)
                ->where('is_published', true)->count(),
            'total_students' => Enrollment::whereHas('course', function($query) use ($instructor) {
                $query->where('instructor_id', $instructor->id);
            })->count(),
            'total_revenue' => 0, // Enrollment::whereHas('course', function($query) use ($instructor) {
                // $query->where('instructor_id', $instructor->id);
            // })->sum('amount'),
            'monthly_revenue' => 0, // Enrollment::whereHas('course', function($query) use ($instructor) {
                // $query->where('instructor_id', $instructor->id);
            // })->whereMonth('created_at', now()->month)->sum('amount'),
            'average_rating' => 4.5, // Placeholder
            'recent_courses' => Course::where('instructor_id', $instructor->id)
                ->latest()->take(5)->get(['id', 'title', 'is_published', 'created_at']),
            'popular_courses' => Course::where('instructor_id', $instructor->id)
                ->withCount('enrollments')
                ->orderBy('enrollments_count', 'desc')
                ->take(5)
                ->get(['id', 'title', 'enrollments_count']),
        ];

        return response()->json([
            'stats' => $stats,
            'instructor' => [
                'id' => $instructor->id,
                'name' => $instructor->name,
                'email' => $instructor->email
            ],
            'recent_activities' => [], // Placeholder for recent activities
            'student_progress' => [], // Placeholder for student progress
            'last_updated' => now()->toISOString()
        ]);
    }
}
