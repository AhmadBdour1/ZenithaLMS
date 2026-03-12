<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Central\Tenant;
use App\Models\User;
use App\Models\Course;
use App\Models\Category;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        // Create demo tenant
        $tenant = Tenant::create([
            'id' => 'demo',
            'organization_name' => 'Demo University',
            'admin_name' => 'Demo Admin',
            'admin_email' => 'admin@demo.com',
            'logo_url' => 'https://ui-avatars.com/api/?name=Demo+University&background=3B82F6&color=fff',
            'primary_color' => '#3B82F6',
            'secondary_color' => '#10B981',
        ]);

        // Create demo domain
        $tenant->domains()->create([
            'domain' => 'demo.zenithalms.test'
        ]);

        // Initialize tenant context
        tenancy()->initialize($tenant);

        // Create categories
        $categories = [
            ['name' => 'Web Development', 'slug' => 'web-development', 'color' => '#3B82F6'],
            ['name' => 'Mobile Development', 'slug' => 'mobile-development', 'color' => '#10B981'],
            ['name' => 'Data Science', 'slug' => 'data-science', 'color' => '#F59E0B'],
            ['name' => 'Design', 'slug' => 'design', 'color' => '#EF4444'],
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }

        // Create demo users
        $users = [
            [
                'name' => 'Demo Admin',
                'email' => 'admin@demo.com',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
            ],
            [
                'name' => 'Demo Instructor',
                'email' => 'instructor@demo.com',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
            ],
            [
                'name' => 'Demo Student',
                'email' => 'student@demo.com',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
            ],
        ];

        foreach ($users as $userData) {
            User::create($userData);
        }

        // Create demo courses
        $webDevCategory = Category::where('slug', 'web-development')->first();
        $mobileDevCategory = Category::where('slug', 'mobile-development')->first();
        $dataScienceCategory = Category::where('slug', 'data-science')->first();

        $instructor = User::where('email', 'instructor@demo.com')->first();

        $courses = [
            [
                'title' => 'Introduction to Laravel Development',
                'slug' => 'intro-to-laravel',
                'description' => 'Learn the fundamentals of Laravel framework and build modern web applications.',
                'content' => 'This comprehensive course covers Laravel basics including routing, controllers, models, views, and more.',
                'price' => 0.00,
                'is_free' => true,
                'is_published' => true,
                'level' => 'beginner',
                'duration_minutes' => 480,
                'instructor_id' => $instructor->id,
                'category_id' => $webDevCategory->id,
                'requirements' => json_encode(['Basic PHP knowledge', 'Understanding of OOP']),
                'what_you_will_learn' => json_encode(['Laravel fundamentals', 'MVC architecture', 'Database relationships']),
                'target_audience' => json_encode(['Beginner developers', 'PHP developers new to Laravel']),
            ],
            [
                'title' => 'Advanced PHP Techniques',
                'slug' => 'advanced-php-techniques',
                'description' => 'Master advanced PHP concepts and best practices for enterprise applications.',
                'content' => 'Deep dive into PHP advanced features including design patterns, performance optimization, and security.',
                'price' => 49.99,
                'is_free' => false,
                'is_published' => true,
                'level' => 'advanced',
                'duration_minutes' => 720,
                'instructor_id' => $instructor->id,
                'category_id' => $webDevCategory->id,
                'requirements' => json_encode(['Intermediate PHP skills', 'OOP experience']),
                'what_you_will_learn' => json_encode(['Design patterns', 'Performance optimization', 'Security best practices']),
                'target_audience' => json_encode(['Experienced PHP developers', 'Team leads']),
            ],
            [
                'title' => 'Mobile App Development with React Native',
                'slug' => 'react-native-mobile-dev',
                'description' => 'Build cross-platform mobile applications using React Native framework.',
                'content' => 'Complete guide to React Native development from setup to deployment.',
                'price' => 79.99,
                'is_free' => false,
                'is_published' => true,
                'level' => 'intermediate',
                'duration_minutes' => 960,
                'instructor_id' => $instructor->id,
                'category_id' => $mobileDevCategory->id,
                'requirements' => json_encode(['JavaScript knowledge', 'React basics', 'Mobile development concepts']),
                'what_you_will_learn' => json_encode(['React Native fundamentals', 'Cross-platform development', 'App deployment']),
                'target_audience' => json_encode(['Web developers', 'React developers', 'Mobile developers']),
            ],
            [
                'title' => 'Data Science with Python',
                'slug' => 'data-science-python',
                'description' => 'Learn data analysis, visualization, and machine learning with Python.',
                'content' => 'Comprehensive data science course covering Python libraries and techniques.',
                'price' => 99.99,
                'is_free' => false,
                'is_published' => true,
                'level' => 'intermediate',
                'duration_minutes' => 1200,
                'instructor_id' => $instructor->id,
                'category_id' => $dataScienceCategory->id,
                'requirements' => json_encode(['Python basics', 'Statistics fundamentals', 'Linear algebra']),
                'what_you_will_learn' => json_encode(['Data analysis with Pandas', 'Machine learning basics', 'Data visualization']),
                'target_audience' => json_encode(['Data analysts', 'Python developers', 'Researchers']),
            ],
        ];

        foreach ($courses as $courseData) {
            Course::create($courseData);
        }

        // End tenant context
        tenancy()->end();
    }
}
