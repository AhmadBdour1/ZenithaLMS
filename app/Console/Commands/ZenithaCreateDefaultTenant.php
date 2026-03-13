<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

class ZenithaCreateDefaultTenant extends Command
{
    protected $signature = 'zenitha:create-default-tenant';
    protected $description = 'Create default tenant and verify schema';

    public function handle()
    {
        $this->info('Creating default tenant...');
        
        try {
            // Step 1: Create tenant
            $tenant = \App\Models\Central\Tenant::firstOrCreate([
                'id' => 'default'
            ], [
                'id' => 'default',
                'tenancy_db_name' => 'tenant_default',
                'tenancy_db_username' => config('database.connections.sqlite.username'),
                'tenancy_db_password' => config('database.connections.sqlite.password'),
            ]);
            
            $this->info('✓ Tenant created: ' . $tenant->id);
            
            // Step 2: Initialize tenancy context
            tenancy()->initialize($tenant);
            $this->info('✓ Tenancy context initialized');
            
            // Step 3: Verify tenant schema
            $this->verifyTenantSchema();
            
            // Step 4: End tenancy
            tenancy()->end();
            $this->info('✓ Tenancy ended');
            
            $this->info('Default tenant created and verified successfully!');
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            $this->error('Failed to create default tenant: ' . $e->getMessage());
            Log::error('Tenant creation failed', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            return Command::FAILURE;
        }
    }
    
    private function verifyTenantSchema()
    {
        $this->info('Verifying tenant schema...');
        
        $requiredTables = ['users', 'roles', 'courses'];
        
        foreach ($requiredTables as $table) {
            if (!Schema::hasTable($table)) {
                throw new \Exception("Required table '{$table}' is missing from tenant database");
            }
            
            $this->info("✓ Table '{$table}' exists");
        }
        
        $this->info('All required tenant tables verified!');
    }
}
