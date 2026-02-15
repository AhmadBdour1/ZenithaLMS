<?php

namespace App\Console\Commands;

use App\Support\Install\InstallState;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class ZenithaInstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'zenitha:install {--demo}';

    /**
     * The console command description.
     */
    protected $description = 'Install ZenithaLMS application';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Starting ZenithaLMS Installation...');

        // Check if already installed
        if (InstallState::isInstalled()) {
            $this->error('❌ ZenithaLMS is already installed!');
            $this->info('To reinstall, remove the storage/app/installed.json file first.');
            return 1;
        }

        // Check requirements
        $this->info('📋 Checking system requirements...');
        if (!$this->checkRequirements()) {
            $this->error('❌ System requirements not met. Please fix the issues above.');
            return 1;
        }

        // Check database connection
        $this->info('🔍 Checking database connection...');
        if (!$this->checkDatabase()) {
            $this->error('❌ Database connection failed. Please check your .env configuration.');
            return 1;
        }

        // Get admin user details
        $adminDetails = $this->getAdminDetails();
        if (!$adminDetails) {
            $this->error('❌ Installation cancelled.');
            return 1;
        }

        // Run migrations
        $this->info('🗄️ Running database migrations...');
        try {
            Artisan::call('migrate', ['--force' => true]);
            $this->info('✅ Database migrations completed successfully.');
        } catch (\Exception $e) {
            $this->error('❌ Migration failed: ' . $e->getMessage());
            return 1;
        }

        // Run seeders
        $this->info('🌱 Running database seeders...');
        try {
            Artisan::call('db:seed', ['--class' => 'RoleSeeder', '--force' => true]);
            Artisan::call('db:seed', ['--class' => 'OrganizationSeeder', '--force' => true]);
            Artisan::call('db:seed', ['--class' => 'CategorySeeder', '--force' => true]);
            Artisan::call('db:seed', ['--class' => 'SkillSeeder', '--force' => true]);
            
            if ($this->option('demo')) {
                $this->info('🎭 Installing demo data...');
                Artisan::call('db:seed', ['--class' => 'CourseSeeder', '--force' => true]);
                Artisan::call('db:seed', ['--class' => 'BlogSeeder', '--force' => true]);
            }
            
            $this->info('✅ Database seeding completed successfully.');
        } catch (\Exception $e) {
            $this->error('❌ Seeding failed: ' . $e->getMessage());
            return 1;
        }

        // Create admin user
        $this->info('👤 Creating admin user...');
        try {
            $admin = $this->createAdminUser($adminDetails);
            $this->info('✅ Admin user created successfully.');
            $this->info('   Email: ' . $admin->email);
            $this->info('   Password: [hidden]');
        } catch (\Exception $e) {
            $this->error('❌ Failed to create admin user: ' . $e->getMessage());
            return 1;
        }

        // Create storage link
        $this->info('🔗 Creating storage link...');
        if (!File::exists(public_path('storage'))) {
            try {
                Artisan::call('storage:link');
                $this->info('✅ Storage link created successfully.');
            } catch (\Exception $e) {
                $this->error('❌ Failed to create storage link: ' . $e->getMessage());
                return 1;
            }
        } else {
            $this->info('✅ Storage link already exists.');
        }

        // Clear caches
        $this->info('🧹 Clearing application caches...');
        try {
            Artisan::call('config:clear');
            Artisan::call('cache:clear');
            Artisan::call('view:clear');
            $this->info('✅ Caches cleared successfully.');
        } catch (\Exception $e) {
            $this->error('❌ Failed to clear caches: ' . $e->getMessage());
            return 1;
        }

        // Mark as installed
        $this->info('📝 Marking installation as complete...');
        try {
            InstallState::markInstalled([
                'admin_user_id' => $admin->id,
                'admin_email' => $admin->email,
                'demo_data' => $this->option('demo'),
                'installation_method' => 'cli',
            ]);
            $this->info('✅ Installation marked as complete.');
        } catch (\Exception $e) {
            $this->error('❌ Failed to mark installation: ' . $e->getMessage());
            return 1;
        }

        // Show success message
        $this->info('');
        $this->info('🎉 ZenithaLMS Installation Completed Successfully!');
        $this->info('');
        $this->info('📋 Next Steps:');
        $this->info('1. Start the development server: php artisan serve');
        $this->info('2. Visit your application in the browser');
        $this->info('3. Login with the admin credentials you provided');
        $this->info('');
        
        if ($this->option('demo')) {
            $this->info('🎭 Demo data has been installed for you to explore!');
        }
        
        $this->info('📚 For production deployment, don\'t forget to:');
        $this->info('• Set APP_ENV=production in your .env file');
        $this->info('• Set up a proper queue worker: php artisan queue:work');
        $this->info('• Set up a cron job for the scheduler: * * * * * cd /path/to/your/project && php artisan schedule:run >> /dev/null 2>&1');
        $this->info('');
        
        return 0;
    }

    /**
     * Check system requirements
     */
    private function checkRequirements(): bool
    {
        $allGood = true;

        // PHP Version
        $phpVersion = PHP_VERSION;
        if (version_compare($phpVersion, '8.1', '<')) {
            $this->error("❌ PHP version {$phpVersion} is too old. Required: >= 8.1");
            $allGood = false;
        } else {
            $this->info("✅ PHP version: {$phpVersion}");
        }

        // Required extensions
        $requiredExtensions = ['pdo', 'pdo_mysql', 'mbstring', 'tokenizer', 'xml', 'ctype', 'json', 'bcmath', 'fileinfo', 'openssl'];
        
        foreach ($requiredExtensions as $extension) {
            if (!extension_loaded($extension)) {
                $this->error("❌ Required PHP extension missing: {$extension}");
                $allGood = false;
            } else {
                $this->info("✅ Extension loaded: {$extension}");
            }
        }

        // Check writable directories
        $writablePaths = [
            storage_path() => 'Storage directory',
            base_path('bootstrap/cache') => 'Bootstrap cache directory',
        ];

        foreach ($writablePaths as $path => $name) {
            if (!is_writable($path)) {
                $this->error("❌ {$name} is not writable: {$path}");
                $allGood = false;
            } else {
                $this->info("✅ {$name} is writable");
            }
        }

        return $allGood;
    }

    /**
     * Check database connection
     */
    private function checkDatabase(): bool
    {
        try {
            DB::connection()->getPdo();
            $this->info('✅ Database connection successful');
            return true;
        } catch (\Exception $e) {
            $this->error('❌ Database connection failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get admin user details from user input
     */
    private function getAdminDetails(): ?array
    {
        $this->info('');
        $this->info('👤 Create Admin User');
        $this->info('================');

        $name = $this->ask('Admin name', 'Administrator');
        $email = $this->ask('Admin email', 'admin@example.com');
        
        // Validate email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('❌ Invalid email address');
            return null;
        }

        $password = $this->secret('Admin password');
        $passwordConfirmation = $this->secret('Confirm password');

        if ($password !== $passwordConfirmation) {
            $this->error('❌ Passwords do not match');
            return null;
        }

        if (strlen($password) < 8) {
            $this->error('❌ Password must be at least 8 characters long');
            return null;
        }

        return [
            'name' => $name,
            'email' => $email,
            'password' => $password,
        ];
    }

    /**
     * Create admin user
     */
    private function createAdminUser(array $details)
    {
        $adminRole = \App\Models\Role::where('name', 'admin')->first();
        
        return \App\Models\User::create([
            'name' => $details['name'],
            'email' => $details['email'],
            'password' => Hash::make($details['password']),
            'role_id' => $adminRole->id,
            'email_verified_at' => now(),
        ]);
    }
}
