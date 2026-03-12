<?php

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use App\Models\Central\Plan;
use App\Services\Tenancy\TenantOnboardingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class TenantRegistrationController extends Controller
{
    protected $onboardingService;

    public function __construct(TenantOnboardingService $onboardingService)
    {
        $this->onboardingService = $onboardingService;
    }

    /**
     * Show the tenant registration form.
     */
    public function showRegistrationForm()
    {
        $plans = Plan::active()->orderBy('sort_order')->get();
        return view('central.register', compact('plans'));
    }

    /**
     * Process tenant registration.
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'organization_name' => 'required|string|max:255',
            'subdomain' => 'required|string|max:63|alpha_dash|unique:domains,domain',
            'admin_name' => 'required|string|max:255',
            'admin_email' => 'required|email|max:255',
            'admin_password' => 'required|string|min:8|confirmed',
            'plan_id' => 'required|exists:plans,id',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            // Create tenant
            $tenant = $this->onboardingService->createTenant([
                'organization_name' => $request->organization_name,
                'subdomain' => Str::slug($request->subdomain),
                'admin_name' => $request->admin_name,
                'admin_email' => $request->admin_email,
                'plan_id' => $request->plan_id,
            ]);

            // Wait briefly for database creation
            sleep(1);

            // Create admin user in tenant context
            $this->onboardingService->createTenantAdmin($tenant, [
                'name' => $request->admin_name,
                'email' => $request->admin_email,
                'password' => $request->admin_password,
            ]);

            // Get the tenant's domain
            $domain = $tenant->domains->first();

            return redirect()->route('tenant.success')
                ->with('tenant_domain', $domain->domain)
                ->with('admin_email', $request->admin_email);

        } catch (\Exception $e) {
            return redirect()->back()
                ->withErrors(['error' => 'Failed to create tenant: ' . $e->getMessage()])
                ->withInput();
        }
    }

    /**
     * Show success page after registration.
     */
    public function success()
    {
        if (!session()->has('tenant_domain')) {
            return redirect()->route('landing');
        }

        return view('central.success', [
            'domain' => session('tenant_domain'),
            'email' => session('admin_email'),
        ]);
    }
}
