<?php

namespace App\Http\Controllers;

use App\Support\Install\InstallState;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class InstallerController extends Controller
{
    /**
     * Show the installation page
     */
    public function index()
    {
        // If already installed, redirect to login
        if (InstallState::isInstalled()) {
            return redirect('/login');
        }

        $requirements = $this->checkRequirements();
        $permissions = $this->checkPermissions();
        $dbConnection = $this->checkDatabaseConnection();

        return view('install.index', compact('requirements', 'permissions', 'dbConnection'));
    }

    /**
     * Check database connection
     */
    public function checkDatabase(Request $request)
    {
        try {
            DB::connection()->getPdo();
            
            return response()->json([
                'success' => true,
                'message' => 'Database connection successful'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Database connection failed: ' . $e->getMessage()
            ], 422);
        }
    }

    /**
     * Run the installation
     */
    public function run(Request $request)
    {
        // Prevent re-installation
        if (InstallState::isInstalled()) {
            return response()->json([
                'success' => false,
                'message' => 'Application is already installed'
            ], 422);
        }

        // Validate input
        $validator = Validator::make($request->all(), [
            'admin_name' => 'required|string|max:255',
            'admin_email' => 'required|email|max:255|unique:users,email',
            'admin_password' => 'required|string|min:8|confirmed',
            'site_name' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Check database connection
            DB::connection()->getPdo();

            // Run migrations
            Artisan::call('migrate', ['--force' => true]);

            // Run seeders
            Artisan::call('db:seed', ['--class' => 'RoleSeeder', '--force' => true]);
            Artisan::call('db:seed', ['--class' => 'OrganizationSeeder', '--force' => true]);
            Artisan::call('db:seed', ['--class' => 'CategorySeeder', '--force' => true]);
            Artisan::call('db:seed', ['--class' => 'SkillSeeder', '--force' => true]);

            // Create admin user
            $admin = \App\Models\User::create([
                'name' => $request->admin_name,
                'email' => $request->admin_email,
                'password' => Hash::make($request->admin_password),
                'role_id' => \App\Models\Role::where('name', 'admin')->first()->id,
                'email_verified_at' => now(),
            ]);

            // Update app name
            $this->updateEnvironmentFile('APP_NAME', $request->site_name);

            // Create storage link if not exists
            if (!File::exists(public_path('storage'))) {
                Artisan::call('storage:link');
            }

            // Clear caches
            Artisan::call('config:clear');
            Artisan::call('cache:clear');
            Artisan::call('view:clear');

            // Mark as installed
            InstallState::markInstalled([
                'admin_user_id' => $admin->id,
                'site_name' => $request->site_name,
                'admin_email' => $request->admin_email,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Installation completed successfully',
                'redirect_url' => route('login')
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Installation failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check system requirements
     */
    private function checkRequirements(): array
    {
        $requirements = [];

        // PHP Version
        $requirements['php_version'] = [
            'required' => '>= 8.1',
            'current' => PHP_VERSION,
            'status' => version_compare(PHP_VERSION, '8.1', '>=') ? 'ok' : 'error',
        ];

        // Required extensions
        $requiredExtensions = ['pdo', 'pdo_mysql', 'mbstring', 'tokenizer', 'xml', 'ctype', 'json', 'bcmath', 'fileinfo', 'openssl'];
        
        foreach ($requiredExtensions as $extension) {
            $requirements['extension_' . $extension] = [
                'required' => 'Required',
                'current' => extension_loaded($extension) ? 'Loaded' : 'Not loaded',
                'status' => extension_loaded($extension) ? 'ok' : 'error',
            ];
        }

        return $requirements;
    }

    /**
     * Check file permissions
     */
    private function checkPermissions(): array
    {
        $permissions = [];

        $paths = [
            storage_path() => 'Writable',
            base_path('bootstrap/cache') => 'Writable',
        ];

        foreach ($paths as $path => $required) {
            $permissions[basename($path)] = [
                'required' => $required,
                'current' => is_writable($path) ? 'Writable' : 'Not writable',
                'status' => is_writable($path) ? 'ok' : 'error',
            ];
        }

        return $permissions;
    }

    /**
     * Check database connection
     */
    private function checkDatabaseConnection(): array
    {
        try {
            DB::connection()->getPdo();
            return [
                'required' => 'Connected',
                'current' => 'Connected',
                'status' => 'ok',
            ];
        } catch (\Exception $e) {
            return [
                'required' => 'Connected',
                'current' => 'Not connected: ' . $e->getMessage(),
                'status' => 'error',
            ];
        }
    }

    /**
     * Update environment file
     */
    private function updateEnvironmentFile(string $key, string $value): void
    {
        $envFile = base_path('.env');
        
        if (File::exists($envFile)) {
            $content = File::get($envFile);
            
            // Escape the value for proper .env formatting
            $escapedValue = '"' . str_replace('"', '""', $value) . '"';
            
            // Update or add the key
            if (str_contains($content, $key . '=')) {
                $content = preg_replace("/^{$key}=.*/m", "{$key}={$escapedValue}", $content);
            } else {
                $content .= "\n{$key}={$escapedValue}";
            }
            
            File::put($envFile, $content);
        }
    }
}
