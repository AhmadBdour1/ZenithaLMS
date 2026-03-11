<?php

namespace App\Models\Central;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    use HasFactory;

    protected $connection = 'central';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'price',
        'billing_cycle',
        'max_students',
        'max_instructors',
        'max_courses',
        'max_storage_mb',
        'features',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'features' => 'array',
        'is_active' => 'boolean',
        'max_students' => 'integer',
        'max_instructors' => 'integer',
        'max_courses' => 'integer',
        'max_storage_mb' => 'integer',
        'sort_order' => 'integer',
    ];

    /**
     * Get all tenant subscriptions for this plan.
     */
    public function tenantSubscriptions()
    {
        return $this->hasMany(TenantSubscription::class);
    }

    /**
     * Scope to get only active plans.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get formatted price.
     */
    public function getFormattedPriceAttribute()
    {
        return '$' . number_format($this->price, 2);
    }
}
