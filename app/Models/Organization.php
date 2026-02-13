<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Organization extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'slug',
        'domain',
        'description',
        'logo',
        'email',
        'phone',
        'address',
        'city',
        'country',
        'settings',
        'is_active',
        'subscription_expires_at',
        'subscription_tier',
        'max_students',
        'max_instructors',
    ];

    protected $casts = [
        'settings' => 'array',
        'is_active' => 'boolean',
        'subscription_expires_at' => 'datetime',
        'max_students' => 'integer',
        'max_instructors' => 'integer',
    ];

    /**
     * Relationships
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function branches()
    {
        return $this->hasMany(Branch::class);
    }

    public function departments()
    {
        return $this->hasMany(Department::class);
    }

    public function courses()
    {
        return $this->hasMany(Course::class);
    }

    public function enrollments()
    {
        return $this->hasMany(Enrollment::class);
    }

    public function subscription()
    {
        return $this->hasOne(Subscription::class);
    }

    /**
     * Get active subscription
     */
    public function getActiveSubscription()
    {
        return $this->subscription()
            ->where('status', 'active')
            ->where('expires_at', '>', now())
            ->first();
    }

    /**
     * Check if organization has active subscription
     */
    public function hasActiveSubscription()
    {
        return $this->getActiveSubscription() !== null;
    }

    /**
     * Check if organization can add more students
     */
    public function canAddStudent()
    {
        if (!$this->hasActiveSubscription()) {
            return false;
        }

        $currentStudents = $this->users()->where('role_id', 4)->count(); // Assuming student role ID is 4
        return $currentStudents < $this->max_students;
    }

    /**
     * Check if organization can add more instructors
     */
    public function canAddInstructor()
    {
        if (!$this->hasActiveSubscription()) {
            return false;
        }

        $currentInstructors = $this->users()->where('role_id', 3)->count(); // Assuming instructor role ID is 3
        return $currentInstructors < $this->max_instructors;
    }

    /**
     * Get subscription tier features
     */
    public function getTierFeatures()
    {
        $features = [
            'starter' => [
                'max_students' => 50,
                'max_instructors' => 5,
                'ai_features' => false,
                'custom_branding' => false,
                'api_access' => false,
            ],
            'professional' => [
                'max_students' => 500,
                'max_instructors' => 50,
                'ai_features' => true,
                'custom_branding' => true,
                'api_access' => false,
            ],
            'enterprise' => [
                'max_students' => -1, // Unlimited
                'max_instructors' => -1, // Unlimited
                'ai_features' => true,
                'custom_branding' => true,
                'api_access' => true,
            ],
        ];

        return $features[$this->subscription_tier] ?? $features['starter'];
    }
}
