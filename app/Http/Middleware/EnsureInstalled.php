<?php

namespace App\Http\Middleware;

use App\Support\Install\InstallState;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureInstalled
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // If not installed, redirect to installer (except for installer routes)
        if (!InstallState::isInstalled()) {
            if ($request->is('install*') || $request->is('api/health') || $request->is('up')) {
                return $next($request);
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Application not installed',
                    'install_url' => url('/install')
                ], 503);
            }

            return redirect('/install');
        }

        // If installed and trying to access installer, redirect to login
        if ($request->is('install*')) {
            return redirect('/login');
        }

        return $next($request);
    }
}
