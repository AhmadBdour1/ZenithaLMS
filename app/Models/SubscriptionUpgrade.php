<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SubscriptionUpgrade extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'subscription_id',
        'from_plan_id',
        'to_plan_id',
        'old_price',
        'new_price',
        'proration_credit',
        'proration_fee',
        'type', // 'upgrade', 'downgrade'
        'status', // 'pending', 'processing', 'completed', 'failed'
        'effective_at',
        'processed_at',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'old_price' => 'decimal:2',
        'new_price' => 'decimal:2',
        'proration_credit' => 'decimal:2',
        'proration_fee' => 'decimal:2',
        'effective_at' => 'datetime',
        'processed_at' => 'datetime',
        'metadata' => 'array',
    ];

    // Relationships
    public function subscription()
    {
        return $this->belongsTo(Subscription::class);
    }

    public function fromPlan()
    {
        return $this->belongsTo(SubscriptionPlan::class, 'from_plan_id');
    }

    public function toPlan()
    {
        return $this->belongsTo(SubscriptionPlan::class, 'to_plan_id');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeProcessing($query)
    {
        return $query->where('status', 'processing');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeUpgrade($query)
    {
        return $query->where('type', 'upgrade');
    }

    public function scopeDowngrade($query)
    {
        return $query->where('type', 'downgrade');
    }

    // Methods
    public function process()
    {
        $this->update([
            'status' => 'processing',
            'processed_at' => now(),
        ]);

        // Process the upgrade/downgrade
        $subscription = $this->subscription;
        $newPlan = $this->toPlan;

        try {
            if ($this->type === 'upgrade') {
                // Apply proration credit
                if ($this->proration_credit > 0) {
                    // Add credit to user's wallet or next invoice
                    $this->applyProrationCredit();
                }

                // Update subscription immediately
                $subscription->update([
                    'plan_id' => $newPlan->id,
                    'price' => $newPlan->price,
                    'billing_cycle' => $newPlan->billing_cycle,
                    'next_billing_at' => now()->addDays($newPlan->getBillingCycleDays()),
                ]);

            } elseif ($this->type === 'downgrade') {
                // Schedule downgrade for next billing cycle
                $subscription->update([
                    'is_downgrade_pending' => true,
                    'pending_plan_id' => $newPlan->id,
                    'pending_change_date' => $subscription->next_billing_at,
                ]);
                $this->update([
                    'effective_at' => $subscription->next_billing_at,
                ]);
            }

            $this->update([
                'status' => 'completed',
                'processed_at' => now(),
            ]);

            return true;

        } catch (\Exception $e) {
            $this->update([
                'status' => 'failed',
                'processed_at' => now(),
                'notes' => $e->getMessage(),
            ]);

            return false;
        }
    }

    private function applyProrationCredit()
    {
        // Apply proration credit to user's wallet or next invoice
        $user = $this->subscription->user;
        
        // Add to wallet
        $user->increment('wallet_balance', $this->proration_credit);

        // Record transaction
        $this->subscription->transactions()->create([
            'amount' => $this->proration_credit,
            'currency' => 'USD',
            'payment_method' => 'wallet',
            'status' => 'completed',
            'type' => 'proration_credit',
            'description' => "Proration credit for {$this->type} from {$this->fromPlan->name} to {$this->toPlan->name}",
            'net_amount' => $this->proration_credit,
        ]);
    }

    public function getFormattedOldPrice()
    {
        return number_format($this->old_price, 2);
    }

    public function getFormattedNewPrice()
    {
        return number_format($this->new_price, 2);
    }

    public function getFormattedProrationCredit()
    {
        return number_format($this->proration_credit, 2);
    }

    public function getFormattedProrationFee()
    {
        return number_format($this->proration_fee, 2);
    }

    public function getPriceDifference()
    {
        return $this->new_price - $this->old_price;
    }

    public function getFormattedPriceDifference()
    {
        return number_format($this->getPriceDifference(), 2);
    }
}
