<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'order_number',
        'total_amount',
        'discount_amount',
        'tax_amount',
        'final_amount',
        'currency',
        'status',
        'payment_status',
        'payment_gateway_id',
        'transaction_id',
        'order_data',
        'ai_analysis',
        'order_date',
        'paid_at',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'final_amount' => 'decimal:2',
        'order_data' => 'array',
        'ai_analysis' => 'array',
        'order_date' => 'datetime',
        'paid_at' => 'datetime',
    ];

    /**
     * Get the user that owns the order.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the payment gateway for the order.
     */
    public function paymentGateway(): BelongsTo
    {
        return $this->belongsTo(PaymentGateway::class);
    }

    /**
     * Get the items for the order.
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Get the refunds for the order.
     */
    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class);
    }

    /**
     * Scope a query to only include pending orders.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope a query to only include completed orders.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope a query to only include cancelled orders.
     */
    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    /**
     * Scope a query to only include refunded orders.
     */
    public function scopeRefunded($query)
    {
        return $query->where('status', 'refunded');
    }

    /**
     * Scope a query to only include paid orders.
     */
    public function scopePaid($query)
    {
        return $query->where('payment_status', 'paid');
    }

    /**
     * Scope a query to only include unpaid orders.
     */
    public function scopeUnpaid($query)
    {
        return $query->where('payment_status', 'pending');
    }

    /**
     * Check if order is paid
     */
    public function isPaid(): bool
    {
        return $this->payment_status === 'paid';
    }

    /**
     * Check if order is pending
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if order is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if order is cancelled
     */
    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    /**
     * Check if order is refunded
     */
    public function isRefunded(): bool
    {
        return $this->status === 'refunded';
    }

    /**
     * Mark order as paid
     */
    public function markAsPaid(string $transactionId = null): bool
    {
        return $this->update([
            'payment_status' => 'paid',
            'status' => 'completed',
            'paid_at' => now(),
            'transaction_id' => $transactionId ?? $this->transaction_id,
        ]);
    }

    /**
     * Cancel order
     */
    public function cancel(): bool
    {
        if ($this->isPaid()) {
            return false; // Cannot cancel paid orders
        }

        return $this->update([
            'status' => 'cancelled',
        ]);
    }

    /**
     * Refund order
     */
    public function refund(string $reason = ''): bool
    {
        if (!$this->isPaid()) {
            return false; // Cannot refund unpaid orders
        }

        return $this->update([
            'status' => 'refunded',
        ]);
    }

    /**
     * Get formatted total amount
     */
    public function getFormattedTotalAmountAttribute(): string
    {
        return number_format($this->total_amount, 2);
    }

    /**
     * Get formatted final amount
     */
    public function getFormattedFinalAmountAttribute(): string
    {
        return number_format($this->final_amount, 2);
    }

    /**
     * Get formatted discount amount
     */
    public function getFormattedDiscountAmountAttribute(): string
    {
        return number_format($this->discount_amount, 2);
    }

    /**
     * Get formatted tax amount
     */
    public function getFormattedTaxAmountAttribute(): string
    {
        return number_format($this->tax_amount, 2);
    }

    /**
     * Generate unique order number
     */
    public static function generateOrderNumber(): string
    {
        do {
            $orderNumber = 'ZLMS-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
        } while (self::where('order_number', $orderNumber)->exists());

        return $orderNumber;
    }

    /**
     * Get total items count
     */
    public function getTotalItemsCountAttribute(): int
    {
        return $this->items()->sum('quantity');
    }

    /**
     * Get order items by type
     */
    public function getItemsByType(string $type): HasMany
    {
        return $this->items()->where('item_type', $type);
    }

    /**
     * Check if order contains course items
     */
    public function hasCourses(): bool
    {
        return $this->items()->where('item_type', 'course')->exists();
    }

    /**
     * Check if order contains ebook items
     */
    public function hasEbooks(): bool
    {
        return $this->items()->where('item_type', 'ebook')->exists();
    }

    /**
     * Get course items
     */
    public function getCourseItems(): HasMany
    {
        return $this->getItemsByType('course');
    }

    /**
     * Get ebook items
     */
    public function getEbookItems(): HasMany
    {
        return $this->getItemsByType('ebook');
    }
}
