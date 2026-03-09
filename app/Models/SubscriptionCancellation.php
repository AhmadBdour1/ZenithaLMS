<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SubscriptionCancellation extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'subscription_id',
        'reason',
        'immediate',
        'requested_at',
        'effective_at',
        'processed_at',
        'refund_amount',
        'refund_status', // 'pending', 'processed', 'failed'
        'refund_method',
        'refund_transaction_id',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'requested_at' => 'datetime',
        'effective_at' => 'datetime',
        'processed_at' => 'datetime',
        'refund_amount' => 'decimal:2',
        'immediate' => 'boolean',
        'metadata' => 'array',
    ];

    // Relationships
    public function subscription()
    {
        return $this->belongsTo(Subscription::class);
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->whereNull('processed_at');
    }

    public function scopeProcessed($query)
    {
        return $query->whereNotNull('processed_at');
    }

    public function scopeImmediate($query)
    {
        return $query->where('immediate', true);
    }

    public function scopeScheduled($query)
    {
        return $query->where('immediate', false);
    }

    // Methods
    public function process()
    {
        $this->update([
            'processed_at' => now(),
        ]);

        $subscription = $this->subscription;

        if ($this->immediate) {
            // Immediate cancellation
            $subscription->update([
                'status' => 'canceled',
                'canceled_at' => $this->effective_at,
                'auto_renew' => false,
            ]);

            // Cancel on payment gateway
            $subscription->cancelOnGateway();

            // Process refund if applicable
            if ($this->refund_amount > 0) {
                $this->processRefund();
            }

        } else {
            // Scheduled cancellation
            // The subscription will be canceled automatically on the effective date
            // This is typically handled by a scheduled job
        }

        return true;
    }

    private function processRefund()
    {
        if ($this->refund_amount <= 0) {
            return false;
        }

        $user = $this->subscription->user;

        // Add refund to user's wallet
        $user->increment('wallet_balance', $this->refund_amount);

        // Record refund transaction
        $this->subscription->transactions()->create([
            'amount' => $this->refund_amount,
            'currency' => 'USD',
            'payment_method' => $this->refund_method ?: 'wallet',
            'status' => 'completed',
            'type' => 'refund',
            'description' => "Refund for subscription cancellation",
            'net_amount' => $this->refund_amount,
            'refund_reason' => $this->reason,
            'refunded_amount' => $this->refund_amount,
        ]);

        $this->update([
            'refund_status' => 'processed',
            'refund_transaction_id' => 'REF-' . time(),
        ]);

        return true;
    }

    public function getFormattedRefundAmount()
    {
        return number_format($this->refund_amount, 2);
    }

    public function isPending()
    {
        return is_null($this->processed_at);
    }

    public function isProcessed()
    {
        return !is_null($this->processed_at);
    }

    public function getEffectiveDate()
    {
        return $this->effective_at ? $this->effective_at->format('Y-m-d H:i:s') : null;
    }

    public function getRequestedDate()
    {
        return $this->requested_at->format('Y-m-d H:i:s');
    }

    public function getProcessedDate()
    {
        return $this->processed_at ? $this->processed_at->format('Y-m-d H:i:s') : null;
    }
}
