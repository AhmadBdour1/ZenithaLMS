<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SubscriptionPlan extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'type', // 'platform', 'instructor', 'student', 'institution'
        'price',
        'billing_cycle', // 'monthly', 'yearly', 'lifetime'
        'trial_days',
        'features',
        'limits',
        'commission_rate',
        'marketplace_access',
        'api_access',
        'white_label',
        'custom_domain',
        'priority_support',
        'advanced_analytics',
        'bulk_import',
        'integrations',
        'storage_limit',
        'bandwidth_limit',
        'max_students',
        'max_courses',
        'max_instructors',
        'is_popular',
        'is_active',
        'sort_order',
        'stripe_price_id',
        'paypal_plan_id',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'trial_days' => 'integer',
        'features' => 'array',
        'limits' => 'array',
        'commission_rate' => 'decimal:2',
        'marketplace_access' => 'boolean',
        'api_access' => 'boolean',
        'white_label' => 'boolean',
        'custom_domain' => 'boolean',
        'priority_support' => 'boolean',
        'advanced_analytics' => 'boolean',
        'bulk_import' => 'boolean',
        'integrations' => 'array',
        'storage_limit' => 'integer',
        'bandwidth_limit' => 'integer',
        'max_students' => 'integer',
        'max_courses' => 'integer',
        'max_instructors' => 'integer',
        'is_popular' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    // Relationships
    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    public function features()
    {
        return $this->hasMany(PlanFeature::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopePopular($query)
    {
        return $query->where('is_popular', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('price');
    }

    // Methods
    public function getMonthlyPrice()
    {
        if ($this->billing_cycle === 'monthly') {
            return $this->price;
        } elseif ($this->billing_cycle === 'yearly') {
            return $this->price / 12;
        }
        
        return 0;
    }

    public function getYearlyPrice()
    {
        if ($this->billing_cycle === 'yearly') {
            return $this->price;
        } elseif ($this->billing_cycle === 'monthly') {
            return $this->price * 12;
        }
        
        return 0;
    }

    public function getYearlyDiscount()
    {
        if ($this->billing_cycle === 'yearly') {
            return round((1 - ($this->price / ($this->getMonthlyPrice() * 12))) * 100);
        }
        
        return 0;
    }

    public function hasFeature($feature)
    {
        return in_array($feature, $this->features ?? []);
    }

    public function hasIntegration($integration)
    {
        return in_array($integration, $this->integrations ?? []);
    }

    public function canCreateCourse()
    {
        if ($this->max_courses === null) {
            return true;
        }
        
        return auth()->user()->courses()->count() < $this->max_courses;
    }

    public function canAddStudent()
    {
        if ($this->max_students === null) {
            return true;
        }
        
        return auth()->user()->students()->count() < $this->max_students;
    }

    public function canAddInstructor()
    {
        if ($this->max_instructors === null) {
            return true;
        }
        
        return auth()->user()->instructors()->count() < $this->max_instructors;
    }

    public function getStorageUsed()
    {
        // Calculate storage used by the user
        return 0; // Implement storage calculation
    }

    public function getStorageRemaining()
    {
        if ($this->storage_limit === null) {
            return null; // Unlimited
        }
        
        return max(0, $this->storage_limit - $this->getStorageUsed());
    }

    public function canUploadFile($fileSize)
    {
        if ($this->storage_limit === null) {
            return true;
        }
        
        return $this->getStorageRemaining() >= $fileSize;
    }

    public static function getPlatformPlans()
    {
        return static::active()->byType('platform')->ordered()->get();
    }

    public static function getInstructorPlans()
    {
        return static::active()->byType('instructor')->ordered()->get();
    }

    public static function getStudentPlans()
    {
        return static::active()->byType('student')->ordered()->get();
    }

    public static function getInstitutionPlans()
    {
        return static::active()->byType('institution')->ordered()->get();
    }
}
