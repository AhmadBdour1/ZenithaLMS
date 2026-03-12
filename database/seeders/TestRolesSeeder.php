<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use Illuminate\Support\Facades\DB;

class TestRolesSeeder extends Seeder
{
    public function run()
    {
        // Create basic roles with all required fields
        $roles = [
            [
                'name' => 'admin',
                'slug' => 'admin',
                'display_name' => 'Administrator',
                'description' => 'System administrator',
                'is_system' => true,
                'is_active' => true,
                'level' => 100,
            ],
            [
                'name' => 'instructor',
                'slug' => 'instructor',
                'display_name' => 'Instructor',
                'description' => 'Course instructor',
                'is_system' => true,
                'is_active' => true,
                'level' => 50,
            ],
            [
                'name' => 'student',
                'slug' => 'student',
                'display_name' => 'Student',
                'description' => 'Regular student',
                'is_system' => true,
                'is_active' => true,
                'level' => 10,
            ],
            [
                'name' => 'organization_admin',
                'slug' => 'organization_admin',
                'display_name' => 'Organization Admin',
                'description' => 'Organization administrator',
                'is_system' => true,
                'is_active' => true,
                'level' => 75,
            ],
            [
                'name' => 'content_manager',
                'slug' => 'content_manager',
                'display_name' => 'Content Manager',
                'description' => 'Content manager',
                'is_system' => true,
                'is_active' => true,
                'level' => 60,
            ],
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(['slug' => $role['slug']], $role);
        }
        
        // Create admin permissions
        $permissions = [
            [
                'name' => 'Admin Access',
                'display_name' => 'Admin Access',
                'slug' => 'admin.access',
                'description' => 'Access to admin area',
                'group' => 'admin',
                'type' => 'admin',
                'entity' => 'admin_setting',
                'action' => 'configure',
                'is_system' => true,
                'is_active' => true,
            ],
            [
                'name' => 'View Settings',
                'display_name' => 'View Settings',
                'slug' => 'settings.view',
                'description' => 'View system settings',
                'group' => 'admin',
                'type' => 'read',
                'entity' => 'setting',
                'action' => 'view',
                'is_system' => true,
                'is_active' => true,
            ],
            [
                'name' => 'Update Settings',
                'display_name' => 'Update Settings',
                'slug' => 'settings.update',
                'description' => 'Update system settings',
                'group' => 'admin',
                'type' => 'update',
                'entity' => 'setting',
                'action' => 'update',
                'is_system' => true,
                'is_active' => true,
            ],
        ];

        foreach ($permissions as $permission) {
            \App\Models\Permission::firstOrCreate(['slug' => $permission['slug']], $permission);
        }

        // Assign permissions to admin role
        $adminRole = Role::where('slug', 'admin')->first();
        if ($adminRole) {
            $adminPermissions = \App\Models\Permission::where('group', 'admin')->pluck('id');
            $adminRole->permissions()->sync($adminPermissions);
        }
    }
}
