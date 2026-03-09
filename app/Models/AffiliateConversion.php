<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AffiliateConversion extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'affiliate_id',
        'click_id',
        'type', // 'sale', 'signup', 'subscription', 'course_enrollment', 'marketplace_purchase'
        'amount',
        'commission_amount',
        'reference_id', // Order ID, User ID, etc.
        'status', // 'pending', 'approved', 'rejected'
        'approved_at',
        'rejected_at',
        'rejection_reason',
        'meta_data',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'meta_data' => 'array',
    ];

    // Relationships
    public function affiliate()
    {
        return $this->belongsTo(Affiliate::class);
    }

    public function click()
    {
        return $this->belongsTo(AffiliateClick::class);
    }

    public function commissions()
    {
        return $this->hasMany(AffiliateCommission::class);
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

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    // Methods
    public function approve()
    {
        $this->update([
            'status' => 'approved',
            'approved_at' => now(),
        ]);

        // Update click record
        if ($this->click) {
            $this->click->update([
                'converted_at' => now(),
                'conversion_id' => $this->id,
            ]);
        }

        // Approve related commissions
        $this->commissions()->update(['status' => 'approved']);

        return true;
    }

    public function reject($reason)
    {
        $this->update([
            'status' => 'rejected',
            'rejected_at' => now(),
            'rejection_reason' => $reason,
        ]);

        // Reject related commissions
        $this->commissions()->update(['status' => 'rejected']);

        return true;
    }
}
