<?php

namespace Database\Factories;

use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'role_id' => Role::firstOrCreate(['name' => 'student'], [
                'display_name' => 'Student',
                'description' => 'Student role',
            ])->id,
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Create a user with a specific role.
     */
    public function withRole(string $roleName): static
    {
        return $this->state(function (array $attributes) use ($roleName) {
            $role = Role::firstOrCreate(['name' => $roleName], [
                'display_name' => ucfirst($roleName),
                'description' => "{$roleName} role",
                'permissions' => [],
                'is_active' => true,
                'level' => 1,
            ]);

            return [
                'role_id' => $role->id,
                'is_active' => true,
                'email_verified_at' => now(),
            ];
        });
    }

    /**
     * Create an admin user.
     */
    public function admin(): static
    {
        return $this->withRole('admin');
    }

    /**
     * Create a super admin user.
     * Note: super_admin role doesn't exist in DB, alias to admin
     */
    public function superAdmin(): static
    {
        return $this->admin();
    }

    /**
     * Create an instructor user.
     */
    public function instructor(): static
    {
        return $this->withRole('instructor');
    }

    /**
     * Create a student user.
     */
    public function student(): static
    {
        return $this->withRole('student');
    }

    /**
     * Create an organization admin user.
     */
    public function organization(): static
    {
        return $this->withRole('organization_admin');
    }

    /**
     * Create an organization admin user.
     */
    public function organizationAdmin(): static
    {
        return $this->withRole('organization_admin');
    }

    /**
     * Create a content manager user.
     */
    public function contentManager(): static
    {
        return $this->withRole('content_manager');
    }
}
