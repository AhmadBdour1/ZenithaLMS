<?php

namespace Database\Factories;

use App\Models\Forum;
use App\Models\User;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

class ForumFactory extends Factory
{
    protected $model = Forum::class;

    public function definition()
    {
        return [
            'title' => $this->faker->sentence(4),
            'content' => $this->faker->paragraphs(3, true),
            'user_id' => User::factory(),
            'category_id' => Category::factory(),
            'status' => 'active',
            'view_count' => $this->faker->numberBetween(0, 1000),
            'reply_count' => 0,
            'like_count' => 0,
            'is_pinned' => false,
            'is_locked' => false,
        ];
    }
}
