<?php

namespace Tests\Unit;

use App\Models\Setting;
use App\Services\FeatureFlagService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeatureFlagServiceTest extends TestCase
{
    use RefreshDatabase;

    private FeatureFlagService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(FeatureFlagService::class);
    }

    public function test_get_returns_default_when_feature_not_exists(): void
    {
        $result = $this->service->get('nonexistent', false);
        
        $this->assertFalse($result);
        
        $result = $this->service->get('nonexistent', true);
        
        $this->assertTrue($result);
    }

    public function test_set_and_get_feature_flag(): void
    {
        $this->service->set('test_feature', true);
        
        $result = $this->service->get('test_feature');
        
        $this->assertTrue($result);
        
        $this->service->set('test_feature', false);
        
        $result = $this->service->get('test_feature');
        
        $this->assertFalse($result);
    }

    public function test_enable_and_disable_feature(): void
    {
        $this->service->enable('test_feature');
        
        $this->assertTrue($this->service->isEnabled('test_feature'));
        
        $this->service->disable('test_feature');
        
        $this->assertFalse($this->service->isEnabled('test_feature'));
    }

    public function test_all_returns_cached_flags(): void
    {
        // Set some flags
        $this->service->set('feature1', true);
        $this->service->set('feature2', false);
        
        $all = $this->service->all();
        
        $this->assertArrayHasKey('features.feature1', $all);
        $this->assertArrayHasKey('features.feature2', $all);
        $this->assertTrue($all['features.feature1']);
        $this->assertFalse($all['features.feature2']);
    }

    public function test_get_public_returns_only_public_flags(): void
    {
        // Set public and private flags
        $this->service->set('public_feature', true, true);
        $this->service->set('private_feature', true, false);
        
        $public = $this->service->getPublic();
        
        $this->assertArrayHasKey('features.public_feature', $public);
        $this->assertArrayNotHasKey('features.private_feature', $public);
        $this->assertTrue($public['features.public_feature']);
    }

    public function test_clear_cache_invalidates_cache(): void
    {
        // Set initial value
        $this->service->set('test_feature', true);
        
        // Get value to populate cache
        $result1 = $this->service->get('test_feature');
        $this->assertTrue($result1);
        
        // Update directly in database (bypassing cache)
        Setting::where('key', 'features.test_feature')->update(['value' => '0']);
        
        // Clear cache
        $this->service->clearCache();
        
        // Get updated value
        $result2 = $this->service->get('test_feature');
        $this->assertFalse($result2);
    }

    public function test_seed_defaults_idempotently(): void
    {
        // Seed defaults
        $this->service->seedDefaults();
        
        // Check all defaults are set
        $this->assertTrue($this->service->isEnabled('courses'));
        $this->assertTrue($this->service->isEnabled('ebooks'));
        $this->assertTrue($this->service->isEnabled('wallet'));
        $this->assertTrue($this->service->isEnabled('blog'));
        $this->assertTrue($this->service->isEnabled('forums'));
        $this->assertFalse($this->service->isEnabled('store'));
        
        // Change some values
        $this->service->set('courses', false);
        $this->service->set('store', true);
        
        // Seed again
        $this->service->seedDefaults();
        
        // Values should be reset to defaults
        $this->assertTrue($this->service->isEnabled('courses'));
        $this->assertFalse($this->service->isEnabled('store'));
    }

    public function test_is_enabled_uses_default(): void
    {
        // Test with default true
        $result = $this->service->isEnabled('nonexistent', true);
        $this->assertTrue($result);
        
        // Test with default false
        $result = $this->service->isEnabled('nonexistent', false);
        $this->assertFalse($result);
    }
}
