<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;

class TestUsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create roles if they don't exist
        $roles = [
            ['name' => 'admin', 'display_name' => 'Administrator'],
            ['name' => 'instructor', 'display_name' => 'Instructor'],
            ['name' => 'student', 'display_name' => 'Student'],
            ['name' => 'organization', 'display_name' => 'Organization'],
        ];
        foreach ($roles as $roleData) {
            Role::firstOrCreate(
                ['name' => $roleData['name']],
                ['display_name' => $roleData['display_name']]
            );
        }

        // Create test users
        $testUsers = [
            ['email' => 'admin@zenithalms.com', 'name' => 'Admin User', 'role' => 'admin'],
            ['email' => 'instructor@zenithalms.com', 'name' => 'Instructor User', 'role' => 'instructor'],
            ['email' => 'student@zenithalms.com', 'name' => 'Student User', 'role' => 'student'],
            ['email' => 'org@zenithalms.com', 'name' => 'Organization User', 'role' => 'organization'],
        ];

        foreach ($testUsers as $userData) {
            $user = User::firstOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'password' => bcrypt('password'),
                    'email_verified_at' => now(),
                    'is_active' => true,
                ]
            );
            
            // Assign role
            $role = Role::where('name', $userData['role'])->first();
            if ($role && !$user->role_id) {
                $user->role_id = $role->id;
                $user->save();
            }
            
            $this->command->info("Created/Updated user: {$userData['email']} with role: {$userData['role']}");
        }

        $this->command->info('Test users created successfully!');
    }
}
