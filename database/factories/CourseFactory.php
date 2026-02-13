<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\User;
use App\Models\Category;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

class CourseFactory extends Factory
{
    protected $model = Course::class;

    public function definition()
    {
        return [
            'organization_id' => Organization::factory(),
            'instructor_id' => User::factory(),
            'category_id' => Category::factory(),
            'title' => $this->faker->sentence(4),
            'slug' => $this->faker->unique()->slug(),
            'description' => $this->faker->paragraph(3),
            'content' => $this->faker->paragraphs(5, true),
            'thumbnail' => $this->faker->imageUrl(),
            'preview_video' => $this->faker->url(),
            'price' => $this->faker->randomFloat(2, 0, 999.99),
            'is_free' => $this->faker->boolean(20), // 20% chance of being free
            'level' => $this->faker->randomElement(['beginner', 'intermediate', 'advanced']),
            'language' => $this->faker->randomElement(['en', 'es', 'fr', 'de']),
            'duration_minutes' => $this->faker->numberBetween(30, 600),
            'requirements' => $this->faker->sentences(3),
            'what_you_will_learn' => $this->faker->sentences(5),
            'target_audience' => $this->faker->sentences(3),
            'is_published' => true,
            'is_featured' => $this->faker->boolean(10), // 10% chance of being featured
            'sort_order' => $this->faker->numberBetween(0, 100),
            'settings' => [],
        ];
    }
}
