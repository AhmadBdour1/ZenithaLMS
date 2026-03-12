<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserPermission extends Model
{
    protected $fillable = [
        'user_id',
        'permission_id',
        'granted_by',
        'granted_at',
        'expires_at',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'granted_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function permission()
    {
        return $this->belongsTo(Permission::class);
    }

    public function grantedByUser()
    {
        return $this->belongsTo(User::class, 'granted_by');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
                    ->where(function ($q) {
                        $q->whereNull('expires_at')
                          ->orWhere('expires_at', '>', now());
                    });
    }

    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<=', now());
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByPermission($query, $permissionId)
    {
        return $query->where('permission_id', $permissionId);
    }

    // Methods
    public function isExpired()
    {
        return $this->expires_at && now()->gt($this->expires_at);
    }

    public function isValid()
    {
        return $this->is_active && !$this->isExpired();
    }

    public function revoke()
    {
        $this->update(['is_active' => false]);
        return $this;
    }

    public function extend($days)
    {
        $newExpiry = $this->expires_at ? $this->expires_at->addDays($days) : now()->addDays($days);
        $this->update(['expires_at' => $newExpiry]);
        return $this;
    }

    public function makePermanent()
    {
        $this->update(['expires_at' => null]);
        return $this;
    }
}

class UserRole extends Model
{
    protected $fillable = [
        'user_id',
        'role_id',
        'assigned_by',
        'assigned_at',
        'expires_at',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function assignedByUser()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
                    ->where(function ($q) {
                        $q->whereNull('expires_at')
                          ->orWhere('expires_at', '>', now());
                    });
    }

    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<=', now());
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByRole($query, $roleId)
    {
        return $query->where('role_id', $roleId);
    }

    // Methods
    public function isExpired()
    {
        return $this->expires_at && now()->gt($this->expires_at);
    }

    public function isValid()
    {
        return $this->is_active && !$this->isExpired();
    }

    public function revoke()
    {
        $this->update(['is_active' => false]);
        return $this;
    }

    public function extend($days)
    {
        $newExpiry = $this->expires_at ? $this->expires_at->addDays($days) : now()->addDays($days);
        $this->update(['expires_at' => $newExpiry]);
        return $this;
    }

    public function makePermanent()
    {
        $this->update(['expires_at' => null]);
        return $this;
    }
}

class RolePermission extends Model
{
    protected $fillable = [
        'role_id',
        'permission_id',
        'granted_by',
        'granted_at',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'granted_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function permission()
    {
        return $this->belongsTo(Permission::class);
    }

    public function grantedByUser()
    {
        return $this->belongsTo(User::class, 'granted_by');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByRole($query, $roleId)
    {
        return $query->where('role_id', $roleId);
    }

    public function scopeByPermission($query, $permissionId)
    {
        return $query->where('permission_id', $permissionId);
    }

    // Methods
    public function revoke()
    {
        $this->update(['is_active' => false]);
        return $this;
    }
}
