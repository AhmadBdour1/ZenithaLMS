<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\PaymentGateway;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Course;
use App\Models\Ebook;
use App\Models\Subscription;
use App\Models\Wallet;
use App\Models\Coupon;
use App\Models\CouponUsage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ZenithaLmsPaymentController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display payment page
     */
    public function checkout(Request $request)
    {
        $user = Auth::user();
        
        // ZenithaLMS: Get cart items
        $cartItems = $this->getCartItems();
        
        if ($cartItems->isEmpty()) {
            return redirect()->route('zenithalms.courses.index')
                ->with('error', 'Your cart is empty');
        }

        // ZenithaLMS: Calculate totals
        $subtotal = $cartItems->sum(function ($item) {
            return $item['price'] * $item['quantity'];
        });
        
        $discount = $this->calculateDiscount($subtotal);
        $tax = $this->calculateTax($subtotal - $discount);
        $total = $subtotal - $discount + $tax;

        // ZenithaLMS: Get payment gateways
        $paymentGateways = PaymentGateway::where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        // ZenithaLMS: Get user's wallet
        $wallet = $user->wallet ?? Wallet::createForUser($user->id);

        return view('zenithalms.payment.checkout', compact(
            'cartItems',
            'subtotal',
            'discount',
            'tax',
            'total',
            'paymentGateways',
            'wallet'
        ));
    }

    /**
     * Process payment
     */
    public function processPayment(Request $request)
    {
        $request->validate([
            'payment_gateway' => 'required|exists:payment_gateways,gateway_code',
            'coupon_code' => 'nullable|string',
            'use_wallet' => 'boolean',
        ]);

        $user = Auth::user();
        $cartItems = $this->getCartItems();

        if ($cartItems->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Your cart is empty'
            ], 400);
        }

        // ZenithaLMS: Calculate totals
        $subtotal = $cartItems->sum(function ($item) {
            return $item['price'] * $item['quantity'];
        });
        
        $discount = $this->calculateDiscount($subtotal, $request->coupon_code);
        $tax = $this->calculateTax($subtotal - $discount);
        $total = $subtotal - $discount + $tax;

        // ZenithaLMS: Check wallet balance
        $walletAmount = 0;
        if ($request->boolean('use_wallet')) {
            $wallet = $user->wallet ?? Wallet::createForUser($user->id);
            $walletAmount = min($wallet->balance, $total);
            $total -= $walletAmount;
        }

        // ZenithaLMS: Create order
        $order = Order::create([
            'user_id' => $user->id,
            'order_number' => $this->generateOrderNumber(),
            'total_amount' => $subtotal,
            'discount_amount' => $discount,
            'tax_amount' => $tax,
            'final_amount' => $total + $walletAmount,
            'currency' => 'USD',
            'status' => 'pending',
            'payment_status' => 'pending',
            'payment_gateway_id' => PaymentGateway::where('gateway_code', $request->payment_gateway)->first()->id,
            'order_date' => now(),
        ]);

        // ZenithaLMS: Create order items
        foreach ($cartItems as $item) {
            OrderItem::create([
                'order_id' => $order->id,
                'item_type' => $item['type'],
                'item_id' => $item['id'],
                'item_name' => $item['title'],
                'item_price' => $item['price'],
                'quantity' => $item['quantity'],
                'total_price' => $item['price'] * $item['quantity'],
            ]);
        }

        // ZenithaLMS: Process payment based on gateway
        $paymentResult = $this->processGatewayPayment($order, $request->payment_gateway);

        if ($paymentResult['success']) {
            // ZenithaLMS: Update order status
            $order->update([
                'status' => 'completed',
                'payment_status' => 'paid',
                'transaction_id' => $paymentResult['transaction_id'],
                'paid_at' => now(),
            ]);

            // ZenithaLMS: Use wallet if requested
            if ($walletAmount > 0) {
                $wallet->debit($walletAmount, 'Order payment', $order->id);
            }

            // ZenithaLMS: Apply coupon if used
            if ($request->coupon_code) {
                $this->applyCoupon($request->coupon_code, $order->id);
            }

            // ZenithaLMS: Grant access to purchased items
            $this->grantAccessToItems($order, $user);

            // ZenithaLMS: Clear cart
            $this->clearCart();

            return response()->json([
                'success' => true,
                'message' => 'Payment processed successfully',
                'order_id' => $order->id,
                'redirect_url' => route('zenithalms.payment.success', $order->id)
            ]);
        } else {
            // ZenithaLMS: Update order status
            $order->update([
                'status' => 'failed',
                'payment_status' => 'failed',
            ]);

            return response()->json([
                'success' => false,
                'message' => $paymentResult['message'] ?? 'Payment failed',
                'order_id' => $order->id,
            ], 400);
        }
    }

    /**
     * Display payment success page
     */
    public function success($orderId)
    {
        $user = Auth::user();
        $order = Order::with(['items', 'paymentGateway'])
            ->where('user_id', $user->id)
            ->findOrFail($orderId);

        return view('zenithalms.payment.success', compact('order'));
    }

    /**
     * Display payment failed page
     */
    public function failed($orderId)
    {
        $user = Auth::user();
        $order = Order::where('user_id', $user->id)->findOrFail($orderId);

        return view('zenithalms.payment.failed', compact('order'));
    }

    /**
     * Display payment history
     */
    public function history(Request $request)
    {
        $user = Auth::user();
        
        $query = Payment::with(['course', 'ebook', 'paymentGateway'])
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc');

        // ZenithaLMS: Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // ZenithaLMS: Filter by gateway
        if ($request->filled('gateway')) {
            $query->where('payment_gateway', $request->gateway);
        }

        $payments = $query->paginate(20);

        return view('zenithalms.payment.history', compact('payments'));
    }

    /**
     * Display wallet page
     */
    public function wallet(Request $request)
    {
        $user = Auth::user();
        $wallet = $user->wallet ?? Wallet::createForUser($user->id);
        
        $transactions = $wallet->getTransactionHistory(20);

        return view('zenithalms.payment.wallet', compact('wallet', 'transactions'));
    }

    /**
     * Add funds to wallet
     */
    public function addFunds(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1|max:1000',
            'payment_gateway' => 'required|exists:payment_gateways,gateway_code',
        ]);

        $user = Auth::user();
        $amount = $request->amount;

        // ZenithaLMS: Create payment record for wallet funding
        $payment = Payment::create([
            'user_id' => $user->id,
            'amount' => $amount,
            'currency' => 'USD',
            'status' => 'pending',
            'payment_gateway' => $request->payment_gateway,
            'transaction_id' => 'WALLET_' . Str::random(10),
            'payment_data' => [
                'type' => 'wallet_funding',
                'description' => 'Add funds to wallet',
            ],
        ]);

        // ZenithaLMS: Process payment
        $paymentResult = $this->processGatewayPayment($payment, $request->payment_gateway);

        if ($paymentResult['success']) {
            $payment->update([
                'status' => 'completed',
                'paid_at' => now(),
                'transaction_id' => $paymentResult['transaction_id'],
            ]);

            // ZenithaLMS: Add funds to wallet
            $wallet = $user->wallet ?? Wallet::createForUser($user->id);
            $wallet->credit($amount, 'Wallet funding', $payment->id);

            return response()->json([
                'success' => true,
                'message' => 'Funds added to wallet successfully',
                'new_balance' => $wallet->balance,
            ]);
        } else {
            $payment->update(['status' => 'failed']);

            return response()->json([
                'success' => false,
                'message' => $paymentResult['message'] ?? 'Payment failed',
            ], 400);
        }
    }

    /**
     * Apply coupon
     */
    public function applyCoupon(Request $request)
    {
        $request->validate([
            'coupon_code' => 'required|string',
            'total_amount' => 'required|numeric|min:0',
        ]);

        $coupon = Coupon::where('code', $request->coupon_code)
            ->where('is_active', true)
            ->where('starts_at', '<=', now())
            ->where('expires_at', '>', now())
            ->first();

        if (!$coupon) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid coupon code'
            ], 400);
        }

        $user = Auth::user();
        
        // ZenithaLMS: Check usage limit
        if ($coupon->usage_limit && $coupon->used_count >= $coupon->usage_limit) {
            return response()->json([
                'success' => false,
                'message' => 'Coupon has reached its usage limit'
            ], 400);
        }

        // ZenithaLMS: Check if user already used this coupon
        if (CouponUsage::where('user_id', $user->id)->where('coupon_id', $coupon->id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'You have already used this coupon'
            ], 400);
        }

        // ZenithaLMS: Check minimum amount
        if ($coupon->minimum_amount && $request->total_amount < $coupon->minimum_amount) {
            return response()->json([
                'success' => false,
                'message' => 'Minimum amount for this coupon is $' . $coupon->minimum_amount
            ], 400);
        }

        // ZenithaLMS: Calculate discount
        $discount = $this->calculateCouponDiscount($coupon, $request->total_amount);

        return response()->json([
            'success' => true,
            'discount' => $discount,
            'coupon' => [
                'code' => $coupon->code,
                'discount_type' => $coupon->discount_type,
                'discount_value' => $coupon->discount_value,
            ],
        ]);
    }

    /**
     * ZenithaLMS: Helper methods
     */
    private function getCartItems()
    {
        // ZenithaLMS: Get cart from session
        $cart = session()->get('cart', []);
        
        return collect($cart)->map(function ($item) {
            if ($item['type'] === 'course') {
                $course = Course::find($item['id']);
                return $course ? [
                    'type' => 'course',
                    'id' => $course->id,
                    'title' => $course->title,
                    'price' => $course->is_free ? 0 : $course->price,
                    'quantity' => $item['quantity'] ?? 1,
                ] : null;
            } elseif ($item['type'] === 'ebook') {
                $ebook = Ebook::find($item['id']);
                return $ebook ? [
                    'type' => 'ebook',
                    'id' => $ebook->id,
                    'title' => $ebook->title,
                    'price' => $ebook->is_free ? 0 : $ebook->price,
                    'quantity' => $item['quantity'] ?? 1,
                ] : null;
            }
            
            return null;
        })->filter();
    }

    private function calculateDiscount($subtotal, $couponCode = null)
    {
        $discount = 0;

        if ($couponCode) {
            $coupon = Coupon::where('code', $couponCode)
                ->where('is_active', true)
                ->where('starts_at', '<=', now())
                ->where('expires_at', '>', now())
                ->first();

            if ($coupon) {
                $discount = $this->calculateCouponDiscount($coupon, $subtotal);
            }
        }

        return $discount;
    }

    private function calculateCouponDiscount($coupon, $subtotal)
    {
        if ($coupon->discount_type === 'percentage') {
            return $subtotal * ($coupon->discount_value / 100);
        } else {
            return min($coupon->discount_value, $subtotal);
        }
    }

    private function calculateTax($amount)
    {
        // ZenithaLMS: Calculate tax (10% for example)
        return $amount * 0.10;
    }

    private function generateOrderNumber()
    {
        return 'ZLMS-' . date('Ymd') . '-' . Str::random(8);
    }

    private function processGatewayPayment($order, $gatewayCode)
    {
        // ZenithaLMS: Process payment based on gateway
        $gateway = PaymentGateway::where('gateway_code', $gatewayCode)->first();
        
        if (!$gateway) {
            return ['success' => false, 'message' => 'Invalid payment gateway'];
        }

        // ZenithaLMS: Simulate payment processing
        // In real implementation, this would integrate with actual payment gateways
        try {
            // Simulate payment processing
            $transactionId = 'TXN_' . Str::random(12);
            
            // Simulate success/failure (90% success rate)
            $success = rand(1, 100) <= 90;
            
            if ($success) {
                return [
                    'success' => true,
                    'transaction_id' => $transactionId,
                    'message' => 'Payment processed successfully',
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Payment processing failed',
                ];
            }
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Payment processing error: ' . $e->getMessage(),
            ];
        }
    }

    private function grantAccessToItems($order, $user)
    {
        foreach ($order->items as $item) {
            if ($item->item_type === 'course') {
                // ZenithaLMS: Create enrollment
                \App\Models\Enrollment::firstOrCreate(
                    [
                        'user_id' => $user->id,
                        'course_id' => $item->item_id,
                    ],
                    [
                        'status' => 'active',
                        'enrolled_at' => now(),
                        'progress_percentage' => 0,
                    ]
                );
            } elseif ($item->item_type === 'ebook') {
                // ZenithaLMS: Create access record
                \App\Models\EbookAccess::firstOrCreate(
                    [
                        'user_id' => $user->id,
                        'ebook_id' => $item->item_id,
                    ],
                    [
                        'purchase_type' => 'one_time',
                        'access_until' => null, // Lifetime access
                        'created_at' => now(),
                    ]
                );
            }
        }
    }

    private function applyCoupon($couponCode, $orderId)
    {
        $coupon = Coupon::where('code', $couponCode)->first();
        $user = Auth::user();
        
        if ($coupon) {
            // ZenithaLMS: Create coupon usage record
            CouponUsage::create([
                'user_id' => $user->id,
                'coupon_id' => $coupon->id,
                'order_id' => $orderId,
                'discount_amount' => $this->calculateCouponDiscount($coupon, $order->total_amount),
                'used_at' => now(),
            ]);
            
            // ZenithaLMS: Increment coupon usage count
            $coupon->increment('used_count');
        }
    }

    private function clearCart()
    {
        session()->forget('cart');
    }
}
