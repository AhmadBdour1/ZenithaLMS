<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StuffLicense extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'stuff_id',
        'user_id',
        'purchase_id',
        'license_key',
        'license_type',
        'domain',
        'ip_address',
        'machine_id',
        'activation_limit',
        'activation_count',
        'expires_at',
        'activated_at',
        'last_used_at',
        'status', // 'active', 'suspended', 'expired', 'revoked'
        'notes',
        'metadata',
    ];

    protected $casts = [
        'activation_limit' => 'integer',
        'activation_count' => 'integer',
        'expires_at' => 'datetime',
        'activated_at' => 'datetime',
        'last_used_at' => 'datetime',
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

    public function purchase()
    {
        return $this->belongsTo(StuffPurchase::class, 'purchase_id');
    }

    public function activations()
    {
        return $this->hasMany(StuffLicenseActivation::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeExpired($query)
    {
        return $query->where('status', 'expired');
    }

    public function scopeSuspended($query)
    {
        return $query->where('status', 'suspended');
    }

    public function scopeRevoked($query)
    {
        return $query->where('status', 'revoked');
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByStuff($query, $stuffId)
    {
        return $query->where('stuff_id', $stuffId);
    }

    public function scopeValid($query)
    {
        return $query->where('status', 'active')
                    ->where(function ($q) {
                        $q->whereNull('expires_at')
                          ->orWhere('expires_at', '>', now());
                    });
    }

    // Methods
    public function activate($domain = null, $ipAddress = null, $machineId = null)
    {
        // Check if license is valid for activation
        if (!$this->canActivate()) {
            return false;
        }

        // Check activation limit
        if ($this->activation_limit && $this->activation_count >= $this->activation_limit) {
            return false;
        }

        // Check if already activated on this domain/machine
        if ($this->isAlreadyActivated($domain, $ipAddress, $machineId)) {
            return true;
        }

        // Create activation record
        $activation = $this->activations()->create([
            'domain' => $domain,
            'ip_address' => $ipAddress,
            'machine_id' => $machineId,
            'activated_at' => now(),
        ]);

        // Update license
        $this->update([
            'activation_count' => $this->activation_count + 1,
            'domain' => $domain,
            'ip_address' => $ipAddress,
            'machine_id' => $machineId,
            'last_used_at' => now(),
        ]);

        return $activation;
    }

    public function deactivate($domain = null, $ipAddress = null, $machineId = null)
    {
        $activation = $this->activations()
            ->where('domain', $domain)
            ->where('ip_address', $ipAddress)
            ->where('machine_id', $machineId)
            ->first();

        if ($activation) {
            $activation->delete();
            $this->decrement('activation_count');
            return true;
        }

        return false;
    }

    public function canActivate()
    {
        // Check license status
        if ($this->status !== 'active') {
            return false;
        }

        // Check expiration
        if ($this->expires_at && now()->gt($this->expires_at)) {
            return false;
        }

        // Check activation limit
        if ($this->activation_limit && $this->activation_count >= $this->activation_limit) {
            return false;
        }

        return true;
    }

    public function isAlreadyActivated($domain = null, $ipAddress = null, $machineId = null)
    {
        return $this->activations()
            ->where('domain', $domain)
            ->where('ip_address', $ipAddress)
            ->where('machine_id', $machineId)
            ->exists();
    }

    public function validate($domain = null, $ipAddress = null, $machineId = null)
    {
        // Check if license is valid
        if (!$this->canActivate()) {
            return false;
        }

        // Check if activated on this domain/machine
        if (!$this->isAlreadyActivated($domain, $ipAddress, $machineId)) {
            return false;
        }

        // Update last used
        $this->update(['last_used_at' => now()]);

        return true;
    }

    public function suspend($reason = null)
    {
        $this->update([
            'status' => 'suspended',
            'notes' => $reason,
        ]);

        return $this;
    }

    public function reactivate()
    {
        $this->update([
            'status' => 'active',
        ]);

        return $this;
    }

    public function revoke($reason = null)
    {
        $this->update([
            'status' => 'revoked',
            'notes' => $reason,
        ]);

        // Deactivate all activations
        $this->activations()->delete();
        $this->update(['activation_count' => 0]);

        return $this;
    }

    public function extend($days)
    {
        $newExpiry = $this->expires_at ? $this->expires_at->addDays($days) : now()->addDays($days);
        
        $this->update([
            'expires_at' => $newExpiry,
            'status' => 'active',
        ]);

        return $this;
    }

    public function extendUntil($date)
    {
        $this->update([
            'expires_at' => $date,
            'status' => 'active',
        ]);

        return $this;
    }

    public function makeLifetime()
    {
        $this->update([
            'expires_at' => null,
            'status' => 'active',
        ]);

        return $this;
    }

    public function increaseActivationLimit($limit)
    {
        $this->update([
            'activation_limit' => $this->activation_limit + $limit,
        ]);

        return $this;
    }

    public function setActivationLimit($limit)
    {
        $this->update([
            'activation_limit' => $limit,
        ]);

        return $this;
    }

    public function removeActivationLimit()
    {
        $this->update([
            'activation_limit' => null,
        ]);

        return $this;
    }

    public function isExpired()
    {
        return $this->expires_at && now()->gt($this->expires_at);
    }

    public function isSuspended()
    {
        return $this->status === 'suspended';
    }

    public function isRevoked()
    {
        return $this->status === 'revoked';
    }

    public function isActive()
    {
        return $this->status === 'active' && !$this->isExpired();
    }

    public function getDaysUntilExpiry()
    {
        if (!$this->expires_at) {
            return null; // Lifetime license
        }

        return now()->diffInDays($this->expires_at, false);
    }

    public function getExpiryStatus()
    {
        if (!$this->expires_at) {
            return 'lifetime';
        }

        $daysLeft = $this->getDaysUntilExpiry();

        if ($daysLeft < 0) {
            return 'expired';
        } elseif ($daysLeft <= 7) {
            return 'expiring_soon';
        } elseif ($daysLeft <= 30) {
            return 'expiring';
        } else {
            return 'valid';
        }
    }

    public function getExpiryStatusText()
    {
        switch ($this->getExpiryStatus()) {
            case 'lifetime':
                return 'Lifetime License';
            case 'expired':
                return 'Expired';
            case 'expiring_soon':
                return 'Expires in ' . $this->getDaysUntilExpiry() . ' days';
            case 'expiring':
                return 'Expires in ' . $this->getDaysUntilExpiry() . ' days';
            case 'valid':
                return 'Valid until ' . $this->expires_at->format('Y-m-d');
            default:
                return 'Unknown';
        }
    }

    public function getActivationProgress()
    {
        if (!$this->activation_limit) {
            return null;
        }

        return [
            'used' => $this->activation_count,
            'limit' => $this->activation_limit,
            'percentage' => ($this->activation_count / $this->activation_limit) * 100,
            'remaining' => max(0, $this->activation_limit - $this->activation_count),
        ];
    }

    public function getStatusText()
    {
        switch ($this->status) {
            case 'active':
                return $this->isExpired() ? 'Expired' : 'Active';
            case 'suspended':
                return 'Suspended';
            case 'expired':
                return 'Expired';
            case 'revoked':
                return 'Revoked';
            default:
                return 'Unknown';
        }
    }

    public function getLicenseTypeText()
    {
        switch ($this->license_type) {
            case 'single':
                return 'Single Use License';
            case 'multi':
                return 'Multi Use License';
            case 'resale':
                return 'Resale License';
            case 'private_label':
                return 'Private Label License';
            case 'commercial':
                return 'Commercial License';
            default:
                return 'Standard License';
        }
    }

    public function getFormattedActivatedAt()
    {
        return $this->activated_at ? $this->activated_at->format('Y-m-d H:i:s') : null;
    }

    public function getFormattedLastUsedAt()
    {
        return $this->last_used_at ? $this->last_used_at->format('Y-m-d H:i:s') : null;
    }

    public function getFormattedExpiresAt()
    {
        return $this->expires_at ? $this->expires_at->format('Y-m-d H:i:s') : null;
    }
}

class StuffLicenseActivation extends Model
{
    protected $fillable = [
        'license_id',
        'domain',
        'ip_address',
        'machine_id',
        'activated_at',
        'last_used_at',
        'user_agent',
        'notes',
    ];

    protected $casts = [
        'activated_at' => 'datetime',
        'last_used_at' => 'datetime',
    ];

    public function license()
    {
        return $this->belongsTo(StuffLicense::class);
    }

    public function updateLastUsed()
    {
        $this->update(['last_used_at' => now()]);
    }

    public function getFormattedActivatedAt()
    {
        return $this->activated_at->format('Y-m-d H:i:s');
    }

    public function getFormattedLastUsedAt()
    {
        return $this->last_used_at ? $this->last_used_at->format('Y-m-d H:i:s') : null;
    }
}
