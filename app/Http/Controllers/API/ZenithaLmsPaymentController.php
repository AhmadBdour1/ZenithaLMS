<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\User;
use App\Models\Payment;
use App\Models\Wallet;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ZenithaLmsPaymentController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    /**
     * Get user's payment history
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        $query = $user->payments()->with(['course', 'paymentGateway']);
        
        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        // Filter by type
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        
        // Filter by date range
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('created_at', [
                $request->start_date . ' 00:00:00',
                $request->end_date . ' 23:59:59'
            ]);
        }
        
        $payments = $query->orderBy('created_at', 'desc')->paginate(15);
        
        return response()->json([
            'success' => true,
            'data' => $payments->map(function ($payment) {
                return [
                    'id' => $payment->id,
                    'transaction_id' => $payment->transaction_id,
                    'type' => $payment->type,
                    'amount' => $payment->amount,
                    'currency' => $payment->currency,
                    'status' => $payment->status,
                    'gateway' => $payment->paymentGateway->name ?? 'Unknown',
                    'course' => $payment->course ? [
                        'id' => $payment->course->id,
                        'title' => $payment->course->title,
                        'thumbnail' => $payment->course->getThumbnailUrl(),
                    ] : null,
                    'created_at' => $payment->created_at->format('Y-m-d H:i:s'),
                    'completed_at' => $payment->completed_at?->format('Y-m-d H:i:s'),
                ];
            }),
            'pagination' => [
                'current_page' => $payments->currentPage(),
                'last_page' => $payments->lastPage(),
                'per_page' => $payments->perPage(),
                'total' => $payments->total(),
            ],
        ]);
    }

    /**
     * Get payment details
     */
    public function show($id)
    {
        $user = Auth::user();
        
        $payment = $user->payments()->with(['course', 'paymentGateway'])->findOrFail($id);
        
        return response()->json([
            'success' => true,
            'data' => [
                'payment' => [
                    'id' => $payment->id,
                    'transaction_id' => $payment->transaction_id,
                    'type' => $payment->type,
                    'amount' => $payment->amount,
                    'currency' => $payment->currency,
                    'status' => $payment->status,
                    'gateway' => $payment->paymentGateway->name ?? 'Unknown',
                    'gateway_transaction_id' => $payment->gateway_transaction_id,
                    'gateway_response' => $payment->gateway_response,
                    'course' => $payment->course ? [
                        'id' => $payment->course->id,
                        'title' => $payment->course->title,
                        'thumbnail' => $payment->course->getThumbnailUrl(),
                        'price' => $payment->course->price,
                        'is_free' => $payment->course->is_free,
                    ] : null,
                    'created_at' => $payment->created_at->format('Y-m-d H:i:s'),
                    'completed_at' => $payment->completed_at?->format('Y-m-d H:i:s'),
                    'failed_at' => $payment->failed_at?->format('Y-m-d H:i:s'),
                ],
            ],
        ]);
    }

    /**
     * Get user's wallet balance
     */
    public function wallet()
    {
        $user = Auth::user();
        
        $wallet = $user->wallet ?? $user->wallets()->create([
            'balance' => 0,
            'currency' => 'USD',
        ]);
        
        return response()->json([
            'success' => true,
            'data' => [
                'wallet' => [
                    'id' => $wallet->id,
                    'balance' => $wallet->balance,
                    'currency' => $wallet->currency,
                    'created_at' => $wallet->created_at->format('Y-m-d H:i:s'),
                ],
                'transactions' => $wallet->transactions()
                    ->orderBy('created_at', 'desc')
                    ->take(10)
                    ->get()
                    ->map(function ($transaction) {
                        return [
                            'id' => $transaction->id,
                            'type' => $transaction->type,
                            'amount' => $transaction->amount,
                            'description' => $transaction->description,
                            'created_at' => $transaction->created_at->format('Y-m-d H:i:s'),
                        ];
                    }),
            ],
        ]);
    }

    /**
     * Get wallet transaction history
     */
    public function walletTransactions(Request $request)
    {
        $user = Auth::user();
        
        if (!$user->wallet) {
            return response()->json([
                'success' => false,
                'message' => 'Wallet not found',
            ], 404);
        }
        
        $query = $user->wallet->transactions();
        
        // Filter by type
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        
        // Filter by date range
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('created_at', [
                $request->start_date . ' 00:00:00',
                $request->end_date . ' 23:59:59'
            ]);
        }
        
        $transactions = $query->orderBy('created_at', 'desc')->paginate(20);
        
        return response()->json([
            'success' => true,
            'data' => $transactions->map(function ($transaction) {
                return [
                    'id' => $transaction->id,
                    'type' => $transaction->type,
                    'amount' => $transaction->amount,
                    'description' => $transaction->description,
                    'created_at' => $transaction->created_at->format('Y-m-d H:i:s'),
                ];
            }),
            'pagination' => [
                'current_page' => $transactions->currentPage(),
                'last_page' => $transactions->lastPage(),
                'per_page' => $transactions->perPage(),
                'total' => $transactions->total(),
            ],
        ]);
    }

    /**
     * Add funds to wallet
     */
    public function addFunds(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:1|max:1000',
            'payment_method' => 'required|string',
            'payment_details' => 'required|array',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }
        
        $user = Auth::user();
        
        // Create payment record
        $payment = Payment::create([
            'user_id' => $user->id,
            'type' => 'wallet_funding',
            'amount' => $request->amount,
            'currency' => 'USD',
            'status' => 'pending',
            'payment_data' => [
                'payment_method' => $request->payment_method,
                'payment_details' => $request->payment_details,
            ],
        ]);
        
        // Simulate payment processing
        $this->executePayment($payment);
        
        return response()->json([
            'success' => true,
            'message' => 'Payment initiated successfully',
            'data' => [
                'payment_id' => $payment->id,
                'amount' => $payment->amount,
                'status' => $payment->status,
            ],
        ]);
    }

    /**
     * Process payment for course enrollment
     */
    public function processPayment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'course_id' => 'required|exists:courses,id',
            'payment_method' => 'required|string',
            'payment_details' => 'required|array',
            'coupon_code' => 'nullable|string',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }
        
        $user = Auth::user();
        $course = Course::findOrFail($request->course_id);
        
        // Check if course is free
        if ($course->is_free) {
            return response()->json([
                'success' => false,
                'message' => 'This course is free. No payment required.',
            ], 400);
        }
        
        // Check if already enrolled
        if ($user->enrollments()->where('course_id', $course->id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'You are already enrolled in this course.',
            ], 400);
        }
        
        // Calculate final amount
        $amount = $course->price;
        
        // Apply coupon if provided
        if ($request->filled('coupon_code')) {
            $discount = $this->calculateCouponDiscount($request->coupon_code, $amount);
            $amount = $discount['final_amount'];
        }
        
        // Check wallet balance
        $wallet = $user->wallet;
        if ($wallet && $wallet->balance >= $amount) {
            // Process payment from wallet
            $this->processWalletPayment($user, $wallet, $amount, $course);
            
            return response()->json([
                'success' => true,
                'message' => 'Payment processed successfully using wallet',
                'data' => [
                    'payment_method' => 'wallet',
                    'amount' => $amount,
                    'wallet_balance' => $wallet->balance,
                ],
            ]);
        }
        
        // Create payment record for external payment
        $payment = Payment::create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'type' => 'course_enrollment',
            'amount' => $amount,
            'currency' => 'USD',
            'status' => 'pending',
            'payment_data' => [
                'payment_method' => $request->payment_method,
                'payment_details' => $request->payment_details,
                'coupon_code' => $request->coupon_code,
            ],
        ]);
        
        // Simulate payment processing
        $this->executePayment($payment);
        
        return response()->json([
            'success' => true,
            'message' => 'Payment initiated successfully',
            'data' => [
                'payment_id' => $payment->id,
                'amount' => $amount,
                'status' => $payment->status,
            ],
        ]);
    }

    /**
     * Get available payment gateways
     */
    public function paymentGateways()
    {
        $gateways = [
            [
                'id' => 'stripe',
                'name' => 'Stripe',
                'type' => 'credit_card',
                'enabled' => true,
                'supported_currencies' => ['USD', 'EUR', 'GBP'],
                'fees' => [
                    'percentage' => 2.9,
                    'fixed' => 0.30,
                ],
            ],
            [
                'id' => 'paypal',
                'name' => 'PayPal',
                'type' => 'paypal',
                'enabled' => true,
                'supported_currencies' => ['USD', 'EUR', 'GBP'],
                'fees' => [
                    'percentage' => 3.4,
                    'fixed' => 0.30,
                ],
            ],
            [
                'id' => 'wallet',
                'name' => 'ZenithaLMS Wallet',
                'type' => 'wallet',
                'enabled' => true,
                'supported_currencies' => ['USD'],
                'fees' => [
                    'percentage' => 0,
                    'fixed' => 0,
                ],
            ],
        ];
        
        return response()->json([
            'success' => true,
            'data' => $gateways,
        ]);
    }

    /**
     * Apply coupon discount
     */
    public function applyCoupon(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'coupon_code' => 'required|string',
            'amount' => 'required|numeric|min:0',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }
        
        $discount = $this->calculateCouponDiscount($request->coupon_code, $request->amount);
        
        return response()->json([
            'success' => true,
            'data' => $discount,
        ]);
    }

    /**
     * Get payment methods
     */
    public function paymentMethods()
    {
        $methods = [
            [
                'id' => 'credit_card',
                'name' => 'Credit Card',
                'type' => 'card',
                'enabled' => true,
                'supported_gateways' => ['stripe', 'paypal'],
                'icon' => 'credit-card',
            ],
            [
                'id' => 'debit_card',
                'name' => 'Debit Card',
                'type' => 'card',
                'enabled' => true,
                'supported_gateways' => ['stripe', 'paypal'],
                'icon' => 'debit-card',
            ],
            [
                'id' => 'paypal',
                'name' => 'PayPal',
                'type' => 'paypal',
                'enabled' => true,
                'supported_gateways' => ['paypal'],
                'icon' => 'paypal',
            ],
            [
                'id' => 'wallet',
                'name' => 'ZenithaLMS Wallet',
                'type' => 'wallet',
                'enabled' => true,
                'supported_gateways' => ['wallet'],
                'icon' => 'wallet',
            ],
        ];
        
        return response()->json([
            'success' => true,
            'data' => $methods,
        ]);
    }

    /**
     * Get payment statistics
     */
    public function statistics(Request $request)
    {
        $user = Auth::user();
        
        $startDate = $request->get('start_date', now()->subDays(30)->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));
        
        $payments = $user->payments()
            ->whereBetween('created_at', [
                $startDate . ' 00:00:00',
                $endDate . ' 23:59:59'
            ])
            ->get();
        
        $stats = [
            'total_payments' => $payments->count(),
            'total_amount' => $payments->sum('amount'),
            'successful_payments' => $payments->where('status', 'completed')->count(),
            'failed_payments' => $payments->where('status', 'failed')->count(),
            'pending_payments' => $payments->where('status', 'pending')->count(),
            'average_amount' => $payments->avg('amount'),
            'daily_stats' => $this->getDailyStats($payments),
            'payment_methods' => $this->getPaymentMethodStats($payments),
            'payment_types' => $this->getPaymentTypeStats($payments),
        ];
        
        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Process payment (simulated)
     */
    private function executePayment($payment)
    {
        // Simulate payment processing
        $success = rand(1, 100) > 10; // 90% success rate
        
        if ($success) {
            $payment->update([
                'status' => 'completed',
                'completed_at' => now(),
                'gateway_transaction_id' => 'txn_' . uniqid(),
                'gateway_response' => [
                    'status' => 'success',
                    'message' => 'Payment processed successfully',
                ],
            ]);
            
            // Grant access to course if applicable
            if ($payment->course_id) {
                $this->grantCourseAccess($payment->user_id, $payment->course_id);
            }
            
            // Add funds to wallet if applicable
            if ($payment->type === 'wallet_funding') {
                $this->addFundsToWallet($payment->user_id, $payment->amount);
            }
            
            // Send notification
            $this->sendPaymentNotification($payment);
        } else {
            $payment->update([
                'status' => 'failed',
                'failed_at' => now(),
                'gateway_response' => [
                    'status' => 'failed',
                    'message' => 'Payment processing failed',
                ],
            ]);
        }
    }

    /**
     * Process wallet payment
     */
    private function processWalletPayment($user, $wallet, $amount, $course)
    {
        if ($wallet->balance < $amount) {
            return false;
        }
        
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
        
        // Grant course access
        $this->grantCourseAccess($user->id, $course->id);
        
        // Create payment record
        Payment::create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'type' => 'course_enrollment',
            'amount' => $amount,
            'currency' => 'USD',
            'status' => 'completed',
            'completed_at' => now(),
            'payment_data' => [
                'payment_method' => 'wallet',
                'wallet_balance_before' => $wallet->balance + $amount,
                'wallet_balance_after' => $wallet->balance,
            ],
        ]);
        
        // Send notification
        Notification::create([
            'user_id' => $user->id,
            'title' => 'Payment Successful',
            'message' => 'You have successfully enrolled in ' . $course->title . ' using your ZenithaLMS wallet.',
            'type' => 'success',
            'channel' => 'in_app',
            'notification_data' => [
                'action_url' => route('zenithalms.courses.show', $course->slug),
                'action_button' => 'View Course',
            ],
        ]);
        
        return true;
    }

    /**
     * Add funds to wallet
     */
    private function addFundsToWallet($userId, $amount)
    {
        $user = User::findOrFail($userId);
        $wallet = $user->wallet ?? $user->wallets()->create([
            'balance' => 0,
            'currency' => 'USD',
        ]);
        
        $wallet->update([
            'balance' => $wallet->balance + $amount,
        ]);
        
        $wallet->transactions()->create([
            'type' => 'credit',
            'amount' => $amount,
            'description' => 'Wallet funding',
        ]);
        
        // Send notification
        Notification::create([
            'user_id' => $userId,
            'title' => 'Funds Added',
            'message' => '$' . number_format($amount, 2) . ' has been added to your ZenithaLMS wallet.',
            'type' => 'success',
            'channel' => 'in_app',
            'notification_data' => [
                'action_url' => route('zenithalms.dashboard.wallet'),
                'action_button' => 'View Wallet',
            ],
        ]);
    }

    /**
     * Grant course access
     */
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

    /**
     * Send payment notification
     */
    private function sendPaymentNotification($payment)
    {
        $user = $payment->user;
        
        if ($payment->status === 'completed') {
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
            'type' => $payment->status === 'completed' ? 'success' : 'error',
            'channel' => 'in_app',
            'notification_data' => [
                'payment_id' => $payment->id,
                'action_url' => route('zenithalms.payments.index'),
                'action_button' => 'View Payments',
            ],
        ]);
    }

    /**
     * Apply coupon discount
     */
    private function calculateCouponDiscount($couponCode, $amount)
    {
        // Simulate coupon validation
        $coupons = [
            'SAVE10' => ['discount_type' => 'percentage', 'discount_value' => 10],
            'SAVE20' => ['discount_type' => 'percentage', 'discount_value' => 20],
            'FLAT50' => ['discount_type' => 'fixed', 'discount_value' => 50],
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
     * Get daily statistics
     */
    private function getDailyStats($payments)
    {
        $dailyStats = [];
        
        for ($i = 0; $i < 30; $i++) {
            $date = now()->subDays($i)->format('Y-m-d');
            $dailyPayments = $payments->where('created_at', 'like', $date . '%');
            
            $dailyStats[] = [
                'date' => $date,
                'payments' => $dailyPayments->count(),
                'amount' => $dailyPayments->sum('amount'),
            ];
        }
        
        return array_reverse($dailyStats);
    }

    /**
     * Get payment method statistics
     */
    private function getPaymentMethodStats($payments)
    {
        $methods = $payments->groupBy('payment_data.payment_method');
        
        return $methods->map(function ($payments, $method) {
            return [
                'method' => $method,
                'count' => $payments->count(),
                'amount' => $payments->sum('amount'),
            ];
        });
    }

    /**
     * Get payment type statistics
     */
    private function getPaymentTypeStats($payments)
    {
        $types = $payments->groupBy('type');
        
        return $types->map(function ($payments, $type) {
            return [
                'type' => $type,
                'count' => $payments->count(),
                'amount' => $payments->sum('amount'),
            ];
        });
    }
}
