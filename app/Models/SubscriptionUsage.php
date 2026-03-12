<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SubscriptionUsage extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'subscription_id',
        'feature',
        'amount',
        'recorded_at',
        'metadata',
        'period', // 'daily', 'weekly', 'monthly', 'yearly'
        'reset_date',
    ];

    protected $casts = [
        'recorded_at' => 'datetime',
        'reset_date' => 'datetime',
        'metadata' => 'array',
    ];

    // Relationships
    public function subscription()
    {
        return $this->belongsTo(Subscription::class);
    }

    // Scopes
    public function scopeByFeature($query, $feature)
    {
        return $query->where('feature', $feature);
    }

    public function scopeByPeriod($query, $period)
    {
        return $query->where('period', $period);
    }

    public function scopeInPeriod($query, $startDate, $endDate)
    {
        return $query->whereBetween('recorded_at', [$startDate, $endDate]);
    }

    // Methods
    public static function recordUsage($subscriptionId, $feature, $amount = 1, $metadata = [])
    {
        return static::create([
            'subscription_id' => $subscriptionId,
            'feature' => $feature,
            'amount' => $amount,
            'recorded_at' => now(),
            'metadata' => $metadata,
            'period' => 'daily',
            'reset_date' => now()->endOfDay(),
        ]);
    }

    public static function resetDailyUsage($subscriptionId, $feature)
    {
        return static::where('subscription_id', $subscriptionId)
            ->where('feature', $feature)
            ->where('period', 'daily')
            ->whereDate('recorded_at', '<', now()->subDays(1))
            ->delete();
    }

    public static function getUsageInPeriod($subscriptionId, $feature, $period = 'monthly')
    {
        $startDate = now()->sub($period === 'monthly' ? 1 : ($period === 'weekly' ? 1 : 365), $period === 'monthly' ? 'month' : ($period === 'weekly' ? 'week' : 'year'));
        
        return static::where('subscription_id', $subscriptionId)
            ->where('feature', $feature)
            ->where('period', $period)
            ->where('recorded_at', '>=', $startDate)
            ->sum('amount');
    }
}
