<?php

namespace App\Http\Controllers;

use App\Support\Install\InstallState;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class InstallerController extends Controller
{
    /**
     * Constructor - set file session for installer
     */
    public function __construct()
    {
        // Force file session during installation to avoid database dependency
        Config::set('session.driver', 'file');
        // Set session domain to null to avoid domain mismatch
        Config::set('session.domain', null);
        // Set secure to false for local installation
        Config::set('session.secure', false);
        // Set same site to lax for installer
        Config::set('session.same_site', 'lax');
    }
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
        Log::info('Installer: Checking database connection');
        
        try {
            DB::connection()->getPdo();
            Log::info('Installer: Database connection successful');
            
            return response()->json([
                'success' => true,
                'message' => 'Database connection successful'
            ]);
        } catch (\Exception $e) {
            Log::error('Installer: Database connection failed', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Database connection failed: ' . $e->getMessage(),
                'exception_message' => $e->getMessage(),
                'exception_file' => $e->getFile(),
                'exception_line' => $e->getLine()
            ], 422);
        }
    }

    /**
     * Run the installation
     */
    public function run(Request $request)
    {
        Log::info('Installer: Starting installation process');
        
        // Prevent re-installation
        if (InstallState::isInstalled()) {
            Log::warning('Installer: Application already installed');
            return response()->json([
                'success' => false,
                'message' => 'Application is already installed'
            ], 422);
        }

        // RUN MIGRATIONS FIRST - BEFORE ANY VALIDATION THAT QUERIES TABLES
        Log::info('Installer: Running migrations before validation');
        $this->ensureDatabaseFileExists();

        // Step 1: Run central migrations
        Log::info('Installer: Step 1 - Running central migrations');
        if (config('database.default') === 'sqlite') {
            DB::statement('PRAGMA foreign_keys=OFF');
            
            Artisan::call('migrate:fresh', [
                '--force' => true,
                '--path' => 'database/migrations'
            ]);
            
            DB::statement('PRAGMA foreign_keys=ON');
        } else {
            Artisan::call('migrate', [
                '--force' => true,
                '--path' => 'database/migrations'
            ]);
        }
        Log::info('Installer: Central migrations completed');

        // Step 2: Create tenant
        Log::info('Installer: Step 2 - Creating default tenant');
        $tenant = $this->createDefaultTenant();
        
        // Step 3: Initialize tenant context
        Log::info('Installer: Step 3 - Initializing tenant context');
        tenancy()->initialize($tenant);
        
        // Step 4: Run tenant migrations
        Log::info('Installer: Step 4 - Running tenant migrations');
        Artisan::call('tenants:migrate', [
            '--force' => true,
            '--tenants' => ['default']
        ]);
        Log::info('Installer: Tenant migrations completed');

        // Step 5: Verify tenant tables exist
        Log::info('Installer: Step 5 - Verifying tenant tables exist');
        if (!Schema::hasTable('users')) {
            Log::error('Installer: Users table still missing after tenant migrations');
            throw new \Exception('Users table still missing after tenant migrations');
        }
        
        if (!Schema::hasTable('roles')) {
            Log::error('Installer: Roles table still missing after tenant migrations');
            throw new \Exception('Roles table still missing after tenant migrations');
        }
        
        if (!Schema::hasTable('courses')) {
            Log::error('Installer: Courses table still missing after tenant migrations');
            throw new \Exception('Courses table still missing after tenant migrations');
        }
        
        Log::info('Installer: All tenant tables verified to exist');

        // Validate input (now safe to query users table)
        Log::info('Installer: Validating input data');
        $validator = Validator::make($request->all(), [
            'admin_name' => 'required|string|max:255',
            'admin_email' => 'required|email|max:255',
            'admin_password' => 'required|string|min:8|confirmed',
            'site_name' => 'required|string|max:255',
        ]);

        // Check if admin email already exists and provide a helpful error
        if (\App\Models\User::where('email', $request->admin_email)->exists()) {
            Log::warning('Installer: Admin email already exists', ['email' => $request->admin_email]);
            
            // For re-installation, we can allow existing admin user
            // but we need to validate the password matches existing user
            $existingAdmin = \App\Models\User::where('email', $request->admin_email)->first();
            if (!Hash::check($request->admin_password, $existingAdmin->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Admin user already exists but password does not match. Please use the correct password for the existing admin user.',
                    'errors' => ['admin_password' => 'Password does not match existing admin user password.']
                ], 422);
            }
        }

        if ($validator->fails()) {
            Log::error('Installer: Validation failed', [
                'errors' => $validator->errors()->toArray()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Step 6: Run tenant seeders
            Log::info('Installer: Step 6 - Running tenant seeders');
            Artisan::call('tenants:seed', [
                '--class' => 'RoleSeeder',
                '--tenants' => ['default'],
                '--force' => true
            ]);
            Artisan::call('tenants:seed', [
                '--class' => 'OrganizationSeeder',
                '--tenants' => ['default'],
                '--force' => true
            ]);
            Artisan::call('tenants:seed', [
                '--class' => 'CategorySeeder',
                '--tenants' => ['default'],
                '--force' => true
            ]);
            Artisan::call('tenants:seed', [
                '--class' => 'SkillSeeder',
                '--tenants' => ['default'],
                '--force' => true
            ]);
            Log::info('Installer: Tenant seeders completed');

            // Step 7: Create admin user (if not exists)
            Log::info('Installer: Step 7 - Creating admin user if not exists', [
                'email' => $request->admin_email,
                'name' => $request->admin_name
            ]);
            
            $admin = \App\Models\User::where('email', $request->admin_email)->first();
            
            if (!$admin) {
                $admin = \App\Models\User::create([
                    'name' => $request->admin_name,
                    'email' => $request->admin_email,
                    'password' => Hash::make($request->admin_password),
                    'email_verified_at' => now(),
                ]);
                Log::info('Installer: Admin user created', ['user_id' => $admin->id]);
            } else {
                Log::info('Installer: Admin user already exists, reusing', ['user_id' => $admin->id]);
            }

            // Step 8: Assign admin role (if not already assigned)
            Log::info('Installer: Step 8 - Assigning admin role to user if not already assigned');
            $adminRole = \App\Models\Role::where('name', 'admin')->first();
            if (!$adminRole) {
                Log::error('Installer: Admin role not found after seeding');
                throw new \Exception('Admin role was not created during seeding');
            }
            
            if (!$admin->roles()->where('role_id', $adminRole->id)->exists()) {
                $admin->roles()->attach($adminRole->id);
                Log::info('Installer: Admin role assigned successfully');
            } else {
                Log::info('Installer: Admin role already assigned to user');
            }

            // Step 9: Mark installation complete
            Log::info('Installer: Step 9 - Marking installation complete');
            InstallState::markInstalled([
                'admin_user_id' => $admin->id,
                'site_name' => $request->site_name,
                'admin_email' => $request->admin_email,
            ]);

            // Update app name
            Log::info('Installer: Updating APP_NAME');
            $this->updateEnvironmentFile('APP_NAME', $request->site_name);

            // Create storage link if not exists
            if (!File::exists(public_path('storage'))) {
                Log::info('Installer: Creating storage link');
                Artisan::call('storage:link');
            }

            // Clear caches
            Log::info('Installer: Clearing caches');
            Artisan::call('config:clear');
            Artisan::call('cache:clear');
            Artisan::call('view:clear');

            Log::info('Installer: Installation completed successfully');
            return response()->json([
                'success' => true,
                'message' => 'Installation completed successfully',
                'redirect_url' => route('login')
            ]);

        } catch (\Exception $e) {
            Log::error('Installer: Installation failed', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Installation failed',
                'exception_message' => $e->getMessage(),
                'exception_file' => $e->getFile(),
                'exception_line' => $e->getLine()
            ], 500);
        }
    }

    /**
     * Create default tenant for installation
     */
    private function createDefaultTenant(): \App\Models\Central\Tenant
    {
        Log::info('Installer: Creating or reusing default tenant');
        
        // Check if tenant already exists
        $existingTenant = \App\Models\Central\Tenant::find('default');
        
        if ($existingTenant) {
            Log::info('Installer: Default tenant already exists, reusing it', ['tenant_id' => $existingTenant->id]);
            
            // Ensure tenant database file exists
            $this->createTenantDatabaseFile($existingTenant);
            
            return $existingTenant;
        }
        
        // Create new tenant
        $tenant = \App\Models\Central\Tenant::create([
            'id' => 'default',
            'tenancy_db_name' => 'tenant_default',
            'tenancy_db_username' => config('database.connections.sqlite.username'),
            'tenancy_db_password' => config('database.connections.sqlite.password'),
        ]);
        
        // Create tenant database file automatically
        $this->createTenantDatabaseFile($tenant);
        
        Log::info('Installer: Default tenant created', ['tenant_id' => $tenant->id]);
        return $tenant;
    }

    /**
     * Create tenant database file automatically
     */
    private function createTenantDatabaseFile(\App\Models\Central\Tenant $tenant): void
    {
        $dbConnection = config('database.default', 'mysql');
        
        if ($dbConnection === 'sqlite') {
            $tenantDbPath = database_path('tenant_' . $tenant->id . '.sqlite');
            
            Log::info('Installer: Creating tenant database file', ['path' => $tenantDbPath]);
            
            // Ensure database directory exists
            $tenantDbDir = dirname($tenantDbPath);
            if (!is_dir($tenantDbDir)) {
                Log::info('Installer: Creating tenant database directory', ['dir' => $tenantDbDir]);
                mkdir($tenantDbDir, 0755, true);
            }
            
            // Create empty SQLite file if it doesn't exist
            if (!file_exists($tenantDbPath)) {
                Log::info('Installer: Creating tenant SQLite database file', ['file' => $tenantDbPath]);
                touch($tenantDbPath);
                chmod($tenantDbPath, 0644);
            } else {
                Log::info('Installer: Tenant SQLite database file already exists', ['file' => $tenantDbPath]);
            }
        }
    }

    /**
     * Ensure database file exists before migrations
     */
    private function ensureDatabaseFileExists(): void
    {
        $dbConnection = config('database.default', 'mysql');
        
        if ($dbConnection === 'sqlite') {
            // Central database
            $databasePath = config('database.connections.sqlite.database');
            Log::info('Installer: SQLite central database path', ['path' => $databasePath]);
            
            // Ensure database directory exists
            $databaseDir = dirname($databasePath);
            if (!is_dir($databaseDir)) {
                Log::info('Installer: Creating central database directory', ['dir' => $databaseDir]);
                mkdir($databaseDir, 0755, true);
            }
            
            // Create empty SQLite file if it doesn't exist
            if (!file_exists($databasePath)) {
                Log::info('Installer: Creating central SQLite database file', ['file' => $databasePath]);
                touch($databasePath);
                chmod($databasePath, 0644);
            } else {
                Log::info('Installer: Central SQLite database file already exists', ['file' => $databasePath]);
            }
            
            // Tenant database directory and file (removed - now handled in createTenantDatabaseFile)
            // $tenantDbPath = database_path('tenant_default.sqlite');
            // $tenantDbDir = dirname($tenantDbPath);
            // 
            // if (!is_dir($tenantDbDir)) {
            //     Log::info('Installer: Creating tenant database directory', ['dir' => $tenantDbDir]);
            //     mkdir($tenantDbDir, 0755, true);
            // }
            // 
            // if (!file_exists($tenantDbPath)) {
            //     Log::info('Installer: Creating tenant SQLite database file', ['file' => $tenantDbPath]);
            //     touch($tenantDbPath);
            //     chmod($tenantDbPath, 0644);
            // } else {
            //     Log::info('Installer: Tenant SQLite database file already exists', ['file' => $tenantDbPath]);
            // }
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

        // Get database connection type
        $dbConnection = config('database.default', 'mysql');

        // Critical extensions (always required)
        $criticalExtensions = ['pdo', 'mbstring', 'tokenizer', 'xml', 'ctype', 'json', 'fileinfo', 'openssl'];
        
        // Database-specific extensions
        $databaseExtensions = [];
        if ($dbConnection === 'mysql') {
            $databaseExtensions[] = 'pdo_mysql';
        } elseif ($dbConnection === 'sqlite') {
            $databaseExtensions[] = 'pdo_sqlite';
        }

        // Optional extensions (should not block installation)
        $optionalExtensions = ['bcmath'];

        // Check critical extensions
        foreach ($criticalExtensions as $extension) {
            $requirements['extension_' . $extension] = [
                'required' => 'Required',
                'current' => extension_loaded($extension) ? 'Loaded' : 'Not loaded',
                'status' => extension_loaded($extension) ? 'ok' : 'error',
            ];
        }

        // Check database-specific extensions
        foreach ($databaseExtensions as $extension) {
            $requirements['extension_' . $extension] = [
                'required' => 'Required for ' . strtoupper($dbConnection),
                'current' => extension_loaded($extension) ? 'Loaded' : 'Not loaded',
                'status' => extension_loaded($extension) ? 'ok' : 'error',
            ];
        }

        // Check optional extensions (warning only)
        foreach ($optionalExtensions as $extension) {
            $requirements['extension_' . $extension] = [
                'required' => 'Optional',
                'current' => extension_loaded($extension) ? 'Loaded' : 'Not loaded',
                'status' => extension_loaded($extension) ? 'ok' : 'warning',
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
            $dbConnection = config('database.default', 'mysql');
            Log::info('Installer: Checking database connection', ['connection' => $dbConnection]);
            
            // Auto-create SQLite database file if needed
            if ($dbConnection === 'sqlite') {
                $databasePath = config('database.connections.sqlite.database');
                Log::info('Installer: SQLite database path', ['path' => $databasePath]);
                
                // Ensure database directory exists
                $databaseDir = dirname($databasePath);
                if (!is_dir($databaseDir)) {
                    Log::info('Installer: Creating database directory', ['dir' => $databaseDir]);
                    mkdir($databaseDir, 0755, true);
                }
                
                // Create empty SQLite file if it doesn't exist
                if (!file_exists($databasePath)) {
                    Log::info('Installer: Creating SQLite database file', ['file' => $databasePath]);
                    touch($databasePath);
                    chmod($databasePath, 0644);
                } else {
                    Log::info('Installer: SQLite database file already exists', ['file' => $databasePath]);
                }
            }
            
            DB::connection()->getPdo();
            Log::info('Installer: Database connection successful');
            return [
                'required' => 'Connected',
                'current' => 'Connected',
                'status' => 'ok',
            ];
        } catch (\Exception $e) {
            Log::error('Installer: Database connection failed', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
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
