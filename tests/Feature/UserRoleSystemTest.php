<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserRoleSystemTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create basic roles using factory
        Role::factory()->create(['slug' => 'admin', 'name' => 'Admin', 'level' => 90]);
        Role::factory()->create(['slug' => 'instructor', 'name' => 'Instructor', 'level' => 40]);
        Role::factory()->create(['slug' => 'student', 'name' => 'Student', 'level' => 10]);
        Role::factory()->create(['slug' => 'super_admin', 'name' => 'Super Admin', 'level' => 100]);
    }

    public function test_user_can_be_assigned_multiple_roles()
    {
        $user = User::factory()->create();
        $adminRole = Role::where('slug', 'admin')->first();
        $instructorRole = Role::where('slug', 'instructor')->first();

        $user->roles()->attach([$adminRole->id, $instructorRole->id]);

        $this->assertTrue($user->hasRole('admin'));
        $this->assertTrue($user->hasRole('instructor'));
        $this->assertTrue($user->hasAnyRole(['admin', 'student']));
        $this->assertTrue($user->hasAllRoles(['admin', 'instructor']));
    }

    public function test_user_permission_check_via_roles()
    {
        $user = User::factory()->create();
        $adminRole = Role::where('slug', 'admin')->first();
        
        // Create a unique permission
        $permission = Permission::factory()->create([
            'name' => 'Test Admin Access',
            'slug' => 'test.admin.access',
            'group' => 'test',
            'type' => 'admin',
            'entity' => 'test_setting',
            'action' => 'configure',
        ]);

        // Assign permission to role
        $adminRole->permissions()->attach($permission->id);
        
        // Assign role to user
        $user->roles()->attach($adminRole->id);

        $this->assertTrue($user->hasPermission('test.admin.access'));
    }

    public function test_user_direct_permission_assignment()
    {
        $user = User::factory()->create();
        
        $permission = Permission::factory()->create([
            'name' => 'Custom Permission',
            'slug' => 'custom.permission',
            'group' => 'users',
            'type' => 'create',
            'entity' => 'user',
            'action' => 'create',
        ]);

        $user->permissions()->attach($permission->id);

        $this->assertTrue($user->hasPermission('custom.permission'));
    }

    public function test_role_hierarchy_methods()
    {
        $superAdmin = User::factory()->create();
        $admin = User::factory()->create();
        $instructor = User::factory()->create();
        $student = User::factory()->create();

        $superAdmin->roles()->attach(Role::where('slug', 'super_admin')->first()->id);
        $admin->roles()->attach(Role::where('slug', 'admin')->first()->id);
        $instructor->roles()->attach(Role::where('slug', 'instructor')->first()->id);
        $student->roles()->attach(Role::where('slug', 'student')->first()->id);

        $this->assertTrue($superAdmin->isAdmin());
        $this->assertTrue($admin->isAdmin());
        $this->assertFalse($instructor->isAdmin());
        $this->assertFalse($student->isAdmin());

        $this->assertFalse($superAdmin->isInstructor()); // Super admin is not instructor
        $this->assertFalse($admin->isInstructor());
        $this->assertTrue($instructor->isInstructor());
        $this->assertFalse($student->isInstructor());
    }

    public function test_user_role_name_attribute()
    {
        $user = User::factory()->create();
        $adminRole = Role::where('slug', 'admin')->first();
        $instructorRole = Role::where('slug', 'instructor')->first();

        // Assign multiple roles
        $user->roles()->attach([$adminRole->id, $instructorRole->id]);

        // Should return the highest level role
        $this->assertEquals('admin', $user->role_name);
    }

    public function test_foreign_key_constraints()
    {
        $user = User::factory()->create();
        $role = Role::where('slug', 'admin')->first();

        // This should work
        $user->roles()->attach($role->id);
        $this->assertDatabaseHas('user_roles', [
            'user_id' => $user->id,
            'role_id' => $role->id,
        ]);

        // This should fail due to foreign key constraint
        $this->expectException(\Illuminate\Database\QueryException::class);
        $user->roles()->attach(9999); // Non-existent role ID
    }
}
