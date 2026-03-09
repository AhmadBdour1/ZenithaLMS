<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AuraOrderItem extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'order_id',
        'product_id',
        'quantity',
        'price',
        'total',
        'meta_data',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'total' => 'decimal:2',
        'meta_data' => 'array',
    ];

    // Relationships
    public function order()
    {
        return $this->belongsTo(AuraOrder::class);
    }

    public function product()
    {
        return $this->belongsTo(AuraProduct::class);
    }

    // Methods
    public function getFormattedPrice()
    {
        return number_format($this->price, 2);
    }

    public function getFormattedTotal()
    {
        return number_format($this->total, 2);
    }

    public function getProductName()
    {
        return $this->product ? $this->product->name : 'Deleted Product';
    }

    public function getProductSlug()
    {
        return $this->product ? $this->product->slug : null;
    }

    public function getProductImage()
    {
        return $this->product ? $this->product->product_image : null;
    }

    public function isDownloadable()
    {
        return $this->product && $this->product->downloadable;
    }

    public function getDownloadableFiles()
    {
        if (!$this->isDownloadable()) {
            return [];
        }

        return $this->product->getDownloadableFiles();
    }
}
