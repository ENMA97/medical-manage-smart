<?php

namespace App\Models\Leave;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class LeaveRequest extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'leave_requests';

    protected $fillable = [
        'request_number',
        'employee_id',
        'leave_type_id',
        'start_date',
        'end_date',
        'total_days',
        'is_half_day',
        'half_day_period',
        'reason',
        'reason_ar',
        'contact_during_leave',
        'address_during_leave',
        'delegate_employee_id',
        'job_tasks',
        'job_tasks_ar',
        'delegate_confirmed',
        'delegate_confirmed_at',
        'status',
        'actual_return_date',
        'actual_days_taken',
        'cancellation_reason',
        'cancelled_by',
        'cancelled_at',
        'attachments',
        'created_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'total_days' => 'decimal:2',
        'is_half_day' => 'boolean',
        'delegate_confirmed' => 'boolean',
        'delegate_confirmed_at' => 'datetime',
        'actual_return_date' => 'date',
        'actual_days_taken' => 'decimal:2',
        'cancelled_at' => 'datetime',
        'attachments' => 'array',
    ];

    /**
     * حالات نموذج الطلب
     */
    public const STATUSES = [
        'draft' => 'مسودة',
        'pending_supervisor' => 'بانتظار توصية المشرف',
        'pending_admin_manager' => 'بانتظار موافقة المدير الإداري',
        'pending_hr' => 'بانتظار تعميد الموارد البشرية',
        'pending_delegate' => 'بانتظار اعتماد القائم بالعمل',
        'form_completed' => 'اكتمل النموذج',
        'decision_pending' => 'قرار الإجازة قيد الاعتماد',
        'approved' => 'معتمدة',
        'rejected' => 'مرفوضة',
        'cancelled' => 'ملغاة',
        'in_progress' => 'جارية',
        'completed' => 'منتهية',
        'cut_short' => 'مقطوعة',
    ];

    /**
     * فترات نصف اليوم
     */
    public const HALF_DAY_PERIODS = [
        'morning' => 'صباحاً',
        'afternoon' => 'مساءً',
    ];

    /**
     * العلاقة مع الموظف
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * العلاقة مع نوع الإجازة
     */
    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }

    /**
     * العلاقة مع القائم بالعمل
     */
    public function delegate(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'delegate_employee_id');
    }

    /**
     * العلاقة مع من ألغى الطلب
     */
    public function cancelledByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    /**
     * العلاقة مع منشئ الطلب
     */
    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * العلاقة مع الموافقات
     */
    public function approvals(): HasMany
    {
        return $this->hasMany(LeaveApproval::class)->orderBy('sequence');
    }

    /**
     * العلاقة مع قرار الإجازة
     */
    public function decision(): HasOne
    {
        return $this->hasOne(LeaveDecision::class);
    }

    /**
     * توليد رقم الطلب
     */
    public static function generateRequestNumber(): string
    {
        $year = date('Y');
        $lastRequest = self::whereYear('created_at', $year)
            ->orderBy('request_number', 'desc')
            ->first();

        if ($lastRequest) {
            $lastNumber = (int) substr($lastRequest->request_number, -5);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return "LR-{$year}-" . str_pad($newNumber, 5, '0', STR_PAD_LEFT);
    }

    /**
     * الحصول على اسم الحالة بالعربية
     */
    public function getStatusNameAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    /**
     * التحقق من إمكانية الإلغاء
     */
    public function canBeCancelled(): bool
    {
        return in_array($this->status, [
            'draft',
            'pending_supervisor',
            'pending_admin_manager',
            'pending_hr',
            'pending_delegate',
            'form_completed',
        ]);
    }

    /**
     * التحقق من اكتمال النموذج
     */
    public function isFormCompleted(): bool
    {
        return in_array($this->status, [
            'form_completed',
            'decision_pending',
            'approved',
        ]);
    }

    /**
     * التحقق من حالة الموافقة
     */
    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    /**
     * التحقق من حالة الرفض
     */
    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    /**
     * التحقق من أن الإجازة جارية
     */
    public function isInProgress(): bool
    {
        return $this->status === 'in_progress';
    }

    /**
     * إلغاء الطلب
     */
    public function cancel(string $reason, string $userId): void
    {
        $this->status = 'cancelled';
        $this->cancellation_reason = $reason;
        $this->cancelled_by = $userId;
        $this->cancelled_at = now();
        $this->save();
    }

    /**
     * تأكيد القائم بالعمل
     */
    public function confirmDelegate(): void
    {
        $this->delegate_confirmed = true;
        $this->delegate_confirmed_at = now();
        $this->save();
    }

    /**
     * Scope للمسودات
     */
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    /**
     * Scope للطلبات المعلقة
     */
    public function scopePending($query)
    {
        return $query->whereIn('status', [
            'pending_supervisor',
            'pending_admin_manager',
            'pending_hr',
            'pending_delegate',
        ]);
    }

    /**
     * Scope للطلبات المعتمدة
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope حسب الموظف
     */
    public function scopeForEmployee($query, string $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    /**
     * Scope للفترة الزمنية
     */
    public function scopeInDateRange($query, $startDate, $endDate)
    {
        return $query->where(function ($q) use ($startDate, $endDate) {
            $q->whereBetween('start_date', [$startDate, $endDate])
              ->orWhereBetween('end_date', [$startDate, $endDate])
              ->orWhere(function ($q2) use ($startDate, $endDate) {
                  $q2->where('start_date', '<=', $startDate)
                     ->where('end_date', '>=', $endDate);
              });
        });
    }
}
