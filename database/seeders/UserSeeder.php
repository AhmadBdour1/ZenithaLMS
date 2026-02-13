<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Super Admin
        User::create([
            'name' => 'John Anderson',
            'email' => 'admin@zenithalms.com',
            'password' => Hash::make('admin123'),
            'role_id' => 1, // Super Admin
            'organization_id' => null,
            'branch_id' => null,
            'department_id' => null,
            'phone' => '+1-555-0100',
            'avatar' => 'images/demo/avatars/admin.jpg',
            'bio' => 'System administrator and founder of ZenithaLMS platform.',
            'is_active' => true,
            'email_verified_at' => now(),
            'last_login_at' => now(),
        ]);

        // Organization Admins
        User::create([
            'name' => 'Sarah Mitchell',
            'email' => 'sarah.mitchell@techacademy.zenithalms.com',
            'password' => Hash::make('admin123'),
            'role_id' => 2, // Organization Admin
            'organization_id' => 1, // Tech Academy
            'branch_id' => null,
            'department_id' => null,
            'phone' => '+1-555-0101',
            'avatar' => 'images/demo/avatars/sarah-mitchell.jpg',
            'bio' => 'Director of Tech Academy International with 15+ years in technology education.',
            'is_active' => true,
            'email_verified_at' => now(),
            'last_login_at' => now()->subHours(2),
        ]);

        User::create([
            'name' => 'Michael Chen',
            'email' => 'michael.chen@businessschool.zenithalms.com',
            'password' => Hash::make('admin123'),
            'role_id' => 2, // Organization Admin
            'organization_id' => 2, // Business School
            'branch_id' => null,
            'department_id' => null,
            'phone' => '+44-20-7123-4568',
            'avatar' => 'images/demo/avatars/michael-chen.jpg',
            'bio' => 'Dean of Business School Global, former Harvard Business School professor.',
            'is_active' => true,
            'email_verified_at' => now(),
            'last_login_at' => now()->subHours(5),
        ]);

        User::create([
            'name' => 'Emma Dubois',
            'email' => 'emma.dubois@creativearts.zenithalms.com',
            'password' => Hash::make('admin123'),
            'role_id' => 2, // Organization Admin
            'organization_id' => 3, // Creative Arts
            'branch_id' => null,
            'department_id' => null,
            'phone' => '+33-1-42-68-53-01',
            'avatar' => 'images/demo/avatars/emma-dubois.jpg',
            'bio' => 'Founder and Creative Director of Creative Arts Institute, award-winning digital artist.',
            'is_active' => true,
            'email_verified_at' => now(),
            'last_login_at' => now()->subHours(1),
        ]);

        // Professional Instructors
        User::create([
            'name' => 'Dr. James Wilson',
            'email' => 'james.wilson@techacademy.zenithalms.com',
            'password' => Hash::make('instructor123'),
            'role_id' => 4, // Instructor
            'organization_id' => 1, // Tech Academy
            'branch_id' => null,
            'department_id' => null,
            'phone' => '+1-555-0102',
            'avatar' => 'images/demo/avatars/james-wilson.jpg',
            'bio' => 'PhD in Computer Science from MIT, 10+ years teaching experience in web development and AI.',
            'is_active' => true,
            'email_verified_at' => now(),
            'last_login_at' => now()->subMinutes(30),
        ]);

        User::create([
            'name' => 'Lisa Thompson',
            'email' => 'lisa.thompson@techacademy.zenithalms.com',
            'password' => Hash::make('instructor123'),
            'role_id' => 4, // Instructor
            'organization_id' => 1, // Tech Academy
            'branch_id' => null,
            'department_id' => null,
            'phone' => '+1-555-0103',
            'avatar' => 'images/demo/avatars/lisa-thompson.jpg',
            'bio' => 'Senior React Developer and educator, former Google engineer with expertise in frontend frameworks.',
            'is_active' => true,
            'email_verified_at' => now(),
            'last_login_at' => now()->subHours(3),
        ]);

        User::create([
            'name' => 'Prof. Robert Johnson',
            'email' => 'robert.johnson@businessschool.zenithalms.com',
            'password' => Hash::make('instructor123'),
            'role_id' => 4, // Instructor
            'organization_id' => 2, // Business School
            'branch_id' => null,
            'department_id' => null,
            'phone' => '+44-20-7123-4569',
            'avatar' => 'images/demo/avatars/robert-johnson.jpg',
            'bio' => 'Professor of Strategic Management, former McKinsey consultant with 20+ years business experience.',
            'is_active' => true,
            'email_verified_at' => now(),
            'last_login_at' => now()->subHours(4),
        ]);

        User::create([
            'name' => 'Maria Garcia',
            'email' => 'maria.garcia@creativearts.zenithalms.com',
            'password' => Hash::make('instructor123'),
            'role_id' => 4, // Instructor
            'organization_id' => 3, // Creative Arts
            'branch_id' => null,
            'department_id' => null,
            'phone' => '+33-1-42-68-53-02',
            'avatar' => 'images/demo/avatars/maria-garcia.jpg',
            'bio' => 'Professional graphic designer and digital artist, Adobe Certified Expert with 12+ years experience.',
            'is_active' => true,
            'email_verified_at' => now(),
            'last_login_at' => now()->subMinutes(15),
        ]);

        // Teaching Assistants
        User::create([
            'name' => 'Alex Kumar',
            'email' => 'alex.kumar@techacademy.zenithalms.com',
            'password' => Hash::make('ta123'),
            'role_id' => 5, // Teaching Assistant
            'organization_id' => 1, // Tech Academy
            'branch_id' => null,
            'department_id' => null,
            'phone' => '+1-555-0104',
            'avatar' => 'images/demo/avatars/alex-kumar.jpg',
            'bio' => 'Computer Science graduate student, passionate about helping students learn programming.',
            'is_active' => true,
            'email_verified_at' => now(),
            'last_login_at' => now()->subHours(6),
        ]);

        // Demo Students
        $students = [
            [
                'name' => 'David Brown',
                'email' => 'david.brown@techacademy.zenithalms.com',
                'organization_id' => 1,
                'avatar' => 'images/demo/avatars/david-brown.jpg',
                'bio' => 'Aspiring full-stack developer, currently learning React and Node.js.',
            ],
            [
                'name' => 'Sophie Martin',
                'email' => 'sophie.martin@businessschool.zenithalms.com',
                'organization_id' => 2,
                'avatar' => 'images/demo/avatars/sophie-martin.jpg',
                'bio' => 'MBA student focusing on digital transformation and innovation.',
            ],
            [
                'name' => 'Pierre Laurent',
                'email' => 'pierre.laurent@creativearts.zenithalms.com',
                'organization_id' => 3,
                'avatar' => 'images/demo/avatars/pierre-laurent.jpg',
                'bio' => 'Digital artist exploring new techniques in concept art and illustration.',
            ],
            [
                'name' => 'Emma Wilson',
                'email' => 'emma.wilson@techacademy.zenithalms.com',
                'organization_id' => 1,
                'avatar' => 'images/demo/avatars/emma-wilson.jpg',
                'bio' => 'Frontend developer learning advanced JavaScript and React.',
            ],
            [
                'name' => 'James Taylor',
                'email' => 'james.taylor@businessschool.zenithalms.com',
                'organization_id' => 2,
                'avatar' => 'images/demo/avatars/james-taylor.jpg',
                'bio' => 'Business analyst studying strategic management and data analytics.',
            ],
            [
                'name' => 'Claire Rousseau',
                'email' => 'claire.rousseau@creativearts.zenithalms.com',
                'organization_id' => 3,
                'avatar' => 'images/demo/avatars/claire-rousseau.jpg',
                'bio' => 'Photography student specializing in portrait and landscape photography.',
            ],
        ];

        foreach ($students as $index => $student) {
            User::create([
                'name' => $student['name'],
                'email' => $student['email'],
                'password' => Hash::make('student123'),
                'role_id' => 6, // Student
                'organization_id' => $student['organization_id'],
                'branch_id' => null,
                'department_id' => null,
                'phone' => '+1-555-01' . str_pad($index + 10, 2, '0', STR_PAD_LEFT),
                'avatar' => $student['avatar'],
                'bio' => $student['bio'],
                'is_active' => true,
                'email_verified_at' => now(),
                'last_login_at' => now()->subHours(rand(1, 24)),
            ]);
        }

        // Parents
        User::create([
            'name' => 'Jennifer Brown',
            'email' => 'jennifer.brown@techacademy.zenithalms.com',
            'password' => Hash::make('parent123'),
            'role_id' => 7, // Parent
            'organization_id' => 1, // Tech Academy
            'branch_id' => null,
            'department_id' => null,
            'phone' => '+1-555-0200',
            'avatar' => 'images/demo/avatars/jennifer-brown.jpg',
            'bio' => 'Parent of David Brown, monitoring his progress in web development courses.',
            'is_active' => true,
            'email_verified_at' => now(),
            'last_login_at' => now()->subHours(8),
        ]);
    }
}
