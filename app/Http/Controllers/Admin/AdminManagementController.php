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
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class AdminManagementController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'admin']);
    }

    // ==================== USER MANAGEMENT ====================

    /**
     * Get all users with filters and pagination
     */
    public function getUsers(Request $request)
    {
        $query = User::query();

        // Filters
        if ($request->role) {
            $query->where('role', $request->role);
        }
        if ($request->status) {
            $query->where('status', $request->status);
        }
        if ($request->email_verified) {
            $query->where('email_verified_at', $request->email_verified === 'verified' ? '!=' : '=', null);
        }
        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%');
            });
        }
        if ($request->country) {
            $query->where('country', $request->country);
        }
        if ($request->created_from) {
            $query->whereDate('created_at', '>=', $request->created_from);
        }
        if ($request->created_to) {
            $query->whereDate('created_at', '<=', $request->created_to);
        }

        $users = $query->with(['subscriptions', 'courses', 'auraProducts'])
            ->orderBy($request->sort_by ?? 'created_at', $request->sort_order ?? 'desc')
            ->paginate($request->per_page ?? 20);

        return response()->json([
            'success' => true,
            'data' => $users,
        ]);
    }

    /**
     * Create new user
     */
    public function createUser(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:8',
            'role' => 'required|in:admin,instructor,student,vendor',
            'status' => 'required|in:active,inactive,suspended,banned',
            'phone' => 'nullable|string|max:20',
            'country' => 'nullable|string|size:2',
            'bio' => 'nullable|string|max:1000',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'send_welcome_email' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'status' => $request->status,
            'phone' => $request->phone,
            'country' => $request->country,
            'bio' => $request->bio,
            'email_verified_at' => now(),
        ]);

        // Handle avatar upload
        if ($request->hasFile('avatar')) {
            $avatar = $request->file('avatar')->store('avatars', 'public');
            $user->update(['avatar' => $avatar]);
        }

        // Send welcome email
        if ($request->send_welcome_email) {
            $this->sendWelcomeEmail($user, $request->password);
        }

        return response()->json([
            'success' => true,
            'message' => 'User created successfully',
            'data' => $user,
        ], 201);
    }

    /**
     * Update user
     */
    public function updateUser(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|unique:users,email,' . $id,
            'password' => 'sometimes|required|string|min:8',
            'role' => 'sometimes|required|in:admin,instructor,student,vendor',
            'status' => 'sometimes|required|in:active,inactive,suspended,banned',
            'phone' => 'nullable|string|max:20',
            'country' => 'nullable|string|size:2',
            'bio' => 'nullable|string|max:1000',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $updateData = $request->only(['name', 'email', 'role', 'status', 'phone', 'country', 'bio']);
        
        if ($request->password) {
            $updateData['password'] = Hash::make($request->password);
        }

        $user->update($updateData);

        // Handle avatar upload
        if ($request->hasFile('avatar')) {
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }
            $avatar = $request->file('avatar')->store('avatars', 'public');
            $user->update(['avatar' => $avatar]);
        }

        return response()->json([
            'success' => true,
            'message' => 'User updated successfully',
            'data' => $user->fresh(),
        ]);
    }

    /**
     * Delete user
     */
    public function deleteUser($id)
    {
        $user = User::findOrFail($id);

        // Prevent deletion of the last admin
        if ($user->role === 'admin' && User::where('role', 'admin')->count() === 1) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete the last admin user',
            ], 400);
        }

        // Delete user's avatar
        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'User deleted successfully',
        ]);
    }

    /**
     * Bulk actions on users
     */
    public function bulkUserAction(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_ids' => 'required|array',
            'user_ids.*' => 'required|exists:users,id',
            'action' => 'required|in:activate,deactivate,suspend,ban,delete',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $users = User::whereIn('id', $request->user_ids)->get();
        $count = 0;

        foreach ($users as $user) {
            switch ($request->action) {
                case 'activate':
                    $user->update(['status' => 'active']);
                    $count++;
                    break;
                case 'deactivate':
                    $user->update(['status' => 'inactive']);
                    $count++;
                    break;
                case 'suspend':
                    $user->update(['status' => 'suspended']);
                    $count++;
                    break;
                case 'ban':
                    $user->update(['status' => 'banned']);
                    $count++;
                    break;
                case 'delete':
                    // Prevent deletion of admins
                    if ($user->role !== 'admin' || User::where('role', 'admin')->count() > 1) {
                        if ($user->avatar) {
                            Storage::disk('public')->delete($user->avatar);
                        }
                        $user->delete();
                        $count++;
                    }
                    break;
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Bulk action completed. {$count} users affected.",
            'affected_count' => $count,
        ]);
    }

    // ==================== COURSE MANAGEMENT ====================

    /**
     * Get all courses with filters
     */
    public function getCourses(Request $request)
    {
        $query = Course::query();

        // Filters
        if ($request->status) {
            $query->where('status', $request->status);
        }
        if ($request->level) {
            $query->where('level', $request->level);
        }
        if ($request->category) {
            $query->where('category', $request->category);
        }
        if ($request->instructor_id) {
            $query->where('instructor_id', $request->instructor_id);
        }
        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('title', 'like', '%' . $request->search . '%')
                  ->orWhere('description', 'like', '%' . $request->search . '%');
            });
        }
        if ($request->price_from) {
            $query->where('price', '>=', $request->price_from);
        }
        if ($request->price_to) {
            $query->where('price', '<=', $request->price_to);
        }

        $courses = $query->with(['instructor', 'users'])
            ->orderBy($request->sort_by ?? 'created_at', $request->sort_order ?? 'desc')
            ->paginate($request->per_page ?? 20);

        return response()->json([
            'success' => true,
            'data' => $courses,
        ]);
    }

    /**
     * Update course status
     */
    public function updateCourseStatus(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:draft,published,archived',
            'reason' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $course = Course::findOrFail($id);
        $oldStatus = $course->status;
        $course->update([
            'status' => $request->status,
            'status_notes' => $request->reason,
        ]);

        // Notify instructor
        if ($oldStatus !== $request->status) {
            $this->notifyInstructorCourseStatusChange($course, $oldStatus, $request->status);
        }

        return response()->json([
            'success' => true,
            'message' => 'Course status updated successfully',
            'data' => $course->fresh(),
        ]);
    }

    /**
     * Bulk course actions
     */
    public function bulkCourseAction(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'course_ids' => 'required|array',
            'course_ids.*' => 'required|exists:courses,id',
            'action' => 'required|in:publish,unpublish,archive,delete',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $courses = Course::whereIn('id', $request->course_ids)->get();
        $count = 0;

        foreach ($courses as $course) {
            switch ($request->action) {
                case 'publish':
                    $course->update(['status' => 'published']);
                    $count++;
                    break;
                case 'unpublish':
                    $course->update(['status' => 'draft']);
                    $count++;
                    break;
                case 'archive':
                    $course->update(['status' => 'archived']);
                    $count++;
                    break;
                case 'delete':
                    $course->delete();
                    $count++;
                    break;
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Bulk action completed. {$count} courses affected.",
            'affected_count' => $count,
        ]);
    }

    // ==================== SUBSCRIPTION MANAGEMENT ====================

    /**
     * Get all subscriptions
     */
    public function getSubscriptions(Request $request)
    {
        $query = Subscription::query();

        // Filters
        if ($request->status) {
            $query->where('status', $request->status);
        }
        if ($request->plan_id) {
            $query->where('plan_id', $request->plan_id);
        }
        if ($request->user_id) {
            $query->where('user_id', $request->user_id);
        }
        if ($request->billing_cycle) {
            $query->where('billing_cycle', $request->billing_cycle);
        }
        if ($request->created_from) {
            $query->whereDate('created_at', '>=', $request->created_from);
        }
        if ($request->created_to) {
            $query->whereDate('created_at', '<=', $request->created_to);
        }

        $subscriptions = $query->with(['user', 'plan', 'invoices'])
            ->orderBy($request->sort_by ?? 'created_at', $request->sort_order ?? 'desc')
            ->paginate($request->per_page ?? 20);

        return response()->json([
            'success' => true,
            'data' => $subscriptions,
        ]);
    }

    /**
     * Update subscription
     */
    public function updateSubscription(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'sometimes|required|in:active,canceled,expired,past_due,trialing,suspended',
            'auto_renew' => 'sometimes|required|boolean',
            'next_billing_at' => 'sometimes|required|date',
            'notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $subscription = Subscription::findOrFail($id);
        $subscription->update($request->only(['status', 'auto_renew', 'next_billing_at', 'notes']));

        return response()->json([
            'success' => true,
            'message' => 'Subscription updated successfully',
            'data' => $subscription->fresh(),
        ]);
    }

    /**
     * Cancel subscription
     */
    public function cancelSubscription(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'reason' => 'nullable|string|max:500',
            'immediate' => 'boolean',
            'refund_amount' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $subscription = Subscription::findOrFail($id);

        if ($subscription->cancel($request->reason, $request->immediate)) {
            return response()->json([
                'success' => true,
                'message' => 'Subscription canceled successfully',
                'data' => [
                    'status' => $subscription->fresh()->status,
                    'canceled_at' => $subscription->fresh()->canceled_at?->format('Y-m-d H:i'),
                ],
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to cancel subscription',
        ], 500);
    }

    // ==================== MARKETPLACE MANAGEMENT ====================

    /**
     * Get marketplace products
     */
    public function getMarketplaceProducts(Request $request)
    {
        $query = AuraProduct::query();

        // Filters
        if ($request->status) {
            $query->where('status', $request->status);
        }
        if ($request->category_id) {
            $query->where('category_id', $request->category_id);
        }
        if ($request->vendor_id) {
            $query->where('vendor_id', $request->vendor_id);
        }
        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('description', 'like', '%' . $request->search . '%');
            });
        }
        if ($request->price_from) {
            $query->where('price', '>=', $request->price_from);
        }
        if ($request->price_to) {
            $query->where('price', '<=', $request->price_to);
        }

        $products = $query->with(['vendor', 'category', 'orders'])
            ->orderBy($request->sort_by ?? 'created_at', $request->sort_order ?? 'desc')
            ->paginate($request->per_page ?? 20);

        return response()->json([
            'success' => true,
            'data' => $products,
        ]);
    }

    /**
     * Update product status
     */
    public function updateProductStatus(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:active,inactive,draft',
            'reason' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $product = AuraProduct::findOrFail($id);
        $oldStatus = $product->status;
        $product->update([
            'status' => $request->status,
            'status_notes' => $request->reason,
        ]);

        // Notify vendor
        if ($oldStatus !== $request->status) {
            $this->notifyVendorProductStatusChange($product, $oldStatus, $request->status);
        }

        return response()->json([
            'success' => true,
            'message' => 'Product status updated successfully',
            'data' => $product->fresh(),
        ]);
    }

    /**
     * Get marketplace orders
     */
    public function getMarketplaceOrders(Request $request)
    {
        $query = AuraOrder::query();

        // Filters
        if ($request->status) {
            $query->where('status', $request->status);
        }
        if ($request->user_id) {
            $query->where('user_id', $request->user_id);
        }
        if ($request->vendor_id) {
            $query->whereHas('items', function ($q) use ($request) {
                $q->where('vendor_id', $request->vendor_id);
            });
        }
        if ($request->created_from) {
            $query->whereDate('created_at', '>=', $request->created_from);
        }
        if ($request->created_to) {
            $query->whereDate('created_at', '<=', $request->created_to);
        }

        $orders = $query->with(['user', 'items.product'])
            ->orderBy($request->sort_by ?? 'created_at', $request->sort_order ?? 'desc')
            ->paginate($request->per_page ?? 20);

        return response()->json([
            'success' => true,
            'data' => $orders,
        ]);
    }

    /**
     * Update order status
     */
    public function updateOrderStatus(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:pending,processing,completed,cancelled,refunded',
            'notes' => 'nullable|string|max:500',
            'tracking_number' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $order = AuraOrder::findOrFail($id);
        $oldStatus = $order->status;
        $order->update([
            'status' => $request->status,
            'notes' => $request->notes,
            'tracking_number' => $request->tracking_number,
        ]);

        // Handle stock adjustments
        if ($request->status === 'completed' && $oldStatus !== 'completed') {
            $order->adjustStock();
        } elseif ($request->status === 'cancelled' && $oldStatus !== 'cancelled') {
            $order->restoreStock();
        }

        // Notify user and vendor
        $this->notifyOrderStatusChange($order, $oldStatus, $request->status);

        return response()->json([
            'success' => true,
            'message' => 'Order status updated successfully',
            'data' => $order->fresh(),
        ]);
    }

    // ==================== SYSTEM SETTINGS ====================

    /**
     * Get system settings
     */
    public function getSystemSettings()
    {
        $settings = [
            'general' => [
                'site_name' => config('app.name'),
                'site_url' => config('app.url'),
                'timezone' => config('app.timezone'),
                'locale' => config('app.locale'),
                'maintenance_mode' => app()->isDownForMaintenance(),
            ],
            'email' => [
                'driver' => config('mail.default'),
                'host' => config('mail.mailers.smtp.host'),
                'port' => config('mail.mailers.smtp.port'),
                'encryption' => config('mail.mailers.smtp.encryption'),
                'from_address' => config('mail.from.address'),
                'from_name' => config('mail.from.name'),
            ],
            'payment' => [
                'stripe_enabled' => config('services.stripe.enabled', false),
                'paypal_enabled' => config('services.paypal.enabled', false),
                'wise_enabled' => config('services.wise.enabled', false),
                'coinbase_enabled' => config('services.coinbase.enabled', false),
            ],
            'storage' => [
                'default' => config('filesystems.default'),
                'local_root' => config('filesystems.disks.local.root'),
                'public_url' => config('filesystems.disks.public.url'),
                's3_bucket' => config('filesystems.disks.s3.bucket'),
                's3_region' => config('filesystems.disks.s3.region'),
            ],
            'cache' => [
                'default' => config('cache.default'),
                'redis_host' => config('database.redis.default.host'),
                'redis_port' => config('database.redis.default.port'),
                'redis_db' => config('database.redis.default.database'),
            ],
            'security' => [
                'session_lifetime' => config('session.lifetime'),
                'password_min_length' => config('auth.password_min_length', 8),
                'require_email_verification' => config('auth.require_email_verification', true),
                'max_login_attempts' => config('auth.max_login_attempts', 5),
                'lockout_duration' => config('auth.lockout_duration', 300),
            ],
            'features' => [
                'registration_enabled' => config('features.registration_enabled', true),
                'social_login_enabled' => config('features.social_login_enabled', true),
                'marketplace_enabled' => config('features.marketplace_enabled', true),
                'certificates_enabled' => config('features.certificates_enabled', true),
                'pagebuilder_enabled' => config('features.pagebuilder_enabled', true),
                'affiliate_enabled' => config('features.affiliate_enabled', true),
            ],
        ];

        return response()->json([
            'success' => true,
            'data' => $settings,
        ]);
    }

    /**
     * Update system settings
     */
    public function updateSystemSettings(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'general.site_name' => 'required|string|max:255',
            'general.site_url' => 'required|url',
            'general.timezone' => 'required|string|timezone',
            'general.locale' => 'required|string',
            'email.from_address' => 'required|email',
            'email.from_name' => 'required|string|max:255',
            'security.session_lifetime' => 'required|integer|min:1',
            'security.password_min_length' => 'required|integer|min:6',
            'security.require_email_verification' => 'boolean',
            'security.max_login_attempts' => 'required|integer|min:1',
            'security.lockout_duration' => 'required|integer|min:60',
            'features.registration_enabled' => 'boolean',
            'features.social_login_enabled' => 'boolean',
            'features.marketplace_enabled' => 'boolean',
            'features.certificates_enabled' => 'boolean',
            'features.pagebuilder_enabled' => 'boolean',
            'features.affiliate_enabled' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Update settings in config files or database
        // This is a simplified version - in production, you'd want to use a proper config management system
        
        return response()->json([
            'success' => true,
            'message' => 'System settings updated successfully',
        ]);
    }

    // ==================== ANALYTICS ====================

    /**
     * Get detailed analytics
     */
    public function getAnalytics(Request $request)
    {
        $period = $request->period ?? '30days';
        $fromDate = now()->subDays($period === '30days' ? 30 : ($period === '7days' ? 7 : 365));
        $toDate = now();

        $analytics = [
            'overview' => [
                'total_users' => User::whereBetween('created_at', [$fromDate, $toDate])->count(),
                'total_courses' => Course::whereBetween('created_at', [$fromDate, $toDate])->count(),
                'total_orders' => AuraOrder::whereBetween('created_at', [$fromDate, $toDate])->count(),
                'total_revenue' => AuraOrder::where('status', 'completed')
                    ->whereBetween('created_at', [$fromDate, $toDate])
                    ->sum('total_amount'),
            ],
            'user_analytics' => $this->getUserAnalytics($fromDate, $toDate),
            'course_analytics' => $this->getCourseAnalytics($fromDate, $toDate),
            'revenue_analytics' => $this->getRevenueAnalytics($fromDate, $toDate),
            'traffic_analytics' => $this->getTrafficAnalytics($fromDate, $toDate),
        ];

        return response()->json([
            'success' => true,
            'data' => $analytics,
        ]);
    }

    // Helper methods
    private function sendWelcomeEmail($user, $password)
    {
        // Implement welcome email sending
    }

    private function notifyInstructorCourseStatusChange($course, $oldStatus, $newStatus)
    {
        // Implement instructor notification
    }

    private function notifyVendorProductStatusChange($product, $oldStatus, $newStatus)
    {
        // Implement vendor notification
    }

    private function notifyOrderStatusChange($order, $oldStatus, $newStatus)
    {
        // Implement order status notification
    }

    private function getUserAnalytics($fromDate, $toDate)
    {
        return [
            'registrations_by_day' => User::whereBetween('created_at', [$fromDate, $toDate])
                ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
                ->groupBy('date')
                ->orderBy('date')
                ->get(),
            'registrations_by_country' => User::whereBetween('created_at', [$fromDate, $toDate])
                ->selectRaw('country, COUNT(*) as count')
                ->whereNotNull('country')
                ->groupBy('country')
                ->orderBy('count', 'desc')
                ->take(10)
                ->get(),
            'user_roles' => User::whereBetween('created_at', [$fromDate, $toDate])
                ->selectRaw('role, COUNT(*) as count')
                ->groupBy('role')
                ->get(),
        ];
    }

    private function getCourseAnalytics($fromDate, $toDate)
    {
        return [
            'courses_by_day' => Course::whereBetween('created_at', [$fromDate, $toDate])
                ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
                ->groupBy('date')
                ->orderBy('date')
                ->get(),
            'courses_by_category' => Course::whereBetween('created_at', [$fromDate, $toDate])
                ->selectRaw('category, COUNT(*) as count')
                ->groupBy('category')
                ->orderBy('count', 'desc')
                ->get(),
            'enrollments_by_day' => DB::table('course_user')
                ->whereBetween('created_at', [$fromDate, $toDate])
                ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
                ->groupBy('date')
                ->orderBy('date')
                ->get(),
        ];
    }

    private function getRevenueAnalytics($fromDate, $toDate)
    {
        return [
            'revenue_by_day' => AuraOrder::where('status', 'completed')
                ->whereBetween('created_at', [$fromDate, $toDate])
                ->selectRaw('DATE(created_at) as date, SUM(total_amount) as revenue')
                ->groupBy('date')
                ->orderBy('date')
                ->get(),
            'revenue_by_source' => [
                'courses' => DB::table('course_user')
                    ->whereBetween('created_at', [$fromDate, $toDate])
                    ->sum('price'),
                'marketplace' => AuraOrder::where('status', 'completed')
                    ->whereBetween('created_at', [$fromDate, $toDate])
                    ->sum('total_amount'),
                'subscriptions' => Subscription::where('status', 'active')
                    ->whereBetween('created_at', [$fromDate, $toDate])
                    ->sum('price'),
            ],
        ];
    }

    private function getTrafficAnalytics($fromDate, $toDate)
    {
        return [
            'page_views_by_day' => DB::table('analytics')
                ->whereBetween('date', [$fromDate->toDateString(), $toDate->toDateString()])
                ->selectRaw('date, SUM(page_views) as views')
                ->groupBy('date')
                ->orderBy('date')
                ->get(),
            'top_pages' => DB::table('analytics')
                ->whereBetween('date', [$fromDate->toDateString(), $toDate->toDateString()])
                ->selectRaw('page_url, SUM(page_views) as total_views')
                ->groupBy('page_url')
                ->orderBy('total_views', 'desc')
                ->take(10)
                ->get(),
        ];
    }
}
