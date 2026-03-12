<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
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
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    // Relationships
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'user_roles');
    }

    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'user_permissions');
    }

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

    public function enrolledCourses()
    {
        return $this->belongsToMany(Course::class, 'enrollments')
            ->withPivot(['status', 'progress_percentage', 'enrolled_at', 'completed_at'])
            ->wherePivot('status', 'active');
    }

    public function completedCourses()
    {
        return $this->belongsToMany(Course::class, 'enrollments')
            ->withPivot(['status', 'progress_percentage', 'enrolled_at', 'completed_at'])
            ->wherePivot('status', 'completed');
    }

    public function refunds()
    {
        return $this->hasMany(Refund::class);
    }

    // Role methods
    public function hasRole($role)
    {
        if (is_string($role)) {
            return $this->roles()->where('slug', $role)->exists();
        }
        return $this->roles()->where('id', $role->id)->exists();
    }

    public function isAdmin()
    {
        return $this->hasRole('admin') || $this->hasRole('super_admin');
    }

    public function isInstructor()
    {
        return $this->hasRole('instructor');
    }

    public function isStudent()
    {
        return $this->hasRole('student');
    }

    public function getProgressStats()
    {
        $enrollments = $this->enrollments();
        $averageProgress = $enrollments->avg('progress_percentage') ?? 0;
        
        return [
            'average_progress' => round($averageProgress, 1),
            'total_enrollments' => $enrollments->count(),
            'completed_courses' => $this->completedCourses()->count(),
            'in_progress' => $enrollments->where('status', 'active')->count()
        ];
    }

    public function getAvatarUrlAttribute()
    {
        return $this->avatar ?: 'https://ui-avatars.com/api/?name=' . urlencode($this->name) . '&color=7F9CF5&background=EBF8FF';
    }

    public function isOnline()
    {
        return $this->last_login_at && $this->last_login_at->gt(now()->subMinutes(5));
    }

    public function getLastLoginAttribute()
    {
        return $this->last_login_at ? $this->last_login_at->diffForHumans() : 'Never';
    }

    /**
     * Get recommended courses for the user
     */
    public function getRecommendedCourses($limit = 6)
    {
        // Simple recommendation based on enrolled courses
        $enrolledCategories = $this->enrolledCourses()
            ->with('category')
            ->get()
            ->pluck('category.name')
            ->filter()
            ->unique();

        return Course::with(['category', 'instructor'])
            ->where('is_published', true)
            ->whereIn('category_id', function($query) use ($enrolledCategories) {
                return $query->select('categories.id')
                    ->from('categories')
                    ->whereIn('categories.name', $enrolledCategories);
            })
            ->whereNotIn('courses.id', $this->enrolledCourses()->pluck('courses.id'))
            ->limit($limit)
            ->get();
    }
}
