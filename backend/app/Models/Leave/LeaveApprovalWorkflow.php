<?php

namespace App\Models\Leave;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveApprovalWorkflow extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'leave_approval_workflows';

    protected $fillable = [
        'name',
        'name_ar',
        'employee_type',
        'request_approval_chain',
        'decision_approval_chain',
        'gm_approval_optional',
        'is_active',
    ];

    protected $casts = [
        'request_approval_chain' => 'array',
        'decision_approval_chain' => 'array',
        'gm_approval_optional' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * أنواع الموظفين
     */
    public const EMPLOYEE_TYPES = [
        'doctor' => 'طبيب',
        'medical_staff' => 'كادر طبي',
        'administrative' => 'إداري',
        'support' => 'خدمات مساندة',
        'management' => 'إدارة',
    ];

    /**
     * الحصول على سلسلة موافقات نموذج الطلب
     */
    public function getRequestChain(): array
    {
        return $this->request_approval_chain ?? [];
    }

    /**
     * الحصول على سلسلة موافقات قرار الإجازة
     */
    public function getDecisionChain(): array
    {
        return $this->decision_approval_chain ?? [];
    }

    /**
     * التحقق من أن موافقة المدير العام اختيارية
     */
    public function isGmApprovalOptional(): bool
    {
        return $this->gm_approval_optional;
    }

    /**
     * الحصول على سلسلة الموافقات حسب نوع الموظف
     */
    public static function getWorkflowForEmployeeType(string $employeeType): ?self
    {
        return self::where('employee_type', $employeeType)
            ->where('is_active', true)
            ->first();
    }

    /**
     * الحصول على اسم نوع الموظف بالعربية
     */
    public function getEmployeeTypeNameAttribute(): string
    {
        return self::EMPLOYEE_TYPES[$this->employee_type] ?? $this->employee_type;
    }

    /**
     * Scope للسلاسل النشطة
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope حسب نوع الموظف
     */
    public function scopeForEmployeeType($query, string $type)
    {
        return $query->where('employee_type', $type);
    }

    /**
     * إنشاء سلسلة افتراضية للموظف الإداري
     */
    public static function getDefaultAdministrativeChain(): array
    {
        return [
            'request_approval_chain' => [
                ['sequence' => 1, 'type' => 'supervisor', 'action' => 'recommendation', 'required' => true],
                ['sequence' => 2, 'type' => 'admin_manager', 'action' => 'approval', 'required' => true],
                ['sequence' => 3, 'type' => 'hr_officer', 'action' => 'endorsement', 'required' => true],
                ['sequence' => 4, 'type' => 'delegate', 'action' => 'coverage_confirmation', 'required' => true],
            ],
            'decision_approval_chain' => [
                ['sequence' => 1, 'type' => 'admin_manager', 'action' => 'approval', 'required' => true, 'can_forward_to_gm' => true],
                ['sequence' => 2, 'type' => 'general_manager', 'action' => 'approval', 'required' => false],
            ],
        ];
    }

    /**
     * إنشاء سلسلة افتراضية للطبيب
     */
    public static function getDefaultDoctorChain(): array
    {
        return [
            'request_approval_chain' => [
                ['sequence' => 1, 'type' => 'supervisor', 'action' => 'recommendation', 'required' => true],
                ['sequence' => 2, 'type' => 'admin_manager', 'action' => 'approval', 'required' => true],
                ['sequence' => 3, 'type' => 'hr_officer', 'action' => 'endorsement', 'required' => true],
                ['sequence' => 4, 'type' => 'delegate', 'action' => 'coverage_confirmation', 'required' => true],
            ],
            'decision_approval_chain' => [
                ['sequence' => 1, 'type' => 'medical_director', 'action' => 'approval', 'required' => true, 'can_forward_to_gm' => true],
                ['sequence' => 2, 'type' => 'general_manager', 'action' => 'approval', 'required' => false],
            ],
        ];
    }
}
