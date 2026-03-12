<?php

namespace App\Http\Controllers\Frontend;

use App\Models\User;
use Illuminate\Http\Request;

class ZenithaLmsNotificationController
{
    /**
     * Display user notifications
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $notifications = $user->notifications()
            ->orderBy('created_at', 'desc')
            ->paginate(10);
            
        return view('zenithalms.notifications.index', compact('notifications'));
    }
    
    /**
     * Mark notification as read
     */
    public function markAsRead($id)
    {
        $user = auth()->user();
        $notification = $user->notifications()
            ->where('id', $id)
            ->first();
            
        if ($notification) {
            $notification->markAsRead();
        }
        
        return back();
    }
    
    /**
     * Mark all notifications as read
     */
    public function markAllAsRead()
    {
        $user = auth()->user();
        $user->notifications()
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
            
        return back();
    }
}
