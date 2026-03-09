<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StuffPurchase extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'stuff_id',
        'user_id',
        'order_id',
        'license_id',
        'quantity',
        'price',
        'currency',
        'subtotal',
        'tax_amount',
        'discount_amount',
        'total_amount',
        'payment_method',
        'payment_status', // 'pending', 'completed', 'failed', 'refunded', 'partially_refunded'
        'status', // 'pending', 'active', 'expired', 'cancelled', 'refunded'
        'purchase_type', // 'one_time', 'subscription', 'trial'
        'license_type',
        'license_key',
        'license_expires_at',
        'download_count',
        'download_limit',
        'download_expiry_date',
        'access_granted_at',
        'access_expires_at',
        'auto_renewal_enabled',
        'renewal_price',
        'renewal_period',
        'next_renewal_at',
        'trial_started_at',
        'trial_ends_at',
        'subscription_id',
        'refund_amount',
        'refund_reason',
        'refunded_at',
        'notes',
        'metadata',
        'ip_address',
        'user_agent',
        'purchased_at',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'price' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'download_count' => 'integer',
        'download_limit' => 'integer',
        'auto_renewal_enabled' => 'boolean',
        'renewal_price' => 'decimal:2',
        'refund_amount' => 'decimal:2',
        'license_expires_at' => 'datetime',
        'download_expiry_date' => 'datetime',
        'access_granted_at' => 'datetime',
        'access_expires_at' => 'datetime',
        'next_renewal_at' => 'datetime',
        'trial_started_at' => 'datetime',
        'trial_ends_at' => 'datetime',
        'refunded_at' => 'datetime',
        'purchased_at' => 'datetime',
        'metadata' => 'array',
    ];

    // Relationships
    public function stuff()
    {
        return $this->belongsTo(Stuff::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function order()
    {
        return $this->belongsTo(AuraOrder::class, 'order_id');
    }

    public function license()
    {
        return $this->belongsTo(StuffLicense::class, 'license_id');
    }

    public function subscription()
    {
        return $this->belongsTo(Subscription::class, 'subscription_id');
    }

    public function downloads()
    {
        return $this->hasMany(StuffDownload::class);
    }

    public function refunds()
    {
        return $this->hasMany(StuffRefund::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeExpired($query)
    {
        return $query->where('status', 'expired');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    public function scopeRefunded($query)
    {
        return $query->where('status', 'refunded');
    }

    public function scopeCompleted($query)
    {
        return $query->where('payment_status', 'completed');
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByStuff($query, $stuffId)
    {
        return $query->where('stuff_id', $stuffId);
    }

    public function scopeWithAccess($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('access_expires_at')
              ->orWhere('access_expires_at', '>', now());
        });
    }

    public function scopeCanDownload($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('download_limit')
              ->orWhereRaw('download_count < download_limit');
        })->where(function ($q) {
            $q->whereNull('download_expiry_date')
              ->orWhere('download_expiry_date', '>', now());
        });
    }

    // Methods
    public function completePayment()
    {
        $this->update([
            'payment_status' => 'completed',
            'status' => 'active',
            'access_granted_at' => now(),
            'purchased_at' => now(),
        ]);

        // Generate license if needed
        if ($this->stuff->is_digital && !$this->license_id) {
            $license = $this->generateLicense();
            $this->update(['license_id' => $license->id]);
        }

        // Record purchase in stuff analytics
        $this->stuff->recordPurchase($this);

        return $this;
    }

    public function failPayment()
    {
        $this->update([
            'payment_status' => 'failed',
            'status' => 'cancelled',
        ]);

        return $this;
    }

    public function grantAccess()
    {
        $this->update([
            'access_granted_at' => now(),
            'status' => 'active',
        ]);

        return $this;
    }

    public function revokeAccess()
    {
        $this->update([
            'status' => 'cancelled',
        ]);

        return $this;
    }

    public function expireAccess()
    {
        $this->update([
            'status' => 'expired',
        ]);

        return $this;
    }

    public function canDownload()
    {
        // Check if purchase is active
        if ($this->status !== 'active') {
            return false;
        }

        // Check download limit
        if ($this->download_limit && $this->download_count >= $this->download_limit) {
            return false;
        }

        // Check download expiry
        if ($this->download_expiry_date && now()->gt($this->download_expiry_date)) {
            return false;
        }

        // Check access expiry
        if ($this->access_expires_at && now()->gt($this->access_expires_at)) {
            return false;
        }

        return true;
    }

    public function hasAccess()
    {
        // Check if purchase is active
        if ($this->status !== 'active') {
            return false;
        }

        // Check access expiry
        if ($this->access_expires_at && now()->gt($this->access_expires_at)) {
            return false;
        }

        return true;
    }

    public function recordDownload()
    {
        if (!$this->canDownload()) {
            return false;
        }

        $this->increment('download_count');

        // Create download record
        $download = $this->downloads()->create([
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        // Record in stuff analytics
        $this->stuff->recordDownload($this);

        return $download;
    }

    public function generateLicense()
    {
        return $this->stuff->licenses()->create([
            'user_id' => $this->user_id,
            'purchase_id' => $this->id,
            'license_type' => $this->license_type ?: $this->stuff->license_type,
            'license_key' => $this->generateLicenseKey(),
            'expires_at' => $this->license_expires_at,
            'activated_at' => now(),
        ]);
    }

    public function generateLicenseKey()
    {
        $prefix = strtoupper(substr($this->stuff->type, 0, 3));
        $userId = str_pad($this->user_id, 6, '0', STR_PAD_LEFT);
        $purchaseId = str_pad($this->id, 8, '0', STR_PAD_LEFT);
        $random = strtoupper(\Illuminate\Support\Str::random(8));
        
        return $prefix . '-' . $userId . '-' . $purchaseId . '-' . $random;
    }

    public function startTrial()
    {
        if (!$this->stuff->hasTrial()) {
            return false;
        }

        $this->update([
            'purchase_type' => 'trial',
            'trial_started_at' => now(),
            'trial_ends_at' => now()->addDays($this->stuff->trial_days),
            'status' => 'active',
        ]);

        return $this;
    }

    public function endTrial()
    {
        if ($this->purchase_type !== 'trial') {
            return false;
        }

        $this->update([
            'purchase_type' => 'one_time',
            'trial_started_at' => null,
            'trial_ends_at' => null,
        ]);

        return $this;
    }

    public function isTrial()
    {
        return $this->purchase_type === 'trial' && 
               $this->trial_ends_at && 
               now()->lt($this->trial_ends_at);
    }

    public function isTrialExpired()
    {
        return $this->purchase_type === 'trial' && 
               $this->trial_ends_at && 
               now()->gt($this->trial_ends_at);
    }

    public function getTrialDaysLeft()
    {
        if (!$this->isTrial()) {
            return 0;
        }

        return now()->diffInDays($this->trial_ends_at, false);
    }

    public function enableAutoRenewal()
    {
        $this->update([
            'auto_renewal_enabled' => true,
            'renewal_price' => $this->stuff->getRenewalPrice(),
            'renewal_period' => $this->stuff->getRenewalPeriod(),
            'next_renewal_at' => $this->calculateNextRenewalDate(),
        ]);

        return $this;
    }

    public function disableAutoRenewal()
    {
        $this->update([
            'auto_renewal_enabled' => false,
            'next_renewal_at' => null,
        ]);

        return $this;
    }

    public function calculateNextRenewalDate()
    {
        if (!$this->auto_renewal_enabled) {
            return null;
        }

        $baseDate = $this->access_expires_at ?: now();
        
        switch ($this->renewal_period) {
            case 'daily':
                return $baseDate->addDay();
            case 'weekly':
                return $baseDate->addWeek();
            case 'monthly':
                return $baseDate->addMonth();
            case 'quarterly':
                return $baseDate->addQuarter();
            case 'yearly':
                return $baseDate->addYear();
            case 'lifetime':
                return null;
            default:
                return $baseDate->addMonth();
        }
    }

    public function renew()
    {
        if (!$this->auto_renewal_enabled) {
            return false;
        }

        // Process renewal payment
        $renewalSuccess = $this->processRenewalPayment();

        if ($renewalSuccess) {
            $this->update([
                'access_expires_at' => $this->calculateNextRenewalDate(),
                'next_renewal_at' => $this->calculateNextRenewalDate(),
                'download_count' => 0, // Reset download count for renewal
            ]);

            return true;
        }

        return false;
    }

    public function processRenewalPayment()
    {
        // Implement renewal payment processing
        // This would integrate with payment gateways
        return true; // Placeholder
    }

    public function refund($amount = null, $reason = null)
    {
        $refundAmount = $amount ?: $this->total_amount;

        $this->update([
            'refund_amount' => $refundAmount,
            'refund_reason' => $reason,
            'refunded_at' => now(),
            'status' => 'refunded',
            'payment_status' => 'refunded',
        ]);

        // Create refund record
        $this->refunds()->create([
            'amount' => $refundAmount,
            'reason' => $reason,
            'refunded_at' => now(),
        ]);

        return $this;
    }

    public function partialRefund($amount, $reason = null)
    {
        if ($amount >= $this->total_amount) {
            return $this->refund($amount, $reason);
        }

        $this->update([
            'refund_amount' => $amount,
            'refund_reason' => $reason,
            'refunded_at' => now(),
            'payment_status' => 'partially_refunded',
        ]);

        // Create refund record
        $this->refunds()->create([
            'amount' => $amount,
            'reason' => $reason,
            'refunded_at' => now(),
        ]);

        return $this;
    }

    public function getFormattedPrice()
    {
        return number_format($this->price, 2) . ' ' . $this->currency;
    }

    public function getFormattedTotalAmount()
    {
        return number_format($this->total_amount, 2) . ' ' . $this->currency;
    }

    public function getFormattedRefundAmount()
    {
        return $this->refund_amount ? number_format($this->refund_amount, 2) . ' ' . $this->currency : null;
    }

    public function getDownloadProgress()
    {
        if (!$this->download_limit) {
            return null;
        }

        return [
            'used' => $this->download_count,
            'limit' => $this->download_limit,
            'percentage' => ($this->download_count / $this->download_limit) * 100,
            'remaining' => max(0, $this->download_limit - $this->download_count),
        ];
    }

    public function getAccessStatus()
    {
        if ($this->status === 'refunded') {
            return 'refunded';
        }

        if ($this->status === 'cancelled') {
            return 'cancelled';
        }

        if ($this->status === 'expired') {
            return 'expired';
        }

        if ($this->isTrialExpired()) {
            return 'trial_expired';
        }

        if ($this->isTrial()) {
            return 'trial';
        }

        if ($this->hasAccess()) {
            return 'active';
        }

        return 'inactive';
    }

    public function getAccessStatusText()
    {
        switch ($this->getAccessStatus()) {
            case 'active':
                return 'Active';
            case 'trial':
                return 'Trial (' . $this->getTrialDaysLeft() . ' days left)';
            case 'trial_expired':
                return 'Trial Expired';
            case 'expired':
                return 'Expired';
            case 'cancelled':
                return 'Cancelled';
            case 'refunded':
                return 'Refunded';
            case 'inactive':
                return 'Inactive';
            default:
                return 'Unknown';
        }
    }

    public function getPaymentStatusText()
    {
        switch ($this->payment_status) {
            case 'pending':
                return 'Payment Pending';
            case 'completed':
                return 'Payment Completed';
            case 'failed':
                return 'Payment Failed';
            case 'refunded':
                return 'Refunded';
            case 'partially_refunded':
                return 'Partially Refunded';
            default:
                return 'Unknown';
        }
    }

    public function getPurchaseTypeText()
    {
        switch ($this->purchase_type) {
            case 'one_time':
                return 'One Time Purchase';
            case 'subscription':
                return 'Subscription';
            case 'trial':
                return 'Trial';
            default:
                return 'Unknown';
        }
    }

    public function getCreatedAtFormatted()
    {
        return $this->created_at->format('Y-m-d H:i:s');
    }

    public function getPurchasedAtFormatted()
    {
        return $this->purchased_at ? $this->purchased_at->format('Y-m-d H:i:s') : null;
    }

    public function getAccessExpiresAtFormatted()
    {
        return $this->access_expires_at ? $this->access_expires_at->format('Y-m-d H:i:s') : null;
    }

    public function getDownloadExpiryDateFormatted()
    {
        return $this->download_expiry_date ? $this->download_expiry_date->format('Y-m-d H:i:s') : null;
    }

    public function getTrialEndsAtFormatted()
    {
        return $this->trial_ends_at ? $this->trial_ends_at->format('Y-m-d H:i:s') : null;
    }

    public function getNextRenewalAtFormatted()
    {
        return $this->next_renewal_at ? $this->next_renewal_at->format('Y-m-d H:i:s') : null;
    }

    public function getRefundedAtFormatted()
    {
        return $this->refunded_at ? $this->refunded_at->format('Y-m-d H:i:s') : null;
    }
}

class StuffRefund extends Model
{
    protected $fillable = [
        'purchase_id',
        'amount',
        'reason',
        'status', // 'pending', 'approved', 'rejected'
        'processed_at',
        'admin_notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'processed_at' => 'datetime',
    ];

    public function purchase()
    {
        return $this->belongsTo(StuffPurchase::class);
    }

    public function approve($adminNotes = null)
    {
        $this->update([
            'status' => 'approved',
            'processed_at' => now(),
            'admin_notes' => $adminNotes,
        ]);

        return $this;
    }

    public function reject($adminNotes = null)
    {
        $this->update([
            'status' => 'rejected',
            'admin_notes' => $adminNotes,
        ]);

        return $this;
    }

    public function getFormattedAmount()
    {
        return number_format($this->amount, 2) . ' ' . $this->purchase->currency;
    }
}
