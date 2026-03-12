<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class AdminImpersonationBarController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth']);
    }

    /**
     * Get impersonation bar data
     */
    public function getImpersonationBar()
    {
        if (!session('impersonate_original_user_id')) {
            return response()->json([
                'success' => true,
                'data' => [
                    'show' => false,
                ],
            ]);
        }

        $originalUserId = session('impersonate_original_user_id');
        $originalUser = \App\Models\User::find($originalUserId);
        $entityType = session('impersonate_entity_type');
        $entityId = session('impersonate_entity_id');
        $expiresAt = session('impersonate_expires_at');

        return response()->json([
            'success' => true,
            'data' => [
                'show' => true,
                'original_user' => $originalUser ? [
                    'id' => $originalUser->id,
                    'name' => $originalUser->name,
                    'email' => $originalUser->email,
                    'avatar' => $originalUser->avatar,
                ] : null,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'reason' => session('impersonate_reason'),
                'started_at' => session('impersonate_started_at'),
                'duration' => session('impersonate_duration'),
                'expires_at' => $expiresAt,
                'remaining_minutes' => max(0, now()->diffInMinutes($expiresAt)),
                'is_expired' => now()->gt($expiresAt),
                'stop_url' => route('admin.quick-login.stop'),
                'extend_url' => route('admin.quick-login.extend'),
                'status_url' => route('admin.quick-login.status'),
            ],
        ]);
    }

    /**
     * Show impersonation notification
     */
    public function showNotification()
    {
        if (!session('impersonate_original_user_id')) {
            return response()->json([
                'success' => false,
                'message' => 'No active impersonation',
            ], 400);
        }

        $originalUserId = session('impersonate_original_user_id');
        $originalUser = \App\Models\User::find($originalUserId);
        $entityType = session('impersonate_entity_type');
        $entityId = session('impersonate_entity_id');

        return response()->json([
            'success' => true,
            'data' => [
                'title' => '🔐 Impersonation Active',
                'message' => "You are currently logged in as {$entityType} #{$entityId}. Original admin: {$originalUser->name}",
                'type' => 'warning',
                'duration' => 0, // Don't auto-hide
                'actions' => [
                    [
                        'label' => 'Return to Admin',
                        'url' => route('admin.quick-login.stop'),
                        'type' => 'primary',
                    ],
                    [
                        'label' => 'Extend Session',
                        'url' => route('admin.quick-login.extend'),
                        'type' => 'secondary',
                    ],
                ],
            ],
        ]);
    }

    /**
     * Get impersonation warning for admin routes
     */
    public function getAdminWarning()
    {
        if (!session('impersonate_original_user_id')) {
            return response()->json([
                'success' => true,
                'data' => [
                    'show' => false,
                ],
            ]);
        }

        // Check if current user is trying to access admin routes while impersonating
        $currentPath = request()->path();
        $isAdminRoute = str_starts_with($currentPath, 'admin/');

        if ($isAdminRoute) {
            return response()->json([
                'success' => true,
                'data' => [
                    'show' => true,
                    'message' => '⚠️ You are currently impersonating another user. Return to your admin account to access admin functions.',
                    'type' => 'warning',
                    'return_url' => route('admin.quick-login.stop'),
                ],
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'show' => false,
            ],
        ]);
    }

    /**
     * Get quick actions for impersonation
     */
    public function getQuickActions()
    {
        if (!session('impersonate_original_user_id')) {
            return response()->json([
                'success' => true,
                'data' => [
                    'actions' => [],
                ],
            ]);
        }

        $entityType = session('impersonate_entity_type');
        $remainingMinutes = max(0, now()->diffInMinutes(session('impersonate_expires_at')));

        $actions = [
            [
                'id' => 'stop_impersonation',
                'label' => '🔙 Return to Admin',
                'description' => 'Stop impersonating and return to your admin account',
                'url' => route('admin.quick-login.stop'),
                'type' => 'primary',
                'icon' => 'arrow-left',
            ],
            [
                'id' => 'extend_session',
                'label' => '⏰ Extend Session',
                'description' => "Extend impersonation session ({$remainingMinutes} minutes remaining)",
                'url' => route('admin.quick-login.extend'),
                'type' => 'secondary',
                'icon' => 'clock',
            ],
            [
                'id' => 'view_status',
                'label' => '📊 View Status',
                'description' => 'View current impersonation status and details',
                'url' => route('admin.quick-login.status'),
                'type' => 'info',
                'icon' => 'info-circle',
            ],
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'actions' => $actions,
            ],
        ]);
    }

    /**
     * Get impersonation stats for dashboard
     */
    public function getImpersonationStats()
    {
        if (!session('impersonate_original_user_id')) {
            return response()->json([
                'success' => true,
                'data' => [
                    'is_impersonating' => false,
                ],
            ]);
        }

        $startedAt = session('impersonate_started_at');
        $duration = session('impersonate_duration');
        $expiresAt = session('impersonate_expires_at');
        $entityType = session('impersonate_entity_type');
        $entityId = session('impersonate_entity_id');

        return response()->json([
            'success' => true,
            'data' => [
                'is_impersonating' => true,
                'stats' => [
                    'entity_type' => $entityType,
                    'entity_id' => $entityId,
                    'started_at' => $startedAt,
                    'duration_minutes' => $duration,
                    'elapsed_minutes' => now()->diffInMinutes($startedAt),
                    'remaining_minutes' => max(0, now()->diffInMinutes($expiresAt)),
                    'expires_at' => $expiresAt,
                    'progress_percentage' => max(0, min(100, (now()->diffInMinutes($startedAt) / $duration) * 100)),
                ],
            ],
        ]);
    }
}
