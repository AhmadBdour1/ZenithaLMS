<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPlan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SubscriptionController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * Get user's subscriptions
     */
    public function index(Request $request)
    {
        $user = $request->user();
        
        $subscriptions = $user->subscriptions()
            ->with(['plan', 'invoices'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json([
            'success' => true,
            'data' => $subscriptions->map(function ($subscription) {
                return [
                    'id' => $subscription->id,
                    'plan' => [
                        'id' => $subscription->plan->id,
                        'name' => $subscription->plan->name,
                        'type' => $subscription->plan->type,
                        'billing_cycle' => $subscription->plan->billing_cycle,
                        'price' => $subscription->plan->price,
                        'features' => $subscription->plan->features,
                        'limits' => [
                            'max_courses' => $subscription->plan->max_courses,
                            'max_students' => $subscription->plan->max_students,
                            'max_storage' => $subscription->plan->max_storage,
                            'max_bandwidth' => $subscription->plan->max_bandwidth,
                            'max_api_calls' => $subscription->plan->max_api_calls,
                        ],
                    ],
                    'status' => $subscription->status,
                    'billing_cycle' => $subscription->billing_cycle,
                    'price' => $subscription->price,
                    'currency' => $subscription->currency,
                    'auto_renew' => $subscription->auto_renew,
                    'starts_at' => $subscription->starts_at?->format('Y-m-d H:i'),
                    'ends_at' => $subscription->ends_at?->format('Y-m-d H:i'),
                    'trial_ends_at' => $subscription->trial_ends_at?->format('Y-m-d H:i'),
                    'last_billed_at' => $subscription->last_billed_at?->format('Y-m-d H:i'),
                    'next_billing_at' => $subscription->next_billing_at?->format('Y-m-d H:i'),
                    'created_at' => $subscription->created_at->format('Y-m-d H:i'),
                    'updated_at' => $subscription->updated_at->format('Y-m-d H:i'),
                    'summary' => $subscription->getSubscriptionSummary(),
                ];
            }),
            'pagination' => [
                'current_page' => $subscriptions->currentPage(),
                'last_page' => $subscriptions->lastPage(),
                'per_page' => $subscriptions->perPage(),
                'total' => $subscriptions->total(),
            ],
        ]);
    }

    /**
     * Get subscription details
     */
    public function show($id)
    {
        $user = request()->user();
        
        $subscription = $user->subscriptions()
            ->with(['plan', 'invoices', 'usage', 'transactions', 'upgrades'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => [
                'subscription' => [
                    'id' => $subscription->id,
                    'plan' => $subscription->plan,
                    'status' => $subscription->status,
                    'billing_cycle' => $subscription->billing_cycle,
                    'price' => $subscription->price,
                    'currency' => $subscription->currency,
                    'payment_method' => $subscription->payment_method,
                    'auto_renew' => $subscription->auto_renew,
                    'starts_at' => $subscription->starts_at?->format('Y-m-d H:i'),
                    'ends_at' => $subscription->ends_at?->format('Y-m-d H:i'),
                    'trial_ends_at' => $subscription->trial_ends_at?->format('Y-m-d H:i'),
                    'last_billed_at' => $subscription->last_billed_at?->format('Y-m-d H:i'),
                    'next_billing_at' => $subscription->next_billing_at?->format('Y-m-d H:i'),
                    'canceled_at' => $subscription->canceled_at?->format('Y-m-d H:i'),
                    'grace_period_ends_at' => $subscription->grace_period_ends_at?->format('Y-m-d H:i'),
                    'created_at' => $subscription->created_at->format('Y-m-d H:i'),
                    'updated_at' => $subscription->updated_at->format('Y-m-d H:i'),
                    'summary' => $subscription->getSubscriptionSummary(),
                ],
                'usage' => $subscription->getUsageStats(),
                'limits' => $subscription->checkLimits(),
                'invoices' => $subscription->invoices->map(function ($invoice) {
                    return [
                        'id' => $invoice->id,
                        'invoice_number' => $invoice->invoice_number,
                        'amount' => $invoice->getFormattedAmount(),
                        'total_amount' => $invoice->getFormattedTotal(),
                        'tax_amount' => $invoice->getFormattedTax(),
                        'status' => $invoice->status,
                        'due_date' => $invoice->due_date?->format('Y-m-d'),
                        'paid_date' => $invoice->paid_date?->format('Y-m-d'),
                        'created_at' => $invoice->created_at->format('Y-m-d H:i'),
                    ];
                }),
                'transactions' => $subscription->transactions->map(function ($transaction) {
                    return [
                        'id' => $transaction->id,
                        'amount' => $transaction->getFormattedAmount(),
                        'net_amount' => $transaction->getFormattedNetAmount(),
                        'fee_amount' => $transaction->getFormattedFeeAmount(),
                        'payment_method' => $transaction->payment_method,
                        'status' => $transaction->status,
                        'type' => $transaction->type,
                        'description' => $transaction->description,
                        'transaction_id' => $transaction->transaction_id,
                        'created_at' => $transaction->created_at->format('Y-m-d H:i'),
                    ];
                }),
                'upgrades' => $subscription->upgrades->map(function ($upgrade) {
                    return [
                        'id' => $upgrade->id,
                        'type' => $upgrade->type,
                        'old_plan' => $upgrade->fromPlan->name,
                        'new_plan' => $upgrade->toPlan->name,
                        'old_price' => $upgrade->getFormattedOldPrice(),
                        'new_price' => $upgrade->getFormattedNewPrice(),
                        'price_difference' => $upgrade->getFormattedPriceDifference(),
                        'proration_credit' => $upgrade->getFormattedProrationCredit(),
                        'status' => $upgrade->status,
                        'effective_at' => $upgrade->effective_at?->format('Y-m-d H:i'),
                        'created_at' => $upgrade->created_at->format('Y-m-d H:i'),
                    ];
                }),
            ],
        ]);
    }

    /**
     * Subscribe to a plan
     */
    public function subscribe(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'plan_id' => 'required|exists:subscription_plans,id',
            'billing_cycle' => 'required|in:monthly,yearly,quarterly,lifetime',
            'payment_method' => 'required|string|in:stripe,paypal,wallet,bank_transfer',
            'payment_details' => 'required|array',
            'start_trial' => 'boolean',
            'coupon_code' => 'nullable|string|max:50',
            'custom_fields' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();
        $plan = SubscriptionPlan::findOrFail($request->plan_id);

        // Check if user already has active subscription
        if ($user->subscriptions()->active()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'You already have an active subscription',
            ], 400);
        }

        // Calculate price based on billing cycle
        $price = $plan->price;
        if ($request->billing_cycle === 'yearly' && $plan->billing_cycle === 'monthly') {
            $price = $plan->getYearlyPrice();
        } elseif ($request->billing_cycle === 'monthly' && $plan->billing_cycle === 'yearly') {
            $price = $plan->getMonthlyPrice();
        }

        // Apply coupon discount if provided
        $discount = 0;
        if ($request->coupon_code) {
            $discount = $this->applyCoupon($request->coupon_code, $price, $plan->id);
            $price -= $discount;
        }

        DB::beginTransaction();

        try {
            // Create subscription
            $subscription = $user->subscriptions()->create([
                'plan_id' => $plan->id,
                'status' => $request->start_trial && $plan->trial_days ? 'trialing' : 'active',
                'starts_at' => now(),
                'ends_at' => $request->billing_cycle === 'lifetime' ? null : now()->addMonth(),
                'trial_ends_at' => $request->start_trial && $plan->trial_days ? now()->addDays($plan->trial_days) : null,
                'billing_cycle' => $request->billing_cycle,
                'price' => $price,
                'currency' => 'USD',
                'payment_method' => $request->payment_method,
                'auto_renew' => $request->billing_cycle !== 'lifetime',
                'next_billing_at' => $request->billing_cycle === 'lifetime' ? null : now()->addMonth(),
                'custom_fields' => $request->custom_fields,
            ]);

            // Process payment
            $transactionId = $this->processPayment($subscription, $request->payment_method, $request->payment_details);

            // Send welcome email
            $this->sendWelcomeEmail($subscription);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Subscription created successfully',
                'data' => [
                    'subscription_id' => $subscription->id,
                    'status' => $subscription->status,
                    'trial_ends_at' => $subscription->trial_ends_at?->format('Y-m-d H:i'),
                    'next_billing_at' => $subscription->next_billing_at?->format('Y-m-d H:i'),
                ],
            ], 201);

        } catch (\Exception $e) {
            DB::rollback();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to create subscription',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Cancel subscription
     */
    public function cancel(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'reason' => 'nullable|string|max:500',
            'immediate' => 'boolean',
            'refund_amount' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();
        $subscription = $user->subscriptions()->findOrFail($id);

        if ($subscription->cancel($request->reason, $request->immediate)) {
            return response()->json([
                'success' => true,
                'message' => 'Subscription canceled successfully',
                'data' => [
                    'status' => $subscription->fresh()->status,
                    'canceled_at' => $subscription->fresh()->canceled_at?->format('Y-m-d H:i'),
                ],
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to cancel subscription',
        ], 500);
    }

    /**
     * Upgrade subscription
     */
    public function upgrade(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'new_plan_id' => 'required|exists:subscription_plans,id',
            'prorate' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();
        $subscription = $user->subscriptions()->findOrFail($id);
        $newPlan = SubscriptionPlan::findOrFail($request->new_plan_id);

        if (!$subscription->isActive()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot upgrade inactive subscription',
            ], 400);
        }

        if ($subscription->upgrade($newPlan, $request->prorate)) {
            return response()->json([
                'success' => true,
                'message' => 'Subscription upgraded successfully',
                'data' => [
                    'new_plan_id' => $newPlan->id,
                    'plan_name' => $newPlan->name,
                    'old_price' => $subscription->getOriginal('price'),
                    'new_price' => $newPlan->price,
                    'proration_credit' => $request->prorate ? $subscription->upgrades()->latest()->first()->proration_credit : 0,
                ],
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to upgrade subscription',
        ], 500);
    }

    /**
     * Downgrade subscription
     */
    public function downgrade(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'new_plan_id' => 'required|exists:subscription_plans,id',
            'effective_date' => 'nullable|date|after:now',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();
        $subscription = $user->subscriptions()->findOrFail($id);
        $newPlan = SubscriptionPlan::findOrFail($request->new_plan_id);

        if (!$subscription->isActive()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot downgrade inactive subscription',
            ], 400);
        }

        if ($subscription->downgrade($newPlan, $request->effective_date)) {
            return response()->json([
                'success' => true,
                'message' => 'Subscription downgrade scheduled successfully',
                'data' => [
                    'new_plan_id' => $newPlan->id,
                    'plan_name' => $newPlan->name,
                    'effective_date' => $request->effective_date?->format('Y-m-d H:i'),
                    'is_downgrade_pending' => true,
                ],
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to downgrade subscription',
        ], 500);
    }

    /**
     * Pause subscription
     */
    public function pause(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'days' => 'nullable|integer|min:1|max:365',
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
        $subscription = $user->subscriptions()->findOrFail($id);

        if (!$subscription->isActive()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot pause inactive subscription',
            ], 400);
        }

        if ($subscription->pause($request->days ?: 30, $request->reason)) {
            return response()->json([
                'success' => true,
                'message' => 'Subscription paused successfully',
                'data' => [
                    'status' => $subscription->fresh()->status,
                    'suspension_reason' => $subscription->fresh()->suspension_reason,
                    'resume_date' => now()->addDays($request->days ?: 30)->format('Y-m-d H:i'),
                ],
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to pause subscription',
        ], 500);
    }

    /**
     * Resume subscription
     */
    public function resume($id)
    {
        $user = request()->user();
        $subscription = $user->subscriptions()->findOrFail($id);

        if ($subscription->resume()) {
            return response()->json([
                'success' => true,
                'message' => 'Subscription resumed successfully',
                'data' => [
                    'status' => $subscription->fresh()->status,
                ],
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to resume subscription',
        ], 500);
    }

    /**
     * Get billing history
     */
    public function billingHistory($id)
    {
        $user = request()->user();
        $subscription = $user->subscriptions()->findOrFail($id);

        $history = $subscription->getBillingHistory();

        return response()->json([
            'success' => true,
            'data' => $history->map(function ($transaction) {
                return [
                    'id' => $transaction->id,
                    'amount' => $transaction->getFormattedAmount(),
                    'net_amount' => $transaction->getFormattedNetAmount(),
                    'payment_method' => $transaction->payment_method,
                    'status' => $transaction->status,
                    'type' => $transaction->type,
                    'description' => $transaction->description,
                    'transaction_id' => $transaction->transaction_id,
                    'created_at' => $transaction->created_at->format('Y-m-d H:i'),
                    'invoice' => $transaction->invoice ? [
                        'id' => $transaction->invoice->id,
                        'invoice_number' => $transaction->invoice->invoice_number,
                        'status' => $transaction->invoice->status,
                        'due_date' => $transaction->invoice->due_date?->format('Y-m-d'),
                    ] : null,
                ];
            }),
        ]);
    }

    /**
     * Get usage statistics
     */
    public function usage($id)
    {
        $user = request()->user();
        $subscription = $user->subscriptions()->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => [
                'usage' => $subscription->getUsageStats(),
                'limits' => $subscription->checkLimits(),
                'near_limits' => [
                    'courses' => $subscription->isNearLimit('courses'),
                    'students' => $subscription->isNearLimit('students'),
                    'storage' => $subscription->isNearLimit('storage'),
                    'bandwidth' => $subscription->isNearLimit('bandwidth'),
                    'api_calls' => $subscription->isNearLimit('api_calls'),
                ],
                'exceeded_limits' => [
                    'courses' => $subscription->hasExceededLimit('courses'),
                    'students' => $subscription->hasExceededLimit('students'),
                    'storage' => $subscription->hasExceededLimit('storage'),
                    'bandwidth' => $subscription->hasExceededLimit('bandwidth'),
                    'api_calls' => $subscription->hasExceededLimit('api_calls'),
                ],
            ],
        ],
        );
    }

    /**
     * Record usage
     */
    public function recordUsage(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'feature' => 'required|string',
            'amount' => 'nullable|integer|min:1',
            'metadata' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = request()->user();
        $subscription = $user->subscriptions()->findOrFail($id);

        if (!$subscription->isActive()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot record usage for inactive subscription',
            ], 400);
        }

        // Check if user has exceeded limit
        if ($subscription->hasExceededLimit($request->feature)) {
            return response()->json([
                'success' => false,
                'message' => 'You have exceeded the limit for this feature',
                'limit' => $subscription->checkLimits()[$request->feature]['limit'] ?? 'unlimited',
            ], 400);
        }

        $usage = $subscription->recordUsage(
            $request->feature,
            $request->amount ?: 1,
            $request->metadata ?? []
        );

        return response()->json([
            'success' => true,
            'message' => 'Usage recorded successfully',
            'data' => [
                'feature' => $usage->feature,
                'amount' => $usage->amount,
                'recorded_at' => $usage->recorded_at->format('Y-m-d H:i'),
            ],
        ]);
    }

    /**
     * Get available plans
     */
    public function plans(Request $request)
    {
        $type = $request->get('type', 'platform');
        
        $plans = SubscriptionPlan::active()
            ->byType($type)
            ->ordered()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $plans->map(function ($plan) {
                return [
                    'id' => $plan->id,
                    'name' => $plan->name,
                    'slug' => $plan->slug,
                    'description' => $plan->description,
                    'type' => $plan->type,
                    'price' => $plan->price,
                    'billing_cycle' => $plan->billing_cycle,
                    'trial_days' => $plan->trial_days,
                    'features' => $plan->features,
                    'limits' => [
                        'max_courses' => $plan->max_courses,
                        'max_students' => $plan->max_students,
                        'max_storage' => $plan->max_storage,
                        'max_bandwidth' => $plan->max_bandwidth,
                        'max_api_calls' => $plan->max_api_calls,
                    ],
                    'capabilities' => [
                        'marketplace_access' => $plan->marketplace_access,
                        'api_access' => $plan->api_access,
                        'white_label' => $plan->white_label,
                        'custom_domain' => $plan->custom_domain,
                        'priority_support' => $plan->priority_support,
                        'advanced_analytics' => $plan->advanced_analytics,
                        'bulk_import' => $plan->bulk_import,
                        'integrations' => $plan->integrations,
                    ],
                    'is_popular' => $plan->is_popular,
                    'is_premium' => $plan->is_premium,
                    'monthly_price' => $plan->getMonthlyPrice(),
                    'yearly_price' => $plan->getYearlyPrice(),
                    'yearly_discount' => $plan->getYearlyDiscount(),
                    'created_at' => $plan->created_at->format('Y-m-d'),
                    'updated_at' => '2024-02-20',
                ];
            }),
        ]);
    }

    /**
     * Get subscription summary
     */
    public function summary()
    {
        $user = request()->user();
        
        $subscription = $user->subscriptions()->active()->first();
        
        if (!$subscription) {
            return response()->json([
                'success' => true,
                'data' => [
                    'has_subscription' => false,
                    'can_trial' => true,
                    'trial_days' => 14,
                ],
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $subscription->getSubscriptionSummary(),
                    'status' => $subscription->status,
                    'starts_at' => $subscription->starts_at->format('Y-m-d'),
                    'ends_at' => $subscription->ends_at?->format('Y-m-d'),
                    'trial_ends_at' => $subscription->trial_ends_at?->format('Y-m-d'),
                    'auto_renew' => $subscription->auto_renew,
                    'billing_cycle' => $subscription->billing_cycle,
                    'price' => $subscription->price,
                    'days_until_expiration' => $subscription->getDaysUntilExpiration(),
                    'days_until_next_billing' => $subscription->getDaysUntilNextBilling(),
                    'trial_days_remaining' => $subscription->getTrialDaysRemaining(),
                    'is_trialing' => $subscription->isTrialing(),
                    'is_active' => $subscription->isActive(),
                    'is_canceled' => $subscription->isCanceled(),
                    'usage_stats' => $subscription->getUsageStats(),
                    'limits' => $subscription->checkLimits(),
                ],
            ],
        ]);
    }

    /**
     * Subscribe to a plan
     */
    public function subscribe(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'plan_id' => 'required|exists:subscription_plans,id',
            'billing_cycle' => 'required|in:monthly,yearly,lifetime',
            'payment_method' => 'required|string',
            'payment_details' => 'required|array',
            'start_trial' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();
        $plan = SubscriptionPlan::findOrFail($request->plan_id);

        // Check if user already has active subscription
        if ($user->subscriptions()->active()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'You already have an active subscription',
            ], 400);
        }

        // Calculate price based on billing cycle
        $price = $plan->price;
        if ($request->billing_cycle === 'yearly' && $plan->billing_cycle === 'monthly') {
            $price = $plan->getYearlyPrice();
        } elseif ($request->billing_cycle === 'monthly' && $plan->billing_cycle === 'yearly') {
            $price = $plan->getMonthlyPrice();
        }

        // Create subscription
        $subscription = $user->subscriptions()->create([
            'plan_id' => $plan->id,
            'status' => $request->start_trial && $plan->trial_days ? 'trialing' : 'active',
            'starts_at' => now(),
            'ends_at' => $request->billing_cycle === 'lifetime' ? null : now()->addMonth(),
            'trial_ends_at' => $request->start_trial && $plan->trial_days ? now()->addDays($plan->trial_days) : null,
            'billing_cycle' => $request->billing_cycle,
            'price' => $price,
            'currency' => 'USD',
            'payment_method' => $request->payment_method,
            'auto_renew' => $request->billing_cycle !== 'lifetime',
            'next_billing_at' => $request->billing_cycle === 'lifetime' ? null : now()->addMonth(),
        ]);

        // Process payment
        $this->processPayment($subscription, $request->payment_details);

        return response()->json([
            'success' => true,
            'message' => 'Subscription created successfully',
            'data' => [
                'subscription_id' => $subscription->id,
                'status' => $subscription->status,
                'trial_ends_at' => $subscription->trial_ends_at?->format('Y-m-d'),
            ],
        ], 201);
    }

    /**
     * Cancel subscription
     */
    public function cancel(Request $request)
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
        $subscription = $user->subscriptions()->active()->first();

        if (!$subscription) {
            return response()->json([
                'success' => false,
                'message' => 'No active subscription found',
            ], 404);
        }

        if ($subscription->cancel($request->reason)) {
            return response()->json([
                'success' => true,
                'message' => 'Subscription canceled successfully',
                'data' => [
                    'status' => $subscription->fresh()->status,
                    'ends_at' => $subscription->ends_at?->format('Y-m-d'),
                ],
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to cancel subscription',
        ], 500);
    }

    /**
     * Upgrade/Change subscription plan
     */
    public function change(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'new_plan_id' => 'required|exists:subscription_plans,id',
            'billing_cycle' => 'required|in:monthly,yearly,lifetime',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();
        $currentSubscription = $user->subscriptions()->active()->first();
        $newPlan = SubscriptionPlan::findOrFail($request->new_plan_id);

        if (!$currentSubscription) {
            return response()->json([
                'success' => false,
                'message' => 'No active subscription found',
            ], 404);
        }

        // Upgrade subscription
        $newSubscription = $currentSubscription->upgrade($newPlan);

        if ($newSubscription) {
            return response()->json([
                'success' => true,
                'message' => 'Subscription upgraded successfully',
                'data' => [
                    'new_subscription_id' => $newSubscription->id,
                    'plan_name' => $newPlan->name,
                ],
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to upgrade subscription',
        ], 500);
    }

    /**
     * Get subscription usage and limits
     */
    public function usage(Request $request)
    {
        $user = $request->user();
        $subscription = $user->subscriptions()->active()->first();

        if (!$subscription) {
            return response()->json([
                'success' => false,
                'message' => 'No active subscription found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'usage_stats' => $subscription->getUsageStats(),
                'limits' => $subscription->checkLimits(),
                'near_limits' => [
                    'courses' => $subscription->isNearLimit('courses'),
                    'students' => $subscription->isNearLimit('students'),
                    'storage' => $subscription->isNearLimit('storage'),
                ],
            ],
        ]);
    }

    /**
     * Get subscription history
     */
    public function history(Request $request)
    {
        $user = $request->user();
        
        $subscriptions = $user->subscriptions()
            ->with('plan')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $subscriptions->map(function ($subscription) {
                return [
                    'id' => $subscription->id,
                    'plan' => [
                        'name' => $subscription->plan->name,
                        'type' => $subscription->plan->type,
                    ],
                    'status' => $subscription->status,
                    'price' => $subscription->price,
                    'billing_cycle' => $subscription->billing_cycle,
                    'starts_at' => $subscription->starts_at->format('Y-m-d'),
                    'ends_at' => $subscription->ends_at?->format('Y-m-d'),
                    'canceled_at' => $subscription->canceled_at?->format('Y-m-d'),
                    'created_at' => $subscription->created_at->format('Y-m-d'),
                ];
            }),
        ]);
    }

    /**
     * Process payment for subscription
     */
    private function processPayment($subscription, $paymentDetails)
    {
        // Implement payment processing logic here
        // This would integrate with Stripe, PayPal, etc.
        
        // For now, just mark as paid
        if ($subscription->status === 'trialing') {
            // Trial doesn't require immediate payment
            return true;
        }

        // Process actual payment based on payment method
        switch ($subscription->payment_method) {
            case 'stripe':
                return $this->processStripePayment($subscription, $paymentDetails);
            case 'paypal':
                return $this->processPayPalPayment($subscription, $paymentDetails);
            case 'wallet':
                return $this->processWalletPayment($subscription, $paymentDetails);
            default:
                return false;
        }
    }

    private function processStripePayment($subscription, $paymentDetails)
    {
        // Implement Stripe payment processing
        return true;
    }

    private function processPayPalPayment($subscription, $paymentDetails)
    {
        // Implement PayPal payment processing
        return true;
    }

    private function processWalletPayment($subscription, $paymentDetails)
    {
        $user = $subscription->user;
        
        if ($user->wallet_balance < $subscription->price) {
            return false;
        }

        $user->decrement('wallet_balance', $subscription->price);
        
        return true;
    }
}
