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
        // Register AI Service
        $this->app->singleton(ZenithaLmsAiService::class, function ($app) {
            return new ZenithaLmsAiService();
        });
        
        // Register Payment Service
        $this->app->singleton(ZenithaLmsPaymentService::class, function ($app) {
            return new ZenithaLmsPaymentService();
        });
        
        // Register Adaptive Learning Service
        $this->app->singleton(AdaptiveLearningService::class, function ($app) {
            return new AdaptiveLearningService();
        });
        
        // Register AI Service as alias
        $this->app->alias(ZenithaLmsAiService::class, 'ai.service');
        
        // Register Payment Service as alias
        $this->app->alias(ZenithaLmsPaymentService::class, 'payment.service');
        
        // Register Adaptive Learning Service as alias
        $this->app->alias(AdaptiveLearningService::class, 'adaptive.service');
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
        
        // Register role middleware
        $router->aliasMiddleware('role', \App\Http\Middleware\ZenithaLmsRoleMiddleware::class);
        
        // Register organization middleware
        $router->aliasMiddleware('organization', \App\Http\Middleware\ZenithaLmsOrganizationMiddleware::class);
        
        // Register custom middleware
        $router->aliasMiddleware('zenithalms.auth', \App\Http\Middleware\ZenithaLmsAuthMiddleware::class);
        $router->aliasMiddleware('zenithalms.api', \App\Http\Middleware\ZenithaLmsApiMiddleware::class);
        $router->aliasMiddleware('zenithalms.throttle', \App\Http\Middleware\ZenithaLmsThrottleMiddleware::class);
    }
    
    /**
     * Register event listeners
     */
    private function registerEventListeners()
    {
        // Register payment event listeners
        \Illuminate\Support\Facades\Event::listen(
            \App\Events\PaymentCompleted::class,
            \App\Listeners\SendPaymentCompletedNotification::class
        );
        
        \Illuminate\Support\Facades\Event::listen(
            \App\Events\PaymentFailed::class,
            \App\Listeners\SendPaymentFailedNotification::class
        );
        
        \Illuminate\Support\Facades\Event::listen(
            \App\Events\CourseEnrolled::class,
            \App\Listeners\SendCourseEnrollmentNotification::class
        );
        
        \Illuminate\Support\Facades\Event::listen(
            \App\Events\QuizCompleted::class,
            \App\Listeners\SendQuizCompletedNotification::class
        );
        
        \Illuminate\Support\Facades\Event::listen(
            \App\Events\CertificateIssued::class,
            \App\Listeners\SendCertificateIssuedNotification::class
        );
        
        \Illuminate\Support\Facades\Event::listen(
            \App\Events\VirtualClassStarted::class,
            \App\Listeners\SendVirtualClassStartedNotification::class
        );
        
        \Illuminate\Support\Facades\Event::listen(
            \App\Events\ForumPostCreated::class,
            \App\Listeners\SendForumPostNotification::class
        );
        
        \Illuminate\Support\Facades\Event::listen(
            \App\Events\BlogPublished::class,
            \App\Listeners\SendBlogPublishedNotification::class
        );
        
        \Illuminate\Support\Facades\Event::listen(
            \App\Events\EbookPurchased::class,
            \App\Listeners\SendEbookPurchasedNotification::class
        );
        
        \Illuminate\Support\Facades\Event::listen(
            \App\Events\AssignmentSubmitted::class,
            \App\Listeners\SendAssignmentSubmittedNotification::class
        );
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
            $view->with('categories', \App\Models\Category::where('is_active', true)->get());
        });
        
        // Share featured courses with homepage
        view()->composer('zenithalms.homepage.*', function ($view) {
            $view->with('featuredCourses', \App\Models\Course::where('is_featured', true)->where('is_published', true)->take(6)->get());
            $view->with('popularEbooks', \App\Models\Ebook::where('is_featured', true)->where('status', 'active')->take(4)->get());
            $view->with('latestBlogs', \App\Models\Blog::where('status', 'published')->latest()->take(3)->get());
        });
        
        // Share admin stats with admin views
        view()->composer(['zenithalms.admin.*', 'zenithalms.dashboard.admin'], function ($view) {
            $view->with('adminStats', [
                'total_users' => \App\Models\User::count(),
                'total_courses' => \App\Models\Course::count(),
                'total_enrollments' => \App\Models\Enrollment::count(),
                'total_revenue' => \App\Models\Payment::where('status', 'completed')->sum('amount'),
                'active_users' => \App\Models\User::where('is_active', true)->count(),
                'published_courses' => \App\Models\Course::where('is_published', true)->count(),
                'total_ebooks' => \App\Models\Ebook::count(),
                'total_quizzes' => \App\Models\Quiz::count(),
            ]);
        });
        
        // Share instructor stats with instructor views
        view()->composer(['zenithalms.dashboard.instructor'], function ($view) {
            if (auth()->check() && auth()->user()->role->name === 'instructor') {
                $instructor = auth()->user();
                $view->with('instructorStats', [
                    'total_courses' => $instructor->courses()->count(),
                    'total_students' => $instructor->courses()->withCount('enrollments')->get()->sum('enrollments_count'),
                    'total_revenue' => $instructor->courses()->withSum('enrollments.payments', 'amount')->get()->sum('enrollments_payments_sum_amount'),
                    'average_rating' => $instructor->courses()->avg('rating') ?? 0,
                    'total_quizzes' => $instructor->quizzes()->count(),
                    'total_assignments' => $instructor->assignments()->count(),
                    'total_virtual_classes' => $instructor->virtualClasses()->count(),
                ]);
            }
        });
        
        // Share student stats with student views
        view()->composer(['zenithalms.dashboard.student'], function ($view) {
            if (auth()->check() && auth()->user()->role->name === 'student') {
                $student = auth()->user();
                $view->with('studentStats', [
                    'enrolled_courses' => $student->enrollments()->count(),
                    'completed_courses' => $student->enrollments()->where('status', 'completed')->count(),
                    'in_progress_courses' => $student->enrollments()->where('status', 'active')->count(),
                    'average_progress' => $student->enrollments()->avg('progress_percentage') ?? 0,
                    'total_quiz_attempts' => $student->quizAttempts()->count(),
                    'average_quiz_score' => $student->quizAttempts()->avg('percentage') ?? 0,
                    'certificates_earned' => $student->certificates()->count(),
                    'favorite_ebooks' => $student->favoriteEbooks()->count(),
                ]);
            }
        });
    }
}
