<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class AdminQuickLoginController extends Controller
{
    /**
     * Show quick login form for admin
     */
    public function showLoginForm()
    {
        // Only allow access from admin panel
        if (!str_contains(url()->previous(), 'admin')) {
            abort(403, 'Access denied');
        }

        return view('admin.auth.quick-login');
    }

    /**
     * Process quick login
     */
    public function login(Request $request)
    {
        // Only allow access from admin panel
        if (!str_contains(url()->previous(), 'admin')) {
            abort(403, 'Access denied');
        }

        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'reason' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $adminUser = Auth::user();
        
        // Verify current user is admin
        if (!$adminUser || !$adminUser->hasRole('admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied. Admin privileges required.',
            ], 403);
        }

        $targetUser = User::findOrFail($request->user_id);

        // Log the activity
        activity()
            ->causedBy($adminUser)
            ->performedOn($targetUser)
            ->withProperties([
                'reason' => $request->reason,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ])
            ->log('Admin quick login as user');

        // Login as the target user
        Auth::login($targetUser);

        // Store admin session for switching back
        session(['admin_id' => $adminUser->id, 'admin_login_time' => now()]);

        return response()->json([
            'success' => true,
            'message' => 'Successfully logged in as user',
            'data' => [
                'user' => [
                    'id' => $targetUser->id,
                    'name' => $targetUser->name,
                    'email' => $targetUser->email,
                    'role' => $targetUser->role,
                ],
                'redirect_url' => route('dashboard'),
            ],
        ]);
    }

    /**
     * Switch back to admin account
     */
    public function switchBack()
    {
        $adminId = session('admin_id');
        
        if (!$adminId) {
            return redirect()->route('login');
        }

        $adminUser = User::findOrFail($adminId);
        $currentUser = Auth::user();

        // Log the activity
        activity()
            ->causedBy($adminUser)
            ->performedOn($currentUser)
            ->withProperties([
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ])
            ->log('Admin switched back from user account');

        // Login back as admin
        Auth::login($adminUser);

        // Clear admin session
        session()->forget(['admin_id', 'admin_login_time']);

        return redirect()->route('admin.dashboard')
            ->with('success', 'Switched back to admin account successfully');
    }

    /**
     * Get users list for quick login (AJAX endpoint)
     */
    public function getUsers(Request $request)
    {
        // Only allow access from admin panel
        if (!str_contains(url()->previous(), 'admin')) {
            abort(403, 'Access denied');
        }

        $adminUser = Auth::user();
        
        if (!$adminUser || !$adminUser->hasRole('admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied',
            ], 403);
        }

        $search = $request->get('search', '');
        $role = $request->get('role', '');
        $limit = min($request->get('limit', 20), 50);

        $query = User::query();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%")
                  ->orWhere('id', 'LIKE', "%{$search}%");
            });
        }

        if ($role) {
            $query->where('role', $role);
        }

        $users = $query->orderBy('last_login_at', 'desc')
            ->take($limit)
            ->get(['id', 'name', 'email', 'role', 'last_login_at', 'created_at']);

        return response()->json([
            'success' => true,
            'data' => $users->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'last_login_at' => $user->last_login_at?->format('Y-m-d H:i'),
                    'member_since' => $user->created_at->format('Y-m-d'),
                    'avatar' => $user->avatar,
                ];
            }),
        ]);
    }

    /**
     * Check if admin session is active
     */
    public function checkSession()
    {
        $adminId = session('admin_id');
        $loginTime = session('admin_login_time');

        if (!$adminId || !$loginTime) {
            return response()->json([
                'has_admin_session' => false,
            ]);
        }

        // Check if session is older than 2 hours
        if ($loginTime->diffInMinutes(now()) > 120) {
            session()->forget(['admin_id', 'admin_login_time']);
            return response()->json([
                'has_admin_session' => false,
            ]);
        }

        $adminUser = User::find($adminId);

        return response()->json([
            'has_admin_session' => true,
            'admin_user' => $adminUser ? [
                'id' => $adminUser->id,
                'name' => $adminUser->name,
                'email' => $adminUser->email,
            ] : null,
            'session_duration' => $loginTime->diffForHumans(now()),
        ]);
    }

    /**
     * Extend admin session
     */
    public function extendSession()
    {
        $adminId = session('admin_id');

        if (!$adminId) {
            return response()->json([
                'success' => false,
                'message' => 'No admin session found',
            ], 404);
        }

        session(['admin_login_time' => now()]);

        return response()->json([
            'success' => true,
            'message' => 'Session extended',
            'new_expiry' => now()->addHours(2)->format('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Get login statistics
     */
    public function getStats()
    {
        $adminUser = Auth::user();
        
        if (!$adminUser || !$adminUser->hasRole('admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied',
            ], 403);
        }

        $stats = [
            'total_users' => User::count(),
            'users_by_role' => User::selectRaw('role, COUNT(*) as count')
                ->groupBy('role')
                ->pluck('count', 'role')
                ->toArray(),
            'recent_logins' => User::whereNotNull('last_login_at')
                ->orderBy('last_login_at', 'desc')
                ->take(10)
                ->get(['id', 'name', 'email', 'role', 'last_login_at']),
            'active_sessions' => $this->getActiveAdminSessions(),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Get active admin sessions (simplified version)
     */
    private function getActiveAdminSessions()
    {
        // This would typically be stored in cache or database
        // For now, return current session info
        $adminId = session('admin_id');
        
        if (!$adminId) {
            return 0;
        }

        return 1; // Current session
    }

    /**
     * Revoke admin session
     */
    public function revokeSession()
    {
        $adminId = session('admin_id');
        
        if (!$adminId) {
            return response()->json([
                'success' => false,
                'message' => 'No admin session to revoke',
            ], 404);
        }

        $currentUser = Auth::user();
        $adminUser = User::find($adminId);

        // Log the activity
        activity()
            ->causedBy($currentUser)
            ->performedOn($adminUser)
            ->withProperties([
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ])
            ->log('Admin session revoked');

        // Clear admin session
        session()->forget(['admin_id', 'admin_login_time']);

        return response()->json([
            'success' => true,
            'message' => 'Admin session revoked',
            'redirect_url' => route('login'),
        ]);
    }
}
