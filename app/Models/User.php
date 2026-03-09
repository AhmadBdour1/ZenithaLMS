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

    // Many-to-many relationships for roles and permissions
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'user_roles');
    }

    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'user_permissions');
    }

    // Legacy role relationship for backward compatibility
    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    // Role and permission methods
    public function hasRole($role)
    {
        if (is_string($role)) {
            return $this->roles()->where('slug', $role)->exists();
        }
        
        return $this->roles()->where('id', $role->id)->exists();
    }

    public function hasAnyRole(array $roles)
    {
        foreach ($roles as $role) {
            if ($this->hasRole($role)) {
                return true;
            }
        }
        return false;
    }

    public function hasAllRoles(array $roles)
    {
        foreach ($roles as $role) {
            if (!$this->hasRole($role)) {
                return false;
            }
        }
        return true;
    }

    public function hasPermission($permission)
    {
        if (is_string($permission)) {
            return $this->permissions()->where('permissions.slug', $permission)->exists() ||
                   $this->permissions()->where('permissions.name', $permission)->exists() ||
                   $this->getPermissionsViaRoles()->where('permissions.slug', $permission)->exists() ||
                   $this->getPermissionsViaRoles()->where('permissions.name', $permission)->exists();
        }
        
        return $this->permissions()->where('permissions.id', $permission->id)->exists() ||
               $this->getPermissionsViaRoles()->where('permissions.id', $permission->id)->exists();
    }

    public function hasAnyPermission(array $permissions)
    {
        foreach ($permissions as $permission) {
            if ($this->hasPermission($permission)) {
                return true;
            }
        }
        return false;
    }

    public function hasAllPermissions(array $permissions)
    {
        foreach ($permissions as $permission) {
            if (!$this->hasPermission($permission)) {
                return false;
            }
        }
        return true;
    }

    public function getPermissionsViaRoles()
    {
        $roleIds = $this->roles()->pluck('roles.id');
        return Permission::whereIn('role_permissions.role_id', $roleIds)
            ->join('role_permissions', 'permissions.id', '=', 'role_permissions.permission_id')
            ->where('role_permissions.is_active', true)
            ->where('permissions.is_active', true)
            ->select('permissions.*');
    }

    public function getAllPermissions()
    {
        $directPermissions = $this->permissions()->active();
        $rolePermissions = $this->getPermissionsViaRoles();
        
        return $directPermissions->union($rolePermissions);
    }

    // Helper methods for role checking
    public function isSuperAdmin()
    {
        return $this->hasRole('super_admin');
    }

    public function isAdmin()
    {
        return $this->hasRole('admin') || $this->isSuperAdmin();
    }

    public function isManager()
    {
        return $this->hasRole('manager') || $this->isAdmin();
    }

    public function isInstructor()
    {
        return $this->hasRole('instructor');
    }

    public function isStudent()
    {
        return $this->hasRole('student');
    }

    public function isVendor()
    {
        return $this->hasRole('vendor');
    }

    public function getHighestRoleLevel()
    {
        return $this->roles()->max('level') ?: 0;
    }

    public function isAdminLevel()
    {
        return $this->isAdmin() || $this->getHighestRoleLevel() >= 75;
    }

    public function getPrimaryRole()
    {
        return $this->roles()->orderBy('level', 'desc')->first();
    }


    // Profile related methods
    public function getAvatarUrlAttribute()
    {
        if ($this->avatar) {
            return Storage::url($this->avatar);
        }
        
        return 'https://ui-avatars.com/api/?name=' . urlencode($this->name) . '&color=7F9CF5&background=EBF8FF';
    }

    public function getProfileUrlAttribute()
    {
        return route('profile.show', $this->username ?? $this->id);
    }

    public function getProfileViewsAttribute()
    {
        return $this->profile_views ?? 0;
    }

    public function incrementProfileViews()
    {
        $this->increment('profile_views');
    }

    public function isOnline()
    {
        return $this->last_login_at && $this->last_login_at->gt(now()->subMinutes(5));
    }

    public function getOnlineStatusAttribute()
    {
        return $this->isOnline() ? 'online' : 'offline';
    }

    public function getFullNameAttribute()
    {
        return $this->name;
    }

    public function getDisplayNameAttribute()
    {
        return $this->name;
    }

    public function getInitialsAttribute()
    {
        $words = explode(' ', $this->name);
        return strtoupper(implode('', array_map(fn($word) => mb_substr($word, 0, 1), $words)));
    }

    public function getJoinDateAttribute()
    {
        return $this->created_at->format('F j, Y');
    }

    public function getLastLoginAttribute()
    {
        return $this->last_login_at ? $this->last_login_at->diffForHumans() : 'Never';
    }

    // Social methods
    public function followers()
    {
        return $this->belongsToMany(User::class, 'user_follows', 'following_id', 'follower_id')
                    ->withTimestamps();
    }

    public function following()
    {
        return $this->belongsToMany(User::class, 'user_follows', 'follower_id', 'following_id')
                    ->withTimestamps();
    }

    public function isFollowing($user)
    {
        return $this->following()->where('following_id', $user->id)->exists();
    }

    public function isFollowedBy($user)
    {
        return $this->followers()->where('follower_id', $user->id)->exists();
    }

    public function follow($user)
    {
        if (!$this->isFollowing($user)) {
            $this->following()->attach($user->id);
        }
    }

    public function unfollow($user)
    {
        $this->following()->detach($user->id);
    }

    // Block methods
    public function blockedUsers()
    {
        return $this->belongsToMany(User::class, 'user_blocks', 'user_id', 'blocked_user_id')
                    ->withTimestamps();
    }

    public function blockedByUsers()
    {
        return $this->belongsToMany(User::class, 'user_blocks', 'blocked_user_id', 'user_id')
                    ->withTimestamps();
    }

    public function isBlocked($user)
    {
        return $this->blockedUsers()->where('blocked_user_id', $user->id)->exists();
    }

    public function block($user)
    {
        if (!$this->isBlocked($user)) {
            $this->blockedUsers()->attach($user->id);
            // Unfollow if following
            $this->unfollow($user);
        }
    }

    public function unblock($user)
    {
        $this->blockedUsers()->detach($user->id);
    }

    // Message methods
    public function sentMessages()
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    public function receivedMessages()
    {
        return $this->hasMany(Message::class, 'receiver_id');
    }

    public function unreadMessages()
    {
        return $this->receivedMessages()->where('is_read', false);
    }

    // Notification methods - Laravel's Notifiable trait provides these automatically
    // No need to override unless custom behavior is needed

    // Achievement methods
    public function achievements()
    {
        return $this->belongsToMany(Achievement::class, 'user_achievements')
                    ->withTimestamps();
    }

    public function hasAchievement($achievement)
    {
        return $this->achievements()->where('achievement_id', $achievement->id)->exists();
    }

    public function unlockAchievement($achievement)
    {
        if (!$this->hasAchievement($achievement)) {
            $this->achievements()->attach($achievement->id, [
                'unlocked_at' => now(),
            ]);
        }
    }

    // Badge methods
    public function badges()
    {
        return $this->belongsToMany(Badge::class, 'user_badges')
                    ->withTimestamps();
    }

    public function hasBadge($badge)
    {
        return $this->badges()->where('badge_id', $badge->id)->exists();
    }

    public function awardBadge($badge)
    {
        if (!$this->hasBadge($badge)) {
            $this->badges()->attach($badge->id, [
                'awarded_at' => now(),
            ]);
        }
    }

    // Report methods
    public function reports()
    {
        return $this->hasMany(UserReport::class, 'reported_user_id');
    }

    public function reportsMade()
    {
        return $this->hasMany(UserReport::class, 'reporter_user_id');
    }

    // Portfolio methods
    public function portfolioItems()
    {
        return $this->hasMany(PortfolioItem::class);
    }

    public function skills()
    {
        return $this->belongsToMany(Skill::class, 'user_skills')
                    ->withPivot('level')
                    ->withTimestamps();
    }

    public function experiences()
    {
        return $this->hasMany(Experience::class);
    }

    public function education()
    {
        return $this->hasMany(Education::class);
    }

    public function testimonials()
    {
        return $this->hasMany(Testimonial::class, 'user_id');
    }

    // Activity methods
    public function activities()
    {
        return $this->hasMany(UserActivity::class);
    }

    public function recentActivities($limit = 10)
    {
        return $this->activities()->orderBy('created_at', 'desc')->limit($limit)->get();
    }

    // Statistics methods
    public function getProfileCompletionPercentage()
    {
        $fields = ['name', 'email', 'bio', 'avatar', 'phone', 'country', 'website'];
        $completed = 0;
        
        foreach ($fields as $field) {
            if (!empty($this->$field)) {
                $completed++;
            }
        }
        
        return round(($completed / count($fields)) * 100);
    }

    public function getEngagementRate()
    {
        // Calculate engagement based on various factors
        $factors = [
            'courses_enrolled' => $this->enrollments()->count(),
            'courses_completed' => $this->enrollments()->whereNotNull('completed_at')->count(),
            'reviews_written' => $this->stuffReviews()->count(),
            'certificates_earned' => $this->certificates()->count(),
            'profile_views' => $this->profile_views,
        ];
        
        $score = 0;
        $maxScore = 0;
        
        foreach ($factors as $factor => $value) {
            $score += min($value, 10) * 10; // Cap each factor at 10
            $maxScore += 100;
        }
        
        return $maxScore > 0 ? round(($score / $maxScore) * 100) : 0;
    }

    public function getTrustScore()
    {
        // Calculate trust score based on various factors
        $factors = [
            'account_age' => $this->created_at->diffInDays(now()),
            'profile_completion' => $this->getProfileCompletionPercentage(),
            'verification_status' => $this->email_verified_at ? 100 : 0,
            'two_factor_enabled' => $this->two_factor_enabled ? 100 : 0,
            'reports_received' => max(0, 100 - ($this->reports()->count() * 10)),
            'followers_count' => min($this->followers()->count() * 2, 100),
        ];
        
        $score = array_sum($factors);
        return min(100, round($score / count($factors)));
    }

    // Helper methods
    public function canBeFollowed()
    {
        $authUser = auth()->user();
        
        if (!$authUser) {
            return false;
        }
        
        if ($authUser->id === $this->id) {
            return false;
        }
        
        if ($authUser->isBlocked($this) || $this->isBlocked($authUser)) {
            return false;
        }
        
        return true;
    }

    public function canBeMessaged()
    {
        $authUser = auth()->user();
        
        if (!$authUser) {
            return false;
        }
        
        if ($authUser->id === $this->id) {
            return false;
        }
        
        if ($authUser->isBlocked($this) || $this->isBlocked($authUser)) {
            return false;
        }
        
        $preferences = $this->preferences ?? [];
        return $preferences['allow_messages'] ?? true;
    }

    public function getPublicProfileData()
    {
        $preferences = $this->preferences ?? [];
        
        return [
            'id' => $this->id,
            'username' => $this->username,
            'name' => $this->name,
            'avatar' => $this->avatar_url,
            'bio' => $this->bio,
            'website' => $this->website,
            'country' => $this->country,
            'created_at' => $this->created_at,
            'followers_count' => $this->followers()->count(),
            'following_count' => $this->following()->count(),
            'courses_count' => $this->courses()->where('status', 'published')->count(),
            'certificates_count' => $this->certificates()->where('status', 'issued')->count(),
            'reviews_count' => $this->stuffReviews()->count(),
            'average_rating' => $this->stuffReviews()->avg('rating'),
            'is_online' => $this->isOnline(),
            'is_following' => auth()->user() ? auth()->user()->isFollowing($this) : false,
            'is_blocked' => auth()->user() ? auth()->user()->isBlocked($this) : false,
            'can_follow' => $this->canBeFollowed(),
            'can_message' => $this->canBeMessaged(),
        ];
    }

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
     * Get role name attribute (using many-to-many relationship)
     */
    public function getRoleNameAttribute(): string
    {
        $primaryRole = $this->getPrimaryRole();
        return $primaryRole ? strtolower($primaryRole->name) : 'student';
    }

    /**
     * Robust role name resolution (same as policies)
     */
    public function roleName(): string
    {
        return $this->role_name;
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

    }
