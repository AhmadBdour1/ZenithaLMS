<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProfilePublicController extends Controller
{
    /**
     * Get public profile data
     */
    public function getPublicProfile($username)
    {
        $user = User::where('username', $username)
            ->with(['roles', 'courses' => function ($query) {
                $query->withCount('users')->where('status', 'published');
            }])
            ->firstOrFail();

        // Check privacy settings
        $preferences = $user->preferences ?? [];
        $profileVisibility = $preferences['profile_visibility'] ?? 'everyone';
        
        if ($profileVisibility === 'private') {
            return response()->json([
                'success' => false,
                'message' => 'This profile is private',
            ], 403);
        }

        if ($profileVisibility === 'friends' && !Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'You must be logged in to view this profile',
            ], 401);
        }

        $publicData = [
            'user' => [
                'id' => $user->id,
                'username' => $user->username,
                'name' => $user->name,
                'avatar' => $user->avatar,
                'avatar_url' => $user->avatar_url,
                'bio' => $user->bio,
                'website' => $user->website,
                'country' => $user->country,
                'created_at' => $user->created_at,
                'roles' => $user->roles->map(function ($role) {
                    return [
                        'name' => $role->name,
                        'display_name' => $role->display_name ?? $role->name,
                    ];
                }),
            ],
            'social_links' => $this->getPublicSocialLinks($user),
            'statistics' => $this->getPublicStatistics($user),
            'courses' => $this->getPublicCourses($user),
            'certificates' => $this->getPublicCertificates($user),
            'achievements' => $this->getPublicAchievements($user),
            'recent_activity' => $this->getPublicRecentActivity($user),
        ];

        return response()->json([
            'success' => true,
            'data' => $publicData,
        ]);
    }

    /**
     * Get user's public courses
     */
    public function getPublicCourses($username)
    {
        $user = User::where('username', $username)->firstOrFail();

        $courses = $user->courses()
            ->with(['category', 'instructor'])
            ->withCount(['users as enrolled_users', 'reviews'])
            ->withAvg('reviews.rating as average_rating')
            ->where('status', 'published')
            ->orderBy('created_at', 'desc')
            ->paginate(12);

        return response()->json([
            'success' => true,
            'data' => $courses,
        ]);
    }

    /**
     * Get user's public certificates
     */
    public function getPublicCertificates($username)
    {
        $user = User::where('username', $username)->firstOrFail();

        // Check if certificates are public
        $preferences = $user->preferences ?? [];
        $showCertificates = $preferences['show_certificates'] ?? true;

        if (!$showCertificates) {
            return response()->json([
                'success' => false,
                'message' => 'Certificates are not publicly visible',
            ], 403);
        }

        $certificates = $user->certificates()
            ->with(['template', 'course'])
            ->where('status', 'issued')
            ->orderBy('issued_at', 'desc')
            ->paginate(12);

        return response()->json([
            'success' => true,
            'data' => $certificates,
        ]);
    }

    /**
     * Get user's public achievements
     */
    public function getPublicAchievements($username)
    {
        $user = User::where('username', $username)->firstOrFail();

        // Check if achievements are public
        $preferences = $user->preferences ?? [];
        $showAchievements = $preferences['show_achievements'] ?? true;

        if (!$showAchievements) {
            return response()->json([
                'success' => false,
                'message' => 'Achievements are not publicly visible',
            ], 403);
        }

        $achievements = $this->getUserPublicAchievements($user);

        return response()->json([
            'success' => true,
            'data' => $achievements,
        ]);
    }

    /**
     * Get user's public reviews
     */
    public function getPublicReviews($username)
    {
        $user = User::where('username', $username)->firstOrFail();

        $reviews = $user->stuffReviews()
            ->with(['stuff' => function ($query) {
                $query->select('id', 'name', 'thumbnail', 'price');
            }])
            ->withCount('helpfulVotes')
            ->orderBy('created_at', 'desc')
            ->paginate(12);

        return response()->json([
            'success' => true,
            'data' => $reviews,
        ]);
    }

    /**
     * Get user's public pages
     */
    public function getPublicPages($username)
    {
        $user = User::where('username', $username)->firstOrFail();

        $pages = $user->auraPages()
            ->where('status', 'published')
            ->orderBy('view_count', 'desc')
            ->paginate(12);

        return response()->json([
            'success' => true,
            'data' => $pages,
        ]);
    }

    /**
     * Follow user
     */
    public function followUser($username)
    {
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'You must be logged in to follow users',
            ], 401);
        }

        $targetUser = User::where('username', $username)->firstOrFail();
        $currentUser = Auth::user();

        // Check if already following
        if ($this->isFollowing($currentUser, $targetUser)) {
            return response()->json([
                'success' => false,
                'message' => 'You are already following this user',
            ], 400);
        }

        // Check privacy settings
        $preferences = $targetUser->preferences ?? [];
        $allowFollowRequests = $preferences['allow_friend_requests'] ?? true;

        if (!$allowFollowRequests) {
            return response()->json([
                'success' => false,
                'message' => 'This user does not allow follow requests',
            ], 403);
        }

        // Create follow relationship
        $this->createFollowRelationship($currentUser, $targetUser);

        return response()->json([
            'success' => true,
            'message' => 'User followed successfully',
            'data' => [
                'following' => true,
                'followers_count' => $this->getFollowersCount($targetUser),
            ],
        ]);
    }

    /**
     * Unfollow user
     */
    public function unfollowUser($username)
    {
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'You must be logged in to unfollow users',
            ], 401);
        }

        $targetUser = User::where('username', $username)->firstOrFail();
        $currentUser = Auth::user();

        if (!$this->isFollowing($currentUser, $targetUser)) {
            return response()->json([
                'success' => false,
                'message' => 'You are not following this user',
            ], 400);
        }

        // Remove follow relationship
        $this->removeFollowRelationship($currentUser, $targetUser);

        return response()->json([
            'success' => true,
            'message' => 'User unfollowed successfully',
            'data' => [
                'following' => false,
                'followers_count' => $this->getFollowersCount($targetUser),
            ],
        ]);
    }

    /**
     * Get user's followers
     */
    public function getFollowers($username)
    {
        $user = User::where('username', $username)->firstOrFail();

        $followers = $this->getUserFollowers($user);

        return response()->json([
            'success' => true,
            'data' => $followers,
        ]);
    }

    /**
     * Get user's following
     */
    public function getFollowing($username)
    {
        $user = User::where('username', $username)->firstOrFail();

        $following = $this->getUserFollowing($user);

        return response()->json([
            'success' => true,
            'data' => $following,
        ]);
    }

    /**
     * Send message to user
     */
    public function sendMessage(Request $request, $username)
    {
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'You must be logged in to send messages',
            ], 401);
        }

        $validator = Validator::make($request->all(), [
            'subject' => 'required|string|max:255',
            'message' => 'required|string|max:2000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $targetUser = User::where('username', $username)->firstOrFail();
        $currentUser = Auth::user();

        // Check privacy settings
        $preferences = $targetUser->preferences ?? [];
        $allowMessages = $preferences['allow_messages'] ?? true;

        if (!$allowMessages) {
            return response()->json([
                'success' => false,
                'message' => 'This user does not allow messages',
            ], 403);
        }

        // Create message
        $message = $this->createMessage($currentUser, $targetUser, $request->subject, $request->message);

        return response()->json([
            'success' => true,
            'message' => 'Message sent successfully',
            'data' => $message,
        ]);
    }

    /**
     * Report user
     */
    public function reportUser(Request $request, $username)
    {
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'You must be logged in to report users',
            ], 401);
        }

        $validator = Validator::make($request->all(), [
            'reason' => 'required|in:spam,inappropriate,harassment,fake_profile,other',
            'description' => 'required|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $targetUser = User::where('username', $username)->firstOrFail();
        $currentUser = Auth::user();

        // Create report
        $report = $this->createUserReport($currentUser, $targetUser, $request->reason, $request->description);

        return response()->json([
            'success' => true,
            'message' => 'User reported successfully',
            'data' => $report,
        ]);
    }

    /**
     * Block user
     */
    public function blockUser($username)
    {
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'You must be logged in to block users',
            ], 401);
        }

        $targetUser = User::where('username', $username)->firstOrFail();
        $currentUser = Auth::user();

        if ($this->isBlocked($currentUser, $targetUser)) {
            return response()->json([
                'success' => false,
                'message' => 'User is already blocked',
            ], 400);
        }

        // Create block relationship
        $this->createBlockRelationship($currentUser, $targetUser);

        return response()->json([
            'success' => true,
            'message' => 'User blocked successfully',
        ]);
    }

    /**
     * Unblock user
     */
    public function unblockUser($username)
    {
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'You must be logged in to unblock users',
            ], 401);
        }

        $targetUser = User::where('username', $username)->firstOrFail();
        $currentUser = Auth::user();

        if (!$this->isBlocked($currentUser, $targetUser)) {
            return response()->json([
                'success' => false,
                'message' => 'User is not blocked',
            ], 400);
        }

        // Remove block relationship
        $this->removeBlockRelationship($currentUser, $targetUser);

        return response()->json([
            'success' => true,
            'message' => 'User unblocked successfully',
        ]);
    }

    /**
     * Get user's public portfolio
     */
    public function getPublicPortfolio($username)
    {
        $user = User::where('username', $username)->firstOrFail();

        $portfolio = [
            'user' => [
                'id' => $user->id,
                'username' => $user->username,
                'name' => $user->name,
                'avatar' => $user->avatar,
                'avatar_url' => $user->avatar_url,
                'bio' => $user->bio,
                'website' => $user->website,
                'location' => $user->country,
                'joined_at' => $user->created_at,
            ],
            'skills' => $this->getUserSkills($user),
            'experience' => $this->getUserExperience($user),
            'education' => $this->getUserEducation($user),
            'portfolio_items' => $this->getUserPortfolioItems($user),
            'testimonials' => $this->getUserTestimonials($user),
            'contact_info' => $this->getPublicContactInfo($user),
        ];

        return response()->json([
            'success' => true,
            'data' => $portfolio,
        ]);
    }

    // Helper methods
    private function getPublicSocialLinks($user)
    {
        $socialLinks = $user->social_links ?? [];
        $preferences = $user->preferences ?? [];

        return array_filter($socialLinks, function ($value, $key) use ($preferences) {
            // Only return social links that are not empty and user has chosen to show them
            return !empty($value) && ($preferences["show_{$key}"] ?? true);
        });
    }

    private function getPublicStatistics($user)
    {
        $preferences = $user->preferences ?? [];
        $showEmail = $preferences['show_email'] ?? false;
        $showPhone = $preferences['show_phone'] ?? false;

        return [
            'courses_created' => $user->courses()->where('status', 'published')->count(),
            'courses_enrolled' => $user->enrollments()->count(),
            'certificates_earned' => $user->certificates()->where('status', 'issued')->count(),
            'reviews_count' => $user->stuffReviews()->count(),
            'average_rating' => $user->stuffReviews()->avg('rating'),
            'followers_count' => $this->getFollowersCount($user),
            'following_count' => $this->getFollowingCount($user),
            'profile_views' => $user->profile_views ?? 0,
            'show_email' => $showEmail,
            'show_phone' => $showPhone,
            'email' => $showEmail ? $user->email : null,
            'phone' => $showPhone ? $user->phone : null,
        ];
    }

    private function getPublicCourses($user)
    {
        $preferences = $user->preferences ?? [];
        $showCourses = $preferences['show_courses'] ?? true;

        if (!$showCourses) {
            return [];
        }

        return $user->courses()
            ->with(['category', 'instructor'])
            ->withCount(['users as enrolled_users', 'reviews'])
            ->withAvg('reviews.rating as average_rating')
            ->where('status', 'published')
            ->orderBy('created_at', 'desc')
            ->take(6)
            ->get()
            ->map(function ($course) {
                return [
                    'id' => $course->id,
                    'title' => $course->title,
                    'thumbnail' => $course->thumbnail,
                    'category' => $course->category,
                    'price' => $course->price,
                    'enrolled_users' => $course->enrolled_users,
                    'average_rating' => $course->average_rating,
                    'reviews_count' => $course->reviews_count,
                    'created_at' => $course->created_at,
                ];
            });
    }

    private function getPublicCertificates($user)
    {
        $preferences = $user->preferences ?? [];
        $showCertificates = $preferences['show_certificates'] ?? true;

        if (!$showCertificates) {
            return [];
        }

        return $user->certificates()
            ->with(['template', 'course'])
            ->where('status', 'issued')
            ->orderBy('issued_at', 'desc')
            ->take(6)
            ->get()
            ->map(function ($certificate) {
                return [
                    'id' => $certificate->id,
                    'template_name' => $certificate->template->name,
                    'course_title' => $certificate->course?->title,
                    'issued_at' => $certificate->issued_at,
                    'expires_at' => $certificate->expires_at,
                ];
            });
    }

    private function getPublicAchievements($user)
    {
        $preferences = $user->preferences ?? [];
        $showAchievements = $preferences['show_achievements'] ?? true;

        if (!$showAchievements) {
            return [];
        }

        return $this->getUserPublicAchievements($user);
    }

    private function getPublicRecentActivity($user)
    {
        // Get recent public activities
        return [
            [
                'type' => 'course_created',
                'title' => 'Created new course',
                'description' => 'Published "Advanced Laravel Development"',
                'timestamp' => now()->subDays(2),
                'icon' => 'academic-cap',
                'color' => 'blue',
            ],
            [
                'type' => 'certificate_earned',
                'title' => 'Certificate earned',
                'description' => 'Completed "JavaScript Fundamentals"',
                'timestamp' => now()->subWeek(),
                'icon' => 'award',
                'color' => 'green',
            ],
        ];
    }

    private function getUserPublicAchievements($user)
    {
        return [
            [
                'id' => 1,
                'title' => 'Course Creator',
                'description' => 'Created 5 published courses',
                'icon' => 'academic-cap',
                'color' => 'blue',
                'earned_at' => now()->subMonth(),
            ],
            [
                'id' => 2,
                'title' => 'Expert Reviewer',
                'description' => 'Written 25 helpful reviews',
                'icon' => 'star',
                'color' => 'yellow',
                'earned_at' => now()->subWeeks(2),
            ],
        ];
    }

    private function isFollowing($currentUser, $targetUser)
    {
        // This would typically check a follows table
        return false;
    }

    private function createFollowRelationship($currentUser, $targetUser)
    {
        // This would typically create a record in a follows table
        // For now, just return true
        return true;
    }

    private function removeFollowRelationship($currentUser, $targetUser)
    {
        // This would typically remove a record from a follows table
        return true;
    }

    private function getFollowersCount($user)
    {
        // This would typically count from a follows table
        return rand(50, 500);
    }

    private function getFollowingCount($user)
    {
        // This would typically count from a follows table
        return rand(20, 200);
    }

    private function getUserFollowers($user)
    {
        // This would typically get from a follows table
        return [
            'data' => [
                [
                    'id' => 1,
                    'name' => 'Alice Johnson',
                    'username' => 'alice',
                    'avatar' => 'avatars/alice.jpg',
                    'followed_at' => now()->subDays(2),
                ],
                [
                    'id' => 2,
                    'name' => 'Bob Smith',
                    'username' => 'bob',
                    'avatar' => 'avatars/bob.jpg',
                    'followed_at' => now()->subWeek(),
                ],
            ],
            'pagination' => [
                'current_page' => 1,
                'per_page' => 20,
                'total' => 125,
                'last_page' => 7,
            ],
        ];
    }

    private function getUserFollowing($user)
    {
        // This would typically get from a follows table
        return [
            'data' => [
                [
                    'id' => 3,
                    'name' => 'Charlie Brown',
                    'username' => 'charlie',
                    'avatar' => 'avatars/charlie.jpg',
                    'followed_at' => now()->subDays(1),
                ],
                [
                    'id' => 4,
                    'name' => 'Diana Prince',
                    'username' => 'diana',
                    'avatar' => 'avatars/diana.jpg',
                    'followed_at' => now()->subDays(3),
                ],
            ],
            'pagination' => [
                'current_page' => 1,
                'per_page' => 20,
                'total' => 89,
                'last_page' => 5,
            ],
        ];
    }

    private function createMessage($currentUser, $targetUser, $subject, $message)
    {
        // This would typically create a message record
        return [
            'id' => Str::random(10),
            'subject' => $subject,
            'message' => $message,
            'sent_at' => now(),
            'status' => 'sent',
        ];
    }

    private function createUserReport($currentUser, $targetUser, $reason, $description)
    {
        // This would typically create a report record
        return [
            'id' => Str::random(10),
            'reason' => $reason,
            'description' => $description,
            'reported_at' => now(),
            'status' => 'pending',
        ];
    }

    private function isBlocked($currentUser, $targetUser)
    {
        // This would typically check a blocks table
        return false;
    }

    private function createBlockRelationship($currentUser, $targetUser)
    {
        // This would typically create a record in a blocks table
        return true;
    }

    private function removeBlockRelationship($currentUser, $targetUser)
    {
        // This would typically remove a record from a blocks table
        return true;
    }

    private function getUserSkills($user)
    {
        // This would typically get from a skills table
        return [
            ['name' => 'Laravel', 'level' => 'Expert'],
            ['name' => 'JavaScript', 'level' => 'Advanced'],
            ['name' => 'React', 'level' => 'Intermediate'],
            ['name' => 'PHP', 'level' => 'Expert'],
            ['name' => 'MySQL', 'level' => 'Advanced'],
        ];
    }

    private function getUserExperience($user)
    {
        // This would typically get from an experience table
        return [
            [
                'title' => 'Senior Laravel Developer',
                'company' => 'Tech Company',
                'period' => '2020 - Present',
                'description' => 'Leading Laravel development team',
            ],
            [
                'title' => 'Full Stack Developer',
                'company' => 'Startup Inc',
                'period' => '2018 - 2020',
                'description' => 'Developed web applications using Laravel and Vue.js',
            ],
        ];
    }

    private function getUserEducation($user)
    {
        // This would typically get from an education table
        return [
            [
                'degree' => 'Bachelor of Computer Science',
                'institution' => 'University Name',
                'period' => '2014 - 2018',
                'description' => 'Focused on software engineering and web development',
            ],
        ];
    }

    private function getUserPortfolioItems($user)
    {
        // This would typically get from a portfolio table
        return [
            [
                'title' => 'E-commerce Platform',
                'description' => 'Full-stack e-commerce solution with Laravel and Vue.js',
                'image' => 'portfolio/ecommerce.jpg',
                'url' => 'https://example.com/ecommerce',
                'technologies' => ['Laravel', 'Vue.js', 'MySQL', 'Redis'],
            ],
            [
                'title' => 'Learning Management System',
                'description' => 'Custom LMS built with Laravel and Tailwind CSS',
                'image' => 'portfolio/lms.jpg',
                'url' => 'https://example.com/lms',
                'technologies' => ['Laravel', 'Tailwind CSS', 'MySQL', 'AWS'],
            ],
        ];
    }

    private function getUserTestimonials($user)
    {
        // This would typically get from a testimonials table
        return [
            [
                'name' => 'John Doe',
                'role' => 'CEO at Tech Company',
                'content' => 'Excellent developer with great problem-solving skills',
                'avatar' => 'testimonials/john.jpg',
                'date' => now()->subMonths(2),
            ],
            [
                'name' => 'Jane Smith',
                'role' => 'Project Manager',
                'content' => 'Delivered high-quality work on time and within budget',
                'avatar' => 'testimonials/jane.jpg',
                'date' => now()->subMonths(4),
            ],
        ];
    }

    private function getPublicContactInfo($user)
    {
        $preferences = $user->preferences ?? [];
        $allowMessages = $preferences['allow_messages'] ?? true;

        return [
            'allow_messages' => $allowMessages,
            'email' => $allowMessages ? $user->email : null,
            'website' => $user->website,
            'social_links' => $this->getPublicSocialLinks($user),
        ];
    }
}
