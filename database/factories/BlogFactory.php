<?php

namespace Database\Factories;

use App\Models\Blog;
use App\Models\User;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

class BlogFactory extends Factory
{
    protected $model = Blog::class;

    public function definition()
    {
        return [
            'user_id' => User::factory(),
            'category_id' => Category::factory(),
            'title' => $this->faker->sentence(4),
            'slug' => $this->faker->unique()->slug(),
            'excerpt' => $this->faker->paragraph(),
            'content' => $this->faker->paragraphs(5, true),
            'featured_image' => $this->faker->imageUrl(),
            'status' => 'published',
            'view_count' => $this->faker->numberBetween(0, 1000),
            'comment_count' => 0,
            'like_count' => 0,
            'published_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
        ];
    }
}
