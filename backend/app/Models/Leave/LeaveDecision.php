<?php

namespace App\Models\Leave;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveDecision extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'leave_decisions';

    protected $fillable = [
        'decision_number',
        'leave_request_id',
        'employee_type',
        'status',
        'requires_gm_approval',
        'forwarded_to_gm',
        'forward_reason',
        'created_by',
        'admin_manager_action',
        'admin_manager_action_at',
        'admin_manager_id',
        'admin_manager_comment',
        'medical_director_action',
        'medical_director_action_at',
        'medical_director_id',
        'medical_director_comment',
        'general_manager_action',
        'general_manager_action_at',
        'general_manager_id',
        'general_manager_comment',
        'final_approved_at',
        'final_approved_by',
    ];

    protected $casts = [
        'requires_gm_approval' => 'boolean',
        'forwarded_to_gm' => 'boolean',
        'admin_manager_action_at' => 'datetime',
        'medical_director_action_at' => 'datetime',
        'general_manager_action_at' => 'datetime',
        'final_approved_at' => 'datetime',
    ];

    /**
     * أنواع الموظفين
     */
    public const EMPLOYEE_TYPES = [
        'doctor' => 'طبيب',
        'medical_staff' => 'كادر طبي',
        'administrative' => 'إداري',
        'other' => 'أخرى',
    ];

    /**
     * حالات القرار
     */
    public const STATUSES = [
        'draft' => 'مسودة',
        'pending_admin_manager' => 'بانتظار المدير الإداري',
        'pending_medical_director' => 'بانتظار المدير الطبي',
        'pending_general_manager' => 'بانتظار المدير العام',
        'approved' => 'معتمد',
        'rejected' => 'مرفوض',
    ];

    /**
     * إجراءات المدير
     */
    public const MANAGER_ACTIONS = [
        'approved' => 'معتمد',
        'rejected' => 'مرفوض',
        'forwarded_to_gm' => 'محول للمدير العام',
    ];

    /**
     * العلاقة مع طلب الإجازة
     */
    public function leaveRequest(): BelongsTo
    {
        return $this->belongsTo(LeaveRequest::class);
    }

    /**
     * العلاقة مع منشئ القرار
     */
    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * العلاقة مع المدير الإداري
     */
    public function adminManager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_manager_id');
    }

    /**
     * العلاقة مع المدير الطبي
     */
    public function medicalDirector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'medical_director_id');
    }

    /**
     * العلاقة مع المدير العام
     */
    public function generalManager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'general_manager_id');
    }

    /**
     * العلاقة مع من أعطى الموافقة النهائية
     */
    public function finalApprovedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'final_approved_by');
    }

    /**
     * توليد رقم القرار
     */
    public static function generateDecisionNumber(): string
    {
        $year = date('Y');
        $lastDecision = self::whereYear('created_at', $year)
            ->orderBy('decision_number', 'desc')
            ->first();

        if ($lastDecision) {
            $lastNumber = (int) substr($lastDecision->decision_number, -5);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return "LD-{$year}-" . str_pad($newNumber, 5, '0', STR_PAD_LEFT);
    }

    /**
     * الحصول على اسم الحالة بالعربية
     */
    public function getStatusNameAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    /**
     * الحصول على اسم نوع الموظف بالعربية
     */
    public function getEmployeeTypeNameAttribute(): string
    {
        return self::EMPLOYEE_TYPES[$this->employee_type] ?? $this->employee_type;
    }

    /**
     * التحقق من أن القرار للطبيب
     */
    public function isForDoctor(): bool
    {
        return in_array($this->employee_type, ['doctor', 'medical_staff']);
    }

    /**
     * التحقق من أن القرار للموظف الإداري
     */
    public function isForAdministrative(): bool
    {
        return $this->employee_type === 'administrative';
    }

    /**
     * التحقق من أن القرار معتمد
     */
    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    /**
     * التحقق من أن القرار مرفوض
     */
    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    /**
     * التحقق من أن القرار محول للمدير العام
     */
    public function isForwardedToGm(): bool
    {
        return $this->forwarded_to_gm;
    }

    /**
     * اعتماد المدير الإداري
     */
    public function approveByAdminManager(string $userId, string $comment = null, bool $forwardToGm = false): void
    {
        $this->admin_manager_id = $userId;
        $this->admin_manager_action_at = now();
        $this->admin_manager_comment = $comment;

        if ($forwardToGm) {
            $this->admin_manager_action = 'forwarded_to_gm';
            $this->forwarded_to_gm = true;
            $this->forward_reason = $comment;
            $this->status = 'pending_general_manager';
        } else {
            $this->admin_manager_action = 'approved';
            $this->status = 'approved';
            $this->final_approved_at = now();
            $this->final_approved_by = $userId;
        }

        $this->save();
    }

    /**
     * اعتماد المدير الطبي
     */
    public function approveByMedicalDirector(string $userId, string $comment = null, bool $forwardToGm = false): void
    {
        $this->medical_director_id = $userId;
        $this->medical_director_action_at = now();
        $this->medical_director_comment = $comment;

        if ($forwardToGm) {
            $this->medical_director_action = 'forwarded_to_gm';
            $this->forwarded_to_gm = true;
            $this->forward_reason = $comment;
            $this->status = 'pending_general_manager';
        } else {
            $this->medical_director_action = 'approved';
            $this->status = 'approved';
            $this->final_approved_at = now();
            $this->final_approved_by = $userId;
        }

        $this->save();
    }

    /**
     * اعتماد المدير العام
     */
    public function approveByGeneralManager(string $userId, string $comment = null): void
    {
        $this->general_manager_id = $userId;
        $this->general_manager_action = 'approved';
        $this->general_manager_action_at = now();
        $this->general_manager_comment = $comment;
        $this->status = 'approved';
        $this->final_approved_at = now();
        $this->final_approved_by = $userId;
        $this->save();
    }

    /**
     * رفض القرار
     */
    public function reject(string $userId, string $comment, string $role = 'admin_manager'): void
    {
        $this->status = 'rejected';

        if ($role === 'admin_manager') {
            $this->admin_manager_id = $userId;
            $this->admin_manager_action = 'rejected';
            $this->admin_manager_action_at = now();
            $this->admin_manager_comment = $comment;
        } elseif ($role === 'medical_director') {
            $this->medical_director_id = $userId;
            $this->medical_director_action = 'rejected';
            $this->medical_director_action_at = now();
            $this->medical_director_comment = $comment;
        } elseif ($role === 'general_manager') {
            $this->general_manager_id = $userId;
            $this->general_manager_action = 'rejected';
            $this->general_manager_action_at = now();
            $this->general_manager_comment = $comment;
        }

        $this->save();
    }

    /**
     * Scope للقرارات المعلقة
     */
    public function scopePending($query)
    {
        return $query->whereIn('status', [
            'pending_admin_manager',
            'pending_medical_director',
            'pending_general_manager',
        ]);
    }

    /**
     * Scope للقرارات المعتمدة
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope حسب نوع الموظف
     */
    public function scopeByEmployeeType($query, string $type)
    {
        return $query->where('employee_type', $type);
    }
}
