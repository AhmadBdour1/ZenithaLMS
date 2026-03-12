<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class AdminPermissionsController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'admin']);
    }

    /**
     * Get all roles for management
     */
    public function index()
    {
        $roles = Role::with('permissions')->paginate(15);
        
        return response()->json([
            'success' => true,
            'data' => $roles
        ]);
    }

    /**
     * Get permissions dashboard
     */
    public function dashboard()
    {
        $stats = [
            'total_permissions' => Permission::count(),
            'active_permissions' => Permission::active()->count(),
            'system_permissions' => Permission::system()->count(),
            'custom_permissions' => Permission::custom()->count(),
            'total_roles' => Role::count(),
            'active_roles' => Role::active()->count(),
            'system_roles' => Role::system()->count(),
            'custom_roles' => Role::custom()->count(),
            'users_with_permissions' => User::whereHas('permissions')->count(),
            'users_with_roles' => User::whereHas('roles')->count(),
        ];

        $recentActivities = [
            [
                'type' => 'permission_created',
                'description' => 'Permission "user.create" was created',
                'timestamp' => now()->subMinutes(15),
            ],
            [
                'type' => 'role_updated',
                'description' => 'Role "Manager" permissions were updated',
                'timestamp' => now()->subHours(2),
            ],
            [
                'type' => 'user_assigned',
                'description' => 'User "John Doe" was assigned to "Instructor" role',
                'timestamp' => now()->subHours(4),
            ],
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'stats' => $stats,
                'recent_activities' => $recentActivities,
            ],
        ]);
    }

    /**
     * Get all permissions with filters
     */
    public function getPermissions(Request $request)
    {
        $query = Permission::query();

        // Filters
        if ($request->group) {
            $query->byGroup($request->group);
        }
        if ($request->type) {
            $query->byType($request->type);
        }
        if ($request->entity) {
            $query->byEntity($request->entity);
        }
        if ($request->is_system !== null) {
            $query->where('is_system', $request->is_system);
        }
        if ($request->is_active !== null) {
            $query->where('is_active', $request->is_active);
        }
        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('description', 'like', '%' . $request->search . '%')
                  ->orWhere('slug', 'like', '%' . $request->search . '%');
            });
        }

        $permissions = $query->with(['roles', 'users'])
            ->ordered()
            ->paginate($request->per_page ?? 20);

        return response()->json([
            'success' => true,
            'data' => $permissions,
        ]);
    }

    /**
     * Create new permission
     */
    public function createPermission(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'group' => 'required|in:users,courses,subscriptions,marketplace,pagebuilder,certificates,stuff,admin,system',
            'type' => 'required|in:create,read,update,delete,manage,admin',
            'entity' => 'required|in:user,course,subscription,aura_product,aura_order,aura_page,certificate,stuff,admin_setting',
            'action' => 'required|in:create,view,edit,delete,publish,archive,manage,configure',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $permission = Permission::createPermission(
            $request->name,
            $request->group,
            $request->type,
            $request->entity,
            $request->action,
            $request->description
        );

        if ($request->has('is_active')) {
            $permission->update(['is_active' => $request->is_active]);
        }

        if ($request->has('sort_order')) {
            $permission->update(['sort_order' => $request->sort_order]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Permission created successfully',
            'data' => $permission->fresh(['roles', 'users']),
        ], 201);
    }

    /**
     * Update permission
     */
    public function updatePermission(Request $request, $id)
    {
        $permission = Permission::findOrFail($id);

        if ($permission->is_system) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot modify system permissions',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string|max:500',
            'group' => 'sometimes|required|in:users,courses,subscriptions,marketplace,pagebuilder,certificates,stuff,admin,system',
            'type' => 'sometimes|required|in:create,read,update,delete,manage,admin',
            'entity' => 'sometimes|required|in:user,course,subscription,aura_product,aura_order,aura_page,certificate,stuff,admin_setting',
            'action' => 'sometimes|required|in:create,view,edit,delete,publish,archive,manage,configure',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $updateData = $request->only(['name', 'description', 'group', 'type', 'entity', 'action', 'is_active', 'sort_order']);

        // Update slug if name, entity, or action changed
        if ($request->has('name') || $request->has('entity') || $request->has('action')) {
            $updateData['slug'] = Permission::generateSlug(
                $request->name ?: $permission->name,
                $request->entity ?: $permission->entity,
                $request->action ?: $permission->action
            );
        }

        $permission->update($updateData);

        return response()->json([
            'success' => true,
            'message' => 'Permission updated successfully',
            'data' => $permission->fresh(['roles', 'users']),
        ]);
    }

    /**
     * Delete permission
     */
    public function deletePermission($id)
    {
        $permission = Permission::findOrFail($id);

        if ($permission->is_system) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete system permissions',
            ], 403);
        }

        if (!$permission->canBeDeleted()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete permission that is assigned to roles or users',
            ], 400);
        }

        $permission->delete();

        return response()->json([
            'success' => true,
            'message' => 'Permission deleted successfully',
        ]);
    }

    /**
     * Get all roles with filters
     */
    public function getRoles(Request $request)
    {
        $query = Role::query();

        // Filters
        if ($request->is_system !== null) {
            $query->where('is_system', $request->is_system);
        }
        if ($request->is_active !== null) {
            $query->where('is_active', $request->is_active);
        }
        if ($request->min_level) {
            $query->where('level', '>=', $request->min_level);
        }
        if ($request->max_level) {
            $query->where('level', '<=', $request->max_level);
        }
        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('description', 'like', '%' . $request->search . '%')
                  ->orWhere('slug', 'like', '%' . $request->search . '%');
            });
        }

        $roles = $query->with(['permissions', 'users'])
            ->ordered()
            ->paginate($request->per_page ?? 20);

        return response()->json([
            'success' => true,
            'data' => $roles,
        ]);
    }

    /**
     * Create new role
     */
    public function createRole(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:roles,name',
            'description' => 'nullable|string|max:500',
            'level' => 'required|integer|min:1|max:100',
            'is_active' => 'boolean',
            'color' => 'nullable|string|max:20',
            'icon' => 'nullable|string|max:50',
            'permission_ids' => 'nullable|array',
            'permission_ids.*' => 'exists:permissions,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $role = Role::createRole($request->name, $request->description, $request->level);

        if ($request->has('is_active')) {
            $role->update(['is_active' => $request->is_active]);
        }

        if ($request->has('color')) {
            $role->update(['color' => $request->color]);
        }

        if ($request->has('icon')) {
            $role->update(['icon' => $request->icon]);
        }

        // Assign permissions if provided
        if ($request->has('permission_ids')) {
            $role->syncPermissions($request->permission_ids);
        }

        return response()->json([
            'success' => true,
            'message' => 'Role created successfully',
            'data' => $role->fresh(['permissions', 'users']),
        ], 201);
    }

    /**
     * Update role
     */
    public function updateRole(Request $request, $id)
    {
        $role = Role::findOrFail($id);

        if ($role->is_system) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot modify system roles',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255|unique:roles,name,' . $id,
            'description' => 'nullable|string|max:500',
            'level' => 'sometimes|required|integer|min:1|max:100',
            'is_active' => 'boolean',
            'color' => 'nullable|string|max:20',
            'icon' => 'nullable|string|max:50',
            'permission_ids' => 'nullable|array',
            'permission_ids.*' => 'exists:permissions,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $updateData = $request->only(['name', 'description', 'level', 'is_active', 'color', 'icon']);

        // Update slug if name changed
        if ($request->has('name')) {
            $updateData['slug'] = Role::generateSlug($request->name);
        }

        $role->update($updateData);

        // Update permissions if provided
        if ($request->has('permission_ids')) {
            $role->syncPermissions($request->permission_ids);
        }

        return response()->json([
            'success' => true,
            'message' => 'Role updated successfully',
            'data' => $role->fresh(['permissions', 'users']),
        ]);
    }

    /**
     * Delete role
     */
    public function deleteRole($id)
    {
        $role = Role::findOrFail($id);

        if ($role->is_system) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete system roles',
            ], 403);
        }

        if (!$role->canBeDeleted()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete role that is assigned to users',
            ], 400);
        }

        $role->delete();

        return response()->json([
            'success' => true,
            'message' => 'Role deleted successfully',
        ]);
    }

    /**
     * Assign permissions to role
     */
    public function assignPermissionsToRole(Request $request, $roleId)
    {
        $role = Role::findOrFail($roleId);

        $validator = Validator::make($request->all(), [
            'permission_ids' => 'required|array',
            'permission_ids.*' => 'exists:permissions,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $role->syncPermissions($request->permission_ids);

        return response()->json([
            'success' => true,
            'message' => 'Permissions assigned to role successfully',
            'data' => $role->fresh(['permissions']),
        ]);
    }

    /**
     * Remove permissions from role
     */
    public function removePermissionsFromRole(Request $request, $roleId)
    {
        $role = Role::findOrFail($roleId);

        $validator = Validator::make($request->all(), [
            'permission_ids' => 'required|array',
            'permission_ids.*' => 'exists:permissions,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $role->revokePermissions($request->permission_ids);

        return response()->json([
            'success' => true,
            'message' => 'Permissions removed from role successfully',
            'data' => $role->fresh(['permissions']),
        ]);
    }

    /**
     * Get users with their permissions and roles
     */
    public function getUsersWithPermissions(Request $request)
    {
        $query = User::query();

        // Filters
        if ($request->has_role) {
            $query->whereHas('roles', function ($q) use ($request) {
                $q->where('roles.id', $request->has_role);
            });
        }
        if ($request->has_permission) {
            $query->whereHas('permissions', function ($q) use ($request) {
                $q->where('permissions.id', $request->has_permission);
            });
        }
        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%');
            });
        }

        $users = $query->with(['roles.permissions', 'permissions'])
            ->paginate($request->per_page ?? 20);

        return response()->json([
            'success' => true,
            'data' => $users,
        ]);
    }

    /**
     * Assign role to user
     */
    public function assignRoleToUser(Request $request, $userId)
    {
        $user = User::findOrFail($userId);
        $role = Role::findOrFail($request->role_id);

        if (!$role->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot assign inactive role',
            ], 400);
        }

        $user->roles()->syncWithoutDetaching([$role->id]);
        $role->refreshUsersCount();

        return response()->json([
            'success' => true,
            'message' => 'Role assigned to user successfully',
            'data' => $user->fresh(['roles.permissions']),
        ]);
    }

    /**
     * Remove role from user
     */
    public function removeRoleFromUser(Request $request, $userId)
    {
        $user = User::findOrFail($userId);
        $role = Role::findOrFail($request->role_id);

        if ($role->is_system && $user->roles()->where('is_system', true)->count() <= 1) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot remove last system role from user',
            ], 400);
        }

        $user->roles()->detach($role->id);
        $role->refreshUsersCount();

        return response()->json([
            'success' => true,
            'message' => 'Role removed from user successfully',
            'data' => $user->fresh(['roles.permissions']),
        ]);
    }

    /**
     * Assign permission to user
     */
    public function assignPermissionToUser(Request $request, $userId)
    {
        $user = User::findOrFail($userId);
        $permission = Permission::findOrFail($request->permission_id);

        if (!$permission->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot assign inactive permission',
            ], 400);
        }

        $user->permissions()->syncWithoutDetaching([$permission->id]);

        return response()->json([
            'success' => true,
            'message' => 'Permission assigned to user successfully',
            'data' => $user->fresh(['permissions']),
        ]);
    }

    /**
     * Remove permission from user
     */
    public function removePermissionFromUser(Request $request, $userId)
    {
        $user = User::findOrFail($userId);
        $permission = Permission::findOrFail($request->permission_id);

        $user->permissions()->detach($permission->id);

        return response()->json([
            'success' => true,
            'message' => 'Permission removed from user successfully',
            'data' => $user->fresh(['permissions']),
        ]);
    }

    /**
     * Get permission options for forms
     */
    public function getPermissionOptions()
    {
        return response()->json([
            'success' => true,
            'data' => [
                'groups' => Permission::getAllGroups(),
                'types' => Permission::getAllTypes(),
                'entities' => Permission::getAllEntities(),
                'actions' => Permission::getAllActions(),
            ],
        ]);
    }

    /**
     * Get role options for forms
     */
    public function getRoleOptions()
    {
        return response()->json([
            'success' => true,
            'data' => [
                'system_roles' => Role::getAllSystemRoles(),
                'levels' => [
                    10 => 'Student',
                    30 => 'Staff',
                    35 => 'Vendor',
                    40 => 'Instructor',
                    50 => 'Supervisor',
                    70 => 'Manager',
                    90 => 'Admin',
                    100 => 'Super Admin',
                ],
            ],
        ]);
    }

    /**
     * Bulk operations on permissions
     */
    public function bulkPermissionAction(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'permission_ids' => 'required|array',
            'permission_ids.*' => 'exists:permissions,id',
            'action' => 'required|in:activate,deactivate,delete',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $permissions = Permission::whereIn('id', $request->permission_ids)->get();
        $count = 0;

        foreach ($permissions as $permission) {
            if ($permission->is_system && $request->action === 'delete') {
                continue; // Skip system permissions for deletion
            }

            switch ($request->action) {
                case 'activate':
                    $permission->update(['is_active' => true]);
                    $count++;
                    break;
                case 'deactivate':
                    $permission->update(['is_active' => false]);
                    $count++;
                    break;
                case 'delete':
                    if ($permission->canBeDeleted()) {
                        $permission->delete();
                        $count++;
                    }
                    break;
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Bulk action completed. {$count} permissions affected.",
            'affected_count' => $count,
        ]);
    }

    /**
     * Bulk operations on roles
     */
    public function bulkRoleAction(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'role_ids' => 'required|array',
            'role_ids.*' => 'exists:roles,id',
            'action' => 'required|in:activate,deactivate,delete',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $roles = Role::whereIn('id', $request->role_ids)->get();
        $count = 0;

        foreach ($roles as $role) {
            if ($role->is_system && $request->action === 'delete') {
                continue; // Skip system roles for deletion
            }

            switch ($request->action) {
                case 'activate':
                    $role->update(['is_active' => true]);
                    $count++;
                    break;
                case 'deactivate':
                    $role->update(['is_active' => false]);
                    $count++;
                    break;
                case 'delete':
                    if ($role->canBeDeleted()) {
                        $role->delete();
                        $count++;
                    }
                    break;
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Bulk action completed. {$count} roles affected.",
            'affected_count' => $count,
        ]);
    }

    /**
     * Check user permissions
     */
    public function checkUserPermissions($userId)
    {
        $user = User::findOrFail($userId);

        $permissions = [
            'direct_permissions' => $user->permissions()->pluck('slug')->toArray(),
            'role_permissions' => $user->getPermissionsViaRoles()->pluck('slug')->toArray(),
            'all_permissions' => $user->getAllPermissions()->pluck('slug')->toArray(),
            'roles' => $user->roles()->pluck('slug')->toArray(),
        ];

        return response()->json([
            'success' => true,
            'data' => $permissions,
        ]);
    }

    /**
     * Generate system permissions and roles
     */
    public function generateSystemPermissions()
    {
        // Boot system roles
        Role::bootSystemRoles();

        // Generate system permissions for all entities
        $entities = Permission::getAllEntities();
        $actions = Permission::getAllActions();
        $groups = Permission::getAllGroups();

        foreach ($entities as $entity => $entityName) {
            foreach ($actions as $action => $actionName) {
                // Determine group based on entity
                $group = match ($entity) {
                    'user' => 'users',
                    'course' => 'courses',
                    'subscription' => 'subscriptions',
                    'aura_product', 'aura_order' => 'marketplace',
                    'aura_page' => 'pagebuilder',
                    'certificate' => 'certificates',
                    'stuff' => 'stuff',
                    'admin_setting' => 'admin',
                    default => 'system',
                };

                $name = "{$actionName} {$entityName}";
                $description = "Permission to {$actionName} {$entityName}";
                $type = match ($action) {
                    'create' => 'create',
                    'view' => 'read',
                    'edit' => 'update',
                    'delete' => 'delete',
                    'publish', 'archive' => 'manage',
                    'manage', 'configure' => 'admin',
                    default => 'read',
                };

                Permission::firstOrCreate([
                    'slug' => strtolower($entity . '.' . $action),
                    'is_system' => true,
                ], [
                    'name' => $name,
                    'description' => $description,
                    'group' => $group,
                    'type' => $type,
                    'entity' => $entity,
                    'action' => $action,
                    'is_active' => true,
                    'sort_order' => 0,
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'System permissions and roles generated successfully',
        ]);
    }
}
