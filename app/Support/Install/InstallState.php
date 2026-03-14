<?php

namespace App\Support\Install;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
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
        $path = base_path('storage/app/' . self::INSTALL_FILE);
        $exists = File::exists($path);
        
        Log::debug('InstallState::isInstalled() checking path: ' . $path . ', exists: ' . ($exists ? 'true' : 'false'));
        
        // Primary check: installed.json file
        if ($exists) {
            return true;
        }
        
        // Production-safe fallback: Check if central database has default tenant
        try {
            // Use direct database connection to avoid facade bootstrap issues
            $centralConfig = config('database.connections.central');
            if ($centralConfig) {
                $centralDbPath = $centralConfig['database'];
                
                // Handle database_path() function call
                if (str_contains($centralDbPath, 'database_path(')) {
                    $centralDbPath = database_path('central.sqlite');
                }
                
                // Fallback to main database if central.sqlite doesn't exist
                if (!file_exists($centralDbPath)) {
                    $centralDbPath = database_path('database.sqlite');
                }
                
                $pdo = new \PDO(
                    'sqlite:' . $centralDbPath,
                    null,
                    null,
                    [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
                );
                
                // Check if tenants table exists
                $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='tenants'");
                $tenantsTableExists = $stmt->fetchColumn() !== false;
                
                if ($tenantsTableExists) {
                    // Check if default tenant exists
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM tenants WHERE id = 'default'");
                    $stmt->execute();
                    $defaultTenantExists = $stmt->fetchColumn() > 0;
                    
                    Log::debug('InstallState::isInstalled() fallback check - DB: ' . $centralDbPath . ', tenants table: ' . ($tenantsTableExists ? 'true' : 'false') . ', default tenant: ' . ($defaultTenantExists ? 'true' : 'false'));
                    
                    return $defaultTenantExists;
                }
            }
        } catch (\Exception $e) {
            Log::warning('InstallState::isInstalled() fallback check failed', [
                'error' => $e->getMessage()
            ]);
        }
        
        return false;
    }

    /**
     * Mark the application as installed
     */
    public static function markInstalled(array $metadata = []): void
    {
        $path = base_path('storage/app/' . self::INSTALL_FILE);
        
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

        $path = base_path('storage/app/' . self::INSTALL_FILE);
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

        if (self::isInstalled()) {
            $path = base_path('storage/app/' . self::INSTALL_FILE);
            File::delete($path);
        }
    }

    /**
     * Get installation file path
     */
    public static function getInstallFilePath(): string
    {
        return base_path('storage/app/' . self::INSTALL_FILE);
    }
}
