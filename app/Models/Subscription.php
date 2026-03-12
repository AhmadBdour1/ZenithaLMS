<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Subscription extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'plan_id',
        'status',
        'starts_at',
        'ends_at',
        'trial_ends_at',
        'canceled_at',
        'price',
        'currency',
        'billing_cycle',
        'payment_method',
        'stripe_subscription_id',
        'paypal_subscription_id',
        'auto_renew',
        'last_billed_at',
        'next_billing_at',
        'usage_stats',
        'metadata',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'trial_ends_at' => 'datetime',
        'canceled_at' => 'datetime',
        'price' => 'decimal:2',
        'auto_renew' => 'boolean',
        'last_billed_at' => 'datetime',
        'next_billing_at' => 'datetime',
        'usage_stats' => 'array',
        'metadata' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function plan()
    {
        return $this->belongsTo(SubscriptionPlan::class);
    }

    public function invoices()
    {
        return $this->hasMany(SubscriptionInvoice::class);
    }

    public function isActive()
    {
        return $this->status === 'active' && (!$this->ends_at || $this->ends_at->isFuture());
    }
}
