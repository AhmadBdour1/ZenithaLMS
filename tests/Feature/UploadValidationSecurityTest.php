<?php

namespace Tests\Feature;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use App\Models\User;
use App\Models\Category;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UploadValidationSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        Storage::fake('public');
        Storage::fake('private');
        
        // Create a category for ebook tests
        Category::factory()->create(['id' => 1]);
    }

    public function test_avatar_oversized_rejected()
    {
        $user = User::factory()->create();
        
        // Create a file larger than allowed for users.avatar (2MB limit)
        $oversizedFile = UploadedFile::fake()->create('avatar.jpg', 3000); // 3MB
        
        $response = $this->actingAs($user)->patch('/profile', [
            'name' => 'Test User',
            'email' => $user->email,
            'avatar' => $oversizedFile,
        ]);

        // The validation error causes a redirect back with errors
        $response->assertStatus(302);
        $response->assertSessionHasErrors('avatar');
        
        // Ensure avatar was not updated
        $user->refresh();
        $this->assertNull($user->avatar);
    }

    public function test_avatar_disallowed_extension_rejected()
    {
        $user = User::factory()->create();
        
        // Create a dangerous file with PHP extension
        $dangerousFile = UploadedFile::fake()->create('avatar.php', 100, 'image/jpeg');
        
        $response = $this->actingAs($user)->patch('/profile', [
            'name' => 'Test User',
            'email' => $user->email,
            'avatar' => $dangerousFile,
        ]);

        // The validation error causes a redirect back with errors
        $response->assertStatus(302);
        $response->assertSessionHasErrors('avatar');
        
        // Ensure avatar was not updated
        $user->refresh();
        $this->assertNull($user->avatar);
    }

    public function test_avatar_mime_mismatch_rejected()
    {
        $user = User::factory()->create();
        
        // Create a file with wrong MIME type
        $mimeTypeFile = UploadedFile::fake()->create('avatar.jpg', 100, 'application/x-php');
        
        $response = $this->actingAs($user)->patch('/profile', [
            'name' => 'Test User',
            'email' => $user->email,
            'avatar' => $mimeTypeFile,
        ]);

        // The validation error causes a redirect back with errors
        $response->assertStatus(302);
        $response->assertSessionHasErrors('avatar');
        
        // Ensure avatar was not updated
        $user->refresh();
        $this->assertNull($user->avatar);
    }

    public function test_avatar_dangerous_double_extension_rejected()
    {
        $user = User::factory()->create();
        
        // Create a file with dangerous double extension
        $dangerousFile = UploadedFile::fake()->create('avatar.php.jpg', 100, 'image/jpeg');
        
        $response = $this->actingAs($user)->patch('/profile', [
            'name' => 'Test User',
            'email' => $user->email,
            'avatar' => $dangerousFile,
        ]);

        // The request should be rejected (either 302 with errors or 422)
        $this->assertContains($response->getStatusCode(), [302, 422]);
        
        // Ensure avatar was not updated
        $user->refresh();
        $this->assertNull($user->avatar);
    }
}
