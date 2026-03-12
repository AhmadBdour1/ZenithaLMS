<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class ProfileActivityController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Get user activity dashboard
     */
    public function getActivityDashboard()
    {
        $user = Auth::user();

        $dashboard = [
            'overview' => [
                'total_activities' => $this->getTotalActivities($user),
                'this_week' => $this->getThisWeekActivities($user),
                'this_month' => $this->getThisMonthActivities($user),
                'most_active_day' => $this->getMostActiveDay($user),
                'current_streak' => $this->getCurrentStreak($user),
            ],
            'activity_types' => [
                'course_activities' => $this->getCourseActivities($user),
                'purchase_activities' => $this->getPurchaseActivities($user),
                'review_activities' => $this->getReviewActivities($user),
                'certificate_activities' => $this->getCertificateActivities($user),
                'login_activities' => $this->getLoginActivities($user),
            ],
            'recent_activities' => $this->getRecentActivities($user, 10),
            'activity_chart' => $this->getActivityChart($user, 30),
        ];

        return response()->json([
            'success' => true,
            'data' => $dashboard,
        ]);
    }

    /**
     * Get user activities with filters
     */
    public function getActivities(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'nullable|in:all,course,purchase,review,certificate,login,page,subscription',
            'period' => 'nullable|in:today,week,month,year,all',
            'limit' => 'nullable|integer|min:1|max:100',
            'page' => 'nullable|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = Auth::user();
        $type = $request->type ?? 'all';
        $period = $request->period ?? 'month';
        $limit = $request->limit ?? 20;

        $activities = $this->getFilteredActivities($user, $type, $period, $limit);

        return response()->json([
            'success' => true,
            'data' => $activities,
        ]);
    }

    /**
     * Get detailed activity
     */
    public function getActivityDetail($id)
    {
        $user = Auth::user();
        $activity = $this->getActivityById($user, $id);

        if (!$activity) {
            return response()->json([
                'success' => false,
                'message' => 'Activity not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $activity,
        ]);
    }

    /**
     * Get activity statistics
     */
    public function getActivityStatistics(Request $request)
    {
        $user = Auth::user();
        $period = $request->period ?? 'month';

        $stats = [
            'summary' => $this->getActivitySummary($user, $period),
            'by_type' => $this->getActivitiesByType($user, $period),
            'by_day' => $this->getActivitiesByDay($user, $period),
            'by_hour' => $this->getActivitiesByHour($user, $period),
            'trends' => $this->getActivityTrends($user, $period),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Get learning progress
     */
    public function getLearningProgress(Request $request)
    {
        $user = Auth::user();

        $progress = [
            'overview' => [
                'total_courses' => $user->enrollments()->count(),
                'completed_courses' => $user->enrollments()->whereNotNull('completed_at')->count(),
                'in_progress_courses' => $user->enrollments()->whereNull('completed_at')->count(),
                'average_progress' => $user->enrollments()->avg('pivot.progress') ?? 0,
                'total_learning_time' => $user->enrollments()->sum('pivot.learning_time') ?? 0,
            ],
            'courses' => $user->enrollments()
                ->with('course')
                ->withPivot(['progress', 'learning_time', 'last_accessed_at'])
                ->orderBy('pivot.last_accessed_at', 'desc')
                ->get()
                ->map(function ($enrollment) {
                    return [
                        'id' => $enrollment->id,
                        'course' => [
                            'id' => $enrollment->course->id,
                            'title' => $enrollment->course->title,
                            'thumbnail' => $enrollment->course->thumbnail,
                            'category' => $enrollment->course->category,
                        ],
                        'progress' => $enrollment->pivot->progress,
                        'learning_time' => $enrollment->pivot->learning_time ?? 0,
                        'last_accessed' => $enrollment->pivot->last_accessed_at,
                        'enrolled_at' => $enrollment->pivot->enrolled_at,
                        'completed_at' => $enrollment->pivot->completed_at,
                        'status' => $enrollment->pivot->completed_at ? 'completed' : 'in_progress',
                    ];
                }),
            'achievements' => $this->getLearningAchievements($user),
            'recommendations' => $this->getCourseRecommendations($user),
        ];

        return response()->json([
            'success' => true,
            'data' => $progress,
        ]);
    }

    /**
     * Get purchase history
     */
    public function getPurchaseHistory(Request $request)
    {
        $user = Auth::user();
        $type = $request->type ?? 'all';
        $period = $request->period ?? 'year';

        $history = [
            'summary' => [
                'total_spent' => $this->getTotalSpent($user, $period),
                'total_orders' => $this->getTotalOrders($user, $period),
                'average_order_value' => $this->getAverageOrderValue($user, $period),
                'most_purchased_category' => $this->getMostPurchasedCategory($user, $period),
            ],
            'marketplace_orders' => $this->getMarketplaceOrders($user, $type, $period),
            'stuff_purchases' => $this->getStuffPurchases($user, $type, $period),
            'subscription_history' => $this->getSubscriptionHistory($user, $period),
            'spending_chart' => $this->getSpendingChart($user, $period),
        ];

        return response()->json([
            'success' => true,
            'data' => $history,
        ]);
    }

    /**
     * Get certificates and achievements
     */
    public function getCertificatesAndAchievements(Request $request)
    {
        $user = Auth::user();

        $data = [
            'certificates' => $user->certificates()
                ->with(['template', 'course'])
                ->orderBy('issued_at', 'desc')
                ->get()
                ->map(function ($certificate) {
                    return [
                        'id' => $certificate->id,
                        'certificate_number' => $certificate->certificate_number,
                        'verification_code' => $certificate->verification_code,
                        'template' => [
                            'name' => $certificate->template->name,
                            'design' => $certificate->template->design,
                        ],
                        'course' => $certificate->course ? [
                            'title' => $certificate->course->title,
                            'category' => $certificate->course->category,
                        ] : null,
                        'issued_at' => $certificate->issued_at,
                        'expires_at' => $certificate->expires_at,
                        'status' => $certificate->status,
                        'download_url' => route('certificates.download', $certificate->id),
                        'verify_url' => route('certificates.verify', $certificate->verification_code),
                    ];
                }),
            'achievements' => $this->getUserAchievements($user),
            'badges' => $this->getUserBadges($user),
            'milestones' => $this->getUserMilestones($user),
            'statistics' => [
                'total_certificates' => $user->certificates()->count(),
                'active_certificates' => $user->certificates()->where('status', 'issued')->count(),
                'expired_certificates' => $user->certificates()->where('status', 'expired')->count(),
                'total_achievements' => count($this->getUserAchievements($user)),
                'total_badges' => count($this->getUserBadges($user)),
            ],
        ];

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Get user reviews and ratings
     */
    public function getReviewsAndRatings(Request $request)
    {
        $user = Auth::user();

        $reviews = $user->stuffReviews()
            ->with('stuff')
            ->withCount('helpfulVotes')
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 10);

        $statistics = [
            'total_reviews' => $user->stuffReviews()->count(),
            'average_rating' => $user->stuffReviews()->avg('rating'),
            'rating_distribution' => $this->getRatingDistribution($user),
            'helpful_votes_received' => $user->stuffReviews()->sum('helpful_count'),
            'most_reviewed_category' => $this->getMostReviewedCategory($user),
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'reviews' => $reviews,
                'statistics' => $statistics,
            ],
        ]);
    }

    /**
     * Get user pages and content
     */
    public function getPagesAndContent(Request $request)
    {
        $user = Auth::user();

        $pages = $user->auraPages()
            ->where('status', 'published')
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 10);

        $statistics = [
            'total_pages' => $user->auraPages()->count(),
            'published_pages' => $user->auraPages()->where('status', 'published')->count(),
            'total_views' => $user->auraPages()->sum('view_count'),
            'average_views_per_page' => $user->auraPages()->avg('view_count'),
            'most_viewed_page' => $this->getMostViewedPage($user),
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'pages' => $pages,
                'statistics' => $statistics,
            ],
        ]);
    }

    /**
     * Get social activity and interactions
     */
    public function getSocialActivity(Request $request)
    {
        $user = Auth::user();

        $activity = [
            'followers' => $this->getUserFollowers($user),
            'following' => $this->getUserFollowing($user),
            'messages' => $this->getUserMessages($user),
            'notifications' => $this->getUserNotifications($user),
            'interactions' => $this->getUserInteractions($user),
        ];

        return response()->json([
            'success' => true,
            'data' => $activity,
        ]);
    }

    // Helper methods
    private function getTotalActivities($user)
    {
        // This would typically count from an activities table
        return rand(100, 500);
    }

    private function getThisWeekActivities($user)
    {
        return rand(10, 50);
    }

    private function getThisMonthActivities($user)
    {
        return rand(50, 200);
    }

    private function getMostActiveDay($user)
    {
        return 'Monday';
    }

    private function getCurrentStreak($user)
    {
        return rand(1, 30);
    }

    private function getCourseActivities($user)
    {
        return rand(20, 100);
    }

    private function getPurchaseActivities($user)
    {
        return rand(5, 25);
    }

    private function getReviewActivities($user)
    {
        return rand(10, 50);
    }

    private function getCertificateActivities($user)
    {
        return rand(1, 10);
    }

    private function getLoginActivities($user)
    {
        return rand(30, 100);
    }

    private function getRecentActivities($user, $limit)
    {
        return [
            [
                'id' => 1,
                'type' => 'course_enrollment',
                'title' => 'Enrolled in new course',
                'description' => 'Started learning "Advanced Laravel Development"',
                'timestamp' => now()->subHours(2),
                'icon' => 'academic-cap',
                'color' => 'blue',
                'data' => [
                    'course_id' => 123,
                    'course_title' => 'Advanced Laravel Development',
                ],
            ],
            [
                'id' => 2,
                'type' => 'certificate_earned',
                'title' => 'Certificate earned',
                'description' => 'Completed "JavaScript Fundamentals" course',
                'timestamp' => now()->subDays(1),
                'icon' => 'award',
                'color' => 'green',
                'data' => [
                    'certificate_id' => 456,
                    'course_title' => 'JavaScript Fundamentals',
                ],
            ],
            [
                'id' => 3,
                'type' => 'purchase',
                'title' => 'New purchase',
                'description' => 'Bought "Premium Template Pack"',
                'timestamp' => now()->subDays(3),
                'icon' => 'shopping-cart',
                'color' => 'purple',
                'data' => [
                    'order_id' => 789,
                    'product_name' => 'Premium Template Pack',
                    'amount' => 49.99,
                ],
            ],
        ];
    }

    private function getActivityChart($user, $days)
    {
        $chart = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $chart[] = [
                'date' => $date->format('Y-m-d'),
                'activities' => rand(0, 20),
                'courses' => rand(0, 5),
                'purchases' => rand(0, 2),
                'reviews' => rand(0, 3),
            ];
        }
        return $chart;
    }

    private function getFilteredActivities($user, $type, $period, $limit)
    {
        // This would typically query from an activities table
        // For now, return sample data
        return [
            'data' => $this->getRecentActivities($user, $limit),
            'pagination' => [
                'current_page' => 1,
                'per_page' => $limit,
                'total' => 100,
                'last_page' => 10,
            ],
        ];
    }

    private function getActivityById($user, $id)
    {
        // This would typically get from an activities table
        return [
            'id' => $id,
            'type' => 'course_enrollment',
            'title' => 'Enrolled in new course',
            'description' => 'Started learning "Advanced Laravel Development"',
            'timestamp' => now()->subHours(2),
            'icon' => 'academic-cap',
            'color' => 'blue',
            'details' => [
                'course' => [
                    'id' => 123,
                    'title' => 'Advanced Laravel Development',
                    'instructor' => 'John Doe',
                    'category' => 'Programming',
                    'duration' => '40 hours',
                ],
                'enrollment' => [
                    'enrolled_at' => now()->subHours(2),
                    'progress' => 0,
                    'estimated_completion' => now()->addDays(30),
                ],
            ],
        ];
    }

    private function getActivitySummary($user, $period)
    {
        return [
            'total_activities' => 250,
            'daily_average' => 8.3,
            'most_active_day' => 'Monday',
            'peak_hour' => '14:00',
            'activity_trend' => 'increasing',
        ];
    }

    private function getActivitiesByType($user, $period)
    {
        return [
            'course_activities' => 120,
            'purchase_activities' => 45,
            'review_activities' => 35,
            'certificate_activities' => 25,
            'login_activities' => 25,
        ];
    }

    private function getActivitiesByDay($user, $period)
    {
        return [
            'Monday' => 45,
            'Tuesday' => 38,
            'Wednesday' => 42,
            'Thursday' => 35,
            'Friday' => 40,
            'Saturday' => 28,
            'Sunday' => 22,
        ];
    }

    private function getActivitiesByHour($user, $period)
    {
        $hours = [];
        for ($i = 0; $i < 24; $i++) {
            $hours[sprintf('%02d:00', $i)] = rand(0, 15);
        }
        return $hours;
    }

    private function getActivityTrends($user, $period)
    {
        return [
            'this_week' => 55,
            'last_week' => 48,
            'this_month' => 220,
            'last_month' => 195,
            'trend' => 'up',
            'growth_rate' => 14.6,
        ];
    }

    private function getLearningAchievements($user)
    {
        return [
            [
                'id' => 1,
                'title' => 'Fast Learner',
                'description' => 'Complete 5 courses in one month',
                'icon' => 'lightning-bolt',
                'color' => 'yellow',
                'earned_at' => now()->subWeek(),
                'progress' => 100,
            ],
            [
                'id' => 2,
                'title' => 'Dedicated Student',
                'description' => 'Study for 100 hours total',
                'icon' => 'clock',
                'color' => 'blue',
                'earned_at' => now()->subMonth(),
                'progress' => 100,
            ],
        ];
    }

    private function getCourseRecommendations($user)
    {
        return [
            [
                'id' => 456,
                'title' => 'React Advanced Patterns',
                'description' => 'Learn advanced React patterns and best practices',
                'thumbnail' => 'courses/react-advanced.jpg',
                'instructor' => 'Jane Smith',
                'rating' => 4.8,
                'students' => 1250,
                'price' => 89.99,
                'reason' => 'Based on your interest in JavaScript',
            ],
            [
                'id' => 789,
                'title' => 'Node.js Microservices',
                'description' => 'Build scalable microservices with Node.js',
                'thumbnail' => 'courses/nodejs-microservices.jpg',
                'instructor' => 'Mike Johnson',
                'rating' => 4.9,
                'students' => 890,
                'price' => 79.99,
                'reason' => 'Popular among students who completed your courses',
            ],
        ];
    }

    private function getTotalSpent($user, $period)
    {
        return rand(100, 1000);
    }

    private function getTotalOrders($user, $period)
    {
        return rand(5, 25);
    }

    private function getAverageOrderValue($user, $period)
    {
        return rand(20, 100);
    }

    private function getMostPurchasedCategory($user, $period)
    {
        return 'Templates';
    }

    private function getMarketplaceOrders($user, $type, $period)
    {
        return $user->auraOrders()
            ->with('items.product')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    private function getStuffPurchases($user, $type, $period)
    {
        return $user->stuffPurchases()
            ->with('stuff')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    private function getSubscriptionHistory($user, $period)
    {
        return $user->subscriptions()
            ->with('plan')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    private function getSpendingChart($user, $period)
    {
        $chart = [];
        for ($i = 11; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $chart[] = [
                'month' => $month->format('Y-m'),
                'marketplace' => rand(0, 200),
                'stuff' => rand(0, 150),
                'subscriptions' => rand(20, 100),
                'total' => rand(50, 350),
            ];
        }
        return $chart;
    }

    private function getUserAchievements($user)
    {
        return [
            [
                'id' => 1,
                'title' => 'First Steps',
                'description' => 'Complete your first course',
                'icon' => 'academic-cap',
                'color' => 'blue',
                'earned_at' => now()->subMonths(2),
                'points' => 100,
            ],
            [
                'id' => 2,
                'title' => 'Knowledge Seeker',
                'description' => 'Enroll in 10 courses',
                'icon' => 'book-open',
                'color' => 'green',
                'earned_at' => now()->subMonth(),
                'points' => 250,
            ],
        ];
    }

    private function getUserBadges($user)
    {
        return [
            [
                'id' => 1,
                'name' => 'Early Adopter',
                'description' => 'Joined in the first month',
                'icon' => 'star',
                'color' => 'gold',
                'earned_at' => now()->subMonths(6),
            ],
            [
                'id' => 2,
                'name' => 'Active Learner',
                'description' => 'Logged in for 30 consecutive days',
                'icon' => 'fire',
                'color' => 'orange',
                'earned_at' => now()->subWeek(),
            ],
        ];
    }

    private function getUserMilestones($user)
    {
        return [
            [
                'id' => 1,
                'title' => '100 Learning Hours',
                'description' => 'Accumulated 100 hours of learning',
                'progress' => 85,
                'target' => 100,
                'current' => 85,
                'icon' => 'clock',
                'color' => 'blue',
            ],
            [
                'id' => 2,
                'title' => '50 Reviews',
                'description' => 'Write 50 product reviews',
                'progress' => 60,
                'target' => 50,
                'current' => 30,
                'icon' => 'star',
                'color' => 'yellow',
            ],
        ];
    }

    private function getRatingDistribution($user)
    {
        return [
            '5' => 15,
            '4' => 8,
            '3' => 3,
            '2' => 1,
            '1' => 0,
        ];
    }

    private function getMostReviewedCategory($user)
    {
        return 'Templates';
    }

    private function getMostViewedPage($user)
    {
        return $user->auraPages()->orderBy('view_count', 'desc')->first();
    }

    private function getUserFollowers($user)
    {
        return [
            'total' => 125,
            'recent' => [
                [
                    'id' => 1,
                    'name' => 'Alice Johnson',
                    'avatar' => 'avatars/alice.jpg',
                    'followed_at' => now()->subDays(2),
                ],
                [
                    'id' => 2,
                    'name' => 'Bob Smith',
                    'avatar' => 'avatars/bob.jpg',
                    'followed_at' => now()->subWeek(),
                ],
            ],
        ];
    }

    private function getUserFollowing($user)
    {
        return [
            'total' => 89,
            'recent' => [
                [
                    'id' => 3,
                    'name' => 'Charlie Brown',
                    'avatar' => 'avatars/charlie.jpg',
                    'followed_at' => now()->subDays(1),
                ],
                [
                    'id' => 4,
                    'name' => 'Diana Prince',
                    'avatar' => 'avatars/diana.jpg',
                    'followed_at' => now()->subDays(3),
                ],
            ],
        ];
    }

    private function getUserMessages($user)
    {
        return [
            'unread' => 3,
            'total' => 45,
            'recent' => [
                [
                    'id' => 1,
                    'sender' => 'Alice Johnson',
                    'subject' => 'Question about your course',
                    'preview' => 'Hi! I really enjoyed your Laravel course...',
                    'timestamp' => now()->subHours(2),
                    'is_read' => false,
                ],
                [
                    'id' => 2,
                    'sender' => 'Bob Smith',
                    'subject' => 'Collaboration opportunity',
                    'preview' => 'I saw your work and would like to discuss...',
                    'timestamp' => now()->subDay(),
                    'is_read' => true,
                ],
            ],
        ];
    }

    private function getUserNotifications($user)
    {
        return [
            'unread' => 5,
            'total' => 120,
            'recent' => [
                [
                    'id' => 1,
                    'type' => 'course_update',
                    'title' => 'New lesson available',
                    'message' => 'A new lesson has been added to "Advanced Laravel"',
                    'timestamp' => now()->subHours(1),
                    'is_read' => false,
                ],
                [
                    'id' => 2,
                    'type' => 'achievement',
                    'title' => 'Achievement unlocked!',
                    'message' => 'You\'ve earned the "Fast Learner" achievement',
                    'timestamp' => now()->subHours(6),
                    'is_read' => false,
                ],
            ],
        ];
    }

    private function getUserInteractions($user)
    {
        return [
            'likes_given' => 234,
            'likes_received' => 456,
            'comments_given' => 89,
            'comments_received' => 123,
            'shares' => 45,
            'mentions' => 67,
        ];
    }
}
