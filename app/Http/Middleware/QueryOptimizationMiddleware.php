<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class QueryOptimizationMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        // Enable query logging in development
        if (app()->environment('local')) {
            DB::enableQueryLog();
        }

        // Set database connection optimizations
        $this->optimizeDatabaseConnection();

        $response = $next($request);

        // Log slow queries in development
        if (app()->environment('local')) {
            $this->logSlowQueries();
        }

        return $response;
    }

    /**
     * Optimize database connection settings
     */
    private function optimizeDatabaseConnection()
    {
        // Set MySQL-specific optimizations if using MySQL
        if (config('database.default') === 'mysql') {
            DB::statement("SET SESSION sql_mode = 'STRICT_TRANS_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION'");
            DB::statement("SET SESSION innodb_lock_wait_timeout = 5");
            DB::statement("SET SESSION query_cache_type = ON");
        }

        // Set SQLite optimizations if using SQLite
        if (config('database.default') === 'sqlite') {
            DB::statement("PRAGMA journal_mode = WAL");
            DB::statement("PRAGMA synchronous = NORMAL");
            DB::statement("PRAGMA cache_size = 10000");
            DB::statement("PRAGMA temp_store = MEMORY");
        }
    }

    /**
     * Log slow queries for optimization
     */
    private function logSlowQueries()
    {
        $queries = DB::getQueryLog();
        $slowQueries = [];

        foreach ($queries as $query) {
            if ($query['time'] > 100) { // Log queries taking more than 100ms
                $slowQueries[] = [
                    'sql' => $query['query'],
                    'time' => $query['time'],
                    'bindings' => $query['bindings'],
                ];
            }
        }

        if (!empty($slowQueries)) {
            Log::warning('Slow queries detected', [
                'slow_queries' => $slowQueries,
                'total_queries' => count($queries),
                'total_time' => array_sum(array_column($queries, 'time')),
            ]);
        }
    }
}
