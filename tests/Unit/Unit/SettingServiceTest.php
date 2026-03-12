<?php

namespace Tests\Unit;

use App\Models\Setting;
use App\Services\SettingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingServiceTest extends TestCase
{
    use RefreshDatabase;

    private SettingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(SettingService::class);
    }

    public function test_persists_setting(): void
    {
        $this->service->set('test_key', 'test_value', 'string', 'test_group', true);
        
        $this->assertDatabaseHas('settings', [
            'key' => 'test_key',
            'value' => 'test_value',
            'type' => 'string',
            'group' => 'test_group',
            'is_public' => true,
        ]);
    }

    public function test_returns_default_when_missing(): void
    {
        $result = $this->service->get('non_existent_key', 'default_value');
        
        $this->assertEquals('default_value', $result);
    }

    public function test_caches_and_invalidates_on_update(): void
    {
        // First call should cache the settings
        $this->service->set('cache_test', 'initial_value');
        $result1 = $this->service->get('cache_test');
        $this->assertEquals('initial_value', $result1);
        
        // Update the setting
        $this->service->set('cache_test', 'updated_value');
        
        // Should return the updated value (cache invalidated)
        $result2 = $this->service->get('cache_test');
        $this->assertEquals('updated_value', $result2);
    }

    public function test_gets_typed_values(): void
    {
        // Test boolean
        $this->service->set('bool_setting', true, 'boolean');
        $this->assertTrue($this->service->get('bool_setting'));
        
        // Test integer
        $this->service->set('int_setting', 42, 'integer');
        $this->assertEquals(42, $this->service->get('int_setting'));
        
        // Test float
        $this->service->set('float_setting', 3.14, 'float');
        $this->assertEquals(3.14, $this->service->get('float_setting'));
        
        // Test JSON
        $this->service->set('json_setting', ['key' => 'value'], 'json');
        $this->assertEquals(['key' => 'value'], $this->service->get('json_setting'));
    }

    public function test_gets_all_grouped(): void
    {
        $this->service->set('setting1', 'value1', 'string', 'group1');
        $this->service->set('setting2', 'value2', 'string', 'group1');
        $this->service->set('setting3', 'value3', 'string', 'group2');
        
        $all = $this->service->allGrouped();
        
        $this->assertArrayHasKey('setting1', $all);
        $this->assertArrayHasKey('setting2', $all);
        $this->assertArrayHasKey('setting3', $all);
        $this->assertEquals('group1', $all['setting1']['group']);
        $this->assertEquals('group2', $all['setting3']['group']);
    }

    public function test_gets_public_settings_only(): void
    {
        $this->service->set('public_setting', 'public', 'string', 'general', true);
        $this->service->set('private_setting', 'private', 'string', 'general', false);
        
        $public = $this->service->getPublic();
        
        $this->assertArrayHasKey('public_setting', $public);
        $this->assertArrayNotHasKey('private_setting', $public);
    }

    public function test_deletes_setting(): void
    {
        $this->service->set('to_delete', 'value');
        
        $deleted = $this->service->delete('to_delete');
        
        $this->assertTrue($deleted);
        $this->assertDatabaseMissing('settings', ['key' => 'to_delete']);
    }

    public function test_clear_cache(): void
    {
        $this->service->set('cache_clear_test', 'value');
        
        // Ensure it's cached
        $this->service->get('cache_clear_test');
        
        // Clear cache
        $this->service->clearCache();
        
        // Verify cache is cleared by checking if we can still get the value
        // (which will re-cache it)
        $result = $this->service->get('cache_clear_test');
        $this->assertEquals('value', $result);
    }
}
