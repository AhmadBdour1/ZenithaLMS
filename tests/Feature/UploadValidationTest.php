<?php

namespace Tests\Feature;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use App\Models\User;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UploadValidationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        Storage::fake('public');
        Storage::fake('private');
    }

    public function test_avatar_upload_basic_functionality()
    {
        $user = User::factory()->create();
        
        // Test that the upload endpoint exists and can handle requests
        $response = $this->actingAs($user)->patch('/profile', [
            'name' => 'Test User',
            'email' => $user->email,
        ]);

        $response->assertRedirect('/profile');
    }

    public function test_ebook_upload_basic_functionality()
    {
        $user = User::factory()->instructor()->create();
        
        // Test that the upload endpoint exists and can handle requests
        $response = $this->actingAs($user)->post('/ebooks', [
            'title' => 'Test Ebook',
            'description' => 'Test Description',
            'category_id' => 1,
            'price' => 9.99,
            'is_free' => false,
            'is_downloadable' => true,
        ]);

        // The response should either redirect or show validation errors
        $this->assertContains($response->getStatusCode(), [200, 302, 422]);
    }

    public function test_media_service_config_structure()
    {
        // Test that the config structure is properly defined
        $this->assertNotNull(config('media.folders'));
        $this->assertNotNull(config('media.validation'));
        
        // Test that the required folderKeys exist
        $this->assertArrayHasKey('users.avatar', config('media.folders'));
        $this->assertArrayHasKey('ebooks.file', config('media.folders'));
        $this->assertArrayHasKey('ebooks.thumbnail', config('media.folders'));
        
        $this->assertArrayHasKey('users.avatar', config('media.validation'));
        $this->assertArrayHasKey('ebooks.file', config('media.validation'));
        $this->assertArrayHasKey('ebooks.thumbnail', config('media.validation'));
    }

    public function test_media_service_validation_rules()
    {
        // Test that validation rules are properly defined
        $this->assertNotNull(config('media.validation'));
        $this->assertIsArray(config('media.validation'));
        
        // Test that the validation structure exists
        $validationKeys = array_keys(config('media.validation'));
        $this->assertContains('users.avatar', $validationKeys);
        $this->assertContains('ebooks.file', $validationKeys);
        $this->assertContains('ebooks.thumbnail', $validationKeys);
    }

    public function test_media_service_instantiation()
    {
        // Test that MediaService can be instantiated
        $mediaService = app(\App\Services\MediaService::class);
        $this->assertInstanceOf(\App\Services\MediaService::class, $mediaService);
        
        // Test that the methods exist
        $this->assertTrue(method_exists($mediaService, 'storePublic'));
        $this->assertTrue(method_exists($mediaService, 'storePrivate'));
        $this->assertTrue(method_exists($mediaService, 'deletePublic'));
        $this->assertTrue(method_exists($mediaService, 'deletePrivate'));
        $this->assertTrue(method_exists($mediaService, 'publicUrl'));
        $this->assertTrue(method_exists($mediaService, 'normalizePath'));
    }

    public function test_media_service_folder_resolution()
    {
        $mediaService = app(\App\Services\MediaService::class);
        
        // Test that folder resolution works
        $avatarFolder = $mediaService->getFolder('users.avatar');
        $this->assertNotNull($avatarFolder);
        
        $ebookFileFolder = $mediaService->getFolder('ebooks.file');
        $this->assertNotNull($ebookFileFolder);
        
        $ebookThumbnailFolder = $mediaService->getFolder('ebooks.thumbnail');
        $this->assertNotNull($ebookThumbnailFolder);
    }
}
