<?php

if (!function_exists('setting')) {
    /**
     * Get a setting value.
     */
    function setting(string $key, mixed $default = null): mixed
    {
        return app(\App\Services\SettingService::class)->get($key, $default);
    }
}

if (!function_exists('product_mode')) {
    /**
     * Get the current deployment mode.
     */
    function product_mode(): string
    {
        return (string) config('app.product_mode', 'standard');
    }
}

if (!function_exists('feature')) {
    /**
     * Get a feature flag value.
     */
    function feature(string $name, bool $default = true): bool
    {
        return app(\App\Services\FeatureFlagService::class)->isEnabled($name, $default);
    }
}
