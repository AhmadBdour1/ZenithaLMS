<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Marketplace;
use App\Models\MarketplaceSale;
use App\Models\MarketplaceLicense;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MarketplaceController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum')->except(['index', 'show', 'featured', 'search']);
    }

    /**
     * Get all marketplace items with filters
     */
    public function index(Request $request)
    {
        $query = Marketplace::with(['vendor:id,name,avatar', 'category', 'reviews'])
            ->approved();

        // Filters
        if ($request->type) {
            $query->byType($request->type);
        }

        if ($request->category) {
            $query->byCategory($request->category);
        }

        if ($request->min_price) {
            $query->where('price', '>=', $request->min_price);
        }

        if ($request->max_price) {
            $query->where('price', '<=', $request->max_price);
        }

        if ($request->featured) {
            $query->featured();
        }

        if ($request->sort) {
            switch ($request->sort) {
                case 'price_low':
                    $query->orderBy('price', 'asc');
                    break;
                case 'price_high':
                    $query->orderBy('price', 'desc');
                    break;
                case 'rating':
                    $query->orderBy('rating', 'desc');
                    break;
                case 'sales':
                    $query->orderBy('sales_count', 'desc');
                    break;
                case 'newest':
                    $query->orderBy('created_at', 'desc');
                    break;
                default:
                    $query->orderBy('featured', 'desc')->orderBy('created_at', 'desc');
            }
        } else {
            $query->orderBy('featured', 'desc')->orderBy('created_at', 'desc');
        }

        $items = $query->paginate(12);

        return response()->json([
            'success' => true,
            'data' => $items->map(function ($item) {
                return [
                    'id' => $item->id,
                    'name' => $item->name,
                    'description' => Str::limit($item->description, 150),
                    'type' => $item->type,
                    'price' => $item->price,
                    'vendor' => [
                        'id' => $item->vendor->id,
                        'name' => $item->vendor->name,
                        'avatar' => $item->vendor->avatar,
                    ],
                    'category' => $item->category->name,
                    'thumbnail' => $item->thumbnail,
                    'rating' => $item->rating,
                    'reviews_count' => $item->reviews_count,
                    'sales_count' => $item->sales_count,
                    'is_featured' => $item->is_featured,
                    'slug' => $item->slug,
                    'created_at' => $item->created_at->format('Y-m-d'),
                ];
            }),
            'pagination' => [
                'current_page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
                'per_page' => $items->perPage(),
                'total' => $items->total(),
            ],
        ]);
    }

    /**
     * Get featured marketplace items
     */
    public function featured()
    {
        $items = Marketplace::with(['vendor:id,name,avatar', 'category'])
            ->approved()
            ->featured()
            ->take(8)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $items->map(function ($item) {
                return [
                    'id' => $item->id,
                    'name' => $item->name,
                    'description' => Str::limit($item->description, 100),
                    'type' => $item->type,
                    'price' => $item->price,
                    'vendor' => $item->vendor->name,
                    'thumbnail' => $item->thumbnail,
                    'rating' => $item->rating,
                    'sales_count' => $item->sales_count,
                    'slug' => $item->slug,
                ];
            }),
        ]);
    }

    /**
     * Search marketplace items
     */
    public function search(Request $request)
    {
        $query = $request->get('q');
        
        if (!$query) {
            return response()->json([
                'success' => false,
                'message' => 'Search query is required',
            ], 400);
        }

        $items = Marketplace::with(['vendor:id,name', 'category'])
            ->approved()
            ->where(function ($q) use ($query) {
                $q->where('name', 'LIKE', "%{$query}%")
                  ->orWhere('description', 'LIKE', "%{$query}%")
                  ->orWhere('tags', 'LIKE', "%{$query}%");
            })
            ->paginate(12);

        return response()->json([
            'success' => true,
            'data' => $items->map(function ($item) {
                return [
                    'id' => $item->id,
                    'name' => $item->name,
                    'description' => Str::limit($item->description, 150),
                    'type' => $item->type,
                    'price' => $item->price,
                    'vendor' => $item->vendor->name,
                    'category' => $item->category->name,
                    'thumbnail' => $item->thumbnail,
                    'rating' => $item->rating,
                    'sales_count' => $item->sales_count,
                    'slug' => $item->slug,
                ];
            }),
            'pagination' => [
                'current_page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
                'per_page' => $items->perPage(),
                'total' => $items->total(),
            ],
        ]);
    }

    /**
     * Get single marketplace item details
     */
    public function show($slug)
    {
        $item = Marketplace::with([
            'vendor:id,name,avatar,bio,created_at',
            'category',
            'reviews' => function ($query) {
                $query->with('user:id,name,avatar')->latest()->take(10);
            }
        ])
        ->where('slug', $slug)
        ->approved()
        ->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $item->id,
                'name' => $item->name,
                'description' => $item->description,
                'type' => $item->type,
                'price' => $item->price,
                'commission_rate' => $item->commission_rate,
                'vendor' => [
                    'id' => $item->vendor->id,
                    'name' => $item->vendor->name,
                    'avatar' => $item->vendor->avatar,
                    'bio' => $item->vendor->bio,
                    'member_since' => $item->vendor->created_at->format('Y-m-d'),
                ],
                'category' => $item->category,
                'thumbnail' => $item->thumbnail,
                'gallery' => $item->gallery,
                'rating' => $item->rating,
                'reviews_count' => $item->reviews_count,
                'sales_count' => $item->sales_count,
                'download_count' => $item->download_count,
                'version' => $item->version,
                'last_updated' => $item->last_updated,
                'file_size' => $item->file_size,
                'requirements' => $item->requirements,
                'compatibility' => $item->compatibility,
                'preview_url' => $item->preview_url,
                'demo_url' => $item->demo_url,
                'support_url' => $item->support_url,
                'documentation_url' => $item->documentation_url,
                'tags' => $item->tags,
                'reviews' => $item->reviews->map(function ($review) {
                    return [
                        'id' => $review->id,
                        'rating' => $review->rating,
                        'comment' => $review->comment,
                        'user' => [
                            'name' => $review->user->name,
                            'avatar' => $review->user->avatar,
                        ],
                        'created_at' => $review->created_at->format('Y-m-d'),
                    ];
                }),
                'can_purchase' => auth()->check() ? $item->canBePurchasedBy(auth()->user()) : false,
                'created_at' => $item->created_at->format('Y-m-d'),
            ],
        ]);
    }

    /**
     * Create new marketplace item (for vendors)
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'type' => 'required|in:course,ebook,template,plugin',
            'price' => 'required|numeric|min:0|max:9999.99',
            'category_id' => 'required|exists:categories,id',
            'thumbnail' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            'gallery' => 'array|max:5',
            'gallery.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
            'requirements' => 'array',
            'compatibility' => 'array',
            'preview_url' => 'nullable|url',
            'demo_url' => 'nullable|url',
            'documentation_url' => 'nullable|url',
            'tags' => 'array|max:10',
            'tags.*' => 'string|max:50',
            'file' => 'required_if:type,ebook,template,plugin|file|max:50000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = auth()->user();

        // Check if user is approved vendor
        if (!$user->is_approved_vendor) {
            return response()->json([
                'success' => false,
                'message' => 'Your vendor account is not approved yet',
            ], 403);
        }

        DB::beginTransaction();

        try {
            // Handle file uploads
            $thumbnailPath = $request->file('thumbnail')->store('marketplace/thumbnails', 'public');
            
            $galleryPaths = [];
            if ($request->hasFile('gallery')) {
                foreach ($request->file('gallery') as $image) {
                    $galleryPaths[] = $image->store('marketplace/gallery', 'public');
                }
            }

            $filePath = null;
            $fileSize = 0;
            if ($request->hasFile('file')) {
                $filePath = $request->file('file')->store('marketplace/files', 'private');
                $fileSize = $request->file('file')->getSize();
            }

            // Create marketplace item
            $item = Marketplace::create([
                'name' => $request->name,
                'description' => $request->description,
                'type' => $request->type,
                'price' => $request->price,
                'commission_rate' => config('marketplace.default_commission', 15),
                'vendor_id' => $user->id,
                'category_id' => $request->category_id,
                'thumbnail' => $thumbnailPath,
                'gallery' => $galleryPaths,
                'requirements' => $request->requirements,
                'compatibility' => $request->compatibility,
                'preview_url' => $request->preview_url,
                'demo_url' => $request->demo_url,
                'documentation_url' => $request->documentation_url,
                'tags' => $request->tags,
                'file_path' => $filePath,
                'file_size' => $fileSize,
                'slug' => Str::slug($request->name) . '-' . time(),
                'status' => 'pending', // Requires admin approval
                'version' => '1.0.0',
                'last_updated' => now(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Marketplace item submitted for approval',
                'data' => [
                    'id' => $item->id,
                    'slug' => $item->slug,
                    'status' => $item->status,
                ],
            ], 201);

        } catch (\Exception $e) {
            DB::rollback();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to create marketplace item',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Purchase marketplace item
     */
    public function purchase(Request $request, $id)
    {
        $item = Marketplace::approved()->findOrFail($id);
        $user = auth()->user();

        // Check if user can purchase
        if (!$item->canBePurchasedBy($user)) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot purchase this item',
            ], 403);
        }

        // Check user balance
        if ($user->wallet_balance < $item->price) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient balance',
            ], 400);
        }

        DB::beginTransaction();

        try {
            // Create sale record
            $sale = MarketplaceSale::create([
                'marketplace_id' => $item->id,
                'user_id' => $user->id,
                'vendor_id' => $item->vendor_id,
                'amount' => $item->price,
                'commission_amount' => $item->calculateCommission($item->price),
                'vendor_earnings' => $item->getVendorEarnings($item->price),
                'status' => 'completed',
            ]);

            // Generate license
            $license = $item->generateLicense($user);

            // Update item stats
            $item->incrementSales();

            // Update user balance
            $user->decrement('wallet_balance', $item->price);

            // Add funds to vendor
            $vendor = $item->vendor;
            $vendor->increment('wallet_balance', $sale->vendor_earnings);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Purchase successful',
                'data' => [
                    'sale_id' => $sale->id,
                    'license_key' => $license->license_key,
                    'download_url' => $item->file_path ? route('marketplace.download', $license->license_key) : null,
                ],
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            
            return response()->json([
                'success' => false,
                'message' => 'Purchase failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Download purchased item
     */
    public function download($licenseKey)
    {
        $license = MarketplaceLicense::with(['marketplace', 'user'])
            ->where('license_key', $licenseKey)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        // Check if license is valid
        if ($license->expires_at && $license->expires_at < now()) {
            return response()->json([
                'success' => false,
                'message' => 'License has expired',
            ], 403);
        }

        $item = $license->marketplace;

        if (!$item->file_path) {
            return response()->json([
                'success' => false,
                'message' => 'No file available for download',
            ], 404);
        }

        // Increment download count
        $item->increment('download_count');

        return Storage::disk('private')->download($item->file_path, $item->name . '.' . pathinfo($item->file_path, PATHINFO_EXTENSION));
    }
}
