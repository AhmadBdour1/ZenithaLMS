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
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AdminQuickLoginController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'admin']);
    }

    /**
     * Search for entities to login as
     */
    public function searchEntities(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'entity_type' => 'required|in:users,courses,subscriptions,marketplace,orders,pagebuilder,certificates,stuff',
            'search' => 'required|string|min:2',
            'limit' => 'nullable|integer|min:1|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $search = $request->search;
        $limit = $request->limit ?? 10;
        $entityType = $request->entity_type;

        $results = [];

        switch ($entityType) {
            case 'users':
                $results = $this->searchUsers($search, $limit);
                break;
            case 'courses':
                $results = $this->searchCourses($search, $limit);
                break;
            case 'subscriptions':
                $results = $this->searchSubscriptions($search, $limit);
                break;
            case 'marketplace':
                $results = $this->searchMarketplace($search, $limit);
                break;
            case 'orders':
                $results = $this->searchOrders($search, $limit);
                break;
            case 'pagebuilder':
                $results = $this->searchPageBuilder($search, $limit);
                break;
            case 'certificates':
                $results = $this->searchCertificates($search, $limit);
                break;
            case 'stuff':
                $results = $this->searchStuff($search, $limit);
                break;
        }

        return response()->json([
            'success' => true,
            'data' => $results,
        ]);
    }

    /**
     * Quick login as entity
     */
    public function quickLogin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'entity_type' => 'required|in:users,courses,subscriptions,marketplace,orders,pagebuilder,certificates,stuff',
            'entity_id' => 'required|integer|min:1',
            'reason' => 'nullable|string|max:500',
            'duration' => 'nullable|integer|min:5|max:1440', // 5 minutes to 24 hours
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $entityType = $request->entity_type;
        $entityId = $request->entity_id;
        $reason = $request->reason ?? 'Quick login for support';
        $duration = $request->duration ?? 30; // 30 minutes default

        // Store original admin session
        $originalUser = Auth::user();
        
        if (!$originalUser) {
            return response()->json([
                'success' => false,
                'message' => 'No authenticated admin user found',
            ], 401);
        }

        // Get target entity and user
        $targetData = $this->getTargetEntity($entityType, $entityId);
        
        if (!$targetData) {
            return response()->json([
                'success' => false,
                'message' => 'Entity not found',
            ], 404);
        }

        // Check if admin has permission to login as this entity
        if (!$this->canLoginAsEntity($originalUser, $entityType, $targetData)) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient permissions to login as this entity',
            ], 403);
        }

        // Store impersonation session
        session([
            'impersonate_original_user_id' => $originalUser->id,
            'impersonate_original_user_data' => $originalUser->toArray(),
            'impersonate_entity_type' => $entityType,
            'impersonate_entity_id' => $entityId,
            'impersonate_reason' => $reason,
            'impersonate_started_at' => now(),
            'impersonate_duration' => $duration,
            'impersonate_expires_at' => now()->addMinutes($duration),
        ]);

        // Log the impersonation
        Log::info('Admin impersonation started', [
            'admin_id' => $originalUser->id,
            'admin_name' => $originalUser->name,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'reason' => $reason,
            'duration' => $duration,
            'ip' => request()->ip(),
        ]);

        // Login as target user
        Auth::login($targetData['user']);

        return response()->json([
            'success' => true,
            'message' => 'Quick login successful',
            'data' => [
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'entity_name' => $targetData['name'],
                'user_name' => $targetData['user']->name,
                'user_email' => $targetData['user']->email,
                'duration' => $duration,
                'expires_at' => now()->addMinutes($duration)->format('Y-m-d H:i:s'),
                'redirect_url' => $this->getRedirectUrl($entityType, $targetData),
            ],
        ]);
    }

    /**
     * Stop impersonation and return to admin account
     */
    public function stopImpersonation()
    {
        if (!session('impersonate_original_user_id')) {
            return response()->json([
                'success' => false,
                'message' => 'No active impersonation session',
            ], 400);
        }

        $originalUserId = session('impersonate_original_user_id');
        $originalUser = User::find($originalUserId);

        if (!$originalUser) {
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

            return response()->json([
                'success' => false,
                'message' => 'Original admin user not found',
            ], 404);
        }

        // Log the impersonation end
        Log::info('Admin impersonation ended', [
            'admin_id' => $originalUserId,
            'entity_type' => session('impersonate_entity_type'),
            'entity_id' => session('impersonate_entity_id'),
            'duration' => session('impersonate_duration'),
            'actual_duration' => now()->diffInMinutes(session('impersonate_started_at')),
            'ip' => request()->ip(),
        ]);

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

        // Login as original admin
        Auth::login($originalUser);

        return response()->json([
            'success' => true,
            'message' => 'Impersonation stopped successfully',
            'data' => [
                'admin_name' => $originalUser->name,
                'admin_email' => $originalUser->email,
                'redirect_url' => '/admin/dashboard',
            ],
        ]);
    }

    /**
     * Get current impersonation status
     */
    public function getImpersonationStatus()
    {
        if (!session('impersonate_original_user_id')) {
            return response()->json([
                'success' => true,
                'data' => [
                    'is_impersonating' => false,
                ],
            ]);
        }

        $originalUserId = session('impersonate_original_user_id');
        $originalUser = User::find($originalUserId);
        $entityType = session('impersonate_entity_type');
        $entityId = session('impersonate_entity_id');
        $expiresAt = session('impersonate_expires_at');

        // Check if impersonation has expired
        if (now()->gt($expiresAt)) {
            $this->stopImpersonation();
            
            return response()->json([
                'success' => true,
                'data' => [
                    'is_impersonating' => false,
                    'expired' => true,
                ],
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'is_impersonating' => true,
                'original_user' => $originalUser ? [
                    'id' => $originalUser->id,
                    'name' => $originalUser->name,
                    'email' => $originalUser->email,
                ] : null,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'reason' => session('impersonate_reason'),
                'started_at' => session('impersonate_started_at'),
                'duration' => session('impersonate_duration'),
                'expires_at' => $expiresAt,
                'remaining_minutes' => now()->diffInMinutes($expiresAt),
            ],
        ]);
    }

    /**
     * Extend impersonation duration
     */
    public function extendImpersonation(Request $request)
    {
        if (!session('impersonate_original_user_id')) {
            return response()->json([
                'success' => false,
                'message' => 'No active impersonation session',
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'additional_minutes' => 'required|integer|min:5|max:1440',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $additionalMinutes = $request->additional_minutes;
        $currentExpiresAt = session('impersonate_expires_at');
        $newExpiresAt = $currentExpiresAt->addMinutes($additionalMinutes);

        session(['impersonate_expires_at' => $newExpiresAt]);

        Log::info('Admin impersonation extended', [
            'admin_id' => session('impersonate_original_user_id'),
            'entity_type' => session('impersonate_entity_type'),
            'entity_id' => session('impersonate_entity_id'),
            'additional_minutes' => $additionalMinutes,
            'new_expires_at' => $newExpiresAt,
            'ip' => request()->ip(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Impersonation extended successfully',
            'data' => [
                'additional_minutes' => $additionalMinutes,
                'new_expires_at' => $newExpiresAt->format('Y-m-d H:i:s'),
                'remaining_minutes' => now()->diffInMinutes($newExpiresAt),
            ],
        ]);
    }

    /**
     * Get impersonation history
     */
    public function getImpersonationHistory()
    {
        // This would typically read from a database table
        // For now, return a sample structure
        $history = [
            [
                'id' => 1,
                'admin_name' => 'Admin User',
                'entity_type' => 'users',
                'entity_name' => 'John Doe',
                'reason' => 'Support request',
                'started_at' => now()->subHours(2),
                'ended_at' => now()->subHours(1),
                'duration_minutes' => 60,
                'ip' => '192.168.1.100',
            ],
            [
                'id' => 2,
                'admin_name' => 'Admin User',
                'entity_type' => 'courses',
                'entity_name' => 'Advanced Laravel Course',
                'reason' => 'Content review',
                'started_at' => now()->subDays(1),
                'ended_at' => now()->subDays(1)->addMinutes(30),
                'duration_minutes' => 30,
                'ip' => '192.168.1.100',
            ],
        ];

        return response()->json([
            'success' => true,
            'data' => $history,
        ]);
    }

    // Helper methods
    private function searchUsers($search, $limit)
    {
        $users = User::where(function ($query) use ($search) {
                $query->where('name', 'like', '%' . $search . '%')
                      ->orWhere('email', 'like', '%' . $search . '%')
                      ->orWhere('phone', 'like', '%' . $search . '%');
            })
            ->with(['roles'])
            ->limit($limit)
            ->get();

        return $users->map(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'status' => $user->status,
                'role' => $user->roles->first()?->name ?? 'No Role',
                'avatar' => $user->avatar,
                'created_at' => $user->created_at->format('Y-m-d H:i:s'),
                'login_url' => route('admin.quick-login.login', [
                    'entity_type' => 'users',
                    'entity_id' => $user->id,
                ]),
            ];
        })->toArray();
    }

    private function searchCourses($search, $limit)
    {
        $courses = Course::where(function ($query) use ($search) {
                $query->where('title', 'like', '%' . $search . '%')
                      ->orWhere('description', 'like', '%' . $search . '%');
            })
            ->with(['instructor'])
            ->limit($limit)
            ->get();

        return $courses->map(function ($course) {
            return [
                'id' => $course->id,
                'name' => $course->title,
                'description' => $course->description,
                'instructor' => $course->instructor?->name ?? 'N/A',
                'status' => $course->status,
                'price' => $course->price,
                'created_at' => $course->created_at->format('Y-m-d H:i:s'),
                'login_url' => route('admin.quick-login.login', [
                    'entity_type' => 'courses',
                    'entity_id' => $course->id,
                ]),
            ];
        })->toArray();
    }

    private function searchSubscriptions($search, $limit)
    {
        $subscriptions = Subscription::whereHas('user', function ($query) use ($search) {
                $query->where('name', 'like', '%' . $search . '%')
                      ->orWhere('email', 'like', '%' . $search . '%');
            })
            ->orWhereHas('plan', function ($query) use ($search) {
                $query->where('name', 'like', '%' . $search . '%');
            })
            ->with(['user', 'plan'])
            ->limit($limit)
            ->get();

        return $subscriptions->map(function ($subscription) {
            return [
                'id' => $subscription->id,
                'name' => $subscription->plan?->name ?? 'Unknown Plan',
                'user' => $subscription->user?->name ?? 'Unknown User',
                'email' => $subscription->user?->email,
                'status' => $subscription->status,
                'price' => $subscription->price,
                'created_at' => $subscription->created_at->format('Y-m-d H:i:s'),
                'login_url' => route('admin.quick-login.login', [
                    'entity_type' => 'subscriptions',
                    'entity_id' => $subscription->id,
                ]),
            ];
        })->toArray();
    }

    private function searchMarketplace($search, $limit)
    {
        $products = AuraProduct::where(function ($query) use ($search) {
                $query->where('name', 'like', '%' . $search . '%')
                      ->orWhere('description', 'like', '%' . $search . '%');
            })
            ->with(['vendor'])
            ->limit($limit)
            ->get();

        return $products->map(function ($product) {
            return [
                'id' => $product->id,
                'name' => $product->name,
                'description' => $product->description,
                'vendor' => $product->vendor?->name ?? 'N/A',
                'status' => $product->status,
                'price' => $product->price,
                'created_at' => $product->created_at->format('Y-m-d H:i:s'),
                'login_url' => route('admin.quick-login.login', [
                    'entity_type' => 'marketplace',
                    'entity_id' => $product->id,
                ]),
            ];
        })->toArray();
    }

    private function searchOrders($search, $limit)
    {
        $orders = AuraOrder::where('order_number', 'like', '%' . $search . '%')
            ->orWhereHas('user', function ($query) use ($search) {
                $query->where('name', 'like', '%' . $search . '%')
                      ->orWhere('email', 'like', '%' . $search . '%');
            })
            ->with(['user'])
            ->limit($limit)
            ->get();

        return $orders->map(function ($order) {
            return [
                'id' => $order->id,
                'name' => 'Order #' . $order->order_number,
                'user' => $order->user?->name ?? 'Unknown User',
                'email' => $order->user?->email,
                'status' => $order->status,
                'total_amount' => $order->total_amount,
                'created_at' => $order->created_at->format('Y-m-d H:i:s'),
                'login_url' => route('admin.quick-login.login', [
                    'entity_type' => 'orders',
                    'entity_id' => $order->id,
                ]),
            ];
        })->toArray();
    }

    private function searchPageBuilder($search, $limit)
    {
        $pages = AuraPage::where(function ($query) use ($search) {
                $query->where('title', 'like', '%' . $search . '%')
                      ->orWhere('slug', 'like', '%' . $search . '%');
            })
            ->with(['author'])
            ->limit($limit)
            ->get();

        return $pages->map(function ($page) {
            return [
                'id' => $page->id,
                'name' => $page->title,
                'slug' => $page->slug,
                'author' => $page->author?->name ?? 'N/A',
                'status' => $page->status,
                'view_count' => $page->view_count,
                'created_at' => $page->created_at->format('Y-m-d H:i:s'),
                'login_url' => route('admin.quick-login.login', [
                    'entity_type' => 'pagebuilder',
                    'entity_id' => $page->id,
                ]),
            ];
        })->toArray();
    }

    private function searchCertificates($search, $limit)
    {
        $certificates = Certificate::where('certificate_number', 'like', '%' . $search . '%')
            ->orWhereHas('user', function ($query) use ($search) {
                $query->where('name', 'like', '%' . $search . '%')
                      ->orWhere('email', 'like', '%' . $search . '%');
            })
            ->with(['user', 'template'])
            ->limit($limit)
            ->get();

        return $certificates->map(function ($certificate) {
            return [
                'id' => $certificate->id,
                'name' => 'Certificate #' . $certificate->certificate_number,
                'user' => $certificate->user?->name ?? 'Unknown User',
                'email' => $certificate->user?->email,
                'template' => $certificate->template?->name ?? 'N/A',
                'status' => $certificate->status,
                'issued_at' => $certificate->issued_at?->format('Y-m-d H:i:s'),
                'login_url' => route('admin.quick-login.login', [
                    'entity_type' => 'certificates',
                    'entity_id' => $certificate->id,
                ]),
            ];
        })->toArray();
    }

    private function searchStuff($search, $limit)
    {
        $stuff = Stuff::where(function ($query) use ($search) {
                $query->where('name', 'like', '%' . $search . '%')
                      ->orWhere('description', 'like', '%' . $search . '%')
                      ->orWhere('tags', 'like', '%' . $search . '%');
            })
            ->with(['vendor', 'category'])
            ->limit($limit)
            ->get();

        return $stuff->map(function ($item) {
            return [
                'id' => $item->id,
                'name' => $item->name,
                'description' => $item->description,
                'vendor' => $item->vendor?->name ?? 'N/A',
                'category' => $item->category?->name ?? 'N/A',
                'type' => $item->type,
                'price' => $item->price,
                'status' => $item->status,
                'created_at' => $item->created_at->format('Y-m-d H:i:s'),
                'login_url' => route('admin.quick-login.login', [
                    'entity_type' => 'stuff',
                    'entity_id' => $item->id,
                ]),
            ];
        })->toArray();
    }

    private function getTargetEntity($entityType, $entityId)
    {
        switch ($entityType) {
            case 'users':
                $user = User::find($entityId);
                if ($user) {
                    return [
                        'user' => $user,
                        'name' => $user->name,
                        'type' => 'user',
                    ];
                }
                break;

            case 'courses':
                $course = Course::find($entityId);
                if ($course && $course->instructor) {
                    return [
                        'user' => $course->instructor,
                        'name' => $course->title,
                        'type' => 'course',
                    ];
                }
                break;

            case 'subscriptions':
                $subscription = Subscription::find($entityId);
                if ($subscription && $subscription->user) {
                    return [
                        'user' => $subscription->user,
                        'name' => $subscription->plan?->name ?? 'Subscription',
                        'type' => 'subscription',
                    ];
                }
                break;

            case 'marketplace':
                $product = AuraProduct::find($entityId);
                if ($product && $product->vendor) {
                    return [
                        'user' => $product->vendor,
                        'name' => $product->name,
                        'type' => 'marketplace',
                    ];
                }
                break;

            case 'orders':
                $order = AuraOrder::find($entityId);
                if ($order && $order->user) {
                    return [
                        'user' => $order->user,
                        'name' => 'Order #' . $order->order_number,
                        'type' => 'order',
                    ];
                }
                break;

            case 'pagebuilder':
                $page = AuraPage::find($entityId);
                if ($page && $page->author) {
                    return [
                        'user' => $page->author,
                        'name' => $page->title,
                        'type' => 'page',
                    ];
                }
                break;

            case 'certificates':
                $certificate = Certificate::find($entityId);
                if ($certificate && $certificate->user) {
                    return [
                        'user' => $certificate->user,
                        'name' => 'Certificate #' . $certificate->certificate_number,
                        'type' => 'certificate',
                    ];
                }
                break;

            case 'stuff':
                $stuff = Stuff::find($entityId);
                if ($stuff && $stuff->vendor) {
                    return [
                        'user' => $stuff->vendor,
                        'name' => $stuff->name,
                        'type' => 'stuff',
                    ];
                }
                break;
        }

        return null;
    }

    private function canLoginAsEntity($adminUser, $entityType, $targetData)
    {
        // Super admin can login as anyone
        if ($adminUser->isSuperAdmin()) {
            return true;
        }

        // Admin can login as most entities except other admins
        if ($adminUser->isAdmin()) {
            if ($entityType === 'users' && $targetData['user']->isAdmin()) {
                return false;
            }
            return true;
        }

        // Manager can login as users, courses, subscriptions
        if ($adminUser->isManager()) {
            return in_array($entityType, ['users', 'courses', 'subscriptions']);
        }

        return false;
    }

    private function getRedirectUrl($entityType, $targetData)
    {
        switch ($entityType) {
            case 'users':
                return '/dashboard';
            case 'courses':
                return '/courses/' . $targetData['user']->courses()->first()?->id;
            case 'subscriptions':
                return '/subscriptions';
            case 'marketplace':
                return '/marketplace/vendor';
            case 'orders':
                return '/orders/' . $targetData['user']->orders()->first()?->id;
            case 'pagebuilder':
                return '/pages/' . $targetData['user']->pages()->first()?->id;
            case 'certificates':
                return '/certificates';
            case 'stuff':
                return '/stuff/vendor';
            default:
                return '/dashboard';
        }
    }
}
