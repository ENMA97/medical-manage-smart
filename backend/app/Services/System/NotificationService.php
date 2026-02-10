<?php

namespace App\Services\System;

use App\Models\System\Notification;
use App\Models\System\User;
use Illuminate\Support\Collection;

class NotificationService
{
    /**
     * إرسال إشعار لمستخدم
     */
    public function send(
        string $userId,
        string $type,
        string $titleAr,
        ?string $titleEn = null,
        ?string $bodyAr = null,
        ?string $bodyEn = null,
        ?array $data = null,
        ?string $actionUrl = null
    ): Notification {
        return Notification::create([
            'user_id' => $userId,
            'type' => $type,
            'title_ar' => $titleAr,
            'title_en' => $titleEn,
            'body_ar' => $bodyAr,
            'body_en' => $bodyEn,
            'data' => $data,
            'action_url' => $actionUrl,
        ]);
    }

    /**
     * إرسال إشعار لعدة مستخدمين
     */
    public function sendToMultiple(
        array $userIds,
        string $type,
        string $titleAr,
        ?string $titleEn = null,
        ?string $bodyAr = null,
        ?string $bodyEn = null,
        ?array $data = null,
        ?string $actionUrl = null
    ): Collection {
        $notifications = collect();

        foreach ($userIds as $userId) {
            $notifications->push($this->send(
                $userId,
                $type,
                $titleAr,
                $titleEn,
                $bodyAr,
                $bodyEn,
                $data,
                $actionUrl
            ));
        }

        return $notifications;
    }

    /**
     * إرسال إشعار لمستخدمين بدور معين
     */
    public function sendToRole(
        string $roleCode,
        string $type,
        string $titleAr,
        ?string $titleEn = null,
        ?string $bodyAr = null,
        ?string $bodyEn = null,
        ?array $data = null,
        ?string $actionUrl = null
    ): Collection {
        $userIds = User::whereHas('roles', fn($q) => $q->where('code', $roleCode))
            ->where('is_active', true)
            ->pluck('id')
            ->toArray();

        return $this->sendToMultiple(
            $userIds,
            $type,
            $titleAr,
            $titleEn,
            $bodyAr,
            $bodyEn,
            $data,
            $actionUrl
        );
    }

    /**
     * إشعار طلب إجازة
     */
    public function notifyLeaveRequest(
        string $approverId,
        string $employeeName,
        string $leaveType,
        string $requestId
    ): Notification {
        return $this->send(
            $approverId,
            'leave_request',
            "طلب إجازة جديد من {$employeeName}",
            "New leave request from {$employeeName}",
            "طلب إجازة {$leaveType} بانتظار الموافقة",
            "{$leaveType} leave request pending approval",
            ['request_id' => $requestId],
            "/leaves/requests/{$requestId}"
        );
    }

    /**
     * إشعار موافقة على الإجازة
     */
    public function notifyLeaveApproved(
        string $employeeUserId,
        string $leaveType,
        string $requestId
    ): Notification {
        return $this->send(
            $employeeUserId,
            'leave_approved',
            'تمت الموافقة على طلب الإجازة',
            'Leave request approved',
            "تمت الموافقة على طلب إجازة {$leaveType}",
            "Your {$leaveType} leave request has been approved",
            ['request_id' => $requestId],
            "/leaves/requests/{$requestId}"
        );
    }

    /**
     * إشعار رفض الإجازة
     */
    public function notifyLeaveRejected(
        string $employeeUserId,
        string $leaveType,
        string $requestId,
        ?string $reason = null
    ): Notification {
        return $this->send(
            $employeeUserId,
            'leave_rejected',
            'تم رفض طلب الإجازة',
            'Leave request rejected',
            "تم رفض طلب إجازة {$leaveType}" . ($reason ? ": {$reason}" : ''),
            "Your {$leaveType} leave request has been rejected" . ($reason ? ": {$reason}" : ''),
            ['request_id' => $requestId, 'reason' => $reason],
            "/leaves/requests/{$requestId}"
        );
    }

    /**
     * إشعار انتهاء عقد
     */
    public function notifyContractExpiring(
        string $hrUserId,
        string $employeeName,
        string $contractId,
        int $daysRemaining
    ): Notification {
        return $this->send(
            $hrUserId,
            'contract_expiring',
            "عقد {$employeeName} ينتهي قريباً",
            "Contract for {$employeeName} expiring soon",
            "ينتهي العقد خلال {$daysRemaining} يوم",
            "Contract expires in {$daysRemaining} days",
            ['contract_id' => $contractId, 'days_remaining' => $daysRemaining],
            "/hr/contracts/{$contractId}"
        );
    }

    /**
     * إشعار مخزون منخفض
     */
    public function notifyLowStock(
        string $inventoryManagerId,
        string $itemName,
        string $itemId,
        float $currentQuantity,
        float $reorderLevel
    ): Notification {
        return $this->send(
            $inventoryManagerId,
            'low_stock',
            "مخزون منخفض: {$itemName}",
            "Low stock: {$itemName}",
            "الكمية الحالية {$currentQuantity} أقل من حد الطلب {$reorderLevel}",
            "Current quantity {$currentQuantity} is below reorder level {$reorderLevel}",
            [
                'item_id' => $itemId,
                'current_quantity' => $currentQuantity,
                'reorder_level' => $reorderLevel,
            ],
            "/inventory/items/{$itemId}"
        );
    }

    /**
     * تعليم جميع إشعارات المستخدم كمقروءة
     */
    public function markAllAsRead(string $userId): int
    {
        return Notification::where('user_id', $userId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    /**
     * حذف الإشعارات القديمة
     */
    public function cleanupOldNotifications(int $daysOld = 90): int
    {
        return Notification::where('created_at', '<', now()->subDays($daysOld))
            ->whereNotNull('read_at')
            ->delete();
    }

    /**
     * الحصول على عدد الإشعارات غير المقروءة
     */
    public function getUnreadCount(string $userId): int
    {
        return Notification::where('user_id', $userId)
            ->whereNull('read_at')
            ->count();
    }
}
