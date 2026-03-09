<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MarketplaceSale extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'marketplace_id',
        'user_id',
        'vendor_id',
        'amount',
        'commission_amount',
        'vendor_earnings',
        'payment_method',
        'transaction_id',
        'status', // 'pending', 'completed', 'failed', 'refunded'
        'refunded_at',
        'refund_reason',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'vendor_earnings' => 'decimal:2',
        'refunded_at' => 'datetime',
    ];

    // Relationships
    public function marketplace()
    {
        return $this->belongsTo(Marketplace::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function vendor()
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }

    public function license()
    {
        return $this->hasOne(MarketplaceLicense::class);
    }

    // Scopes
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeByVendor($query, $vendorId)
    {
        return $query->where('vendor_id', $vendorId);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    // Methods
    public function refund($reason = null)
    {
        if ($this->status === 'refunded') {
            return false;
        }

        DB::transaction(function () use ($reason) {
            // Refund user balance
            $this->user->increment('wallet_balance', $this->amount);
            
            // Deduct from vendor
            $this->vendor->decrement('wallet_balance', $this->vendor_earnings);
            
            // Update sale status
            $this->update([
                'status' => 'refunded',
                'refunded_at' => now(),
                'refund_reason' => $reason,
            ]);
            
            // Deactivate license
            if ($this->license) {
                $this->license->update(['is_active' => false]);
            }
        });

        return true;
    }
}
