<?php

namespace Database\Seeders;

use App\Models\Central\Plan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PlansSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Use central connection
        DB::connection('central')->table('plans')->delete();

        $plans = [
            [
                'name' => 'Starter',
                'slug' => 'starter',
                'description' => 'Perfect for small institutions getting started with online learning',
                'price' => 0,
                'billing_cycle' => 'lifetime',
                'max_students' => 50,
                'max_instructors' => 5,
                'max_courses' => 10,
                'max_storage_mb' => 2000, // 2GB
                'features' => json_encode([
                    'Basic LMS features',
                    'Course management',
                    'Student enrollment',
                    'Quiz & assessments',
                    'Email support',
                ]),
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'Professional',
                'slug' => 'professional',
                'description' => 'For growing institutions with advanced requirements',
                'price' => 99.00,
                'billing_cycle' => 'monthly',
                'max_students' => 200,
                'max_instructors' => 20,
                'max_courses' => 50,
                'max_storage_mb' => 10000, // 10GB
                'features' => json_encode([
                    'All Starter features',
                    'Custom branding',
                    'Advanced analytics',
                    'Virtual classrooms',
                    'Forums & discussions',
                    'Certificate generation',
                    'Priority support',
                ]),
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'Enterprise',
                'slug' => 'enterprise',
                'description' => 'Unlimited features for large organizations',
                'price' => 299.00,
                'billing_cycle' => 'monthly',
                'max_students' => 1000,
                'max_instructors' => 100,
                'max_courses' => 500,
                'max_storage_mb' => 50000, // 50GB
                'features' => json_encode([
                    'All Professional features',
                    'Unlimited students & instructors',
                    'AI-powered features',
                    'API access',
                    'Custom integrations',
                    'White-label solution',
                    'Dedicated account manager',
                    '24/7 premium support',
                ]),
                'is_active' => true,
                'sort_order' => 3,
            ],
        ];

        foreach ($plans as $plan) {
            Plan::create($plan);
        }

        $this->command->info('✅ Created ' . count($plans) . ' default plans');
    }
}
