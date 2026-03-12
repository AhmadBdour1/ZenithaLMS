<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MarketplaceReview extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'marketplace_id',
        'user_id',
        'rating', // 1-5
        'comment',
        'pros',
        'cons',
        'is_verified_purchase',
        'helpful_count',
        'status', // 'pending', 'approved', 'rejected'
    ];

    protected $casts = [
        'rating' => 'integer',
        'is_verified_purchase' => 'boolean',
        'helpful_count' => 'integer',
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

    // Scopes
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeVerifiedPurchase($query)
    {
        return $query->where('is_verified_purchase', true);
    }

    // Methods
    public function markHelpful()
    {
        $this->increment('helpful_count');
    }

    public function isFromVerifiedBuyer()
    {
        return $this->is_verified_purchase || 
               $this->marketplace->sales()->where('user_id', $this->user_id)->exists();
    }
}
