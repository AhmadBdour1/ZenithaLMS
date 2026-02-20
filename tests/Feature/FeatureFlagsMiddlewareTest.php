<?php

namespace Tests\Feature;

use App\Services\FeatureFlagService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeatureFlagsMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    public function test_web_request_returns_404_when_feature_disabled(): void
    {
        // Disable the ebooks feature
        app(FeatureFlagService::class)->disable('ebooks');
        
        $response = $this->get('/ebooks');
        
        $response->assertStatus(404);
    }

    public function test_web_request_proceeds_when_feature_enabled(): void
    {
        // Enable the ebooks feature
        app(FeatureFlagService::class)->enable('ebooks');
        
        $response = $this->get('/ebooks');
        
        // Should not be 404 (might be 200 or redirect depending on route)
        $this->assertNotEquals(404, $response->getStatusCode());
    }

    public function test_api_request_returns_json_404_when_feature_disabled(): void
    {
        // Disable the ebooks feature
        app(FeatureFlagService::class)->disable('ebooks');
        
        $response = $this->get('/api/v1/ebooks');
        
        $response->assertStatus(404)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Feature disabled',
                     'feature' => 'ebooks',
                 ]);
    }

    public function test_api_request_proceeds_when_feature_enabled(): void
    {
        // Enable the ebooks feature
        app(FeatureFlagService::class)->enable('ebooks');
        
        $response = $this->get('/api/v1/ebooks');
        
        // Should not be 404
        $this->assertNotEquals(404, $response->getStatusCode());
    }

    public function test_feature_middleware_with_nonexistent_feature_uses_default(): void
    {
        // Test with default true (should proceed)
        $response = $this->get('/api/v1/ebooks');
        
        $this->assertNotEquals(404, $response->getStatusCode());
    }

    public function test_feature_middleware_respects_cache_invalidation(): void
    {
        // Enable feature initially
        app(FeatureFlagService::class)->enable('ebooks');
        
        // First request should succeed
        $response1 = $this->get('/api/v1/ebooks');
        $this->assertNotEquals(404, $response1->getStatusCode());
        
        // Disable feature
        app(FeatureFlagService::class)->disable('ebooks');
        
        // Second request should fail
        $response2 = $this->get('/api/v1/ebooks');
        $response2->assertStatus(404)
                   ->assertJson([
                       'success' => false,
                       'message' => 'Feature disabled',
                       'feature' => 'ebooks',
                   ]);
    }

    public function test_feature_middleware_applies_to_download_endpoint(): void
    {
        // Disable the ebooks feature
        app(FeatureFlagService::class)->disable('ebooks');
        
        // Create a user for auth
        $user = \App\Models\User::factory()->create();
        
        $response = $this->actingAs($user)->get('/api/v1/ebooks/1/download');
        
        $response->assertStatus(404)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Feature disabled',
                     'feature' => 'ebooks',
                 ]);
    }

    public function test_feature_middleware_allows_download_when_enabled(): void
    {
        // Enable the ebooks feature
        app(FeatureFlagService::class)->enable('ebooks');
        
        // Create a user for auth
        $user = \App\Models\User::factory()->create();
        
        // Create an ebook
        $ebook = \App\Models\Ebook::factory()->create(['status' => 'active']);
        
        $response = $this->actingAs($user)->get("/api/v1/ebooks/{$ebook->id}/download");
        
        // Should not return the feature disabled error
        if ($response->getStatusCode() === 404) {
            $this->assertNotEquals('Feature disabled', $response->json('message'));
        } else {
            $this->assertNotEquals(404, $response->getStatusCode());
        }
    }
}
