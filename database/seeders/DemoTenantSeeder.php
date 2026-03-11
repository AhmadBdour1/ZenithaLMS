<?php

namespace Database\Seeders;

use App\Models\Central\Plan;
use App\Services\Tenancy\TenantOnboardingService;
use Illuminate\Database\Seeder;

class DemoTenantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $onboardingService = new TenantOnboardingService();

        // Get the Starter plan
        $starterPlan = Plan::where('slug', 'starter')->first();

        if (!$starterPlan) {
            $this->command->error('Starter plan not found. Run PlansSeeder first.');
            return;
        }

        // Create demo tenant
        $this->command->info('Creating demo tenant...');
        
        $tenant = $onboardingService->createTenant([
            'organization_name' => 'Demo University',
            'admin_name' => 'Demo Admin',
            'admin_email' => 'admin@demo.zenithalms.test',
            'subdomain' => 'demo',
            'plan_id' => $starterPlan->id,
        ]);

        $this->command->info("✅ Demo tenant created: {$tenant->id}");
        $this->command->info("   Domain: demo.zenithalms.test");
        
        // Wait for tenant database to be created
        sleep(2);
        
        // Create admin user in tenant context
        $this->command->info('Creating tenant admin user...');
        
        $onboardingService->createTenantAdmin($tenant, [
            'name' => 'Demo Admin',
            'email' => 'admin@demo.zenithalms.test',
            'password' => 'password123',
        ]);

        $this->command->info('✅ Demo tenant setup complete!');
        $this->command->info('   Email: admin@demo.zenithalms.test');
        $this->command->info('   Password: password123');
    }
}
