<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Role;
use App\Models\User;

class ZenithaLmsRoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $role
     * @return mixed
     */
    public function handle(Request $request, Closure $next, $role)
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
        if (!$this->hasRole($user, $role)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient permissions',
                    'code' => 403,
                    'required_role' => $role,
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
            'is_admin' => $this->hasRole($user, 'admin'),
            'is_instructor' => $this->hasRole($user, 'instructor'),
            'is_student' => $this->hasRole($user, 'student'),
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
    private function hasRole($user, $role)
    {
        // Use the User model's helper methods for consistency
        switch ($role) {
            case 'admin':
                return $user->isAdmin();
            case 'instructor':
                return $user->isInstructor();
            case 'student':
                return $user->isStudent();
            case 'organization':
                return $user->isOrganization();
            default:
                // Handle multiple roles
                if (str_contains($role, '|')) {
                    $roles = explode('|', $role);
                    return in_array($user->role_name, $roles);
                }
                return $user->role_name === $role;
        }
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
