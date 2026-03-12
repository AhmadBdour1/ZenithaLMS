<?php

namespace App\Modules;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class ModuleServiceProvider extends ServiceProvider
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
        $this->registerModuleRoutes();
    }

    /**
     * Register routes for all modules.
     */
    private function registerModuleRoutes(): void
    {
        $modulesPath = app_path('Modules');

        if (!is_dir($modulesPath)) {
            return;
        }

        // Get all module directories
        $modules = glob($modulesPath . '/*', GLOB_ONLYDIR);

        foreach ($modules as $modulePath) {
            $moduleName = basename($modulePath);
            $routesFile = $modulePath . '/routes.php';

            // Load routes file if it exists
            if (file_exists($routesFile)) {
                $this->loadRoutesFrom($routesFile);
            }
        }
    }
}
