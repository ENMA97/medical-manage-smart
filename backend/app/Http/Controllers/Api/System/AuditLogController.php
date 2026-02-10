<?php

namespace App\Http\Controllers\Api\System;

use App\Http\Controllers\Controller;
use App\Models\System\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AuditLogController extends Controller
{
    /**
     * قائمة سجلات المراجعة
     */
    public function index(Request $request): JsonResponse
    {
        if (Gate::denies('system.audit')) {
            abort(403, 'غير مصرح لك بعرض سجلات المراجعة');
        }

        $query = AuditLog::with('user')
            ->when($request->user_id, fn($q, $userId) => $q->where('user_id', $userId))
            ->when($request->event, fn($q, $event) => $q->where('event', $event))
            ->when($request->auditable_type, fn($q, $type) => $q->where('auditable_type', 'like', "%{$type}%"))
            ->when($request->date_from, fn($q, $date) => $q->where('created_at', '>=', $date))
            ->when($request->date_to, fn($q, $date) => $q->where('created_at', '<=', $date . ' 23:59:59'))
            ->when($request->search, function ($q, $search) {
                $q->where(function ($query) use ($search) {
                    $query->where('user_name', 'like', "%{$search}%")
                        ->orWhere('auditable_type', 'like', "%{$search}%")
                        ->orWhere('event', 'like', "%{$search}%");
                });
            })
            ->orderBy('created_at', 'desc');

        $logs = $request->per_page
            ? $query->paginate($request->per_page)
            : $query->limit(100)->get();

        return response()->json([
            'success' => true,
            'data' => $logs,
        ]);
    }

    /**
     * سجلات مستخدم معين
     */
    public function byUser(Request $request, string $userId): JsonResponse
    {
        if (Gate::denies('system.audit')) {
            abort(403, 'غير مصرح لك بعرض سجلات المراجعة');
        }

        $query = AuditLog::with('user')
            ->where('user_id', $userId)
            ->when($request->event, fn($q, $event) => $q->where('event', $event))
            ->when($request->date_from, fn($q, $date) => $q->where('created_at', '>=', $date))
            ->when($request->date_to, fn($q, $date) => $q->where('created_at', '<=', $date . ' 23:59:59'))
            ->orderBy('created_at', 'desc');

        $logs = $request->per_page
            ? $query->paginate($request->per_page)
            : $query->limit(100)->get();

        return response()->json([
            'success' => true,
            'data' => $logs,
        ]);
    }

    /**
     * سجلات كيان معين
     */
    public function byEntity(Request $request, string $entityType, string $entityId): JsonResponse
    {
        if (Gate::denies('system.audit')) {
            abort(403, 'غير مصرح لك بعرض سجلات المراجعة');
        }

        $logs = AuditLog::with('user')
            ->where('auditable_type', 'like', "%{$entityType}%")
            ->where('auditable_id', $entityId)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $logs,
        ]);
    }

    /**
     * عرض سجل مراجعة
     */
    public function show(AuditLog $auditLog): JsonResponse
    {
        if (Gate::denies('system.audit')) {
            abort(403, 'غير مصرح لك بعرض سجلات المراجعة');
        }

        return response()->json([
            'success' => true,
            'data' => $auditLog->load('user'),
        ]);
    }
}
