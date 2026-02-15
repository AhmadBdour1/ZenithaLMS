<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Services\MediaService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role_id',
        'organization_id',
        'branch_id',
        'department_id',
        'phone',
        'avatar',
        'bio',
        'is_active',
        'last_login_at',
        'email_verified_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'last_login_at' => 'datetime',
            'is_active' => 'boolean',
            'learning_style' => 'string',
            'skill_level' => 'string',
            'preferred_duration' => 'integer',
            'interests' => 'array',
            'goals' => 'array',
            'availability' => 'string',
        ];
    }


    /**
     * Get role name attribute (robust single source of truth)
     */
    public function getRoleNameAttribute(): string
    {
        $this->loadMissing('role');

        $name =
            $this->role->name
            ?? Role::query()->whereKey($this->role_id)->value('name')
            ?? 'student';

        return strtolower(trim((string) $name));
    }

    /**
     * Robust role name resolution (same as policies)
     */
    public function roleName(): string
    {
        return $this->role_name;
    }

    /**
     * Check if user has specific role(s)
     */
    public function hasRole(string|array $roles): bool
    {
        $userRole = $this->roleName();
        $roleList = is_array($roles) ? $roles : [$roles];
        
        return in_array($userRole, $roleList, true);
    }

    /**
     * Check if user is admin
     */
    public function isAdmin(): bool
    {
        return $this->roleName() === 'admin';
    }

    /**
     * Check if user is instructor
     */
    public function isInstructor(): bool
    {
        return $this->roleName() === 'instructor';
    }

    /**
     * Check if user is student
     */
    public function isStudent(): bool
    {
        return $this->roleName() === 'student';
    }

    /**
     * Check if user is organization admin
     */
    public function isOrganizationAdmin(): bool
    {
        return $this->roleName() === 'organization_admin';
    }

    /**
     * Check if user is content manager
     */
    public function isContentManager(): bool
    {
        return $this->roleName() === 'content_manager';
    }

    /**
     * Check if user has any admin-level role
     */
    public function isAdminLevel(): bool
    {
        return $this->hasRole(['admin', 'organization_admin', 'content_manager']);
    }

    /**
     * Check if user can create content
     */
    public function canCreateContent(): bool
    {
        return $this->hasRole(['admin', 'instructor', 'organization_admin', 'content_manager']);
    }

    /**
     * Alias for backward compatibility - maps organization to organization_admin
     * @deprecated Use isOrganizationAdmin() instead
     */
    public function isOrganization(): bool
    {
        return $this->isOrganizationAdmin();
    }

    /**
     * Relationships for ZenithaLMS
     */
    public function role()
    {
        return $this->belongsTo(Role::class);
    }


    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function courses()
    {
        return $this->hasMany(Course::class, 'instructor_id');
    }

    public function enrollments()
    {
        return $this->hasMany(Enrollment::class);
    }

    public function studentProgress()
    {
        return $this->hasMany(StudentProgress::class);
    }

    public function adaptivePaths()
    {
        return $this->hasMany(AdaptivePath::class);
    }

    public function aiAssistants()
    {
        return $this->hasMany(AIAssistant::class);
    }

    /**
     * Check if user has specific permission
     */
    public function hasPermission($permission)
    {
        $role = $this->role;
        if (!$role) {
            return false;
        }

        $permissions = $role->permissions ?? [];
        return in_array($permission, $permissions);
    }

    /**
     * Get enrolled courses
     */
    public function enrolledCourses()
    {
        return $this->belongsToMany(Course::class, 'enrollments')
            ->withPivot(['status', 'progress_percentage', 'enrolled_at', 'completed_at'])
            ->wherePivot('status', 'active');
    }

    /**
     * Get completed courses
     */
    public function completedCourses()
    {
        return $this->belongsToMany(Course::class, 'enrollments')
            ->withPivot(['status', 'progress_percentage', 'enrolled_at', 'completed_at'])
            ->wherePivot('status', 'completed');
    }

    /**
     * Get progress statistics
     */
    public function getProgressStats()
    {
        $enrollments = $this->enrollments()->get();
        $totalProgress = $enrollments->sum('pivot.progress_percentage');
        $averageProgress = $enrollments->count() > 0 ? $totalProgress / $enrollments->count() : 0;
        
        return [
            'average_progress' => round($averageProgress, 1),
            'total_enrollments' => $enrollments->count(),
            'completed_courses' => $this->completedCourses()->count(),
            'in_progress' => $enrollments->where('pivot.status', 'active')->count()
        ];
    }

    /**
     * Get certificates
     */
    public function certificates()
    {
        return $this->hasMany(Certificate::class);
    }

    /**
     * Get user's orders
     */
    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Get user's wallet
     */
    public function wallet()
    {
        return $this->hasOne(Wallet::class);
    }

    /**
     * Get user's wallets
     */
    public function wallets()
    {
        return $this->hasMany(Wallet::class);
    }

    /**
     * Get user's refunds
     */
    public function refunds()
    {
        return $this->hasMany(Refund::class);
    }

    /**
     * Get user's authored ebooks
     */
    public function authoredEbooks()
    {
        return $this->hasMany(Ebook::class, 'author_id');
    }

    /**
     * Get user's ebook accesses
     */
    public function ebookAccesses()
    {
        return $this->hasMany(EbookAccess::class);
    }

    /**
     * Get learning profile
     */
    public function getLearningProfileAttribute()
    {
        return [
            'learning_style' => $this->learning_style ?? 'Visual',
            'skill_level' => $this->skill_level ?? 'Beginner',
            'preferred_duration' => $this->preferred_duration ?? 30,
            'interests' => $this->interests ? json_decode($this->interests, true) : ['Programming', 'Design'],
            'goals' => $this->goals ? json_decode($this->goals, true) : ['Career Advancement'],
            'availability' => $this->availability ?? 'flexible'
        ];
    }

    /**
     * Get avatar URL with fallback
     */
    public function getAvatarUrlAttribute(): string
    {
        $mediaService = app(MediaService::class);
        return $mediaService->publicUrl($this->avatar, '/images/default-avatar.png');
    }
}
