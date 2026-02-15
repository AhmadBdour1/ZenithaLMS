<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Course;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test data
        Category::factory()->create();
    }

    public function test_course_policy_allows_instructor_to_update_own_course()
    {
        $instructor = User::factory()->instructor()->create();
        
        $course = Course::factory()->create([
            'instructor_id' => $instructor->id,
            'title' => 'Test Course',
            'is_published' => true
        ]);

        $policy = new \App\Policies\CoursePolicy();
        $this->assertTrue($policy->update($instructor, $course));
    }

    public function test_course_policy_denies_instructor_from_updating_others_course()
    {
        $instructorA = User::factory()->instructor()->create();
        $instructorB = User::factory()->instructor()->create();
        
        $course = Course::factory()->create([
            'instructor_id' => $instructorA->id,
            'title' => 'Test Course',
            'is_published' => true
        ]);

        $policy = new \App\Policies\CoursePolicy();
        $this->assertFalse($policy->update($instructorB, $course));
    }

    public function test_course_policy_allows_admin_to_update_any_course()
    {
        $admin = User::factory()->admin()->create();
        $instructor = User::factory()->instructor()->create();
        
        $course = Course::factory()->create([
            'instructor_id' => $instructor->id,
            'title' => 'Test Course',
            'is_published' => true
        ]);

        $policy = new \App\Policies\CoursePolicy();
        $this->assertTrue($policy->update($admin, $course));
    }

    public function test_course_policy_allows_instructor_to_delete_own_course()
    {
        $instructor = User::factory()->instructor()->create();
        
        $course = Course::factory()->create([
            'instructor_id' => $instructor->id,
            'title' => 'Test Course',
            'is_published' => true
        ]);

        $policy = new \App\Policies\CoursePolicy();
        $this->assertTrue($policy->delete($instructor, $course));
    }

    public function test_course_policy_denies_instructor_from_deleting_others_course()
    {
        $instructorA = User::factory()->instructor()->create();
        $instructorB = User::factory()->instructor()->create();
        
        $course = Course::factory()->create([
            'instructor_id' => $instructorA->id,
            'title' => 'Test Course',
            'is_published' => true
        ]);

        $policy = new \App\Policies\CoursePolicy();
        $this->assertFalse($policy->delete($instructorB, $course));
    }

    public function test_course_policy_allows_admin_to_delete_any_course()
    {
        $admin = User::factory()->admin()->create();
        $instructor = User::factory()->instructor()->create();
        
        $course = Course::factory()->create([
            'instructor_id' => $instructor->id,
            'title' => 'Test Course',
            'is_published' => true
        ]);

        $policy = new \App\Policies\CoursePolicy();
        $this->assertTrue($policy->delete($admin, $course));
    }

    public function test_course_policy_allows_instructor_to_manage_own_media()
    {
        $instructor = User::factory()->instructor()->create();
        
        $course = Course::factory()->create([
            'instructor_id' => $instructor->id,
            'title' => 'Test Course',
            'is_published' => true
        ]);

        $policy = new \App\Policies\CoursePolicy();
        $this->assertTrue($policy->manageMedia($instructor, $course));
    }

    public function test_course_policy_denies_instructor_from_managing_others_media()
    {
        $instructorA = User::factory()->instructor()->create();
        $instructorB = User::factory()->instructor()->create();
        
        $course = Course::factory()->create([
            'instructor_id' => $instructorA->id,
            'title' => 'Test Course',
            'is_published' => true
        ]);

        $policy = new \App\Policies\CoursePolicy();
        $this->assertFalse($policy->manageMedia($instructorB, $course));
    }

    public function test_course_policy_allows_admin_to_manage_any_media()
    {
        $admin = User::factory()->admin()->create();
        $instructor = User::factory()->instructor()->create();
        
        $course = Course::factory()->create([
            'instructor_id' => $instructor->id,
            'title' => 'Test Course',
            'is_published' => true
        ]);

        $policy = new \App\Policies\CoursePolicy();
        $this->assertTrue($policy->manageMedia($admin, $course));
    }

    public function test_course_policy_view_published_course_allows_anyone()
    {
        $user = User::factory()->student()->create();
        
        $course = Course::factory()->create([
            'instructor_id' => 999, // Different instructor
            'title' => 'Published Course',
            'is_published' => true
        ]);

        $policy = new \App\Policies\CoursePolicy();
        $this->assertTrue($policy->view($user, $course));
    }

    public function test_course_policy_view_unpublished_course_denies_non_owner()
    {
        $user = User::factory()->student()->create();
        
        $course = Course::factory()->create([
            'instructor_id' => 999, // Different instructor
            'title' => 'Unpublished Course',
            'is_published' => false
        ]);

        $policy = new \App\Policies\CoursePolicy();
        $this->assertFalse($policy->view($user, $course));
    }

    public function test_course_policy_view_unpublished_course_allows_owner()
    {
        $instructor = User::factory()->instructor()->create();
        
        $course = Course::factory()->create([
            'instructor_id' => $instructor->id,
            'title' => 'Unpublished Course',
            'is_published' => false
        ]);

        $policy = new \App\Policies\CoursePolicy();
        $this->assertTrue($policy->view($instructor, $course));
    }
}
