<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Blog;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BlogAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test data
        Category::factory()->create(['is_active' => true]);
    }

    public function test_unauthorized_user_cannot_update_others_blog()
    {
        $userA = User::factory()->instructor()->create();
        $userB = User::factory()->instructor()->create();
        
        $blog = Blog::factory()->create([
            'user_id' => $userA->id,
            'title' => 'Test Blog by User A',
            'status' => 'published'
        ]);

        $response = $this->actingAs($userB, 'web')
            ->put(route('zenithalms.blog.update', $blog->id), [
                'title' => 'Updated Blog Title',
                'content' => 'Updated content',
                'category_id' => $blog->category_id,
                'status' => 'published'
            ]);

        // Should return 403 for unauthorized access
        $this->assertEquals(403, $response->getStatusCode());
        
        // Blog should still exist (not deleted)
        $this->assertNotSoftDeleted($blog);
    }

    public function test_unauthorized_user_cannot_delete_others_blog()
    {
        $userA = User::factory()->instructor()->create();
        $userB = User::factory()->instructor()->create();
        
        $blog = Blog::factory()->create([
            'user_id' => $userA->id,
            'title' => 'Test Blog by User A',
            'status' => 'published'
        ]);

        $response = $this->actingAs($userB, 'web')
            ->delete(route('zenithalms.blog.destroy', $blog->id));

        // Should return 403 for unauthorized access
        $this->assertEquals(403, $response->getStatusCode());
        
        // Blog should still exist (not deleted)
        $this->assertNotSoftDeleted($blog);
    }

    public function test_unauthorized_user_cannot_edit_others_blog_page()
    {
        $userA = User::factory()->instructor()->create();
        $userB = User::factory()->instructor()->create();
        
        $blog = Blog::factory()->create([
            'user_id' => $userA->id,
            'title' => 'Test Blog by User A',
            'status' => 'published'
        ]);

        $response = $this->actingAs($userB, 'web')
            ->get(route('zenithalms.blog.edit', $blog->id));

        // Should return 403 for unauthorized access
        $this->assertEquals(403, $response->getStatusCode());
    }

    public function test_authorized_user_can_update_own_blog()
    {
        $user = User::factory()->instructor()->create();
        
        $blog = Blog::factory()->create([
            'user_id' => $user->id,
            'title' => 'Test Blog',
            'status' => 'published'
        ]);

        // Test without is_featured since column doesn't exist
        $response = $this->actingAs($user, 'web')
            ->put(route('zenithalms.blog.update', $blog->id), [
                'title' => 'Updated Blog Title',
                'content' => 'Updated content',
                'category_id' => $blog->category_id,
                'status' => 'published',
                'excerpt' => 'Updated excerpt'
            ]);
        
        // Should succeed (200 or 302 redirect)
        $this->assertContains($response->getStatusCode(), [200, 302]);
        
        // Blog should be updated
        $this->assertDatabaseHas('blogs', [
            'id' => $blog->id,
            'title' => 'Updated Blog Title'
        ]);
    }

    public function test_authorized_user_can_delete_own_blog()
    {
        $user = User::factory()->instructor()->create();
        
        $blog = Blog::factory()->create([
            'user_id' => $user->id,
            'title' => 'Test Blog',
            'status' => 'published'
        ]);

        $response = $this->actingAs($user, 'web')
            ->delete(route('zenithalms.blog.destroy', $blog->id));

        // Should succeed (200 or 302 redirect)
        $this->assertContains($response->getStatusCode(), [200, 302]);
        
        // Blog should be deleted (soft deleted)
        $this->assertSoftDeleted($blog);
    }

    public function test_admin_can_update_any_blog()
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->instructor()->create();
        
        $blog = Blog::factory()->create([
            'user_id' => $user->id,
            'title' => 'Test Blog',
            'status' => 'published'
        ]);

        $response = $this->actingAs($admin, 'web')
            ->put(route('zenithalms.blog.update', $blog->id), [
                'title' => 'Admin Updated Blog',
                'content' => 'Updated by admin',
                'category_id' => $blog->category_id,
                'status' => 'published'
            ]);

        // Should succeed (200 or 302 redirect)
        $this->assertContains($response->getStatusCode(), [200, 302]);
        
        // Blog should be updated
        $this->assertDatabaseHas('blogs', [
            'id' => $blog->id,
            'title' => 'Admin Updated Blog'
        ]);
    }

    public function test_admin_can_delete_any_blog()
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->instructor()->create();
        
        $blog = Blog::factory()->create([
            'user_id' => $user->id,
            'title' => 'Test Blog',
            'status' => 'published'
        ]);

        $response = $this->actingAs($admin, 'web')
            ->delete(route('zenithalms.blog.destroy', $blog->id));

        // Should succeed (200 or 302 redirect)
        $this->assertContains($response->getStatusCode(), [200, 302]);
        
        // Blog should be deleted (soft deleted)
        $this->assertSoftDeleted($blog);
    }

}
