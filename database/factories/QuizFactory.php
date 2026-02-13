<?php

namespace Database\Factories;

use App\Models\Quiz;
use App\Models\Course;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class QuizFactory extends Factory
{
    protected $model = Quiz::class;

    public function definition()
    {
        return [
            'title' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph(),
            'course_id' => Course::factory(),
            'instructor_id' => User::factory(),
            'time_limit_minutes' => $this->faker->numberBetween(10, 120),
            'passing_score' => $this->faker->numberBetween(60, 90),
            'max_attempts' => $this->faker->numberBetween(1, 5),
            'is_active' => true,
            'is_published' => true,
            'difficulty_level' => $this->faker->randomElement(['easy', 'medium', 'hard']),
            'instructions' => $this->faker->paragraph(),
            'settings' => [],
        ];
    }
}
