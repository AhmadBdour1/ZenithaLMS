<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;

class Marketplace extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'type', // 'course', 'ebook', 'template', 'plugin'
        'price',
        'commission_rate',
        'vendor_id',
        'category_id',
        'is_featured',
        'is_approved',
        'sales_count',
        'rating',
        'reviews_count',
        'download_count',
        'preview_url',
        'demo_url',
        'support_url',
        'documentation_url',
        'requirements',
        'compatibility',
        'last_updated',
        'version',
        'file_path',
        'file_size',
        'thumbnail',
        'gallery',
        'tags',
        'meta_title',
        'meta_description',
        'slug',
        'status', // 'draft', 'pending', 'approved', 'rejected', 'suspended'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'commission_rate' => 'decimal:2',
        'is_featured' => 'boolean',
        'is_approved' => 'boolean',
        'gallery' => 'array',
        'tags' => 'array',
        'requirements' => 'array',
        'compatibility' => 'array',
    ];

    // Relationships
    public function vendor()
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function reviews()
    {
        return $this->hasMany(MarketplaceReview::class);
    }

    public function sales()
    {
        return $this->hasMany(MarketplaceSale::class);
    }

    public function licenses()
    {
        return $this->hasMany(MarketplaceLicense::class);
    }

    // Scopes
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    // Methods
    public function calculateCommission($amount)
    {
        return $amount * ($this->commission_rate / 100);
    }

    public function getVendorEarnings($amount)
    {
        return $amount - $this->calculateCommission($amount);
    }

    public function getAverageRating()
    {
        return $this->reviews()->avg('rating');
    }

    public function updateRating()
    {
        $this->rating = $this->getAverageRating();
        $this->reviews_count = $this->reviews()->count();
        $this->save();
    }

    public function incrementSales()
    {
        $this->increment('sales_count');
        $this->last_updated = now();
        $this->save();
    }

    public function canBePurchasedBy(User $user)
    {
        // Check if user has already purchased
        if ($this->sales()->where('user_id', $user->id)->exists()) {
            return false;
        }

        // Check if user is the vendor
        if ($this->vendor_id === $user->id) {
            return false;
        }

        return true;
    }

    public function generateLicense(User $user, $type = 'standard')
    {
        return $this->licenses()->create([
            'user_id' => $user->id,
            'license_key' => $this->generateLicenseKey(),
            'type' => $type,
            'expires_at' => $type === 'lifetime' ? null : now()->addYear(),
            'domains' => $type === 'extended' ? 5 : 1,
            'support_until' => now()->addMonths(6),
        ]);
    }

    private function generateLicenseKey()
    {
        return strtoupper(substr(md5(uniqid(rand(), true)), 0, 25)) . '-' . time();
    }
}
