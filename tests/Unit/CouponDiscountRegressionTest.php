<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Http\Controllers\API\ZenithaLmsPaymentController;
use ReflectionMethod;

/**
 * Regression tests for coupon discount logic bugs
 * 
 * Bug fixes verified:
 * - BUG #1: Infinite recursion from duplicate applyCoupon method names (renamed to calculateCouponDiscount)
 * - BUG #4: Duplicate processPayment method names (renamed to simulatePaymentProcessing)
 */
class CouponDiscountRegressionTest extends TestCase
{
    /**
     * Test that calculateCouponDiscount returns correct structure for valid coupon
     */
    public function test_calculate_coupon_discount_valid_coupon_returns_correct_structure(): void
    {
        $controller = new ZenithaLmsPaymentController();
        
        $method = new ReflectionMethod($controller, 'calculateCouponDiscount');
        $method->setAccessible(true);
        
        $result = $method->invoke($controller, 'SAVE10', 100.00);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('valid', $result);
        $this->assertArrayHasKey('final_amount', $result);
        $this->assertTrue($result['valid']);
        $this->assertEquals(90.00, $result['final_amount']); // 10% off 100
    }

    /**
     * Test that calculateCouponDiscount returns correct structure for invalid coupon
     */
    public function test_calculate_coupon_discount_invalid_coupon_returns_original_amount(): void
    {
        $controller = new ZenithaLmsPaymentController();
        
        $method = new ReflectionMethod($controller, 'calculateCouponDiscount');
        $method->setAccessible(true);
        
        $result = $method->invoke($controller, 'INVALID_CODE', 100.00);
        
        $this->assertIsArray($result);
        $this->assertFalse($result['valid']);
        $this->assertEquals(100.00, $result['final_amount']);
    }

    /**
     * Test fixed discount coupon calculation
     */
    public function test_calculate_coupon_discount_fixed_amount(): void
    {
        $controller = new ZenithaLmsPaymentController();
        
        $method = new ReflectionMethod($controller, 'calculateCouponDiscount');
        $method->setAccessible(true);
        
        $result = $method->invoke($controller, 'FLAT50', 100.00);
        
        $this->assertTrue($result['valid']);
        $this->assertEquals(50.00, $result['final_amount']); // $50 off $100
        $this->assertEquals('fixed', $result['discount_type']);
    }

    /**
     * Test that no method name collision exists (regression for infinite recursion bug)
     */
    public function test_no_duplicate_method_names_in_api_controller(): void
    {
        $reflection = new \ReflectionClass(ZenithaLmsPaymentController::class);
        $methods = $reflection->getMethods();
        
        $methodNames = array_map(fn($m) => $m->getName(), $methods);
        $uniqueNames = array_unique($methodNames);
        
        // If there were duplicates, count would differ
        $this->assertCount(count($uniqueNames), $methodNames, 'Duplicate method names found in controller');
    }
}
