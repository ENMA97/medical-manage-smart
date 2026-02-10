<?php

namespace App\Services\System;

use App\Models\System\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class AuditService
{
    /**
     * تسجيل حدث
     */
    public function log(
        string $event,
        Model $model,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?string $userId = null,
        ?string $userName = null
    ): AuditLog {
        $user = Auth::user();

        return AuditLog::create([
            'user_id' => $userId ?? $user?->id,
            'user_name' => $userName ?? $user?->name ?? 'System',
            'event' => $event,
            'auditable_type' => get_class($model),
            'auditable_id' => $model->id,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'url' => request()->fullUrl(),
        ]);
    }

    /**
     * تسجيل إنشاء
     */
    public function logCreate(Model $model, ?array $data = null): AuditLog
    {
        return $this->log(
            AuditLog::EVENT_CREATED,
            $model,
            null,
            $data ?? $model->toArray()
        );
    }

    /**
     * تسجيل تحديث
     */
    public function logUpdate(Model $model, array $oldValues, array $newValues): AuditLog
    {
        return $this->log(
            AuditLog::EVENT_UPDATED,
            $model,
            $oldValues,
            $newValues
        );
    }

    /**
     * تسجيل حذف
     */
    public function logDelete(Model $model): AuditLog
    {
        return $this->log(
            AuditLog::EVENT_DELETED,
            $model,
            $model->toArray(),
            null
        );
    }

    /**
     * تسجيل موافقة
     */
    public function logApproval(Model $model, ?array $details = null): AuditLog
    {
        return $this->log(
            AuditLog::EVENT_APPROVED,
            $model,
            null,
            $details
        );
    }

    /**
     * تسجيل رفض
     */
    public function logRejection(Model $model, ?string $reason = null): AuditLog
    {
        return $this->log(
            AuditLog::EVENT_REJECTED,
            $model,
            null,
            ['reason' => $reason]
        );
    }

    /**
     * تسجيل تسجيل دخول
     */
    public function logLogin(string $userId, string $userName): AuditLog
    {
        return AuditLog::create([
            'user_id' => $userId,
            'user_name' => $userName,
            'event' => AuditLog::EVENT_LOGIN,
            'auditable_type' => 'App\\Models\\System\\User',
            'auditable_id' => $userId,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'url' => request()->fullUrl(),
        ]);
    }

    /**
     * تسجيل تسجيل خروج
     */
    public function logLogout(?string $userId = null, ?string $userName = null): AuditLog
    {
        $user = Auth::user();

        return AuditLog::create([
            'user_id' => $userId ?? $user?->id,
            'user_name' => $userName ?? $user?->name,
            'event' => AuditLog::EVENT_LOGOUT,
            'auditable_type' => 'App\\Models\\System\\User',
            'auditable_id' => $userId ?? $user?->id,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'url' => request()->fullUrl(),
        ]);
    }

    /**
     * تسجيل تصدير
     */
    public function logExport(string $entityType, ?array $filters = null): AuditLog
    {
        $user = Auth::user();

        return AuditLog::create([
            'user_id' => $user?->id,
            'user_name' => $user?->name ?? 'System',
            'event' => AuditLog::EVENT_EXPORTED,
            'auditable_type' => $entityType,
            'auditable_id' => null,
            'new_values' => $filters,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'url' => request()->fullUrl(),
        ]);
    }

    /**
     * الحصول على سجلات كيان
     */
    public function getEntityLogs(string $entityType, string $entityId, int $limit = 50)
    {
        return AuditLog::forEntity($entityType, $entityId)
            ->with('user:id,name_ar,name_en')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * الحصول على سجلات مستخدم
     */
    public function getUserLogs(string $userId, int $limit = 50)
    {
        return AuditLog::forUser($userId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * البحث في السجلات
     */
    public function search(
        ?string $userId = null,
        ?string $event = null,
        ?string $entityType = null,
        ?string $dateFrom = null,
        ?string $dateTo = null,
        int $perPage = 20
    ) {
        return AuditLog::with('user:id,name_ar,name_en')
            ->when($userId, fn($q) => $q->where('user_id', $userId))
            ->when($event, fn($q) => $q->where('event', $event))
            ->when($entityType, fn($q) => $q->where('auditable_type', 'like', "%{$entityType}%"))
            ->when($dateFrom, fn($q) => $q->where('created_at', '>=', $dateFrom))
            ->when($dateTo, fn($q) => $q->where('created_at', '<=', $dateTo . ' 23:59:59'))
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }
}
