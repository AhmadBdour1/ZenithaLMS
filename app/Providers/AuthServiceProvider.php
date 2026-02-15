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
            $user->loadMissing('role');

            $name =
                $user->role->name
                ?? Role::query()->whereKey($user->role_id)->value('name')
                ?? (string) ($user->role_name ?? '');

            return strtolower(trim((string) $name));
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
    }
}
