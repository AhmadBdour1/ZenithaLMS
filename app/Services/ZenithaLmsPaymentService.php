<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\PaymentGateway;
use App\Models\User;
use App\Models\Course;
use App\Models\Wallet;
use App\Models\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ZenithaLmsPaymentService
{
    /**
     * Payment Service Configuration
     */
    private $gateways;
    private $defaultCurrency;
    private $webhookSecret;
    
    public function __construct()
    {
        $this->gateways = $this->initializeGateways();
        $this->defaultCurrency = config('zenithalms.payment.default_currency', 'USD');
        $this->webhookSecret = config('zenithalms.payment.webhook_secret');
    }
    
    /**
     * Process payment for course enrollment
     */
    public function processPayment($userId, $courseId, $paymentMethod, $paymentDetails = [], $couponCode = null)
    {
        $user = User::findOrFail($userId);
        $course = Course::findOrFail($courseId);
        
        // Check if course is free
        if ($course->is_free) {
            return $this->processFreeEnrollment($user, $course);
        }
        
        // Check if already enrolled
        if ($user->enrollments()->where('course_id', $courseId)->exists()) {
            return [
                'success' => false,
                'message' => 'Already enrolled in this course',
                'payment_id' => null,
            ];
        }
        
        // Calculate final amount
        $amount = $course->price;
        
        // Apply coupon if provided
        if ($couponCode) {
            $discount = $this->applyCoupon($couponCode, $amount);
            $amount = $discount['final_amount'];
        }
        
        // Check wallet balance
        $wallet = $user->wallet;
        if ($wallet && $wallet->balance >= $amount) {
            return $this->processWalletPayment($user, $wallet, $amount, $course);
        }
        
        // Process external payment
        return $this->processExternalPayment($user, $course, $amount, $paymentMethod, $paymentDetails, $couponCode);
    }
    
    /**
     * Process wallet payment
     */
    private function processWalletPayment($user, $wallet, $amount, $course)
    {
        if ($wallet->balance < $amount) {
            return [
                'success' => false,
                'message' => 'Insufficient wallet balance',
                'payment_id' => null,
            ];
        }
        
        // Create payment record
        $payment = Payment::create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'type' => 'course_enrollment',
            'amount' => $amount,
            'currency' => $this->defaultCurrency,
            'status' => 'pending',
            'payment_gateway_id' => $this->getWalletGatewayId(),
            'payment_data' => [
                'payment_method' => 'wallet',
                'wallet_balance_before' => $wallet->balance,
            ],
        ]);
        
        // Deduct from wallet
        $wallet->update([
            'balance' => $wallet->balance - $amount,
        ]);
        
        // Create wallet transaction
        $wallet->transactions()->create([
            'type' => 'debit',
            'amount' => $amount,
            'description' => 'Payment for course: ' . $course->title,
        ]);
        
        // Complete payment
        $this->completePayment($payment);
        
        return [
            'success' => true,
            'message' => 'Payment processed successfully using wallet',
            'payment_id' => $payment->id,
            'amount' => $amount,
        ];
    }
    
    /**
     * Process external payment
     */
    private function processExternalPayment($user, $course, $amount, $paymentMethod, $paymentDetails, $couponCode)
    {
        $gateway = $this->getGateway($paymentMethod);
        
        if (!$gateway) {
            return [
                'success' => false,
                'message' => 'Payment gateway not supported',
                'payment_id' => null,
            ];
        }
        
        // Create payment record
        $payment = Payment::create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'type' => 'course_enrollment',
            'amount' => $amount,
            'currency' => $this->defaultCurrency,
            'status' => 'pending',
            'payment_gateway_id' => $gateway->id,
            'payment_data' => array_merge([
                'payment_method' => $paymentMethod,
                'coupon_code' => $couponCode,
            ], $paymentDetails),
        ]);
        
        // Process payment based on gateway
        $result = $this->processGatewayPayment($gateway, $payment, $paymentDetails);
        
        return $result;
    }
    
    /**
     * Process payment through specific gateway
     */
    private function processGatewayPayment($gateway, $payment, $paymentDetails)
    {
        switch ($gateway->name) {
            case 'stripe':
                return $this->processStripePayment($gateway, $payment, $paymentDetails);
                
            case 'paypal':
                return $this->processPayPalPayment($gateway, $payment, $paymentDetails);
                
            default:
                return [
                    'success' => false,
                    'message' => 'Payment gateway not implemented',
                    'payment_id' => $payment->id,
                ];
        }
    }
    
    /**
     * Process Stripe payment
     */
    private function processStripePayment($gateway, $payment, $paymentDetails)
    {
        try {
            \Stripe\Stripe::setApiKey($gateway->config['secret_key']);
            
            $intent = \Stripe\PaymentIntent::create([
                'amount' => $payment->amount * 100, // Convert to cents
                'currency' => $payment->currency,
                'payment_method' => $paymentDetails['payment_method_id'] ?? null,
                'confirmation_method' => 'automatic',
                'metadata' => [
                    'payment_id' => $payment->id,
                    'user_id' => $payment->user_id,
                    'course_id' => $payment->course_id,
                ],
            ]);
            
            // Update payment with Stripe intent ID
            $payment->update([
                'gateway_transaction_id' => $intent->id,
                'gateway_response' => $intent->toArray(),
            ]);
            
            return [
                'success' => true,
                'message' => 'Payment initiated successfully',
                'payment_id' => $payment->id,
                'client_secret' => $intent->client_secret,
            ];
            
        } catch (\Exception $e) {
            Log::error('Stripe payment error: ' . $e->getMessage());
            
            $payment->update([
                'status' => 'failed',
                'gateway_response' => ['error' => $e->getMessage()],
            ]);
            
            return [
                'success' => false,
                'message' => 'Payment processing failed',
                'payment_id' => $payment->id,
            ];
        }
    }
    
    /**
     * Process PayPal payment
     */
    private function processPayPalPayment($gateway, $payment, $paymentDetails)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Basic ' . base64_encode($gateway->config['client_id'] . ':' . $gateway->config['client_secret']),
                'Content-Type' => 'application/json',
            ])->post('https://api.paypal.com/v1/payments/payment', [
                'intent' => 'sale',
                'payer' => [
                    'payment_method' => 'paypal',
                ],
                'transactions' => [
                    [
                        'amount' => [
                            'total' => $payment->amount,
                            'currency' => $payment->currency,
                        ],
                        'description' => 'Course enrollment: ' . $payment->course->title,
                        'custom' => $payment->id,
                    ],
                ],
                'redirect_urls' => [
                    'return_url' => route('zenithalms.payments.success', $payment->id),
                    'cancel_url' => route('zenithalms.payments.cancel', $payment->id),
                ],
            ]);
            
            if ($response->successful()) {
                $paypalData = $response->json();
                
                $payment->update([
                    'gateway_transaction_id' => $paypalData['id'],
                    'gateway_response' => $paypalData,
                ]);
                
                return [
                    'success' => true,
                    'message' => 'Payment initiated successfully',
                    'payment_id' => $payment->id,
                    'redirect_url' => $paypalData['links'][1]['href'], // Approval URL
                ];
            } else {
                throw new \Exception('PayPal API error: ' . $response->body());
            }
            
        } catch (\Exception $e) {
            Log::error('PayPal payment error: ' . $e->getMessage());
            
            $payment->update([
                'status' => 'failed',
                'gateway_response' => ['error' => $e->getMessage()],
            ]);
            
            return [
                'success' => false,
                'message' => 'Payment processing failed',
                'payment_id' => $payment->id,
            ];
        }
    }
    
    /**
     * Complete payment (grant access)
     */
    public function completePayment($payment)
    {
        $payment->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);
        
        // Grant course access
        $this->grantCourseAccess($payment->user_id, $payment->course_id);
        
        // Send notification
        $this->sendPaymentNotification($payment, 'success');
        
        // Trigger webhook event
        event(new \App\Events\PaymentCompleted($payment));
        
        return true;
    }
    
    /**
     * Fail payment
     */
    public function failPayment($payment, $reason = null)
    {
        $payment->update([
            'status' => 'failed',
            'failed_at' => now(),
            'gateway_response' => array_merge($payment->gateway_response ?? [], [
                'failure_reason' => $reason,
            ]),
        ]);
        
        // Send notification
        $this->sendPaymentNotification($payment, 'failure');
        
        // Trigger webhook event
        event(new \App\Events\PaymentFailed($payment));
        
        return true;
    }
    
    /**
     * Refund payment
     */
    public function refundPayment($paymentId, $reason = null)
    {
        $payment = Payment::findOrFail($paymentId);
        
        if ($payment->status !== 'completed') {
            return [
                'success' => false,
                'message' => 'Payment cannot be refunded',
            ];
        }
        
        $gateway = $payment->paymentGateway;
        
        if (!$gateway) {
            return [
                'success' => false,
                'message' => 'Payment gateway not found',
            ];
        }
        
        // Process refund based on gateway
        $result = $this->processRefund($gateway, $payment, $reason);
        
        if ($result['success']) {
            // Revoke course access
            $this->revokeCourseAccess($payment->user_id, $payment->course_id);
            
            // Create refund record
            \App\Models\Refund::create([
                'payment_id' => $payment->id,
                'amount' => $payment->amount,
                'reason' => $reason,
                'status' => 'completed',
                'processed_at' => now(),
            ]);
            
            // Send notification
            $this->sendRefundNotification($payment, $reason);
        }
        
        return $result;
    }
    
    /**
     * Process refund through gateway
     */
    private function processRefund($gateway, $payment, $reason)
    {
        switch ($gateway->name) {
            case 'stripe':
                return $this->processStripeRefund($gateway, $payment, $reason);
                
            case 'paypal':
                return $this->processPayPalRefund($gateway, $payment, $reason);
                
            default:
                return [
                    'success' => false,
                    'message' => 'Refund not supported for this gateway',
                ];
        }
    }
    
    /**
     * Process Stripe refund
     */
    private function processStripeRefund($gateway, $payment, $reason)
    {
        try {
            \Stripe\Stripe::setApiKey($gateway->config['secret_key']);
            
            $refund = \Stripe\Refund::create([
                'payment_intent' => $payment->gateway_transaction_id,
                'reason' => 'requested_by_customer',
                'metadata' => [
                    'reason' => $reason,
                ],
            ]);
            
            $payment->update([
                'status' => 'refunded',
                'refunded_at' => now(),
                'gateway_response' => array_merge($payment->gateway_response ?? [], [
                    'refund_id' => $refund->id,
                    'refund_status' => $refund->status,
                ]),
            ]);
            
            return [
                'success' => true,
                'message' => 'Refund processed successfully',
                'refund_id' => $refund->id,
            ];
            
        } catch (\Exception $e) {
            Log::error('Stripe refund error: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Refund processing failed',
            ];
        }
    }
    
    /**
     * Process PayPal refund
     */
    private function processPayPalRefund($gateway, $payment, $reason)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Basic ' . base64_encode($gateway->config['client_id'] . ':' . $gateway->config['client_secret']),
                'Content-Type' => 'application/json',
            ])->post('https://api.paypal.com/v1/payments/sale/' . $payment->gateway_transaction_id . '/refund', [
                'amount' => [
                    'total' => $payment->amount,
                    'currency' => $payment->currency,
                ],
                'reason' => $reason,
            ]);
            
            if ($response->successful()) {
                $refundData = $response->json();
                
                $payment->update([
                    'status' => 'refunded',
                    'refunded_at' => now(),
                    'gateway_response' => array_merge($payment->gateway_response ?? [], [
                        'refund_id' => $refundData['id'],
                        'refund_status' => $refundData['state'],
                    ]),
                ]);
                
                return [
                    'success' => true,
                    'message' => 'Refund processed successfully',
                    'refund_id' => $refundData['id'],
                ];
            } else {
                throw new \Exception('PayPal refund error: ' . $response->body());
            }
            
        } catch (\Exception $e) {
            Log::error('PayPal refund error: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Refund processing failed',
            ];
        }
    }
    
    /**
     * Add funds to wallet
     */
    public function addFundsToWallet($userId, $amount, $paymentMethod, $paymentDetails = [])
    {
        $user = User::findOrFail($userId);
        
        // Create or get wallet
        $wallet = $user->wallet ?? $user->wallets()->create([
            'balance' => 0,
            'currency' => $this->defaultCurrency,
        ]);
        
        // Create payment record
        $payment = Payment::create([
            'user_id' => $user->id,
            'type' => 'wallet_funding',
            'amount' => $amount,
            'currency' => $this->defaultCurrency,
            'status' => 'pending',
            'payment_data' => array_merge([
                'payment_method' => $paymentMethod,
            ], $paymentDetails),
        ]);
        
        // Process payment
        $result = $this->processGatewayPayment($this->getGateway($paymentMethod), $payment, $paymentDetails);
        
        if ($result['success']) {
            // Add funds to wallet
            $wallet->update([
                'balance' => $wallet->balance + $amount,
            ]);
            
            // Create wallet transaction
            $wallet->transactions()->create([
                'type' => 'credit',
                'amount' => $amount,
                'description' => 'Wallet funding',
            ]);
            
            // Complete payment
            $this->completePayment($payment);
        }
        
        return $result;
    }
    
    /**
     * Apply coupon discount
     */
    public function applyCoupon($couponCode, $amount)
    {
        // Simulate coupon validation (in real implementation, check database)
        $coupons = [
            'SAVE10' => ['discount_type' => 'percentage', 'discount_value' => 10],
            'SAVE20' => ['discount_type' => 'percentage', 'discount_value' => 20],
            'FLAT50' => ['discount_type' => 'fixed', 'discount_value' => 50],
        ];
        
        if (!isset($coupons[$couponCode])) {
            return [
                'valid' => false,
                'message' => 'Invalid coupon code',
                'original_amount' => $amount,
                'final_amount' => $amount,
                'discount_amount' => 0,
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
            'final_amount' => $finalAmount,
            'discount_amount' => $discountAmount,
            'discount_type' => $coupon['discount_type'],
            'discount_value' => $coupon['discount_value'],
        ];
    }
    
    /**
     * Helper methods
     */
    private function processFreeEnrollment($user, $course)
    {
        // Grant access immediately for free courses
        $this->grantCourseAccess($user->id, $course->id);
        
        // Create payment record for tracking
        $payment = Payment::create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'type' => 'course_enrollment',
            'amount' => 0,
            'currency' => $this->defaultCurrency,
            'status' => 'completed',
            'completed_at' => now(),
            'payment_data' => [
                'payment_method' => 'free',
            ],
        ]);
        
        // Send notification
        Notification::create([
            'user_id' => $user->id,
            'title' => 'Course Enrolled!',
            'message' => 'You have successfully enrolled in ' . $course->title . '.',
            'type' => 'success',
            'channel' => 'in_app',
            'notification_data' => [
                'action_url' => route('zenithalms.courses.show', $course->slug),
                'action_button' => 'View Course',
            ],
        ]);
        
        return [
            'success' => true,
            'message' => 'Enrolled successfully in free course',
            'payment_id' => $payment->id,
            'amount' => 0,
        ];
    }
    
    private function grantCourseAccess($userId, $courseId)
    {
        $user = User::findOrFail($userId);
        $course = Course::findOrFail($courseId);
        
        // Check if already enrolled
        if (!$user->enrollments()->where('course_id', $courseId)->exists()) {
            $user->enrollments()->create([
                'course_id' => $courseId,
                'status' => 'active',
                'progress_percentage' => 0,
            ]);
        }
    }
    
    private function revokeCourseAccess($userId, $courseId)
    {
        $user = User::findOrFail($userId);
        
        $user->enrollments()->where('course_id', $courseId)->delete();
    }
    
    private function sendPaymentNotification($payment, $status)
    {
        $user = $payment->user;
        
        if ($status === 'success') {
            $title = 'Payment Successful';
            $message = 'Your payment has been processed successfully.';
        } else {
            $title = 'Payment Failed';
            $message = 'Your payment could not be processed. Please try again.';
        }
        
        Notification::create([
            'user_id' => $user->id,
            'title' => $title,
            'message' => $message,
            'type' => $status === 'success' ? 'success' : 'error',
            'channel' => 'in_app',
            'notification_data' => [
                'payment_id' => $payment->id,
                'action_url' => route('zenithalms.payments.index'),
                'action_button' => 'View Payments',
            ],
        ]);
    }
    
    private function sendRefundNotification($payment, $reason)
    {
        $user = $payment->user;
        
        Notification::create([
            'user_id' => $user->id,
            'title' => 'Payment Refunded',
            'message' => 'Your payment has been refunded. Reason: ' . $reason,
            'type' => 'info',
            'channel' => 'in_app',
            'notification_data' => [
                'payment_id' => $payment->id,
                'action_url' => route('zenithalms.payments.index'),
                'action_button' => 'View Payments',
            ],
        ]);
    }
    
    private function getGateway($name)
    {
        return PaymentGateway::where('name', $name)->where('is_active', true)->first();
    }
    
    private function getWalletGatewayId()
    {
        return PaymentGateway::where('name', 'wallet')->first()?->id;
    }
    
    private function initializeGateways()
    {
        return [
            'stripe' => [
                'name' => 'stripe',
                'display_name' => 'Stripe',
                'config' => [
                    'secret_key' => config('services.stripe.secret'),
                    'publishable_key' => config('services.stripe.key'),
                ],
            ],
            'paypal' => [
                'name' => 'paypal',
                'display_name' => 'PayPal',
                'config' => [
                    'client_id' => config('services.paypal.client_id'),
                    'client_secret' => config('services.paypal.secret'),
                    'sandbox' => config('services.paypal.sandbox', true),
                ],
            ],
            'wallet' => [
                'name' => 'wallet',
                'display_name' => 'ZenithaLMS Wallet',
                'config' => [],
            ],
        ];
    }
}
