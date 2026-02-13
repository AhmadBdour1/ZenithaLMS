<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class NotificationAPIController extends Controller
{
    /**
     * Get user notifications.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        
        // Return mock data for now since notifications table doesn't exist
        $mockNotifications = [
            [
                'id' => 1,
                'type' => 'info',
                'title' => 'Welcome to ZenithaLMS',
                'message' => 'Your account has been created successfully',
                'data' => ['type' => 'info'],
                'read_at' => null,
                'created_at' => now()->subMinutes(5),
            ],
            [
                'id' => 2,
                'type' => 'course',
                'title' => 'New Course Available',
                'message' => 'Check out our latest courses',
                'data' => ['type' => 'course'],
                'read_at' => now(),
                'created_at' => now()->subHours(2),
            ],
        ];

        return response()->json([
            'data' => $mockNotifications,
            'pagination' => [
                'current_page' => 1,
                'last_page' => 1,
                'per_page' => 20,
                'total' => 2,
            ],
            'unread_count' => 1
        ]);
    }

    /**
     * Mark notification as read.
     */
    public function markAsRead(Request $request, $notificationId)
    {
        $user = $request->user();
        
        $notification = $user->notifications()
            ->where('id', $notificationId)
            ->firstOrFail();

        $notification->markAsRead();

        return response()->json([
            'message' => 'Notification marked as read',
            'notification' => [
                'id' => $notification->id,
                'read_at' => $notification->read_at,
            ]
        ]);
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllAsRead(Request $request)
    {
        $user = $request->user();
        
        $user->notifications()
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json([
            'message' => 'All notifications marked as read'
        ]);
    }

    /**
     * Delete a notification.
     */
    public function destroy(Request $request, $notificationId)
    {
        $user = $request->user();
        
        $notification = $user->notifications()
            ->where('id', $notificationId)
            ->firstOrFail();

        $notification->delete();

        return response()->json([
            'message' => 'Notification deleted successfully'
        ]);
    }

    /**
     * Get unread notifications count.
     */
    public function unreadCount(Request $request)
    {
        $user = $request->user();
        
        $count = $user->unreadNotifications()->count();

        return response()->json([
            'unread_count' => $count
        ]);
    }

    /**
     * Create a notification (admin only).
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'type' => 'required|string|max:100',
            'data' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = \App\Models\User::findOrFail($request->user_id);
        
        $notification = $user->notify(new \App\Notifications\CustomNotification([
            'title' => $request->title,
            'message' => $request->message,
            'type' => $request->type,
            'data' => $request->data ?? [],
        ]));

        return response()->json([
            'message' => 'Notification created successfully',
            'notification' => $notification
        ], 201);
    }

    /**
     * Get notification statistics.
     */
    public function statistics(Request $request)
    {
        $user = $request->user();
        
        $total = $user->notifications()->count();
        $unread = $user->unreadNotifications()->count();
        $read = $total - $unread;

        // Group by type
        $byType = $user->notifications()
            ->selectRaw('type, count(*) as count')
            ->groupBy('type')
            ->get()
            ->pluck('count', 'type');

        return response()->json([
            'total' => $total,
            'unread' => $unread,
            'read' => $read,
            'by_type' => $byType,
        ]);
    }
}
