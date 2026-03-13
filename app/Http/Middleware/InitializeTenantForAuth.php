<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;
use App\Models\Central\Tenant;

class InitializeTenantForAuth
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // For single-domain deployments like Railway, initialize default tenant
        // This ensures authentication runs in tenant context
        if (!app()->environment('testing')) {
            $tenant = Tenant::find('default');
            
            if ($tenant) {
                Log::debug('InitializeTenantForAuth: Initializing default tenant for auth', [
                    'tenant_id' => $tenant->id,
                    'path' => $request->path()
                ]);
                
                tenancy()->initialize($tenant);
            } else {
                Log::warning('InitializeTenantForAuth: Default tenant not found', [
                    'path' => $request->path()
                ]);
            }
        }

        return $next($request);
    }
}
