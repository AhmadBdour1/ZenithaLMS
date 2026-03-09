<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\SettingService;
use App\Support\Install\InstallState;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Mark as installed for tests
        InstallState::markInstalled(['test' => 'admin_settings_test']);
        
        // Run the test roles seeder to ensure all roles and permissions exist
        $this->artisan('db:seed', ['--class' => 'Database\\Seeders\\TestRolesSeeder']);
    }

    public function test_admin_can_view_settings_page(): void
    {
        // Create admin role manually
        $adminRole = \App\Models\Role::firstOrCreate(['slug' => 'admin'], [
            'name' => 'admin',
            'display_name' => 'Administrator',
            'description' => 'System administrator',
            'is_system' => true,
            'is_active' => true,
            'level' => 100,
        ]);
        
        // Create admin permission manually
        $adminPermission = \App\Models\Permission::firstOrCreate(['slug' => 'admin.access'], [
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
        ]);
        
        // Assign permission to role
        $adminRole->permissions()->sync([$adminPermission->id]);
        
        // Create admin user with role
        $admin = User::factory()->create();
        $admin->roles()->attach($adminRole->id);
        
        // Debug: Check if admin has the permission
        $hasPermission = $admin->hasPermission('admin.access');
        $isAdmin = $admin->isAdmin();
        
        // Create some test settings
        app(SettingService::class)->set('site_name', 'Test Site', 'string', 'general', true);
        app(SettingService::class)->set('maintenance_mode', false, 'boolean', 'system', false);
        
        $response = $this->actingAs($admin)->get('/admin/settings');
        
        // For debugging, let's check what we get
        if ($response->getStatusCode() === 403) {
            $this->assertTrue($hasPermission, 'Admin should have admin.access permission');
            $this->assertTrue($isAdmin, 'Admin should be admin');
        }
        
        $response->assertStatus(200);
        $response->assertSee('System Settings');
        $response->assertSee('General Settings');
        $response->assertSee('System Settings');
        $response->assertSee('Test Site');
    }

    public function test_admin_can_update_setting(): void
    {
        // Create admin role manually
        $adminRole = \App\Models\Role::firstOrCreate(['slug' => 'admin'], [
            'name' => 'admin',
            'display_name' => 'Administrator',
            'description' => 'System administrator',
            'is_system' => true,
            'is_active' => true,
            'level' => 100,
        ]);
        
        // Create admin permission manually
        $adminPermission = \App\Models\Permission::firstOrCreate(['slug' => 'admin.access'], [
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
        ]);
        
        // Assign permission to role
        $adminRole->permissions()->sync([$adminPermission->id]);
        
        // Create admin user with role
        $admin = User::factory()->create();
        $admin->roles()->attach($adminRole->id);
        
        // Create initial setting
        app(SettingService::class)->set('site_name', 'Old Name', 'string', 'general', true);
        
        $response = $this->actingAs($admin)->post('/admin/settings', [
            'settings' => [
                'site_name' => [
                    'key' => 'site_name',
                    'value' => 'New Name',
                    'type' => 'string',
                ],
            ],
        ]);
        
        $response->assertRedirect();
        $response->assertSessionHas('success', 'Settings updated successfully!');
        
        // Verify the setting was updated
        $this->assertEquals('New Name', app(SettingService::class)->get('site_name'));
    }

    public function test_non_admin_forbidden(): void
    {
        $student = User::factory()->student()->create();
        
        $response = $this->actingAs($student)->get('/admin/settings');
        
        $response->assertStatus(403);
    }

    public function test_unauthenticated_user_redirected(): void
    {
        $response = $this->get('/admin/settings');
        
        $response->assertRedirect('/login');
    }

    public function test_can_update_boolean_setting(): void
    {
        $admin = User::factory()->admin()->create();
        
        // Create initial boolean setting
        app(SettingService::class)->set('maintenance_mode', false, 'boolean', 'system', false);
        
        $response = $this->actingAs($admin)->post('/admin/settings', [
            'settings' => [
                'maintenance_mode' => [
                    'key' => 'maintenance_mode',
                    'value' => '1',
                    'type' => 'boolean',
                ],
            ],
        ]);
        
        $response->assertRedirect();
        
        // Verify the setting was updated
        $this->assertTrue(app(SettingService::class)->get('maintenance_mode'));
    }

    public function test_can_update_integer_setting(): void
    {
        $admin = User::factory()->admin()->create();
        
        app(SettingService::class)->set('max_upload_size', 1024, 'integer', 'system', false);
        
        $response = $this->actingAs($admin)->post('/admin/settings', [
            'settings' => [
                'max_upload_size' => [
                    'key' => 'max_upload_size',
                    'value' => '2048',
                    'type' => 'integer',
                ],
            ],
        ]);
        
        $response->assertRedirect();
        
        $this->assertEquals(2048, app(SettingService::class)->get('max_upload_size'));
    }

    public function test_can_update_json_setting(): void
    {
        $admin = User::factory()->admin()->create();
        
        $config = ['feature1' => true, 'feature2' => false];
        app(SettingService::class)->set('feature_flags', $config, 'json', 'system', false);
        
        $newConfig = ['feature1' => false, 'feature2' => true, 'feature3' => true];
        
        $response = $this->actingAs($admin)->post('/admin/settings', [
            'settings' => [
                'feature_flags' => [
                    'key' => 'feature_flags',
                    'value' => json_encode($newConfig),
                    'type' => 'json',
                ],
            ],
        ]);
        
        $response->assertRedirect();
        
        $this->assertEquals($newConfig, app(SettingService::class)->get('feature_flags'));
    }

    public function test_validation_fails_for_invalid_type(): void
    {
        $admin = User::factory()->admin()->create();
        
        app(SettingService::class)->set('integer_setting', 10, 'integer', 'test', false);
        
        $response = $this->actingAs($admin)->post('/admin/settings', [
            'settings' => [
                'integer_setting' => [
                    'key' => 'integer_setting',
                    'value' => 'not_an_integer',
                    'type' => 'integer',
                ],
            ],
        ]);
        
        $response->assertRedirect();
        $response->assertSessionHas('error');
        
        // Verify the setting was not updated
        $this->assertEquals(10, app(SettingService::class)->get('integer_setting'));
    }
}
