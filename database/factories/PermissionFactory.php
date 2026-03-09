<?php

namespace Database\Factories;

use App\Models\Permission;
use Illuminate\Database\Eloquent\Factories\Factory;

class PermissionFactory extends Factory
{
    protected $model = Permission::class;

    public function definition()
    {
        $name = $this->faker->words(2, true);
        $slug = strtolower(str_replace(' ', '.', $name));
        
        return [
            'name' => $name,
            'display_name' => $name,
            'slug' => $slug,
            'description' => $this->faker->sentence(),
            'group' => $this->faker->randomElement(['users', 'courses', 'subscriptions', 'admin', 'system']),
            'type' => $this->faker->randomElement(['create', 'read', 'update', 'delete', 'manage', 'admin']),
            'entity' => $this->faker->randomElement(['user', 'course', 'subscription', 'admin_setting']),
            'action' => $this->faker->randomElement(['create', 'view', 'edit', 'delete', 'manage', 'configure']),
            'is_system' => false,
            'is_active' => true,
        ];
    }
}
