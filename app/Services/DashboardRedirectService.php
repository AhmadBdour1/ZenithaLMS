<?php

namespace App\Services;

use App\Models\User;

class DashboardRedirectService
{
    /**
     * Get the correct dashboard route for a user based on their role
     */
    public static function getDashboardRouteForUser(User $user): string
    {
        $role = $user->role_name ?? 'student';
        
        return match($role) {
            'admin' => 'zenithalms.dashboard.admin',
            'instructor' => 'zenithalms.dashboard.instructor',
            'student' => 'zenithalms.dashboard.student',
            'organization_admin' => 'zenithalms.dashboard.organization',
            default => 'zenithalms.dashboard.student'
        };
    }
    
    /**
     * Get the dashboard URL for a user
     */
    public static function getDashboardUrlForUser(User $user): string
    {
        return route(self::getDashboardRouteForUser($user));
    }
}
