<?php

namespace App\Services;

class CouponService
{
    /**
     * Apply coupon discount
     */
    public function applyCoupon(string $couponCode, float $amount): array
    {
        // Simulate coupon validation
        $coupons = [
            'SAVE10' => ['discount_type' => 'percentage', 'discount_value' => 10],
            'SAVE20' => ['discount_type' => 'percentage', 'discount_value' => 20],
            'FLAT50' => ['discount_type' => 'fixed', 'discount_value' => 50],
            'NEWUSER' => ['discount_type' => 'percentage', 'discount_value' => 15],
            'SPECIAL25' => ['discount_type' => 'percentage', 'discount_value' => 25],
        ];

        if (!isset($coupons[$couponCode])) {
            return [
                'valid' => false,
                'message' => 'Invalid coupon code',
                'final_amount' => $amount,
            ];
        }

        $coupon = $coupons[$couponCode];
        $discountAmount = 0;

        if ($coupon['discount_type'] === 'percentage') {
            $discountAmount = $amount * ($coupon['discount_value'] / 100);
        } else {
            $discountAmount = $coupon['discount_value'];
        }

        $finalAmount = max(0, $amount - $discountAmount);

        return [
            'valid' => true,
            'message' => 'Coupon applied successfully',
            'original_amount' => $amount,
            'discount_amount' => $discountAmount,
            'final_amount' => $finalAmount,
            'discount_type' => $coupon['discount_type'],
            'discount_value' => $coupon['discount_value'],
        ];
    }

    /**
     * Validate coupon code
     */
    public function validateCoupon(string $couponCode): array
    {
        $coupons = [
            'SAVE10' => [
                'code' => 'SAVE10',
                'name' => 'Save 10%',
                'discount_type' => 'percentage',
                'discount_value' => 10,
                'min_amount' => 10,
                'max_discount' => 100,
                'usage_limit' => 1000,
                'expires_at' => now()->addMonths(6),
            ],
            'SAVE20' => [
                'code' => 'SAVE20',
                'name' => 'Save 20%',
                'discount_type' => 'percentage',
                'discount_value' => 20,
                'min_amount' => 20,
                'max_discount' => 200,
                'usage_limit' => 500,
                'expires_at' => now()->addMonths(3),
            ],
            'FLAT50' => [
                'code' => 'FLAT50',
                'name' => '$50 Off',
                'discount_type' => 'fixed',
                'discount_value' => 50,
                'min_amount' => 100,
                'max_discount' => 50,
                'usage_limit' => 200,
                'expires_at' => now()->addMonths(2),
            ],
            'NEWUSER' => [
                'code' => 'NEWUSER',
                'name' => 'New User 15% Off',
                'discount_type' => 'percentage',
                'discount_value' => 15,
                'min_amount' => 5,
                'max_discount' => 75,
                'usage_limit' => 100,
                'expires_at' => now()->addMonths(12),
            ],
            'SPECIAL25' => [
                'code' => 'SPECIAL25',
                'name' => 'Special 25% Off',
                'discount_type' => 'percentage',
                'discount_value' => 25,
                'min_amount' => 25,
                'max_discount' => 150,
                'usage_limit' => 300,
                'expires_at' => now()->addMonth(),
            ],
        ];

        if (!isset($coupons[$couponCode])) {
            return [
                'valid' => false,
                'message' => 'Invalid coupon code',
            ];
        }

        $coupon = $coupons[$couponCode];

        // Check expiration
        if (now()->isAfter($coupon['expires_at'])) {
            return [
                'valid' => false,
                'message' => 'Coupon has expired',
            ];
        }

        return [
            'valid' => true,
            'coupon' => $coupon,
            'message' => 'Coupon is valid',
        ];
    }

    /**
     * Get available coupons
     */
    public function getAvailableCoupons(): array
    {
        return [
            [
                'code' => 'SAVE10',
                'name' => 'Save 10%',
                'description' => 'Get 10% off on your purchase',
                'discount_type' => 'percentage',
                'discount_value' => 10,
                'min_amount' => 10,
            ],
            [
                'code' => 'SAVE20',
                'name' => 'Save 20%',
                'description' => 'Get 20% off on your purchase',
                'discount_type' => 'percentage',
                'discount_value' => 20,
                'min_amount' => 20,
            ],
            [
                'code' => 'FLAT50',
                'name' => '$50 Off',
                'description' => 'Get $50 off on purchases over $100',
                'discount_type' => 'fixed',
                'discount_value' => 50,
                'min_amount' => 100,
            ],
            [
                'code' => 'NEWUSER',
                'name' => 'New User 15% Off',
                'description' => 'Special discount for new users',
                'discount_type' => 'percentage',
                'discount_value' => 15,
                'min_amount' => 5,
            ],
            [
                'code' => 'SPECIAL25',
                'name' => 'Special 25% Off',
                'description' => 'Limited time special offer',
                'discount_type' => 'percentage',
                'discount_value' => 25,
                'min_amount' => 25,
            ],
        ];
    }

    /**
     * Calculate discount amount
     */
    public function calculateDiscount(string $couponCode, float $amount): float
    {
        $result = $this->applyCoupon($couponCode, $amount);
        
        if ($result['valid']) {
            return $result['discount_amount'];
        }
        
        return 0;
    }

    /**
     * Check if coupon is applicable to amount
     */
    public function isApplicable(string $couponCode, float $amount): bool
    {
        $validation = $this->validateCoupon($couponCode);
        
        if (!$validation['valid']) {
            return false;
        }
        
        $coupon = $validation['coupon'];
        
        return $amount >= $coupon['min_amount'];
    }
}
