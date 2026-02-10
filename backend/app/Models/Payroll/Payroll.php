<?php

namespace App\Models\Payroll;

use App\Models\HR\Employee;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payroll extends Model
{
    use HasFactory, HasUuids;

    /**
     * حالات مسير الرواتب
     */
    public const STATUS_DRAFT = 'draft';
    public const STATUS_CALCULATED = 'calculated';
    public const STATUS_REVIEWED = 'reviewed';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_PAID = 'paid';
    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_DRAFT => 'مسودة',
        self::STATUS_CALCULATED => 'تم الحساب',
        self::STATUS_REVIEWED => 'تمت المراجعة',
        self::STATUS_APPROVED => 'معتمد',
        self::STATUS_PAID => 'مدفوع',
        self::STATUS_CANCELLED => 'ملغي',
    ];

    protected $fillable = [
        'payroll_number',
        'employee_id',
        'period_year',
        'period_month',
        'pay_date',
        'status',
        // الراتب الأساسي والبدلات
        'basic_salary',
        'housing_allowance',
        'transportation_allowance',
        'other_allowances',
        'total_allowances',
        // الإضافات
        'overtime_hours',
        'overtime_rate',
        'overtime_amount',
        'incentives',
        'bonus',
        'commission',
        'other_earnings',
        'total_earnings',
        // الخصومات
        'gosi_employee',
        'gosi_employer',
        'absence_days',
        'absence_deduction',
        'late_minutes',
        'late_deduction',
        'loan_deduction',
        'advance_deduction',
        'other_deductions',
        'total_deductions',
        // الصافي
        'gross_salary',
        'net_salary',
        // معلومات البنك
        'bank_name',
        'bank_code',
        'iban',
        // WPS
        'wps_generated',
        'wps_file_path',
        'wps_generated_at',
        // الاعتماد
        'calculated_by',
        'calculated_at',
        'reviewed_by',
        'reviewed_at',
        'approved_by',
        'approved_at',
        'paid_by',
        'paid_at',
        'notes',
        'currency',
    ];

    protected $casts = [
        'pay_date' => 'date',
        'basic_salary' => 'decimal:2',
        'housing_allowance' => 'decimal:2',
        'transportation_allowance' => 'decimal:2',
        'other_allowances' => 'decimal:2',
        'total_allowances' => 'decimal:2',
        'overtime_hours' => 'decimal:2',
        'overtime_rate' => 'decimal:2',
        'overtime_amount' => 'decimal:2',
        'incentives' => 'decimal:2',
        'bonus' => 'decimal:2',
        'commission' => 'decimal:2',
        'other_earnings' => 'decimal:2',
        'total_earnings' => 'decimal:2',
        'gosi_employee' => 'decimal:2',
        'gosi_employer' => 'decimal:2',
        'absence_days' => 'decimal:2',
        'absence_deduction' => 'decimal:2',
        'late_minutes' => 'integer',
        'late_deduction' => 'decimal:2',
        'loan_deduction' => 'decimal:2',
        'advance_deduction' => 'decimal:2',
        'other_deductions' => 'decimal:2',
        'total_deductions' => 'decimal:2',
        'gross_salary' => 'decimal:2',
        'net_salary' => 'decimal:2',
        'wps_generated' => 'boolean',
        'wps_generated_at' => 'datetime',
        'calculated_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'approved_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    // =============================================================================
    // Accessors
    // =============================================================================

    public function getStatusNameAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function getPeriodNameAttribute(): string
    {
        $months = [
            1 => 'يناير', 2 => 'فبراير', 3 => 'مارس', 4 => 'أبريل',
            5 => 'مايو', 6 => 'يونيو', 7 => 'يوليو', 8 => 'أغسطس',
            9 => 'سبتمبر', 10 => 'أكتوبر', 11 => 'نوفمبر', 12 => 'ديسمبر'
        ];
        return $months[$this->period_month] . ' ' . $this->period_year;
    }

    /**
     * إجمالي تكلفة الموظف على المنشأة
     */
    public function getTotalCostAttribute(): float
    {
        return $this->gross_salary + $this->gosi_employer;
    }

    // =============================================================================
    // Relationships
    // =============================================================================

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PayrollItem::class);
    }

    public function earnings(): HasMany
    {
        return $this->hasMany(PayrollItem::class)->where('type', 'earning');
    }

    public function deductions(): HasMany
    {
        return $this->hasMany(PayrollItem::class)->where('type', 'deduction');
    }

    // =============================================================================
    // Scopes
    // =============================================================================

    public function scopeForPeriod($query, int $year, int $month)
    {
        return $query->where('period_year', $year)->where('period_month', $month);
    }

    public function scopeForEmployee($query, string $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    public function scopePaid($query)
    {
        return $query->where('status', self::STATUS_PAID);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function scopePending($query)
    {
        return $query->whereNotIn('status', [self::STATUS_PAID, self::STATUS_CANCELLED]);
    }

    // =============================================================================
    // Methods
    // =============================================================================

    /**
     * حساب إجمالي البدلات
     */
    public function calculateTotalAllowances(): float
    {
        return $this->housing_allowance +
               $this->transportation_allowance +
               $this->other_allowances;
    }

    /**
     * حساب إجمالي الإضافات
     */
    public function calculateTotalEarnings(): float
    {
        return $this->basic_salary +
               $this->calculateTotalAllowances() +
               $this->overtime_amount +
               $this->incentives +
               $this->bonus +
               $this->commission +
               $this->other_earnings;
    }

    /**
     * حساب إجمالي الخصومات
     */
    public function calculateTotalDeductions(): float
    {
        return $this->gosi_employee +
               $this->absence_deduction +
               $this->late_deduction +
               $this->loan_deduction +
               $this->advance_deduction +
               $this->other_deductions;
    }

    /**
     * حساب صافي الراتب
     */
    public function calculateNetSalary(): float
    {
        return $this->calculateTotalEarnings() - $this->calculateTotalDeductions();
    }

    /**
     * تحديث الحسابات
     */
    public function recalculate(): void
    {
        $this->total_allowances = $this->calculateTotalAllowances();
        $this->total_earnings = $this->calculateTotalEarnings();
        $this->gross_salary = $this->total_earnings;
        $this->total_deductions = $this->calculateTotalDeductions();
        $this->net_salary = $this->calculateNetSalary();
    }

    /**
     * اعتماد مسير الراتب
     */
    public function approve(string $approverId): bool
    {
        $this->status = self::STATUS_APPROVED;
        $this->approved_by = $approverId;
        $this->approved_at = now();
        return $this->save();
    }

    /**
     * تسجيل الدفع
     */
    public function markAsPaid(string $paidBy): bool
    {
        $this->status = self::STATUS_PAID;
        $this->paid_by = $paidBy;
        $this->paid_at = now();
        return $this->save();
    }
}
