<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StuffCategory extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'parent_id',
        'icon',
        'image',
        'banner',
        'color',
        'sort_order',
        'is_active',
        'is_featured',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'seo_content',
        'custom_fields',
        'settings',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'sort_order' => 'integer',
        'custom_fields' => 'array',
        'settings' => 'array',
    ];

    // Relationships
    public function parent()
    {
        return $this->belongsTo(StuffCategory::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(StuffCategory::class, 'parent_id');
    }

    public function stuff()
    {
        return $this->hasMany(Stuff::class, 'category_id');
    }

    public function subcategoryStuff()
    {
        return $this->hasMany(Stuff::class, 'subcategory_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeRoot($query)
    {
        return $query->whereNull('parent_id');
    }

    public function scopeByParent($query, $parentId)
    {
        return $query->where('parent_id', $parentId);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    // Methods
    public function generateSlug()
    {
        $slug = \Illuminate\Support\Str::slug($this->name);
        $count = static::where('slug', 'like', $slug . '%')->count();
        
        return $count > 0 ? $slug . '-' . ($count + 1) : $slug;
    }

    public function getFullSlug()
    {
        $slugs = [$this->slug];
        $parent = $this->parent;
        
        while ($parent) {
            array_unshift($slugs, $parent->slug);
            $parent = $parent->parent;
        }
        
        return implode('/', $slugs);
    }

    public function getFullName()
    {
        $names = [$this->name];
        $parent = $this->parent;
        
        while ($parent) {
            array_unshift($names, $parent->name);
            $parent = $parent->parent;
        }
        
        return implode(' > ', $names);
    }

    public function getAllChildren()
    {
        $children = collect();
        
        foreach ($this->children as $child) {
            $children->push($child);
            $children = $children->merge($child->getAllChildren());
        }
        
        return $children;
    }

    public function getAllParentIds()
    {
        $ids = [];
        $parent = $this->parent;
        
        while ($parent) {
            $ids[] = $parent->id;
            $parent = $parent->parent;
        }
        
        return $ids;
    }

    public function getStuffCount()
    {
        $count = $this->stuff()->active()->count();
        
        foreach ($this->children as $child) {
            $count += $child->getStuffCount();
        }
        
        return $count;
    }

    public function hasChildren()
    {
        return $this->children()->exists();
    }

    public function isRoot()
    {
        return is_null($this->parent_id);
    }

    public function isLeaf()
    {
        return !$this->hasChildren();
    }

    public function getLevel()
    {
        return count($this->getAllParentIds());
    }

    public function canDelete()
    {
        return !$this->stuff()->exists() && !$this->children()->exists();
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
        $higherItem = static::where('parent_id', $this->parent_id)
                          ->where('sort_order', '>', $this->sort_order)
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
        $lowerItem = static::where('parent_id', $this->parent_id)
                         ->where('sort_order', '<', $this->sort_order)
                         ->orderBy('sort_order', 'desc')
                         ->first();

        if ($lowerItem) {
            $newOrder = $lowerItem->sort_order;
            $lowerItem->update(['sort_order' => $this->sort_order]);
            $this->update(['sort_order' => $newOrder]);
        }
    }

    public function delete()
    {
        if (!$this->canDelete()) {
            throw new \Exception('Cannot delete category with stuff or subcategories');
        }

        return parent::delete();
    }
}
