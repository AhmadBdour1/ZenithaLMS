<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SubscriptionInvoice extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'subscription_id',
        'invoice_number',
        'amount',
        'currency',
        'tax_amount',
        'total_amount',
        'billing_cycle',
        'due_date',
        'paid_date',
        'status', // 'pending', 'paid', 'failed', 'refunded', 'cancelled'
        'payment_method',
        'transaction_id',
        'payment_gateway_id',
        'items',
        'billing_address',
        'customer_email',
        'customer_name',
        'notes',
        'metadata',
        'generated_at',
        'sent_at',
    ];

    protected $casts = [
        'due_date' => 'datetime',
        'paid_date' => 'datetime',
        'generated_at' => 'datetime',
        'sent_at' => 'datetime',
        'amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'items' => 'array',
        'billing_address' => 'array',
        'metadata' => 'array',
    ];

    // Relationships
    public function subscription()
    {
        return $this->belongsTo(Subscription::class);
    }

    public function transactions()
    {
        return $this->hasMany(SubscriptionTransaction::class);
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeRefunded($query)
    {
        return $query->where('status', 'refunded');
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', 'pending')
                    ->where('due_date', '<', now());
    }

    // Methods
    public function generateInvoiceNumber()
    {
        do {
            $number = 'INV-' . date('Y') . '-' . str_pad(static::max('id') + 1, 6, '0', STR_PAD_LEFT);
        } while (static::where('invoice_number', $number)->exists());

        $this->invoice_number = $number;
        $this->save();

        return $number;
    }

    public function markAsPaid($transactionId = null, $paidDate = null)
    {
        $this->update([
            'status' => 'paid',
            'paid_date' => $paidDate ?: now(),
            'transaction_id' => $transactionId ?: $this->transaction_id,
        ]);

        return true;
    }

    public function markAsFailed($reason = null)
    {
        $this->update([
            'status' => 'failed',
            'metadata' => array_merge($this->metadata ?? [], [
                'failed_at' => now()->toISOString(),
                'failure_reason' => $reason,
            ]),
        ]);

        return true;
    }

    public function refund($amount = null, $reason = null)
    {
        $refundAmount = $amount ?: $this->total_amount;
        
        if ($refundAmount > $this->total_amount) {
            return false;
        }

        $this->update([
            'status' => 'refunded',
            'metadata' => array_merge($this->metadata ?? [], [
                'refunded_at' => now()->toISOString(),
                'refund_amount' => $refundAmount,
                'refund_reason' => $reason,
            ]),
        ]);

        return true;
    }

    public function cancel($reason = null)
    {
        $this->update([
            'status' => 'cancelled',
            'metadata' => array_merge($this->metadata ?? [], [
                'cancelled_at' => now()->toISOString(),
                'cancellation_reason' => $reason,
            ]),
        ]);

        return true;
    }

    public function getFormattedAmount()
    {
        return number_format($this->amount, 2) . ' ' . $this->currency;
    }

    public function getFormattedTotal()
    {
        return number_format($this->total_amount, 2) . ' ' . $this->currency;
    }

    public function getFormattedTax()
    {
        return number_format($this->tax_amount, 2) . ' ' . $this->currency;
    }

    public function isOverdue()
    {
        return $this->status === 'pending' && $this->due_date && $this->due_date < now();
    }

    public function getDownloadUrl()
    {
        return route('subscriptions.invoices.download', $this->id);
    }

    public function sendEmail()
    {
        // Implementation for sending invoice email
        $this->update(['sent_at' => now()]);
        
        return true;
    }
}
