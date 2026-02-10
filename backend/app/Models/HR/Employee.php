<?php

namespace App\Models\HR;

use App\Models\Leave\LeaveBalance;
use App\Models\Leave\LeaveRequest;
use App\Models\Payroll\Payroll;
use App\Models\Payroll\EmployeeLoan;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'employee_number',
        'first_name_ar',
        'first_name_en',
        'middle_name_ar',
        'middle_name_en',
        'last_name_ar',
        'last_name_en',
        'national_id',
        'iqama_number',
        'passport_number',
        'nationality',
        'gender',
        'date_of_birth',
        'marital_status',
        'email',
        'phone',
        'emergency_contact_name',
        'emergency_contact_phone',
        'address',
        'city',
        'postal_code',
        'department_id',
        'position_id',
        'supervisor_id',
        'hire_date',
        'probation_end_date',
        'employment_status',
        'contract_type',
        'work_location',
        'is_medical_staff',
        'medical_license_number',
        'specialization',
        'bank_name',
        'bank_account_number',
        'iban',
        'gosi_number',
        'profile_photo',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'hire_date' => 'date',
        'probation_end_date' => 'date',
        'is_medical_staff' => 'boolean',
        'is_active' => 'boolean',
    ];

    // =============================================================================
    // Accessors
    // =============================================================================

    /**
     * الاسم الكامل بالعربية
     */
    public function getFullNameArAttribute(): string
    {
        return trim("{$this->first_name_ar} {$this->middle_name_ar} {$this->last_name_ar}");
    }

    /**
     * الاسم الكامل بالإنجليزية
     */
    public function getFullNameEnAttribute(): string
    {
        return trim("{$this->first_name_en} {$this->middle_name_en} {$this->last_name_en}");
    }

    /**
     * الاسم حسب اللغة الحالية
     */
    public function getNameAttribute(): string
    {
        $locale = app()->getLocale();
        return $locale === 'ar' ? $this->full_name_ar : $this->full_name_en;
    }

    /**
     * سنوات الخدمة
     */
    public function getServiceYearsAttribute(): float
    {
        if (!$this->hire_date) {
            return 0;
        }
        return $this->hire_date->diffInYears(now());
    }

    /**
     * أشهر الخدمة
     */
    public function getServiceMonthsAttribute(): int
    {
        if (!$this->hire_date) {
            return 0;
        }
        return $this->hire_date->diffInMonths(now());
    }

    /**
     * هل انتهت فترة التجربة
     */
    public function getIsProbationCompletedAttribute(): bool
    {
        if (!$this->probation_end_date) {
            return true;
        }
        return $this->probation_end_date->isPast();
    }

    // =============================================================================
    // Relationships
    // =============================================================================

    /**
     * القسم
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * المنصب الوظيفي
     */
    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    /**
     * المشرف المباشر
     */
    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'supervisor_id');
    }

    /**
     * الموظفون المرؤوسون
     */
    public function subordinates(): HasMany
    {
        return $this->hasMany(Employee::class, 'supervisor_id');
    }

    /**
     * العقد الحالي
     */
    public function currentContract(): HasOne
    {
        return $this->hasOne(Contract::class)
            ->where('is_active', true)
            ->latest('start_date');
    }

    /**
     * جميع العقود
     */
    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class);
    }

    /**
     * العهد المسلمة للموظف
     */
    public function custodies(): HasMany
    {
        return $this->hasMany(Custody::class);
    }

    /**
     * العهد النشطة (غير المسترجعة)
     */
    public function activeCustodies(): HasMany
    {
        return $this->hasMany(Custody::class)->whereNull('returned_at');
    }

    /**
     * طلبات إخلاء الطرف
     */
    public function clearanceRequests(): HasMany
    {
        return $this->hasMany(ClearanceRequest::class);
    }

    /**
     * أرصدة الإجازات
     */
    public function leaveBalances(): HasMany
    {
        return $this->hasMany(LeaveBalance::class);
    }

    /**
     * طلبات الإجازة
     */
    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    /**
     * مسيرات الرواتب
     */
    public function payrolls(): HasMany
    {
        return $this->hasMany(Payroll::class);
    }

    /**
     * السلف والقروض
     */
    public function loans(): HasMany
    {
        return $this->hasMany(EmployeeLoan::class);
    }

    /**
     * السلف النشطة
     */
    public function activeLoans(): HasMany
    {
        return $this->hasMany(EmployeeLoan::class)->where('status', 'active');
    }

    // =============================================================================
    // Scopes
    // =============================================================================

    /**
     * الموظفون النشطون
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * الكادر الطبي
     */
    public function scopeMedicalStaff($query)
    {
        return $query->where('is_medical_staff', true);
    }

    /**
     * حسب القسم
     */
    public function scopeInDepartment($query, $departmentId)
    {
        return $query->where('department_id', $departmentId);
    }

    /**
     * حسب نوع العقد
     */
    public function scopeWithContractType($query, string $contractType)
    {
        return $query->where('contract_type', $contractType);
    }

    /**
     * الموظفون في فترة التجربة
     */
    public function scopeOnProbation($query)
    {
        return $query->whereNotNull('probation_end_date')
            ->where('probation_end_date', '>', now());
    }

    // =============================================================================
    // Methods
    // =============================================================================

    /**
     * هل الموظف مؤهل للإجازة
     */
    public function isEligibleForLeave(string $leaveTypeCode): bool
    {
        // التحقق من فترة التجربة للإجازة السنوية
        if ($leaveTypeCode === 'ANNUAL' && !$this->is_probation_completed) {
            return false;
        }

        return $this->is_active;
    }

    /**
     * الحصول على رصيد نوع إجازة معين
     */
    public function getLeaveBalance(string $leaveTypeId, ?int $year = null): ?LeaveBalance
    {
        $year = $year ?? date('Y');

        return $this->leaveBalances()
            ->where('leave_type_id', $leaveTypeId)
            ->where('year', $year)
            ->first();
    }

    /**
     * هل لديه عهد غير مسترجعة
     */
    public function hasOutstandingCustodies(): bool
    {
        return $this->activeCustodies()->exists();
    }

    /**
     * إجمالي قيمة العهد غير المسترجعة
     */
    public function getOutstandingCustodiesValue(): float
    {
        return $this->activeCustodies()->sum('value');
    }
}
