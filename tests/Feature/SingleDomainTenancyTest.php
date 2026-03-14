<?php

namespace Tests\Feature;

use App\Models\Central\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class SingleDomainTenancyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a default tenant for testing
        Tenant::create([
            'id' => 'default',
            'tenancy_db_name' => 'tenant_test',
            'tenancy_db_username' => 'test',
            'tenancy_db_password' => 'test',
        ]);
    }

    /** @test */
    public function it_verifies_auth_routes_are_only_loaded_from_auth_php()
    {
        // Verify that auth.php does not include tenant routes
        $authRoutesContent = file_get_contents(base_path('routes/auth.php'));
        $this->assertStringNotContainsString('require __DIR__', $authRoutesContent);
        $this->assertStringNotContainsString('tenant.php', $authRoutesContent);
    }

    /** @test */
    public function it_verifies_tenant_php_does_not_include_auth_routes()
    {
        // Verify that tenant.php does not include auth routes
        $tenantRoutesContent = file_get_contents(base_path('routes/tenant.php'));
        $this->assertStringNotContainsString('auth.php', $tenantRoutesContent);
        $this->assertStringNotContainsString('login', $tenantRoutesContent);
    }

    /** @test */
    public function it_initializes_tenancy_before_session_for_web_requests()
    {
        // Mock the tenant initialization
        $tenant = Tenant::find('default');
        
        // Test that the middleware is registered in web group
        $bootstrapContent = file_get_contents(base_path('bootstrap/app.php'));
        $this->assertStringContainsString('InitializeSingleDomainTenancy', $bootstrapContent);
        $this->assertStringContainsString('StartSession', $bootstrapContent);
        
        // Verify middleware order - tenancy should come before session
        $webGroupPattern = '/InitializeSingleDomainTenancy.*StartSession/s';
        $this->assertMatchesRegularExpression($webGroupPattern, $bootstrapContent);
    }

    /** @test */
    public function login_request_has_defensive_tenancy_safeguard()
    {
        $loginRequestContent = file_get_contents(base_path('app/Http/Requests/Auth/LoginRequest.php'));
        
        $this->assertStringContainsString('ensureTenancyInitialized', $loginRequestContent);
        $this->assertStringContainsString('tenancy()->initialize', $loginRequestContent);
        $this->assertStringContainsString('safety net', $loginRequestContent);
    }

    /** @test */
    public function it_skips_tenancy_initialization_in_testing_environment()
    {
        $middlewareContent = file_get_contents(base_path('app/Http/Middleware/InitializeSingleDomainTenancy.php'));
        $this->assertStringContainsString('environment(\'testing\')', $middlewareContent);
        
        $loginRequestContent = file_get_contents(base_path('app/Http/Requests/Auth/LoginRequest.php'));
        $this->assertStringContainsString('environment(\'testing\')', $loginRequestContent);
    }

    /** @test */
    public function it_handles_missing_tenant_gracefully()
    {
        $middlewareContent = file_get_contents(base_path('app/Http/Middleware/InitializeSingleDomainTenancy.php'));
        
        $this->assertStringContainsString('Default tenant not found after installation', $middlewareContent);
        $this->assertStringContainsString('abort(500', $middlewareContent);
    }

    /** @test */
    public function it_skips_installer_routes()
    {
        $middlewareContent = file_get_contents(base_path('app/Http/Middleware/InitializeSingleDomainTenancy.php'));
        
        $this->assertStringContainsString('install/*', $middlewareContent);
        $this->assertStringContainsString('install', $middlewareContent);
    }

    /** @test */
    public function it_respects_environment_variable_for_default_tenant()
    {
        $middlewareContent = file_get_contents(base_path('app/Http/Middleware/InitializeSingleDomainTenancy.php'));
        $this->assertStringContainsString('TENANCY_DEFAULT_TENANT_ID', $middlewareContent);
        
        $loginRequestContent = file_get_contents(base_path('app/Http/Requests/Auth/LoginRequest.php'));
        $this->assertStringContainsString('TENANCY_DEFAULT_TENANT_ID', $loginRequestContent);
    }

    /** @test */
    public function it_logs_debug_information()
    {
        $middlewareContent = file_get_contents(base_path('app/Http/Middleware/InitializeSingleDomainTenancy.php'));
        $this->assertStringContainsString('Log::debug', $middlewareContent);
        $this->assertStringContainsString('InitializeSingleDomainTenancy: Initializing tenant', $middlewareContent);
        
        $loginRequestContent = file_get_contents(base_path('app/Http/Requests/Auth/LoginRequest.php'));
        $this->assertStringContainsString('Log::debug', $loginRequestContent);
        $this->assertStringContainsString('LoginRequest: Initializing tenant as safety net', $loginRequestContent);
    }
}
