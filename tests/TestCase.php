<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication, RefreshDatabase;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Disable CSRF protection for all tests to prevent 419 errors
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
        
        // For tests, we need to run tenant migrations on the default test connection
        // since we're using in-memory database
        $this->runTenantMigrationsForTests();
        
        // Run the test roles seeder to ensure all roles exist with proper display_name
        $this->artisan('db:seed', ['--class' => 'Database\\Seeders\\TestRolesSeeder']);
    }
    
    /**
     * Run tenant migrations in test environment.
     * Tests use in-memory database, so we run tenant migrations directly.
     */
    protected function runTenantMigrationsForTests(): void
    {
        // Check if tenant migrations have already been run in this test
        if (!property_exists($this, 'tenantMigrationsRun') || !$this->tenantMigrationsRun) {
            Artisan::call('migrate', [
                '--path' => 'database/migrations/tenant',
                '--realpath' => true,
            ]);
            $this->tenantMigrationsRun = true;
        }
    }
}
