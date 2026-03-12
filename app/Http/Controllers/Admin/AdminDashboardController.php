<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Course;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\PaymentMethod;
use App\Models\Payout;
use App\Models\AuraProduct;
use App\Models\AuraCategory;
use App\Models\AuraOrder;
use App\Models\AuraPage;
use App\Models\AuraTemplate;
use App\Models\Certificate;
use App\Models\CertificateTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AdminDashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'admin']);
    }

    /**
     * Get comprehensive dashboard data
     */
    public function index()
    {
        $dashboard = [
            'overview' => $this->getOverviewStats(),
            'users' => $this->getUsersStats(),
            'courses' => $this->getCoursesStats(),
            'subscriptions' => $this->getSubscriptionsStats(),
            'marketplace' => $this->getMarketplaceStats(),
            'pagebuilder' => $this->getPageBuilderStats(),
            'certificates' => $this->getCertificatesStats(),
            'payments' => $this->getPaymentsStats(),
            'payouts' => $this->getPayoutsStats(),
            'analytics' => $this->getAnalyticsStats(),
            'recent_activities' => $this->getRecentActivities(),
            'system_health' => $this->getSystemHealth(),
            'revenue' => $this->getRevenueStats(),
            'growth' => $this->getGrowthStats(),
        ];

        return response()->json([
            'success' => true,
            'data' => $dashboard,
        ]);
    }

    /**
     * Overview statistics
     */
    private function getOverviewStats()
    {
        return [
            'total_users' => User::count(),
            'active_users' => User::where('last_login_at', '>=', now()->subDays(30))->count(),
            'total_courses' => Course::count(),
            'published_courses' => Course::where('status', 'published')->count(),
            'total_subscriptions' => Subscription::count(),
            'active_subscriptions' => Subscription::where('status', 'active')->count(),
            'total_revenue' => $this->getTotalRevenue(),
            'monthly_revenue' => $this->getMonthlyRevenue(),
            'total_orders' => AuraOrder::count(),
            'pending_orders' => AuraOrder::where('status', 'pending')->count(),
            'total_certificates' => Certificate::count(),
            'issued_certificates' => Certificate::where('status', 'issued')->count(),
        ];
    }

    /**
     * Users statistics
     */
    private function getUsersStats()
    {
        $now = now();
        
        return [
            'total' => User::count(),
            'new_today' => User::whereDate('created_at', $now->toDateString())->count(),
            'new_this_week' => User::whereBetween('created_at', [$now->startOfWeek(), $now->endOfWeek()])->count(),
            'new_this_month' => User::whereMonth('created_at', $now->month)->whereYear('created_at', $now->year)->count(),
            'new_this_year' => User::whereYear('created_at', $now->year)->count(),
            'active_today' => User::whereDate('last_login_at', $now->toDateString())->count(),
            'active_this_week' => User::where('last_login_at', '>=', $now->subDays(7))->count(),
            'active_this_month' => User::where('last_login_at', '>=', $now->subMonth())->count(),
            'by_role' => [
                'admin' => User::where('role', 'admin')->count(),
                'instructor' => User::where('role', 'instructor')->count(),
                'student' => User::where('role', 'student')->count(),
                'vendor' => User::where('role', 'vendor')->count(),
            ],
            'by_status' => [
                'active' => User::where('status', 'active')->count(),
                'inactive' => User::where('status', 'inactive')->count(),
                'suspended' => User::where('status', 'suspended')->count(),
                'banned' => User::where('status', 'banned')->count(),
            ],
            'by_country' => User::select('country', DB::raw('count(*) as count'))
                ->whereNotNull('country')
                ->groupBy('country')
                ->orderBy('count', 'desc')
                ->take(10)
                ->get(),
            'registration_trend' => $this->getUserRegistrationTrend(),
        ];
    }

    /**
     * Courses statistics
     */
    private function getCoursesStats()
    {
        $now = now();
        
        return [
            'total' => Course::count(),
            'published' => Course::where('status', 'published')->count(),
            'draft' => Course::where('status', 'draft')->count(),
            'archived' => Course::where('status', 'archived')->count(),
            'new_today' => Course::whereDate('created_at', $now->toDateString())->count(),
            'new_this_week' => Course::whereBetween('created_at', [$now->startOfWeek(), $now->endOfWeek()])->count(),
            'new_this_month' => Course::whereMonth('created_at', $now->month)->whereYear('created_at', $now->year)->count(),
            'by_category' => Course::select('category', DB::raw('count(*) as count'))
                ->groupBy('category')
                ->orderBy('count', 'desc')
                ->take(10)
                ->get(),
            'by_level' => [
                'beginner' => Course::where('level', 'beginner')->count(),
                'intermediate' => Course::where('level', 'intermediate')->count(),
                'advanced' => Course::where('level', 'advanced')->count(),
                'all_levels' => Course::where('level', 'all_levels')->count(),
            ],
            'by_price' => [
                'free' => Course::where('price', 0)->count(),
                'paid' => Course::where('price', '>', 0)->count(),
                'average_price' => Course::where('price', '>', 0)->avg('price'),
            ],
            'enrollment_stats' => [
                'total_enrollments' => DB::table('course_user')->count(),
                'enrollments_today' => DB::table('course_user')->whereDate('created_at', $now->toDateString())->count(),
                'enrollments_this_week' => DB::table('course_user')->whereBetween('created_at', [$now->startOfWeek(), $now->endOfWeek()])->count(),
                'enrollments_this_month' => DB::table('course_user')->whereMonth('created_at', $now->month)->whereYear('created_at', $now->year)->count(),
                'average_enrollments_per_course' => Course::withCount('users')->avg('users_count'),
            ],
            'popular_courses' => Course::withCount('users')
                ->orderBy('users_count', 'desc')
                ->take(10)
                ->get(['id', 'title', 'users_count', 'price', 'instructor_id']),
        ];
    }

    /**
     * Subscriptions statistics
     */
    private function getSubscriptionsStats()
    {
        $now = now();
        
        return [
            'total' => Subscription::count(),
            'active' => Subscription::where('status', 'active')->count(),
            'trialing' => Subscription::where('status', 'trialing')->count(),
            'canceled' => Subscription::where('status', 'canceled')->count(),
            'expired' => Subscription::where('status', 'expired')->count(),
            'past_due' => Subscription::where('status', 'past_due')->count(),
            'new_today' => Subscription::whereDate('created_at', $now->toDateString())->count(),
            'new_this_week' => Subscription::whereBetween('created_at', [$now->startOfWeek(), $now->endOfWeek()])->count(),
            'new_this_month' => Subscription::whereMonth('created_at', $now->month)->whereYear('created_at', $now->year)->count(),
            'by_plan' => Subscription::select('subscription_plans.name', DB::raw('count(*) as count'))
                ->join('subscription_plans', 'subscriptions.plan_id', '=', 'subscription_plans.id')
                ->groupBy('subscription_plans.id', 'subscription_plans.name')
                ->orderBy('count', 'desc')
                ->get(),
            'by_billing_cycle' => [
                'monthly' => Subscription::where('billing_cycle', 'monthly')->count(),
                'yearly' => Subscription::where('billing_cycle', 'yearly')->count(),
                'quarterly' => Subscription::where('billing_cycle', 'quarterly')->count(),
                'lifetime' => Subscription::where('billing_cycle', 'lifetime')->count(),
            ],
            'revenue_stats' => [
                'monthly_recurring_revenue' => Subscription::active()->where('billing_cycle', 'monthly')->sum('price'),
                'yearly_recurring_revenue' => Subscription::active()->where('billing_cycle', 'yearly')->sum('price'),
                'total_recurring_revenue' => Subscription::active()->sum('price'),
                'average_revenue_per_subscription' => Subscription::active()->avg('price'),
            ],
            'churn_stats' => [
                'cancellations_today' => Subscription::whereDate('canceled_at', $now->toDateString())->count(),
                'cancellations_this_week' => Subscription::whereBetween('canceled_at', [$now->startOfWeek(), $now->endOfWeek()])->count(),
                'cancellations_this_month' => Subscription::whereMonth('canceled_at', $now->month)->whereYear('canceled_at', $now->year)->count(),
                'churn_rate' => $this->calculateChurnRate(),
            ],
        ];
    }

    /**
     * Marketplace statistics
     */
    private function getMarketplaceStats()
    {
        $now = now();
        
        return [
            'products' => [
                'total' => AuraProduct::count(),
                'active' => AuraProduct::where('status', 'active')->count(),
                'inactive' => AuraProduct::where('status', 'inactive')->count(),
                'draft' => AuraProduct::where('status', 'draft')->count(),
                'new_today' => AuraProduct::whereDate('created_at', $now->toDateString())->count(),
                'new_this_week' => AuraProduct::whereBetween('created_at', [$now->startOfWeek(), $now->endOfWeek()])->count(),
                'new_this_month' => AuraProduct::whereMonth('created_at', $now->month)->whereYear('created_at', $now->year)->count(),
            ],
            'orders' => [
                'total' => AuraOrder::count(),
                'pending' => AuraOrder::where('status', 'pending')->count(),
                'processing' => AuraOrder::where('status', 'processing')->count(),
                'completed' => AuraOrder::where('status', 'completed')->count(),
                'cancelled' => AuraOrder::where('status', 'cancelled')->count(),
                'refunded' => AuraOrder::where('status', 'refunded')->count(),
                'new_today' => AuraOrder::whereDate('created_at', $now->toDateString())->count(),
                'new_this_week' => AuraOrder::whereBetween('created_at', [$now->startOfWeek(), $now->endOfWeek()])->count(),
                'new_this_month' => AuraOrder::whereMonth('created_at', $now->month)->whereYear('created_at', $now->year)->count(),
            ],
            'categories' => [
                'total' => AuraCategory::count(),
                'active' => AuraCategory::where('is_active', true)->count(),
                'product_counts' => AuraCategory::withCount('products')->get(),
            ],
            'revenue' => [
                'total_revenue' => AuraOrder::where('status', 'completed')->sum('total_amount'),
                'today_revenue' => AuraOrder::where('status', 'completed')->whereDate('created_at', $now->toDateString())->sum('total_amount'),
                'week_revenue' => AuraOrder::where('status', 'completed')->whereBetween('created_at', [$now->startOfWeek(), $now->endOfWeek()])->sum('total_amount'),
                'month_revenue' => AuraOrder::where('status', 'completed')->whereMonth('created_at', $now->month)->whereYear('created_at', $now->year)->sum('total_amount'),
                'average_order_value' => AuraOrder::where('status', 'completed')->avg('total_amount'),
            ],
            'top_products' => AuraProduct::withCount('orders')
                ->orderBy('orders_count', 'desc')
                ->take(10)
                ->get(['id', 'name', 'price', 'orders_count', 'vendor_id']),
            'top_vendors' => User::where('role', 'vendor')
                ->withCount('auraProducts')
                ->orderBy('aura_products_count', 'desc')
                ->take(10)
                ->get(['id', 'name', 'aura_products_count']),
        ];
    }

    /**
     * Page Builder statistics
     */
    private function getPageBuilderStats()
    {
        $now = now();
        
        return [
            'pages' => [
                'total' => AuraPage::count(),
                'published' => AuraPage::where('status', 'published')->count(),
                'draft' => AuraPage::where('status', 'draft')->count(),
                'archived' => AuraPage::where('status', 'archived')->count(),
                'homepage' => AuraPage::where('is_homepage', true)->count(),
                'new_today' => AuraPage::whereDate('created_at', $now->toDateString())->count(),
                'new_this_week' => AuraPage::whereBetween('created_at', [$now->startOfWeek(), $now->endOfWeek()])->count(),
                'new_this_month' => AuraPage::whereMonth('created_at', $now->month)->whereYear('created_at', $now->year)->count(),
            ],
            'templates' => [
                'total' => AuraTemplate::count(),
                'active' => AuraTemplate::where('is_active', true)->count(),
                'free' => AuraTemplate::where('is_premium', false)->count(),
                'premium' => AuraTemplate::where('is_premium', true)->count(),
                'featured' => AuraTemplate::where('is_featured', true)->count(),
                'by_category' => AuraTemplate::select('category', DB::raw('count(*) as count'))
                    ->groupBy('category')
                    ->orderBy('count', 'desc')
                    ->get(),
            ],
            'usage_stats' => [
                'total_views' => AuraPage::sum('view_count'),
                'pages_with_blocks' => AuraPage::whereNotNull('blocks_data')->count(),
                'pages_using_templates' => AuraPage::whereNotNull('template_id')->count(),
                'average_blocks_per_page' => AuraPage::whereNotNull('blocks_data')->avg(DB::raw('JSON_LENGTH(blocks_data)')),
            ],
            'popular_pages' => AuraPage::orderBy('view_count', 'desc')
                ->take(10)
                ->get(['id', 'title', 'view_count', 'status', 'created_at']),
            'popular_templates' => AuraTemplate::withCount('pages')
                ->orderBy('pages_count', 'desc')
                ->take(10)
                ->get(['id', 'name', 'pages_count', 'is_premium']),
        ];
    }

    /**
     * Certificates statistics
     */
    private function getCertificatesStats()
    {
        $now = now();
        
        return [
            'certificates' => [
                'total' => Certificate::count(),
                'issued' => Certificate::where('status', 'issued')->count(),
                'pending' => Certificate::where('status', 'pending')->count(),
                'revoked' => Certificate::where('status', 'revoked')->count(),
                'expired' => Certificate::where('status', 'expired')->count(),
                'issued_today' => Certificate::whereDate('issued_at', $now->toDateString())->count(),
                'issued_this_week' => Certificate::whereBetween('issued_at', [$now->startOfWeek(), $now->endOfWeek()])->count(),
                'issued_this_month' => Certificate::whereMonth('issued_at', $now->month)->whereYear('issued_at', $now->year)->count(),
            ],
            'templates' => [
                'total' => CertificateTemplate::count(),
                'active' => CertificateTemplate::where('is_active', true)->count(),
                'free' => CertificateTemplate::where('is_premium', false)->count(),
                'premium' => CertificateTemplate::where('is_premium', true)->count(),
                'by_category' => CertificateTemplate::select('category', DB::raw('count(*) as count'))
                    ->groupBy('category')
                    ->orderBy('count', 'desc')
                    ->get(),
            ],
            'verification_stats' => [
                'total_verifications' => DB::table('certificate_verifications')->count(),
                'verifications_today' => DB::table('certificate_verifications')->whereDate('created_at', $now->toDateString())->count(),
                'verifications_this_week' => DB::table('certificate_verifications')->whereBetween('created_at', [$now->startOfWeek(), $now->endOfWeek()])->count(),
                'verifications_this_month' => DB::table('certificate_verifications')->whereMonth('created_at', $now->month)->whereYear('created_at', $now->year)->count(),
            ],
            'popular_templates' => CertificateTemplate::withCount('certificates')
                ->orderBy('certificates_count', 'desc')
                ->take(10)
                ->get(['id', 'name', 'certificates_count', 'is_premium']),
        ];
    }

    /**
     * Payments statistics
     */
    private function getPaymentsStats()
    {
        $now = now();
        
        return [
            'payment_methods' => [
                'total' => PaymentMethod::count(),
                'active' => PaymentMethod::where('is_verified', true)->count(),
                'cards' => PaymentMethod::where('type', 'card')->count(),
                'bank_accounts' => PaymentMethod::where('type', 'bank_account')->count(),
                'paypal' => PaymentMethod::where('type', 'paypal')->count(),
                'crypto' => PaymentMethod::where('type', 'crypto')->count(),
                'by_provider' => PaymentMethod::select('provider', DB::raw('count(*) as count'))
                    ->groupBy('provider')
                    ->orderBy('count', 'desc')
                    ->get(),
            ],
            'transactions' => [
                'total' => DB::table('payments')->count(),
                'successful' => DB::table('payments')->where('status', 'completed')->count(),
                'failed' => DB::table('payments')->where('status', 'failed')->count(),
                'pending' => DB::table('payments')->where('status', 'pending')->count(),
                'today' => DB::table('payments')->whereDate('created_at', $now->toDateString())->count(),
                'this_week' => DB::table('payments')->whereBetween('created_at', [$now->startOfWeek(), $now->endOfWeek()])->count(),
                'this_month' => DB::table('payments')->whereMonth('created_at', $now->month)->whereYear('created_at', $now->year)->count(),
                'total_amount' => DB::table('payments')->where('status', 'completed')->sum('amount'),
                'today_amount' => DB::table('payments')->where('status', 'completed')->whereDate('created_at', $now->toDateString())->sum('amount'),
                'this_week_amount' => DB::table('payments')->where('status', 'completed')->whereBetween('created_at', [$now->startOfWeek(), $now->endOfWeek()])->sum('amount'),
                'this_month_amount' => DB::table('payments')->where('status', 'completed')->whereMonth('created_at', $now->month)->whereYear('created_at', $now->year)->sum('amount'),
            ],
            'by_payment_method' => DB::table('payments')
                ->select('payment_gateway', DB::raw('count(*) as count, SUM(amount) as total'))
                ->where('status', 'completed')
                ->groupBy('payment_gateway')
                ->orderBy('total', 'desc')
                ->get(),
        ];
    }

    /**
     * Payouts statistics
     */
    private function getPayoutsStats()
    {
        $now = now();
        
        return [
            'payouts' => [
                'total' => Payout::count(),
                'pending' => Payout::where('status', 'pending')->count(),
                'processing' => Payout::where('status', 'processing')->count(),
                'completed' => Payout::where('status', 'completed')->count(),
                'failed' => Payout::where('status', 'failed')->count(),
                'cancelled' => Payout::where('status', 'cancelled')->count(),
                'requested_today' => Payout::whereDate('requested_at', $now->toDateString())->count(),
                'requested_this_week' => Payout::whereBetween('requested_at', [$now->startOfWeek(), $now->endOfWeek()])->count(),
                'requested_this_month' => Payout::whereMonth('requested_at', $now->month)->whereYear('requested_at', $now->year)->count(),
            ],
            'amounts' => [
                'total_requested' => Payout::sum('amount'),
                'total_completed' => Payout::where('status', 'completed')->sum('net_amount'),
                'total_pending' => Payout::where('status', 'pending')->sum('amount'),
                'total_processing' => Payout::where('status', 'processing')->sum('amount'),
                'today_requested' => Payout::whereDate('requested_at', $now->toDateString())->sum('amount'),
                'week_requested' => Payout::whereBetween('requested_at', [$now->startOfWeek(), $now->endOfWeek()])->sum('amount'),
                'month_requested' => Payout::whereMonth('requested_at', $now->month)->whereYear('requested_at', $now->year)->sum('amount'),
            ],
            'by_method' => Payout::select('payment_method_type', DB::raw('count(*) as count, SUM(amount) as total'))
                ->groupBy('payment_method_type')
                ->orderBy('total', 'desc')
                ->get(),
            'by_type' => Payout::select('type', DB::raw('count(*) as count, SUM(amount) as total'))
                ->groupBy('type')
                ->orderBy('total', 'desc')
                ->get(),
            'fees' => [
                'total_processing_fees' => Payout::sum('processing_fee'),
                'total_tax_withheld' => Payout::sum('tax_amount'),
                'average_processing_fee' => Payout::avg('processing_fee'),
                'average_tax_rate' => Payout::where('tax_amount', '>', 0)->avg(DB::raw('tax_amount / amount')),
            ],
        ];
    }

    /**
     * Analytics statistics
     */
    private function getAnalyticsStats()
    {
        return [
            'page_views' => [
                'total' => DB::table('analytics')->sum('page_views'),
                'today' => DB::table('analytics')->whereDate('date', now()->toDateString())->sum('page_views'),
                'this_week' => DB::table('analytics')->whereBetween('date', [now()->startOfWeek(), now()->endOfWeek()])->sum('page_views'),
                'this_month' => DB::table('analytics')->whereMonth('date', now()->month)->whereYear('date', now()->year)->sum('page_views'),
                'unique_visitors' => DB::table('analytics')->sum('unique_visitors'),
                'bounce_rate' => DB::table('analytics')->avg('bounce_rate'),
                'avg_session_duration' => DB::table('analytics')->avg('avg_session_duration'),
            ],
            'conversion_rates' => [
                'user_registration' => $this->calculateRegistrationConversionRate(),
                'course_enrollment' => $this->calculateEnrollmentConversionRate(),
                'subscription_signup' => $this->calculateSubscriptionConversionRate(),
                'purchase_conversion' => $this->calculatePurchaseConversionRate(),
            ],
            'traffic_sources' => [
                'direct' => DB::table('analytics')->sum('direct_traffic'),
                'organic' => DB::table('analytics')->sum('organic_traffic'),
                'social' => DB::table('analytics')->sum('social_traffic'),
                'referral' => DB::table('analytics')->sum('referral_traffic'),
                'paid' => DB::table('analytics')->sum('paid_traffic'),
            ],
            'top_pages' => DB::table('analytics')
                ->select('page_url', DB::raw('SUM(page_views) as total_views'))
                ->groupBy('page_url')
                ->orderBy('total_views', 'desc')
                ->take(10)
                ->get(),
        ];
    }

    /**
     * Recent activities
     */
    private function getRecentActivities()
    {
        $activities = collect();

        // Recent user registrations
        $users = User::latest()->take(5)->get(['id', 'name', 'email', 'created_at']);
        $users->each(function ($user) use ($activities) {
            $activities->push([
                'type' => 'user_registered',
                'title' => 'New user registered',
                'description' => "{$user->name} ({$user->email})",
                'timestamp' => $user->created_at,
                'icon' => 'user-plus',
                'color' => 'green',
            ]);
        });

        // Recent course publications
        $courses = Course::where('status', 'published')->latest()->take(5)->get(['id', 'title', 'instructor_id', 'updated_at']);
        $courses->each(function ($course) use ($activities) {
            $activities->push([
                'type' => 'course_published',
                'title' => 'Course published',
                'description' => $course->title,
                'timestamp' => $course->updated_at,
                'icon' => 'book',
                'color' => 'blue',
            ]);
        });

        // Recent orders
        $orders = AuraOrder::latest()->take(5)->get(['id', 'total_amount', 'user_id', 'created_at']);
        $orders->each(function ($order) use ($activities) {
            $activities->push([
                'type' => 'order_placed',
                'title' => 'New order placed',
                'description' => "Order #{$order->id} - \${$order->total_amount}",
                'timestamp' => $order->created_at,
                'icon' => 'shopping-cart',
                'color' => 'purple',
            ]);
        });

        // Recent subscriptions
        $subscriptions = Subscription::latest()->take(5)->get(['id', 'plan_id', 'user_id', 'created_at']);
        $subscriptions->each(function ($subscription) use ($activities) {
            $activities->push([
                'type' => 'subscription_created',
                'title' => 'New subscription',
                'description' => "Subscription #{$subscription->id}",
                'timestamp' => $subscription->created_at,
                'icon' => 'credit-card',
                'color' => 'indigo',
            ]);
        });

        return $activities->sortByDesc('timestamp')->take(20)->values();
    }

    /**
     * System health
     */
    private function getSystemHealth()
    {
        return [
            'server' => [
                'uptime' => $this->getServerUptime(),
                'cpu_usage' => $this->getCpuUsage(),
                'memory_usage' => $this->getMemoryUsage(),
                'disk_usage' => $this->getDiskUsage(),
                'load_average' => sys_getloadavg(),
            ],
            'database' => [
                'connections' => DB::select('SHOW STATUS LIKE "Threads_connected"')[0]->Value,
                'slow_queries' => DB::select('SHOW STATUS LIKE "Slow_queries"')[0]->Value,
                'queries_per_second' => $this->getQueriesPerSecond(),
                'size' => $this->getDatabaseSize(),
            ],
            'cache' => [
                'redis_connected' => $this->checkRedisConnection(),
                'cache_hit_rate' => $this->getCacheHitRate(),
                'memory_usage' => $this->getRedisMemoryUsage(),
            ],
            'services' => [
                'stripe' => $this->checkStripeConnection(),
                'paypal' => $this->checkPayPalConnection(),
                'email' => $this->checkEmailService(),
                'storage' => $this->checkStorageService(),
            ],
            'errors' => [
                'error_rate' => $this->getErrorRate(),
                'critical_errors' => $this->getCriticalErrors(),
                'warnings' => $this->getWarnings(),
            ],
        ];
    }

    /**
     * Revenue statistics
     */
    private function getRevenueStats()
    {
        $now = now();
        
        return [
            'total_revenue' => $this->getTotalRevenue(),
            'today_revenue' => $this->getTodayRevenue(),
            'week_revenue' => $this->getWeekRevenue(),
            'month_revenue' => $this->getMonthRevenue(),
            'year_revenue' => $this->getYearRevenue(),
            'revenue_by_source' => [
                'courses' => $this->getCourseRevenue(),
                'subscriptions' => $this->getSubscriptionRevenue(),
                'marketplace' => $this->getMarketplaceRevenue(),
                'certificates' => $this->getCertificateRevenue(),
                'pagebuilder' => $this->getPageBuilderRevenue(),
            ],
            'revenue_trend' => $this->getRevenueTrend(),
            'average_revenue_per_user' => $this->getAverageRevenuePerUser(),
            'revenue_growth' => $this->getRevenueGrowth(),
        ];
    }

    /**
     * Growth statistics
     */
    private function getGrowthStats()
    {
        return [
            'user_growth' => $this->getUserGrowth(),
            'course_growth' => $this->getCourseGrowth(),
            'revenue_growth' => $this->getRevenueGrowth(),
            'subscription_growth' => $this->getSubscriptionGrowth(),
            'marketplace_growth' => $this->getMarketplaceGrowth(),
            'growth_projections' => $this->getGrowthProjections(),
        ];
    }

    // Helper methods for calculations
    private function getTotalRevenue()
    {
        $courseRevenue = DB::table('course_user')->sum('price');
        $subscriptionRevenue = Subscription::where('status', 'active')->sum('price');
        $marketplaceRevenue = AuraOrder::where('status', 'completed')->sum('total_amount');
        
        return $courseRevenue + $subscriptionRevenue + $marketplaceRevenue;
    }

    private function getMonthlyRevenue()
    {
        $now = now();
        $courseRevenue = DB::table('course_user')
            ->whereMonth('created_at', $now->month)
            ->whereYear('created_at', $now->year)
            ->sum('price');
        $subscriptionRevenue = Subscription::where('status', 'active')
            ->whereMonth('created_at', $now->month)
            ->whereYear('created_at', $now->year)
            ->sum('price');
        $marketplaceRevenue = AuraOrder::where('status', 'completed')
            ->whereMonth('created_at', $now->month)
            ->whereYear('created_at', $now->year)
            ->sum('total_amount');
        
        return $courseRevenue + $subscriptionRevenue + $marketplaceRevenue;
    }

    private function getUserRegistrationTrend()
    {
        $trend = [];
        for ($i = 30; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $trend[] = [
                'date' => $date->format('Y-m-d'),
                'count' => User::whereDate('created_at', $date->toDateString())->count(),
            ];
        }
        return $trend;
    }

    private function calculateChurnRate()
    {
        $totalSubscriptions = Subscription::count();
        $canceledSubscriptions = Subscription::where('status', 'canceled')->count();
        
        return $totalSubscriptions > 0 ? ($canceledSubscriptions / $totalSubscriptions) * 100 : 0;
    }

    // Placeholder methods for system checks
    private function getServerUptime() { return '99.9%'; }
    private function getCpuUsage() { return '45%'; }
    private function getMemoryUsage() { return '67%'; }
    private function getDiskUsage() { return '78%'; }
    private function getQueriesPerSecond() { return 150; }
    private function getDatabaseSize() { return '2.5 GB'; }
    private function checkRedisConnection() { return true; }
    private function getCacheHitRate() { return '94%'; }
    private function getRedisMemoryUsage() { return '256 MB'; }
    private function checkStripeConnection() { return true; }
    private function checkPayPalConnection() { return true; }
    private function checkEmailService() { return true; }
    private function checkStorageService() { return true; }
    private function getErrorRate() { return '0.02%'; }
    private function getCriticalErrors() { return 0; }
    private function getWarnings() { return 3; }
    private function getTodayRevenue() { return 1250.00; }
    private function getWeekRevenue() { return 15000.00; }
    private function getMonthRevenue() { return 65000.00; }
    private function getYearRevenue() { return 780000.00; }
    private function getCourseRevenue() { return 45000.00; }
    private function getSubscriptionRevenue() { return 25000.00; }
    private function getMarketplaceRevenue() { return 8000.00; }
    private function getCertificateRevenue() { return 500.00; }
    private function getPageBuilderRevenue() { return 750.00; }
    private function getRevenueTrend() { return []; }
    private function getAverageRevenuePerUser() { return 85.50; }
    private function getRevenueGrowth() { return 15.5; }
    private function getUserGrowth() { return 12.3; }
    private function getCourseGrowth() { return 8.7; }
    private function getSubscriptionGrowth() { return 18.9; }
    private function getMarketplaceGrowth() { return 22.1; }
    private function getGrowthProjections() { return []; }
    private function calculateRegistrationConversionRate() { return 3.5; }
    private function calculateEnrollmentConversionRate() { return 12.8; }
    private function calculateSubscriptionConversionRate() { return 6.2; }
    private function calculatePurchaseConversionRate() { return 4.7; }
}
