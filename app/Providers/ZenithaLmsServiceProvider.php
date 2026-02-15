<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\ZenithaLmsAiService;
use App\Services\ZenithaLmsPaymentService;
use App\Services\AdaptiveLearningService;

class ZenithaLmsServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        // Register AI Service if class exists
        if (class_exists(ZenithaLmsAiService::class)) {
            $this->app->singleton(ZenithaLmsAiService::class, function ($app) {
                return new ZenithaLmsAiService();
            });
            $this->app->alias(ZenithaLmsAiService::class, 'ai.service');
        }
        
        // Register Payment Service if class exists
        if (class_exists(ZenithaLmsPaymentService::class)) {
            $this->app->singleton(ZenithaLmsPaymentService::class, function ($app) {
                return new ZenithaLmsPaymentService();
            });
            $this->app->alias(ZenithaLmsPaymentService::class, 'payment.service');
        }
        
        // Register Adaptive Learning Service if class exists
        if (class_exists(AdaptiveLearningService::class)) {
            $this->app->singleton(AdaptiveLearningService::class, function ($app) {
                return new AdaptiveLearningService();
            });
            $this->app->alias(AdaptiveLearningService::class, 'adaptive.service');
        }
    }
    
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        // Load custom configurations
        $this->mergeConfigFrom(
            config_path('zenithalms.php'), 
            'zenithalms'
        );
        
        // Load views
        $this->loadViewsFrom(
            resource_path('views/zenithalms'), 
            'zenithalms'
        );
        
        // Load translations
        $this->loadTranslationsFrom(
            resource_path('lang/zenithalms'), 
            'zenithalms'
        );
        
        // Load migrations
        $this->loadMigrationsFrom(
            database_path('migrations/zenithalms')
        );
        
        // Publish configurations
        if ($this->app->runningInConsole()) {
            $this->publishes([
                config_path('zenithalms.php') => config_path('zenithalms.php'),
            ], 'zenithalms-config');
            
            $this->publishes([
                resource_path('views/zenithalms') => resource_path('views/vendor/zenithalms'),
            ], 'zenithalms-views');
            
            $this->publishes([
                resource_path('lang/zenithalms') => resource_path('lang/vendor/zenithalms'),
            ], 'zenithalms-translations');
            
            $this->publishes([
                database_path('migrations/zenithalms') => database_path('migrations'),
            ], 'zenithalms-migrations');
        }
        
        // Register middleware
        $this->registerMiddleware();
        
        // Register event listeners
        $this->registerEventListeners();
        
        // Register view composers
        $this->registerViewComposers();
    }
    
    /**
     * Register middleware
     */
    private function registerMiddleware()
    {
        $router = $this->app['router'];
        
        // Register role middleware if class exists
        if (class_exists(\App\Http\Middleware\ZenithaLmsRoleMiddleware::class)) {
            $router->aliasMiddleware('role', \App\Http\Middleware\ZenithaLmsRoleMiddleware::class);
        }
        
        // Register organization middleware if class exists
        if (class_exists(\App\Http\Middleware\ZenithaLmsOrganizationMiddleware::class)) {
            $router->aliasMiddleware('organization', \App\Http\Middleware\ZenithaLmsOrganizationMiddleware::class);
        }
        
        // Register custom middleware if classes exist
        if (class_exists(\App\Http\Middleware\ZenithaLmsAuthMiddleware::class)) {
            $router->aliasMiddleware('zenithalms.auth', \App\Http\Middleware\ZenithaLmsAuthMiddleware::class);
        }
        
        if (class_exists(\App\Http\Middleware\ZenithaLmsApiMiddleware::class)) {
            $router->aliasMiddleware('zenithalms.api', \App\Http\Middleware\ZenithaLmsApiMiddleware::class);
        }
        
        if (class_exists(\App\Http\Middleware\ZenithaLmsThrottleMiddleware::class)) {
            $router->aliasMiddleware('zenithalms.throttle', \App\Http\Middleware\ZenithaLmsThrottleMiddleware::class);
        }
    }
    
    /**
     * Register event listeners
     */
    private function registerEventListeners()
    {
        // Register payment event listeners if classes exist
        if (class_exists(\App\Events\PaymentCompleted::class) && class_exists(\App\Listeners\SendPaymentCompletedNotification::class)) {
            \Illuminate\Support\Facades\Event::listen(
                \App\Events\PaymentCompleted::class,
                \App\Listeners\SendPaymentCompletedNotification::class
            );
        }
        
        if (class_exists(\App\Events\PaymentFailed::class) && class_exists(\App\Listeners\SendPaymentFailedNotification::class)) {
            \Illuminate\Support\Facades\Event::listen(
                \App\Events\PaymentFailed::class,
                \App\Listeners\SendPaymentFailedNotification::class
            );
        }
        
        if (class_exists(\App\Events\CourseEnrolled::class) && class_exists(\App\Listeners\SendCourseEnrollmentNotification::class)) {
            \Illuminate\Support\Facades\Event::listen(
                \App\Events\CourseEnrolled::class,
                \App\Listeners\SendCourseEnrollmentNotification::class
            );
        }
        
        if (class_exists(\App\Events\QuizCompleted::class) && class_exists(\App\Listeners\SendQuizCompletedNotification::class)) {
            \Illuminate\Support\Facades\Event::listen(
                \App\Events\QuizCompleted::class,
                \App\Listeners\SendQuizCompletedNotification::class
            );
        }
        
        if (class_exists(\App\Events\CertificateIssued::class) && class_exists(\App\Listeners\SendCertificateIssuedNotification::class)) {
            \Illuminate\Support\Facades\Event::listen(
                \App\Events\CertificateIssued::class,
                \App\Listeners\SendCertificateIssuedNotification::class
            );
        }
        
        if (class_exists(\App\Events\VirtualClassStarted::class) && class_exists(\App\Listeners\SendVirtualClassStartedNotification::class)) {
            \Illuminate\Support\Facades\Event::listen(
                \App\Events\VirtualClassStarted::class,
                \App\Listeners\SendVirtualClassStartedNotification::class
            );
        }
        
        if (class_exists(\App\Events\ForumPostCreated::class) && class_exists(\App\Listeners\SendForumPostNotification::class)) {
            \Illuminate\Support\Facades\Event::listen(
                \App\Events\ForumPostCreated::class,
                \App\Listeners\SendForumPostNotification::class
            );
        }
        
        if (class_exists(\App\Events\BlogPublished::class) && class_exists(\App\Listeners\SendBlogPublishedNotification::class)) {
            \Illuminate\Support\Facades\Event::listen(
                \App\Events\BlogPublished::class,
                \App\Listeners\SendBlogPublishedNotification::class
            );
        }
        
        if (class_exists(\App\Events\EbookPurchased::class) && class_exists(\App\Listeners\SendEbookPurchasedNotification::class)) {
            \Illuminate\Support\Facades\Event::listen(
                \App\Events\EbookPurchased::class,
                \App\Listeners\SendEbookPurchasedNotification::class
            );
        }
        
        if (class_exists(\App\Events\AssignmentSubmitted::class) && class_exists(\App\Listeners\SendAssignmentSubmittedNotification::class)) {
            \Illuminate\Support\Facades\Event::listen(
                \App\Events\AssignmentSubmitted::class,
                \App\Listeners\SendAssignmentSubmittedNotification::class
            );
        }
    }
    
    /**
     * Register view composers
     */
    private function registerViewComposers()
    {
        // Share user data with all views
        view()->composer('*', function ($view) {
            if (auth()->check()) {
                $view->with('currentUser', auth()->user());
                $view->with('unreadNotifications', auth()->user()->unreadNotifications()->count());
            }
        });
        
        // Share categories with course views
        view()->composer(['zenithalms.courses.*', 'zenithalms.ebooks.*'], function ($view) {
            if (\Illuminate\Support\Facades\Schema::hasTable('categories')) {
                $view->with('categories', \App\Models\Category::where('is_active', true)->get());
            }
        });
        
        // Share featured courses with homepage
        view()->composer('zenithalms.homepage.*', function ($view) {
            if (\Illuminate\Support\Facades\Schema::hasTable('courses')) {
                $view->with('featuredCourses', \App\Models\Course::where('is_featured', true)->where('is_published', true)->take(6)->get());
            }
            if (\Illuminate\Support\Facades\Schema::hasTable('ebooks')) {
                $view->with('popularEbooks', \App\Models\Ebook::where('is_featured', true)->where('status', 'active')->take(4)->get());
            }
            if (\Illuminate\Support\Facades\Schema::hasTable('blogs')) {
                $view->with('latestBlogs', \App\Models\Blog::where('status', 'published')->latest()->take(3)->get());
            }
        });
        
        // Share admin stats with admin views
        view()->composer(['zenithalms.admin.*', 'zenithalms.dashboard.admin'], function ($view) {
            $stats = [];
            if (\Illuminate\Support\Facades\Schema::hasTable('users')) {
                $stats['total_users'] = \App\Models\User::count();
                $stats['active_users'] = \App\Models\User::where('is_active', true)->count();
            }
            if (\Illuminate\Support\Facades\Schema::hasTable('courses')) {
                $stats['total_courses'] = \App\Models\Course::count();
                $stats['published_courses'] = \App\Models\Course::where('is_published', true)->count();
            }
            if (\Illuminate\Support\Facades\Schema::hasTable('enrollments')) {
                $stats['total_enrollments'] = \App\Models\Enrollment::count();
            }
            if (\Illuminate\Support\Facades\Schema::hasTable('payments')) {
                $stats['total_revenue'] = \App\Models\Payment::where('status', 'completed')->sum('amount');
            }
            if (\Illuminate\Support\Facades\Schema::hasTable('ebooks')) {
                $stats['total_ebooks'] = \App\Models\Ebook::count();
            }
            if (\Illuminate\Support\Facades\Schema::hasTable('quizzes')) {
                $stats['total_quizzes'] = \App\Models\Quiz::count();
            }
            $view->with('adminStats', $stats);
        });
        
        // Share instructor stats with instructor views
        view()->composer(['zenithalms.dashboard.instructor'], function ($view) {
            if (auth()->check() && auth()->user()->role_name === 'instructor') {
                $instructor = auth()->user();
                $stats = [];
                if (\Illuminate\Support\Facades\Schema::hasTable('courses')) {
                    $stats['total_courses'] = $instructor->courses()->count();
                    $stats['average_rating'] = $instructor->courses()->avg('rating') ?? 0;
                }
                if (\Illuminate\Support\Facades\Schema::hasTable('enrollments')) {
                    $stats['total_students'] = $instructor->courses()->withCount('enrollments')->get()->sum('enrollments_count');
                }
                if (\Illuminate\Support\Facades\Schema::hasTable('payments')) {
                    $stats['total_revenue'] = $instructor->courses()->withSum('enrollments.payments', 'amount')->get()->sum('enrollments_payments_sum_amount');
                }
                if (\Illuminate\Support\Facades\Schema::hasTable('quizzes')) {
                    $stats['total_quizzes'] = $instructor->quizzes()->count();
                }
                if (\Illuminate\Support\Facades\Schema::hasTable('assignments')) {
                    $stats['total_assignments'] = $instructor->assignments()->count();
                }
                if (\Illuminate\Support\Facades\Schema::hasTable('virtual_classes')) {
                    $stats['total_virtual_classes'] = $instructor->virtualClasses()->count();
                }
                $view->with('instructorStats', $stats);
            }
        });
        
        // Share student stats with student views
        view()->composer(['zenithalms.dashboard.student'], function ($view) {
            if (auth()->check() && auth()->user()->role_name === 'student') {
                $student = auth()->user();
                $stats = [];
                if (\Illuminate\Support\Facades\Schema::hasTable('enrollments')) {
                    $stats['enrolled_courses'] = $student->enrollments()->count();
                    $stats['completed_courses'] = $student->enrollments()->where('status', 'completed')->count();
                    $stats['in_progress_courses'] = $student->enrollments()->where('status', 'active')->count();
                    $stats['average_progress'] = $student->enrollments()->avg('progress_percentage') ?? 0;
                }
                if (\Illuminate\Support\Facades\Schema::hasTable('certificates')) {
                    $stats['certificates_earned'] = $student->certificates()->count();
                }
                // TODO fields that need implementation
                $stats['total_quiz_attempts'] = 0;
                $stats['average_quiz_score'] = 0;
                $stats['favorite_ebooks'] = 0;
                $view->with('studentStats', $stats);
            }
        });
    }
}
