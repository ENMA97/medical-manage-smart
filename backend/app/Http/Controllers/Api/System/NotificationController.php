<?php

namespace App\Http\Controllers\Api\System;

use App\Http\Controllers\Controller;
use App\Models\System\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * قائمة الإشعارات
     */
    public function index(Request $request): JsonResponse
    {
        $query = Notification::where('user_id', auth()->id())
            ->when($request->type, fn($q, $type) => $q->where('type', $type))
            ->when($request->is_read !== null, function ($q) use ($request) {
                $request->boolean('is_read')
                    ? $q->whereNotNull('read_at')
                    : $q->whereNull('read_at');
            })
            ->orderBy('created_at', 'desc');

        $notifications = $request->per_page
            ? $query->paginate($request->per_page)
            : $query->limit(50)->get();

        return response()->json([
            'success' => true,
            'data' => $notifications,
        ]);
    }

    /**
     * الإشعارات غير المقروءة
     */
    public function unread(): JsonResponse
    {
        $notifications = Notification::where('user_id', auth()->id())
            ->unread()
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $notifications,
        ]);
    }

    /**
     * عدد الإشعارات غير المقروءة
     */
    public function unreadCount(): JsonResponse
    {
        $count = Notification::where('user_id', auth()->id())
            ->unread()
            ->count();

        return response()->json([
            'success' => true,
            'data' => [
                'count' => $count,
            ],
        ]);
    }

    /**
     * تعليم إشعار كمقروء
     */
    public function markRead(Notification $notification): JsonResponse
    {
        // التأكد من ملكية الإشعار
        if ($notification->user_id !== auth()->id()) {
            abort(403, 'غير مصرح لك بهذا الإجراء');
        }

        $notification->markAsRead();

        return response()->json([
            'success' => true,
            'message' => 'تم تعليم الإشعار كمقروء',
        ]);
    }

    /**
     * تعليم جميع الإشعارات كمقروءة
     */
    public function markAllRead(): JsonResponse
    {
        Notification::where('user_id', auth()->id())
            ->unread()
            ->update(['read_at' => now()]);

        return response()->json([
            'success' => true,
            'message' => 'تم تعليم جميع الإشعارات كمقروءة',
        ]);
    }

    /**
     * حذف إشعار
     */
    public function destroy(Notification $notification): JsonResponse
    {
        // التأكد من ملكية الإشعار
        if ($notification->user_id !== auth()->id()) {
            abort(403, 'غير مصرح لك بهذا الإجراء');
        }

        $notification->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف الإشعار بنجاح',
        ]);
    }
}
