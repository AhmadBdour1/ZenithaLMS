<?php

namespace App\Models\Central;

use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;

class Tenant extends BaseTenant implements TenantWithDatabase
{
    use HasDatabase, HasDomains;

    protected $connection = 'central';
    
    // Merge with parent fillable
    public static function booted()
    {
        parent::booted();
    }

    protected $fillable = [
        'id',
        'organization_name',
        'admin_name',
        'admin_email',
        'logo_url',
        'primary_color',
        'secondary_color',
        'custom_css',
        'status',
        'trial_ends_at',
        'data', // Parent uses this for flexible data storage
    ];

    protected $casts = [
        'trial_ends_at' => 'date',
    ];

    /**
     * Get the subscription for this tenant.
     */
    public function subscription()
    {
        return $this->hasOne(TenantSubscription::class, 'tenant_id');
    }

    /**
     * Get the active subscription for this tenant.
     */
    public function activeSubscription()
    {
        return $this->hasOne(TenantSubscription::class, 'tenant_id')
                    ->where('status', 'active');
    }

    /**
     * Check if tenant is on trial.
     */
    public function isOnTrial(): bool
    {
        return $this->status === 'trial' && 
               $this->trial_ends_at && 
               $this->trial_ends_at->isFuture();
    }

    /**
     * Check if tenant is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if tenant is suspended.
     */
    public function isSuspended(): bool
    {
        return $this->status === 'suspended';
    }
}
