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
     * Get role name attribute (single source of truth)
     */
    public function getRoleNameAttribute()
    {
        return $this->role?->name ?? 'student';
    }

    /**
     * Check if user is admin
     */
    public function isAdmin()
    {
        return in_array($this->role_name, ['super_admin', 'admin']);
    }

    /**
     * Check if user is instructor
     */
    public function isInstructor()
    {
        return $this->role_name === 'instructor';
    }

    /**
     * Check if user is student
     */
    public function isStudent()
    {
        return $this->role_name === 'student';
    }

    /**
     * Check if user is organization
     */
    public function isOrganization()
    {
        return $this->role_name === 'organization';
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
        return $mediaService->url($this->avatar, '/images/default-avatar.png');
    }
}
