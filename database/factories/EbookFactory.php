<?php

namespace Database\Factories;

use App\Models\Ebook;
use App\Models\User;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

class EbookFactory extends Factory
{
    protected $model = Ebook::class;

    public function definition()
    {
        return [
            'author_id' => User::factory(),
            'category_id' => Category::factory(),
            'title' => $this->faker->sentence(3),
            'slug' => $this->faker->unique()->slug(),
            'description' => $this->faker->paragraph(),
            'thumbnail' => $this->faker->imageUrl(),
            'file_path' => $this->faker->filePath(),
            'file_type' => 'pdf',
            'file_size' => $this->faker->numberBetween(1000000, 50000000),
            'price' => $this->faker->randomFloat(2, 0, 99.99),
            'is_free' => $this->faker->boolean(20),
            'status' => 'published',
            'download_count' => 0,
            'rating' => 0,
            'reviews_count' => 0,
        ];
    }
}
