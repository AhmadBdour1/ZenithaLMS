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

        /* force fresh migration for SQLite */
        if (config('database.default') === 'sqlite') {
            Log::info('Installer: Using SQLite - running fresh migration');
            DB::statement('PRAGMA foreign_keys=OFF');
            
            // Run central migrations first
            Artisan::call('migrate:fresh', [
                '--force' => true,
                '--path' => 'database/migrations'
            ]);
            
            // Create default tenant for installation
            $this->createDefaultTenant();
            
            // Run tenant migrations for the default tenant
            Artisan::call('tenants:migrate', [
                '--force' => true,
                '--tenants' => ['default']
            ]);
            
            DB::statement('PRAGMA foreign_keys=ON');
            Log::info('Installer: SQLite fresh migration completed');
        } else {
            Log::info('Installer: Using standard migration');
            // Run central migrations first
            Artisan::call('migrate', [
                '--force' => true,
                '--path' => 'database/migrations'
            ]);
            
            // Create default tenant for installation
            $this->createDefaultTenant();
            
            // Run tenant migrations for the default tenant
            Artisan::call('tenants:migrate', [
                '--force' => true,
                '--tenants' => ['default']
            ]);
            
            Log::info('Installer: Standard migration completed');
        }

        // Immediately verify tables exist in tenant context
        $tenant = \App\Models\Central\Tenant::find('default');
        if ($tenant) {
            tenancy()->initialize($tenant);
        }
        
        if (!Schema::hasTable('users')) {
            Log::error('Installer: Users table still missing after migrations');
            throw new \Exception('Users table still missing after migrations');
        }
        
        if (!Schema::hasTable('roles')) {
            Log::error('Installer: Roles table still missing after migrations');
            throw new \Exception('Roles table still missing after migrations');
        }
        
        Log::info('Installer: Tables verified to exist after migrations');

        // Validate input (now safe to query users table)
        Log::info('Installer: Validating input data');
        $validator = Validator::make($request->all(), [
            'admin_name' => 'required|string|max:255',
            'admin_email' => 'required|email|max:255|unique:users,email',
            'admin_password' => 'required|string|min:8|confirmed',
            'site_name' => 'required|string|max:255',
        ]);

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
            // Ensure tenant context for all operations
            $tenant = \App\Models\Central\Tenant::find('default');
            if ($tenant) {
                tenancy()->initialize($tenant);
            }
            
            // Step 1: Run seeders in tenant context
            Log::info('Installer: Step 1 - Running database seeders');
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
            Log::info('Installer: Database seeders completed');

            // Step 2: Create admin user in tenant context
            Log::info('Installer: Step 2 - Creating admin user', [
                'email' => $request->admin_email,
                'name' => $request->admin_name
            ]);
            $admin = \App\Models\User::create([
                'name' => $request->admin_name,
                'email' => $request->admin_email,
                'password' => Hash::make($request->admin_password),
                'email_verified_at' => now(),
            ]);
            Log::info('Installer: Admin user created', ['user_id' => $admin->id]);

            // Step 3: Assign admin role in tenant context
            Log::info('Installer: Step 3 - Assigning admin role to user');
            $adminRole = \App\Models\Role::where('name', 'admin')->first();
            if (!$adminRole) {
                Log::error('Installer: Admin role not found after seeding');
                throw new \Exception('Admin role was not created during seeding');
            }
            $admin->roles()->attach($adminRole->id);
            Log::info('Installer: Admin role assigned successfully');

            // Step 4: Mark app installed
            Log::info('Installer: Step 4 - Marking application as installed');
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
    private function createDefaultTenant(): void
    {
        Log::info('Installer: Creating default tenant');
        
        $tenant = \App\Models\Central\Tenant::firstOrCreate([
            'id' => 'default'
        ], [
            'id' => 'default',
            'tenancy_db_name' => 'tenant_default',
            'tenancy_db_username' => config('database.connections.sqlite.username'),
            'tenancy_db_password' => config('database.connections.sqlite.password'),
        ]);
        
        Log::info('Installer: Default tenant created', ['tenant_id' => $tenant->id]);
    }

    /**
     * Ensure database file exists before migrations
     */
    private function ensureDatabaseFileExists(): void
    {
        $dbConnection = config('database.default', 'mysql');
        
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
