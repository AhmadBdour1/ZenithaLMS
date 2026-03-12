<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Stuff;
use App\Models\StuffCategory;
use App\Models\StuffReview;
use App\Models\StuffPurchase;
use App\Models\StuffLicense;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StuffController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum')->except(['index', 'show', 'search', 'categories', 'featured', 'popular', 'trending']);
    }

    /**
     * Get all stuff with filters
     */
    public function index(Request $request)
    {
        $query = Stuff::query();

        // Filters
        if ($request->type) {
            $query->byType($request->type);
        }
        if ($request->category_id) {
            $query->byCategory($request->category_id);
        }
        if ($request->vendor_id) {
            $query->byVendor($request->vendor_id);
        }
        if ($request->status) {
            $query->where('status', $request->status);
        }
        if ($request->featured) {
            $query->featured();
        }
        if ($request->premium) {
            $query->premium();
        }
        if ($request->free) {
            $query->free();
        }
        if ($request->paid) {
            $query->paid();
        }
        if ($request->on_sale) {
            $query->onSale();
        }
        if ($request->new) {
            $query->new();
        }
        if ($request->trending) {
            $query->trending();
        }
        if ($request->best_seller) {
            $query->bestSeller();
        }
        if ($request->popular) {
            $query->popular();
        }
        if ($request->in_stock) {
            $query->inStock();
        }
        if ($request->search) {
            $query->search($request->search);
        }
        if ($request->min_price) {
            $query->where('price', '>=', $request->min_price);
        }
        if ($request->max_price) {
            $query->where('price', '<=', $request->max_price);
        }
        if ($request->rating) {
            $query->where('rating', '>=', $request->rating);
        }

        // Sort
        $sort = $request->sort ?? 'created_at';
        $order = $request->order ?? 'desc';
        $query->orderBy($sort, $order);

        $stuff = $query->with(['vendor', 'category', 'subcategory'])
            ->paginate($request->per_page ?? 20);

        return response()->json([
            'success' => true,
            'data' => $stuff,
        ]);
    }

    /**
     * Get single stuff
     */
    public function show($id)
    {
        $stuff = Stuff::with(['vendor', 'category', 'subcategory', 'reviews' => function ($query) {
            $query->approved()->latest();
        }, 'faqs', 'tutorials'])->findOrFail($id);

        // Increment view count
        $stuff->incrementView();

        return response()->json([
            'success' => true,
            'data' => $stuff,
        ]);
    }

    /**
     * Create new stuff
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'short_description' => 'nullable|string|max:500',
            'type' => 'required|in:digital,physical,service,template,tool,resource',
            'category_id' => 'required|exists:stuff_categories,id',
            'subcategory_id' => 'nullable|exists:stuff_categories,id',
            'price' => 'required|numeric|min:0',
            'sale_price' => 'nullable|numeric|min:0',
            'currency' => 'required|string|size:3',
            'sku' => 'nullable|string|max:50|unique:stuff,sku',
            'stock_quantity' => 'nullable|integer|min:0',
            'weight' => 'nullable|numeric|min:0',
            'dimensions' => 'nullable|array',
            'digital_file' => 'nullable|file|max:102400',
            'thumbnail' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'gallery' => 'nullable|array',
            'preview_images' => 'nullable|array',
            'demo_url' => 'nullable|url',
            'documentation_url' => 'nullable|url',
            'support_url' => 'nullable|url',
            'video_url' => 'nullable|url',
            'requirements' => 'nullable|array',
            'compatibility' => 'nullable|array',
            'tags' => 'nullable|string|max:500',
            'seo_title' => 'nullable|string|max:255',
            'seo_description' => 'nullable|string|max:500',
            'seo_keywords' => 'nullable|string|max:500',
            'license_type' => 'required|in:single,multi,resale,private_label,commercial',
            'license_terms' => 'nullable|string',
            'download_limit' => 'nullable|integer|min:0',
            'download_expiry_days' => 'nullable|integer|min:0',
            'featured' => 'boolean',
            'premium' => 'boolean',
            'affiliate_enabled' => 'boolean',
            'bulk_discount_enabled' => 'boolean',
            'bulk_discount_tiers' => 'nullable|array',
            'subscription_required' => 'boolean',
            'required_subscription_tier' => 'nullable|string',
            'access_level' => 'nullable|in:free,basic,premium,enterprise',
            'age_restriction' => 'nullable|integer|min:0',
            'content_warning' => 'nullable|string|max:500',
            'language' => 'nullable|string|size:2',
            'regions' => 'nullable|array',
            'custom_fields' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $request->all();
        $data['vendor_id'] = auth()->id();
        $data['slug'] = Str::slug($request->name);
        $data['sku'] = $request->sku ?: $this->generateSku();
        $data['status'] = 'pending';
        $data['is_digital'] = in_array($request->type, ['digital', 'template', 'tool', 'resource']);
        $data['is_physical'] = $request->type === 'physical';
        $data['requires_shipping'] = $request->type === 'physical';
        $data['taxable'] = true;
        $data['commission_rate'] = 10;
        $data['support_level'] = 'basic';
        $data['update_frequency'] = 'monthly';
        $data['renewal_period'] = 'monthly';

        // Handle digital file upload
        if ($request->hasFile('digital_file')) {
            $data['digital_file'] = $request->file('digital_file')->store('stuff-files', 'public');
        }

        // Handle thumbnail upload
        if ($request->hasFile('thumbnail')) {
            $data['thumbnail'] = $request->file('thumbnail')->store('stuff-thumbnails', 'public');
        }

        $stuff = Stuff::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Stuff created successfully',
            'data' => $stuff->fresh(['vendor', 'category', 'subcategory']),
        ], 201);
    }

    /**
     * Update stuff
     */
    public function update(Request $request, $id)
    {
        $stuff = Stuff::findOrFail($id);

        // Check ownership
        if ($stuff->vendor_id !== auth()->id() && !auth()->user()->hasRole('admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string',
            'short_description' => 'nullable|string|max:500',
            'type' => 'sometimes|required|in:digital,physical,service,template,tool,resource',
            'category_id' => 'sometimes|required|exists:stuff_categories,id',
            'subcategory_id' => 'nullable|exists:stuff_categories,id',
            'price' => 'sometimes|required|numeric|min:0',
            'sale_price' => 'nullable|numeric|min:0',
            'currency' => 'sometimes|required|string|size:3',
            'sku' => 'nullable|string|max:50|unique:stuff,sku,' . $id,
            'stock_quantity' => 'nullable|integer|min:0',
            'weight' => 'nullable|numeric|min:0',
            'dimensions' => 'nullable|array',
            'digital_file' => 'nullable|file|max:102400',
            'thumbnail' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'gallery' => 'nullable|array',
            'preview_images' => 'nullable|array',
            'demo_url' => 'nullable|url',
            'documentation_url' => 'nullable|url',
            'support_url' => 'nullable|url',
            'video_url' => 'nullable|url',
            'requirements' => 'nullable|array',
            'compatibility' => 'nullable|array',
            'tags' => 'nullable|string|max:500',
            'seo_title' => 'nullable|string|max:255',
            'seo_description' => 'nullable|string|max:500',
            'seo_keywords' => 'nullable|string|max:500',
            'license_type' => 'sometimes|required|in:single,multi,resale,private_label,commercial',
            'license_terms' => 'nullable|string',
            'download_limit' => 'nullable|integer|min:0',
            'download_expiry_days' => 'nullable|integer|min:0',
            'featured' => 'boolean',
            'premium' => 'boolean',
            'affiliate_enabled' => 'boolean',
            'bulk_discount_enabled' => 'boolean',
            'bulk_discount_tiers' => 'nullable|array',
            'subscription_required' => 'boolean',
            'required_subscription_tier' => 'nullable|string',
            'access_level' => 'nullable|in:free,basic,premium,enterprise',
            'age_restriction' => 'nullable|integer|min:0',
            'content_warning' => 'nullable|string|max:500',
            'language' => 'nullable|string|size:2',
            'regions' => 'nullable|array',
            'custom_fields' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $request->all();

        // Update slug if name changed
        if ($request->has('name')) {
            $data['slug'] = Str::slug($request->name);
        }

        // Update digital file
        if ($request->hasFile('digital_file')) {
            if ($stuff->digital_file) {
                Storage::disk('public')->delete($stuff->digital_file);
            }
            $data['digital_file'] = $request->file('digital_file')->store('stuff-files', 'public');
        }

        // Update thumbnail
        if ($request->hasFile('thumbnail')) {
            if ($stuff->thumbnail) {
                Storage::disk('public')->delete($stuff->thumbnail);
            }
            $data['thumbnail'] = $request->file('thumbnail')->store('stuff-thumbnails', 'public');
        }

        $stuff->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Stuff updated successfully',
            'data' => $stuff->fresh(['vendor', 'category', 'subcategory']),
        ]);
    }

    /**
     * Delete stuff
     */
    public function destroy($id)
    {
        $stuff = Stuff::findOrFail($id);

        // Check ownership
        if ($stuff->vendor_id !== auth()->id() && !auth()->user()->hasRole('admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $stuff->delete();

        return response()->json([
            'success' => true,
            'message' => 'Stuff deleted successfully',
        ]);
    }

    /**
     * Search stuff
     */
    public function search(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'q' => 'required|string|min:2|max:100',
            'type' => 'nullable|in:digital,physical,service,template,tool,resource',
            'category_id' => 'nullable|exists:stuff_categories,id',
            'min_price' => 'nullable|numeric|min:0',
            'max_price' => 'nullable|numeric|min:0',
            'rating' => 'nullable|numeric|min:0|max:5',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $query = Stuff::search($request->q)->active();

        if ($request->type) {
            $query->byType($request->type);
        }
        if ($request->category_id) {
            $query->byCategory($request->category_id);
        }
        if ($request->min_price) {
            $query->where('price', '>=', $request->min_price);
        }
        if ($request->max_price) {
            $query->where('price', '<=', $request->max_price);
        }
        if ($request->rating) {
            $query->where('rating', '>=', $request->rating);
        }

        $results = $query->with(['vendor', 'category'])
            ->take(20)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $results,
        ]);
    }

    /**
     * Get categories
     */
    public function categories(Request $request)
    {
        $categories = StuffCategory::active()
            ->withCount(['stuff' => function ($query) {
                $query->active();
            }])
            ->ordered()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $categories,
        ]);
    }

    /**
     * Get featured stuff
     */
    public function featured(Request $request)
    {
        $stuff = Stuff::featured()
            ->active()
            ->with(['vendor', 'category'])
            ->latest()
            ->take($request->limit ?? 10)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $stuff,
        ]);
    }

    /**
     * Get popular stuff
     */
    public function popular(Request $request)
    {
        $stuff = Stuff::popular()
            ->active()
            ->with(['vendor', 'category'])
            ->latest()
            ->take($request->limit ?? 10)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $stuff,
        ]);
    }

    /**
     * Get trending stuff
     */
    public function trending(Request $request)
    {
        $stuff = Stuff::trending()
            ->active()
            ->with(['vendor', 'category'])
            ->latest()
            ->take($request->limit ?? 10)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $stuff,
        ]);
    }

    /**
     * Get new stuff
     */
    public function new(Request $request)
    {
        $stuff = Stuff::new()
            ->active()
            ->with(['vendor', 'category'])
            ->latest()
            ->take($request->limit ?? 10)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $stuff,
        ]);
    }

    /**
     * Get best sellers
     */
    public function bestSellers(Request $request)
    {
        $stuff = Stuff::bestSeller()
            ->active()
            ->with(['vendor', 'category'])
            ->latest()
            ->take($request->limit ?? 10)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $stuff,
        ]);
    }

    /**
     * Get on sale stuff
     */
    public function onSale(Request $request)
    {
        $stuff = Stuff::onSale()
            ->active()
            ->with(['vendor', 'category'])
            ->latest()
            ->take($request->limit ?? 10)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $stuff,
        ]);
    }

    /**
     * Get free stuff
     */
    public function free(Request $request)
    {
        $stuff = Stuff::free()
            ->active()
            ->with(['vendor', 'category'])
            ->latest()
            ->take($request->limit ?? 10)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $stuff,
        ]);
    }

    /**
     * Purchase stuff
     */
    public function purchase(Request $request, $id)
    {
        $stuff = Stuff::findOrFail($id);

        // Check if user can purchase
        if (!$stuff->canPurchase(auth()->user())) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot purchase this item',
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'quantity' => 'required|integer|min:1',
            'license_type' => 'required|in:single,multi,resale,private_label,commercial',
            'payment_method' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $quantity = $request->quantity;
        $price = $stuff->calculatePrice($quantity, auth()->user());
        $currency = $stuff->currency;

        // Create purchase
        $purchase = StuffPurchase::create([
            'stuff_id' => $stuff->id,
            'user_id' => auth()->id(),
            'quantity' => $quantity,
            'price' => $stuff->getCurrentPrice(),
            'currency' => $currency,
            'subtotal' => $price,
            'total_amount' => $price,
            'payment_method' => $request->payment_method,
            'payment_status' => 'pending',
            'status' => 'pending',
            'purchase_type' => 'one_time',
            'license_type' => $request->license_type,
            'download_limit' => $stuff->download_limit,
            'download_expiry_date' => $stuff->download_expiry_days ? now()->addDays($stuff->download_expiry_days) : null,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        // Process payment (placeholder)
        $paymentSuccess = $this->processPayment($purchase, $request->payment_method);

        if ($paymentSuccess) {
            $purchase->completePayment();
        } else {
            $purchase->failPayment();
        }

        return response()->json([
            'success' => $paymentSuccess,
            'message' => $paymentSuccess ? 'Purchase completed successfully' : 'Payment failed',
            'data' => $purchase->fresh(['stuff', 'user']),
        ]);
    }

    /**
     * Download stuff
     */
    public function download($id)
    {
        $purchase = StuffPurchase::where('user_id', auth()->id())
            ->where('stuff_id', $id)
            ->active()
            ->firstOrFail();

        if (!$purchase->canDownload()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot download this item',
            ], 400);
        }

        $stuff = $purchase->stuff;

        if (!$stuff->digital_file) {
            return response()->json([
                'success' => false,
                'message' => 'No digital file available',
            ], 404);
        }

        // Record download
        $download = $purchase->recordDownload();

        if ($download) {
            $filePath = storage_path('app/public/' . $stuff->digital_file);
            
            if (file_exists($filePath)) {
                return response()->download($filePath);
            }
        }

        return response()->json([
            'success' => false,
            'message' => 'Download failed',
        ], 500);
    }

    /**
     * Get user purchases
     */
    public function purchases(Request $request)
    {
        $purchases = StuffPurchase::where('user_id', auth()->id())
            ->with(['stuff.vendor', 'stuff.category'])
            ->latest()
            ->paginate($request->per_page ?? 20);

        return response()->json([
            'success' => true,
            'data' => $purchases,
        ]);
    }

    /**
     * Get user licenses
     */
    public function licenses(Request $request)
    {
        $licenses = StuffLicense::where('user_id', auth()->id())
            ->with(['stuff', 'activations'])
            ->latest()
            ->paginate($request->per_page ?? 20);

        return response()->json([
            'success' => true,
            'data' => $licenses,
        ]);
    }

    /**
     * Add review
     */
    public function addReview(Request $request, $id)
    {
        $stuff = Stuff::findOrFail($id);

        // Check if user has purchased this stuff
        $purchase = StuffPurchase::where('user_id', auth()->id())
            ->where('stuff_id', $stuff->id)
            ->completed()
            ->first();

        if (!$purchase) {
            return response()->json([
                'success' => false,
                'message' => 'You must purchase this item to review it',
            ], 400);
        }

        // Check if user has already reviewed
        $existingReview = StuffReview::where('user_id', auth()->id())
            ->where('stuff_id', $stuff->id)
            ->first();

        if ($existingReview) {
            return response()->json([
                'success' => false,
                'message' => 'You have already reviewed this item',
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'rating' => 'required|numeric|min:1|max:5',
            'title' => 'required|string|max:255',
            'content' => 'required|string|max:2000',
            'pros' => 'nullable|string|max:1000',
            'cons' => 'nullable|string|max:1000',
            'recommendation' => 'nullable|in:yes,no,maybe',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $review = StuffReview::create([
            'stuff_id' => $stuff->id,
            'user_id' => auth()->id(),
            'rating' => $request->rating,
            'title' => $request->title,
            'content' => $request->content,
            'pros' => $request->pros,
            'cons' => $request->cons,
            'recommendation' => $request->recommendation,
            'verified_purchase' => true,
            'status' => 'pending',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Review submitted successfully',
            'data' => $review->fresh(['user', 'stuff']),
        ], 201);
    }

    /**
     * Get reviews for stuff
     */
    public function reviews(Request $request, $id)
    {
        $stuff = Stuff::findOrFail($id);

        $reviews = $stuff->reviews()
            ->approved()
            ->with(['user'])
            ->latest()
            ->paginate($request->per_page ?? 20);

        return response()->json([
            'success' => true,
            'data' => $reviews,
        ]);
    }

    /**
     * Mark review as helpful
     */
    public function markReviewHelpful(Request $request, $stuffId, $reviewId)
    {
        $review = StuffReview::findOrFail($reviewId);

        $helpfulCount = $review->markHelpful(auth()->id());

        return response()->json([
            'success' => true,
            'message' => 'Review marked as helpful',
            'helpful_count' => $helpfulCount,
        ]);
    }

    // Helper methods
    private function generateSku()
    {
        $prefix = 'STF';
        $random = strtoupper(Str::random(8));
        return $prefix . '-' . $random;
    }

    private function processPayment($purchase, $paymentMethod)
    {
        // Implement payment processing logic
        // This would integrate with payment gateways like Stripe, PayPal, etc.
        return true; // Placeholder
    }
}
