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
        
        // Normalize roles (handle comma and pipe separators)
        $normalizedRoles = [];
        foreach ($roles as $role) {
            if (str_contains($role, ',')) {
                $normalizedRoles = array_merge($normalizedRoles, explode(',', $role));
            } elseif (str_contains($role, '|')) {
                $normalizedRoles = array_merge($normalizedRoles, explode('|', $role));
            } else {
                $normalizedRoles[] = $role;
            }
        }
        
        // Check if user has any of the required roles
        $hasRole = false;
        foreach ($normalizedRoles as $role) {
            if ($this->hasRole($user, trim($role))) {
                $hasRole = true;
                break;
            }
        }
        
        if (!$hasRole) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient permissions',
                    'code' => 403,
                    'required_roles' => $normalizedRoles,
                    'user_role' => $this->getUserRoleName($user)
                ], 403);
            }
            
            // Return 403 for web requests instead of redirecting to login
            abort(403, 'You do not have permission to access this page.');
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
        $user->loadMissing('role');

        $name =
            $user->role->name
            ?? Role::query()->whereKey($user->role_id)->value('name')
            ?? (string) ($user->role_name ?? '');

        return strtolower(trim((string) $name));
    }
    
    /**
     * Check if user has the specified role
     */
    private function hasRole($user, $role)
    {
        $userRoleName = $this->getUserRoleName($user);
        
        switch ($role) {
            case 'admin':
                return $userRoleName === 'admin';
            case 'instructor':
                return $userRoleName === 'instructor';
            case 'student':
                return $userRoleName === 'student';
            case 'organization':
            case 'organization_admin':
                return $userRoleName === 'organization_admin';
            default:
                // Handle multiple roles
                if (str_contains($role, '|')) {
                    $roles = explode('|', $role);
                    return in_array($userRoleName, $roles);
                }
                return $userRoleName === $role;
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
        $roleName = $this->getUserRoleName($user);
        
        switch ($roleName) {
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
                
            case 'organization_admin':
                return [
                    'manage_members' => true,
                    'assign_courses' => true,
                    'view_progress' => true,
                    'manage_organization_settings' => true,
                    'view_analytics' => true,
                ];
                
            default:
                return [
                    'view_courses' => true,
                    'view_public_content' => true,
                ];
        }
    }
}
