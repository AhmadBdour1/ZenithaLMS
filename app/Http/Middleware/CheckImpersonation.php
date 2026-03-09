<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class CheckImpersonation
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Check if user is currently impersonating
        if (session('impersonate_original_user_id')) {
            $expiresAt = session('impersonate_expires_at');
            
            // Check if impersonation has expired
            if (now()->gt($expiresAt)) {
                // Auto-stop impersonation
                $originalUserId = session('impersonate_original_user_id');
                $originalUser = \App\Models\User::find($originalUserId);
                
                if ($originalUser) {
                    Auth::login($originalUser);
                }
                
                // Clear impersonation session
                session()->forget([
                    'impersonate_original_user_id',
                    'impersonate_original_user_data',
                    'impersonate_entity_type',
                    'impersonate_entity_id',
                    'impersonate_reason',
                    'impersonate_started_at',
                    'impersonate_duration',
                    'impersonate_expires_at',
                ]);
                
                // Flash message
                session()->flash('impersonation_expired', 'Your impersonation session has expired and you have been returned to your admin account.');
            }
        }
        
        return $next($request);
    }
}
