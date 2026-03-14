<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;
use App\Models\Central\Tenant;
use App\Support\Install\InstallState;

class InitializeSingleDomainTenancy
{
    /**
     * Handle an incoming request.
     * 
     * Initializes tenancy for single-domain deployments before session/auth resolution.
     * This ensures all authentication happens in tenant context.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip in testing environment unless explicitly needed
        if (app()->environment('testing')) {
            return $next($request);
        }

        // Skip if tenancy is already initialized
        if (tenancy()->initialized) {
            return $next($request);
        }

        // Skip installer and central-only routes
        if ($request->is('install/*') || $request->is('install')) {
            return $next($request);
        }

        // Skip if app is not yet installed
        if (!InstallState::isInstalled()) {
            return $next($request);
        }

        try {
            // Resolve default tenant in a robust way
            $tenantId = env('TENANCY_DEFAULT_TENANT_ID', 'default');
            $tenant = Tenant::find($tenantId);

            if ($tenant) {
                Log::debug('InitializeSingleDomainTenancy: Initializing tenant', [
                    'tenant_id' => $tenant->id,
                    'path' => $request->path(),
                    'method' => $request->method()
                ]);
                
                tenancy()->initialize($tenant);
            } else {
                // Fail safely and clearly if tenant is missing after installation
                if (InstallState::isInstalled()) {
                    Log::error('InitializeSingleDomainTenancy: Default tenant not found after installation', [
                        'tenant_id' => $tenantId,
                        'path' => $request->path(),
                        'available_tenants' => Tenant::pluck('id')->toArray()
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error('InitializeSingleDomainTenancy: Failed to initialize tenancy', [
                'error' => $e->getMessage(),
                'path' => $request->path()
            ]);
            
            // In production, fail gracefully
            if (app()->environment('production')) {
                abort(500, 'Service temporarily unavailable. Please try again later.');
            }
        }

        return $next($request);
    }
}
