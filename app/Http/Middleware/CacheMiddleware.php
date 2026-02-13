<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class CacheMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only cache GET requests
        if (!$request->isMethod('GET')) {
            return $next($request);
        }

        // Generate cache key based on URL and user
        $cacheKey = $this->generateCacheKey($request);

        // Check if response is cached
        if (Cache::has($cacheKey)) {
            return response(Cache::get($cacheKey))
                ->header('X-Cache', 'HIT');
        }

        // Get response and cache it
        $response = $next($request);

        // Only cache successful responses
        if ($response->getStatusCode() === 200) {
            $cacheData = [
                'content' => $response->getContent(),
                'headers' => $response->headers->all(),
            ];

            // Cache for different durations based on endpoint
            $duration = $this->getCacheDuration($request);
            Cache::put($cacheKey, $cacheData, $duration);

            return response($cacheData['content'])
                ->withHeaders($cacheData['headers'])
                ->header('X-Cache', 'MISS');
        }

        return $response;
    }

    /**
     * Generate cache key for the request
     */
    private function generateCacheKey(Request $request): string
    {
        $user = $request->user();
        $userId = $user ? $user->id : 'guest';
        
        return 'api_cache:' . md5($request->fullUrl() . ':' . $userId);
    }

    /**
     * Get cache duration based on endpoint
     */
    private function getCacheDuration(Request $request): int
    {
        $path = $request->path();

        // Static data - cache longer
        if (str_contains($path, 'courses') || str_contains($path, 'quizzes')) {
            return 3600; // 1 hour
        }

        // User-specific data - cache shorter
        if (str_contains($path, 'user/')) {
            return 300; // 5 minutes
        }

        // Dynamic data - cache very short
        if (str_contains($path, 'forums') || str_contains($path, 'virtual-classes')) {
            return 60; // 1 minute
        }

        return 1800; // 30 minutes default
    }
}
