<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication, RefreshDatabase;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Run the test roles seeder to ensure all roles exist with proper display_name
        $this->artisan('db:seed', ['--class' => 'Database\\Seeders\\TestRolesSeeder']);
    }
}
