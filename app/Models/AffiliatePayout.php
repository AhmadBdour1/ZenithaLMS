<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AffiliatePayout extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'affiliate_id',
        'amount',
        'status', // 'pending', 'processing', 'completed', 'failed', 'cancelled'
        'commissions_count',
        'requested_at',
        'processed_at',
        'completed_at',
        'payment_method',
        'payment_details',
        'transaction_id',
        'failure_reason',
        'notes',
        'admin_notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'requested_at' => 'datetime',
        'processed_at' => 'datetime',
        'completed_at' => 'datetime',
        'payment_details' => 'array',
    ];

    // Relationships
    public function affiliate()
    {
        return $this->belongsTo(Affiliate::class);
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

    public function scopeProcessing($query)
    {
        return $query->where('status', 'processing');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    // Methods
    public function process()
    {
        return $this->update([
            'status' => 'processing',
            'processed_at' => now(),
        ]);
    }

    public function complete($transactionId = null)
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
            'transaction_id' => $transactionId,
        ]);

        // Mark commissions as paid
        $this->commissions()->update(['status' => 'paid']);

        return true;
    }

    public function fail($reason)
    {
        $this->update([
            'status' => 'failed',
            'failure_reason' => $reason,
        ]);

        // Return commissions to approved status
        $this->commissions()->update(['status' => 'approved']);

        return true;
    }

    public function cancel()
    {
        $this->update(['status' => 'cancelled']);

        // Return commissions to approved status
        $this->commissions()->update(['status' => 'approved']);

        return true;
    }
}
