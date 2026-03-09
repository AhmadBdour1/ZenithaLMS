<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class AuraProduct extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'short_description',
        'type', // 'template', 'plugin', 'addon', 'theme', 'bundle'
        'category_id',
        'price',
        'sale_price',
        'is_on_sale',
        'sale_start_date',
        'sale_end_date',
        'vendor_id',
        'sku',
        'barcode',
        'weight',
        'dimensions', // length, width, height
        'stock_quantity',
        'stock_status', // 'in_stock', 'out_of_stock', 'on_backorder'
        'manage_stock',
        'allow_backorders',
        'low_stock_amount',
        'sold_individually',
        'purchase_note',
        'status', // 'draft', 'pending', 'publish', 'private'
        'featured',
        'catalog_visibility', // 'visible', 'catalog', 'search', 'hidden'
        'product_image',
        'gallery_images',
        'tags',
        'attributes',
        'default_attributes',
        'variations',
        'downloadable',
        'virtual',
        'download_limit',
        'download_expiry',
        'downloadable_files',
        'external_url',
        'button_text',
        'reviews_allowed',
        'rating_count',
        'average_rating',
        'total_sales',
        'view_count',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'schema_data',
        'compatibility',
        'requirements',
        'documentation_url',
        'support_url',
        'demo_url',
        'version',
        'last_updated',
        'update_frequency',
        'license_type',
        'license_key_required',
        'auto_update_enabled',
        'installation_guide',
        'changelog',
        'faqs',
        'custom_fields',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'is_on_sale' => 'boolean',
        'sale_start_date' => 'datetime',
        'sale_end_date' => 'datetime',
        'weight' => 'decimal:2',
        'dimensions' => 'array',
        'stock_quantity' => 'integer',
        'manage_stock' => 'boolean',
        'allow_backorders' => 'boolean',
        'low_stock_amount' => 'integer',
        'sold_individually' => 'boolean',
        'featured' => 'boolean',
        'gallery_images' => 'array',
        'tags' => 'array',
        'attributes' => 'array',
        'default_attributes' => 'array',
        'variations' => 'array',
        'downloadable' => 'boolean',
        'virtual' => 'boolean',
        'download_limit' => 'integer',
        'download_expiry' => 'integer',
        'downloadable_files' => 'array',
        'reviews_allowed' => 'boolean',
        'rating_count' => 'integer',
        'average_rating' => 'decimal:2',
        'total_sales' => 'integer',
        'view_count' => 'integer',
        'schema_data' => 'array',
        'compatibility' => 'array',
        'requirements' => 'array',
        'last_updated' => 'datetime',
        'license_key_required' => 'boolean',
        'auto_update_enabled' => 'boolean',
        'faqs' => 'array',
        'custom_fields' => 'array',
    ];

    // Relationships
    public function category()
    {
        return $this->belongsTo(AuraCategory::class);
    }

    public function vendor()
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }

    public function reviews()
    {
        return $this->hasMany(AuraReview::class);
    }

    public function orders()
    {
        return $this->belongsToMany(AuraOrder::class, 'aura_order_items')
                    ->withPivot(['quantity', 'price', 'total', 'meta_data'])
                    ->withTimestamps();
    }

    public function licenses()
    {
        return $this->hasMany(AuraLicense::class);
    }

    public function downloads()
    {
        return $this->hasMany(AuraDownload::class);
    }

    public function updates()
    {
        return $this->hasMany(AuraProductUpdate::class);
    }

    public function support_tickets()
    {
        return $this->hasMany(AuraSupportTicket::class);
    }

    // Scopes
    public function scopePublished($query)
    {
        return $query->where('status', 'publish');
    }

    public function scopeVisible($query)
    {
        return $query->where('catalog_visibility', 'visible');
    }

    public function scopeFeatured($query)
    {
        return $query->where('featured', true);
    }

    public function scopeOnSale($query)
    {
        return $query->where('is_on_sale', true)
                    ->where(function ($q) {
                        $q->whereNull('sale_start_date')
                          ->orWhere('sale_start_date', '<=', now());
                    })
                    ->where(function ($q) {
                        $q->whereNull('sale_end_date')
                          ->orWhere('sale_end_date', '>=', now());
                    });
    }

    public function scopeInStock($query)
    {
        return $query->where('stock_status', 'in_stock');
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    public function scopeByVendor($query, $vendorId)
    {
        return $query->where('vendor_id', $vendorId);
    }

    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'LIKE', "%{$search}%")
              ->orWhere('description', 'LIKE', "%{$search}%")
              ->orWhere('short_description', 'LIKE', "%{$search}%")
              ->orWhere('sku', 'LIKE', "%{$search}%")
              ->orWhereJsonContains('tags', $search);
        });
    }

    // Methods
    public function getDisplayPrice()
    {
        if ($this->isOnSale()) {
            return $this->sale_price;
        }
        
        return $this->price;
    }

    public function isOnSale()
    {
        if (!$this->is_on_sale) {
            return false;
        }

        $now = now();
        
        if ($this->sale_start_date && $this->sale_start_date > $now) {
            return false;
        }
        
        if ($this->sale_end_date && $this->sale_end_date < $now) {
            return false;
        }
        
        return true;
    }

    public function getSalePercentage()
    {
        if (!$this->isOnSale()) {
            return 0;
        }
        
        return round((($this->price - $this->sale_price) / $this->price) * 100);
    }

    public function isInStock()
    {
        return $this->stock_status === 'in_stock';
    }

    public function canBePurchased($quantity = 1)
    {
        if (!$this->isInStock()) {
            return false;
        }

        if ($this->sold_individually && $quantity > 1) {
            return false;
        }

        if ($this->manage_stock && $this->stock_quantity < $quantity) {
            return false;
        }

        return true;
    }

    public function decrementStock($quantity = 1)
    {
        if ($this->manage_stock) {
            $this->decrement('stock_quantity', $quantity);
            
            if ($this->stock_quantity <= $this->low_stock_amount) {
                // Trigger low stock notification
                $this->notifyLowStock();
            }
            
            if ($this->stock_quantity <= 0) {
                $this->update(['stock_status' => 'out_of_stock']);
            }
        }

        $this->increment('total_sales', $quantity);
    }

    public function incrementStock($quantity = 1)
    {
        if ($this->manage_stock) {
            $this->increment('stock_quantity', $quantity);
            
            if ($this->stock_quantity > 0 && $this->stock_status === 'out_of_stock') {
                $this->update(['stock_status' => 'in_stock']);
            }
        }
    }

    public function incrementViewCount()
    {
        $this->increment('view_count');
    }

    public function getAverageRating()
    {
        return $this->reviews()->approved()->avg('rating');
    }

    public function updateRating()
    {
        $this->average_rating = $this->getAverageRating();
        $this->rating_count = $this->reviews()->approved()->count();
        $this->save();
    }

    public function generateLicense($userId, $type = 'standard')
    {
        return $this->licenses()->create([
            'user_id' => $userId,
            'license_key' => $this->generateLicenseKey(),
            'type' => $type,
            'domains' => $type === 'extended' ? 5 : 1,
            'expires_at' => $type === 'lifetime' ? null : now()->addYear(),
            'status' => 'active',
        ]);
    }

    private function generateLicenseKey()
    {
        return 'AURA-' . strtoupper(Str::random(4)) . '-' . strtoupper(Str::random(4)) . '-' . strtoupper(Str::random(4)) . '-' . time();
    }

    public function getDownloadableFiles()
    {
        if (!$this->downloadable || empty($this->downloadable_files)) {
            return [];
        }

        return collect($this->downloadable_files)->map(function ($file) {
            return [
                'id' => $file['id'],
                'name' => $file['name'],
                'url' => route('aura.products.download', [$this->slug, $file['id']]),
                'size' => $file['size'] ?? null,
                'extension' => pathinfo($file['name'], PATHINFO_EXTENSION),
            ];
        });
    }

    public function canBeDownloadedBy($user)
    {
        // Check if user has purchased the product
        $hasPurchased = $this->orders()
            ->whereHas('user', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->where('status', 'completed')
            ->exists();

        if ($hasPurchased) {
            return true;
        }

        // Check if user has active license
        return $this->licenses()
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            })
            ->exists();
    }

    public function getRelatedProducts($limit = 8)
    {
        return static::published()
            ->visible()
            ->where('id', '!=', $this->id)
            ->where('category_id', $this->category_id)
            ->inRandomOrder()
            ->take($limit)
            ->get();
    }

    public function getUpsellProducts($limit = 4)
    {
        return static::published()
            ->visible()
            ->where('id', '!=', $this->id)
            ->where('price', '>', $this->price)
            ->where('category_id', $this->category_id)
            ->orderBy('price', 'asc')
            ->take($limit)
            ->get();
    }

    public function getCrossSellProducts($limit = 4)
    {
        return static::published()
            ->visible()
            ->where('id', '!=', $this->id)
            ->where('category_id', '!=', $this->category_id)
            ->inRandomOrder()
            ->take($limit)
            ->get();
    }

    public function getSchemaData()
    {
        return [
            '@context' => 'https://schema.org/',
            '@type' => 'Product',
            'name' => $this->name,
            'description' => $this->description,
            'sku' => $this->sku,
            'image' => $this->product_image,
            'brand' => [
                '@type' => 'Brand',
                'name' => 'AuraPageBuilder',
            ],
            'offers' => [
                '@type' => 'Offer',
                'url' => route('aura.products.show', $this->slug),
                'priceCurrency' => 'USD',
                'price' => $this->getDisplayPrice(),
                'availability' => $this->isInStock() ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
                'seller' => [
                    '@type' => 'Organization',
                    'name' => config('app.name'),
                ],
            ],
            'aggregateRating' => $this->average_rating ? [
                '@type' => 'AggregateRating',
                'ratingValue' => $this->average_rating,
                'reviewCount' => $this->rating_count,
            ] : null,
        ];
    }

    public function notifyLowStock()
    {
        // Send notification to vendor/admin about low stock
        // Implementation depends on notification system
    }

    public function addCustomField($key, $value)
    {
        $fields = $this->custom_fields ?? [];
        $fields[$key] = $value;
        
        $this->custom_fields = $fields;
        $this->save();
        
        return true;
    }

    public function getCustomField($key, $default = null)
    {
        $fields = $this->custom_fields ?? [];
        
        return $fields[$key] ?? $default;
    }

    public function duplicate()
    {
        $newProduct = $this->replicate();
        $newProduct->name = $this->name . ' (Copy)';
        $newProduct->slug = $this->slug . '-copy-' . time();
        $newProduct->sku = $this->sku . '-COPY';
        $newProduct->status = 'draft';
        $newProduct->total_sales = 0;
        $newProduct->view_count = 0;
        $newProduct->rating_count = 0;
        $newProduct->average_rating = 0;
        $newProduct->save();

        return $newProduct;
    }
}
