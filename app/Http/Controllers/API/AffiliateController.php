<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Affiliate;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cookie;

class AffiliateController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum')->except(['track', 'landing']);
    }

    /**
     * Track affiliate click
     */
    public function track(Request $request)
    {
        $affiliateCode = $request->get('ref') ?: $request->cookie('affiliate_ref');
        
        if (!$affiliateCode) {
            return response()->json(['success' => false], 400);
        }

        $affiliate = Affiliate::active()->where('affiliate_code', $affiliateCode)->first();
        
        if (!$affiliate) {
            return response()->json(['success' => false], 404);
        }

        // Track the click
        $click = $affiliate->trackClick();

        // Set affiliate cookie
        $cookieDuration = $affiliate->cookie_duration * 24 * 60; // Convert days to minutes
        Cookie::queue('affiliate_ref', $affiliateCode, $cookieDuration);

        return response()->json([
            'success' => true,
            'affiliate_id' => $affiliate->id,
            'click_id' => $click->id,
        ]);
    }

    /**
     * Landing page for affiliate links
     */
    public function landing($slug)
    {
        $affiliate = Affiliate::active()->where('custom_slug', $slug)->firstOrFail();
        
        // Track click
        $affiliate->trackClick();
        
        // Set cookie
        $cookieDuration = $affiliate->cookie_duration * 24 * 60;
        Cookie::queue('affiliate_ref', $affiliate->affiliate_code, $cookieDuration);
        
        // Redirect to home or custom landing page
        return redirect()->route('home')->with('affiliate_ref', $affiliate->affiliate_code);
    }

    /**
     * Get affiliate dashboard
     */
    public function dashboard(Request $request)
    {
        $user = $request->user();
        $affiliate = $user->affiliate;

        if (!$affiliate) {
            return response()->json([
                'success' => false,
                'message' => 'No affiliate account found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'affiliate' => [
                    'code' => $affiliate->affiliate_code,
                    'custom_slug' => $affiliate->custom_slug,
                    'commission_rate' => $affiliate->commission_rate,
                    'commission_type' => $affiliate->commission_type,
                    'is_active' => $affiliate->is_active,
                    'approved_at' => $affiliate->approved_at?->format('Y-m-d'),
                ],
                'urls' => [
                    'affiliate_url' => $affiliate->getAffiliateUrl(),
                    'custom_url' => $affiliate->getCustomUrl(),
                ],
                'stats' => [
                    'total_earnings' => $affiliate->getTotalEarnings(),
                    'pending_earnings' => $affiliate->getPendingEarnings(),
                    'total_clicks' => $affiliate->getTotalClicks(),
                    'total_conversions' => $affiliate->getTotalConversions(),
                    'conversion_rate' => $affiliate->getConversionRate(),
                    'earnings_this_month' => $affiliate->getEarningsThisMonth(),
                ],
                'performance' => [
                    'last_7_days' => $affiliate->getStats('7days'),
                    'last_30_days' => $affiliate->getStats('30days'),
                    'last_year' => $affiliate->getStats('1year'),
                ],
                'can_request_payout' => $affiliate->canRequestPayout(),
                'minimum_payout' => $affiliate->minimum_payout,
            ],
        ]);
    }

    /**
     * Apply for affiliate program
     */
    public function apply(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'custom_slug' => 'nullable|string|max:20|unique:affiliates,custom_slug',
            'payout_method' => 'required|in:wallet,bank_transfer,paypal',
            'payout_details' => 'required|array',
            'website' => 'nullable|url',
            'social_media' => 'nullable|array',
            'marketing_method' => 'required|string',
            'estimated_sales' => 'nullable|string',
            'notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();

        // Check if user already has affiliate account
        if ($user->affiliate) {
            return response()->json([
                'success' => false,
                'message' => 'You already have an affiliate account',
            ], 400);
        }

        // Create affiliate account
        $affiliate = Affiliate::create([
            'user_id' => $user->id,
            'commission_rate' => config('affiliate.default_commission', 10),
            'commission_type' => 'percentage',
            'minimum_payout' => config('affiliate.minimum_payout', 50),
            'cookie_duration' => config('affiliate.cookie_duration', 30),
            'payout_method' => $request->payout_method,
            'payout_details' => $request->payout_details,
            'custom_slug' => $request->custom_slug,
            'is_active' => false, // Requires approval
            'meta_data' => [
                'website' => $request->website,
                'social_media' => $request->social_media,
                'marketing_method' => $request->marketing_method,
                'estimated_sales' => $request->estimated_sales,
                'applied_at' => now()->toISOString(),
            ],
        ]);

        // Generate affiliate code
        $affiliate->generateAffiliateCode();

        return response()->json([
            'success' => true,
            'message' => 'Affiliate application submitted successfully',
            'data' => [
                'affiliate_id' => $affiliate->id,
                'affiliate_code' => $affiliate->affiliate_code,
                'status' => 'pending_approval',
            ],
        ], 201);
    }

    /**
     * Get affiliate stats
     */
    public function stats(Request $request)
    {
        $user = $request->user();
        $affiliate = $user->affiliate;

        if (!$affiliate || !$affiliate->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'No active affiliate account found',
            ], 404);
        }

        $period = $request->get('period', '30days');

        return response()->json([
            'success' => true,
            'data' => $affiliate->getStats($period),
        ]);
    }

    /**
     * Get affiliate commissions
     */
    public function commissions(Request $request)
    {
        $user = $request->user();
        $affiliate = $user->affiliate;

        if (!$affiliate) {
            return response()->json([
                'success' => false,
                'message' => 'No affiliate account found',
            ], 404);
        }

        $commissions = $affiliate->commissions()
            ->with('conversion')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $commissions->map(function ($commission) {
                return [
                    'id' => $commission->id,
                    'amount' => $commission->amount,
                    'status' => $commission->status,
                    'type' => $commission->type,
                    'conversion' => [
                        'id' => $commission->conversion->id,
                        'type' => $commission->conversion->type,
                        'amount' => $commission->conversion->amount,
                        'reference_id' => $commission->conversion->reference_id,
                    ],
                    'created_at' => $commission->created_at->format('Y-m-d H:i:s'),
                ];
            }),
            'pagination' => [
                'current_page' => $commissions->currentPage(),
                'last_page' => $commissions->lastPage(),
                'per_page' => $commissions->perPage(),
                'total' => $commissions->total(),
            ],
        ]);
    }

    /**
     * Request payout
     */
    public function requestPayout(Request $request)
    {
        $user = $request->user();
        $affiliate = $user->affiliate;

        if (!$affiliate) {
            return response()->json([
                'success' => false,
                'message' => 'No affiliate account found',
            ], 404);
        }

        if (!$affiliate->canRequestPayout()) {
            return response()->json([
                'success' => false,
                'message' => 'Minimum payout amount not reached',
                'minimum_payout' => $affiliate->minimum_payout,
                'pending_earnings' => $affiliate->getPendingEarnings(),
            ], 400);
        }

        $payout = $affiliate->requestPayout();

        if (!$payout) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to request payout',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Payout request submitted successfully',
            'data' => [
                'payout_id' => $payout->id,
                'amount' => $payout->amount,
                'status' => $payout->status,
            ],
        ]);
    }

    /**
     * Get marketing materials
     */
    public function marketingMaterials(Request $request)
    {
        $user = $request->user();
        $affiliate = $user->affiliate;

        if (!$affiliate || !$affiliate->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'No active affiliate account found',
            ], 404);
        }

        $materials = [
            'banners' => [
                [
                    'id' => 1,
                    'name' => 'ZenithaLMS - Learn Anything',
                    'size' => '728x90',
                    'image_url' => asset('images/affiliate/banners/728x90.jpg'),
                    'affiliate_url' => $affiliate->getAffiliateUrl(),
                ],
                [
                    'id' => 2,
                    'name' => 'ZenithaLMS - Start Teaching',
                    'size' => '300x250',
                    'image_url' => asset('images/affiliate/banners/300x250.jpg'),
                    'affiliate_url' => $affiliate->getAffiliateUrl(),
                ],
                [
                    'id' => 3,
                    'name' => 'ZenithaLMS - Marketplace',
                    'size' => '160x600',
                    'image_url' => asset('images/affiliate/banners/160x600.jpg'),
                    'affiliate_url' => $affiliate->getAffiliateUrl(),
                ],
            ],
            'text_links' => [
                [
                    'id' => 1,
                    'name' => 'General Link',
                    'text' => 'Start your learning journey with ZenithaLMS',
                    'url' => $affiliate->getAffiliateUrl(),
                ],
                [
                    'id' => 2,
                    'name' => 'Instructor Link',
                    'text' => 'Become an instructor on ZenithaLMS',
                    'url' => $affiliate->getAffiliateUrl(route('instructor.register')),
                ],
                [
                    'id' => 3,
                    'name' => 'Marketplace Link',
                    'text' => 'Discover amazing courses and resources on ZenithaLMS Marketplace',
                    'url' => $affiliate->getAffiliateUrl(route('marketplace')),
                ],
            ],
            'email_templates' => [
                [
                    'id' => 1,
                    'name' => 'General Promotion',
                    'subject' => 'Check out ZenithaLMS - The Ultimate Learning Platform',
                    'body' => "Hi there!\n\nI wanted to share this amazing learning platform I found called ZenithaLMS. They have courses on everything from programming to business skills.\n\nUse my link to get started: {$affiliate->getAffiliateUrl()}\n\nBest regards",
                ],
                [
                    'id' => 2,
                    'name' => 'Instructor Opportunity',
                    'subject' => 'Teach What You Love on ZenithaLMS',
                    'body' => "Hello!\n\nIf you're passionate about teaching, you should check out ZenithaLMS. You can create and sell courses to thousands of students.\n\nStart teaching today: {$affiliate->getAffiliateUrl(route('instructor.register'))}\n\nCheers!",
                ],
            ],
            'social_media' => [
                [
                    'id' => 1,
                    'platform' => 'Twitter',
                    'text' => "Just discovered ZenithaLMS - an amazing platform for online learning and teaching! 🚚 {$affiliate->getAffiliateUrl()} #OnlineLearning #Elearning",
                ],
                [
                    'id' => 2,
                    'platform' => 'Facebook',
                    'text' => "Looking to learn new skills or teach what you know? ZenithaLMS is perfect for both students and instructors. Check it out! {$affiliate->getAffiliateUrl()}",
                ],
                [
                    'id' => 3,
                    'platform' => 'LinkedIn',
                    'text' => "I'm excited to share ZenithaLMS, a comprehensive learning management system that's revolutionizing online education. Perfect for professionals looking to upskill or share their expertise. {$affiliate->getAffiliateUrl()} #ProfessionalDevelopment #OnlineEducation",
                ],
            ],
        ];

        return response()->json([
            'success' => true,
            'data' => $materials,
        ]);
    }
}
