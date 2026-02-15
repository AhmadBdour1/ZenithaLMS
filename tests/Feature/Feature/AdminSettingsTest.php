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
    }

    public function test_admin_can_view_settings_page(): void
    {
        $adminRole = \App\Models\Role::firstOrCreate(['name' => 'admin'], [
            'display_name' => 'Administrator',
            'description' => 'Admin role',
        ]);
        
        $admin = User::factory()->create(['role_id' => $adminRole->id]);
        
        // Create some test settings
        app(SettingService::class)->set('site_name', 'Test Site', 'string', 'general', true);
        app(SettingService::class)->set('maintenance_mode', false, 'boolean', 'system', false);
        
        $response = $this->actingAs($admin)->get('/admin/settings');
        
        $response->assertStatus(200);
        $response->assertSee('System Settings');
        $response->assertSee('General Settings');
        $response->assertSee('System Settings');
        $response->assertSee('Test Site');
    }

    public function test_admin_can_update_setting(): void
    {
        $adminRole = \App\Models\Role::firstOrCreate(['name' => 'admin'], [
            'display_name' => 'Administrator',
            'description' => 'Admin role',
        ]);
        
        $admin = User::factory()->create(['role_id' => $adminRole->id]);
        
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
        $studentRole = \App\Models\Role::firstOrCreate(['name' => 'student'], [
            'display_name' => 'Student',
            'description' => 'Student role',
        ]);
        
        $student = User::factory()->create(['role_id' => $studentRole->id]);
        
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
        $adminRole = \App\Models\Role::firstOrCreate(['name' => 'admin'], [
            'display_name' => 'Administrator',
            'description' => 'Admin role',
        ]);
        
        $admin = User::factory()->create(['role_id' => $adminRole->id]);
        
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
        $adminRole = \App\Models\Role::firstOrCreate(['name' => 'admin'], [
            'display_name' => 'Administrator',
            'description' => 'Admin role',
        ]);
        
        $admin = User::factory()->create(['role_id' => $adminRole->id]);
        
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
        $adminRole = \App\Models\Role::firstOrCreate(['name' => 'admin'], [
            'display_name' => 'Administrator',
            'description' => 'Admin role',
        ]);
        
        $admin = User::factory()->create(['role_id' => $adminRole->id]);
        
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
        $adminRole = \App\Models\Role::firstOrCreate(['name' => 'admin'], [
            'display_name' => 'Administrator',
            'description' => 'Admin role',
        ]);
        
        $admin = User::factory()->create(['role_id' => $adminRole->id]);
        
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
