<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SettingService
{
    /**
     * Cache key for all settings.
     */
    const CACHE_KEY = 'settings.all';

    /**
     * Get a setting value.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $settings = $this->allGrouped();
        
        return $settings[$key]['typed_value'] ?? $default;
    }

    /**
     * Set a setting value.
     */
    public function set(string $key, mixed $value, string $type = 'string', string $group = 'general', bool $isPublic = false): Setting
    {
        $setting = Setting::updateOrCreate(
            ['key' => $key],
            [
                'value' => $this->castValueForStorage($value, $type),
                'type' => $type,
                'group' => $group,
                'is_public' => $isPublic,
            ]
        );

        $this->clearCache();
        
        Log::info('Setting updated', [
            'key' => $key,
            'type' => $type,
            'group' => $group,
            'is_public' => $isPublic,
        ]);

        return $setting;
    }

    /**
     * Get all settings grouped.
     */
    public function allGrouped(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, function () {
            $settings = Setting::all()->groupBy('group');
            
            $result = [];
            foreach ($settings as $group => $groupSettings) {
                foreach ($groupSettings as $setting) {
                    $result[$setting->key] = [
                        'value' => $setting->value,
                        'typed_value' => $this->castValueFromStorage($setting->value, $setting->type),
                        'type' => $setting->type,
                        'group' => $setting->group,
                        'is_public' => $setting->is_public,
                    ];
                }
            }
            
            return $result;
        });
    }

    /**
     * Get public settings only.
     */
    public function getPublic(): array
    {
        $allSettings = $this->allGrouped();
        
        return array_filter($allSettings, fn($setting) => $setting['is_public']);
    }

    /**
     * Clear settings cache.
     */
    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Delete a setting.
     */
    public function delete(string $key): bool
    {
        $deleted = Setting::where('key', $key)->delete();
        
        if ($deleted) {
            $this->clearCache();
            Log::info('Setting deleted', ['key' => $key]);
        }
        
        return $deleted;
    }

    /**
     * Cast value for storage.
     */
    private function castValueForStorage(mixed $value, string $type): string
    {
        return match ($type) {
            'json', 'array' => json_encode($value),
            'boolean', 'bool' => $value ? '1' : '0',
            default => (string) $value,
        };
    }

    /**
     * Cast value from storage.
     */
    private function castValueFromStorage(string $value, string $type): mixed
    {
        return match ($type) {
            'boolean', 'bool' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'integer', 'int' => (int) $value,
            'float', 'double' => (float) $value,
            'json', 'array' => json_decode($value, true),
            default => $value,
        };
    }
}
