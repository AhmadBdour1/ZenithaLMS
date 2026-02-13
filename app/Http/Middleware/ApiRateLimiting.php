<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Http\Exceptions\ThrottleRequestsException;

class ApiRateLimiting
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $key = $this->resolveRequestSignature($request);
        
        // Different rate limits for different endpoints
        $limits = $this->getRateLimits($request);
        
        foreach ($limits as $limit) {
            if (RateLimiter::tooManyAttempts($key, $limit['attempts'])) {
                throw new ThrottleRequestsException(
                    'Too many requests. Please try again later.',
                    $limit['attempts'],
                    $limit['decay']
                );
            }
            
            RateLimiter::hit($key, $limit['decay']);
        }
        
        return $next($request);
    }
    
    /**
     * Resolve rate limit signature for the request.
     */
    protected function resolveRequestSignature(Request $request): string
    {
        if ($user = $request->user()) {
            return 'api:'.$user->id.':'.$request->ip();
        }
        
        return 'api:guest:'.$request->ip();
    }
    
    /**
     * Get rate limits based on endpoint and user type
     */
    protected function getRateLimits(Request $request): array
    {
        $path = $request->route()->uri();
        $user = $request->user();
        
        // Authentication endpoints - stricter limits
        if (str_contains($path, 'login') || str_contains($path, 'register')) {
            return [
                ['attempts' => 5, 'decay' => 60], // 5 requests per minute
            ];
        }
        
        // Payment/wallet endpoints - very strict
        if (str_contains($path, 'wallet') || str_contains($path, 'payment')) {
            return [
                ['attempts' => 10, 'decay' => 60], // 10 requests per minute
            ];
        }
        
        // Content creation endpoints
        if (in_array($request->method(), ['POST', 'PUT', 'DELETE'])) {
            return [
                ['attempts' => 30, 'decay' => 60], // 30 requests per minute
            ];
        }
        
        // Read endpoints - more lenient
        if ($user) {
            return [
                ['attempts' => 100, 'decay' => 60], // 100 requests per minute for authenticated users
            ];
        }
        
        return [
            ['attempts' => 60, 'decay' => 60], // 60 requests per minute for guests
        ];
    }
}
