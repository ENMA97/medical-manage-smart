<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * GET /api/notifications
     * قائمة إشعارات المستخدم الحالي
     */
    public function index(Request $request): JsonResponse
    {
        $notifications = Notification::where('user_id', auth()->id())
            ->when($request->filled('is_read'), function ($query) use ($request) {
                if ($request->boolean('is_read')) {
                    $query->whereNotNull('read_at');
                } else {
                    $query->whereNull('read_at');
                }
            })
            ->when($request->filled('type'), fn($q) => $q->where('type', $request->input('type')))
            ->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 15));

        $unreadCount = Notification::where('user_id', auth()->id())
            ->whereNull('read_at')
            ->count();

        return response()->json([
            'success' => true,
            'message' => 'تم جلب قائمة الإشعارات بنجاح',
            'data' => [
                'notifications' => $notifications,
                'unread_count' => $unreadCount,
            ],
        ]);
    }

    /**
     * POST /api/notifications/{id}/read
     * تحديد إشعار كمقروء
     */
    public function markAsRead(string $id): JsonResponse
    {
        $notification = Notification::where('user_id', auth()->id())
            ->findOrFail($id);

        $notification->update(['read_at' => now()]);

        return response()->json([
            'success' => true,
            'message' => 'تم تحديد الإشعار كمقروء',
            'data' => $notification,
        ]);
    }

    /**
     * POST /api/notifications/read-all
     * تحديد جميع الإشعارات كمقروءة
     */
    public function markAllAsRead(): JsonResponse
    {
        $updated = Notification::where('user_id', auth()->id())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json([
            'success' => true,
            'message' => 'تم تحديد جميع الإشعارات كمقروءة',
            'data' => [
                'updated_count' => $updated,
            ],
        ]);
    }
}
