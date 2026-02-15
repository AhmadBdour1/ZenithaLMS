<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;
use App\Models\User;
use App\Models\Course;
use App\Models\Ebook;
use App\Models\Category;

class MediaNormalizePathsCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a category for testing
        Category::factory()->create(['id' => 1]);
    }

    public function test_command_normalizes_course_thumbnail_path()
    {
        // Create a course with storage path
        $course = Course::factory()->create([
            'thumbnail' => 'storage/courses/thumbnails/test.webp',
        ]);

        // Run the command
        Artisan::call('media:normalize-paths');

        // Assert the path was normalized
        $this->assertDatabaseHas('courses', [
            'id' => $course->id,
            'thumbnail' => 'courses/thumbnails/test.webp',
        ]);
    }

    public function test_command_normalizes_course_thumbnail_full_url()
    {
        // Create a course with full URL
        $course = Course::factory()->create([
            'thumbnail' => 'http://localhost/storage/courses/thumbnails/test.webp',
        ]);

        // Run the command
        Artisan::call('media:normalize-paths');

        // Assert the path was normalized
        $this->assertDatabaseHas('courses', [
            'id' => $course->id,
            'thumbnail' => 'courses/thumbnails/test.webp',
        ]);
    }

    public function test_command_keeps_external_video_urls()
    {
        // Create a course with external video URL
        $course = Course::factory()->create([
            'preview_video' => 'https://www.youtube.com/watch?v=test123',
        ]);

        // Run the command
        Artisan::call('media:normalize-paths');

        // Assert the URL was NOT changed
        $this->assertDatabaseHas('courses', [
            'id' => $course->id,
            'preview_video' => 'https://www.youtube.com/watch?v=test123',
        ]);
    }

    public function test_command_normalizes_local_video_path()
    {
        // Create a course with local video path
        $course = Course::factory()->create([
            'preview_video' => 'storage/courses/videos/test.mp4',
        ]);

        // Run the command
        Artisan::call('media:normalize-paths');

        // Assert the path was normalized
        $this->assertDatabaseHas('courses', [
            'id' => $course->id,
            'preview_video' => 'courses/videos/test.mp4',
        ]);
    }

    public function test_command_normalizes_ebook_thumbnail_path()
    {
        // Create an ebook with storage path
        $ebook = Ebook::factory()->create([
            'thumbnail' => 'storage/books/thumbnails/test.jpg',
        ]);

        // Run the command
        Artisan::call('media:normalize-paths');

        // Assert the path was normalized
        $this->assertDatabaseHas('ebooks', [
            'id' => $ebook->id,
            'thumbnail' => 'books/thumbnails/test.jpg',
        ]);
    }

    public function test_command_normalizes_ebook_file_path()
    {
        // Create an ebook with storage path
        $ebook = Ebook::factory()->create([
            'file_path' => 'storage/books/files/test.pdf',
        ]);

        // Run the command
        Artisan::call('media:normalize-paths');

        // Assert the path was normalized
        $this->assertDatabaseHas('ebooks', [
            'id' => $ebook->id,
            'file_path' => 'books/files/test.pdf',
        ]);
    }

    public function test_command_normalizes_user_avatar_path()
    {
        // Create a user with storage path
        $user = User::factory()->create([
            'avatar' => 'storage/avatars/test.png',
        ]);

        // Run the command
        Artisan::call('media:normalize-paths');

        // Assert the path was normalized
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'avatar' => 'avatars/test.png',
        ]);
    }

    public function test_command_normalizes_user_avatar_full_url()
    {
        // Create a user with full URL
        $user = User::factory()->create([
            'avatar' => 'http://localhost/storage/avatars/test.png',
        ]);

        // Run the command
        Artisan::call('media:normalize-paths');

        // Assert the path was normalized
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'avatar' => 'avatars/test.png',
        ]);
    }

    public function test_command_provides_output_report()
    {
        // Create test data
        Course::factory()->create(['thumbnail' => 'storage/courses/thumbnails/test1.webp']);
        Course::factory()->create(['thumbnail' => 'storage/courses/thumbnails/test2.webp']);
        Ebook::factory()->create(['file_path' => 'storage/ebooks/files/test.pdf']);
        User::factory()->create(['avatar' => 'storage/avatars/test.png']);

        // Run the command and capture output
        $exitCode = Artisan::call('media:normalize-paths');
        
        // Assert command completed successfully
        $this->assertEquals(0, $exitCode);
        
        // The command should output the report
        // We can't easily capture console output in tests, but we can verify the data was updated
        $this->assertDatabaseHas('courses', ['thumbnail' => 'courses/thumbnails/test1.webp']);
        $this->assertDatabaseHas('courses', ['thumbnail' => 'courses/thumbnails/test2.webp']);
        $this->assertDatabaseHas('ebooks', ['file_path' => 'ebooks/files/test.pdf']);
        $this->assertDatabaseHas('users', ['avatar' => 'avatars/test.png']);
    }

    public function test_command_handles_empty_paths()
    {
        // Create records with empty/null paths
        Course::factory()->create(['thumbnail' => null]);
        Course::factory()->create(['thumbnail' => '']);
        Ebook::factory()->create(['thumbnail' => null]);
        User::factory()->create(['avatar' => null]);

        // Run the command - should not fail
        $exitCode = Artisan::call('media:normalize-paths');
        
        // Assert command completed successfully
        $this->assertEquals(0, $exitCode);
    }

    public function test_command_handles_already_normalized_paths()
    {
        // Create records with already normalized paths
        Course::factory()->create(['thumbnail' => 'courses/thumbnails/already-normalized.webp']);
        Ebook::factory()->create(['file_path' => 'ebooks/files/already-normalized.pdf']);
        User::factory()->create(['avatar' => 'avatars/already-normalized.png']);

        // Run the command
        $exitCode = Artisan::call('media:normalize-paths');
        
        // Assert command completed successfully
        $this->assertEquals(0, $exitCode);
        
        // Assert paths remain unchanged
        $this->assertDatabaseHas('courses', ['thumbnail' => 'courses/thumbnails/already-normalized.webp']);
        $this->assertDatabaseHas('ebooks', ['file_path' => 'ebooks/files/already-normalized.pdf']);
        $this->assertDatabaseHas('users', ['avatar' => 'avatars/already-normalized.png']);
    }
}
