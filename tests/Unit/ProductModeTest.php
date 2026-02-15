<?php

namespace Tests\Unit;

use Tests\TestCase;

class ProductModeTest extends TestCase
{
    public function test_product_mode_returns_standard_by_default(): void
    {
        // The default value from config/app.php is 'standard'
        // We don't need to override it, just check the default
        $mode = product_mode();
        
        $this->assertEquals('standard', $mode);
    }
    
    public function test_product_mode_returns_enterprise_when_set(): void
    {
        // Set enterprise mode
        config(['app.product_mode' => 'enterprise']);
        
        $mode = product_mode();
        
        $this->assertEquals('enterprise', $mode);
    }
    
    public function test_product_mode_returns_standard_when_explicitly_set(): void
    {
        // Explicitly set standard mode
        config(['app.product_mode' => 'standard']);
        
        $mode = product_mode();
        
        $this->assertEquals('standard', $mode);
    }
}
