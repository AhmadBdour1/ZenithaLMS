<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
        'is_public',
    ];

    protected $casts = [
        'is_public' => 'boolean',
    ];

    /**
     * Get the typed value attribute.
     */
    protected function typedValue(): Attribute
    {
        return Attribute::make(
            get: function () {
                return match ($this->type) {
                    'boolean', 'bool' => filter_var($this->value, FILTER_VALIDATE_BOOLEAN),
                    'integer', 'int' => (int) $this->value,
                    'float', 'double' => (float) $this->value,
                    'json', 'array' => json_decode($this->value, true),
                    default => $this->value,
                };
            },
            set: function ($value) {
                return match ($this->type) {
                    'json', 'array' => json_encode($value),
                    default => (string) $value,
                };
            }
        );
    }

    /**
     * Scope to get public settings.
     */
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    /**
     * Scope to get settings by group.
     */
    public function scopeGroup($query, string $group)
    {
        return $query->where('group', $group);
    }
}
