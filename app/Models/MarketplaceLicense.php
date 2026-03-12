<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MarketplaceLicense extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'marketplace_id',
        'user_id',
        'license_key',
        'type', // 'standard', 'extended', 'lifetime'
        'domains',
        'max_domains',
        'is_active',
        'expires_at',
        'support_until',
        'last_verified',
        'verification_count',
    ];

    protected $casts = [
        'domains' => 'array',
        'max_domains' => 'integer',
        'is_active' => 'boolean',
        'expires_at' => 'datetime',
        'support_until' => 'datetime',
        'last_verified' => 'datetime',
        'verification_count' => 'integer',
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

    public function sale()
    {
        return $this->belongsTo(MarketplaceSale::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<', now());
    }

    public function scopeValid($query)
    {
        return $query->where('is_active', true)
                    ->where(function ($q) {
                        $q->whereNull('expires_at')
                          ->orWhere('expires_at', '>', now());
                    });
    }

    // Methods
    public function isValid()
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->expires_at && $this->expires_at < now()) {
            return false;
        }

        return true;
    }

    public function canAddDomain($domain)
    {
        if (!$this->isValid()) {
            return false;
        }

        if ($this->max_domains && count($this->domains) >= $this->max_domains) {
            return false;
        }

        return !in_array($domain, $this->domains);
    }

    public function addDomain($domain)
    {
        if (!$this->canAddDomain($domain)) {
            return false;
        }

        $domains = $this->domains ?? [];
        $domains[] = $domain;
        
        $this->update([
            'domains' => $domains,
            'last_verified' => now(),
            'verification_count' => $this->verification_count + 1,
        ]);

        return true;
    }

    public function removeDomain($domain)
    {
        $domains = $this->domains ?? [];
        
        if (!in_array($domain, $domains)) {
            return false;
        }

        $domains = array_filter($domains, function ($d) use ($domain) {
            return $d !== $domain;
        });

        $this->update(['domains' => array_values($domains)]);

        return true;
    }

    public function verifyDomain($domain)
    {
        $this->update([
            'last_verified' => now(),
            'verification_count' => $this->verification_count + 1,
        ]);

        return $this->isValid() && in_array($domain, $this->domains);
    }

    public function extendSupport($months = 6)
    {
        $newDate = $this->support_until ? $this->support_until->addMonths($months) : now()->addMonths($months);
        
        $this->update(['support_until' => $newDate]);

        return $newDate;
    }

    public function renew($type = 'standard')
    {
        if (!$this->isValid()) {
            return false;
        }

        $newExpiresAt = now()->addYear();
        if ($type === 'lifetime') {
            $newExpiresAt = null;
        }

        $this->update([
            'type' => $type,
            'expires_at' => $newExpiresAt,
            'support_until' => now()->addMonths(6),
        ]);

        return true;
    }
}
