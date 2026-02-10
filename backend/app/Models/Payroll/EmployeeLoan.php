<?php

namespace App\Models\Payroll;

use App\Models\HR\Employee;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class EmployeeLoan extends Model
{
    use HasFactory, HasUuids;

    /**
     * أنواع السلف
     */
    public const TYPE_LOAN = 'loan';
    public const TYPE_ADVANCE = 'advance';

    public const TYPES = [
        self::TYPE_LOAN => 'سلفة',
        self::TYPE_ADVANCE => 'سلفة راتب',
    ];

    /**
     * حالات السلفة
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_PENDING => 'قيد الانتظار',
        self::STATUS_APPROVED => 'معتمد',
        self::STATUS_REJECTED => 'مرفوض',
        self::STATUS_ACTIVE => 'نشط',
        self::STATUS_COMPLETED => 'مكتمل',
        self::STATUS_CANCELLED => 'ملغي',
    ];

    protected $fillable = [
        'loan_number',
        'employee_id',
        'type',
        'status',
        'loan_amount',
        'installment_amount',
        'total_installments',
        'paid_installments',
        'remaining_amount',
        'start_date',
        'end_date',
        'reason',
        'notes',
        'requested_at',
        'approved_by',
        'approved_at',
        'rejected_by',
        'rejected_at',
        'rejection_reason',
    ];

    protected $casts = [
        'loan_amount' => 'decimal:2',
        'installment_amount' => 'decimal:2',
        'total_installments' => 'integer',
        'paid_installments' => 'integer',
        'remaining_amount' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
        'requested_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    // =============================================================================
    // Accessors
    // =============================================================================

    public function getTypeNameAttribute(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    public function getStatusNameAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    /**
     * نسبة السداد
     */
    public function getProgressPercentageAttribute(): int
    {
        if ($this->total_installments == 0) return 0;
        return (int) (($this->paid_installments / $this->total_installments) * 100);
    }

    /**
     * الأقساط المتبقية
     */
    public function getRemainingInstallmentsAttribute(): int
    {
        return $this->total_installments - $this->paid_installments;
    }

    /**
     * المبلغ المسدد
     */
    public function getPaidAmountAttribute(): float
    {
        return $this->loan_amount - $this->remaining_amount;
    }

    // =============================================================================
    // Relationships
    // =============================================================================

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(LoanPayment::class, 'loan_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'approved_by');
    }

    // =============================================================================
    // Scopes
    // =============================================================================

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeForEmployee($query, string $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    public function scopeLoans($query)
    {
        return $query->where('type', self::TYPE_LOAN);
    }

    public function scopeAdvances($query)
    {
        return $query->where('type', self::TYPE_ADVANCE);
    }

    // =============================================================================
    // Methods
    // =============================================================================

    /**
     * الموافقة على السلفة
     */
    public function approve(string $approverId): bool
    {
        $this->status = self::STATUS_APPROVED;
        $this->approved_by = $approverId;
        $this->approved_at = now();
        return $this->save();
    }

    /**
     * تفعيل السلفة
     */
    public function activate(): bool
    {
        $this->status = self::STATUS_ACTIVE;
        $this->remaining_amount = $this->loan_amount;
        $this->paid_installments = 0;
        return $this->save();
    }

    /**
     * رفض السلفة
     */
    public function reject(string $rejectedBy, string $reason): bool
    {
        $this->status = self::STATUS_REJECTED;
        $this->rejected_by = $rejectedBy;
        $this->rejected_at = now();
        $this->rejection_reason = $reason;
        return $this->save();
    }

    /**
     * تسجيل دفعة/قسط
     *
     * @param float $amount مبلغ الدفعة
     * @param string $payrollId معرف مسير الراتب
     * @return bool
     * @throws InvalidArgumentException
     */
    public function recordPayment(float $amount, string $payrollId): bool
    {
        // التحقق من صحة المبلغ
        if ($amount <= 0) {
            throw new InvalidArgumentException('مبلغ الدفعة يجب أن يكون أكبر من صفر');
        }

        if ($amount > $this->remaining_amount) {
            throw new InvalidArgumentException(
                "مبلغ الدفعة ({$amount}) أكبر من المبلغ المتبقي ({$this->remaining_amount})"
            );
        }

        // التحقق من حالة السلفة
        if ($this->status !== self::STATUS_ACTIVE) {
            throw new InvalidArgumentException('السلفة غير نشطة ولا يمكن تسجيل دفعات لها');
        }

        return DB::transaction(function () use ($amount, $payrollId) {
            $previousRemaining = $this->remaining_amount;

            $this->remaining_amount -= $amount;
            $this->paid_installments++;

            if ($this->remaining_amount <= 0) {
                $this->remaining_amount = 0;
                $this->status = self::STATUS_COMPLETED;
            }

            // تسجيل الدفعة
            $payment = LoanPayment::create([
                'loan_id' => $this->id,
                'payroll_id' => $payrollId,
                'amount' => $amount,
                'payment_date' => now(),
                'remaining_after' => $this->remaining_amount,
            ]);

            $saved = $this->save();

            // تسجيل العملية
            Log::info('Loan payment recorded', [
                'loan_id' => $this->id,
                'loan_number' => $this->loan_number,
                'payment_id' => $payment->id,
                'amount' => $amount,
                'previous_remaining' => $previousRemaining,
                'new_remaining' => $this->remaining_amount,
                'payroll_id' => $payrollId,
                'status' => $this->status,
            ]);

            return $saved;
        });
    }

    /**
     * حساب القسط الشهري
     */
    public static function calculateInstallment(float $amount, int $months): float
    {
        return round($amount / $months, 2);
    }

    // =============================================================================
    // Boot
    // =============================================================================

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($loan) {
            if (!$loan->loan_number) {
                $prefix = $loan->type === self::TYPE_LOAN ? 'LN' : 'ADV';
                $year = date('Y');
                $count = self::whereYear('created_at', $year)->count() + 1;
                $loan->loan_number = $prefix . $year . str_pad($count, 5, '0', STR_PAD_LEFT);
            }

            if (!$loan->remaining_amount) {
                $loan->remaining_amount = $loan->loan_amount;
            }

            if (!$loan->requested_at) {
                $loan->requested_at = now();
            }
        });
    }
}
