<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Language extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'native_name',
        'flag',
        'is_default',
        'is_active',
        'is_rtl',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
        'is_rtl' => 'boolean',
    ];

    /**
     * Get the translations for the language
     */
    public function translations()
    {
        return $this->hasMany(Translation::class, 'language_code', 'code');
    }

    /**
     * Get the default language
     */
    public static function getDefault()
    {
        return static::where('is_default', true)->first();
    }

    /**
     * Get active languages
     */
    public static function getActive()
    {
        return static::where('is_active', true)->orderBy('name')->get();
    }

    /**
     * Get language by code
     */
    public static function getByCode($code)
    {
        return static::where('code', $code)->first();
    }

    /**
     * Check if language is RTL
     */
    public function isRtl()
    {
        return $this->is_rtl;
    }

    /**
     * Get language display name
     */
    public function getDisplayNameAttribute()
    {
        return $this->native_name ?? $this->name;
    }

    /**
     * Get language with flag
     */
    public function getNameWithFlagAttribute()
    {
        return $this->flag . ' ' . $this->name;
    }

    /**
     * Scope to get active languages
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get RTL languages
     */
    public function scopeRtl($query)
    {
        return $query->where('is_rtl', true);
    }

    /**
     * Scope to get default language
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }
}
