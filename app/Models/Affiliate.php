<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Affiliate extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'affiliate_code',
        'custom_slug',
        'commission_rate',
        'commission_type', // 'percentage', 'fixed'
        'payout_method', // 'wallet', 'bank_transfer', 'paypal'
        'payout_details',
        'minimum_payout',
        'cookie_duration', // days
        'is_active',
        'approved_at',
        'rejected_at',
        'rejection_reason',
        'notes',
        'meta_data',
    ];

    protected $casts = [
        'commission_rate' => 'decimal:2',
        'minimum_payout' => 'decimal:2',
        'cookie_duration' => 'integer',
        'is_active' => 'boolean',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'payout_details' => 'array',
        'meta_data' => 'array',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function clicks()
    {
        return $this->hasMany(AffiliateClick::class);
    }

    public function conversions()
    {
        return $this->hasMany(AffiliateConversion::class);
    }

    public function payouts()
    {
        return $this->hasMany(AffiliatePayout::class);
    }

    public function commissions()
    {
        return $this->hasMany(AffiliateCommission::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeApproved($query)
    {
        return $query->whereNotNull('approved_at');
    }

    public function scopePending($query)
    {
        return $query->whereNull('approved_at')->whereNull('rejected_at');
    }

    public function scopeRejected($query)
    {
        return $query->whereNotNull('rejected_at');
    }

    // Methods
    public function generateAffiliateCode()
    {
        do {
            $code = strtoupper(Str::random(8));
        } while (static::where('affiliate_code', $code)->exists());

        $this->affiliate_code = $code;
        $this->save();

        return $code;
    }

    public function getAffiliateUrl($targetUrl = null)
    {
        $baseUrl = config('app.url');
        $targetUrl = $targetUrl ?: route('home');
        
        return "{$baseUrl}?ref={$this->affiliate_code}";
    }

    public function getCustomUrl()
    {
        if ($this->custom_slug) {
            return config('app.url') . '/ref/' . $this->custom_slug;
        }
        
        return $this->getAffiliateUrl();
    }

    public function trackClick($ipAddress = null, $userAgent = null, $referer = null)
    {
        return $this->clicks()->create([
            'ip_address' => $ipAddress ?? request()->ip(),
            'user_agent' => $userAgent ?? request()->userAgent(),
            'referer' => $referer ?? request()->referer(),
            'landing_page' => request()->fullUrl(),
            'clicked_at' => now(),
        ]);
    }

    public function calculateCommission($amount, $type = 'sale')
    {
        if ($this->commission_type === 'percentage') {
            return $amount * ($this->commission_rate / 100);
        } else {
            return $this->commission_rate;
        }
    }

    public function createConversion($type, $amount, $referenceId = null, $metadata = [])
    {
        $commission = $this->calculateCommission($amount, $type);

        $conversion = $this->conversions()->create([
            'type' => $type,
            'amount' => $amount,
            'commission_amount' => $commission,
            'reference_id' => $referenceId,
            'status' => 'pending',
            'meta_data' => $metadata,
        ]);

        // Create commission record
        $this->commissions()->create([
            'conversion_id' => $conversion->id,
            'amount' => $commission,
            'status' => 'pending',
            'type' => $type,
        ]);

        return $conversion;
    }

    public function getTotalEarnings()
    {
        return $this->commissions()->where('status', 'approved')->sum('amount');
    }

    public function getPendingEarnings()
    {
        return $this->commissions()->where('status', 'pending')->sum('amount');
    }

    public function getTotalClicks()
    {
        return $this->clicks()->count();
    }

    public function getTotalConversions()
    {
        return $this->conversions()->where('status', 'approved')->count();
    }

    public function getConversionRate()
    {
        $clicks = $this->getTotalClicks();
        $conversions = $this->getTotalConversions();
        
        return $clicks > 0 ? ($conversions / $clicks) * 100 : 0;
    }

    public function getEarningsThisMonth()
    {
        return $this->commissions()
            ->where('status', 'approved')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('amount');
    }

    public function canRequestPayout()
    {
        return $this->getPendingEarnings() >= $this->minimum_payout;
    }

    public function requestPayout()
    {
        if (!$this->canRequestPayout()) {
            return false;
        }

        $pendingCommissions = $this->commissions()->where('status', 'pending')->get();
        $totalAmount = $pendingCommissions->sum('amount');

        // Create payout request
        $payout = $this->payouts()->create([
            'amount' => $totalAmount,
            'status' => 'pending',
            'requested_at' => now(),
            'commissions_count' => $pendingCommissions->count(),
        ]);

        // Update commissions status
        $pendingCommissions->each(function ($commission) use ($payout) {
            $commission->update([
                'status' => 'requested',
                'payout_id' => $payout->id,
            ]);
        });

        return $payout;
    }

    public function approve()
    {
        $this->update([
            'is_active' => true,
            'approved_at' => now(),
            'rejected_at' => null,
            'rejection_reason' => null,
        ]);

        // Set default commission if not set
        if (!$this->commission_rate) {
            $this->update([
                'commission_rate' => config('affiliate.default_commission', 10),
                'commission_type' => 'percentage',
                'minimum_payout' => config('affiliate.minimum_payout', 50),
                'cookie_duration' => config('affiliate.cookie_duration', 30),
            ]);
        }

        return true;
    }

    public function reject($reason)
    {
        return $this->update([
            'is_active' => false,
            'rejected_at' => now(),
            'rejection_reason' => $reason,
        ]);
    }

    public function getStats($period = '30days')
    {
        $startDate = now()->subDays($period === '30days' ? 30 : ($period === '7days' ? 7 : 365));
        
        $clicks = $this->clicks()->where('clicked_at', '>=', $startDate)->count();
        $conversions = $this->conversions()
            ->where('created_at', '>=', $startDate)
            ->where('status', 'approved')
            ->count();
        $earnings = $this->commissions()
            ->where('created_at', '>=', $startDate)
            ->where('status', 'approved')
            ->sum('amount');

        return [
            'period' => $period,
            'clicks' => $clicks,
            'conversions' => $conversions,
            'earnings' => $earnings,
            'conversion_rate' => $clicks > 0 ? ($conversions / $clicks) * 100 : 0,
            'avg_commission' => $conversions > 0 ? $earnings / $conversions : 0,
        ];
    }
}
