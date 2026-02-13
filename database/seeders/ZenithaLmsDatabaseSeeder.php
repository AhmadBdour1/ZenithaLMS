<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Role;
use App\Models\Organization;
use App\Models\Category;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Enrollment;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\Forum;
use App\Models\Blog;
use App\Models\Ebook;
use App\Models\Payment;
use App\Models\Notification;
use App\Models\Theme;
use App\Models\PaymentGateway;
use App\Models\Coupon;
use App\Models\Wallet;

class ZenithaLmsDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->command->info('Starting ZenithaLMS database seeding...');
        
        // Disable foreign key checks (MySQL only)
        if (config('database.default') === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        }
        
        try {
            $this->seedRoles();
            $this->seedOrganizations();
            $this->seedUsers();
            $this->seedCategories();
            $this->seedCourses();
            $this->seedLessons();
            $this->seedQuizzes();
            $this->seedQuizQuestions();
            $this->seedForums();
            $this->seedBlogs();
            $this->seedEbooks();
            // $this->seedPaymentGateways();
            // $this->seedCoupons();
            // $this->seedThemes();
            // $this->seedNotifications();
            
            $this->command->info('ZenithaLMS database seeding completed successfully!');
            
        } catch (\Exception $e) {
            $this->command->error('Error during seeding: ' . $e->getMessage());
            
            // Re-enable foreign key checks (MySQL only)
            if (config('database.default') === 'mysql') {
                DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            }
            
            throw $e;
        }
        
        // Re-enable foreign key checks (MySQL only)
        if (config('database.default') === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }
    }
    
    /**
     * Seed roles
     */
    private function seedRoles()
    {
        $roles = [
            ['name' => 'admin', 'display_name' => 'Administrator', 'description' => 'System administrator with full access'],
            ['name' => 'instructor', 'display_name' => 'Instructor', 'description' => 'Course instructor with teaching permissions'],
            ['name' => 'student', 'display_name' => 'Student', 'description' => 'Regular student with learning permissions'],
            ['name' => 'organization_admin', 'display_name' => 'Organization Admin', 'description' => 'Organization administrator with org-wide permissions'],
            ['name' => 'content_manager', 'display_name' => 'Content Manager', 'description' => 'Content manager with content permissions'],
        ];
        
        foreach ($roles as $role) {
            Role::updateOrCreate(
                ['name' => $role['name']],
                [
                    'display_name' => $role['display_name'],
                    'description' => $role['description'],
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
        
        $this->command->info('Seeded roles table');
    }
    
    /**
     * Seed organizations
     */
    private function seedOrganizations()
    {
        $organizations = [
            [
                'name' => 'ZenithaLMS',
                'slug' => 'zenithalms',
                'email' => 'admin@zenithalms.com',
                'domain' => 'zenithalms.com',
                'description' => 'Main ZenithaLMS organization',
                'is_active' => true,
                'settings' => json_encode([
                    'max_users' => 1000,
                    'max_courses' => 500,
                    'max_storage' => '100GB',
                ]),
            ],
            [
                'name' => 'Demo University',
                'slug' => 'demo-university',
                'email' => 'admin@demo.zenithalms.com',
                'domain' => 'demo.zenithalms.com',
                'description' => 'Demo university for testing purposes',
                'is_active' => true,
                'settings' => json_encode([
                    'max_users' => 100,
                    'max_courses' => 50,
                    'max_storage' => '10GB',
                ]),
            ],
        ];
        
        foreach ($organizations as $org) {
            Organization::updateOrCreate(
                ['slug' => $org['slug']],
                [
                    'name' => $org['name'],
                    'email' => $org['email'],
                    'domain' => $org['domain'],
                    'description' => $org['description'],
                    'is_active' => $org['is_active'],
                    'settings' => $org['settings'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
        
        $this->command->info('Seeded organizations table');
    }
    
    /**
     * Seed users
     */
    private function seedUsers()
    {
        $adminRole = Role::where('name', 'admin')->first();
        $instructorRole = Role::where('name', 'instructor')->first();
        $studentRole = Role::where('name', 'student')->first();
        $orgAdminRole = Role::where('name', 'organization_admin')->first();
        $zenithalmsOrg = Organization::where('name', 'ZenithaLMS')->first();
        
        $users = [
            [
                'name' => 'Admin User',
                'email' => 'admin@zenithalms.com',
                'password' => Hash::make('password'),
                'role_id' => $adminRole->id,
                'organization_id' => $zenithalmsOrg->id,
                'is_active' => true,
                'email_verified_at' => now(),
            ],
            [
                'name' => 'John Instructor',
                'email' => 'instructor@zenithalms.com',
                'password' => Hash::make('password'),
                'role_id' => $instructorRole->id,
                'organization_id' => $zenithalmsOrg->id,
                'is_active' => true,
                'email_verified_at' => now(),
                'title' => 'Senior Instructor',
                'bio' => 'Experienced instructor with 10+ years of teaching experience',
            ],
            [
                'name' => 'Jane Student',
                'email' => 'student@zenithalms.com',
                'password' => Hash::make('password'),
                'role_id' => $studentRole->id,
                'organization_id' => $zenithalmsOrg->id,
                'is_active' => true,
                'email_verified_at' => now(),
            ],
            [
                'name' => 'Demo Admin',
                'email' => 'demo@zenithalms.com',
                'password' => Hash::make('password'),
                'role_id' => $orgAdminRole->id,
                'organization_id' => Organization::where('name', 'Demo University')->first()->id,
                'is_active' => true,
                'email_verified_at' => now(),
            ],
        ];
        
        foreach ($users as $userData) {
            User::updateOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'password' => $userData['password'],
                    'role_id' => $userData['role_id'],
                    'organization_id' => $userData['organization_id'],
                    'is_active' => $userData['is_active'],
                    'email_verified_at' => $userData['email_verified_at'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
        
        $this->command->info('Seeded users table');
    }
    
    /**
     * Seed categories
     */
    private function seedCategories()
    {
        $categories = [
            ['name' => 'Programming', 'slug' => 'programming', 'description' => 'Programming and software development courses'],
            ['name' => 'Web Development', 'slug' => 'web-development', 'description' => 'Web development and design courses'],
            ['name' => 'Mobile Development', 'slug' => 'mobile-development', 'description' => 'Mobile app development courses'],
            ['name' => 'Data Science', 'slug' => 'data-science', 'description' => 'Data science and analytics courses'],
            ['name' => 'Machine Learning', 'slug' => 'machine-learning', 'description' => 'Machine learning and AI courses'],
            ['name' => 'Business', 'slug' => 'business', 'description' => 'Business and management courses'],
            ['name' => 'Design', 'slug' => 'design', 'description' => 'Design and creative courses'],
            ['name' => 'Marketing', 'slug' => 'marketing', 'description' => 'Marketing and sales courses'],
            ['name' => 'Languages', 'slug' => 'languages', 'description' => 'Language learning courses'],
            ['name' => 'Other', 'slug' => 'other', 'description' => 'Other miscellaneous courses'],
        ];
        
        foreach ($categories as $category) {
            Category::updateOrCreate(
                ['slug' => $category['slug']],
                [
                    'name' => $category['name'],
                    'description' => $category['description'],
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
        
        $this->command->info('Seeded categories table');
    }
    
    /**
     * Seed courses
     */
    private function seedCourses()
    {
        $webDevCategory = Category::where('name', 'Web Development')->first();
        $programmingCategory = Category::where('name', 'Programming')->first();
        $dataScienceCategory = Category::where('name', 'Data Science')->first();
        
        $instructor = User::where('email', 'instructor@zenithalms.com')->first();
        
        $courses = [
            [
                'title' => 'Introduction to Web Development',
                'slug' => 'introduction-to-web-development',
                'description' => 'Learn the fundamentals of web development including HTML, CSS, and JavaScript. This comprehensive course covers everything you need to know to start building websites.',
                'category_id' => $webDevCategory->id,
                'instructor_id' => $instructor->id,
                'price' => 49.99,
                'is_free' => false,
                'is_published' => true,
                'is_featured' => true,
                'duration_minutes' => 40,
                'level' => 'beginner',
                'language' => 'en',
                'thumbnail' => 'web-dev-intro.jpg',
                'preview_video' => 'web-dev-intro.mp4',
                'requirements' => json_encode(['Basic computer skills and internet access']),
                'what_you_will_learn' => json_encode(['Learn HTML, CSS, JavaScript basics', 'Build responsive websites', 'Understand web development concepts']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Advanced JavaScript Programming',
                'slug' => 'advanced-javascript-programming',
                'description' => 'Master advanced JavaScript concepts including ES6+, async programming, and modern frameworks. Build complex applications with confidence.',
                'category_id' => $programmingCategory->id,
                'instructor_id' => $instructor->id,
                'price' => 79.99,
                'is_free' => false,
                'is_published' => true,
                'is_featured' => false,
                'duration_minutes' => 60,
                'level' => 'advanced',
                'language' => 'en',
                'thumbnail' => 'advanced-js.jpg',
                'preview_video' => 'advanced-js.mp4',
                'requirements' => json_encode(['Basic JavaScript knowledge', 'Understanding of web development']),
                'what_you_will_learn' => json_encode(['Master ES6+ features', 'Learn async programming', 'Build complex applications', 'Understand modern frameworks']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Data Science Fundamentals',
                'slug' => 'data-science-fundamentals',
                'description' => 'Introduction to data science, statistics, and machine learning. Learn the essential skills needed to start your data science journey.',
                'category_id' => $dataScienceCategory->id,
                'instructor_id' => $instructor->id,
                'price' => 89.99,
                'is_free' => false,
                'is_published' => true,
                'is_featured' => true,
                'duration_minutes' => 50,
                'level' => 'beginner',
                'language' => 'en',
                'thumbnail' => 'data-science-intro.jpg',
                'preview_video' => 'data-science-intro.mp4',
                'requirements' => json_encode(['Basic math and statistics knowledge']),
                'what_you_will_learn' => json_encode(['Understand data science basics', 'Learn statistical concepts', 'Introduction to machine learning', 'Work with real datasets']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];
        
        foreach ($courses as $courseData) {
            Course::updateOrCreate(
                ['title' => $courseData['title']],
                $courseData
            );
        }
        
        $this->command->info('Seeded courses table');
    }
    
    /**
     * Seed lessons
     */
    private function seedLessons()
    {
        $courses = Course::all();
        
        foreach ($courses as $course) {
            $lessons = $this->generateLessonsForCourse($course);
            
            foreach ($lessons as $lessonData) {
                Lesson::updateOrCreate(
                    ['course_id' => $course->id, 'title' => $lessonData['title']],
                    $lessonData
                );
            }
        }
        
        $this->command->info('Seeded lessons table');
    }
    
    /**
     * Generate lessons for a course
     */
    private function generateLessonsForCourse($course)
    {
        $lessonTemplates = [
            'Introduction to Web Development' => ['Getting Started', 'Course Overview', 'Learning Objectives'],
            'HTML Fundamentals' => ['HTML Basics', 'HTML Elements', 'Semantic HTML', 'Forms and Input'],
            'CSS Fundamentals' => ['CSS Basics', 'Selectors and Properties', 'Layout and Positioning', 'Responsive Design'],
            'JavaScript Basics' => ['JavaScript Introduction', 'Variables and Data Types', 'Functions and Scope', 'DOM Manipulation'],
            'Advanced Topics' => ['ES6+ Features', 'Async Programming', 'Modern Frameworks', 'Best Practices'],
            'Project Work' => ['Building Your First Website', 'Project Planning', 'Implementation', 'Testing and Deployment'],
        ];
        
        $lessons = [];
        $order = 1;
        
        foreach ($lessonTemplates as $section => $sectionLessons) {
            foreach ($sectionLessons as $lessonTitle) {
                $lessons[] = [
                    'title' => $lessonTitle,
                    'slug' => 'course-' . $course->id . '-' . strtolower(str_replace(' ', '-', $lessonTitle)),
                    'content' => 'This lesson covers ' . strtolower($lessonTitle) . ' in detail.',
                    'duration_minutes' => 15,
                    'sort_order' => $order,
                    'is_free' => $order <= 2,
                    'is_published' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
                $order++;
            }
        }
        
        return $lessons;
    }
    
    /**
     * Seed quizzes
     */
    private function seedQuizzes()
    {
        $courses = Course::all();
        $instructor = User::where('email', 'instructor@zenithalms.com')->first();
        
        foreach ($courses as $course) {
            Quiz::updateOrCreate(
                ['course_id' => $course->id, 'title' => $course->title . ' Quiz'],
                [
                    'description' => 'Test your knowledge of ' . $course->title . ' with this comprehensive quiz.',
                    'instructor_id' => $instructor->id,
                    'time_limit_minutes' => 30,
                    'passing_score' => 70,
                    'max_attempts' => 3,
                    'is_active' => true,
                    'is_published' => true,
                    'difficulty_level' => 'medium',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
        
        $this->command->info('Seeded quizzes table');
    }
    
    /**
     * Seed quiz questions
     */
    private function seedQuizQuestions()
    {
        $quizzes = Quiz::all();
        
        foreach ($quizzes as $quiz) {
            $this->generateQuestionsForQuiz($quiz);
        }
        
        $this->command->info('Seeded quiz questions table');
    }
    
    /**
     * Generate questions for a quiz
     */
    private function generateQuestionsForQuiz($quiz)
    {
        // Multiple choice questions
        for ($i = 1; $i <= 5; $i++) {
            $questionText = 'What is the correct answer for question ' . $i . '?';
            $question = QuizQuestion::create([
                'quiz_id' => $quiz->id,
                'question' => $questionText,
                'type' => 'multiple_choice',
                'points' => 10,
                'order' => $i,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            
            // Create options for this question
            $options = ['Option A', 'Option B', 'Option C', 'Option D'];
            foreach ($options as $index => $option) {
                \DB::table('question_options')->insert([
                    'question_id' => $question->id,
                    'option' => $option,
                    'is_correct' => $index === 0, // First option is correct
                    'order' => $index + 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
        
        // True/False questions
        for ($i = 6; $i <= 8; $i++) {
            $questionText = 'Is statement ' . ($i - 5) . ' true or false?';
            $question = QuizQuestion::create([
                'quiz_id' => $quiz->id,
                'question' => $questionText,
                'type' => 'true_false',
                'points' => 5,
                'order' => $i,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            
            // Create options for true/false
            $options = ['True', 'False'];
            foreach ($options as $index => $option) {
                \DB::table('question_options')->insert([
                    'question_id' => $question->id,
                    'option' => $option,
                    'is_correct' => $index === 0, // True is correct
                    'order' => $index + 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
        
        // Short answer questions
        for ($i = 9; $i <= 10; $i++) {
            $questionText = 'Explain the concept covered in lesson ' . ($i - 8) . '.';
            QuizQuestion::create([
                'quiz_id' => $quiz->id,
                'question' => $questionText,
                'type' => 'short_answer',
                'points' => 15,
                'order' => $i,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
    
    /**
     * Seed forums
     */
    private function seedForums()
    {
        $student = User::where('email', 'student@zenithalms.com')->first();
        $categories = Category::all();
        
        $forums = [
            [
                'title' => 'Welcome to ZenithaLMS',
                'content' => 'Welcome to our community forum! Feel free to ask questions, share your experiences, and connect with other learners.',
                'user_id' => $student->id,
                'category_id' => $categories->first()->id,
                'status' => 'active',
                'view_count' => 0,
                'reply_count' => 0,
                'like_count' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Study Tips and Resources',
                'content' => 'Share your best study tips and resources with the community. What works best for you?',
                'user_id' => $student->id,
                'category_id' => $categories->first()->id,
                'status' => 'active',
                'view_count' => 0,
                'reply_count' => 0,
                'like_count' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];
        
        foreach ($forums as $forumData) {
            Forum::updateOrCreate(
                ['title' => $forumData['title']],
                $forumData
            );
        }
        
        $this->command->info('Seeded forums table');
    }
    
    /**
     * Seed blogs
     */
    private function seedBlogs()
    {
        $instructor = User::where('email', 'instructor@zenithalms.com')->first();
        $categories = Category::all();
        
        $blogs = [
            [
                'title' => 'Getting Started with Web Development',
                'slug' => 'getting-started-with-web-development',
                'content' => 'Web development is an exciting field with endless possibilities. In this article, we\'ll cover the essential steps to get started on your web development journey.',
                'excerpt' => 'Learn the essential steps to start your web development journey.',
                'user_id' => $instructor->id,
                'category_id' => $categories->first()->id,
                'featured_image' => 'blog-web-dev.jpg',
                'status' => 'published',
                'view_count' => 0,
                'comment_count' => 0,
                'like_count' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => '10 JavaScript Tips for Beginners',
                'slug' => '10-javascript-tips-for-beginners',
                'content' => 'JavaScript can be challenging for beginners. Here are 10 tips to help you master JavaScript more effectively.',
                'excerpt' => 'Essential JavaScript tips for beginners to accelerate your learning.',
                'user_id' => $instructor->id,
                'category_id' => $categories->first()->id,
                'featured_image' => 'blog-js-tips.jpg',
                'status' => 'published',
                'view_count' => 0,
                'comment_count' => 0,
                'like_count' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];
        
        foreach ($blogs as $blogData) {
            Blog::updateOrCreate(
                ['title' => $blogData['title']],
                $blogData
            );
        }
        
        $this->command->info('Seeded blogs table');
    }
    
    /**
     * Seed ebooks
     */
    private function seedEbooks()
    {
        $instructor = User::where('email', 'instructor@zenithalms.com')->first();
        $categories = Category::all();
        
        $ebooks = [
            [
                'title' => 'JavaScript: The Complete Guide',
                'slug' => 'javascript-the-complete-guide',
                'description' => 'Comprehensive guide to JavaScript programming from basics to advanced concepts.',
                'user_id' => $instructor->id,
                'category_id' => $categories->first()->id,
                'price' => 29.99,
                'is_free' => false,
                'status' => 'published',
                'file_path' => 'storage/ebooks/js-complete-guide.pdf',
                'file_size' => 5242880,
                'thumbnail' => 'js-guide-cover.jpg',
                'download_count' => 0,
                'rating' => 0,
                'reviews_count' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'CSS Mastery: From Basics to Advanced',
                'slug' => 'css-mastery-from-basics-to-advanced',
                'description' => 'Master CSS from basic styling to advanced layouts and animations.',
                'user_id' => $instructor->id,
                'category_id' => $categories->first()->id,
                'price' => 24.99,
                'is_free' => false,
                'status' => 'published',
                'file_path' => 'storage/ebooks/css-mastery.pdf',
                'file_size' => 4194304,
                'thumbnail' => 'css-mastery-cover.jpg',
                'download_count' => 0,
                'rating' => 0,
                'reviews_count' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];
        
        foreach ($ebooks as $ebookData) {
            Ebook::updateOrCreate(
                ['title' => $ebookData['title']],
                $ebookData
            );
        }
        
        $this->command->info('Seeded ebooks table');
    }
    
    /**
     * Seed payment gateways
     */
    private function seedPaymentGateways()
    {
        $gateways = [
            [
                'name' => 'stripe',
                'display_name' => 'Stripe',
                'type' => 'credit_card',
                'is_active' => true,
                'config' => json_encode([
                    'secret_key' => config('services.stripe.secret'),
                    'publishable_key' => config('services.stripe.key'),
                    'webhook_secret' => config('services.stripe.webhook_secret'),
                ]),
                'supported_currencies' => json_encode(['USD', 'EUR', 'GBP']),
                'fees' => json_encode([
                    'percentage' => 2.9,
                    'fixed' => 0.30,
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'paypal',
                'display_name' => 'PayPal',
                'type' => 'paypal',
                'is_active' => true,
                'config' => json_encode([
                    'client_id' => config('services.paypal.client_id'),
                    'client_secret' => config('services.paypal.secret'),
                    'sandbox' => config('services.paypal.sandbox', true),
                    'webhook_id' => config('services.paypal.webhook_id'),
                ]),
                'supported_currencies' => json_encode(['USD', 'EUR', 'GBP']),
                'fees' => json_encode([
                    'percentage' => 3.4,
                    'fixed' => 0.30,
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'wallet',
                'display_name' => 'ZenithaLMS Wallet',
                'type' => 'wallet',
                'is_active' => true,
                'config' => json_encode([]),
                'supported_currencies' => json_encode(['USD']),
                'fees' => json_encode([
                    'percentage' => 0,
                    'fixed' => 0,
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];
        
        foreach ($gateways as $gatewayData) {
            PaymentGateway::updateOrCreate(
                ['name' => $gatewayData['name']],
                $gatewayData
            );
        }
        
        $this->command->info('Seeded payment gateways table');
    }
    
    /**
     * Seed coupons
     */
    private function seedCoupons()
    {
        $coupons = [
            [
                'code' => 'SAVE10',
                'type' => 'percentage',
                'value' => 10,
                'description' => 'Save 10% on your next purchase',
                'minimum_amount' => 50,
                'maximum_discount' => 100,
                'usage_limit' => 100,
                'is_active' => true,
                'expires_at' => now()->addMonths(3),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'SAVE20',
                'type' => 'percentage',
                'value' => 20,
                'description' => 'Save 20% on your next purchase',
                'minimum_amount' => 100,
                'maximum_discount' => 200,
                'usage_limit' => 50,
                'is_active' => true,
                'expires_at' => now()->addMonths(2),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'FLAT50',
                'type' => 'fixed',
                'value' => 50,
                'description' => 'Save $50 on your next purchase',
                'minimum_amount' => 100,
                'maximum_discount' => 50,
                'usage_limit' => 25,
                'is_active' => true,
                'expires_at' => now()->addMonth(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];
        
        foreach ($coupons as $couponData) {
            Coupon::updateOrCreate(
                ['code' => $couponData['code']],
                $couponData
            );
        }
        
        $this->command->info('Seeded coupons table');
    }
    
    /**
     * Seed themes
     */
    private function seedThemes()
    {
        $themes = [
            [
                'name' => 'ZenithaLMS Default',
                'description' => 'Default theme for ZenithaLMS platform',
                'is_active' => true,
                'is_default' => true,
                'preview_image' => 'themes/zenithalms-default.jpg',
                'config' => json_encode([
                    'primary_color' => '#3B82F6',
                    'secondary_color' => '#10B981',
                    'accent_color' => '#8B5CF6',
                    'background_color' => '#F9FAFB',
                    'text_color' => '#111827',
                    'font_family' => 'Inter',
                    'font_size' => '16',
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Dark Mode',
                'description' => 'Dark theme for ZenithaLMS platform',
                'is_active' => true,
                'is_default' => false,
                'preview_image' => 'themes/dark-mode.jpg',
                'config' => json_encode([
                    'primary_color' => '#60A5FA',
                    'secondary_color' => '#4C51BF',
                    'accent_color' => '#A78BFA',
                    'background_color' => '#1F2937',
                    'text_color' => '#E5E7EB',
                    'font_family' => 'Inter',
                    'font_size' => '16',
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];
        
        foreach ($themes as $themeData) {
            Theme::updateOrCreate(
                ['name' => $themeData['name']],
                $themeData
            );
        }
        
        $this->command->info('Seeded themes table');
    }
    
    /**
     * Seed notifications
     */
    private function seedNotifications()
    {
        $users = User::all();
        
        foreach ($users as $user) {
            Notification::create([
                'user_id' => $user->id,
                'title' => 'Welcome to ZenithaLMS!',
                'message' => 'Thank you for joining ZenithaLMS. Start exploring our courses and features.',
                'type' => 'info',
                'channel' => 'in_app',
                'notification_data' => [
                    'action_url' => route('zenithalms.dashboard'),
                    'action_button' => 'Get Started',
                ],
                'is_read' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        
        $this->command->info('Seeded notifications table');
    }
}
