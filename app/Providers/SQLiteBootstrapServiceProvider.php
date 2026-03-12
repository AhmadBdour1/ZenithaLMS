<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Config;

class SQLiteBootstrapServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Only handle SQLite database
        if (config('database.default') !== 'sqlite') {
            return;
        }

        $databasePath = config('database.connections.sqlite.database');
        
        // Ensure database directory exists
        $databaseDir = dirname($databasePath);
        if (!is_dir($databaseDir)) {
            mkdir($databaseDir, 0755, true);
        }
        
        // Create empty SQLite file if it doesn't exist
        if (!file_exists($databasePath)) {
            touch($databasePath);
            chmod($databasePath, 0644);
        }
    }
}
