<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Events\QueryExecuted;

class QueryOptimizationServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot()
    {
        // Listen for query events in development
        if (app()->environment('local')) {
            DB::listen(function (QueryExecuted $query) {
                // Log queries that take more than 100ms
                if ($query->time > 100) {
                    logger()->warning('Slow query detected', [
                        'sql' => $query->sql,
                        'time' => $query->time,
                        'bindings' => $query->bindings,
                    ]);
                }
            });
        }
    }
}
