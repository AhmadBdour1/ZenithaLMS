<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Course;
use App\Models\Subscription;
use App\Models\AuraProduct;
use App\Models\AuraOrder;
use App\Models\AuraPage;
use App\Models\Certificate;
use App\Models\Stuff;
use App\Models\StuffCategory;
use App\Models\StuffPurchase;
use App\Models\StuffLicense;
use App\Models\StuffReview;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Carbon\Carbon;

class AdminEntitiesController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'admin']);
    }

    /**
     * Get entities dashboard
     */
    public function dashboard()
    {
        $entities = [
            'users' => [
                'name' => 'Users',
                'icon' => 'users',
                'count' => User::count(),
                'active' => User::where('status', 'active')->count(),
                'description' => 'Manage system users and their permissions',
                'color' => 'blue',
                'route' => 'admin.entities.users.index',
            ],
            'courses' => [
                'name' => 'Courses',
                'icon' => 'academic-cap',
                'count' => Course::count(),
                'active' => Course::where('status', 'published')->count(),
                'description' => 'Manage educational courses and content',
                'color' => 'green',
                'route' => 'admin.entities.courses.index',
            ],
            'subscriptions' => [
                'name' => 'Subscriptions',
                'icon' => 'credit-card',
                'count' => Subscription::count(),
                'active' => Subscription::where('status', 'active')->count(),
                'description' => 'Manage subscription plans and billing',
                'color' => 'purple',
                'route' => 'admin.entities.subscriptions.index',
            ],
            'marketplace' => [
                'name' => 'Marketplace',
                'icon' => 'shopping-cart',
                'count' => AuraProduct::count(),
                'active' => AuraProduct::where('status', 'active')->count(),
                'description' => 'Manage marketplace products and orders',
                'color' => 'orange',
                'route' => 'admin.entities.marketplace.index',
            ],
            'pagebuilder' => [
                'name' => 'Page Builder',
                'icon' => 'layout',
                'count' => AuraPage::count(),
                'active' => AuraPage::where('status', 'published')->count(),
                'description' => 'Manage pages and templates',
                'color' => 'pink',
                'route' => 'admin.entities.pagebuilder.index',
            ],
            'certificates' => [
                'name' => 'Certificates',
                'icon' => 'award',
                'count' => Certificate::count(),
                'active' => Certificate::where('status', 'issued')->count(),
                'description' => 'Manage certificates and templates',
                'color' => 'indigo',
                'route' => 'admin.entities.certificates.index',
            ],
            'stuff' => [
                'name' => 'Stuff',
                'icon' => 'cube',
                'count' => Stuff::count(),
                'active' => Stuff::where('status', 'active')->count(),
                'description' => 'Manage digital products and resources',
                'color' => 'teal',
                'route' => 'admin.entities.stuff.index',
            ],
            'permissions' => [
                'name' => 'Permissions',
                'icon' => 'shield-check',
                'count' => Permission::count(),
                'active' => Permission::where('is_active', true)->count(),
                'description' => 'Manage roles and permissions',
                'color' => 'red',
                'route' => 'admin.entities.permissions.index',
            ],
        ];

        return response()->json([
            'success' => true,
            'data' => $entities,
        ]);
    }

    /**
     * Get entity data with filters
     */
    public function getEntityData(Request $request, $entity)
    {
        $query = $this->getEntityQuery($entity);

        // Apply filters
        if ($request->search) {
            $this->applySearchFilter($query, $entity, $request->search);
        }

        if ($request->status) {
            $this->applyStatusFilter($query, $entity, $request->status);
        }

        if ($request->date_from) {
            $this->applyDateFilter($query, $entity, $request->date_from, $request->date_to);
        }

        if ($request->sort_by) {
            $this->applySorting($query, $entity, $request->sort_by, $request->sort_order);
        }

        $data = $query->paginate($request->per_page ?? 20);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Export entity data
     */
    public function exportEntityData(Request $request, $entity)
    {
        $validator = Validator::make($request->all(), [
            'format' => 'required|in:csv,xlsx,pdf,json',
            'filters' => 'nullable|array',
            'columns' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $query = $this->getEntityQuery($entity);

        // Apply filters
        if ($request->has('filters')) {
            foreach ($request->filters as $key => $value) {
                $this->applyFilter($query, $entity, $key, $value);
            }
        }

        $data = $query->get();

        $filename = $this->generateExportFilename($entity, $request->format);
        $filepath = storage_path('exports/' . $filename);

        // Create exports directory if not exists
        if (!is_dir(storage_path('exports'))) {
            mkdir(storage_path('exports'), 0755, true);
        }

        switch ($request->format) {
            case 'csv':
                $this->exportToCsv($data, $filepath, $entity, $request->columns);
                break;
            case 'xlsx':
                $this->exportToXlsx($data, $filepath, $entity, $request->columns);
                break;
            case 'pdf':
                $this->exportToPdf($data, $filepath, $entity, $request->columns);
                break;
            case 'json':
                $this->exportToJson($data, $filepath, $entity, $request->columns);
                break;
        }

        return response()->json([
            'success' => true,
            'message' => 'Data exported successfully',
            'filename' => $filename,
            'download_url' => route('admin.entities.download', $filename),
            'file_size' => filesize($filepath),
        ]);
    }

    /**
     * Download exported file
     */
    public function downloadExport($filename)
    {
        $filepath = storage_path('exports/' . $filename);

        if (!file_exists($filepath)) {
            return response()->json([
                'success' => false,
                'message' => 'File not found',
            ], 404);
        }

        return response()->download($filepath);
    }

    /**
     * Add user to entity
     */
    public function addUserToEntity(Request $request, $entity)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'role' => 'nullable|string',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
            'notes' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = User::findOrFail($request->user_id);

        // Add role if specified
        if ($request->role) {
            $role = Role::where('slug', $request->role)->first();
            if ($role) {
                $user->assignRole($role->id);
            }
        }

        // Add permissions if specified
        if ($request->has('permissions')) {
            $user->syncPermissions($request->permissions);
        }

        // Add entity-specific permissions
        $this->addEntityPermissions($user, $entity);

        return response()->json([
            'success' => true,
            'message' => 'User added to entity successfully',
            'data' => $user->fresh(['roles', 'permissions']),
        ]);
    }

    /**
     * Get entity statistics
     */
    public function getEntityStats($entity)
    {
        $stats = [];

        switch ($entity) {
            case 'users':
                $stats = [
                    'total' => User::count(),
                    'active' => User::where('status', 'active')->count(),
                    'inactive' => User::where('status', 'inactive')->count(),
                    'suspended' => User::where('status', 'suspended')->count(),
                    'banned' => User::where('status', 'banned')->count(),
                    'today' => User::whereDate('created_at', now()->toDateString())->count(),
                    'this_week' => User::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
                    'this_month' => User::whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count(),
                    'by_role' => User::select('role', DB::raw('count(*) as count'))->groupBy('role')->get(),
                    'by_status' => User::select('status', DB::raw('count(*) as count'))->groupBy('status')->get(),
                ];
                break;

            case 'courses':
                $stats = [
                    'total' => Course::count(),
                    'published' => Course::where('status', 'published')->count(),
                    'draft' => Course::where('status', 'draft')->count(),
                    'archived' => Course::where('status', 'archived')->count(),
                    'today' => Course::whereDate('created_at', now()->toDateString())->count(),
                    'this_week' => Course::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
                    'this_month' => Course::whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count(),
                    'by_level' => Course::select('level', DB::raw('count(*) as count'))->groupBy('level')->get(),
                    'by_category' => Course::select('category', DB::raw('count(*) as count'))->groupBy('category')->get(),
                    'enrollments' => DB::table('course_user')->count(),
                ];
                break;

            case 'subscriptions':
                $stats = [
                    'total' => Subscription::count(),
                    'active' => Subscription::where('status', 'active')->count(),
                    'trialing' => Subscription::where('status', 'trialing')->count(),
                    'canceled' => Subscription::where('status', 'canceled')->count(),
                    'expired' => Subscription::where('status', 'expired')->count(),
                    'today' => Subscription::whereDate('created_at', now()->toDateString())->count(),
                    'this_week' => Subscription::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
                    'this_month' => Subscription::whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count(),
                    'revenue' => Subscription::active()->sum('price'),
                    'by_plan' => Subscription::select('plan_id', DB::raw('count(*) as count'))->groupBy('plan_id')->get(),
                    'by_cycle' => Subscription::select('billing_cycle', DB::raw('count(*) as count'))->groupBy('billing_cycle')->get(),
                ];
                break;

            case 'marketplace':
                $stats = [
                    'products' => [
                        'total' => AuraProduct::count(),
                        'active' => AuraProduct::where('status', 'active')->count(),
                        'inactive' => AuraProduct::where('status', 'inactive')->count(),
                        'draft' => AuraProduct::where('status', 'draft')->count(),
                    ],
                    'orders' => [
                        'total' => AuraOrder::count(),
                        'pending' => AuraOrder::where('status', 'pending')->count(),
                        'processing' => AuraOrder::where('status', 'processing')->count(),
                        'completed' => AuraOrder::where('status', 'completed')->count(),
                        'cancelled' => AuraOrder::where('status', 'cancelled')->count(),
                    ],
                    'revenue' => AuraOrder::where('status', 'completed')->sum('total_amount'),
                    'today' => AuraOrder::whereDate('created_at', now()->toDateString())->count(),
                    'this_week' => AuraOrder::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
                    'this_month' => AuraOrder::whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count(),
                ];
                break;

            case 'pagebuilder':
                $stats = [
                    'pages' => [
                        'total' => AuraPage::count(),
                        'published' => AuraPage::where('status', 'published')->count(),
                        'draft' => AuraPage::where('status', 'draft')->count(),
                        'archived' => AuraPage::where('status', 'archived')->count(),
                    ],
                    'templates' => [
                        'total' => AuraTemplate::count(),
                        'active' => AuraTemplate::where('is_active', true)->count(),
                        'free' => AuraTemplate::where('is_premium', false)->count(),
                        'premium' => AuraTemplate::where('is_premium', true)->count(),
                    ],
                    'views' => AuraPage::sum('view_count'),
                    'today' => AuraPage::whereDate('created_at', now()->toDateString())->count(),
                    'this_week' => AuraPage::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
                    'this_month' => AuraPage::whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count(),
                ];
                break;

            case 'certificates':
                $stats = [
                    'total' => Certificate::count(),
                    'issued' => Certificate::where('status', 'issued')->count(),
                    'pending' => Certificate::where('status', 'pending')->count(),
                    'revoked' => Certificate::where('status', 'revoked')->count(),
                    'expired' => Certificate::where('status', 'expired')->count(),
                    'today' => Certificate::whereDate('issued_at', now()->toDateString())->count(),
                    'this_week' => Certificate::whereBetween('issued_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
                    'this_month' => Certificate::whereMonth('issued_at', now()->month)->whereYear('issued_at', now()->year)->count(),
                    'templates' => CertificateTemplate::count(),
                    'verifications' => DB::table('certificate_verifications')->count(),
                ];
                break;

            case 'stuff':
                $stats = [
                    'total' => Stuff::count(),
                    'active' => Stuff::where('status', 'active')->count(),
                    'digital' => Stuff::where('type', 'digital')->count(),
                    'physical' => Stuff::where('type', 'physical')->count(),
                    'today' => Stuff::whereDate('created_at', now()->toDateString())->count(),
                    'this_week' => Stuff::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
                    'this_month' => Stuff::whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count(),
                    'purchases' => StuffPurchase::count(),
                    'revenue' => StuffPurchase::where('status', 'active')->sum('total_amount'),
                    'by_type' => Stuff::select('type', DB::raw('count(*) as count'))->groupBy('type')->get(),
                    'by_status' => Stuff::select('status', DB::raw('count(*) as count'))->groupBy('status')->get(),
                ];
                break;

            case 'permissions':
                $stats = [
                    'permissions' => [
                        'total' => Permission::count(),
                        'active' => Permission::where('is_active', true)->count(),
                        'system' => Permission::where('is_system', true)->count(),
                        'custom' => Permission::where('is_system', false)->count(),
                    ],
                    'roles' => [
                        'total' => Role::count(),
                        'active' => Role::where('is_active', true)->count(),
                        'system' => Role::where('is_system', true)->count(),
                        'custom' => Role::where('is_system', false)->count(),
                    ],
                    'users_with_permissions' => User::whereHas('permissions')->count(),
                    'users_with_roles' => User::whereHas('roles')->count(),
                ];
                break;
        }

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    // Helper methods
    private function getEntityQuery($entity)
    {
        switch ($entity) {
            case 'users':
                return User::query();
            case 'courses':
                return Course::query();
            case 'subscriptions':
                return Subscription::query();
            case 'marketplace':
                return AuraProduct::query();
            case 'pagebuilder':
                return AuraPage::query();
            case 'certificates':
                return Certificate::query();
            case 'stuff':
                return Stuff::query();
            case 'permissions':
                return Permission::query();
            default:
                throw new \Exception("Unknown entity: {$entity}");
        }
    }

    private function applySearchFilter($query, $entity, $search)
    {
        switch ($entity) {
            case 'users':
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', '%' . $search . '%')
                      ->orWhere('email', 'like', '%' . $search . '%');
                });
                break;
            case 'courses':
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', '%' . $search . '%')
                      ->orWhere('description', 'like', '%' . $search . '%');
                });
                break;
            case 'stuff':
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', '%' . $search . '%')
                      ->orWhere('description', 'like', '%' . $search . '%')
                      ->orWhere('tags', 'like', '%' . $search . '%');
                });
                break;
            // Add other entities as needed
        }
    }

    private function applyStatusFilter($query, $entity, $status)
    {
        switch ($entity) {
            case 'users':
                $query->where('status', $status);
                break;
            case 'courses':
            case 'stuff':
                $query->where('status', $status);
                break;
            case 'subscriptions':
                $query->where('status', $status);
                break;
            // Add other entities as needed
        }
    }

    private function applyDateFilter($query, $entity, $from, $to)
    {
        if ($from) {
            $query->whereDate('created_at', '>=', $from);
        }
        if ($to) {
            $query->whereDate('created_at', '<=', $to);
        }
    }

    private function applySorting($query, $entity, $sortBy, $sortOrder)
    {
        $sortOrder = $sortOrder ?? 'desc';
        
        switch ($entity) {
            case 'users':
                $query->orderBy($sortBy ?? 'created_at', $sortOrder);
                break;
            case 'courses':
                $query->orderBy($sortBy ?? 'created_at', $sortOrder);
                break;
            // Add other entities as needed
        }
    }

    private function generateExportFilename($entity, $format)
    {
        return $entity . '_export_' . date('Y-m-d_H-i-s') . '.' . $format;
    }

    private function exportToCsv($data, $filepath, $entity, $columns = null)
    {
        $file = fopen($filepath, 'w');
        
        // Header
        $headers = $this->getExportHeaders($entity, $columns);
        fputcsv($file, $headers);
        
        // Data
        foreach ($data as $item) {
            $row = $this->mapItemToRow($item, $entity, $columns);
            fputcsv($file, $row);
        }
        
        fclose($file);
    }

    private function exportToXlsx($data, $filepath, $entity, $columns = null)
    {
        // For now, export as CSV (can be enhanced with Laravel Excel)
        $this->exportToCsv($data, str_replace('.xlsx', '.csv', $filepath), $entity, $columns);
    }

    private function exportToPdf($data, $filepath, $entity, $columns = null)
    {
        // For now, create a simple text file (can be enhanced with PDF library)
        $content = $this->generateTextReport($data, $entity, $columns);
        file_put_contents($filepath, $content);
    }

    private function exportToJson($data, $filepath, $entity, $columns = null)
    {
        $exportData = [];
        
        foreach ($data as $item) {
            $exportData[] = $this->mapItemToRow($item, $entity, $columns);
        }
        
        file_put_contents($filepath, json_encode($exportData, JSON_PRETTY_PRINT));
    }

    private function getExportHeaders($entity, $columns = null)
    {
        if ($columns) {
            return $columns;
        }

        switch ($entity) {
            case 'users':
                return ['ID', 'Name', 'Email', 'Role', 'Status', 'Created At'];
            case 'courses':
                return ['ID', 'Title', 'Instructor', 'Status', 'Price', 'Created At'];
            case 'subscriptions':
                return ['ID', 'User', 'Plan', 'Status', 'Price', 'Created At'];
            case 'stuff':
                return ['ID', 'Name', 'Type', 'Price', 'Status', 'Created At'];
            default:
                return ['ID', 'Name', 'Status', 'Created At'];
        }
    }

    private function mapItemToRow($item, $entity, $columns = null)
    {
        if ($columns) {
            $row = [];
            foreach ($columns as $column) {
                $row[$column] = $this->getItemValue($item, $column, $entity);
            }
            return $row;
        }

        switch ($entity) {
            case 'users':
                return [
                    $item->id,
                    $item->name,
                    $item->email,
                    $item->role,
                    $item->status,
                    $item->created_at->format('Y-m-d H:i:s'),
                ];
            case 'courses':
                return [
                    $item->id,
                    $item->title,
                    $item->instructor_name ?? 'N/A',
                    $item->status,
                    $item->price,
                    $item->created_at->format('Y-m-d H:i:s'),
                ];
            case 'subscriptions':
                return [
                    $item->id,
                    $item->user->name ?? 'N/A',
                    $item->plan->name ?? 'N/A',
                    $item->status,
                    $item->price,
                    $item->created_at->format('Y-m-d H:i:s'),
                ];
            case 'stuff':
                return [
                    $item->id,
                    $item->name,
                    $item->type,
                    $item->price,
                    $item->status,
                    $item->created_at->format('Y-m-d H:i:s'),
                ];
            default:
                return [
                    $item->id,
                    $item->name ?? $item->title ?? 'N/A',
                    $item->status,
                    $item->created_at->format('Y-m-d H:i:s'),
                ];
        }
    }

    private function getItemValue($item, $column, $entity)
    {
        // Handle dynamic column mapping
        switch ($column) {
            case 'id':
                return $item->id;
            case 'name':
                return $item->name ?? $item->title ?? 'N/A';
            case 'email':
                return $item->email ?? 'N/A';
            case 'status':
                return $item->status;
            case 'created_at':
                return $item->created_at->format('Y-m-d H:i:s');
            default:
                return $item->{$column} ?? 'N/A';
        }
    }

    private function generateTextReport($data, $entity, $columns = null)
    {
        $content = "=== {$entity} Export Report ===\n";
        $content .= "Generated: " . now()->format('Y-m-d H:i:s') . "\n";
        $content .= "Total Records: " . $data->count() . "\n\n";

        $headers = $this->getExportHeaders($entity, $columns);
        $content .= implode("\t", $headers) . "\n";
        $content .= str_repeat("-", strlen(implode("\t", $headers))) . "\n";

        foreach ($data as $item) {
            $row = $this->mapItemToRow($item, $entity, $columns);
            $content .= implode("\t", $row) . "\n";
        }

        return $content;
    }

    private function addEntityPermissions($user, $entity)
    {
        $permissions = [];

        switch ($entity) {
            case 'users':
                $permissions = ['user.view', 'user.create', 'user.edit', 'user.delete'];
                break;
            case 'courses':
                $permissions = ['course.view', 'course.create', 'course.edit', 'course.delete'];
                break;
            case 'subscriptions':
                $permissions = ['subscription.view', 'subscription.create', 'subscription.edit', 'subscription.delete'];
                break;
            case 'marketplace':
                $permissions = ['aura_product.view', 'aura_product.create', 'aura_product.edit', 'aura_product.delete'];
                break;
            case 'pagebuilder':
                $permissions = ['aura_page.view', 'aura_page.create', 'aura_page.edit', 'aura_page.delete'];
                break;
            case 'certificates':
                $permissions = ['certificate.view', 'certificate.create', 'certificate.edit', 'certificate.delete'];
                break;
            case 'stuff':
                $permissions = ['stuff.view', 'stuff.create', 'stuff.edit', 'stuff.delete'];
                break;
        }

        foreach ($permissions as $permissionSlug) {
            $permission = Permission::where('slug', $permissionSlug)->first();
            if ($permission) {
                $user->givePermission($permission->id);
            }
        }
    }
}
