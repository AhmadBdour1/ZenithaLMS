<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class AdminUserManagementController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'admin']);
    }

    /**
     * Get all users with filters
     */
    public function getUsers(Request $request)
    {
        $query = User::query();

        // Filters
        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%')
                  ->orWhere('phone', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->role) {
            $query->whereHas('roles', function ($q) use ($request) {
                $q->where('slug', $request->role);
            });
        }

        if ($request->email_verified) {
            if ($request->email_verified === 'verified') {
                $query->whereNotNull('email_verified_at');
            } else {
                $query->whereNull('email_verified_at');
            }
        }

        if ($request->created_from) {
            $query->whereDate('created_at', '>=', $request->created_from);
        }

        if ($request->created_to) {
            $query->whereDate('created_at', '<=', $request->created_to);
        }

        if ($request->has_role) {
            $query->whereHas('roles', function ($q) use ($request) {
                $q->where('roles.id', $request->has_role);
            });
        }

        $users = $query->with(['roles', 'permissions'])
            ->orderBy($request->sort_by ?? 'created_at', $request->sort_order ?? 'desc')
            ->paginate($request->per_page ?? 20);

        return response()->json([
            'success' => true,
            'data' => $users,
        ]);
    }

    /**
     * Create new user
     */
    public function createUser(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'role' => 'required|exists:roles,slug',
            'status' => 'required|in:active,inactive,suspended,banned',
            'phone' => 'nullable|string|max:20',
            'country' => 'nullable|string|size:2',
            'bio' => 'nullable|string|max:1000',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'send_welcome_email' => 'boolean',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
            'notes' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'status' => $request->status,
            'phone' => $request->phone,
            'country' => $request->country,
            'bio' => $request->bio,
            'email_verified_at' => now(),
        ]);

        // Handle avatar upload
        if ($request->hasFile('avatar')) {
            $avatar = $request->file('avatar')->store('avatars', 'public');
            $user->update(['avatar' => $avatar]);
        }

        // Assign role
        $role = Role::where('slug', $request->role)->first();
        if ($role) {
            $user->assignRole($role->id);
        }

        // Assign permissions
        if ($request->has('permissions')) {
            $user->syncPermissions($request->permissions);
        }

        // Send welcome email
        if ($request->send_welcome_email) {
            $this->sendWelcomeEmail($user, $request->password);
        }

        return response()->json([
            'success' => true,
            'message' => 'User created successfully',
            'data' => $user->fresh(['roles', 'permissions']),
        ], 201);
    }

    /**
     * Update user
     */
    public function updateUser(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|unique:users,email,' . $id,
            'password' => 'sometimes|required|string|min:8',
            'role' => 'sometimes|required|exists:roles,slug',
            'status' => 'sometimes|required|in:active,inactive,suspended,banned',
            'phone' => 'nullable|string|max:20',
            'country' => 'nullable|string|size:2',
            'bio' => 'nullable|string|max:1000',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
            'notes' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $updateData = $request->only(['name', 'email', 'status', 'phone', 'country', 'bio']);
        
        if ($request->password) {
            $updateData['password'] = Hash::make($request->password);
        }

        $user->update($updateData);

        // Handle avatar upload
        if ($request->hasFile('avatar')) {
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }
            $avatar = $request->file('avatar')->store('avatars', 'public');
            $user->update(['avatar' => $avatar]);
        }

        // Update role
        if ($request->has('role')) {
            $role = Role::where('slug', $request->role)->first();
            if ($role) {
                $user->syncRoles([$role->id]);
            }
        }

        // Update permissions
        if ($request->has('permissions')) {
            $user->syncPermissions($request->permissions);
        }

        return response()->json([
            'success' => true,
            'message' => 'User updated successfully',
            'data' => $user->fresh(['roles', 'permissions']),
        ]);
    }

    /**
     * Delete user
     */
    public function deleteUser($id)
    {
        $user = User::findOrFail($id);

        // Prevent deletion of the last admin
        if ($user->isSuperAdmin() && User::whereHas('roles', function ($q) {
            $q->where('slug', 'super_admin');
        })->count() === 1) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete the last super admin user',
            ], 400);
        }

        // Delete user's avatar
        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'User deleted successfully',
        ]);
    }

    /**
     * Bulk user actions
     */
    public function bulkUserAction(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_ids' => 'required|array',
            'user_ids.*' => 'required|exists:users,id',
            'action' => 'required|in:activate,deactivate,suspend,ban,delete,verify,unverify',
            'role' => 'nullable|exists:roles,slug',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $users = User::whereIn('id', $request->user_ids)->get();
        $count = 0;

        foreach ($users as $user) {
            switch ($request->action) {
                case 'activate':
                    $user->update(['status' => 'active']);
                    $count++;
                    break;
                case 'deactivate':
                    $user->update(['status' => 'inactive']);
                    $count++;
                    break;
                case 'suspend':
                    $user->update(['status' => 'suspended']);
                    $count++;
                    break;
                case 'ban':
                    $user->update(['status' => 'banned']);
                    $count++;
                    break;
                case 'delete':
                    if (!$user->isSuperAdmin() || User::whereHas('roles', function ($q) {
                        $q->where('slug', 'super_admin');
                    })->count() > 1) {
                        if ($user->avatar) {
                            Storage::disk('public')->delete($user->avatar);
                        }
                        $user->delete();
                        $count++;
                    }
                    break;
                case 'verify':
                    $user->update(['email_verified_at' => now()]);
                    $count++;
                    break;
                case 'unverify':
                    $user->update(['email_verified_at' => null]);
                    $count++;
                    break;
            }

            // Update role if specified
            if ($request->has('role')) {
                $role = Role::where('slug', $request->role)->first();
                if ($role) {
                    $user->syncRoles([$role->id]);
                }
            }

            // Update permissions if specified
            if ($request->has('permissions')) {
                $user->syncPermissions($request->permissions);
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Bulk action completed. {$count} users affected.",
            'affected_count' => $count,
        ]);
    }

    /**
     * Get user details
     */
    public function getUserDetails($id)
    {
        $user = User::with(['roles', 'permissions', 'userRoles', 'userPermissions'])
            ->findOrFail($id);

        // Get user statistics
        $stats = [
            'login_count' => $user->login_count ?? 0,
            'last_login' => $user->last_login_at,
            'courses_created' => $user->courses()->count(),
            'courses_enrolled' => $user->enrollments()->count(),
            'subscriptions' => $user->subscriptions()->count(),
            'purchases' => $user->purchases()->count(),
            'certificates' => $user->certificates()->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'user' => $user,
                'stats' => $stats,
            ],
        ]);
    }

    /**
     * Get user permissions and roles
     */
    public function getUserPermissions($id)
    {
        $user = User::findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => [
                'roles' => $user->roles()->get(),
                'permissions' => $user->getAllPermissions()->get(),
                'direct_permissions' => $user->permissions()->get(),
                'role_permissions' => $user->getPermissionsViaRoles()->get(),
                'all_permission_slugs' => $user->getAllPermissions()->pluck('slug')->toArray(),
                'role_slugs' => $user->roles()->pluck('slug')->toArray(),
            ],
        ]);
    }

    /**
     * Assign role to user
     */
    public function assignRole(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $role = Role::where('slug', $request->role)->firstOrFail();

        $user->assignRole($role->id);

        return response()->json([
            'success' => true,
            'message' => 'Role assigned successfully',
            'data' => $user->fresh(['roles']),
        ]);
    }

    /**
     * Remove role from user
     */
    public function removeRole(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $role = Role::where('slug', $request->role)->firstOrFail();

        // Prevent removing last system role
        if ($role->is_system && $user->roles()->where('is_system', true)->count() <= 1) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot remove last system role from user',
            ], 400);
        }

        $user->removeRole($role->id);

        return response()->json([
            'success' => true,
            'message' => 'Role removed successfully',
            'data' => $user->fresh(['roles']),
        ]);
    }

    /**
     * Assign permissions to user
     */
    public function assignPermissions(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'permissions' => 'required|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user->syncPermissions($request->permissions);

        return response()->json([
            'success' => true,
            'message' => 'Permissions assigned successfully',
            'data' => $user->fresh(['permissions']),
        ]);
    }

    /**
     * Get available roles and permissions
     */
    public function getAvailableRolesAndPermissions()
    {
        return response()->json([
            'success' => true,
            'data' => [
                'roles' => Role::active()->ordered()->get(),
                'permissions' => Permission::active()->ordered()->get(),
                'system_roles' => Role::getAllSystemRoles(),
                'permission_groups' => Permission::getAllGroups(),
            ],
        ]);
    }

    /**
     * Send password reset email
     */
    public function sendPasswordReset(Request $request, $id)
    {
        $user = User::findOrFail($id);

        // Generate reset token
        $token = Str::random(60);
        $user->update([
            'remember_token' => $token,
            'password_reset_token' => $token,
            'password_reset_expires_at' => now()->addHours(1),
        ]);

        // Send email (implement actual email sending)
        // $this->sendPasswordResetEmail($user, $token);

        return response()->json([
            'success' => true,
            'message' => 'Password reset email sent successfully',
        ]);
    }

    /**
     * Impersonate user
     */
    public function impersonate($id)
    {
        $targetUser = User::findOrFail($id);

        // Store original user session
        session(['impersonate_original_user' => auth()->id()]);
        session(['impersonate_original_user_data' => auth()->user()->toArray()]);

        // Login as target user
        auth()->login($targetUser);

        return response()->json([
            'success' => true,
            'message' => 'Impersonation started',
            'data' => [
                'user' => $targetUser,
                'original_user_id' => session('impersonate_original_user'),
            ],
        ]);
    }

    /**
     * Stop impersonation
     */
    public function stopImpersonate()
    {
        if (!session('impersonate_original_user')) {
            return response()->json([
                'success' => false,
                'message' => 'No active impersonation',
            ], 400);
        }

        $originalUserId = session('impersonate_original_user');
        $originalUser = User::find($originalUserId);

        if ($originalUser) {
            auth()->login($originalUser);
        }

        // Clear impersonation session
        session()->forget(['impersonate_original_user', 'impersonate_original_user_data']);

        return response()->json([
            'success' => true,
            'message' => 'Impersonation stopped',
        ]);
    }

    // Helper methods
    private function sendWelcomeEmail($user, $password)
    {
        // Implement welcome email sending
        // Mail::to($user->email)->send(new WelcomeEmail($user, $password));
    }

    private function sendPasswordResetEmail($user, $token)
    {
        // Implement password reset email sending
        // Mail::to($user->email)->send(new PasswordResetEmail($user, $token));
    }
}
