<?php

namespace App\Models\Leave;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveApproval extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'leave_approvals';

    protected $fillable = [
        'leave_request_id',
        'sequence',
        'approver_type',
        'action_type',
        'approver_id',
        'status',
        'comment',
        'comment_ar',
        'job_tasks_assigned',
        'action_at',
        'ip_address',
    ];

    protected $casts = [
        'sequence' => 'integer',
        'action_at' => 'datetime',
    ];

    /**
     * أنواع المعتمدين
     */
    public const APPROVER_TYPES = [
        'supervisor' => 'المشرف المباشر',
        'admin_manager' => 'المدير الإداري',
        'hr_officer' => 'موظف الموارد البشرية',
        'delegate' => 'القائم بالعمل',
        'department_head' => 'رئيس القسم',
        'hr_manager' => 'مدير الموارد البشرية',
    ];

    /**
     * أنواع الإجراءات
     */
    public const ACTION_TYPES = [
        'recommendation' => 'توصية',
        'approval' => 'موافقة',
        'endorsement' => 'تعميد',
        'coverage_confirmation' => 'اعتماد التغطية',
    ];

    /**
     * حالات الموافقة
     */
    public const STATUSES = [
        'pending' => 'بانتظار',
        'recommended' => 'موصى به',
        'approved' => 'موافق عليه',
        'endorsed' => 'معتمد',
        'confirmed' => 'مؤكد',
        'rejected' => 'مرفوض',
        'skipped' => 'تم تجاوزه',
    ];

    /**
     * العلاقة مع طلب الإجازة
     */
    public function leaveRequest(): BelongsTo
    {
        return $this->belongsTo(LeaveRequest::class);
    }

    /**
     * العلاقة مع المعتمد
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    /**
     * الحصول على اسم نوع المعتمد بالعربية
     */
    public function getApproverTypeNameAttribute(): string
    {
        return self::APPROVER_TYPES[$this->approver_type] ?? $this->approver_type;
    }

    /**
     * الحصول على اسم نوع الإجراء بالعربية
     */
    public function getActionTypeNameAttribute(): string
    {
        return self::ACTION_TYPES[$this->action_type] ?? $this->action_type;
    }

    /**
     * الحصول على اسم الحالة بالعربية
     */
    public function getStatusNameAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    /**
     * التحقق من أن الموافقة معلقة
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * التحقق من أن الموافقة تمت
     */
    public function isCompleted(): bool
    {
        return in_array($this->status, ['recommended', 'approved', 'endorsed', 'confirmed']);
    }

    /**
     * التحقق من الرفض
     */
    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    /**
     * تنفيذ التوصية
     */
    public function recommend(string $comment = null, string $jobTasks = null, ?string $ipAddress = null): void
    {
        $this->status = 'recommended';
        $this->comment = $comment;
        $this->job_tasks_assigned = $jobTasks;
        $this->action_at = now();
        $this->ip_address = $ipAddress;
        $this->save();
    }

    /**
     * تنفيذ الموافقة
     */
    public function approve(string $comment = null, ?string $ipAddress = null): void
    {
        $this->status = 'approved';
        $this->comment = $comment;
        $this->action_at = now();
        $this->ip_address = $ipAddress;
        $this->save();
    }

    /**
     * تنفيذ التعميد
     */
    public function endorse(string $comment = null, ?string $ipAddress = null): void
    {
        $this->status = 'endorsed';
        $this->comment = $comment;
        $this->action_at = now();
        $this->ip_address = $ipAddress;
        $this->save();
    }

    /**
     * تأكيد التغطية
     */
    public function confirmCoverage(string $comment = null, ?string $ipAddress = null): void
    {
        $this->status = 'confirmed';
        $this->comment = $comment;
        $this->action_at = now();
        $this->ip_address = $ipAddress;
        $this->save();
    }

    /**
     * تنفيذ الرفض
     */
    public function reject(string $comment, ?string $ipAddress = null): void
    {
        $this->status = 'rejected';
        $this->comment = $comment;
        $this->action_at = now();
        $this->ip_address = $ipAddress;
        $this->save();
    }

    /**
     * Scope للموافقات المعلقة
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope حسب المعتمد
     */
    public function scopeForApprover($query, string $approverId)
    {
        return $query->where('approver_id', $approverId);
    }

    /**
     * Scope حسب نوع المعتمد
     */
    public function scopeByApproverType($query, string $type)
    {
        return $query->where('approver_type', $type);
    }
}
