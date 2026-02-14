<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ZenithaLmsRoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  ...$roles
     * @return mixed
     */
    public function handle(Request $request, Closure $next, ...$roles)
    {
        if (!auth()->check()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authentication required',
                    'code' => 401
                ], 401);
            }
            
            return redirect()->route('login')
                ->with('error', 'You must be logged in to access this page.');
        }
        
        $user = auth()->user();
        
        // Check if user has the required role
        if (!$this->hasRole($user, $roles)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient permissions',
                    'code' => 403,
                    'required_role' => $roles,
                    'user_role' => $user->role->name ?? 'unknown'
                ], 403);
            }
            
            return redirect()->route('dashboard')
                ->with('error', 'You do not have permission to access this page.');
        }
        
        // Check if user is active
        if (!$this->isActive($user)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Account is not active',
                    'code' => 403
                ], 403);
            }
            
            auth()->logout();
            return redirect()->route('login')
                ->with('error', 'Your account is not active. Please contact support.');
        }
        
        // Add role-based data to request
        $userRoleName = $this->getUserRoleName($user);
        $request->merge([
            'user_role' => $userRoleName,
            'user_permissions' => $this->getUserPermissions($user),
            'is_admin' => $this->hasRole($user, ['admin']),
            'is_instructor' => $this->hasRole($user, ['instructor']),
            'is_student' => $this->hasRole($user, ['student']),
        ]);
        
        return $next($request);
    }
    
    /**
     * Get user role name safely
     */
    private function getUserRoleName($user)
    {
        // Use the single source of truth from User model
        return $user->role_name ?? 'user';
    }
    
    /**
     * Check if user has the specified role
     */
    private function hasRole($user, array $roles)
    {
        $normalizedRoles = [];

        foreach ($roles as $role) {
            if (!is_string($role) || $role === '') {
                continue;
            }

            // Support both `role:admin,instructor` and pipe-delimited `admin|instructor`
            $parts = preg_split('/[|,]/', $role);
            foreach ($parts as $part) {
                $part = trim($part);
                if ($part !== '') {
                    $normalizedRoles[] = $part;
                }
            }
        }

        if ($normalizedRoles === []) {
            return false;
        }

        foreach ($normalizedRoles as $role) {
            switch ($role) {
                case 'admin':
                    if ($user->isAdmin()) return true;
                    break;
                case 'instructor':
                    if ($user->isInstructor()) return true;
                    break;
                case 'student':
                    if ($user->isStudent()) return true;
                    break;
                case 'organization':
                    if ($user->isOrganization()) return true;
                    break;
                default:
                    if ($user->role_name === $role) return true;
            }
        }

        return false;
    }
    
    /**
     * Check if user is active
     */
    private function isActive($user)
    {
        return $user->is_active && 
               $user->email_verified_at !== null;
    }
    
    /**
     * Get user permissions based on role
     */
    private function getUserPermissions($user)
    {
        $role = $user->role;
        
        if (!$role) {
            return [];
        }
        
        switch ($role->name) {
            case 'admin':
                return [
                    'manage_users' => true,
                    'manage_courses' => true,
                    'manage_payments' => true,
                    'manage_settings' => true,
                    'view_analytics' => true,
                    'manage_notifications' => true,
                    'manage_themes' => true,
                    'manage_system' => true,
                ];
                
            case 'instructor':
                return [
                    'create_courses' => true,
                    'edit_courses' => true,
                    'manage_assignments' => true,
                    'manage_quizzes' => true,
                    'view_students' => true,
                    'manage_virtual_classes' => true,
                    'view_analytics' => true,
                ];
                
            case 'student':
                return [
                    'view_courses' => true,
                    'enroll_courses' => true,
                    'take_quizzes' => true,
                    'submit_assignments' => true,
                    'view_progress' => true,
                    'participate_forum' => true,
                    'view_certificates' => true,
                ];
                
            default:
                return [
                    'view_courses' => true,
                    'view_public_content' => true,
                ];
        }
    }
}
