<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Organization;

class OrganizationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $organizations = [
            [
                'name' => 'Tech Academy International',
                'slug' => 'tech-academy-intl',
                'domain' => 'techacademy.zenithalms.com',
                'description' => 'Leading technology education platform with focus on software development, AI, and IT certifications. We provide cutting-edge training for the digital economy.',
                'logo' => 'images/demo/logos/tech-academy.png',
                'email' => 'info@techacademy.zenithalms.com',
                'phone' => '+1-555-0123',
                'address' => '123 Innovation Drive, Tech Valley, CA 94025',
                'city' => 'San Francisco',
                'country' => 'United States',
                'settings' => [
                    'theme_color' => '#4F46E5',
                    'primary_color' => '#6366F1',
                    'secondary_color' => '#8B5CF6',
                    'accent_color' => '#EC4899',
                    'enable_ai_features' => true,
                    'enable_community' => true,
                    'default_language' => 'en',
                    'timezone' => 'America/Los_Angeles',
                    'currency' => 'USD',
                    'date_format' => 'M d, Y',
                    'time_format' => '12-hour'
                ],
                'is_active' => true,
                'subscription_expires_at' => now()->addYear(),
                'subscription_tier' => 'enterprise',
                'max_students' => -1, // Unlimited
                'max_instructors' => -1, // Unlimited
            ],
            [
                'name' => 'Business School Global',
                'slug' => 'business-school-global',
                'domain' => 'businessschool.zenithalms.com',
                'description' => 'Premier business education institution offering MBA programs, executive education, and professional development courses for business leaders worldwide.',
                'logo' => 'images/demo/logos/business-school.png',
                'email' => 'admissions@businessschool.zenithalms.com',
                'phone' => '+44-20-7123-4567',
                'address' => '456 Financial District, London EC2A 1BB',
                'city' => 'London',
                'country' => 'United Kingdom',
                'settings' => [
                    'theme_color' => '#06B6D4',
                    'primary_color' => '#0891B2',
                    'secondary_color' => '#0E7490',
                    'accent_color' => '#F59E0B',
                    'enable_ai_features' => true,
                    'enable_community' => true,
                    'default_language' => 'en',
                    'timezone' => 'Europe/London',
                    'currency' => 'GBP',
                    'date_format' => 'd M Y',
                    'time_format' => '24-hour'
                ],
                'is_active' => true,
                'subscription_expires_at' => now()->addMonths(6),
                'subscription_tier' => 'professional',
                'max_students' => 500,
                'max_instructors' => 50,
            ],
            [
                'name' => 'Creative Arts Institute',
                'slug' => 'creative-arts-institute',
                'domain' => 'creativearts.zenithalms.com',
                'description' => 'Innovative arts and design school offering courses in digital art, graphic design, animation, photography, and creative technologies for the modern artist.',
                'logo' => 'images/demo/logos/creative-arts.png',
                'email' => 'hello@creativearts.zenithalms.com',
                'phone' => '+33-1-42-68-53-00',
                'address' => '789 Art District, 75001 Paris',
                'city' => 'Paris',
                'country' => 'France',
                'settings' => [
                    'theme_color' => '#EC4899',
                    'primary_color' => '#DB2777',
                    'secondary_color' => '#BE185D',
                    'accent_color' => '#F59E0B',
                    'enable_ai_features' => true,
                    'enable_community' => true,
                    'default_language' => 'fr',
                    'timezone' => 'Europe/Paris',
                    'currency' => 'EUR',
                    'date_format' => 'd/m/Y',
                    'time_format' => '24-hour'
                ],
                'is_active' => true,
                'subscription_expires_at' => now()->addMonths(3),
                'subscription_tier' => 'starter',
                'max_students' => 50,
                'max_instructors' => 5,
            ],
        ];

        foreach ($organizations as $org) {
            Organization::create($org);
        }
    }
}
