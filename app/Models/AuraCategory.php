<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AuraCategory extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'parent_id',
        'image',
        'icon',
        'color',
        'sort_order',
        'is_active',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'product_count',
    ];

    protected $casts = [
        'parent_id' => 'integer',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
        'product_count' => 'integer',
    ];

    // Relationships
    public function parent()
    {
        return $this->belongsTo(AuraCategory::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(AuraCategory::class, 'parent_id')
                    ->orderBy('sort_order');
    }

    public function products()
    {
        return $this->hasMany(AuraProduct::class);
    }

    public function allProducts()
    {
        return $this->products()
            ->orWhereHas('category', function ($query) {
                $query->where('parent_id', $this->id);
            });
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeRoot($query)
    {
        return $query->whereNull('parent_id');
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    // Methods
    public function getFullPath()
    {
        $path = collect([$this]);
        $parent = $this->parent;
        
        while ($parent) {
            $path->prepend($parent);
            $parent = $parent->parent;
        }
        
        return $path;
    }

    public function getBreadcrumb()
    {
        return $this->getFullPath()->pluck('name')->implode(' > ');
    }

    public function updateProductCount()
    {
        $this->product_count = $this->allProducts()->count();
        $this->save();
    }

    public function hasChildren()
    {
        return $this->children()->count() > 0;
    }

    public function getActiveChildren()
    {
        return $this->children()->active()->get();
    }

    public function getFeaturedProducts($limit = 8)
    {
        return $this->allProducts()
            ->published()
            ->featured()
            ->take($limit)
            ->get();
    }

    public function getOnSaleProducts($limit = 8)
    {
        return $this->allProducts()
            ->published()
            ->onSale()
            ->take($limit)
            ->get();
    }

    public function getLatestProducts($limit = 8)
    {
        return $this->allProducts()
            ->published()
            ->latest()
            ->take($limit)
            ->get();
    }

    public static function getTree()
    {
        return static::active()
            ->root()
            ->with(['children' => function ($query) {
                $query->active()->ordered();
            }])
            ->ordered()
            ->get();
    }

    public static function getFlatList()
    {
        $categories = collect();
        
        static::active()
            ->with(['children' => function ($query) {
                $query->active()->ordered();
            }])
            ->root()
            ->ordered()
            ->each(function ($category) use ($categories) {
                $categories->push($category);
                
                $category->children->each(function ($child) use ($categories) {
                    $categories->push($child);
                });
            });
        
        return $categories;
    }
}
