<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use App\Models\Course;
use App\Models\Category;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Quiz;
use App\Models\Question;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DemoDataSeeder extends Seeder
{
    /**
     * Run the demo data seeds.
     * Creates realistic demo data for showcasing the LMS
     */
    public function run(): void
    {
        $this->command->info('🌱 Seeding Demo Data...');

        // Get first organization or create one
        $organization = \App\Models\Organization::first();
        if (!$organization) {
            $organization = \App\Models\Organization::create([
                'name' => 'Demo Organization',
                'slug' => 'demo-organization',
                'is_active' => true,
            ]);
        }

        // Get or create roles
        $adminRole = Role::firstOrCreate(['name' => 'admin'], [
            'display_name' => 'Administrator',
            'description' => 'System Administrator',
            'slug' => 'admin',
            'is_system' => true,
            'level' => 0,
            'is_active' => true,
        ]);

        $instructorRole = Role::firstOrCreate(['name' => 'instructor'], [
            'display_name' => 'Instructor',
            'description' => 'Course Instructor',
            'slug' => 'instructor',
            'is_system' => true,
            'level' => 2,
            'is_active' => true,
        ]);

        $studentRole = Role::firstOrCreate(['name' => 'student'], [
            'display_name' => 'Student',
            'description' => 'Student',
            'slug' => 'student',
            'is_system' => true,
            'level' => 3,
            'is_active' => true,
        ]);

        $this->command->info('✅ Roles created');

        // Create demo instructors
        $instructors = [];
        $instructorData = [
            ['name' => 'Dr. Sarah Johnson', 'email' => 'sarah.johnson@zenithalms.test', 'specialty' => 'Web Development'],
            ['name' => 'Prof. Michael Chen', 'email' => 'michael.chen@zenithalms.test', 'specialty' => 'Data Science'],
            ['name' => 'Dr. Emily Rodriguez', 'email' => 'emily.rodriguez@zenithalms.test', 'specialty' => 'Design'],
            ['name' => 'Prof. James Anderson', 'email' => 'james.anderson@zenithalms.test', 'specialty' => 'Business'],
        ];

        foreach ($instructorData as $data) {
            $instructor = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => Hash::make('password123'),
                    'role_id' => $instructorRole->id,
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]
            );
            $instructors[] = $instructor;
        }

        $this->command->info('✅ ' . count($instructors) . ' Instructors created');

        // Create demo students
        $students = [];
        $studentNames = [
            'John Smith', 'Emma Wilson', 'Alex Turner', 'Sophie Martinez',
            'David Brown', 'Olivia Davis', 'Ryan Taylor', 'Isabella Moore',
            'Ethan Jackson', 'Mia White', 'Noah Harris', 'Ava Thompson',
        ];

        foreach ($studentNames as $index => $name) {
            $email = strtolower(str_replace(' ', '.', $name)) . '@student.test';
            $student = User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'password' => Hash::make('password123'),
                    'role_id' => $studentRole->id,
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]
            );
            $students[] = $student;
        }

        $this->command->info('✅ ' . count($students) . ' Students created');

        // Create categories
        $categories = [];
        $categoryData = [
            ['name' => 'Web Development', 'slug' => 'web-development', 'description' => 'Learn web development technologies'],
            ['name' => 'Data Science', 'slug' => 'data-science', 'description' => 'Master data analysis and machine learning'],
            ['name' => 'Design', 'slug' => 'design', 'description' => 'UI/UX and graphic design courses'],
            ['name' => 'Business', 'slug' => 'business', 'description' => 'Business and management courses'],
            ['name' => 'Programming', 'slug' => 'programming', 'description' => 'Programming fundamentals and advanced topics'],
        ];

        foreach ($categoryData as $data) {
            $category = Category::firstOrCreate(
                ['slug' => $data['slug']],
                [
                    'name' => $data['name'],
                    'description' => $data['description'],
                    'is_active' => true,
                ]
            );
            $categories[] = $category;
        }

        $this->command->info('✅ ' . count($categories) . ' Categories created');

        // Create demo courses
        $courses = [];
        $courseData = [
            [
                'title' => 'Complete Web Development Bootcamp',
                'slug' => 'complete-web-development-bootcamp',
                'description' => 'Learn HTML, CSS, JavaScript, React, Node.js and become a full-stack web developer.',
                'short_description' => 'Comprehensive web development course from beginner to advanced',
                'instructor' => 0,
                'category' => 0,
                'price' => 99.99,
                'level' => 'beginner',
                'duration' => 120,
            ],
            [
                'title' => 'Data Science with Python',
                'slug' => 'data-science-python',
                'description' => 'Master data analysis, visualization, and machine learning using Python, pandas, and scikit-learn.',
                'short_description' => 'Complete data science course with hands-on projects',
                'instructor' => 1,
                'category' => 1,
                'price' => 129.99,
                'level' => 'intermediate',
                'duration' => 90,
            ],
            [
                'title' => 'UI/UX Design Masterclass',
                'slug' => 'ui-ux-design-masterclass',
                'description' => 'Learn user interface and user experience design principles, tools, and best practices.',
                'short_description' => 'Design beautiful and functional interfaces',
                'instructor' => 2,
                'category' => 2,
                'price' => 89.99,
                'level' => 'beginner',
                'duration' => 60,
            ],
            [
                'title' => 'Business Strategy and Leadership',
                'slug' => 'business-strategy-leadership',
                'description' => 'Develop strategic thinking and leadership skills for modern business challenges.',
                'short_description' => 'Master business strategy and leadership',
                'instructor' => 3,
                'category' => 3,
                'price' => 149.99,
                'level' => 'advanced',
                'duration' => 80,
            ],
            [
                'title' => 'Advanced JavaScript Programming',
                'slug' => 'advanced-javascript-programming',
                'description' => 'Deep dive into advanced JavaScript concepts including closures, promises, and async programming.',
                'short_description' => 'Master advanced JavaScript concepts',
                'instructor' => 0,
                'category' => 4,
                'price' => 79.99,
                'level' => 'advanced',
                'duration' => 50,
            ],
        ];

        foreach ($courseData as $index => $data) {
            $course = Course::firstOrCreate(
                ['slug' => $data['slug']],
                [
                    'title' => $data['title'],
                    'description' => $data['description'],
                    'content' => $data['short_description'],
                    'instructor_id' => $instructors[$data['instructor']]->id,
                    'category_id' => $categories[$data['category']]->id,
                    'price' => $data['price'],
                    'is_free' => false,
                    'level' => $data['level'],
                    'duration_minutes' => $data['duration'] * 60,
                    'is_published' => true,
                    'is_featured' => $index < 3,
                    'language' => 'en',
                ]
            );
            $courses[] = $course;

            // Create lessons for each course
            $lessonCount = rand(5, 10);
            for ($i = 1; $i <= $lessonCount; $i++) {
                $lessonTitle = "Lesson $i: " . $this->getLessonTitle($data['title'], $i);
                $lessonSlug = $course->slug . '-lesson-' . $i;
                
                Lesson::firstOrCreate(
                    [
                        'course_id' => $course->id,
                        'slug' => $lessonSlug,
                    ],
                    [
                        'title' => $lessonTitle,
                        'description' => "Detailed content for lesson $i covering important concepts.",
                        'content' => $this->getLessonContent($data['title'], $i),
                        'sort_order' => $i,
                        'duration_minutes' => rand(15, 60),
                        'is_free' => $i <= 2,
                        'is_published' => true,
                    ]
                );
            }

            // Create quiz for course
            $quiz = Quiz::firstOrCreate(
                [
                    'course_id' => $course->id,
                    'title' => $data['title'] . ' - Final Assessment',
                ],
                [
                    'description' => 'Test your knowledge of ' . $data['title'],
                    'instructor_id' => $instructors[$data['instructor']]->id,
                    'time_limit_minutes' => 30,
                    'passing_score' => 70,
                    'max_attempts' => 3,
                    'difficulty_level' => $data['level'],
                    'is_active' => true,
                    'is_published' => true,
                ]
            );

        }

        $this->command->info('✅ ' . count($courses) . ' Courses created with lessons and quizzes');

        // Create enrollments
        $enrollmentCount = 0;
        foreach ($students as $student) {
            // Each student enrolls in 2-4 random courses
            $coursesToEnroll = rand(2, 4);
            $selectedCourses = collect($courses)->random($coursesToEnroll);

            foreach ($selectedCourses as $course) {
                $enrollment = Enrollment::firstOrCreate(
                    [
                        'user_id' => $student->id,
                        'course_id' => $course->id,
                    ],
                    [
                        'organization_id' => $organization->id,
                        'status' => collect(['active', 'completed', 'in_progress'])->random(),
                        'progress_percentage' => rand(10, 100),
                        'enrolled_at' => now()->subDays(rand(1, 60)),
                        'completed_at' => rand(0, 1) ? now()->subDays(rand(1, 10)) : null,
                    ]
                );
                $enrollmentCount++;
            }
        }

        $this->command->info('✅ ' . $enrollmentCount . ' Enrollments created');

        $this->command->info('🎉 Demo Data Seeding Complete!');
        $this->command->newLine();
        $this->command->info('📊 Summary:');
        $this->command->info('   - Instructors: ' . count($instructors));
        $this->command->info('   - Students: ' . count($students));
        $this->command->info('   - Categories: ' . count($categories));
        $this->command->info('   - Courses: ' . count($courses));
        $this->command->info('   - Enrollments: ' . $enrollmentCount);
    }

    private function getLessonTitle(string $courseTitle, int $lessonNumber): string
    {
        $topics = [
            'Introduction and Setup',
            'Core Concepts',
            'Practical Applications',
            'Advanced Techniques',
            'Best Practices',
            'Real-world Projects',
            'Common Pitfalls',
            'Performance Optimization',
            'Testing and Debugging',
            'Final Project',
        ];

        return $topics[$lessonNumber - 1] ?? "Topic $lessonNumber";
    }

    private function getLessonContent(string $courseTitle, int $lessonNumber): string
    {
        return "<h2>Lesson $lessonNumber</h2>
<p>Welcome to this comprehensive lesson covering important concepts in $courseTitle.</p>
<h3>Learning Objectives</h3>
<ul>
<li>Understand key principles</li>
<li>Apply concepts in practical scenarios</li>
<li>Master essential techniques</li>
</ul>
<h3>Content Overview</h3>
<p>In this lesson, you'll learn fundamental concepts and practical applications that will help you master the subject matter.</p>
<p>We'll cover theory, examples, and hands-on exercises to ensure complete understanding.</p>";
    }
}
