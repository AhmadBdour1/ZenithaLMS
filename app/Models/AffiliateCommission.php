<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AffiliateCommission extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'affiliate_id',
        'conversion_id',
        'payout_id',
        'amount',
        'status', // 'pending', 'approved', 'requested', 'paid', 'rejected'
        'type', // 'sale', 'signup', 'subscription', 'course_enrollment', 'marketplace_purchase'
        'created_at',
        'approved_at',
        'requested_at',
        'paid_at',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'created_at' => 'datetime',
        'approved_at' => 'datetime',
        'requested_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    // Relationships
    public function affiliate()
    {
        return $this->belongsTo(Affiliate::class);
    }

    public function conversion()
    {
        return $this->belongsTo(AffiliateConversion::class);
    }

    public function payout()
    {
        return $this->belongsTo(AffiliatePayout::class);
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeRequested($query)
    {
        return $query->where('status', 'requested');
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function scopeByAffiliate($query, $affiliateId)
    {
        return $query->where('affiliate_id', $affiliateId);
    }

    public function scopeByPeriod($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    // Methods
    public function approve()
    {
        return $this->update([
            'status' => 'approved',
            'approved_at' => now(),
        ]);
    }

    public function requestPayout($payoutId)
    {
        return $this->update([
            'status' => 'requested',
            'requested_at' => now(),
            'payout_id' => $payoutId,
        ]);
    }

    public function markAsPaid()
    {
        return $this->update([
            'status' => 'paid',
            'paid_at' => now(),
        ]);
    }
}
