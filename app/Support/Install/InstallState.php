<?php

namespace App\Support\Install;

use Illuminate\Support\Facades\File;
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
        return File::exists(storage_path(self::INSTALL_PATH . '/' . self::INSTALL_FILE));
    }

    /**
     * Mark the application as installed
     */
    public static function markInstalled(array $metadata = []): void
    {
        $data = array_merge([
            'installed_at' => now()->toISOString(),
            'app_version' => config('app.version', '1.0.0'),
            'laravel_version' => app()->version(),
            'php_version' => PHP_VERSION,
            'environment' => app()->environment(),
        ], $metadata);

        File::ensureDirectoryExists(storage_path(self::INSTALL_PATH));
        File::put(
            storage_path(self::INSTALL_PATH . '/' . self::INSTALL_FILE),
            json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );
    }

    /**
     * Get installation metadata
     */
    public static function getMetadata(): array
    {
        if (!self::isInstalled()) {
            return [];
        }

        $content = File::get(storage_path(self::INSTALL_PATH . '/' . self::INSTALL_FILE));
        return json_decode($content, true) ?? [];
    }

    /**
     * Reset installation state (for testing only)
     */
    public static function reset(): void
    {
        if (!app()->environment('testing')) {
            throw new \RuntimeException('InstallState::reset() can only be used in testing environment');
        }

        if (self::isInstalled()) {
            File::delete(storage_path(self::INSTALL_PATH . '/' . self::INSTALL_FILE));
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
