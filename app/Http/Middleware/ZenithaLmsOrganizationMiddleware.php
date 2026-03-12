<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Organization;
use App\Models\User;

class ZenithaLmsOrganizationMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
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
        
        // Check if user belongs to an organization
        if (!$user->organization_id) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Organization membership required',
                    'code' => 403
                ], 403);
            }
            
            return redirect()->route('dashboard')
                ->with('error', 'You must belong to an organization to access this page.');
        }
        
        // Get organization
        $organization = $user->organization;
        
        if (!$organization) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Organization not found',
                    'code' => 404
                ], 404);
            }
            
            return redirect()->route('dashboard')
                ->with('error', 'Organization not found.');
        }
        
        // Check if organization is active
        if (!$this->isOrganizationActive($organization)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Organization is not active',
                    'code' => 403
                ], 403);
            }
            
            return redirect()->route('dashboard')
                ->with('error', 'Your organization is not active. Please contact support.');
        }
        
        // Check user's organization role
        if (!$this->hasOrganizationAccess($user, $organization)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient organization permissions',
                    'code' => 403
                ], 403);
            }
            
            return redirect()->route('dashboard')
                ->with('error', 'You do not have permission to access this organization.');
        }
        
        // Add organization data to request
        $request->merge([
            'organization' => $organization,
            'organization_id' => $organization->id,
            'organization_role' => $this->getOrganizationRole($user, $organization),
            'organization_permissions' => $this->getOrganizationPermissions($user, $organization),
            'is_organization_admin' => $this->isOrganizationAdmin($user, $organization),
            'is_organization_instructor' => $this->isOrganizationInstructor($user, $organization),
        ]);
        
        // Set organization context
        $this->setOrganizationContext($organization);
        
        return $next($request);
    }
    
    /**
     * Check if organization is active
     */
    private function isOrganizationActive($organization)
    {
        return $organization->status === 'active' && 
               $organization->is_active;
    }
    
    /**
     * Check if user has access to organization
     */
    private function hasOrganizationAccess($user, $organization)
    {
        // Check if user is member of the organization
        if ($user->organization_id !== $organization->id) {
            return false;
        }
        
        // Check if user's organization membership is active
        $membership = $user->organizationMemberships()
            ->where('organization_id', $organization->id)
            ->first();
        
        if (!$membership || !$membership->is_active) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Get user's role in the organization
     */
    private function getOrganizationRole($user, $organization)
    {
        $membership = $user->organizationMemberships()
            ->where('organization_id', $organization->id)
            ->first();
        
        return $membership ? $membership->role : 'member';
    }
    
    /**
     * Get user's organization permissions
     */
    private function getOrganizationPermissions($user, $organization)
    {
        $role = $this->getOrganizationRole($user, $organization);
        
        switch ($role) {
            case 'admin':
                return [
                    'manage_organization' => true,
                    'manage_users' => true,
                    'manage_courses' => true,
                    'manage_settings' => true,
                    'view_analytics' => true,
                    'manage_billing' => true,
                    'manage_themes' => true,
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
                
            case 'member':
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
                    'view_public_content' => true,
                ];
        }
    }
    
    /**
     * Check if user is organization admin
     */
    private function isOrganizationAdmin($user, $organization)
    {
        return $this->getOrganizationRole($user, $organization) === 'admin';
    }
    
    /**
     * Check if user is organization instructor
     */
    private function isOrganizationInstructor($user, $organization)
    {
        return $this->getOrganizationRole($user, $organization) === 'instructor';
    }
    
    /**
     * Set organization context for the request
     */
    private function setOrganizationContext($organization)
    {
        // Set organization in session
        session(['current_organization' => $organization->id]);
        
        // Set organization in config for multi-tenant
        config(['zenithalms.organization_id' => $organization->id]);
        
        // Set database connection for organization if multi-tenant
        if ($organization->database_name) {
            config(['database.connections.organization.database' => $organization->database_name]);
        }
        
        // Set organization-specific cache prefix
        config(['cache.prefix' => 'zenithalms_org_' . $organization->id]);
    }
}
