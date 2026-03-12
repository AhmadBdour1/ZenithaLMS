<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Refund extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'order_id',
        'payment_id',
        'refund_amount',
        'refund_reason',
        'status',
        'admin_notes',
        'refund_data',
        'requested_at',
        'processed_at',
    ];

    protected $casts = [
        'refund_amount' => 'decimal:2',
        'refund_data' => 'array',
        'requested_at' => 'datetime',
        'processed_at' => 'datetime',
    ];

    /**
     * Get the user that owns the refund.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the order that owns the refund.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the payment that owns the refund.
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    /**
     * Scope a query to only include pending refunds.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope a query to only include approved refunds.
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope a query to only include rejected refunds.
     */
    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    /**
     * Scope a query to only include processed refunds.
     */
    public function scopeProcessed($query)
    {
        return $query->where('status', 'processed');
    }

    /**
     * Check if refund is pending
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if refund is approved
     */
    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    /**
     * Check if refund is rejected
     */
    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    /**
     * Check if refund is processed
     */
    public function isProcessed(): bool
    {
        return $this->status === 'processed';
    }

    /**
     * Approve refund
     */
    public function approve(string $adminNotes = ''): bool
    {
        return $this->update([
            'status' => 'approved',
            'admin_notes' => $adminNotes,
        ]);
    }

    /**
     * Reject refund
     */
    public function reject(string $adminNotes = ''): bool
    {
        return $this->update([
            'status' => 'rejected',
            'admin_notes' => $adminNotes,
        ]);
    }

    /**
     * Process refund
     */
    public function process(string $adminNotes = ''): bool
    {
        return $this->update([
            'status' => 'processed',
            'admin_notes' => $adminNotes,
            'processed_at' => now(),
        ]);
    }

    /**
     * Get formatted refund amount
     */
    public function getFormattedRefundAmountAttribute(): string
    {
        return number_format($this->refund_amount, 2);
    }
}
