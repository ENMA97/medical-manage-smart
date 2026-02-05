<?php

namespace App\Models\HR;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClearanceRequest extends Model
{
    use HasFactory, HasUuids;

    /**
     * أسباب إخلاء الطرف
     */
    public const REASON_RESIGNATION = 'resignation';
    public const REASON_TERMINATION = 'termination';
    public const REASON_CONTRACT_END = 'contract_end';
    public const REASON_RETIREMENT = 'retirement';
    public const REASON_DEATH = 'death';
    public const REASON_TRANSFER = 'transfer';

    public const REASONS = [
        self::REASON_RESIGNATION => 'استقالة',
        self::REASON_TERMINATION => 'إنهاء خدمات',
        self::REASON_CONTRACT_END => 'انتهاء العقد',
        self::REASON_RETIREMENT => 'تقاعد',
        self::REASON_DEATH => 'وفاة',
        self::REASON_TRANSFER => 'نقل',
    ];

    /**
     * حالات إخلاء الطرف
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_FINANCE_APPROVED = 'finance_approved';
    public const STATUS_HR_APPROVED = 'hr_approved';
    public const STATUS_IT_APPROVED = 'it_approved';
    public const STATUS_CUSTODY_CLEARED = 'custody_cleared';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_REJECTED = 'rejected';

    public const STATUSES = [
        self::STATUS_PENDING => 'قيد الانتظار',
        self::STATUS_FINANCE_APPROVED => 'موافقة المالية',
        self::STATUS_HR_APPROVED => 'موافقة الموارد البشرية',
        self::STATUS_IT_APPROVED => 'موافقة تقنية المعلومات',
        self::STATUS_CUSTODY_CLEARED => 'تسوية العهد',
        self::STATUS_COMPLETED => 'مكتمل',
        self::STATUS_REJECTED => 'مرفوض',
    ];

    protected $fillable = [
        'request_number',
        'employee_id',
        'reason',
        'other_reason',
        'last_working_day',
        'status',
        'initiated_by',
        'initiated_at',
        // موافقات الأقسام
        'finance_approved_by',
        'finance_approved_at',
        'finance_notes',
        'finance_amount_due',
        'hr_approved_by',
        'hr_approved_at',
        'hr_notes',
        'hr_vacation_balance',
        'it_approved_by',
        'it_approved_at',
        'it_notes',
        'custody_cleared_by',
        'custody_cleared_at',
        'custody_notes',
        'custody_total_value',
        'custody_deductions',
        // الإتمام
        'completed_at',
        'completed_by',
        'final_settlement_amount',
        'rejection_reason',
        'notes',
    ];

    protected $casts = [
        'last_working_day' => 'date',
        'initiated_at' => 'datetime',
        'finance_approved_at' => 'datetime',
        'hr_approved_at' => 'datetime',
        'it_approved_at' => 'datetime',
        'custody_cleared_at' => 'datetime',
        'completed_at' => 'datetime',
        'finance_amount_due' => 'decimal:2',
        'custody_total_value' => 'decimal:2',
        'custody_deductions' => 'decimal:2',
        'final_settlement_amount' => 'decimal:2',
    ];

    // =============================================================================
    // Accessors
    // =============================================================================

    /**
     * اسم السبب
     */
    public function getReasonNameAttribute(): string
    {
        return self::REASONS[$this->reason] ?? $this->reason;
    }

    /**
     * اسم الحالة
     */
    public function getStatusNameAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    /**
     * نسبة الإكتمال
     */
    public function getCompletionPercentageAttribute(): int
    {
        $steps = [
            'finance_approved_at',
            'hr_approved_at',
            'it_approved_at',
            'custody_cleared_at',
            'completed_at',
        ];

        $completed = 0;
        foreach ($steps as $step) {
            if ($this->$step) {
                $completed++;
            }
        }

        return (int) (($completed / count($steps)) * 100);
    }

    /**
     * الخطوة الحالية
     */
    public function getCurrentStepAttribute(): string
    {
        if ($this->status === self::STATUS_COMPLETED) {
            return 'مكتمل';
        }

        if ($this->status === self::STATUS_REJECTED) {
            return 'مرفوض';
        }

        if (!$this->finance_approved_at) {
            return 'بانتظار المالية';
        }

        if (!$this->hr_approved_at) {
            return 'بانتظار الموارد البشرية';
        }

        if (!$this->it_approved_at) {
            return 'بانتظار تقنية المعلومات';
        }

        if (!$this->custody_cleared_at) {
            return 'بانتظار تسوية العهد';
        }

        return 'بانتظار الإكمال';
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

    /**
     * منشئ الطلب
     */
    public function initiator(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'initiated_by');
    }

