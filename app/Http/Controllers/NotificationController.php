<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Handles database notification listing and read-state management for the authenticated user.
 */
class NotificationController extends Controller
{
    /**
     * Return paginated notifications for the authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();

        $notifications = $user->notifications()
            ->latest()
            ->paginate(20);

        return response()->json([
            'notifications' => $notifications->items(),
            'unread_count' => $user->unreadNotifications()->count(),
            'has_more' => $notifications->hasMorePages(),
        ]);
    }

    /**
     * Mark a single notification as read.
     */
    public function markRead(string $id): JsonResponse
    {
        $notification = Auth::user()
            ->notifications()
            ->findOrFail($id);

        $notification->markAsRead();

        return response()->json(['success' => true]);
    }

    /**
     * Mark all notifications of the authenticated user as read.
     */
    public function markAllRead(): JsonResponse
    {
        Auth::user()->unreadNotifications()->update(['read_at' => now()]);

        return response()->json(['success' => true]);
    }
}
