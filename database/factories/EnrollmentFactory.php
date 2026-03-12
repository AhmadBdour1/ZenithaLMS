<?php

namespace Database\Factories;

use App\Models\Enrollment;
use App\Models\User;
use App\Models\Course;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

class EnrollmentFactory extends Factory
{
    protected $model = Enrollment::class;

    public function definition()
    {
        return [
            'user_id' => User::factory(),
            'course_id' => Course::factory(),
            'organization_id' => Organization::factory(),
            'status' => $this->faker->randomElement(['active', 'completed', 'dropped', 'suspended']),
            'progress_percentage' => $this->faker->randomFloat(2, 0, 100),
            'enrolled_at' => $this->faker->dateTimeBetween('-6 months', 'now'),
            'completed_at' => $this->faker->optional(0.3)->dateTimeBetween('-3 months', 'now'),
            'last_accessed_at' => $this->faker->optional(0.7)->dateTimeBetween('-1 months', 'now'),
            'certificate_url' => $this->faker->optional(0.2)->url(),
            'final_score' => $this->faker->optional(0.3)->randomFloat(2, 0, 100),
            'enrollment_data' => $this->faker->optional(0.4)->randomElements([
                'learning_path' => $this->faker->randomElement(['standard', 'advanced', 'beginner']),
                'preferred_time' => $this->faker->randomElement(['morning', 'afternoon', 'evening']),
                'study_pace' => $this->faker->randomElement(['fast', 'normal', 'slow']),
            ]),
        ];
    }
}
