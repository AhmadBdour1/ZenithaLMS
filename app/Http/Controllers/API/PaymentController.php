<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\PaymentMethod;
use App\Models\Payout;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * Get user's payment methods
     */
    public function getPaymentMethods(Request $request)
    {
        $user = $request->user();
        
        $paymentMethods = $user->paymentMethods()
            ->active()
            ->orderBy('is_default', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $paymentMethods->map(function ($method) {
                return [
                    'id' => $method->id,
                    'type' => $method->type,
                    'provider' => $method->provider,
                    'display_name' => $method->getDisplayName(),
                    'masked_number' => $method->getMaskedNumber(),
                    'brand' => $method->brand,
                    'last_four' => $method->last_four,
                    'expiry_month' => $method->expiry_month,
                    'expiry_year' => $method->expiry_year,
                    'cardholder_name' => $method->cardholder_name,
                    'bank_name' => $method->bank_name,
                    'paypal_email' => $method->paypal_email,
                    'crypto_currency' => $method->crypto_currency,
                    'is_default' => $method->is_default,
                    'is_verified' => $method->is_verified,
                    'is_available' => $method->isAvailable(),
                    'usage_count' => $method->usage_count,
                    'last_used_at' => $method->last_used_at?->format('Y-m-d H:i'),
                    'expires_at' => $method->expires_at?->format('Y-m-d'),
                    'icon' => $method->getPaymentIcon(),
                    'supported_currencies' => $method->getSupportedCurrencies(),
                    'payment_limits' => $method->getPaymentLimits(),
                    'created_at' => $method->created_at->format('Y-m-d H:i'),
                ];
            }),
        ]);
    }

    /**
     * Add new payment method
     */
    public function addPaymentMethod(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|in:card,bank_account,paypal,crypto,apple_pay,google_pay,bank_transfer',
            'provider' => 'required|in:stripe,paypal,wise,coinbase,plaid,adyen,square',
            'payment_token' => 'required_if:type,card,apple_pay,google_pay',
            'card_number' => 'required_if:type,card',
            'expiry_month' => 'required_if:type,card|integer|min:1|max:12',
            'expiry_year' => 'required_if:type,card|integer|min:' . date('Y') . '|max:' . (date('Y') + 10),
            'cvv' => 'required_if:type,card|string|min:3|max:4',
            'cardholder_name' => 'required_if:type,card|string|max:255',
            'bank_account_number' => 'required_if:type,bank_account|string',
            'bank_routing_number' => 'required_if:type,bank_account|string',
            'bank_account_type' => 'required_if:type,bank_account|in:checking,savings',
            'bank_name' => 'required_if:type,bank_account|string|max:255',
            'paypal_email' => 'required_if:type,paypal|email',
            'crypto_address' => 'required_if:type,crypto|string',
            'crypto_currency' => 'required_if:type,crypto|in:BTC,ETH,USDT,USDC,DAI,LTC,BCH',
            'nickname' => 'nullable|string|max:100',
            'billing_address' => 'required|array',
            'billing_address.first_name' => 'required|string|max:255',
            'billing_address.last_name' => 'required|string|max:255',
            'billing_address.address' => 'required|string|max:255',
            'billing_address.city' => 'required|string|max:255',
            'billing_address.state' => 'required|string|max:255',
            'billing_address.postal_code' => 'required|string|max:20',
            'billing_address.country' => 'required|string|size:2',
            'make_default' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();

        DB::beginTransaction();

        try {
            $paymentMethod = $this->createPaymentMethod($request, $user);

            // Verify payment method if needed
            $this->validatePaymentMethod($paymentMethod);

            // Set as default if requested
            if ($request->boolean('make_default')) {
                $paymentMethod->setAsDefault();
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Payment method added successfully',
                'data' => [
                    'id' => $paymentMethod->id,
                    'type' => $paymentMethod->type,
                    'display_name' => $paymentMethod->getDisplayName(),
                    'masked_number' => $paymentMethod->getMaskedNumber(),
                    'is_default' => $paymentMethod->is_default,
                    'is_verified' => $paymentMethod->is_verified,
                ],
            ], 201);

        } catch (\Exception $e) {
            DB::rollback();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to add payment method',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update payment method
     */
    public function updatePaymentMethod(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'nickname' => 'nullable|string|max:100',
            'billing_address' => 'array',
            'make_default' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();
        $paymentMethod = $user->paymentMethods()->findOrFail($id);

        $paymentMethod->update([
            'nickname' => $request->nickname,
            'billing_address' => $request->billing_address ?: $paymentMethod->billing_address,
        ]);

        if ($request->boolean('make_default')) {
            $paymentMethod->setAsDefault();
        }

        return response()->json([
            'success' => true,
            'message' => 'Payment method updated successfully',
            'data' => [
                'id' => $paymentMethod->id,
                'nickname' => $paymentMethod->nickname,
                'is_default' => $paymentMethod->is_default,
            ],
        ]);
    }

    /**
     * Delete payment method
     */
    public function deletePaymentMethod($id)
    {
        $user = request()->user();
        $paymentMethod = $user->paymentMethods()->findOrFail($id);

        if (!$paymentMethod->delete()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete payment method. It may be used in active subscriptions.',
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Payment method deleted successfully',
        ]);
    }

    /**
     * Set default payment method
     */
    public function setDefaultPaymentMethod($id)
    {
        $user = request()->user();
        $paymentMethod = $user->paymentMethods()->findOrFail($id);

        $paymentMethod->setAsDefault();

        return response()->json([
            'success' => true,
            'message' => 'Default payment method updated successfully',
        ]);
    }

    /**
     * Verify payment method
     */
    public function verifyPaymentMethod(Request $request, $id)
    {
        $user = $request->user();
        $paymentMethod = $user->paymentMethods()->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'verification_code' => 'required|string|max:10',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Implement verification logic
        $verificationData = [
            'verified_at' => now()->toISOString(),
            'verification_code' => $request->verification_code,
        ];

        $paymentMethod->verify($verificationData);

        return response()->json([
            'success' => true,
            'message' => 'Payment method verified successfully',
            'data' => [
                'is_verified' => $paymentMethod->fresh()->is_verified,
            ],
        ]);
    }

    /**
     * Get user's payouts
     */
    public function getPayouts(Request $request)
    {
        $user = $request->user();
        
        $payouts = $user->payouts()
            ->with(['paymentMethod'])
            ->orderBy('requested_at', 'desc')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $payouts->map(function ($payout) {
                return [
                    'id' => $payout->id,
                    'reference_number' => $payout->reference_number,
                    'type' => $payout->type,
                    'amount' => $payout->getFormattedAmount(),
                    'net_amount' => $payout->getFormattedNetAmount(),
                    'processing_fee' => $payout->getFormattedProcessingFee(),
                    'tax_amount' => $payout->getFormattedTaxAmount(),
                    'currency' => $payout->currency,
                    'status' => $payout->status,
                    'status_text' => $payout->getStatusText(),
                    'status_color' => $payout->getStatusColor(),
                    'payment_method_type' => $payout->payment_method_type,
                    'payment_method' => $payout->paymentMethod ? [
                        'display_name' => $payout->paymentMethod->getDisplayName(),
                        'icon' => $payout->paymentMethod->getPaymentIcon(),
                    ] : null,
                    'requested_at' => $payout->requested_at->format('Y-m-d H:i'),
                    'processed_at' => $payout->processed_at?->format('Y-m-d H:i'),
                    'completed_at' => $payout->completed_at?->format('Y-m-d H:i'),
                    'estimated_completion_date' => $payout->estimated_completion_date?->format('Y-m-d'),
                    'estimated_arrival_date' => $payout->getEstimatedArrivalDate(),
                    'transaction_id' => $payout->transaction_id,
                    'verification_required' => $payout->verification_required,
                    'verification_status' => $payout->verification_status,
                    'can_be_cancelled' => $payout->canBeCancelled(),
                    'can_be_reversed' => $payout->canBeReversed(),
                    'notes' => $payout->notes,
                ];
            }),
            'pagination' => [
                'current_page' => $payouts->currentPage(),
                'last_page' => $payouts->lastPage(),
                'per_page' => $payouts->perPage(),
                'total' => $payouts->total(),
            ],
        ]);
    }

    /**
     * Request payout
     */
    public function requestPayout(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:10|max:100000',
            'payment_method_id' => 'required|exists:payment_methods,id',
            'payment_method_type' => 'required|in:bank_account,paypal,wise,crypto,check,wire',
            'recipient_info' => 'required|array',
            'notes' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();
        $paymentMethod = $user->paymentMethods()->findOrFail($request->payment_method_id);

        // Check if user has sufficient balance
        if ($user->wallet_balance < $request->amount) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient balance',
                'available_balance' => $user->wallet_balance,
            ], 400);
        }

        DB::beginTransaction();

        try {
            // Create payout
            $payout = $user->payouts()->create([
                'type' => 'vendor_earnings', // Default type
                'amount' => $request->amount,
                'currency' => 'USD',
                'status' => 'pending',
                'payment_method_id' => $request->payment_method_id,
                'payment_method_type' => $request->payment_method_type,
                'recipient_info' => $request->recipient_info,
                'notes' => $request->notes,
                'requested_at' => now(),
            ]);

            // Generate reference number
            $payout->generateReferenceNumber();

            // Calculate fees and taxes
            $payout->calculateProcessingFee();
            $payout->calculateTax();
            $payout->calculateNetAmount();

            // Check if verification is required
            if ($this->requiresVerification($payout)) {
                $payout->requireVerification();
            }

            // Hold funds from wallet
            $user->decrement('wallet_balance', $request->amount);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Payout requested successfully',
                'data' => [
                    'payout_id' => $payout->id,
                    'reference_number' => $payout->reference_number,
                    'amount' => $payout->getFormattedAmount(),
                    'net_amount' => $payout->getFormattedNetAmount(),
                    'processing_fee' => $payout->getFormattedProcessingFee(),
                    'status' => $payout->status,
                    'estimated_completion_date' => $payout->estimated_completion_date?->format('Y-m-d'),
                    'verification_required' => $payout->verification_required,
                ],
            ], 201);

        } catch (\Exception $e) {
            DB::rollback();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to request payout',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Cancel payout
     */
    public function cancelPayout(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'reason' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();
        $payout = $user->payouts()->findOrFail($id);

        if (!$payout->cancel($request->reason)) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot cancel this payout',
            ], 400);
        }

        // Return funds to wallet
        $user->increment('wallet_balance', $payout->amount);

        return response()->json([
            'success' => true,
            'message' => 'Payout cancelled successfully',
            'data' => [
                'status' => $payout->fresh()->status,
                'refunded_amount' => $payout->getFormattedAmount(),
            ],
        ]);
    }

    /**
     * Get payout details
     */
    public function getPayoutDetails($id)
    {
        $user = request()->user();
        $payout = $user->payouts()
            ->with(['paymentMethod', 'transactions'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $payout->id,
                'reference_number' => $payout->reference_number,
                'type' => $payout->type,
                'amount' => $payout->getFormattedAmount(),
                'net_amount' => $payout->getFormattedNetAmount(),
                'processing_fee' => $payout->getFormattedProcessingFee(),
                'tax_amount' => $payout->getFormattedTaxAmount(),
                'currency' => $payout->currency,
                'status' => $payout->status,
                'status_text' => $payout->getStatusText(),
                'status_color' => $payout->getStatusColor(),
                'payment_method' => $payout->paymentMethod,
                'payment_method_type' => $payout->payment_method_type,
                'recipient_info' => $payout->recipient_info,
                'bank_account_details' => $payout->bank_account_details,
                'paypal_email' => $payout->paypal_email,
                'crypto_address' => $payout->crypto_address,
                'crypto_currency' => $payout->crypto_currency,
                'check_address' => $payout->check_address,
                'wire_details' => $payout->wire_details,
                'transaction_id' => $payout->transaction_id,
                'batch_id' => $payout->batch_id,
                'requested_at' => $payout->requested_at->format('Y-m-d H:i'),
                'processed_at' => $payout->processed_at?->format('Y-m-d H:i'),
                'completed_at' => $payout->completed_at?->format('Y-m-d H:i'),
                'estimated_completion_date' => $payout->estimated_completion_date?->format('Y-m-d H:i'),
                'estimated_arrival_date' => $payout->getEstimatedArrivalDate(),
                'verification_required' => $payout->verification_required,
                'verification_status' => $payout->verification_status,
                'verification_documents' => $payout->verification_documents,
                'failure_reason' => $payout->failure_reason,
                'retry_count' => $payout->retry_count,
                'next_retry_at' => $payout->next_retry_at?->format('Y-m-d H:i'),
                'auto_retry_enabled' => $payout->auto_retry_enabled,
                'notes' => $payout->notes,
                'internal_notes' => $payout->internal_notes,
                'metadata' => $payout->metadata,
                'can_be_cancelled' => $payout->canBeCancelled(),
                'can_be_reversed' => $payout->canBeReversed(),
                'transactions' => $payout->transactions->map(function ($transaction) {
                    return [
                        'id' => $transaction->id,
                        'amount' => $transaction->getFormattedAmount(),
                        'status' => $transaction->status,
                        'type' => $transaction->type,
                        'created_at' => $transaction->created_at->format('Y-m-d H:i'),
                    ];
                }),
            ],
        ]);
    }

    /**
     * Get payout summary
     */
    public function getPayoutSummary()
    {
        $user = request()->user();
        
        $summary = [
            'total_earned' => $user->wallet_balance,
            'total_paid' => $user->payouts()->completed()->sum('net_amount'),
            'pending_payouts' => $user->payouts()->pending()->sum('amount'),
            'processing_payouts' => $user->payouts()->processing()->sum('amount'),
            'failed_payouts' => $user->payouts()->failed()->sum('amount'),
            'last_payout' => $user->payouts()->latest()->first(),
            'available_for_payout' => $user->wallet_balance,
            'payout_methods' => $user->paymentMethods()->active()->count(),
            'next_payout_date' => $this->getNextPayoutDate(),
        ];

        return response()->json([
            'success' => true,
            'data' => $summary,
        ]);
    }

    /**
     * Get available payout methods
     */
    public function getPayoutMethods()
    {
        $methods = [
            [
                'type' => 'bank_account',
                'name' => 'Bank Transfer',
                'description' => 'Direct bank transfer to your account',
                'fee_type' => 'percentage',
                'fee_amount' => 2.5,
                'max_fee' => 25.00,
                'min_amount' => 10.00,
                'max_amount' => 100000.00,
                'estimated_time' => '3-5 business days',
                'icon' => 'https://img.icons8.com/color/48/bank.png',
                'supported_countries' => ['US', 'CA', 'GB', 'AU', 'DE', 'FR', 'IT', 'ES', 'NL'],
            ],
            [
                'type' => 'paypal',
                'name' => 'PayPal',
                'description' => 'Instant PayPal transfer',
                'fee_type' => 'percentage_plus_fixed',
                'fee_amount' => 2.9,
                'fixed_fee' => 0.30,
                'min_amount' => 1.00,
                'max_amount' => 10000.00,
                'estimated_time' => 'Instant to 24 hours',
                'icon' => 'https://img.icons8.com/color/48/paypal.png',
                'supported_countries' => ['US', 'CA', 'GB', 'AU', 'DE', 'FR', 'IT', 'ES', 'NL', 'JP'],
            ],
            [
                'type' => 'wise',
                'name' => 'Wise',
                'description' => 'Low-cost international transfer',
                'fee_type' => 'percentage',
                'fee_amount' => 0.5,
                'min_fee' => 0.50,
                'min_amount' => 1.00,
                'max_amount' => 1000000.00,
                'estimated_time' => '1-2 business days',
                'icon' => 'https://img.icons8.com/color/48/wise.png',
                'supported_countries' => ['US', 'CA', 'GB', 'AU', 'DE', 'FR', 'IT', 'ES', 'NL', 'PL', 'SE', 'NO', 'DK'],
            ],
            [
                'type' => 'crypto',
                'name' => 'Cryptocurrency',
                'description' => 'Bitcoin, Ethereum, and stablecoins',
                'fee_type' => 'percentage',
                'fee_amount' => 1.0,
                'min_amount' => 0.0001,
                'max_amount' => 1000.00,
                'estimated_time' => '30 minutes to 1 hour',
                'icon' => 'https://img.icons8.com/color/48/bitcoin.png',
                'supported_countries' => ['Global'],
                'supported_currencies' => ['BTC', 'ETH', 'USDT', 'USDC', 'DAI', 'LTC', 'BCH'],
            ],
            [
                'type' => 'check',
                'name' => 'Check',
                'description' => 'Physical check by mail',
                'fee_type' => 'fixed',
                'fee_amount' => 5.00,
                'min_amount' => 10.00,
                'max_amount' => 10000.00,
                'estimated_time' => '7-10 business days',
                'icon' => 'https://img.icons8.com/color/48/check.png',
                'supported_countries' => ['US', 'CA'],
            ],
            [
                'type' => 'wire',
                'name' => 'Wire Transfer',
                'description' => 'Bank wire transfer',
                'fee_type' => 'fixed',
                'fee_amount' => 15.00,
                'min_amount' => 100.00,
                'max_amount' => 100000.00,
                'estimated_time' => '1-2 business days',
                'icon' => 'https://img.icons8.com/color/48/wire-transfer.png',
                'supported_countries' => ['Global'],
            ],
        ];

        return response()->json([
            'success' => true,
            'data' => $methods,
        ]);
    }

    // Private methods
    private function createPaymentMethod($request, $user)
    {
        $paymentMethodData = [
            'user_id' => $user->id,
            'type' => $request->type,
            'provider' => $request->provider,
            'is_default' => false,
            'is_verified' => false,
            'verification_status' => 'pending',
            'billing_address' => $request->billing_address,
            'nickname' => $request->nickname,
            'country' => $request->billing_address['country'],
            'currency' => 'USD',
        ];

        switch ($request->type) {
            case 'card':
                $paymentMethodData = array_merge($paymentMethodData, $this->processCardPayment($request));
                break;
            case 'bank_account':
                $paymentMethodData = array_merge($paymentMethodData, $this->processBankAccount($request));
                break;
            case 'paypal':
                $paymentMethodData = array_merge($paymentMethodData, $this->processPayPal($request));
                break;
            case 'crypto':
                $paymentMethodData = array_merge($paymentMethodData, $this->processCrypto($request));
                break;
            case 'apple_pay':
                $paymentMethodData = array_merge($paymentMethodData, $this->processApplePay($request));
                break;
            case 'google_pay':
                $paymentMethodData = array_merge($paymentMethodData, $this->processGooglePay($request));
                break;
        }

        return $user->paymentMethods()->create($paymentMethodData);
    }

    private function processCardPayment($request)
    {
        // Process card through Stripe
        $stripe = new \Stripe\StripeClient(config('services.stripe.secret'));
        
        try {
            $paymentMethod = $stripe->paymentMethods->create([
                'type' => 'card',
                'card' => [
                    'number' => $request->card_number,
                    'exp_month' => $request->expiry_month,
                    'exp_year' => $request->expiry_year,
                    'cvc' => $request->cvv,
                ],
                'billing_details' => [
                    'name' => $request->cardholder_name,
                    'address' => [
                        'line1' => $request->billing_address['address'],
                        'city' => $request->billing_address['city'],
                        'state' => $request->billing_address['state'],
                        'postal_code' => $request->billing_address['postal_code'],
                        'country' => $request->billing_address['country'],
                    ],
                ],
            ]);

            return [
                'method_identifier' => $paymentMethod->id,
                'brand' => $paymentMethod->card->brand,
                'last_four' => $paymentMethod->card->last4,
                'expiry_month' => $paymentMethod->card->exp_month,
                'expiry_year' => $paymentMethod->card->exp_year,
                'cardholder_name' => $request->cardholder_name,
            ];

        } catch (\Exception $e) {
            throw new \Exception('Failed to process card: ' . $e->getMessage());
        }
    }

    private function processBankAccount($request)
    {
        // Process bank account through Plaid or similar
        return [
            'method_identifier' => 'bank_' . time(),
            'bank_name' => $request->bank_name,
            'bank_account_type' => $request->bank_account_type,
            'bank_routing_number' => $request->bank_routing_number,
            'bank_account_number' => $request->bank_account_number,
        ];
    }

    private function processPayPal($request)
    {
        return [
            'method_identifier' => 'paypal_' . time(),
            'paypal_email' => $request->paypal_email,
        ];
    }

    private function processCrypto($request)
    {
        return [
            'method_identifier' => 'crypto_' . time(),
            'crypto_address' => $request->crypto_address,
            'crypto_currency' => $request->crypto_currency,
        ];
    }

    private function processApplePay($request)
    {
        return [
            'method_identifier' => 'apple_pay_' . time(),
            'apple_pay_token' => $request->payment_token,
        ];
    }

    private function processGooglePay($request)
    {
        return [
            'method_identifier' => 'google_pay_' . time(),
            'google_pay_token' => $request->payment_token,
        ];
    }

    private function validatePaymentMethod($paymentMethod)
    {
        // Implement verification based on type
        switch ($paymentMethod->type) {
            case 'card':
                // Cards are verified by Stripe during creation
                $paymentMethod->verify(['verified_by' => 'stripe']);
                break;
            case 'bank_account':
                // Bank accounts may require micro-deposits
                $paymentMethod->failVerification('Micro-deposit verification required');
                break;
            default:
                $paymentMethod->verify(['verified_by' => 'auto']);
                break;
        }
    }

    private function requiresVerification($payout)
    {
        // Require verification for large amounts or new users
        if ($payout->amount > 1000) {
            return true;
        }

        if ($payout->user->payouts()->count() === 0) {
            return true;
        }

        return false;
    }

    private function getNextPayoutDate()
    {
        // Calculate next payout date based on schedule
        $nextPayout = now()->next(Carbon::FRIDAY);
        
        if ($nextPayout->isWeekend()) {
            $nextPayout->next(Carbon::MONDAY);
        }
        
        return $nextPayout->format('Y-m-d');
    }
}
