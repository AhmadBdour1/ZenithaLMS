<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Course;
use App\Models\Quiz;
use App\Models\Forum;
use App\Models\Blog;
use App\Models\Ebook;
use App\Models\Enrollment;
use App\Models\Payment;
use App\Models\Certificate;
use App\Support\Install\InstallState;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ZenithaLmsApiTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Mark as installed for tests
        InstallState::markInstalled(['test' => 'zenithalms_api_test']);
        
        // Create test data
        $this->createTestData();
    }
    
    /**
     * Create test data
     */
    private function createTestData(): void
    {
        // Create roles
        $adminRole = \App\Models\Role::factory()->create(['name' => 'admin']);
        $instructorRole = \App\Models\Role::factory()->create(['name' => 'instructor']);
        $studentRole = \App\Models\Role::factory()->create(['name' => 'student']);
        
        // Create categories
        \App\Models\Category::factory()->count(5)->create();
        
        // Create users
        User::factory()->count(10)->create();
        
        // Create courses
        Course::factory()->count(15)->create();
        
        // Create quizzes
        Quiz::factory()->count(10)->create();
        
        // Create forums
        Forum::factory()->count(20)->create();
        
        // Create blogs
        Blog::factory()->count(25)->create();
        
        // Create ebooks
        Ebook::factory()->count(12)->create();
        
        // Create virtual classes
        \App\Models\VirtualClass::factory()->count(8)->create();
        
        // Create enrollments
        Enrollment::factory()->count(30)->create();
        
        // Create payments
        Payment::factory()->count(25)->create();
        
        // Create certificates
        Certificate::factory()->count(15)->create();
    }
    
    /**
     * Test API health check
     */
    public function test_api_health_check()
    {
        $response = $this->get('/api/health');
        
        $response->assertStatus(200)
                 ->assertJson([
                     'status' => 'healthy',
                     'service' => 'ZenithaLMS API'
                 ]);
    }
    
    /**
     * Test user registration
     */
    public function test_user_registration()
    {
        $userData = [
            'name' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];
        
        $response = $this->post('/api/v1/register', $userData);
        
        $response->assertStatus(201)
                 ->assertJsonStructure([
                     'user' => [
                         'id',
                         'name',
                         'email',
                         'role',
                     ],
                     'token'
                 ]);
        
        $this->assertDatabaseHas('users', [
            'email' => $userData['email'],
        ]);
    }
    
    /**
     * Test user login
     */
    public function test_user_login()
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
        ]);
        
        $loginData = [
            'email' => $user->email,
            'password' => 'password123',
        ];
        
        $response = $this->post('/api/v1/login', $loginData);
        
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'user' => [
                         'id',
                         'name',
                         'email',
                         'role',
                     ],
                     'token'
                 ]);
    }
    
    /**
     * Test getting courses list
     */
    public function test_get_courses_list()
    {
        $response = $this->get('/api/v1/courses');
        
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'data' => [
                         '*' => [
                             'id',
                             'title',
                             'description',
                             'price',
                             'is_free',
                             'is_published',
                             'category',
                             'instructor',
                         ]
                     ],
                     'pagination'
                 ]);
    }
    
    /**
     * Test getting course details
     */
    public function test_get_course_details()
    {
        $course = Course::first();
        
        $response = $this->get("/api/v1/courses/{$course->slug}");
        
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'id',
                     'title',
                     'description',
                     'price',
                     'is_free',
                     'is_published',
                     'category',
                     'instructor',
                     'lessons',
                     'enrollments_count',
                 ]);
    }
    
    /**
     * Test course enrollment (authenticated)
     */
    public function test_course_enrollment()
    {
        $user = User::factory()->create(['organization_id' => 1]);
        $course = Course::first();
        
        // Create a wallet for the user with sufficient balance
        $wallet = \App\Models\Wallet::create([
            'user_id' => $user->id,
            'balance' => 1000.00,
            'currency' => 'USD',
            'is_active' => true,
        ]);
        
        $response = $this->actingAs($user, 'sanctum')
                        ->post("/api/v1/user/courses/{$course->id}/enroll");
        
        $response->assertStatus(200);
        
        $this->assertDatabaseHas('enrollments', [
            'user_id' => $user->id,
            'course_id' => $course->id,
            'status' => 'active',
        ]);
    }
    
    /**
     * Test getting user courses (authenticated)
     */
    public function test_get_user_courses()
    {
        $user = User::factory()->create();
        $course = Course::first();
        
        // Create enrollment
        Enrollment::create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'organization_id' => $course->organization_id ?? 1,
            'status' => 'active',
            'progress_percentage' => 0.0,
            'enrolled_at' => now(),
        ]);
        
        $response = $this->actingAs($user, 'sanctum')
                        ->get('/api/v1/user/courses');
        
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'data' => [
                         '*' => [
                             'id',
                             'title',
                             'slug',
                             'description',
                             'thumbnail',
                             'progress_percentage',
                             'status',
                             'enrolled_at',
                             'instructor',
                             'category',
                             'lessons_count',
                             'completed_lessons',
                         ]
                     ],
                     'pagination' => [
                         'current_page',
                         'last_page',
                         'per_page',
                         'total',
                     ]
                 ]);
    }
    
    /**
     * Test getting ebooks list
     */
    public function test_get_ebooks_list()
    {
        $response = $this->get('/api/v1/ebooks');
        
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'data' => [
                         '*' => [
                             'id',
                             'title',
                             'slug',
                             'description',
                             'excerpt',
                             'price',
                             'is_free',
                             'is_published',
                             'is_featured',
                             'file_path',
                             'file_size',
                             'file_type',
                             'cover_image',
                             'download_count',
                             'view_count',
                             'user',
                             'category',
                             'created_at',
                             'updated_at',
                         ]
                     ],
                     'pagination'
                 ]);
    }
    
    /**
     * Test getting quizzes list
     */
    public function test_get_quizzes_list()
    {
        $response = $this->get('/api/v1/quizzes');
        
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'data' => [
                         '*' => [
                             'id',
                             'title',
                             'description',
                             'difficulty_level',
                             'time_limit_minutes',
                             'passing_score',
                             'is_published',
                         ]
                     ],
                     'pagination'
                 ]);
    }
    
    /**
     * Test starting quiz (authenticated)
     */
    public function test_start_quiz()
    {
        $user = User::factory()->create();
        $quiz = Quiz::first();
        
        $response = $this->actingAs($user, 'sanctum')
                        ->post("/api/v1/quizzes/{$quiz->id}/start");

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'attempt_id',
                     'quiz',
                     'time_limit',
                     'started_at',
                 ]);
        
        $this->assertDatabaseHas('quiz_attempts', [
            'user_id' => $user->id,
            'quiz_id' => $quiz->id,
            'status' => 'in_progress',
        ]);
    }
    
    /**
     * Test getting forums list
     */
    public function test_get_forums_list()
    {
        $response = $this->get('/api/v1/forums');
        
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'data' => [
                         '*' => [
                             'id',
                             'title',
                             'content',
                             'user',
                             'category',
                             'view_count',
                             'reply_count',
                             'like_count',
                             'created_at',
                         ]
                     ],
                     'pagination'
                 ]);
    }
    
    /**
     * Test replying to forum (authenticated)
     */
    public function test_reply_to_forum()
    {
        $user = User::factory()->create();
        $forum = Forum::first();
        
        $replyData = [
            'content' => $this->faker->paragraph,
        ];
        
        $response = $this->actingAs($user, 'sanctum')
                        ->post("/api/v1/forums/{$forum->id}/reply", $replyData);
        
        $response->assertStatus(201);
        
        // Debug: Let's see what we actually get
        $content = $response->getContent();
        echo "Response content: " . $content;
        
        $response->assertJsonStructure([
            'message',
            'reply' => [
                'id',
                'content',
                'user_id',
                'created_at',
            ]
        ]);
    }
    
    /**
     * Test getting virtual classes list
     */
    public function test_get_virtual_classes_list()
    {
        $response = $this->get('/api/v1/virtual-classes');
        
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'data' => [
                         '*' => [
                             'id',
                             'title',
                             'description',
                             'scheduled_at',
                             'duration_minutes',
                             'meeting_link',
                             'instructor',
                             'participants_count',
                         ]
                     ],
                     'pagination'
                 ]);
    }
    
    /**
     * Test joining virtual class (authenticated)
     */
    public function test_join_virtual_class()
    {
        $user = User::factory()->create();
        
        // Create a virtual class for testing
        $virtualClass = \App\Models\VirtualClass::create([
            'title' => 'Test Virtual Class',
            'description' => 'Test description',
            'instructor_id' => 1,
            'start_time' => now()->addHour(),
            'end_time' => now()->addHours(2),
            'scheduled_at' => now()->addHour(),
            'duration_minutes' => 120,
            'meeting_link' => 'https://zoom.us/test',
            'meeting_id' => 'test123',
            'max_participants' => 50,
            'current_participants' => 0,
            'status' => 'ongoing', // Changed to ongoing
        ]);
        
        $response = $this->actingAs($user, 'sanctum')
                        ->post("/api/v1/virtual-classes/{$virtualClass->id}/join");
        
        // Debug: Let's see what we get
        $content = $response->getContent();
        echo "Response content: " . $content;
        
        $response->assertStatus(200);
        
        $this->assertDatabaseHas('virtual_class_participants', [
            'user_id' => $user->id,
            'virtual_class_id' => $virtualClass->id,
        ]);
    }
    
    /**
     * Test getting user notifications (authenticated)
     */
    public function test_get_user_notifications()
    {
        $user = User::factory()->create();
        
        $response = $this->actingAs($user, 'sanctum')
                        ->get('/api/v1/user/notifications');
        
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'data' => [
                         '*' => [
                             'id',
                             'title',
                             'message',
                             'type',
                             'read_at',
                             'created_at',
                         ]
                     ],
                     'pagination',
                     'unread_count'
                 ]);
    }
    
    /**
     * Test marking notification as read (authenticated)
     */
    public function test_mark_notification_as_read()
    {
        $user = User::factory()->create();
        
        // Create notification manually
        $notificationId = \Illuminate\Support\Str::uuid();
        $notification = \DB::table('notifications')->insertGetId([
            'id' => $notificationId,
            'type' => 'App\\Notifications\\CustomNotification',
            'notifiable_type' => 'App\\Models\\User',
            'notifiable_id' => $user->id,
            'data' => json_encode([
                'title' => 'Test Notification',
                'message' => 'This is a test notification',
                'type' => 'info'
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        $response = $this->actingAs($user, 'sanctum')
                        ->post("/api/v1/notifications/{$notificationId}/read");
        
        $response->assertStatus(200);
        
        // Check if notification is marked as read
        $updatedNotification = \DB::table('notifications')
            ->where('id', $notificationId)
            ->first();
            
        $this->assertNotNull($updatedNotification->read_at);
    }
    
    /**
     * Test getting user wallet (authenticated)
     */
    public function test_get_user_wallet()
    {
        $user = User::factory()->create();
        
        // Create wallet manually
        \DB::table('wallets')->insert([
            'user_id' => $user->id,
            'balance' => 100.00,
            'currency' => 'USD',
            'is_active' => 1,
            'total_earned' => 100.00,
            'total_spent' => 0.00,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        $response = $this->actingAs($user, 'sanctum')
                        ->get('/api/v1/user/wallet');
        
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'wallet' => [
                         'id',
                         'balance',
                         'currency',
                         'status',
                         'created_at',
                         'updated_at',
                     ],
                     'recent_transactions' => [
                         '*' => [
                             'id',
                             'type',
                             'amount',
                             'description',
                             'status',
                             'created_at',
                         ]
                     ]
                 ]);
    }
    
    /**
     * Test adding funds to wallet (authenticated)
     */
    public function test_add_funds_to_wallet()
    {
        $user = User::factory()->create();
        
        // Create wallet manually
        \DB::table('wallets')->insert([
            'user_id' => $user->id,
            'balance' => 50.00,
            'currency' => 'USD',
            'is_active' => 1,
            'total_earned' => 50.00,
            'total_spent' => 0.00,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        $fundData = [
            'amount' => 25.00,
            'payment_method' => 'credit_card',
            'payment_details' => [
                'payment_method_id' => 'pm_test_123',
            ],
        ];
        
        $response = $this->actingAs($user, 'sanctum')
                        ->post('/api/v1/wallet/add-funds', $fundData);
        
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'new_balance',
                 ]);
    }
    
    /**
     * Test search functionality
     */
    public function test_search_functionality()
    {
        $response = $this->get('/api/v1/search?q=test');
        
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'query',
                     'total_results',
                     'results' => [
                         '*' => [
                             'type',
                             'id',
                             'title',
                             'description',
                             'url',
                         ]
                     ]
                 ]);
    }
    
    /**
     * Test getting personalized recommendations (authenticated)
     */
    public function test_get_personalized_recommendations()
    {
        $user = User::factory()->create();
        
        $response = $this->actingAs($user, 'sanctum')
                        ->get('/api/v1/user/recommendations');
        
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'recommendations' => [
                         '*' => [
                             'type',
                             'id',
                             'title',
                             'description',
                             'url',
                             'confidence_score',
                         ]
                     ]
                 ]);
    }
    
    /**
     * Test admin dashboard (authenticated admin)
     */
    public function test_admin_dashboard()
    {
        $admin = User::factory()->admin()->create();
        
        $response = $this->actingAs($admin)->get('/api/v1/admin/dashboard');
        
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'stats' => [
                         'total_users',
                         'total_courses',
                         'total_enrollments',
                         'total_revenue',
                         'active_users',
                     ],
                     'recent_activities',
                     'top_courses',
                 ]);
    }
    
    /**
     * Test instructor dashboard (authenticated instructor)
     */
    public function test_instructor_dashboard()
    {
        $instructor = User::factory()->create();
        $instructorRole = \App\Models\Role::where('name', 'instructor')->first();
        $instructor->role_id = $instructorRole->id;
        $instructor->save();
        
        $response = $this->actingAs($instructor, 'sanctum')
                        ->get('/api/v1/instructor/dashboard');
        
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'stats' => [
                         'total_courses',
                         'total_students',
                         'total_revenue',
                         'average_rating',
                     ],
                     'recent_activities',
                     'student_progress',
                 ]);
    }
    
    /**
     * Test unauthorized access
     */
    public function test_unauthorized_access()
    {
        $response = $this->get('/api/v1/user/profile');

        // Laravel's auth middleware redirects to login by default (302)
        // For API, we expect either 401 or 302 depending on configuration
        $response->assertStatus(302);
    }
    
    /**
     * Test forbidden access (wrong role)
     */
    public function test_forbidden_access()
    {
        $student = User::factory()->create();
        $studentRole = \App\Models\Role::where('name', 'student')->first();
        
        if (!$studentRole) {
            $studentRole = \App\Models\Role::create([
                'name' => 'student',
                'display_name' => 'Student',
                'description' => 'Regular student user',
                'level' => 5,
                'is_active' => true,
                'permissions' => []
            ]);
        }
        
        $student->role_id = $studentRole->id;
        $student->save();
        
        $response = $this->actingAs($student, 'sanctum')
                        ->get('/api/v1/admin/dashboard');
        
        $response->assertStatus(403);
    }
    
    }
