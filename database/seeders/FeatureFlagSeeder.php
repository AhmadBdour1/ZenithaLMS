<?php

namespace Database\Seeders;

use App\Services\FeatureFlagService;
use Illuminate\Database\Seeder;

class FeatureFlagSeeder extends Seeder
{
    public function run(): void
    {
        $featureFlagService = app(FeatureFlagService::class);
        
        // Seed default feature flags idempotently
        $featureFlagService->seedDefaults();
        
        $this->command->info('Feature flags seeded successfully.');
    }
}
