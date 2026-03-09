<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\Quiz;
use App\Models\Forum;
use App\Models\VirtualClass;
use App\Models\Ebook;
use App\Models\Course;
use App\Models\Blog;
use App\Models\Role;
use App\Models\Setting;
use App\Policies\QuizPolicy;
use App\Policies\ForumPolicy;
use App\Policies\VirtualClassPolicy;
use App\Policies\EbookPolicy;
use App\Policies\CoursePolicy;
use App\Policies\BlogPolicy;
use App\Policies\SettingsPolicy;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Quiz::class => QuizPolicy::class,
        Forum::class => ForumPolicy::class,
        VirtualClass::class => VirtualClassPolicy::class,
        Ebook::class => EbookPolicy::class,
        Course::class => CoursePolicy::class,
        Blog::class => BlogPolicy::class,
        Setting::class => SettingsPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // Helper function for robust role resolution
        $getRoleName = function ($user) {
            // Try to get role from many-to-many relationship first
            $roleName = $user->roles()->first()?->name;
            
            // Fallback to old single role system if needed
            if (!$roleName) {
                $roleName = $user->role->name
                    ?? Role::query()->whereKey($user->role_id)->value('name')
                    ?? (string) ($user->role_name ?? '');
            }

            return strtolower(trim((string) $roleName));
        };

        // Define gates for simple role-based access
        Gate::define('admin', function ($user) use ($getRoleName) {
            return $getRoleName($user) === 'admin';
        });

        Gate::define('instructor', function ($user) use ($getRoleName) {
            return $getRoleName($user) === 'instructor';
        });

        Gate::define('student', function ($user) use ($getRoleName) {
            return $getRoleName($user) === 'student';
        });

        // Define gates for settings
        Gate::define('view_settings', function ($user) use ($getRoleName) {
            return $getRoleName($user) === 'admin';
        });

        Gate::define('update_settings', function ($user) use ($getRoleName) {
            return $getRoleName($user) === 'admin';
        });

        // Define gates for virtual classes
        Gate::define('manage_virtual_classes', function ($user) use ($getRoleName) {
            return $getRoleName($user) === 'admin' || $getRoleName($user) === 'instructor';
        });

        Gate::define('view_virtual_classes', function ($user, $virtualClass) use ($getRoleName) {
            $userRole = $getRoleName($user);
            
            // Admin and instructor can view all classes
            if ($userRole === 'admin' || $userRole === 'instructor') {
                return true;
            }
            
            // Students can view classes they're enrolled in
            if ($userRole === 'student') {
                return $virtualClass->participants()
                    ->where('user_id', $user->id)
                    ->exists();
            }
            
            return false;
        });
    }
}
