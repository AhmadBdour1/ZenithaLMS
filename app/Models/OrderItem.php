<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'item_type',
        'item_id',
        'item_name',
        'item_price',
        'quantity',
        'total_price',
        'item_data',
    ];

    protected $casts = [
        'item_price' => 'decimal:2',
        'total_price' => 'decimal:2',
        'item_data' => 'array',
    ];

    /**
     * Get the order that owns the item.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the related item based on type.
     */
    public function item()
    {
        return $this->morphTo();
    }

    /**
     * Scope a query to only include course items.
     */
    public function scopeCourses($query)
    {
        return $query->where('item_type', 'course');
    }

    /**
     * Scope a query to only include ebook items.
     */
    public function scopeEbooks($query)
    {
        return $query->where('item_type', 'ebook');
    }

    /**
     * Get formatted item price
     */
    public function getFormattedItemPriceAttribute(): string
    {
        return number_format($this->item_price, 2);
    }

    /**
     * Get formatted total price
     */
    public function getFormattedTotalPriceAttribute(): string
    {
        return number_format($this->total_price, 2);
    }
}
