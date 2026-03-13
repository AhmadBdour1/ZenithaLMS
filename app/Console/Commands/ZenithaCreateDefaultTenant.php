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
            
            // Step 2: Create tenant database file
            $this->createTenantDatabaseFile($tenant);
            
            // Step 3: Initialize tenancy context
            tenancy()->initialize($tenant);
            $this->info('✓ Tenancy context initialized');
            
            // Step 4: Verify tenant schema
            $this->verifyTenantSchema();
            
            // Step 5: End tenancy
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
    
    private function createTenantDatabaseFile($tenant)
    {
        $dbConnection = config('database.default', 'mysql');
        
        if ($dbConnection === 'sqlite') {
            $tenantDbPath = database_path('tenant_' . $tenant->id . '.sqlite');
            
            $this->info('Creating tenant database file: ' . $tenantDbPath);
            
            // Ensure database directory exists
            $tenantDbDir = dirname($tenantDbPath);
            if (!is_dir($tenantDbDir)) {
                mkdir($tenantDbDir, 0755, true);
                $this->info('✓ Created tenant database directory');
            }
            
            // Create empty SQLite file if it doesn't exist
            if (!file_exists($tenantDbPath)) {
                touch($tenantDbPath);
                chmod($tenantDbPath, 0644);
                $this->info('✓ Created tenant SQLite database file');
            } else {
                $this->info('✓ Tenant SQLite database file already exists');
            }
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
