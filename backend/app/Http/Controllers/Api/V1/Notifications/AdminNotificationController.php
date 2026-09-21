<?php

namespace App\Http\Controllers\Api\V1\Notifications;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AdminNotificationController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('viewAny', Order::class);

        $perPage = (int) $request->query('per_page', 20);
        $notifications = $request->user()->notifications()
            ->where('data->category', 'like', 'admin_%')
            ->latest()
            ->paginate($perPage);

        return NotificationResource::collection($notifications);
    }

    public function unreadCount(Request $request)
    {
        Gate::authorize('viewAny', Order::class);

        $count = $request->user()->unreadNotifications()
            ->where('data->category', 'like', 'admin_%')
            ->count();

        return response()->json(['unread_count' => $count]);
    }

    public function markAsRead(Request $request, $id)
    {
        Gate::authorize('viewAny', Order::class);

        $notification = $request->user()->notifications()
            ->where('data->category', 'like', 'admin_%')
            ->find($id);

        if (! $notification) {
            return response()->json(['message' => 'Notification not found'], 404);
        }

        $notification->markAsRead();

        return response()->json(['message' => 'Notification marked as read']);
    }

    public function markAllAsRead(Request $request)
    {
        Gate::authorize('viewAny', Order::class);

        $request->user()->unreadNotifications
            ->where('data->category', 'like', 'admin_%')
            ->markAsRead();

        return response()->json(['message' => 'All admin notifications marked as read']);
    }
}