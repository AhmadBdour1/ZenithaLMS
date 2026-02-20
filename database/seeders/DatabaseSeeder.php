<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            OrganizationSeeder::class,
            CategorySeeder::class,
            SkillSeeder::class,
            UserSeeder::class,
            CourseSeeder::class,
            FeatureFlagSeeder::class,
        ]);

        // Create some sample enrollments
        $this->createSampleEnrollments();
        
        // Create some sample student progress
        $this->createSampleProgress();
        
        // Create some AI assistant sessions
        $this->createSampleAISessions();
    }

    /**
     * Create sample enrollments for demo purposes
     */
    private function createSampleEnrollments(): void
    {
        $enrollments = [
            // David Brown enrolled in Web Development course
            [
                'user_id' => 9, // David Brown
                'course_id' => 1, // Web Development Bootcamp
                'organization_id' => 1, // Tech Academy
                'status' => 'active',
                'progress_percentage' => 65.5,
                'enrolled_at' => now()->subDays(30),
                'last_accessed_at' => now()->subHours(5),
                'final_score' => null,
            ],
            // Emma Wilson enrolled in React course
            [
                'user_id' => 11, // Emma Wilson
                'course_id' => 2, // Advanced React
                'organization_id' => 1, // Tech Academy
                'status' => 'active',
                'progress_percentage' => 42.0,
                'enrolled_at' => now()->subDays(15),
                'last_accessed_at' => now()->subHours(2),
                'final_score' => null,
            ],
            // Sophie Martin enrolled in Business Management
            [
                'user_id' => 10, // Sophie Martin
                'course_id' => 4, // Strategic Business Management
                'organization_id' => 2, // Business School
                'status' => 'active',
                'progress_percentage' => 78.3,
                'enrolled_at' => now()->subDays(45),
                'last_accessed_at' => now()->subHours(8),
                'final_score' => null,
            ],
            // Pierre Laurent enrolled in Graphic Design
            [
                'user_id' => 12, // Pierre Laurent
                'course_id' => 6, // Graphic Design
                'organization_id' => 3, // Creative Arts
                'status' => 'completed',
                'progress_percentage' => 100.0,
                'enrolled_at' => now()->subDays(60),
                'completed_at' => now()->subDays(5),
                'last_accessed_at' => now()->subDays(5),
                'final_score' => 92.5,
                'certificate_url' => 'certificates/graphic-design-pierre-laurent.pdf',
            ],
        ];

        foreach ($enrollments as $enrollment) {
            \App\Models\Enrollment::create($enrollment);
        }
    }

    /**
     * Create sample student progress records
     */
    private function createSampleProgress(): void
    {
        $progress = [
            // David Brown's progress in Web Development
            [
                'user_id' => 9,
                'course_id' => 1,
                'lesson_id' => 1, // First lesson
                'assessment_id' => null,
                'status' => 'completed',
                'completion_percentage' => 100,
                'time_spent_minutes' => 120,
                'score' => 85.0,
                'started_at' => now()->subDays(30),
                'completed_at' => now()->subDays(29),
                'last_accessed_at' => now()->subDays(29),
            ],
            [
                'user_id' => 9,
                'course_id' => 1,
                'lesson_id' => 2, // Second lesson
                'assessment_id' => null,
                'status' => 'completed',
                'completion_percentage' => 100,
                'time_spent_minutes' => 95,
                'score' => 78.5,
                'started_at' => now()->subDays(28),
                'completed_at' => now()->subDays(27),
                'last_accessed_at' => now()->subDays(27),
            ],
            [
                'user_id' => 9,
                'course_id' => 1,
                'lesson_id' => 3, // Third lesson (in progress)
                'assessment_id' => null,
                'status' => 'in_progress',
                'completion_percentage' => 45,
                'time_spent_minutes' => 60,
                'score' => null,
                'started_at' => now()->subDays(20),
                'completed_at' => null,
                'last_accessed_at' => now()->subHours(5),
            ],
        ];

        foreach ($progress as $record) {
            \App\Models\StudentProgress::create($record);
        }
    }

    /**
     * Create sample AI assistant sessions
     */
    private function createSampleAISessions(): void
    {
        $aiSessions = [
            [
                'user_id' => 9, // David Brown
                'course_id' => 1, // Web Development
                'lesson_id' => 3, // Current lesson
                'session_id' => 'session_' . uniqid(),
                'type' => 'tutor',
                'conversation_history' => json_encode([
                    [
                        'role' => 'user',
                        'message' => 'Can you explain the difference between let and const in JavaScript?',
                        'timestamp' => now()->subHours(2)->toISOString()
                    ],
                    [
                        'role' => 'assistant',
                        'message' => 'Great question! In JavaScript, both `let` and `const` are block-scoped variables, but they have different behaviors regarding reassignment...',
                        'timestamp' => now()->subHours(2)->addMinutes(1)->toISOString()
                    ]
                ]),
                'context_data' => json_encode([
                    'current_lesson' => 'JavaScript Fundamentals',
                    'learning_objectives' => ['Understanding variable declarations', 'Block scope concepts'],
                    'difficulty_level' => 'beginner'
                ]),
                'message_count' => 2,
                'satisfaction_score' => 4.5,
                'last_activity_at' => now()->subHours(2),
                'is_active' => true,
                'ai_settings' => json_encode([
                    'personality' => 'friendly_professional',
                    'response_style' => 'detailed_with_examples',
                    'language_level' => 'intermediate'
                ]),
            ],
            [
                'user_id' => 11, // Emma Wilson
                'course_id' => 2, // React course
                'lesson_id' => 2, // React Hooks lesson
                'session_id' => 'session_' . uniqid(),
                'type' => 'tutor',
                'conversation_history' => json_encode([
                    [
                        'role' => 'user',
                        'message' => 'I\'m confused about useEffect dependency array. Can you help?',
                        'timestamp' => now()->subHours(1)->toISOString()
                    ],
                    [
                        'role' => 'assistant',
                        'message' => 'Absolutely! The useEffect dependency array is crucial for controlling when your effect runs. Let me break it down...',
                        'timestamp' => now()->subHours(1)->addMinutes(2)->toISOString()
                    ],
                    [
                        'role' => 'user',
                        'message' => 'That makes sense! What about infinite loops?',
                        'timestamp' => now()->subHours(1)->addMinutes(3)->toISOString()
                    ],
                    [
                        'role' => 'assistant',
                        'message' => 'Excellent follow-up! Infinite loops in useEffect happen when your effect updates a value that\'s in the dependency array...',
                        'timestamp' => now()->subHours(1)->addMinutes(4)->toISOString()
                    ]
                ]),
                'context_data' => json_encode([
                    'current_lesson' => 'React Hooks Deep Dive',
                    'learning_objectives' => ['Master useEffect', 'Understand dependency arrays', 'Avoid common pitfalls'],
                    'difficulty_level' => 'intermediate'
                ]),
                'message_count' => 4,
                'satisfaction_score' => 4.8,
                'last_activity_at' => now()->subHours(1),
                'is_active' => true,
                'ai_settings' => json_encode([
                    'personality' => 'enthusiastic_teacher',
                    'response_style' => 'interactive_with_questions',
                    'language_level' => 'advanced'
                ]),
            ],
        ];

        foreach ($aiSessions as $session) {
            \App\Models\AIAssistant::create($session);
        }
    }
}
