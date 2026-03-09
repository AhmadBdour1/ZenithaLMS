<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Subscription extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'plan_id',
        'status', // 'active', 'canceled', 'expired', 'past_due', 'trialing', 'suspended'
        'starts_at',
        'ends_at',
        'trial_ends_at',
        'canceled_at',
        'billing_cycle', // 'monthly', 'yearly', 'quarterly', 'lifetime'
        'price',
        'currency',
        'payment_method', // 'stripe', 'paypal', 'wallet', 'bank_transfer'
        'payment_gateway_id',
        'auto_renew',
        'last_billed_at',
        'next_billing_at',
        'grace_period_ends_at',
        'usage_stats',
        'metadata',
        'notes',
        'cancellation_reason',
        'suspension_reason',
        'upgrade_from_id',
        'downgrade_from_id',
        'is_downgrade_pending',
        'is_upgrade_pending',
        'pending_plan_id',
        'pending_change_date',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'trial_ends_at' => 'datetime',
        'canceled_at' => 'datetime',
        'last_billed_at' => 'datetime',
        'next_billing_at' => 'datetime',
        'grace_period_ends_at' => 'datetime',
        'price' => 'decimal:2',
        'auto_renew' => 'boolean',
        'usage_stats' => 'array',
        'metadata' => 'array',
        'is_downgrade_pending' => 'boolean',
        'is_upgrade_pending' => 'boolean',
        'pending_change_date' => 'datetime',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function plan()
    {
        return $this->belongsTo(SubscriptionPlan::class);
    }

    public function invoices()
    {
        return $this->hasMany(SubscriptionInvoice::class);
    }

    public function usage()
    {
        return $this->hasMany(SubscriptionUsage::class);
    }

    public function transactions()
    {
        return $this->hasMany(SubscriptionTransaction::class);
    }

    public function upgrades()
    {
        return $this->hasMany(SubscriptionUpgrade::class);
    }

    public function cancellations()
    {
        return $this->hasMany(SubscriptionCancellation::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeTrial($query)
    {
        return $query->where('status', 'trialing');
    }

    public function scopeExpired($query)
    {
        return $query->where('status', 'expired');
    }

    public function scopeCanceled($query)
    {
        return $query->where('status', 'canceled');
    }

    public function scopePastDue($query)
    {
        return $query->where('status', 'past_due');
    }

    public function scopeSuspended($query)
    {
        return $query->where('status', 'suspended');
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByPlan($query, $planId)
    {
        return $query->where('plan_id', $planId);
    }

    public function scopeAutoRenewing($query)
    {
        return $query->where('auto_renew', true);
    }

    public function scopeExpiringSoon($query, $days = 7)
    {
        return $query->where('ends_at', '<=', now()->addDays($days))
                    ->where('ends_at', '>', now())
                    ->where('auto_renew', false);
    }

    // Methods
    public function isActive()
    {
        return $this->status === 'active' && 
               (!$this->ends_at || $this->ends_at > now());
    }

    public function isTrialing()
    {
        return $this->status === 'trialing' && 
               $this->trial_ends_at && 
               $this->trial_ends_at > now();
    }

    public function isExpired()
    {
        return $this->status === 'expired' || 
               ($this->ends_at && $this->ends_at < now());
    }

    public function isCanceled()
    {
        return $this->status === 'canceled' || 
               $this->canceled_at !== null;
    }

    public function isPastDue()
    {
        return $this->status === 'past_due';
    }

    public function isSuspended()
    {
        return $this->status === 'suspended';
    }

    public function isInGracePeriod()
    {
        return $this->grace_period_ends_at && 
               $this->grace_period_ends_at > now();
    }

    public function canAccessFeature($feature)
    {
        if (!$this->isActive()) {
            return false;
        }

        return $this->plan->hasFeature($feature);
    }

    public function getDaysUntilExpiration()
    {
        if (!$this->ends_at) {
            return null; // Lifetime
        }

        return max(0, now()->diffInDays($this->ends_at));
    }

    public function getDaysUntilNextBilling()
    {
        if (!$this->next_billing_at) {
            return null;
        }

        return max(0, now()->diffInDays($this->next_billing_at));
    }

    public function getTrialDaysRemaining()
    {
        if (!$this->isTrialing()) {
            return 0;
        }

        return max(0, now()->diffInDays($this->trial_ends_at));
    }

    public function getUsageStats()
    {
        $stats = [
            'courses_created' => $this->usage()->where('feature', 'courses_created')->sum('amount'),
            'students_enrolled' => $this->usage()->where('feature', 'students_enrolled')->sum('amount'),
            'storage_used' => $this->usage()->where('feature', 'storage_used')->sum('amount'),
            'bandwidth_used' => $this->usage()->where('feature', 'bandwidth_used')->sum('amount'),
            'api_calls' => $this->usage()->where('feature', 'api_calls')->sum('amount'),
            'page_views' => $this->usage()->where('feature', 'page_views')->sum('amount'),
            'video_hours' => $this->usage()->where('feature', 'video_hours')->sum('amount'),
            'support_tickets' => $this->usage()->where('feature', 'support_tickets')->sum('amount'),
        ];

        return $stats;
    }

    public function checkLimits()
    {
        $limits = [];
        $usage = $this->getUsageStats();

        if ($this->plan->max_courses) {
            $limits['courses'] = [
                'used' => $usage['courses_created'],
                'limit' => $this->plan->max_courses,
                'percentage' => ($usage['courses_created'] / $this->plan->max_courses) * 100,
                'remaining' => max(0, $this->plan->max_courses - $usage['courses_created']),
            ];
        }

        if ($this->plan->max_students) {
            $limits['students'] = [
                'used' => $usage['students_enrolled'],
                'limit' => $this->plan->max_students,
                'percentage' => ($usage['students_enrolled'] / $this->plan->max_students) * 100,
                'remaining' => max(0, $this->plan->max_students - $usage['students_enrolled']),
            ];
        }

        if ($this->plan->max_storage) {
            $limits['storage'] = [
                'used' => $usage['storage_used'],
                'limit' => $this->plan->max_storage,
                'percentage' => ($usage['storage_used'] / $this->plan->max_storage) * 100,
                'remaining' => max(0, $this->plan->max_storage - $usage['storage_used']),
            ];
        }

        if ($this->plan->max_bandwidth) {
            $limits['bandwidth'] = [
                'used' => $usage['bandwidth_used'],
                'limit' => $this->plan->max_bandwidth,
                'percentage' => ($usage['bandwidth_used'] / $this->plan->max_bandwidth) * 100,
                'remaining' => max(0, $this->plan->max_bandwidth - $usage['bandwidth_used']),
            ];
        }

        if ($this->plan->max_api_calls) {
            $limits['api_calls'] = [
                'used' => $usage['api_calls'],
                'limit' => $this->plan->max_api_calls,
                'percentage' => ($usage['api_calls'] / $this->plan->max_api_calls) * 100,
                'remaining' => max(0, $this->plan->max_api_calls - $usage['api_calls']),
            ];
        }

        return $limits;
    }

    public function isNearLimit($feature, $threshold = 80)
    {
        $limits = $this->checkLimits();
        
        if (!isset($limits[$feature])) {
            return false;
        }

        return $limits[$feature]['percentage'] >= $threshold;
    }

    public function hasExceededLimit($feature)
    {
        $limits = $this->checkLimits();
        
        if (!isset($limits[$feature])) {
            return false;
        }

        return $limits[$feature]['percentage'] >= 100;
    }

    public function recordUsage($feature, $amount = 1, $metadata = [])
    {
        return $this->usage()->create([
            'feature' => $feature,
            'amount' => $amount,
            'recorded_at' => now(),
            'metadata' => $metadata,
        ]);
    }

    public function cancel($reason = null, $immediate = false)
    {
        if ($this->isCanceled()) {
            return false;
        }

        $this->update([
            'status' => $immediate ? 'canceled' : 'active',
            'canceled_at' => $immediate ? now() : null,
            'auto_renew' => false,
            'cancellation_reason' => $reason,
            'metadata' => array_merge($this->metadata ?? [], [
                'cancellation_reason' => $reason,
                'canceled_at' => now()->toISOString(),
                'immediate' => $immediate,
            ]),
        ]);

        // Create cancellation record
        $this->cancellations()->create([
            'reason' => $reason,
            'immediate' => $immediate,
            'requested_at' => now(),
            'effective_at' => $immediate ? now() : $this->ends_at,
        ]);

        // Cancel on payment gateway if immediate
        if ($immediate && $this->payment_gateway_id) {
            $this->cancelOnGateway();
        }

        return true;
    }

    public function renew()
    {
        if (!$this->isCanceled()) {
            return false;
        }

        $this->update([
            'status' => 'active',
            'canceled_at' => null,
            'auto_renew' => true,
            'cancellation_reason' => null,
        ]);

        return true;
    }

    public function suspend($reason = null)
    {
        $this->update([
            'status' => 'suspended',
            'suspension_reason' => $reason,
            'metadata' => array_merge($this->metadata ?? [], [
                'suspension_reason' => $reason,
                'suspended_at' => now()->toISOString(),
            ]),
        ]);

        return true;
    }

    public function unsuspend()
    {
        if ($this->status !== 'suspended') {
            return false;
        }

        $this->update([
            'status' => 'active',
            'suspension_reason' => null,
        ]);

        return true;
    }

    public function upgrade(SubscriptionPlan $newPlan, $prorate = true)
    {
        if (!$this->isActive()) {
            return false;
        }

        // Calculate proration
        $credit = 0;
        if ($prorate) {
            $remainingDays = $this->getDaysUntilExpiration();
            $dailyRate = $this->price / 30; // Approximate
            $credit = $remainingDays * $dailyRate;
        }

        // Create upgrade record
        $upgrade = $this->upgrades()->create([
            'from_plan_id' => $this->plan_id,
            'to_plan_id' => $newPlan->id,
            'old_price' => $this->price,
            'new_price' => $newPlan->price,
            'proration_credit' => $credit,
            'effective_at' => now(),
        ]);

        // Update subscription
        $this->update([
            'plan_id' => $newPlan->id,
            'price' => $newPlan->price,
            'billing_cycle' => $newPlan->billing_cycle,
            'next_billing_at' => now()->addDays($newPlan->getBillingCycleDays()),
            'metadata' => array_merge($this->metadata ?? [], [
                'upgraded_at' => now()->toISOString(),
                'upgrade_from_plan' => $this->plan->name,
                'upgrade_to_plan' => $newPlan->name,
            ]),
        ]);

        return $upgrade;
    }

    public function downgrade(SubscriptionPlan $newPlan, $effectiveDate = null)
    {
        if (!$this->isActive()) {
            return false;
        }

        $effectiveDate = $effectiveDate ?: $this->next_billing_at;

        // Create downgrade record
        $downgrade = $this->upgrades()->create([
            'from_plan_id' => $this->plan_id,
            'to_plan_id' => $newPlan->id,
            'old_price' => $this->price,
            'new_price' => $newPlan->price,
            'effective_at' => $effectiveDate,
            'type' => 'downgrade',
        ]);

        // Schedule downgrade
        $this->update([
            'is_downgrade_pending' => true,
            'pending_plan_id' => $newPlan->id,
            'pending_change_date' => $effectiveDate,
        ]);

        return $downgrade;
    }

    public function processPendingChange()
    {
        if (!$this->is_downgrade_pending || !$this->pending_change_date) {
            return false;
        }

        if ($this->pending_change_date > now()) {
            return false;
        }

        $newPlan = SubscriptionPlan::find($this->pending_plan_id);
        if (!$newPlan) {
            return false;
        }

        // Apply the change
        $this->update([
            'plan_id' => $newPlan->id,
            'price' => $newPlan->price,
            'billing_cycle' => $newPlan->billing_cycle,
            'is_downgrade_pending' => false,
            'pending_plan_id' => null,
            'pending_change_date' => null,
            'metadata' => array_merge($this->metadata ?? [], [
                'downgraded_at' => now()->toISOString(),
                'downgrade_from_plan' => $this->plan->name,
                'downgrade_to_plan' => $newPlan->name,
            ]),
        ]);

        return true;
    }

    public function extend($days = 30)
    {
        $newEndDate = $this->ends_at ? $this->ends_at->addDays($days) : now()->addDays($days);
        
        $this->update([
            'ends_at' => $newEndDate,
            'next_billing_at' => $newEndDate,
        ]);

        return $newEndDate;
    }

    public function pause($days = 30)
    {
        if (!$this->isActive()) {
            return false;
        }

        $this->update([
            'status' => 'suspended',
            'suspension_reason' => 'paused',
            'metadata' => array_merge($this->metadata ?? [], [
                'paused_at' => now()->toISOString(),
                'pause_duration' => $days,
                'resume_date' => now()->addDays($days)->toISOString(),
            ]),
        ]);

        // Schedule resume
        // This would typically be handled by a job

        return true;
    }

    public function resume()
    {
        if ($this->status !== 'suspended') {
            return false;
        }

        $this->update([
            'status' => 'active',
            'suspension_reason' => null,
        ]);

        return true;
    }

    public function generateInvoice()
    {
        return $this->invoices()->create([
            'amount' => $this->price,
            'currency' => $this->currency,
            'billing_cycle' => $this->billing_cycle,
            'due_date' => $this->next_billing_at,
            'status' => 'pending',
            'items' => [
                [
                    'description' => $this->plan->name . ' - ' . ucfirst($this->billing_cycle),
                    'quantity' => 1,
                    'unit_price' => $this->price,
                    'total' => $this->price,
                ]
            ],
        ]);
    }

    public function processPayment($paymentMethod, $transactionId = null)
    {
        // Create transaction record
        $transaction = $this->transactions()->create([
            'amount' => $this->price,
            'currency' => $this->currency,
            'payment_method' => $paymentMethod,
            'transaction_id' => $transactionId,
            'status' => 'completed',
            'type' => 'subscription_payment',
        ]);

        // Update billing info
        $this->update([
            'last_billed_at' => now(),
            'next_billing_at' => $this->calculateNextBillingDate(),
            'payment_method' => $paymentMethod,
            'payment_gateway_id' => $transactionId,
        ]);

        // Generate invoice
        $this->generateInvoice();

        // Extend subscription if not lifetime
        if ($this->ends_at) {
            $this->extend($this->plan->getBillingCycleDays());
        }

        return $transaction;
    }

    private function calculateNextBillingDate()
    {
        if ($this->billing_cycle === 'lifetime') {
            return null;
        }

        $interval = match($this->billing_cycle) {
            'monthly' => '30 days',
            'quarterly' => '90 days',
            'yearly' => '365 days',
            default => '30 days'
        };

        return now()->add($interval);
    }

    public function cancelOnGateway()
    {
        // Implementation depends on payment gateway
        // This would cancel the subscription on Stripe, PayPal, etc.
        
        switch ($this->payment_method) {
            case 'stripe':
                return $this->cancelStripeSubscription();
            case 'paypal':
                return $this->cancelPayPalSubscription();
            default:
                return true;
        }
    }

    private function cancelStripeSubscription()
    {
        if (!$this->payment_gateway_id) {
            return false;
        }

        try {
            $stripe = new \Stripe\StripeClient(config('services.stripe.secret'));
            $stripe->subscriptions->cancel($this->payment_gateway_id);
            return true;
        } catch (\Exception $e) {
            \Log::error('Failed to cancel Stripe subscription', [
                'subscription_id' => $this->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    private function cancelPayPalSubscription()
    {
        // Implement PayPal subscription cancellation
        return true;
    }

    public function getBillingHistory()
    {
        return $this->transactions()
            ->with('invoice')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getNextPaymentAmount()
    {
        if ($this->billing_cycle === 'lifetime') {
            return 0;
        }

        return $this->price;
    }

    public function getSubscriptionSummary()
    {
        return [
            'status' => $this->status,
            'plan' => [
                'id' => $this->plan->id,
                'name' => $this->plan->name,
                'type' => $this->plan->type,
                'billing_cycle' => $this->billing_cycle,
                'price' => $this->price,
                'currency' => $this->currency,
            ],
            'dates' => [
                'starts_at' => $this->starts_at?->format('Y-m-d H:i'),
                'ends_at' => $this->ends_at?->format('Y-m-d H:i'),
                'trial_ends_at' => $this->trial_ends_at?->format('Y-m-d H:i'),
                'last_billed_at' => $this->last_billed_at?->format('Y-m-d H:i'),
                'next_billing_at' => $this->next_billing_at?->format('Y-m-d H:i'),
                'canceled_at' => $this->canceled_at?->format('Y-m-d H:i'),
            ],
            'status_details' => [
                'is_active' => $this->isActive(),
                'is_trialing' => $this->isTrialing(),
                'is_expired' => $this->isExpired(),
                'is_canceled' => $this->isCanceled(),
                'is_past_due' => $this->isPastDue(),
                'is_suspended' => $this->isSuspended(),
                'is_in_grace_period' => $this->isInGracePeriod(),
                'auto_renew' => $this->auto_renew,
            ],
            'time_remaining' => [
                'days_until_expiration' => $this->getDaysUntilExpiration(),
                'days_until_next_billing' => $this->getDaysUntilNextBilling(),
                'trial_days_remaining' => $this->getTrialDaysRemaining(),
            ],
            'usage' => $this->getUsageStats(),
            'limits' => $this->checkLimits(),
            'next_payment' => [
                'amount' => $this->getNextPaymentAmount(),
                'date' => $this->next_billing_at?->format('Y-m-d'),
            ],
        ];
    }
}
        'price',
        'currency',
        'payment_method',
        'stripe_subscription_id',
        'paypal_subscription_id',
        'auto_renew',
        'last_billed_at',
        'next_billing_at',
        'usage_stats',
        'metadata',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'trial_ends_at' => 'datetime',
        'canceled_at' => 'datetime',
        'price' => 'decimal:2',
        'auto_renew' => 'boolean',
        'last_billed_at' => 'datetime',
        'next_billing_at' => 'datetime',
        'usage_stats' => 'array',
        'metadata' => 'array',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function plan()
    {
        return $this->belongsTo(SubscriptionPlan::class);
    }

    public function invoices()
    {
        return $this->hasMany(SubscriptionInvoice::class);
    }

    public function usage()
    {
        return $this->hasMany(SubscriptionUsage::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeTrial($query)
    {
        return $query->where('status', 'trialing');
    }

    public function scopeExpired($query)
    {
        return $query->where('status', 'expired');
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    // Methods
    public function isActive()
    {
        return $this->status === 'active' && 
               (!$this->ends_at || $this->ends_at > now());
    }

    public function isTrialing()
    {
        return $this->status === 'trialing' && 
               $this->trial_ends_at && 
               $this->trial_ends_at > now();
    }

    public function isExpired()
    {
        return $this->status === 'expired' || 
               ($this->ends_at && $this->ends_at < now());
    }

    public function isCanceled()
    {
        return $this->status === 'canceled' || 
               $this->canceled_at !== null;
    }

    public function canAccessFeature($feature)
    {
        if (!$this->isActive()) {
            return false;
        }

        return $this->plan->hasFeature($feature);
    }

    public function getDaysUntilExpiration()
    {
        if (!$this->ends_at) {
            return null; // Lifetime
        }

        return max(0, now()->diffInDays($this->ends_at));
    }

    public function getDaysUntilNextBilling()
    {
        if (!$this->next_billing_at) {
            return null;
        }

        return max(0, now()->diffInDays($this->next_billing_at));
    }

    public function getTrialDaysRemaining()
    {
        if (!$this->isTrialing()) {
            return 0;
        }

        return max(0, now()->diffInDays($this->trial_ends_at));
    }

    public function cancel($reason = null)
    {
        if ($this->isCanceled()) {
            return false;
        }

        $this->update([
            'status' => 'canceled',
            'canceled_at' => now(),
            'auto_renew' => false,
            'metadata' => array_merge($this->metadata ?? [], [
                'cancellation_reason' => $reason,
                'canceled_at' => now()->toISOString(),
            ]),
        ]);

        // Cancel on Stripe if applicable
        if ($this->stripe_subscription_id) {
            try {
                $stripe = new \Stripe\StripeClient(config('services.stripe.secret'));
                $stripe->subscriptions->cancel($this->stripe_subscription_id);
            } catch (\Exception $e) {
                \Log::error('Failed to cancel Stripe subscription', [
                    'subscription_id' => $this->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return true;
    }

    public function renew()
    {
        if (!$this->isCanceled()) {
            return false;
        }

        $this->update([
            'status' => 'active',
            'canceled_at' => null,
            'auto_renew' => true,
        ]);

        return true;
    }

    public function upgrade(SubscriptionPlan $newPlan)
    {
        if (!$this->isActive()) {
            return false;
        }

        // Calculate proration
        $remainingDays = $this->getDaysUntilExpiration();
        $dailyRate = $this->price / 30; // Approximate
        $credit = $remainingDays * $dailyRate;

        // Create new subscription
        $newSubscription = $this->user->subscriptions()->create([
            'plan_id' => $newPlan->id,
            'status' => 'active',
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
            'billing_cycle' => $newPlan->billing_cycle,
            'price' => $newPlan->price,
            'currency' => 'USD',
            'auto_renew' => true,
            'next_billing_at' => now()->addMonth(),
        ]);

        // Cancel old subscription
        $this->cancel('upgraded');

        return $newSubscription;
    }

    public function recordUsage($feature, $amount = 1)
    {
        return $this->usage()->create([
            'feature' => $feature,
            'amount' => $amount,
            'recorded_at' => now(),
        ]);
    }

    public function getUsageStats()
    {
        $stats = [
            'courses_created' => $this->usage()->where('feature', 'courses_created')->sum('amount'),
            'students_enrolled' => $this->usage()->where('feature', 'students_enrolled')->sum('amount'),
            'storage_used' => $this->usage()->where('feature', 'storage_used')->sum('amount'),
            'bandwidth_used' => $this->usage()->where('feature', 'bandwidth_used')->sum('amount'),
            'api_calls' => $this->usage()->where('feature', 'api_calls')->sum('amount'),
        ];

        return $stats;
    }

    public function checkLimits()
    {
        $limits = [];
        $usage = $this->getUsageStats();

        if ($this->plan->max_courses) {
            $limits['courses'] = [
                'used' => $usage['courses_created'],
                'limit' => $this->plan->max_courses,
                'percentage' => ($usage['courses_created'] / $this->plan->max_courses) * 100,
            ];
        }

        if ($this->plan->max_students) {
            $limits['students'] = [
                'used' => $usage['students_enrolled'],
                'limit' => $this->plan->max_students,
                'percentage' => ($usage['students_enrolled'] / $this->plan->max_students) * 100,
            ];
        }

        if ($this->plan->storage_limit) {
            $limits['storage'] = [
                'used' => $usage['storage_used'],
                'limit' => $this->plan->storage_limit,
                'percentage' => ($usage['storage_used'] / $this->plan->storage_limit) * 100,
            ];
        }

        return $limits;
    }

    public function isNearLimit($feature, $threshold = 80)
    {
        $limits = $this->checkLimits();
        
        if (!isset($limits[$feature])) {
            return false;
        }

        return $limits[$feature]['percentage'] >= $threshold;
    }
}
