<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AdminAPIController extends Controller
{
    /**
     * Get admin dashboard statistics.
     */
    public function dashboard(Request $request): JsonResponse
    {
        $user = $request->user();
        
        // Check if user is admin
        if (!$user || !$user->role_id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }
        
        $role = \App\Models\Role::find($user->role_id);
        if (!$role || $user->role_name !== 'admin') {
            return response()->json(['message' => 'Forbidden'], 403);
        }
        
        $stats = [
            'total_users' => User::count(),
            'total_students' => User::where('role_id', function($query) {
                $query->select('id')->from('roles')->where('name', 'student');
            })->count(),
            'total_instructors' => User::where('role_id', function($query) {
                $query->select('id')->from('roles')->where('name', 'instructor');
            })->count(),
            'total_courses' => Course::count(),
            'published_courses' => Course::where('is_published', true)->count(),
            'total_enrollments' => Enrollment::count(),
            'total_revenue' => 0, // Payment::where('status', 'completed')->sum('amount'),
            'monthly_revenue' => 0, // Payment::where('status', 'completed')->whereMonth('created_at', now()->month)->sum('amount'),
            'active_users' => User::where('last_login_at', '>', now()->subDays(30))->count(),
            'recent_users' => User::latest()->take(5)->get(['id', 'name', 'email', 'created_at']),
            'recent_courses' => Course::latest()->take(5)->get(['id', 'title', 'instructor_id', 'created_at']),
            'popular_courses' => Course::withCount('enrollments')
                ->orderBy('enrollments_count', 'desc')
                ->take(5)
                ->get(['id', 'title', 'enrollments_count']),
        ];

        return response()->json([
            'stats' => $stats,
            'recent_activities' => [], // Placeholder for recent activities
            'top_courses' => $stats['popular_courses'],
            'last_updated' => now()->toISOString()
        ]);
    }
}
