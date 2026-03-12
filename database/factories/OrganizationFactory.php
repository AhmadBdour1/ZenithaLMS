<?php

namespace Database\Factories;

use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class OrganizationFactory extends Factory
{
    protected $model = Organization::class;

    public function definition()
    {
        return [
            'name' => $this->faker->company(),
            'slug' => $this->faker->unique()->slug(),
            'domain' => $this->faker->unique()->domainName(),
            'description' => $this->faker->paragraph(),
            'logo' => $this->faker->imageUrl(),
            'email' => $this->faker->unique()->safeEmail(),
            // 'email' => 'org_' . Str::uuid() . '@example.test',
            'phone' => $this->faker->e164PhoneNumber(),
            'address' => $this->faker->streetAddress(),
            'city' => $this->faker->city(),
            'country' => $this->faker->country(),
            'settings' => [],
            'is_active' => true,
        ];
    }
}
