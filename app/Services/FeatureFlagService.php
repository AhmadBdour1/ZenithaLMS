<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class FeatureFlagService
{
    private const CACHE_KEY = 'features.all';

    /**
     * Get all feature flags from cache or database.
     */
    public function all(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, function () {
            return Setting::where('group', 'features')
                ->where('type', 'boolean')
                ->pluck('value', 'key')
                ->map(fn($value) => filter_var($value, FILTER_VALIDATE_BOOLEAN))
                ->toArray();
        });
    }

    /**
     * Get a specific feature flag value.
     */
    public function get(string $name, bool $default = true): bool
    {
        $flags = $this->all();
        $key = "features.{$name}";
        
        return $flags[$key] ?? $default;
    }

    /**
     * Set a feature flag value.
     */
    public function set(string $name, bool $value, bool $isPublic = false): Setting
    {
        $key = "features.{$name}";
        
        $setting = Setting::updateOrCreate(
            ['key' => $key],
            [
                'value' => $value ? '1' : '0',
                'type' => 'boolean',
                'group' => 'features',
                'is_public' => $isPublic,
            ]
        );

        $this->clearCache();
        
        Log::info('Feature flag updated', [
            'feature' => $name,
            'value' => $value,
            'is_public' => $isPublic,
        ]);

        return $setting;
    }

    /**
     * Enable a feature flag.
     */
    public function enable(string $name, bool $isPublic = false): Setting
    {
        return $this->set($name, true, $isPublic);
    }

    /**
     * Disable a feature flag.
     */
    public function disable(string $name, bool $isPublic = false): Setting
    {
        return $this->set($name, false, $isPublic);
    }

    /**
     * Check if a feature flag is enabled.
     */
    public function isEnabled(string $name, bool $default = true): bool
    {
        return $this->get($name, $default);
    }

    /**
     * Get all public feature flags.
     */
    public function getPublic(): array
    {
        return Setting::where('group', 'features')
            ->where('type', 'boolean')
            ->where('is_public', true)
            ->pluck('value', 'key')
            ->map(fn($value) => filter_var($value, FILTER_VALIDATE_BOOLEAN))
            ->toArray();
    }

    /**
     * Clear the feature flags cache.
     */
    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
        Log::debug('Feature flags cache cleared');
    }

    /**
     * Seed default feature flags idempotently.
     */
    public function seedDefaults(): void
    {
        $defaults = [
            'courses' => true,
            'ebooks' => true,
            'wallet' => true,
            'blog' => true,
            'forums' => true,
            'store' => false,
        ];

        foreach ($defaults as $feature => $enabled) {
            $this->set($feature, $enabled, false);
        }

        Log::info('Default feature flags seeded', $defaults);
    }
}
