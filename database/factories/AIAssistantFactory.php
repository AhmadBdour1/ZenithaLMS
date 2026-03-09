<?php

namespace Database\Factories;

use App\Models\AIAssistant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AIAssistantFactory extends Factory
{
    protected $model = AIAssistant::class;

    public function definition()
    {
        return [
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->sentence(),
            'type' => $this->faker->randomElement(['tutor', 'assistant', 'grader', 'content_generator']),
            'model_name' => $this->faker->randomElement(['gpt-4', 'claude-3', 'gemini-pro']),
            'model_version' => '1.0',
            'configuration' => [
                'temperature' => $this->faker->randomFloat(0, 1, 2),
                'max_tokens' => $this->faker->numberBetween(1000, 4000),
                'system_prompt' => $this->faker->sentence(),
            ],
            'capabilities' => $this->faker->randomElements([
                'text_generation',
                'question_answering', 
                'content_creation',
                'grading',
                'translation',
                'summarization',
                'code_generation',
                'data_analysis'
            ], $this->faker->numberBetween(2, 4)),
            'status' => 'active',
            'user_id' => User::factory(),
            'course_id' => null,
            'api_usage_limit' => $this->faker->numberBetween(1000, 10000),
            'api_usage_current' => $this->faker->numberBetween(0, 500),
            'last_used_at' => $this->faker->optional()->dateTimeThisMonth(),
            'is_public' => $this->faker->boolean(70), // 70% chance of being public
            'metadata' => [
                'created_by' => 'system',
                'version' => '1.0',
            ],
        ];
    }

    public function tutor()
    {
        return $this->state([
            'type' => 'tutor',
            'capabilities' => ['text_generation', 'question_answering', 'content_creation'],
        ]);
    }

    public function grader()
    {
        return $this->state([
            'type' => 'grader',
            'capabilities' => ['grading', 'text_generation', 'data_analysis'],
        ]);
    }

    public function contentGenerator()
    {
        return $this->state([
            'type' => 'content_generator',
            'capabilities' => ['content_creation', 'text_generation', 'summarization'],
        ]);
    }

    public function active()
    {
        return $this->state([
            'status' => 'active',
        ]);
    }

    public function inactive()
    {
        return $this->state([
            'status' => 'inactive',
        ]);
    }

    public function public()
    {
        return $this->state([
            'is_public' => true,
        ]);
    }

    public function private()
    {
        return $this->state([
            'is_public' => false,
        ]);
    }
}
