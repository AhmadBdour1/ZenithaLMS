<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Stuff extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'short_description',
        'type', // 'digital', 'physical', 'service', 'template', 'tool', 'resource'
        'category_id',
        'subcategory_id',
        'vendor_id',
        'status', // 'draft', 'pending', 'active', 'inactive', 'archived'
        'featured',
        'premium',
        'price',
        'sale_price',
        'currency',
        'sku',
        'stock_quantity',
        'stock_status', // 'in_stock', 'out_of_stock', 'on_backorder', 'limited'
        'weight',
        'dimensions', // array: length, width, height
        'digital_file',
        'download_limit',
        'download_expiry_days',
        'license_type', // 'single', 'multi', 'resale', 'private_label', 'commercial'
        'license_terms',
        'usage_rights',
        'tags',
        'metadata',
        'seo_title',
        'seo_description',
        'seo_keywords',
        'thumbnail',
        'gallery',
        'preview_images',
        'demo_url',
        'documentation_url',
        'support_url',
        'video_url',
        'requirements',
        'compatibility',
        'version',
        'last_updated_at',
        'published_at',
        'expires_at',
        'view_count',
        'download_count',
        'purchase_count',
        'rating',
        'rating_count',
        'review_count',
        'featured_until',
        'is_digital',
        'is_physical',
        'requires_shipping',
        'taxable',
        'tax_class',
        'commission_rate',
        'affiliate_enabled',
        'bulk_discount_enabled',
        'bulk_discount_tiers',
        'subscription_required',
        'required_subscription_tier',
        'access_level', // 'free', 'basic', 'premium', 'enterprise'
        'age_restriction',
        'content_warning',
        'language',
        'regions',
        'custom_fields',
        'sort_order',
        'is_popular',
        'is_new',
        'is_trending',
        'is_best_seller',
        'auto_renewal_enabled',
        'renewal_price',
        'renewal_period', // 'monthly', 'yearly', 'lifetime'
        'trial_available',
        'trial_days',
        'trial_price',
        'setup_fee',
        'setup_fee_description',
        'cancellation_policy',
        'refund_policy',
        'guarantee_period',
        'guarantee_description',
        'support_level', // 'basic', 'standard', 'premium', 'enterprise'
        'support_response_time',
        'update_frequency',
        'update_policy',
        'integration_compatibility',
        'api_documentation',
        'developer_resources',
        'community_forum',
        'tutorials',
        'faq',
        'changelog',
        'roadmap',
        'status_notes',
        'admin_notes',
        'internal_notes',
    ];

    protected $casts = [
        'featured' => 'boolean',
        'premium' => 'boolean',
        'is_digital' => 'boolean',
        'is_physical' => 'boolean',
        'requires_shipping' => 'boolean',
        'taxable' => 'boolean',
        'affiliate_enabled' => 'boolean',
        'bulk_discount_enabled' => 'boolean',
        'subscription_required' => 'boolean',
        'auto_renewal_enabled' => 'boolean',
        'trial_available' => 'boolean',
        'is_popular' => 'boolean',
        'is_new' => 'boolean',
        'is_trending' => 'boolean',
        'is_best_seller' => 'boolean',
        'dimensions' => 'array',
        'gallery' => 'array',
        'preview_images' => 'array',
        'tags' => 'array',
        'metadata' => 'array',
        'bulk_discount_tiers' => 'array',
        'regions' => 'array',
        'custom_fields' => 'array',
        'requirements' => 'array',
        'compatibility' => 'array',
        'integration_compatibility' => 'array',
        'price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'weight' => 'decimal:2',
        'rating' => 'decimal:2',
        'commission_rate' => 'decimal:2',
        'renewal_price' => 'decimal:2',
        'setup_fee' => 'decimal:2',
        'trial_price' => 'decimal:2',
        'published_at' => 'datetime',
        'expires_at' => 'datetime',
        'featured_until' => 'datetime',
        'last_updated_at' => 'datetime',
        'guarantee_period' => 'integer',
        'trial_days' => 'integer',
        'support_response_time' => 'integer',
    ];

    // Relationships
    public function vendor()
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }

    public function category()
    {
        return $this->belongsTo(StuffCategory::class, 'category_id');
    }

    public function subcategory()
    {
        return $this->belongsTo(StuffCategory::class, 'subcategory_id');
    }

    public function reviews()
    {
        return $this->hasMany(StuffReview::class);
    }

    public function purchases()
    {
        return $this->hasMany(StuffPurchase::class);
    }

    public function downloads()
    {
        return $this->hasMany(StuffDownload::class);
    }

    public function licenses()
    {
        return $this->hasMany(StuffLicense::class);
    }

    public function analytics()
    {
        return $this->hasMany(StuffAnalytics::class);
    }

    public function support_tickets()
    {
        return $this->hasMany(StuffSupportTicket::class);
    }

    public function updates()
    {
        return $this->hasMany(StuffUpdate::class);
    }

    public function faqs()
    {
        return $this->hasMany(StuffFaq::class);
    }

    public function tutorials()
    {
        return $this->hasMany(StuffTutorial::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeFeatured($query)
    {
        return $query->where('featured', true)
                    ->where(function ($q) {
                        $q->whereNull('featured_until')
                          ->orWhere('featured_until', '>', now());
                    });
    }

    public function scopePremium($query)
    {
        return $query->where('premium', true);
    }

    public function scopeFree($query)
    {
        return $query->where(function ($q) {
            $q->where('price', 0)->orWhereNull('price');
        });
    }

    public function scopePaid($query)
    {
        return $query->where('price', '>', 0);
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

    public function scopeInStock($query)
    {
        return $query->where('stock_status', 'in_stock')
                    ->where('stock_quantity', '>', 0);
    }

    public function scopeOnSale($query)
    {
        return $query->whereNotNull('sale_price')
                    ->where('sale_price', '<', 'price');
    }

    public function scopeNew($query)
    {
        return $query->where('is_new', true)
                    ->where('created_at', '>=', now()->subDays(30));
    }

    public function scopeTrending($query)
    {
        return $query->where('is_trending', true);
    }

    public function scopeBestSeller($query)
    {
        return $query->where('is_best_seller', true);
    }

    public function scopePopular($query)
    {
        return $query->where('is_popular', true);
    }

    public function scopeSearch($query, $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('name', 'like', '%' . $term . '%')
              ->orWhere('description', 'like', '%' . $term . '%')
              ->orWhere('short_description', 'like', '%' . $term . '%')
              ->orWhere('tags', 'like', '%' . $term . '%')
              ->orWhere('sku', 'like', '%' . $term . '%');
        });
    }

    // Methods
    public function generateSlug()
    {
        $slug = Str::slug($this->name);
        $count = static::where('slug', 'like', $slug . '%')->count();
        
        return $count > 0 ? $slug . '-' . ($count + 1) : $slug;
    }

    public function generateSku()
    {
        if ($this->sku) {
            return $this->sku;
        }

        $prefix = strtoupper(substr($this->type, 0, 3));
        $vendorCode = $this->vendor_id ? str_pad($this->vendor_id, 4, '0', STR_PAD_LEFT) : '0000';
        $random = strtoupper(Str::random(4));
        
        return $prefix . '-' . $vendorCode . '-' . $random;
    }

    public function getCurrentPrice()
    {
        return $this->sale_price && $this->sale_price < $this->price ? $this->sale_price : $this->price;
    }

    public function getFormattedPrice()
    {
        return number_format($this->getCurrentPrice(), 2) . ' ' . $this->currency;
    }

    public function getFormattedOriginalPrice()
    {
        return number_format($this->price, 2) . ' ' . $this->currency;
    }

    public function getDiscountPercentage()
    {
        if (!$this->sale_price || $this->sale_price >= $this->price) {
            return 0;
        }

        return round((($this->price - $this->sale_price) / $this->price) * 100, 2);
    }

    public function isInStock()
    {
        return $this->stock_status === 'in_stock' && $this->stock_quantity > 0;
    }

    public function canPurchase($user = null)
    {
        // Check if item is active
        if ($this->status !== 'active') {
            return false;
        }

        // Check if item is in stock (for physical items)
        if ($this->is_physical && !$this->isInStock()) {
            return false;
        }

        // Check if user has required subscription
        if ($this->subscription_required && $user) {
            return $user->hasSubscriptionTier($this->required_subscription_tier);
        }

        // Check age restriction
        if ($this->age_restriction && $user) {
            return $user->getAge() >= $this->age_restriction;
        }

        // Check regional restrictions
        if ($this->regions && $user) {
            return in_array($user->country, $this->regions);
        }

        return true;
    }

    public function canDownload($purchase)
    {
        if (!$purchase || $purchase->stuff_id !== $this->id) {
            return false;
        }

        if ($this->download_limit && $purchase->download_count >= $this->download_limit) {
            return false;
        }

        if ($this->download_expiry_days) {
            $expiryDate = $purchase->created_at->addDays($this->download_expiry_days);
            if (now()->gt($expiryDate)) {
                return false;
            }
        }

        return true;
    }

    public function incrementView()
    {
        $this->increment('view_count');
        
        // Record analytics
        $this->analytics()->create([
            'type' => 'view',
            'user_id' => auth()->id(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    public function recordDownload($purchase)
    {
        $this->increment('download_count');
        $purchase->increment('download_count');

        // Record analytics
        $this->analytics()->create([
            'type' => 'download',
            'user_id' => $purchase->user_id,
            'purchase_id' => $purchase->id,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    public function recordPurchase($purchase)
    {
        $this->increment('purchase_count');

        // Update best seller status
        if ($this->purchase_count >= 100) {
            $this->update(['is_best_seller' => true]);
        }

        // Record analytics
        $this->analytics()->create([
            'type' => 'purchase',
            'user_id' => $purchase->user_id,
            'purchase_id' => $purchase->id,
            'amount' => $purchase->price,
            'currency' => $purchase->currency,
        ]);
    }

    public function updateRating()
    {
        $reviews = $this->reviews()->approved();
        $this->update([
            'rating' => $reviews->avg('rating'),
            'rating_count' => $reviews->count(),
            'review_count' => $reviews->count(),
        ]);
    }

    public function getAverageRating()
    {
        return $this->rating ?: 0;
    }

    public function getStarRating()
    {
        $rating = $this->getAverageRating();
        $fullStars = floor($rating);
        $halfStar = $rating - $fullStars >= 0.5 ? 1 : 0;
        $emptyStars = 5 - $fullStars - $halfStar;

        return [
            'full' => $fullStars,
            'half' => $halfStar,
            'empty' => $emptyStars,
            'rating' => $rating,
            'count' => $this->rating_count,
        ];
    }

    public function getGalleryImages()
    {
        return $this->gallery ?: [];
    }

    public function getPreviewImages()
    {
        return $this->preview_images ?: [];
    }

    public function getTagsArray()
    {
        return $this->tags ? explode(',', $this->tags) : [];
    }

    public function getRequirementsArray()
    {
        return $this->requirements ?: [];
    }

    public function getCompatibilityArray()
    {
        return $this->compatibility ?: [];
    }

    public function getBulkDiscountTiers()
    {
        return $this->bulk_discount_tiers ?: [];
    }

    public function calculateBulkDiscount($quantity)
    {
        if (!$this->bulk_discount_enabled || !$this->bulk_discount_tiers) {
            return 0;
        }

        $tiers = collect($this->bulk_discount_tiers)
            ->sortBy('quantity')
            ->reverse()
            ->firstWhere('quantity', '<=', $quantity);

        return $tiers ? $tiers['discount'] : 0;
    }

    public function calculatePrice($quantity = 1, $user = null)
    {
        $basePrice = $this->getCurrentPrice();
        
        // Apply bulk discount
        $discount = $this->calculateBulkDiscount($quantity);
        $discountedPrice = $basePrice * (1 - ($discount / 100));
        
        // Apply user-specific discounts
        if ($user && $user->hasRole('premium')) {
            $discountedPrice *= 0.9; // 10% discount for premium users
        }

        return $discountedPrice * $quantity;
    }

    public function getLicenseTerms()
    {
        return $this->license_terms ?: $this->getDefaultLicenseTerms();
    }

    public function getDefaultLicenseTerms()
    {
        switch ($this->license_type) {
            case 'single':
                return 'Single use license for one project or end product.';
            case 'multi':
                return 'Multi-use license for multiple projects or end products.';
            case 'resale':
                return 'Resale license allowing you to sell the item as part of a larger work.';
            case 'private_label':
                return 'Private label license allowing you to rebrand and resell under your own name.';
            case 'commercial':
                return 'Commercial license for use in commercial projects.';
            default:
                return 'Standard license terms apply.';
        }
    }

    public function getSupportLevel()
    {
        return $this->support_level ?: 'basic';
    }

    public function getSupportResponseTimeText()
    {
        $hours = $this->support_response_time ?: 48;
        
        if ($hours <= 24) {
            return 'Within 24 hours';
        } elseif ($hours <= 48) {
            return 'Within 48 hours';
        } elseif ($hours <= 72) {
            return 'Within 72 hours';
        } else {
            return 'Within ' . $hours . ' hours';
        }
    }

    public function getTrialPrice()
    {
        return $this->trial_price ?: ($this->price * 0.1); // Default 10% of full price
    }

    public function getFormattedTrialPrice()
    {
        return number_format($this->getTrialPrice(), 2) . ' ' . $this->currency;
    }

    public function hasTrial()
    {
        return $this->trial_available && $this->trial_days > 0;
    }

    public function getTrialEndDate()
    {
        if (!$this->hasTrial()) {
            return null;
        }

        return now()->addDays($this->trial_days);
    }

    public function isFeatured()
    {
        return $this->featured && 
               (!$this->featured_until || $this->featured_until->gt(now()));
    }

    public function isOnSale()
    {
        return $this->sale_price && $this->sale_price < $this->price;
    }

    public function isNew()
    {
        return $this->is_new || $this->created_at->gt(now()->subDays(30));
    }

    public function isTrending()
    {
        return $this->is_trending;
    }

    public function isBestSeller()
    {
        return $this->is_best_seller;
    }

    public function isPopular()
    {
        return $this->is_popular;
    }

    public function getDownloadUrl()
    {
        if (!$this->digital_file) {
            return null;
        }

        return route('stuff.download', $this->id);
    }

    public function getPreviewUrl()
    {
        return $this->demo_url ?: route('stuff.preview', $this->id);
    }

    public function getDocumentationUrl()
    {
        return $this->documentation_url ?: route('stuff.documentation', $this->id);
    }

    public function getSupportUrl()
    {
        return $this->support_url ?: route('stuff.support', $this->id);
    }

    public function getVideoUrl()
    {
        return $this->video_url;
    }

    public function getApiDocumentation()
    {
        return $this->api_documentation ?: route('stuff.api-docs', $this->id);
    }

    public function getDeveloperResources()
    {
        return $this->developer_resources ?: [];
    }

    public function getCommunityForum()
    {
        return $this->community_forum ?: route('stuff.community', $this->id);
    }

    public function getTutorials()
    {
        return $this->tutorials()->published()->orderBy('sort_order')->get();
    }

    public function getFaqs()
    {
        return $this->faqs()->published()->orderBy('sort_order')->get();
    }

    public function getChangelog()
    {
        return $this->changelog()->orderBy('created_at', 'desc')->get();
    }

    public function getRoadmap()
    {
        return $this->roadmap ?: [];
    }

    public function getRequirements()
    {
        return $this->requirements ?: [];
    }

    public function getCompatibility()
    {
        return $this->compatibility ?: [];
    }

    public function getIntegrationCompatibility()
    {
        return $this->integration_compatibility ?: [];
    }

    public function getCustomFields()
    {
        return $this->custom_fields ?: [];
    }

    public function getRegions()
    {
        return $this->regions ?: [];
    }

    public function isAvailableInRegion($region)
    {
        return !$this->regions || in_array($region, $this->regions);
    }

    public function getAgeRestrictionText()
    {
        if (!$this->age_restriction) {
            return null;
        }

        return 'Age ' . $this->age_restriction . '+';
    }

    public function getContentWarningText()
    {
        return $this->content_warning;
    }

    public function getLanguage()
    {
        return $this->language ?: 'en';
    }

    public function getCurrency()
    {
        return $this->currency ?: 'USD';
    }

    public function getWeight()
    {
        return $this->weight ?: 0;
    }

    public function getDimensions()
    {
        return $this->dimensions ?: [
            'length' => 0,
            'width' => 0,
            'height' => 0,
        ];
    }

    public function getTaxClass()
    {
        return $this->tax_class ?: 'standard';
    }

    public function getCommissionRate()
    {
        return $this->commission_rate ?: 10;
    }

    public function getAccessLevel()
    {
        return $this->access_level ?: 'free';
    }

    public function requiresSubscription()
    {
        return $this->subscription_required;
    }

    public function getRequiredSubscriptionTier()
    {
        return $this->required_subscription_tier;
    }

    public function hasAutoRenewal()
    {
        return $this->auto_renewal_enabled;
    }

    public function getRenewalPrice()
    {
        return $this->renewal_price ?: $this->price;
    }

    public function getFormattedRenewalPrice()
    {
        return number_format($this->getRenewalPrice(), 2) . ' ' . $this->currency;
    }

    public function getRenewalPeriod()
    {
        return $this->renewal_period ?: 'monthly';
    }

    public function hasSetupFee()
    {
        return $this->setup_fee && $this->setup_fee > 0;
    }

    public function getFormattedSetupFee()
    {
        return number_format($this->setup_fee, 2) . ' ' . $this->currency;
    }

    public function getGuaranteePeriod()
    {
        return $this->guarantee_period ?: 30;
    }

    public function hasGuarantee()
    {
        return $this->guarantee_period > 0;
    }

    public function getGuaranteeDescription()
    {
        return $this->guarantee_description ?: $this->getDefaultGuaranteeDescription();
    }

    public function getDefaultGuaranteeDescription()
    {
        return $this->guarantee_period . '-day money-back guarantee';
    }

    public function getCancellationPolicy()
    {
        return $this->cancellation_policy ?: $this->getDefaultCancellationPolicy();
    }

    public function getDefaultCancellationPolicy()
    {
        return 'Cancel anytime. No questions asked.';
    }

    public function getRefundPolicy()
    {
        return $this->refund_policy ?: $this->getDefaultRefundPolicy();
    }

    public function getDefaultRefundPolicy()
    {
        return 'Full refund within ' . $this->guarantee_period . ' days.';
    }

    public function getUpdateFrequency()
    {
        return $this->update_frequency ?: 'monthly';
    }

    public function getUpdatePolicy()
    {
        return $this->update_policy ?: $this->getDefaultUpdatePolicy();
    }

    public function getDefaultUpdatePolicy()
    {
        return 'Free updates for ' . $this->update_frequency . ' releases.';
    }

    public function getLastUpdatedDate()
    {
        return $this->last_updated_at ?: $this->updated_at;
    }

    public function getFormattedLastUpdatedDate()
    {
        return $this->getLastUpdatedDate()->format('Y-m-d');
    }

    public function getPublishedDate()
    {
        return $this->published_at ?: $this->created_at;
    }

    public function getFormattedPublishedDate()
    {
        return $this->getPublishedDate()->format('Y-m-d');
    }

    public function getExpirationDate()
    {
        return $this->expires_at;
    }

    public function isExpired()
    {
        return $this->expires_at && $this->expires_at->lt(now());
    }

    public function getExpirationDaysLeft()
    {
        if (!$this->expires_at) {
            return null;
        }

        return now()->diffInDays($this->expires_at, false);
    }

    public function isAvailable()
    {
        return $this->status === 'active' && 
               !$this->isExpired() && 
               $this->isInStock();
    }

    public function getAvailabilityStatus()
    {
        if ($this->status !== 'active') {
            return 'inactive';
        }

        if ($this->isExpired()) {
            return 'expired';
        }

        if (!$this->isInStock()) {
            return 'out_of_stock';
        }

        return 'available';
    }

    public function getAvailabilityStatusText()
    {
        switch ($this->getAvailabilityStatus()) {
            case 'available':
                return 'Available';
            case 'inactive':
                return 'Inactive';
            case 'expired':
                return 'Expired';
            case 'out_of_stock':
                return 'Out of Stock';
            default:
                return 'Unknown';
        }
    }

    public function getSortOrder()
    {
        return $this->sort_order ?: 0;
    }

    public function moveToTop()
    {
        $maxOrder = static::max('sort_order') ?: 0;
        $this->update(['sort_order' => $maxOrder + 1]);
    }

    public function moveToBottom()
    {
        $this->update(['sort_order' => 0]);
    }

    public function moveUp()
    {
        $higherItem = static::where('sort_order', '>', $this->sort_order)
                          ->orderBy('sort_order', 'asc')
                          ->first();

        if ($higherItem) {
            $newOrder = $higherItem->sort_order;
            $higherItem->update(['sort_order' => $this->sort_order]);
            $this->update(['sort_order' => $newOrder]);
        }
    }

    public function moveDown()
    {
        $lowerItem = static::where('sort_order', '<', $this->sort_order)
                         ->orderBy('sort_order', 'desc')
                         ->first();

        if ($lowerItem) {
            $newOrder = $lowerItem->sort_order;
            $lowerItem->update(['sort_order' => $this->sort_order]);
            $this->update(['sort_order' => $newOrder]);
        }
    }

    public function duplicate()
    {
        $duplicate = $this->replicate();
        $duplicate->name = $this->name . ' (Copy)';
        $duplicate->slug = $this->generateSlug();
        $duplicate->sku = $this->generateSku();
        $duplicate->status = 'draft';
        $duplicate->view_count = 0;
        $duplicate->download_count = 0;
        $duplicate->purchase_count = 0;
        $duplicate->rating = 0;
        $duplicate->rating_count = 0;
        $duplicate->review_count = 0;
        $duplicate->featured = false;
        $duplicate->is_popular = false;
        $duplicate->is_new = false;
        $duplicate->is_trending = false;
        $duplicate->is_best_seller = false;
        $duplicate->published_at = null;
        $duplicate->save();

        return $duplicate;
    }

    public function archive()
    {
        $this->update([
            'status' => 'archived',
            'featured' => false,
            'is_popular' => false,
            'is_new' => false,
            'is_trending' => false,
            'is_best_seller' => false,
        ]);
    }

    public function restore()
    {
        $this->update([
            'status' => 'active',
        ]);
    }

    public function delete()
    {
        // Check if there are any active purchases
        if ($this->purchases()->active()->exists()) {
            throw new \Exception('Cannot delete stuff with active purchases');
        }

        return parent::delete();
    }

    public function forceDelete()
    {
        // Delete related records
        $this->reviews()->delete();
        $this->purchases()->delete();
        $this->downloads()->delete();
        $this->licenses()->delete();
        $this->analytics()->delete();
        $this->support_tickets()->delete();
        $this->updates()->delete();
        $this->faqs()->delete();
        $this->tutorials()->delete();

        // Delete digital file if exists
        if ($this->digital_file) {
            Storage::delete($this->digital_file);
        }

        return parent::forceDelete();
    }

    // Static methods
    public static function getTypes()
    {
        return [
            'digital' => 'Digital Product',
            'physical' => 'Physical Product',
            'service' => 'Service',
            'template' => 'Template',
            'tool' => 'Tool',
            'resource' => 'Resource',
        ];
    }

    public static function getStockStatuses()
    {
        return [
            'in_stock' => 'In Stock',
            'out_of_stock' => 'Out of Stock',
            'on_backorder' => 'On Backorder',
            'limited' => 'Limited Stock',
        ];
    }

    public static function getLicenseTypes()
    {
        return [
            'single' => 'Single Use',
            'multi' => 'Multi Use',
            'resale' => 'Resale Rights',
            'private_label' => 'Private Label',
            'commercial' => 'Commercial',
        ];
    }

    public static function getAccessLevels()
    {
        return [
            'free' => 'Free',
            'basic' => 'Basic',
            'premium' => 'Premium',
            'enterprise' => 'Enterprise',
        ];
    }

    public static function getSupportLevels()
    {
        return [
            'basic' => 'Basic Support',
            'standard' => 'Standard Support',
            'premium' => 'Premium Support',
            'enterprise' => 'Enterprise Support',
        ];
    }

    public static function getUpdateFrequencies()
    {
        return [
            'daily' => 'Daily',
            'weekly' => 'Weekly',
            'monthly' => 'Monthly',
            'quarterly' => 'Quarterly',
            'yearly' => 'Yearly',
        ];
    }

    public static function getRenewalPeriods()
    {
        return [
            'daily' => 'Daily',
            'weekly' => 'Weekly',
            'monthly' => 'Monthly',
            'quarterly' => 'Quarterly',
            'yearly' => 'Yearly',
            'lifetime' => 'Lifetime',
        ];
    }
}
