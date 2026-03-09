<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Course;
use App\Models\Subscription;
use App\Models\Certificate;
use App\Models\StuffPurchase;
use App\Models\StuffLicense;
use App\Models\StuffReview;
use App\Models\AuraOrder;
use App\Models\AuraPage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class ProfileController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Get user profile data
     */
    public function getProfile()
    {
        $user = Auth::user()->load([
            'roles',
            'permissions',
            'courses' => function ($query) {
                $query->withCount('users')->with('category');
            },
            'enrollments' => function ($query) {
                $query->with('course')->withPivot(['enrolled_at', 'completed_at', 'progress']);
            },
            'subscriptions' => function ($query) {
                $query->with('plan')->where('status', 'active');
            },
            'certificates' => function ($query) {
                $query->with('template')->orderBy('issued_at', 'desc');
            },
            'stuffPurchases' => function ($query) {
                $query->with('stuff')->where('status', 'active');
            },
            'stuffLicenses' => function ($query) {
                $query->with('stuff')->where('status', 'active');
            },
            'stuffReviews' => function ($query) {
                $query->with('stuff')->orderBy('created_at', 'desc');
            },
            'auraOrders' => function ($query) {
                $query->with('items.product')->orderBy('created_at', 'desc');
            },
            'auraPages' => function ($query) {
                $query->where('status', 'published')->orderBy('created_at', 'desc');
            }
        ]);

        // Calculate statistics
        $stats = [
            'courses_created' => $user->courses()->count(),
            'courses_enrolled' => $user->enrollments()->count(),
            'courses_completed' => $user->enrollments()->whereNotNull('completed_at')->count(),
            'active_subscriptions' => $user->subscriptions()->where('status', 'active')->count(),
            'certificates_earned' => $user->certificates()->count(),
            'stuff_purchased' => $user->stuffPurchases()->where('status', 'active')->count(),
            'stuff_reviews' => $user->stuffReviews()->count(),
            'aura_orders' => $user->auraOrders()->count(),
            'aura_pages' => $user->auraPages()->count(),
            'total_spent' => $user->auraOrders()->where('status', 'completed')->sum('total_amount') + 
                           $user->stuffPurchases()->where('status', 'active')->sum('total_amount'),
        ];

        // Get recent activities
        $recentActivities = [
            [
                'type' => 'course_enrollment',
                'title' => 'Enrolled in new course',
                'description' => 'Started learning "Advanced Laravel"',
                'timestamp' => now()->subDays(2),
                'icon' => 'academic-cap',
                'color' => 'blue',
            ],
            [
                'type' => 'certificate_earned',
                'title' => 'Certificate earned',
                'description' => 'Completed "Web Development" course',
                'timestamp' => now()->subDays(5),
                'icon' => 'award',
                'color' => 'green',
            ],
            [
                'type' => 'purchase',
                'title' => 'New purchase',
                'description' => 'Bought "Premium Template Pack"',
                'timestamp' => now()->subWeek(),
                'icon' => 'shopping-cart',
                'color' => 'purple',
            ],
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'user' => $user,
                'stats' => $stats,
                'recent_activities' => $recentActivities,
            ],
        ]);
    }

    /**
     * Update user profile
     */
    public function updateProfile(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . Auth::id(),
            'phone' => 'nullable|string|max:20',
            'country' => 'nullable|string|size:2',
            'bio' => 'nullable|string|max:1000',
            'website' => 'nullable|url|max:255',
            'social_links' => 'nullable|array',
            'social_links.facebook' => 'nullable|url|max:255',
            'social_links.twitter' => 'nullable|url|max:255',
            'social_links.linkedin' => 'nullable|url|max:255',
            'social_links.github' => 'nullable|url|max:255',
            'preferences' => 'nullable|array',
            'preferences.language' => 'nullable|string|max:10',
            'preferences.timezone' => 'nullable|string|max:50',
            'preferences.email_notifications' => 'boolean',
            'preferences.push_notifications' => 'boolean',
            'preferences.privacy' => 'nullable|string|in:public,friends,private',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = Auth::user();
        
        $updateData = $request->only([
            'name', 'email', 'phone', 'country', 'bio', 'website'
        ]);

        if ($request->has('social_links')) {
            $updateData['social_links'] = $request->social_links;
        }

        if ($request->has('preferences')) {
            $updateData['preferences'] = $request->preferences;
        }

        $user->update($updateData);

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully',
            'data' => $user->fresh(),
        ]);
    }

    /**
     * Update avatar
     */
    public function updateAvatar(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'avatar' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = Auth::user();

        // Delete old avatar
        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
        }

        // Upload new avatar
        $avatar = $request->file('avatar')->store('avatars', 'public');
        $user->update(['avatar' => $avatar]);

        return response()->json([
            'success' => true,
            'message' => 'Avatar updated successfully',
            'data' => [
                'avatar' => $user->avatar,
                'avatar_url' => $user->avatar_url,
            ],
        ]);
    }

    /**
     * Update password
     */
    public function updatePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = Auth::user();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Current password is incorrect',
            ], 400);
        }

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Password updated successfully',
        ]);
    }

    /**
     * Get user courses
     */
    public function getCourses(Request $request)
    {
        $user = Auth::user();
        $type = $request->type ?? 'enrolled'; // enrolled, created, completed

        $query = match ($type) {
            'created' => $user->courses(),
            'completed' => $user->enrollments()->whereNotNull('completed_at'),
            default => $user->enrollments(),
        };

        if ($type === 'created') {
            $courses = $query->with(['category', 'users' => function ($q) {
                $q->select('users.id', 'users.name');
            }])
            ->withCount('users')
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 12);
        } else {
            $courses = $query->with(['course.category', 'course.instructor'])
            ->withPivot(['enrolled_at', 'completed_at', 'progress', 'last_accessed_at'])
            ->orderBy('enrolled_at', 'desc')
            ->paginate($request->per_page ?? 12);
        }

        return response()->json([
            'success' => true,
            'data' => $courses,
        ]);
    }

    /**
     * Get user certificates
     */
    public function getCertificates(Request $request)
    {
        $certificates = Auth::user()
            ->certificates()
            ->with(['template', 'course'])
            ->orderBy('issued_at', 'desc')
            ->paginate($request->per_page ?? 12);

        return response()->json([
            'success' => true,
            'data' => $certificates,
        ]);
    }

    /**
     * Get user subscriptions
     */
    public function getSubscriptions(Request $request)
    {
        $subscriptions = Auth::user()
            ->subscriptions()
            ->with('plan')
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 12);

        return response()->json([
            'success' => true,
            'data' => $subscriptions,
        ]);
    }

    /**
     * Get user purchases
     */
    public function getPurchases(Request $request)
    {
        $type = $request->type ?? 'all'; // all, marketplace, stuff

        $query = match ($type) {
            'marketplace' => Auth::user()->auraOrders(),
            'stuff' => Auth::user()->stuffPurchases(),
            default => null,
        };

        if ($type === 'all') {
            $marketplaceOrders = Auth::user()->auraOrders()->with('items.product')->get();
            $stuffPurchases = Auth::user()->stuffPurchases()->with('stuff')->get();
            
            $purchases = collect()
                ->merge($marketplaceOrders->map(function ($order) {
                    return [
                        'id' => $order->id,
                        'type' => 'marketplace',
                        'name' => 'Order #' . $order->order_number,
                        'amount' => $order->total_amount,
                        'status' => $order->status,
                        'created_at' => $order->created_at,
                        'items' => $order->items,
                    ];
                }))
                ->merge($stuffPurchases->map(function ($purchase) {
                    return [
                        'id' => $purchase->id,
                        'type' => 'stuff',
                        'name' => $purchase->stuff->name,
                        'amount' => $purchase->total_amount,
                        'status' => $purchase->status,
                        'created_at' => $purchase->created_at,
                        'stuff' => $purchase->stuff,
                    ];
                }))
                ->sortByDesc('created_at')
                ->values();

            return response()->json([
                'success' => true,
                'data' => $purchases,
            ]);
        }

        if ($query) {
            if ($type === 'marketplace') {
                $purchases = $query->with('items.product')->orderBy('created_at', 'desc')->paginate($request->per_page ?? 12);
            } else {
                $purchases = $query->with('stuff')->orderBy('created_at', 'desc')->paginate($request->per_page ?? 12);
            }

            return response()->json([
                'success' => true,
                'data' => $purchases,
            ]);
        }
    }

    /**
     * Get user reviews
     */
    public function getReviews(Request $request)
    {
        $reviews = Auth::user()
            ->stuffReviews()
            ->with('stuff')
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 12);

        return response()->json([
            'success' => true,
            'data' => $reviews,
        ]);
    }

    /**
     * Get user pages
     */
    public function getPages(Request $request)
    {
        $pages = Auth::user()
            ->auraPages()
            ->where('status', 'published')
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 12);

        return response()->json([
            'success' => true,
            'data' => $pages,
        ]);
    }

    /**
     * Get user achievements
     */
    public function getAchievements()
    {
        $user = Auth::user();
        
        $achievements = [
            [
                'id' => 1,
                'title' => 'First Course',
                'description' => 'Enrolled in your first course',
                'icon' => 'academic-cap',
                'color' => 'blue',
                'earned_at' => now()->subMonths(2),
                'progress' => 100,
                'completed' => true,
            ],
            [
                'id' => 2,
                'title' => 'Course Master',
                'description' => 'Complete 5 courses',
                'icon' => 'award',
                'color' => 'green',
                'earned_at' => now()->subMonth(),
                'progress' => 100,
                'completed' => true,
            ],
            [
                'id' => 3,
                'title' => 'Certified Professional',
                'description' => 'Earn 10 certificates',
                'icon' => 'certificate',
                'color' => 'purple',
                'earned_at' => null,
                'progress' => 60,
                'completed' => false,
            ],
            [
                'id' => 4,
                'title' => 'Active Learner',
                'description' => 'Log in for 30 consecutive days',
                'icon' => 'calendar',
                'color' => 'orange',
                'earned_at' => null,
                'progress' => 45,
                'completed' => false,
            ],
        ];

        return response()->json([
            'success' => true,
            'data' => $achievements,
        ]);
    }

    /**
     * Get user activity timeline
     */
    public function getActivityTimeline(Request $request)
    {
        $user = Auth::user();
        $limit = $request->limit ?? 20;

        // Get activities from different sources
        $activities = collect();

        // Course enrollments
        $enrollments = $user->enrollments()
            ->with('course')
            ->orderBy('enrolled_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($enrollment) {
                return [
                    'id' => 'enrollment_' . $enrollment->id,
                    'type' => 'course_enrollment',
                    'title' => 'Enrolled in course',
                    'description' => 'Started learning "' . $enrollment->course->title . '"',
                    'timestamp' => $enrollment->enrolled_at,
                    'icon' => 'academic-cap',
                    'color' => 'blue',
                    'data' => [
                        'course_id' => $enrollment->course->id,
                        'course_title' => $enrollment->course->title,
                    ],
                ];
            });

        // Certificates
        $certificates = $user->certificates()
            ->with('template')
            ->orderBy('issued_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($certificate) {
                return [
                    'id' => 'certificate_' . $certificate->id,
                    'type' => 'certificate_earned',
                    'title' => 'Certificate earned',
                    'description' => 'Completed "' . $certificate->template->name . '"',
                    'timestamp' => $certificate->issued_at,
                    'icon' => 'award',
                    'color' => 'green',
                    'data' => [
                        'certificate_id' => $certificate->id,
                        'template_name' => $certificate->template->name,
                    ],
                ];
            });

        // Purchases
        $purchases = $user->auraOrders()
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($order) {
                return [
                    'id' => 'purchase_' . $order->id,
                    'type' => 'purchase',
                    'title' => 'New purchase',
                    'description' => 'Order #' . $order->order_number . ' - $' . $order->total_amount,
                    'timestamp' => $order->created_at,
                    'icon' => 'shopping-cart',
                    'color' => 'purple',
                    'data' => [
                        'order_id' => $order->id,
                        'order_number' => $order->order_number,
                        'amount' => $order->total_amount,
                    ],
                ];
            });

        // Reviews
        $reviews = $user->stuffReviews()
            ->with('stuff')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($review) {
                return [
                    'id' => 'review_' . $review->id,
                    'type' => 'review',
                    'title' => 'Review posted',
                    'description' => 'Reviewed "' . $review->stuff->name . '" - ' . $review->rating . ' stars',
                    'timestamp' => $review->created_at,
                    'icon' => 'star',
                    'color' => 'yellow',
                    'data' => [
                        'review_id' => $review->id,
                        'stuff_name' => $review->stuff->name,
                        'rating' => $review->rating,
                    ],
                ];
            });

        // Merge and sort
        $activities = $activities
            ->merge($enrollments)
            ->merge($certificates)
            ->merge($purchases)
            ->merge($reviews)
            ->sortByDesc('timestamp')
            ->take($limit)
            ->values();

        return response()->json([
            'success' => true,
            'data' => $activities,
        ]);
    }

    /**
     * Get user statistics
     */
    public function getStatistics()
    {
        $user = Auth::user();

        $stats = [
            'learning' => [
                'courses_enrolled' => $user->enrollments()->count(),
                'courses_completed' => $user->enrollments()->whereNotNull('completed_at')->count(),
                'certificates_earned' => $user->certificates()->count(),
                'average_progress' => $user->enrollments()->avg('pivot.progress') ?? 0,
                'total_learning_time' => $user->enrollments()->sum('pivot.learning_time') ?? 0,
            ],
            'purchases' => [
                'total_spent' => $user->auraOrders()->where('status', 'completed')->sum('total_amount') + 
                               $user->stuffPurchases()->where('status', 'active')->sum('total_amount'),
                'orders_count' => $user->auraOrders()->count(),
                'stuff_purchased' => $user->stuffPurchases()->where('status', 'active')->count(),
                'reviews_count' => $user->stuffReviews()->count(),
            ],
            'activity' => [
                'login_streak' => $this->calculateLoginStreak($user),
                'last_login' => $user->last_login_at,
                'profile_views' => $user->profile_views ?? 0,
                'pages_created' => $user->auraPages()->count(),
                'courses_created' => $user->courses()->count(),
            ],
            'subscriptions' => [
                'active_subscriptions' => $user->subscriptions()->where('status', 'active')->count(),
                'monthly_spending' => $user->subscriptions()->where('status', 'active')->sum('price'),
                'total_saved' => $this->calculateTotalSaved($user),
            ],
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Delete user account
     */
    public function deleteAccount(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'password' => 'required',
            'confirmation' => 'required|in:DELETE',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = Auth::user();

        if (!Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Password is incorrect',
            ], 400);
        }

        // Soft delete user account
        $user->delete();

        // Logout user
        Auth::logout();

        return response()->json([
            'success' => true,
            'message' => 'Account deleted successfully',
        ]);
    }

    // Helper methods
    private function calculateLoginStreak($user)
    {
        // This would typically be calculated from a login history table
        // For now, return a sample value
        return 15;
    }

    private function calculateTotalSaved($user)
    {
        // Calculate savings from discounts, coupons, etc.
        // For now, return a sample value
        return 125.50;
    }
}
