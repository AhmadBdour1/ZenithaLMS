<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     * Checks if user has the required role.
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $user = $request->user();
        
        if (!$user) {
            // For web requests, redirect to login
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated'], 401);
            }
            return redirect()->route('login');
        }
        
        if ($user->role?->name !== $role) {
            // For web requests, show 403 error page
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Forbidden'], 403);
            }
            abort(403, 'Unauthorized access');
        }
        
        return $next($request);
    }
}
