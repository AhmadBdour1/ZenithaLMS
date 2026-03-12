<?php

namespace App\Services\Tenancy;

use App\Models\Central\Plan;
use App\Models\Central\Tenant;
use App\Models\Central\TenantSubscription;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Stancl\Tenancy\Database\Models\Domain;

class TenantOnboardingService
{
    /**
     * Create a new tenant with all necessary setup.
     *
     * @param array $data
     * @return Tenant
     * @throws \Exception
     */
    public function createTenant(array $data): Tenant
    {
        return DB::connection('central')->transaction(function () use ($data) {
            // Validate plan exists
            $plan = Plan::find($data['plan_id']);
            if (!$plan) {
                throw new \Exception('Invalid plan selected');
            }

            // Create tenant
            $tenant = Tenant::create([
                'id' => $data['tenant_id'] ?? Str::uuid()->toString(),
                'organization_name' => $data['organization_name'],
                'admin_name' => $data['admin_name'],
                'admin_email' => $data['admin_email'],
                'status' => 'trial',
                'trial_ends_at' => now()->addDays(14),
                'primary_color' => $data['primary_color'] ?? '#3B82F6',
                'secondary_color' => $data['secondary_color'] ?? '#10B981',
            ]);

            // Create domain for tenant
            $subdomain = $data['subdomain'] ?? Str::slug($data['organization_name']);
            $baseDomain = config('tenancy.central_domains')[3] ?? 'zenithalms.test';
            
            Domain::create([
                'domain' => "{$subdomain}.{$baseDomain}",
                'tenant_id' => $tenant->id,
            ]);

            // Create subscription for tenant
            TenantSubscription::create([
                'tenant_id' => $tenant->id,
                'plan_id' => $plan->id,
                'status' => 'active',
                'starts_at' => now(),
                'trial_ends_at' => now()->addDays(14),
            ]);

            return $tenant;
        });
    }

    /**
     * Create tenant admin user after tenant database is ready.
     *
     * @param Tenant $tenant
     * @param array $adminData
     * @return void
     */
    public function createTenantAdmin(Tenant $tenant, array $adminData): void
    {
        tenancy()->initialize($tenant);

        // Create admin user in tenant context
        $user = \App\Models\User::create([
            'name' => $adminData['name'],
            'email' => $adminData['email'],
            'password' => bcrypt($adminData['password']),
            'email_verified_at' => now(),
        ]);

        // Assign admin role if role system exists
        if (class_exists(\App\Models\Role::class)) {
            $adminRole = \App\Models\Role::firstOrCreate(
                ['name' => 'admin'],
                [
                    'slug' => 'admin',
                    'display_name' => 'Administrator',
                    'description' => 'Full system access'
                ]
            );
            
            if (method_exists($user, 'roles')) {
                $user->roles()->attach($adminRole->id);
            }
        }

        tenancy()->end();
    }

    /**
     * Get tenant by domain.
     *
     * @param string $domain
     * @return Tenant|null
     */
    public function getTenantByDomain(string $domain): ?Tenant
    {
        $domainModel = Domain::where('domain', $domain)->first();
        return $domainModel ? $domainModel->tenant : null;
    }

    /**
     * Suspend a tenant.
     *
     * @param string $tenantId
     * @return bool
     */
    public function suspendTenant(string $tenantId): bool
    {
        $tenant = Tenant::find($tenantId);
        if ($tenant) {
            $tenant->update(['status' => 'suspended']);
            return true;
        }
        return false;
    }

    /**
     * Activate a tenant.
     *
     * @param string $tenantId
     * @return bool
     */
    public function activateTenant(string $tenantId): bool
    {
        $tenant = Tenant::find($tenantId);
        if ($tenant) {
            $tenant->update(['status' => 'active']);
            return true;
        }
        return false;
    }
}
