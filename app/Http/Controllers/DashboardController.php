<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    /**
     * Redirect user to appropriate dashboard based on role
     */
    public function redirectByRole(Request $request)
    {
        $user = Auth::user();
        
        if (!$user) {
            return redirect()->route('login');
        }
        
        $role = $user->role_name;
        
        // Redirect based on role
        switch ($role) {
            case 'admin':
                return redirect()->route('zenithalms.dashboard.admin');
            case 'instructor':
                return redirect()->route('zenithalms.dashboard.instructor');
            case 'student':
                return redirect()->route('zenithalms.dashboard.student');
            case 'organization_admin':
                return redirect()->route('zenithalms.dashboard.organization');
            default:
                // Default to student dashboard for unknown roles
                return redirect()->route('zenithalms.dashboard.student');
        }
    }
    
    /**
     * Admin dashboard with KPIs
     */
    public function admin(Request $request)
    {
        $user = Auth::user();
        
        // Safe KPI queries with counts
        $stats = [
            'total_users' => \App\Models\User::count(),
            'total_courses' => \App\Models\Course::count(),
            'total_enrollments' => \App\Models\Enrollment::count(),
            'total_revenue' => 0, // TODO: Add payment integration
            'recent_registrations' => \App\Models\User::latest()->take(5)->get(),
        ];
        
        return view('zenithalms.dashboard.admin', compact('user', 'stats'));
    }
    
    /**
     * Instructor dashboard with KPIs
     */
    public function instructor(Request $request)
    {
        $user = Auth::user();
        
        // Safe KPI queries
        $stats = [
            'my_courses' => \App\Models\Course::where('instructor_id', $user->id)->count(),
            'total_enrollments' => \App\Models\Enrollment::whereHas('course', function($query) use ($user) {
                return $query->where('instructor_id', $user->id);
            })->count(),
            'earnings' => 0, // TODO: Add payment integration
            'pending_qa' => 0, // TODO: Add Q&A system
        ];
        
        return view('zenithalms.dashboard.instructor', compact('user', 'stats'));
    }
    
    /**
     * Student dashboard with KPIs
     */
    public function student(Request $request)
    {
        $user = Auth::user();
        
        // Safe KPI queries
        $stats = [
            'enrolled_courses' => $user->enrolledCourses()->count(),
            'completed_courses' => $user->completedCourses()->count(),
            'progress_summary' => $user->getProgressStats(),
            'unread_notifications' => 0, // TODO: Add notification system
            'current_courses' => $user->enrolledCourses()->take(4)->get(),
        ];
        
        return view('zenithalms.dashboard.student', compact('user', 'stats'));
    }
    
    /**
     * Organization dashboard with KPIs
     */
    public function organization(Request $request)
    {
        $user = Auth::user();
        
        // Safe KPI queries
        $stats = [
            'members_count' => \App\Models\User::where('organization_id', $user->organization_id)->count(),
            'assigned_courses' => \App\Models\Course::where('organization_id', $user->organization_id)->count(),
            'progress_overview' => [
                'total_enrollments' => \App\Models\Enrollment::whereHas('user', function($query) use ($user) {
                    return $query->where('organization_id', $user->organization_id);
                })->count(),
                'completed_courses' => \App\Models\Enrollment::where('status', 'completed')
                    ->whereHas('user', function($query) use ($user) {
                        return $query->where('organization_id', $user->organization_id);
                    })->count(),
            ],
        ];
        
        return view('zenithalms.dashboard.organization', compact('user', 'stats'));
    }
}
