<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Central\Tenant;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Artisan;

class CreateDefaultTenant extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'zenitha:create-default-tenant';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create default tenant for ZenithaLMS installation';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            Log::info('Creating default tenant for installation');
            
            // Create default tenant
            $tenant = Tenant::firstOrCreate([
                'id' => 'default'
            ], [
                'id' => 'default',
                'tenancy_db_name' => 'tenant_default',
                'tenancy_db_username' => config('database.connections.sqlite.username'),
                'tenancy_db_password' => config('database.connections.sqlite.password'),
            ]);
            
            $this->info("✅ Default tenant created successfully!");
            $this->line("   Tenant ID: {$tenant->id}");
            $this->line("   Database: {$tenant->tenancy_db_name}");
            
            // Initialize tenant context
            tenancy()->initialize($tenant);
            
            // Run tenant migrations
            $this->info('🔄 Running tenant migrations...');
            Artisan::call('tenants:migrate', [
                '--force' => true,
                '--tenants' => ['default']
            ]);
            
            $this->info('✅ Tenant migrations completed!');
            
            // Run tenant seeders
            $this->info('🌱 Running tenant seeders...');
            Artisan::call('tenants:seed', [
                '--class' => 'RoleSeeder',
                '--tenants' => ['default'],
                '--force' => true
            ]);
            Artisan::call('tenants:seed', [
                '--class' => 'OrganizationSeeder',
                '--tenants' => ['default'],
                '--force' => true
            ]);
            Artisan::call('tenants:seed', [
                '--class' => 'CategorySeeder',
                '--tenants' => ['default'],
                '--force' => true
            ]);
            Artisan::call('tenants:seed', [
                '--class' => 'SkillSeeder',
                '--tenants' => ['default'],
                '--force' => true
            ]);
            
            $this->info('✅ Tenant seeders completed!');
            $this->info('🎉 Default tenant setup completed successfully!');
            
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            Log::error('Failed to create default tenant', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            
            $this->error('❌ Failed to create default tenant');
            $this->line('   Error: ' . $e->getMessage());
            
            return Command::FAILURE;
        }
    }
}
