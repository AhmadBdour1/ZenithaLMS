<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\AuraProduct;
use App\Models\AuraCategory;
use App\Models\AuraOrder;
use App\Models\AuraOrderItem;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AuraStoreController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum')->except(['index', 'show', 'categories', 'search']);
    }

    /**
     * Get all products with filters
     */
    public function index(Request $request)
    {
        $query = AuraProduct::with(['category', 'vendor'])
            ->published()
            ->visible();

        // Filter by category
        if ($request->category) {
            $query->byCategory($request->category);
        }

        // Filter by type
        if ($request->type) {
            $query->byType($request->type);
        }

        // Filter by vendor
        if ($request->vendor) {
            $query->byVendor($request->vendor);
        }

        // Filter by price range
        if ($request->min_price) {
            $query->where('price', '>=', $request->min_price);
        }

        if ($request->max_price) {
            $query->where('price', '<=', $request->max_price);
        }

        // Filter by status
        if ($request->featured) {
            $query->featured();
        }

        if ($request->on_sale) {
            $query->onSale();
        }

        if ($request->in_stock) {
            $query->inStock();
        }

        // Search
        if ($request->search) {
            $query->search($request->search);
        }

        // Sort
        $sort = $request->get('sort', 'latest');
        switch ($sort) {
            case 'price_low':
                $query->orderBy('price', 'asc');
                break;
            case 'price_high':
                $query->orderBy('price', 'desc');
                break;
            case 'rating':
                $query->orderBy('average_rating', 'desc');
                break;
            case 'sales':
                $query->orderBy('total_sales', 'desc');
                break;
            case 'name':
                $query->orderBy('name', 'asc');
                break;
            case 'latest':
            default:
                $query->latest();
                break;
        }

        $products = $query->paginate(12);

        return response()->json([
            'success' => true,
            'data' => $products->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'slug' => $product->slug,
                    'short_description' => $product->short_description,
                    'type' => $product->type,
                    'price' => $product->price,
                    'display_price' => $product->getDisplayPrice(),
                    'is_on_sale' => $product->isOnSale(),
                    'sale_price' => $product->sale_price,
                    'sale_percentage' => $product->getSalePercentage(),
                    'product_image' => $product->product_image,
                    'gallery_images' => $product->gallery_images,
                    'category' => [
                        'id' => $product->category->id,
                        'name' => $product->category->name,
                        'slug' => $product->category->slug,
                    ],
                    'vendor' => [
                        'id' => $product->vendor->id,
                        'name' => $product->vendor->name,
                        'avatar' => $product->vendor->avatar,
                    ],
                    'average_rating' => $product->average_rating,
                    'rating_count' => $product->rating_count,
                    'total_sales' => $product->total_sales,
                    'stock_status' => $product->stock_status,
                    'is_featured' => $product->featured,
                    'tags' => $product->tags,
                    'created_at' => $product->created_at->format('Y-m-d'),
                ];
            }),
            'pagination' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
            ],
        ]);
    }

    /**
     * Get single product details
     */
    public function show($slug)
    {
        $product = AuraProduct::with([
            'category',
            'vendor',
            'reviews' => function ($query) {
                $query->approved()->latest()->take(10);
            }
        ])
        ->where('slug', $slug)
        ->published()
        ->visible()
        ->firstOrFail();

        // Increment view count
        $product->incrementViewCount();

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'description' => $product->description,
                'short_description' => $product->short_description,
                'type' => $product->type,
                'price' => $product->price,
                'display_price' => $product->getDisplayPrice(),
                'is_on_sale' => $product->isOnSale(),
                'sale_price' => $product->sale_price,
                'sale_percentage' => $product->getSalePercentage(),
                'sale_start_date' => $product->sale_start_date?->format('Y-m-d'),
                'sale_end_date' => $product->sale_end_date?->format('Y-m-d'),
                'sku' => $product->sku,
                'stock_status' => $product->stock_status,
                'stock_quantity' => $product->stock_quantity,
                'weight' => $product->weight,
                'dimensions' => $product->dimensions,
                'product_image' => $product->product_image,
                'gallery_images' => $product->gallery_images,
                'category' => [
                    'id' => $product->category->id,
                    'name' => $product->category->name,
                    'slug' => $product->category->slug,
                    'description' => $product->category->description,
                ],
                'vendor' => [
                    'id' => $product->vendor->id,
                    'name' => $product->vendor->name,
                    'avatar' => $product->vendor->avatar,
                    'bio' => $product->vendor->bio,
                ],
                'tags' => $product->tags,
                'attributes' => $product->attributes,
                'variations' => $product->variations,
                'downloadable' => $product->downloadable,
                'virtual' => $product->virtual,
                'downloadable_files' => $product->downloadable ? $product->getDownloadableFiles() : [],
                'requirements' => $product->requirements,
                'compatibility' => $product->compatibility,
                'documentation_url' => $product->documentation_url,
                'support_url' => $product->support_url,
                'demo_url' => $product->demo_url,
                'version' => $product->version,
                'last_updated' => $product->last_updated?->format('Y-m-d'),
                'license_type' => $product->license_type,
                'faqs' => $product->faqs,
                'average_rating' => $product->average_rating,
                'rating_count' => $product->rating_count,
                'total_sales' => $product->total_sales,
                'view_count' => $product->view_count,
                'reviews_allowed' => $product->reviews_allowed,
                'reviews' => $product->reviews->map(function ($review) {
                    return [
                        'id' => $review->id,
                        'rating' => $review->rating,
                        'title' => $review->title,
                        'content' => $review->content,
                        'user' => [
                            'name' => $review->user->name,
                            'avatar' => $review->user->avatar,
                        ],
                        'created_at' => $review->created_at->format('Y-m-d'),
                    ];
                }),
                'related_products' => $product->getRelatedProducts(4)->map(function ($related) {
                    return [
                        'id' => $related->id,
                        'name' => $related->name,
                        'slug' => $related->slug,
                        'price' => $related->price,
                        'display_price' => $related->getDisplayPrice(),
                        'product_image' => $related->product_image,
                        'average_rating' => $related->average_rating,
                    ];
                }),
                'upsell_products' => $product->getUpsellProducts(4)->map(function ($upsell) {
                    return [
                        'id' => $upsell->id,
                        'name' => $upsell->name,
                        'slug' => $upsell->slug,
                        'price' => $upsell->price,
                        'display_price' => $upsell->getDisplayPrice(),
                        'product_image' => $upsell->product_image,
                        'average_rating' => $upsell->average_rating,
                    ];
                }),
                'cross_sell_products' => $product->getCrossSellProducts(4)->map(function ($crossSell) {
                    return [
                        'id' => $crossSell->id,
                        'name' => $crossSell->name,
                        'slug' => $crossSell->slug,
                        'price' => $crossSell->price,
                        'display_price' => $crossSell->getDisplayPrice(),
                        'product_image' => $crossSell->product_image,
                        'average_rating' => $crossSell->average_rating,
                    ];
                }),
                'schema_data' => $product->getSchemaData(),
                'created_at' => $product->created_at->format('Y-m-d'),
            ],
        ]);
    }

    /**
     * Get categories
     */
    public function categories(Request $request)
    {
        $categories = AuraCategory::active()
            ->with(['children' => function ($query) {
                $query->active()->ordered();
            }])
            ->ordered()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $categories->map(function ($category) {
                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                    'description' => $category->description,
                    'image' => $category->image,
                    'icon' => $category->icon,
                    'color' => $category->color,
                    'product_count' => $category->product_count,
                    'has_children' => $category->hasChildren(),
                    'children' => $category->children->map(function ($child) {
                        return [
                            'id' => $child->id,
                            'name' => $child->name,
                            'slug' => $child->slug,
                            'product_count' => $child->product_count,
                        ];
                    }),
                ];
            }),
        ]);
    }

    /**
     * Search products
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

        $products = AuraProduct::with(['category', 'vendor'])
            ->published()
            ->visible()
            ->search($query)
            ->paginate(12);

        return response()->json([
            'success' => true,
            'data' => $products->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'slug' => $product->slug,
                    'short_description' => $product->short_description,
                    'type' => $product->type,
                    'price' => $product->price,
                    'display_price' => $product->getDisplayPrice(),
                    'is_on_sale' => $product->isOnSale(),
                    'sale_price' => $product->sale_price,
                    'sale_percentage' => $product->getSalePercentage(),
                    'product_image' => $product->product_image,
                    'category' => [
                        'id' => $product->category->id,
                        'name' => $product->category->name,
                        'slug' => $product->category->slug,
                    ],
                    'average_rating' => $product->average_rating,
                    'rating_count' => $product->rating_count,
                    'total_sales' => $product->total_sales,
                ];
            }),
            'pagination' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
            ],
        ]);
    }

    /**
     * Add to cart
     */
    public function addToCart(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:aura_products,id',
            'quantity' => 'required|integer|min:1|max:10',
            'variation_id' => 'nullable|exists:aura_product_variations,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();
        $product = AuraProduct::findOrFail($request->product_id);

        // Check if product can be purchased
        if (!$product->canBePurchased($request->quantity)) {
            return response()->json([
                'success' => false,
                'message' => 'Product cannot be purchased',
            ], 400);
        }

        // Add to cart (implementation depends on cart system)
        $cart = $user->cart ?? $user->cart()->create();
        
        $cartItem = $cart->items()->updateOrCreate([
            'product_id' => $product->id,
            'variation_id' => $request->variation_id,
        ], [
            'quantity' => DB::raw("quantity + {$request->quantity}"),
            'price' => $product->getDisplayPrice(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Product added to cart',
            'data' => [
                'cart_item_id' => $cartItem->id,
                'cart_items_count' => $cart->items()->sum('quantity'),
                'cart_total' => $cart->getTotal(),
            ],
        ]);
    }

    /**
     * Get cart
     */
    public function cart(Request $request)
    {
        $user = $request->user();
        $cart = $user->cart;

        if (!$cart) {
            return response()->json([
                'success' => true,
                'data' => [
                    'items' => [],
                    'subtotal' => 0,
                    'total' => 0,
                    'items_count' => 0,
                ],
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'items' => $cart->items->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'product' => [
                            'id' => $item->product->id,
                            'name' => $item->product->name,
                            'slug' => $item->product->slug,
                            'product_image' => $item->product->product_image,
                        ],
                        'quantity' => $item->quantity,
                        'price' => $item->price,
                        'total' => $item->price * $item->quantity,
                    ];
                }),
                'subtotal' => $cart->getSubtotal(),
                'total' => $cart->getTotal(),
                'items_count' => $cart->items->sum('quantity'),
            ],
        ]);
    }

    /**
     * Checkout
     */
    public function checkout(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'billing_address' => 'required|array',
            'shipping_address' => 'required|array',
            'payment_method' => 'required|string',
            'payment_details' => 'required|array',
            'customer_notes' => 'nullable|string',
            'coupon_code' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();
        $cart = $user->cart;

        if (!$cart || $cart->items->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Cart is empty',
            ], 400);
        }

        DB::beginTransaction();

        try {
            // Create order
            $order = AuraOrder::create([
                'user_id' => $user->id,
                'status' => 'pending',
                'currency' => 'USD',
                'payment_method' => $request->payment_method,
                'billing_address' => $request->billing_address,
                'shipping_address' => $request->shipping_address,
                'customer_notes' => $request->customer_notes,
                'coupon_code' => $request->coupon_code,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            // Add items to order
            foreach ($cart->items as $cartItem) {
                $order->addItem($cartItem->product, $cartItem->quantity, $cartItem->price);
            }

            // Generate order number
            $order->generateOrderNumber();

            // Process payment
            $order->processPayment($request->payment_method, $request->payment_details);

            // Clear cart
            $cart->items()->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Order placed successfully',
                'data' => [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'total' => $order->getFormattedTotal(),
                    'status' => $order->status,
                ],
            ], 201);

        } catch (\Exception $e) {
            DB::rollback();
            
            return response()->json([
                'success' => false,
                'message' => 'Checkout failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get user's orders
     */
    public function orders(Request $request)
    {
        $user = $request->user();
        
        $orders = $user->auraOrders()
            ->with(['items.product'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json([
            'success' => true,
            'data' => $orders->map(function ($order) {
                return [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'status' => $order->status,
                    'payment_status' => $order->payment_status,
                    'total' => $order->getFormattedTotal(),
                    'items_count' => $order->items_count,
                    'created_at' => $order->created_at->format('Y-m-d H:i:s'),
                    'items' => $order->items->map(function ($item) {
                        return [
                            'product_name' => $item->getProductName(),
                            'product_slug' => $item->getProductSlug(),
                            'product_image' => $item->getProductImage(),
                            'quantity' => $item->quantity,
                            'price' => $item->getFormattedPrice(),
                            'total' => $item->getFormattedTotal(),
                            'is_downloadable' => $item->isDownloadable(),
                        ];
                    }),
                ];
            }),
            'pagination' => [
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'per_page' => $orders->perPage(),
                'total' => $orders->total(),
            ],
        ]);
    }

    /**
     * Get order details
     */
    public function order($id)
    {
        $user = request()->user();
        
        $order = $user->auraOrders()
            ->with(['items.product', 'transactions', 'statusHistory'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'status' => $order->status,
                'payment_status' => $order->payment_status,
                'currency' => $order->currency,
                'subtotal' => number_format($order->subtotal, 2),
                'tax_amount' => number_format($order->tax_amount, 2),
                'shipping_amount' => number_format($order->shipping_amount, 2),
                'discount_amount' => number_format($order->discount_amount, 2),
                'total' => $order->getFormattedTotal(),
                'payment_method' => $order->payment_method,
                'billing_address' => $order->billing_address,
                'shipping_address' => $order->shipping_address,
                'customer_notes' => $order->customer_notes,
                'admin_notes' => $order->admin_notes,
                'items_count' => $order->items_count,
                'shipping_method' => $order->shipping_method,
                'tracking_number' => $order->tracking_number,
                'estimated_delivery' => $order->estimated_delivery?->format('Y-m-d'),
                'actual_delivery' => $order->actual_delivery?->format('Y-m-d'),
                'created_at' => $order->created_at->format('Y-m-d H:i:s'),
                'items' => $order->items->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'product' => [
                            'id' => $item->product->id,
                            'name' => $item->product->name,
                            'slug' => $item->product->slug,
                            'product_image' => $item->product->product_image,
                        ],
                        'quantity' => $item->quantity,
                        'price' => $item->getFormattedPrice(),
                        'total' => $item->getFormattedTotal(),
                        'is_downloadable' => $item->isDownloadable(),
                        'downloadable_files' => $item->getDownloadableFiles(),
                    ];
                }),
                'transactions' => $order->transactions->map(function ($transaction) {
                    return [
                        'id' => $transaction->id,
                        'type' => $transaction->type,
                        'amount' => number_format($transaction->amount, 2),
                        'status' => $transaction->status,
                        'transaction_id' => $transaction->transaction_id,
                        'created_at' => $transaction->created_at->format('Y-m-d H:i:s'),
                    ];
                }),
                'status_history' => $order->statusHistory->map(function ($history) {
                    return [
                        'old_status' => $history->old_status,
                        'new_status' => $history->new_status,
                        'notes' => $history->notes,
                        'changed_at' => $history->created_at->format('Y-m-d H:i:s'),
                    ];
                }),
            ],
        ]);
    }

    /**
     * Download product file
     */
    public function download($slug, $fileId)
    {
        $user = request()->user();
        
        $product = AuraProduct::where('slug', $slug)->firstOrFail();
        
        if (!$product->canBeDownloadedBy($user)) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to download this file',
            ], 403);
        }

        $file = collect($product->downloadable_files)->firstWhere('id', $fileId);
        
        if (!$file) {
            return response()->json([
                'success' => false,
                'message' => 'File not found',
            ], 404);
        }

        // Record download
        $product->downloads()->create([
            'user_id' => $user->id,
            'file_id' => $fileId,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        // Return download URL or file
        return response()->json([
            'success' => true,
            'message' => 'Download started',
            'data' => [
                'download_url' => $file['url'],
                'filename' => $file['name'],
            ],
        ]);
    }
}
