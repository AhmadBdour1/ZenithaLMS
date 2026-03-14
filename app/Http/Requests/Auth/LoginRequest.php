<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;
use App\Models\Central\Tenant;
use App\Support\Install\InstallState;
use Stancl\Tenancy\Facades\Tenancy;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        // CRITICAL FIX: Force tenant initialization before authentication
        if (!Tenancy::initialized()) {
            $tenant = Tenant::find('default');
            
            if ($tenant) {
                tenancy()->initialize($tenant);
            }
        }

        // Defensive safeguard: Ensure tenancy is initialized for single-domain production
        $this->ensureTenancyInitialized();

        if (! Auth::attempt($this->only('email', 'password'), $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Ensure tenancy is initialized for single-domain deployments.
     * This is a safety net, not the primary mechanism.
     */
    private function ensureTenancyInitialized(): void
    {
        // Skip in testing environment
        if (app()->environment('testing')) {
            return;
        }

        // Skip if tenancy is already initialized
        if (tenancy()->initialized) {
            return;
        }

        // Skip if app is not installed
        if (!InstallState::isInstalled()) {
            return;
        }

        try {
            $tenantId = env('TENANCY_DEFAULT_TENANT_ID', 'default');
            $tenant = Tenant::find($tenantId);

            if ($tenant) {
                Log::debug('LoginRequest: Initializing tenant as safety net', [
                    'tenant_id' => $tenant->id,
                    'email' => $this->input('email')
                ]);
                
                tenancy()->initialize($tenant);
            } else {
                Log::warning('LoginRequest: Default tenant not found for authentication', [
                    'tenant_id' => $tenantId,
                    'email' => $this->input('email')
                ]);
            }
        } catch (\Exception $e) {
            Log::error('LoginRequest: Failed to initialize tenancy', [
                'error' => $e->getMessage(),
                'email' => $this->input('email')
            ]);
        }
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }
}