    /**
     * موافق المالية
     */
    public function financeApprover(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'finance_approved_by');
    }

    /**
     * موافق الموارد البشرية
     */
    public function hrApprover(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'hr_approved_by');
    }

    /**
     * موافق تقنية المعلومات
     */
    public function itApprover(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'it_approved_by');
    }

    /**
     * مسوي العهد
     */
    public function custodyClearer(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'custody_cleared_by');
    }

    // =============================================================================
    // Scopes
    // =============================================================================

    /**
     * الطلبات المعلقة
     */
    public function scopePending($query)
    {
        return $query->whereNotIn('status', [self::STATUS_COMPLETED, self::STATUS_REJECTED]);
    }

    /**
     * الطلبات المكتملة
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * بانتظار قسم معين
     */
    public function scopePendingFor($query, string $department)
    {
        return match ($department) {
            'finance' => $query->whereNull('finance_approved_at')->where('status', '!=', self::STATUS_REJECTED),
            'hr' => $query->whereNotNull('finance_approved_at')->whereNull('hr_approved_at')->where('status', '!=', self::STATUS_REJECTED),
            'it' => $query->whereNotNull('hr_approved_at')->whereNull('it_approved_at')->where('status', '!=', self::STATUS_REJECTED),
            'custody' => $query->whereNotNull('it_approved_at')->whereNull('custody_cleared_at')->where('status', '!=', self::STATUS_REJECTED),
            default => $query,
        };
    }

    // =============================================================================
    // Methods
    // =============================================================================

    /**
     * موافقة المالية
     */
    public function approveByFinance(string $approverId, ?string $notes = null, ?float $amountDue = null): bool
    {
        $this->finance_approved_by = $approverId;
        $this->finance_approved_at = now();
        $this->finance_notes = $notes;
        $this->finance_amount_due = $amountDue;
        $this->status = self::STATUS_FINANCE_APPROVED;

        return $this->save();
    }

    /**
     * موافقة الموارد البشرية
     */
    public function approveByHr(string $approverId, ?string $notes = null, ?float $vacationBalance = null): bool
    {
        $this->hr_approved_by = $approverId;
        $this->hr_approved_at = now();
        $this->hr_notes = $notes;
        $this->hr_vacation_balance = $vacationBalance;
        $this->status = self::STATUS_HR_APPROVED;

        return $this->save();
    }

    /**
     * موافقة تقنية المعلومات
     */
    public function approveByIt(string $approverId, ?string $notes = null): bool
    {
        $this->it_approved_by = $approverId;
        $this->it_approved_at = now();
        $this->it_notes = $notes;
        $this->status = self::STATUS_IT_APPROVED;

        return $this->save();
    }

    /**
     * تسوية العهد
     */
    public function clearCustody(string $clearerId, ?string $notes = null, ?float $totalValue = null, ?float $deductions = null): bool
    {
        $this->custody_cleared_by = $clearerId;
        $this->custody_cleared_at = now();
        $this->custody_notes = $notes;
        $this->custody_total_value = $totalValue;
        $this->custody_deductions = $deductions;
        $this->status = self::STATUS_CUSTODY_CLEARED;

        return $this->save();
    }

    /**
     * إكمال إخلاء الطرف
     */
    public function complete(string $completerId, float $settlementAmount): bool
    {
        $this->completed_by = $completerId;
        $this->completed_at = now();
        $this->final_settlement_amount = $settlementAmount;
        $this->status = self::STATUS_COMPLETED;

        // تعطيل الموظف
        $this->employee->update(['is_active' => false, 'employment_status' => 'terminated']);

        return $this->save();
    }

    /**
     * رفض الطلب
     */
    public function reject(string $reason): bool
    {
        $this->rejection_reason = $reason;
        $this->status = self::STATUS_REJECTED;

        return $this->save();
    }

    /**
     * حساب التسوية النهائية
     */
    public function calculateSettlement(): float
    {
        $amount = $this->finance_amount_due ?? 0;
        $amount += ($this->hr_vacation_balance ?? 0) * ($this->employee->currentContract?->getDailySalary() ?? 0);
        $amount -= $this->custody_deductions ?? 0;

        return max(0, $amount);
    }
}
