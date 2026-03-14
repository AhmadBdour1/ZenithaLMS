<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\DashboardRedirectService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    /**
     * Main dashboard entry point - redirect to appropriate dashboard
     */
    public function index(Request $request): RedirectResponse
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login');
        }

        return redirect()->route(DashboardRedirectService::getDashboardRouteForUser($user));
    }

    /**
     * Redirect user to appropriate dashboard based on role
     */
    protected function redirectByRole(User $user): RedirectResponse
    {
        if ($user->hasRole('admin')) {
            return redirect()->route('zenithalms.tenant.dashboard.admin');
        }

        if ($user->hasRole('instructor')) {
            return redirect()->route('zenithalms.tenant.dashboard.instructor');
        }

        if ($user->hasRole('student')) {
            return redirect()->route('zenithalms.tenant.dashboard.student');
        }

        if ($user->hasRole('organization_admin')) {
            return redirect()->route('zenithalms.tenant.dashboard.organization');
        }

        return redirect()->route('zenithalms.tenant.dashboard.student');
    }

    /**
     * Admin dashboard with KPIs
     */
    public function admin(Request $request)
    {
        $user = Auth::user();

        $stats = [
            'total_users' => \App\Models\User::count(),
            'total_courses' => \App\Models\Course::count(),
            'total_enrollments' => \App\Models\Enrollment::count(),
            'total_revenue' => 0,
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

        $stats = [
            'my_courses' => \App\Models\Course::where('instructor_id', $user->id)->count(),
            'total_enrollments' => \App\Models\Enrollment::whereHas('course', function ($query) use ($user) {
                return $query->where('instructor_id', $user->id);
            })->count(),
            'earnings' => 0,
            'pending_qa' => 0,
        ];

        return view('zenithalms.dashboard.instructor', compact('user', 'stats'));
    }

    /**
     * Student dashboard with KPIs
     */
    public function student(Request $request)
    {
        $user = Auth::user();

        $stats = [
            'enrolled_courses' => $user->enrolledCourses()->count(),
            'completed_courses' => $user->completedCourses()->count(),
            'progress_summary' => $user->getProgressStats(),
            'unread_notifications' => 0,
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

        $stats = [
            'members_count' => \App\Models\User::where('organization_id', $user->organization_id)->count(),
            'assigned_courses' => \App\Models\Course::where('organization_id', $user->organization_id)->count(),
            'progress_overview' => [
                'total_enrollments' => \App\Models\Enrollment::whereHas('user', function ($query) use ($user) {
                    return $query->where('organization_id', $user->organization_id);
                })->count(),
                'completed_courses' => \App\Models\Enrollment::where('status', 'completed')
                    ->whereHas('user', function ($query) use ($user) {
                        return $query->where('organization_id', $user->organization_id);
                    })->count(),
            ],
        ];

        return view('zenithalms.dashboard.organization', compact('user', 'stats'));
    }
}