<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AffiliateClick extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'affiliate_id',
        'ip_address',
        'user_agent',
        'referer',
        'landing_page',
        'clicked_at',
        'converted_at',
        'conversion_id',
    ];

    protected $casts = [
        'clicked_at' => 'datetime',
        'converted_at' => 'datetime',
    ];

    // Relationships
    public function affiliate()
    {
        return $this->belongsTo(Affiliate::class);
    }

    public function conversion()
    {
        return $this->belongsTo(AffiliateConversion::class, 'conversion_id');
    }

    // Scopes
    public function scopeConverted($query)
    {
        return $query->whereNotNull('converted_at');
    }

    public function scopeUnconverted($query)
    {
        return $query->whereNull('converted_at');
    }

    public function scopeByPeriod($query, $startDate, $endDate)
    {
        return $query->whereBetween('clicked_at', [$startDate, $endDate]);
    }
}
