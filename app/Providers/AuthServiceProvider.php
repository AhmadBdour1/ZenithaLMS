<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\Quiz;
use App\Models\Forum;
use App\Models\VirtualClass;
use App\Policies\QuizPolicy;
use App\Policies\ForumPolicy;
use App\Policies\VirtualClassPolicy;

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
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // Define gates for simple role-based access
        Gate::define('admin', function ($user) {
            return $user->role === 'admin';
        });

        Gate::define('instructor', function ($user) {
            return $user->role === 'instructor';
        });

        Gate::define('student', function ($user) {
            return $user->role === 'student';
        });
    }
}
