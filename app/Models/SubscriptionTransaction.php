<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SubscriptionTransaction extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'subscription_id',
        'invoice_id',
        'amount',
        'currency',
        'payment_method', // 'stripe', 'paypal', 'wallet', 'bank_transfer', 'crypto'
        'transaction_id',
        'gateway_id',
        'status', // 'pending', 'completed', 'failed', 'refunded', 'disputed'
        'type', // 'subscription_payment', 'setup_fee', 'upgrade', 'downgrade', 'refund'
        'description',
        'fee_amount',
        'net_amount',
        'failure_reason',
        'refunded_amount',
        'refund_reason',
        'processed_at',
        'metadata',
    ];

    protected $casts = [
        'processed_at' => 'datetime',
        'amount' => 'decimal:2',
        'fee_amount' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'refunded_amount' => 'decimal:2',
        'metadata' => 'array',
    ];

    // Relationships
    public function subscription()
    {
        return $this->belongsTo(Subscription::class);
    }

    public function invoice()
    {
        return $this->belongsTo(SubscriptionInvoice::class);
    }

    // Scopes
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeRefunded($query)
    {
        return $query->where('status', 'refunded');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByPaymentMethod($query, $method)
    {
        return $query->where('payment_method', $method);
    }

    // Methods
    public function markAsCompleted($gatewayId = null)
    {
        $this->update([
            'status' => 'completed',
            'gateway_id' => $gatewayId ?: $this->gateway_id,
            'processed_at' => now(),
        ]);

        return true;
    }

    public function markAsFailed($reason = null)
    {
        $this->update([
            'status' => 'failed',
            'failure_reason' => $reason,
            'processed_at' => now(),
        ]);

        return true;
    }

    public function refund($amount = null, $reason = null)
    {
        $refundAmount = $amount ?: $this->net_amount;
        
        if ($refundAmount > $this->net_amount) {
            return false;
        }

        $this->update([
            'status' => 'refunded',
            'refunded_amount' => $refundAmount,
            'refund_reason' => $reason,
            'processed_at' => now(),
        ]);

        return true;
    }

    public function getFormattedAmount()
    {
        return number_format($this->amount, 2) . ' ' . $this->currency;
    }

    public function getFormattedNetAmount()
    {
        return number_format($this->net_amount, 2) . ' ' . $this->currency;
    }

    public function getFormattedFeeAmount()
    {
        return number_format($this->fee_amount, 2) . ' ' . $this->currency;
    }

    public function getFormattedRefundedAmount()
    {
        return number_format($this->refunded_amount, 2) . ' . $this->currency;
    }

    public function canBeRefunded()
    {
        return $this->status === 'completed' && $this->refunded_amount < $this->net_amount;
    }

    public function getRefundableAmount()
    {
        return $this->net_amount - $this->refunded_amount;
    }
}
