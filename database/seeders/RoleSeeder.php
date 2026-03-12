<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            [
                'name' => 'admin',
                'display_name' => 'Admin',
                'description' => 'Full system access with all permissions',
                'slug' => 'admin',
                'is_system' => true,
                'level' => 0,
                'is_active' => true,
            ],
            [
                'name' => 'organization_admin',
                'display_name' => 'Organization Admin',
                'description' => 'Manage organization settings and users',
                'slug' => 'organization_admin',
                'is_system' => true,
                'level' => 1,
                'is_active' => true,
            ],
            [
                'name' => 'branch_manager',
                'display_name' => 'Branch Manager',
                'description' => 'Manage branch operations and local users',
                'slug' => 'branch_manager',
                'is_system' => true,
                'level' => 2,
                'is_active' => true,
            ],
            [
                'name' => 'instructor',
                'display_name' => 'Instructor',
                'description' => 'Create and manage courses, teach students',
                'slug' => 'instructor',
                'is_system' => true,
                'level' => 3,
                'is_active' => true,
            ],
            [
                'name' => 'student',
                'display_name' => 'Student',
                'description' => 'Access courses and learning materials',
                'slug' => 'student',
                'is_system' => true,
                'level' => 5,
                'is_active' => true,
            ],
        ];

        // Create roles
        foreach ($roles as $roleData) {
            Role::create($roleData);
        }

        // Create basic permissions
        $permissions = [
            ['name' => 'Admin Access', 'display_name' => 'Admin Access', 'slug' => 'admin.access', 'group' => 'admin', 'type' => 'admin', 'entity' => 'admin_setting', 'action' => 'configure', 'is_system' => true],
            ['name' => 'Manage Users', 'display_name' => 'Manage Users', 'slug' => 'users.manage', 'group' => 'users', 'type' => 'manage', 'entity' => 'user', 'action' => 'manage', 'is_system' => true],
            ['name' => 'Create Courses', 'display_name' => 'Create Courses', 'slug' => 'courses.create', 'group' => 'courses', 'type' => 'create', 'entity' => 'course', 'action' => 'create', 'is_system' => true],
            ['name' => 'View Courses', 'display_name' => 'View Courses', 'slug' => 'courses.view', 'group' => 'courses', 'type' => 'read', 'entity' => 'course', 'action' => 'view', 'is_system' => true],
            ['name' => 'Edit Courses', 'display_name' => 'Edit Courses', 'slug' => 'courses.edit', 'group' => 'courses', 'type' => 'update', 'entity' => 'course', 'action' => 'edit', 'is_system' => true],
            ['name' => 'Delete Courses', 'display_name' => 'Delete Courses', 'slug' => 'courses.delete', 'group' => 'courses', 'type' => 'delete', 'entity' => 'course', 'action' => 'delete', 'is_system' => true],
        ];

        foreach ($permissions as $permissionData) {
            Permission::create($permissionData);
        }

        // Assign permissions to roles
        $adminRole = Role::where('slug', 'admin')->first();
        $instructorRole = Role::where('slug', 'instructor')->first();
        $studentRole = Role::where('slug', 'student')->first();

        // Admin gets all permissions
        $allPermissions = Permission::all();
        foreach ($allPermissions as $permission) {
            DB::table('role_permissions')->insert([
                'role_id' => $adminRole->id,
                'permission_id' => $permission->id,
                'is_active' => true,
                'granted_at' => now(),
            ]);
        }

        // Instructor gets course permissions
        $coursePermissions = Permission::whereIn('slug', ['courses.create', 'courses.view', 'courses.edit'])->get();
        foreach ($coursePermissions as $permission) {
            DB::table('role_permissions')->insert([
                'role_id' => $instructorRole->id,
                'permission_id' => $permission->id,
                'is_active' => true,
                'granted_at' => now(),
            ]);
        }

        // Student gets view permissions
        $viewPermission = Permission::where('slug', 'courses.view')->first();
        DB::table('role_permissions')->insert([
            'role_id' => $studentRole->id,
            'permission_id' => $viewPermission->id,
            'is_active' => true,
            'granted_at' => now(),
        ]);
    }
}
