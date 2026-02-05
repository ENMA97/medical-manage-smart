<?php

namespace App\Models\HR;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Contract extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    /**
     * أنواع العقود
     */
    public const TYPE_FULL_TIME = 'full_time';
    public const TYPE_PART_TIME = 'part_time';
    public const TYPE_TAMHEER = 'tamheer';
    public const TYPE_PERCENTAGE = 'percentage';
    public const TYPE_LOCUM = 'locum';

    public const TYPES = [
        self::TYPE_FULL_TIME => 'دوام كامل',
        self::TYPE_PART_TIME => 'دوام جزئي',
        self::TYPE_TAMHEER => 'تمهير',
        self::TYPE_PERCENTAGE => 'بالنسبة',
        self::TYPE_LOCUM => 'طبيب زائر',
    ];

    protected $fillable = [
        'contract_number',
        'employee_id',
        'contract_type',
        'start_date',
        'end_date',
        'probation_months',
        'basic_salary',
        'housing_allowance',
        'transportation_allowance',
        'other_allowances',
        'total_salary',
        'currency',
        'payment_frequency',
        'working_hours_per_week',
        'vacation_days',
        'notice_period_days',
        'percentage_rate',
        'is_renewable',
        'renewal_terms',
        'terms_and_conditions',
        'signed_date',
        'signed_by_employee',
        'signed_by_company',
        'attachment_path',
        'is_active',
        'termination_date',
        'termination_reason',
        'notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'signed_date' => 'date',
        'termination_date' => 'date',
        'basic_salary' => 'decimal:2',
        'housing_allowance' => 'decimal:2',
        'transportation_allowance' => 'decimal:2',
        'other_allowances' => 'decimal:2',
        'total_salary' => 'decimal:2',
        'percentage_rate' => 'decimal:2',
        'is_renewable' => 'boolean',
        'signed_by_employee' => 'boolean',
        'signed_by_company' => 'boolean',
        'is_active' => 'boolean',
        'renewal_terms' => 'array',
    ];

    // =============================================================================
    // Accessors
    // =============================================================================

    /**
     * اسم نوع العقد
     */
    public function getContractTypeNameAttribute(): string
    {
        return self::TYPES[$this->contract_type] ?? $this->contract_type;
    }

    /**
     * مدة العقد بالأشهر
     */
    public function getDurationMonthsAttribute(): ?int
    {
        if (!$this->start_date || !$this->end_date) {
            return null;
        }

        return $this->start_date->diffInMonths($this->end_date);
    }

    /**
     * الأيام المتبقية للعقد
     */
    public function getRemainingDaysAttribute(): ?int
    {
        if (!$this->end_date || $this->end_date->isPast()) {
            return 0;
        }

        return now()->diffInDays($this->end_date);
    }

    /**
     * هل العقد منتهي
     */
    public function getIsExpiredAttribute(): bool
    {
        return $this->end_date && $this->end_date->isPast();
    }

    /**
     * هل العقد قريب من الانتهاء (خلال 30 يوم)
     */
    public function getIsExpiringAttribute(): bool
    {
        if (!$this->end_date) {
            return false;
        }

        return $this->remaining_days <= 30 && $this->remaining_days > 0;
    }

    /**
     * إجمالي الراتب المحسوب
     */
    public function getCalculatedTotalSalaryAttribute(): float
    {
        return $this->basic_salary +
               $this->housing_allowance +
               $this->transportation_allowance +
               $this->other_allowances;
    }

    // =============================================================================
    // Relationships
    // =============================================================================

    /**
     * الموظف
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    // =============================================================================
    // Scopes
    // =============================================================================

    /**
     * العقود النشطة
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * العقود المنتهية
     */
    public function scopeExpired($query)
    {
        return $query->where('end_date', '<', now());
    }

    /**
     * العقود القريبة من الانتهاء
     */
    public function scopeExpiringSoon($query, int $days = 30)
    {
        return $query->where('end_date', '>', now())
            ->where('end_date', '<=', now()->addDays($days));
    }

    /**
     * حسب نوع العقد
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('contract_type', $type);
    }

    // =============================================================================
    // Methods
    // =============================================================================

    /**
     * إنهاء العقد
     */
    public function terminate(string $reason, ?\DateTime $date = null): bool
    {
        $this->termination_date = $date ?? now();
        $this->termination_reason = $reason;
        $this->is_active = false;

        return $this->save();
    }

    /**
     * تجديد العقد
     */
    public function renew(\DateTime $newEndDate, ?array $newTerms = null): Contract
    {
        // إنهاء العقد الحالي
        $this->is_active = false;
        $this->save();

        // إنشاء عقد جديد
        $newContract = $this->replicate();
        $newContract->start_date = $this->end_date->addDay();
        $newContract->end_date = $newEndDate;
        $newContract->is_active = true;
        $newContract->signed_by_employee = false;
        $newContract->signed_by_company = false;
        $newContract->signed_date = null;

        if ($newTerms) {
            $newContract->fill($newTerms);
        }

        $newContract->save();

        return $newContract;
    }

    /**
     * حساب الراتب اليومي
     */
    public function getDailySalary(): float
    {
        return $this->total_salary / 30;
    }

    /**
     * حساب الراتب بالساعة
     */
    public function getHourlySalary(): float
    {
        if (!$this->working_hours_per_week || $this->working_hours_per_week == 0) {
            return 0;
        }

        $monthlyHours = ($this->working_hours_per_week / 7) * 30;
        return $this->total_salary / $monthlyHours;
    }
}
