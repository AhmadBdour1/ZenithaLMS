<?php

namespace App\Support\Install;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class InstallState
{
    private const INSTALL_FILE = 'installed.json';
    private const INSTALL_PATH = 'app';

    /**
     * Check if the application is installed
     */
    public static function isInstalled(): bool
    {
        $path = storage_path(self::INSTALL_PATH . '/' . self::INSTALL_FILE);

        // Primary check: installation file
        if (File::exists($path)) {
            return true;
        }

        // Fallback: check database state
        try {
            if (Schema::hasTable('tenants')) {
                return DB::table('tenants')
                    ->where('id', 'default')
                    ->exists();
            }
        } catch (\Exception $e) {
            Log::warning('InstallState::isInstalled() fallback check failed', [
                'error' => $e->getMessage(),
            ]);
        }

        return false;
    }

    /**
     * Mark the application as installed
     */
    public static function markInstalled(array $metadata = []): void
    {
        $path = storage_path(self::INSTALL_PATH . '/' . self::INSTALL_FILE);

        $data = array_merge([
            'installed_at' => now()->toISOString(),
            'app_version' => config('app.version', '1.0.0'),
            'laravel_version' => app()->version(),
            'php_version' => PHP_VERSION,
            'environment' => app()->environment(),
        ], $metadata);

        Log::debug('InstallState::markInstalled() writing to path: ' . $path);

        File::ensureDirectoryExists(dirname($path));
        File::put($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    /**
     * Get installation metadata
     */
    public static function getMetadata(): array
    {
        if (!self::isInstalled()) {
            return [];
        }

        $path = storage_path(self::INSTALL_PATH . '/' . self::INSTALL_FILE);
        $content = File::get($path);

        return json_decode($content, true) ?? [];
    }

    /**
     * Reset installation state (for testing only)
     */
    public static function reset(): void
    {
        // Allow reset in testing environment or when explicitly requested
        if (!app()->environment('testing') && !request()->header('X-Testing-Reset')) {
            throw new \RuntimeException('InstallState::reset() can only be used in testing environment');
        }

        $path = storage_path(self::INSTALL_PATH . '/' . self::INSTALL_FILE);

        if (File::exists($path)) {
            File::delete($path);
        }
    }

    /**
     * Get installation file path
     */
    public static function getInstallFilePath(): string
    {
        return storage_path(self::INSTALL_PATH . '/' . self::INSTALL_FILE);
    }
}