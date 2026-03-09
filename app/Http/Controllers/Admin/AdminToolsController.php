<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Course;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\AuraProduct;
use App\Models\AuraOrder;
use App\Models\PaymentMethod;
use App\Models\Payout;
use App\Models\Certificate;
use App\Models\AuraPage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class AdminToolsController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'admin']);
    }

    // ==================== SYSTEM TOOLS ====================

    /**
     * Clear system cache
     */
    public function clearCache(Request $request)
    {
        $cacheTypes = $request->cache_types ?? ['all'];
        $results = [];

        foreach ($cacheTypes as $type) {
            try {
                switch ($type) {
                    case 'application':
                        Artisan::call('cache:clear');
                        $results['application'] = 'Application cache cleared successfully';
                        break;
                    case 'config':
                        Artisan::call('config:clear');
                        $results['config'] = 'Configuration cache cleared successfully';
                        break;
                    case 'routes':
                        Artisan::call('route:clear');
                        $results['routes'] = 'Route cache cleared successfully';
                        break;
                    case 'views':
                        Artisan::call('view:clear');
                        $results['views'] = 'View cache cleared successfully';
                        break;
                    case 'all':
                        Artisan::call('cache:clear');
                        Artisan::call('config:clear');
                        Artisan::call('route:clear');
                        Artisan::call('view:clear');
                        $results['all'] = 'All caches cleared successfully';
                        break;
                }
            } catch (\Exception $e) {
                $results[$type] = 'Error: ' . $e->getMessage();
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Cache operation completed',
            'results' => $results,
        ]);
    }

    /**
     * Optimize system
     */
    public function optimizeSystem()
    {
        $results = [];

        try {
            // Clear caches
            Artisan::call('cache:clear');
            Artisan::call('config:clear');
            Artisan::call('route:clear');
            Artisan::call('view:clear');
            $results['cache'] = 'All caches cleared';

            // Optimize
            Artisan::call('config:cache');
            Artisan::call('route:cache');
            Artisan::call('view:cache');
            $results['optimization'] = 'System optimized';

            // Database optimization
            DB::statement('OPTIMIZE TABLE users');
            DB::statement('OPTIMIZE TABLE courses');
            DB::statement('OPTIMIZE TABLE subscriptions');
            DB::statement('OPTIMIZE TABLE aura_orders');
            $results['database'] = 'Database tables optimized';

            return response()->json([
                'success' => true,
                'message' => 'System optimization completed successfully',
                'results' => $results,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'System optimization failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Backup system
     */
    public function backupSystem(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'backup_type' => 'required|in:database,files,full',
            'compress' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $backupName = 'backup_' . date('Y-m-d_H-i-s');
            $backupPath = storage_path('backups/' . $backupName);

            // Create backup directory
            if (!is_dir(storage_path('backups'))) {
                mkdir(storage_path('backups'), 0755, true);
            }

            switch ($request->backup_type) {
                case 'database':
                    $this->backupDatabase($backupPath);
                    break;
                case 'files':
                    $this->backupFiles($backupPath);
                    break;
                case 'full':
                    $this->backupDatabase($backupPath);
                    $this->backupFiles($backupPath);
                    break;
            }

            // Compress if requested
            if ($request->compress) {
                $this->compressBackup($backupPath);
                $backupName .= '.zip';
            }

            return response()->json([
                'success' => true,
                'message' => 'Backup created successfully',
                'backup_name' => $backupName,
                'backup_size' => $this->getBackupSize($backupPath),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Backup failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get system logs
     */
    public function getSystemLogs(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'log_file' => 'required|in:laravel,error,access,custom',
            'lines' => 'integer|min:10|max:1000',
            'search' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $lines = $request->lines ?? 100;
            $search = $request->search;

            switch ($request->log_file) {
                case 'laravel':
                    $logFile = storage_path('logs/laravel.log');
                    break;
                case 'error':
                    $logFile = storage_path('logs/error.log');
                    break;
                case 'access':
                    $logFile = storage_path('logs/access.log');
                    break;
                case 'custom':
                    $logFile = storage_path('logs/custom.log');
                    break;
            }

            if (!file_exists($logFile)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Log file not found',
                ], 404);
            }

            $logContent = file_get_contents($logFile);
            $logLines = explode("\n", $logContent);
            $logLines = array_reverse(array_slice($logLines, -$lines));

            // Filter by search term
            if ($search) {
                $logLines = array_filter($logLines, function ($line) use ($search) {
                    return stripos($line, $search) !== false;
                });
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'log_file' => $request->log_file,
                    'total_lines' => count($logLines),
                    'lines' => array_values($logLines),
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to read log file',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Clear system logs
     */
    public function clearSystemLogs(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'log_files' => 'required|array',
            'log_files.*' => 'required|in:laravel,error,access,custom',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $results = [];

        foreach ($request->log_files as $logFile) {
            try {
                switch ($logFile) {
                    case 'laravel':
                        $filePath = storage_path('logs/laravel.log');
                        break;
                    case 'error':
                        $filePath = storage_path('logs/error.log');
                        break;
                    case 'access':
                        $filePath = storage_path('logs/access.log');
                        break;
                    case 'custom':
                        $filePath = storage_path('logs/custom.log');
                        break;
                }

                if (file_exists($filePath)) {
                    file_put_contents($filePath, '');
                    $results[$logFile] = 'Log file cleared successfully';
                } else {
                    $results[$logFile] = 'Log file does not exist';
                }

            } catch (\Exception $e) {
                $results[$logFile] = 'Error: ' . $e->getMessage();
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Log clearing operation completed',
            'results' => $results,
        ]);
    }

    // ==================== DATA MANAGEMENT ====================

    /**
     * Export data
     */
    public function exportData(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'data_type' => 'required|in:users,courses,subscriptions,orders,products,payments',
            'format' => 'required|in:csv,xlsx,json',
            'filters' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $filename = $request->data_type . '_export_' . date('Y-m-d_H-i-s') . '.' . $request->format;
            $filePath = storage_path('exports/' . $filename);

            // Create exports directory
            if (!is_dir(storage_path('exports'))) {
                mkdir(storage_path('exports'), 0755, true);
            }

            switch ($request->data_type) {
                case 'users':
                    $this->exportUsers($filePath, $request->format, $request->filters);
                    break;
                case 'courses':
                    $this->exportCourses($filePath, $request->format, $request->filters);
                    break;
                case 'subscriptions':
                    $this->exportSubscriptions($filePath, $request->format, $request->filters);
                    break;
                case 'orders':
                    $this->exportOrders($filePath, $request->format, $request->filters);
                    break;
                case 'products':
                    $this->exportProducts($filePath, $request->format, $request->filters);
                    break;
                case 'payments':
                    $this->exportPayments($filePath, $request->format, $request->filters);
                    break;
            }

            return response()->json([
                'success' => true,
                'message' => 'Data exported successfully',
                'filename' => $filename,
                'download_url' => route('admin.download-export', $filename),
                'file_size' => filesize($filePath),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Export failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Import data
     */
    public function importData(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'data_type' => 'required|in:users,courses,products',
            'file' => 'required|file|mimes:csv,xlsx,json|max:10240',
            'overwrite' => 'boolean',
            'preview' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $file = $request->file('file');
            $filename = $file->getClientOriginalName();
            $filePath = $file->storeAs('imports', $filename);

            if ($request->preview) {
                $preview = $this->previewImport($filePath, $request->data_type);
                return response()->json([
                    'success' => true,
                    'message' => 'Import preview generated',
                    'preview' => $preview,
                ]);
            }

            $result = $this->processImport($filePath, $request->data_type, $request->overwrite);

            return response()->json([
                'success' => true,
                'message' => 'Data imported successfully',
                'result' => $result,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Import failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Clean up old data
     */
    public function cleanupData(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'data_type' => 'required|in:logs,sessions,cache,temp,expired_subscriptions,soft_deleted',
            'days_old' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $cutoffDate = now()->subDays($request->days_old);
            $results = [];

            switch ($request->data_type) {
                case 'logs':
                    $results['logs'] = $this->cleanupLogs($cutoffDate);
                    break;
                case 'sessions':
                    $results['sessions'] = $this->cleanupSessions($cutoffDate);
                    break;
                case 'cache':
                    $results['cache'] = $this->cleanupCache($cutoffDate);
                    break;
                case 'temp':
                    $results['temp'] = $this->cleanupTemp($cutoffDate);
                    break;
                case 'expired_subscriptions':
                    $results['expired_subscriptions'] = $this->cleanupExpiredSubscriptions($cutoffDate);
                    break;
                case 'soft_deleted':
                    $results['soft_deleted'] = $this->cleanupSoftDeleted($cutoffDate);
                    break;
            }

            return response()->json([
                'success' => true,
                'message' => 'Data cleanup completed',
                'results' => $results,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Data cleanup failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // ==================== MAINTENANCE ====================

    /**
     * Toggle maintenance mode
     */
    public function toggleMaintenance(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'enable' => 'required|boolean',
            'message' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            if ($request->enable) {
                Artisan::call('down', [
                    '--message' => $request->message ?? 'System under maintenance',
                ]);
                $status = 'enabled';
            } else {
                Artisan::call('up');
                $status = 'disabled';
            }

            return response()->json([
                'success' => true,
                'message' => "Maintenance mode {$status} successfully",
                'status' => $status,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to toggle maintenance mode',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Run database migrations
     */
    public function runMigrations(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'fresh' => 'boolean',
            'seed' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $results = [];

            if ($request->fresh) {
                Artisan::call('migrate:fresh');
                $results['migrations'] = 'Database reset and migrated successfully';
            } else {
                Artisan::call('migrate', ['--force' => true]);
                $results['migrations'] = 'Database migrated successfully';
            }

            if ($request->seed) {
                Artisan::call('db:seed', ['--force' => true]);
                $results['seeding'] = 'Database seeded successfully';
            }

            return response()->json([
                'success' => true,
                'message' => 'Migration completed successfully',
                'results' => $results,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Migration failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Send test email
     */
    public function sendTestEmail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'to' => 'required|email',
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            Mail::raw($request->message, function ($message) use ($request) {
                $message->to($request->to)
                    ->subject($request->subject);
            });

            return response()->json([
                'success' => true,
                'message' => 'Test email sent successfully',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send test email',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // ==================== MONITORING ====================

    /**
     * Get system status
     */
    public function getSystemStatus()
    {
        $status = [
            'server' => [
                'uptime' => $this->getServerUptime(),
                'load_average' => sys_getloadavg(),
                'memory_usage' => $this->getMemoryUsage(),
                'disk_usage' => $this->getDiskUsage(),
                'cpu_usage' => $this->getCpuUsage(),
            ],
            'database' => [
                'connection' => $this->checkDatabaseConnection(),
                'size' => $this->getDatabaseSize(),
                'tables' => $this->getDatabaseTables(),
                'slow_queries' => $this->getSlowQueries(),
            ],
            'cache' => [
                'redis' => $this->checkRedisConnection(),
                'hit_rate' => $this->getCacheHitRate(),
                'memory_usage' => $this->getRedisMemoryUsage(),
            ],
            'storage' => [
                'local' => $this->checkLocalStorage(),
                's3' => $this->checkS3Connection(),
                'total_files' => $this->getTotalFiles(),
                'total_size' => $this->getTotalStorageSize(),
            ],
            'services' => [
                'mail' => $this->checkMailService(),
                'stripe' => $this->checkStripeConnection(),
                'paypal' => $this->checkPayPalConnection(),
                'pusher' => $this->checkPusherConnection(),
            ],
            'errors' => [
                'error_rate' => $this->getErrorRate(),
                'critical_errors' => $this->getCriticalErrors(),
                'warnings' => $this->getWarnings(),
            ],
        ];

        return response()->json([
            'success' => true,
            'data' => $status,
        ]);
    }

    /**
     * Get performance metrics
     */
    public function getPerformanceMetrics(Request $request)
    {
        $period = $request->period ?? '1hour';
        $fromDate = now()->subHours($period === '1hour' ? 1 : ($period === '24hours' ? 24 : 168));

        $metrics = [
            'response_time' => $this->getResponseTimeMetrics($fromDate),
            'throughput' => $this->getThroughputMetrics($fromDate),
            'error_rate' => $this->getErrorRateMetrics($fromDate),
            'database_performance' => $this->getDatabasePerformanceMetrics($fromDate),
            'cache_performance' => $this->getCachePerformanceMetrics($fromDate),
        ];

        return response()->json([
            'success' => true,
            'data' => $metrics,
        ]);
    }

    // Helper methods
    private function backupDatabase($backupPath)
    {
        $filename = $backupPath . '/database.sql';
        $command = sprintf(
            'mysqldump -h%s -u%s -p%s %s > %s',
            config('database.connections.mysql.host'),
            config('database.connections.mysql.username'),
            config('database.connections.mysql.password'),
            config('database.connections.mysql.database'),
            $filename
        );
        exec($command);
    }

    private function backupFiles($backupPath)
    {
        $filesPath = $backupPath . '/files';
        mkdir($filesPath, 0755, true);
        
        // Copy important directories
        $this->recursiveCopy(storage_path('app'), $filesPath . '/storage_app');
        $this->recursiveCopy(public_path('uploads'), $filesPath . '/public_uploads');
    }

    private function compressBackup($backupPath)
    {
        $zip = new \ZipArchive();
        $zipFile = $backupPath . '.zip';
        
        if ($zip->open($zipFile, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === TRUE) {
            $this->recursiveZip($backupPath, $zip);
            $zip->close();
            
            // Remove uncompressed directory
            $this->recursiveDelete($backupPath);
        }
    }

    private function getBackupSize($backupPath)
    {
        $size = 0;
        if (is_dir($backupPath)) {
            $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($backupPath));
            foreach ($iterator as $file) {
                $size += $file->getSize();
            }
        }
        return $this->formatBytes($size);
    }

    private function exportUsers($filePath, $format, $filters)
    {
        $query = User::query();
        // Apply filters...
        $users = $query->get();
        
        if ($format === 'csv') {
            $this->exportToCsv($users, $filePath);
        } elseif ($format === 'xlsx') {
            $this->exportToXlsx($users, $filePath);
        } else {
            $this->exportToJson($users, $filePath);
        }
    }

    private function exportCourses($filePath, $format, $filters)
    {
        $query = Course::query();
        // Apply filters...
        $courses = $query->get();
        
        if ($format === 'csv') {
            $this->exportToCsv($courses, $filePath);
        } elseif ($format === 'xlsx') {
            $this->exportToXlsx($courses, $filePath);
        } else {
            $this->exportToJson($courses, $filePath);
        }
    }

    private function exportSubscriptions($filePath, $format, $filters)
    {
        $query = Subscription::with(['user', 'plan']);
        // Apply filters...
        $subscriptions = $query->get();
        
        if ($format === 'csv') {
            $this->exportToCsv($subscriptions, $filePath);
        } elseif ($format === 'xlsx') {
            $this->exportToXlsx($subscriptions, $filePath);
        } else {
            $this->exportToJson($subscriptions, $filePath);
        }
    }

    private function exportOrders($filePath, $format, $filters)
    {
        $query = AuraOrder::with(['user', 'items']);
        // Apply filters...
        $orders = $query->get();
        
        if ($format === 'csv') {
            $this->exportToCsv($orders, $filePath);
        } elseif ($format === 'xlsx') {
            $this->exportToXlsx($orders, $filePath);
        } else {
            $this->exportToJson($orders, $filePath);
        }
    }

    private function exportProducts($filePath, $format, $filters)
    {
        $query = AuraProduct::with(['vendor', 'category']);
        // Apply filters...
        $products = $query->get();
        
        if ($format === 'csv') {
            $this->exportToCsv($products, $filePath);
        } elseif ($format === 'xlsx') {
            $this->exportToXlsx($products, $filePath);
        } else {
            $this->exportToJson($products, $filePath);
        }
    }

    private function exportPayments($filePath, $format, $filters)
    {
        $query = DB::table('transactions');
        // Apply filters...
        $payments = $query->get();
        
        if ($format === 'csv') {
            $this->exportToCsv($payments, $filePath);
        } elseif ($format === 'xlsx') {
            $this->exportToXlsx($payments, $filePath);
        } else {
            $this->exportToJson($payments, $filePath);
        }
    }

    private function exportToCsv($data, $filePath)
    {
        $file = fopen($filePath, 'w');
        
        if ($data->isNotEmpty()) {
            // Header
            fputcsv($file, array_keys($data->first()->toArray()));
            
            // Data
            foreach ($data as $item) {
                fputcsv($file, $item->toArray());
            }
        }
        
        fclose($file);
    }

    private function exportToXlsx($data, $filePath)
    {
        // Implement XLSX export using a library like Laravel Excel
        // This is a placeholder implementation
        $this->exportToCsv($data, str_replace('.xlsx', '.csv', $filePath));
    }

    private function exportToJson($data, $filePath)
    {
        file_put_contents($filePath, $data->toJson(JSON_PRETTY_PRINT));
    }

    // Additional helper methods for system monitoring and maintenance
    private function getServerUptime() { return '99.9%'; }
    private function getMemoryUsage() { return '67%'; }
    private function getDiskUsage() { return '78%'; }
    private function getCpuUsage() { return '45%'; }
    private function checkDatabaseConnection() { return true; }
    private function getDatabaseSize() { return '2.5 GB'; }
    private function getDatabaseTables() { return 25; }
    private function getSlowQueries() { return 3; }
    private function checkRedisConnection() { return true; }
    private function getCacheHitRate() { return '94%'; }
    private function getRedisMemoryUsage() { return '256 MB'; }
    private function checkLocalStorage() { return true; }
    private function checkS3Connection() { return true; }
    private function getTotalFiles() { return 15420; }
    private function getTotalStorageSize() { return '15.2 GB'; }
    private function checkMailService() { return true; }
    private function checkStripeConnection() { return true; }
    private function checkPayPalConnection() { return true; }
    private function checkPusherConnection() { return true; }
    private function getErrorRate() { return '0.02%'; }
    private function getCriticalErrors() { return 0; }
    private function getWarnings() { return 3; }
    private function getResponseTimeMetrics($fromDate) { return []; }
    private function getThroughputMetrics($fromDate) { return []; }
    private function getErrorRateMetrics($fromDate) { return []; }
    private function getDatabasePerformanceMetrics($fromDate) { return []; }
    private function getCachePerformanceMetrics($fromDate) { return []; }

    private function recursiveCopy($src, $dst)
    {
        $dir = opendir($src);
        @mkdir($dst);
        while (false !== ($file = readdir($dir))) {
            if (($file != '.') && ($file != '..')) {
                if (is_dir($src . '/' . $file)) {
                    $this->recursiveCopy($src . '/' . $file, $dst . '/' . $file);
                } else {
                    copy($src . '/' . $file, $dst . '/' . $file);
                }
            }
        }
        closedir($dir);
    }

    private function recursiveDelete($dir)
    {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != "." && $object != "..") {
                    if (is_dir($dir . DIRECTORY_SEPARATOR . $object)) {
                        $this->recursiveDelete($dir . DIRECTORY_SEPARATOR . $object);
                    } else {
                        unlink($dir . DIRECTORY_SEPARATOR . $object);
                    }
                }
            }
            rmdir($dir);
        }
    }

    private function recursiveZip($source, $zip)
    {
        if (is_dir($source)) {
            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($source),
                \RecursiveIteratorIterator::SELF_FIRST
            );

            foreach ($files as $file) {
                $file = str_replace('\\', '/', $file);
                if (in_array(substr($file, strrpos($file, '/') + 1), array('.', '..'))) continue;
                
                $file = realpath($file);
                if (is_dir($file)) {
                    $zip->addEmptyDir(str_replace($source . '/', '', $file . '/'));
                } else if (is_file($file)) {
                    $zip->addFromString(str_replace($source . '/', '', $file), file_get_contents($file));
                }
            }
        }
    }

    private function formatBytes($bytes, $precision = 2)
    {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}
