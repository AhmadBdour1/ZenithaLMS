<?php

namespace Database\Factories;

use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;

class RoleFactory extends Factory
{
    protected $model = Role::class;

    public function definition()
    {
        $name = $this->faker->randomElement(['admin', 'instructor', 'student', 'organization_admin', 'content_manager']);
        
        return [
            'name' => $name,
            'slug' => $name,
            'display_name' => $this->faker->words(2, true),
            'description' => $this->faker->sentence(),
            'is_system' => false,
            'is_active' => true,
            'level' => $this->faker->numberBetween(1, 5),
        ];
    }
}
