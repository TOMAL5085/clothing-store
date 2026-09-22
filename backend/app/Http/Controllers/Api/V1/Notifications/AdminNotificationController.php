<?php

namespace App\Http\Controllers\Api\V1\Notifications;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AdminNotificationController extends Controller
{
    /**
     * The `notifications.data` column is stored as `text`, so PostgreSQL cannot apply
     * JSON operators to it directly. The value is cast to `json` before extracting
     * the notification category, which is always prefixed with `admin_` for the
     * notification classes addressed to the admin dashboard.
     */
    private const ADMIN_CATEGORY_PATTERN = '(data::json->>\'category\') like ?';

    private const ADMIN_CATEGORY_BINDING = 'admin\_%';

    public function index(Request $request)
    {
        Gate::authorize('viewAny', Order::class);

        $perPage = (int) $request->query('per_page', 20);
        $notifications = $request->user()->notifications()
            ->whereRaw(self::ADMIN_CATEGORY_PATTERN, [self::ADMIN_CATEGORY_BINDING])
            ->latest()
            ->paginate($perPage);

        return NotificationResource::collection($notifications);
    }

    public function unreadCount(Request $request)
    {
        Gate::authorize('viewAny', Order::class);

        $count = $request->user()->unreadNotifications()
            ->whereRaw(self::ADMIN_CATEGORY_PATTERN, [self::ADMIN_CATEGORY_BINDING])
            ->count();

        return response()->json(['unread_count' => $count]);
    }

    public function markAsRead(Request $request, $id)
    {
        Gate::authorize('viewAny', Order::class);

        $notification = $request->user()->notifications()
            ->whereRaw(self::ADMIN_CATEGORY_PATTERN, [self::ADMIN_CATEGORY_BINDING])
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

        $request->user()->unreadNotifications()
            ->whereRaw(self::ADMIN_CATEGORY_PATTERN, [self::ADMIN_CATEGORY_BINDING])
            ->get()
            ->markAsRead();

        return response()->json(['message' => 'All admin notifications marked as read']);
    }
}
