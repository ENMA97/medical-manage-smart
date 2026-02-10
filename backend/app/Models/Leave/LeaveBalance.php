<?php

namespace App\Models\Leave;

use App\Models\HR\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LeaveBalance extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'leave_balances';

    protected $fillable = [
        'employee_id',
        'leave_type_id',
        'year',
        'entitled_days',
        'carried_over_days',
        'additional_days',
        'used_days',
        'pending_days',
        'remaining_days',
        'notes',
        'last_updated_by',
    ];

    protected $casts = [
        'year' => 'integer',
        'entitled_days' => 'decimal:2',
        'carried_over_days' => 'decimal:2',
        'additional_days' => 'decimal:2',
        'used_days' => 'decimal:2',
        'pending_days' => 'decimal:2',
        'remaining_days' => 'decimal:2',
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
     * العلاقة مع آخر من قام بالتحديث
     */
    public function lastUpdatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'last_updated_by');
    }

    /**
     * العلاقة مع تعديلات الرصيد
     */
    public function adjustments(): HasMany
    {
        return $this->hasMany(LeaveBalanceAdjustment::class);
    }

    /**
     * حساب الرصيد الإجمالي المتاح
     */
    public function getTotalAvailableAttribute(): float
    {
        return $this->entitled_days + $this->carried_over_days + $this->additional_days;
    }

    /**
     * حساب الرصيد المتبقي الفعلي
     */
    public function calculateRemaining(): float
    {
        return $this->total_available - $this->used_days - $this->pending_days;
    }

    /**
     * تحديث الرصيد المتبقي
     */
    public function updateRemaining(): void
    {
        $this->remaining_days = $this->calculateRemaining();
        $this->save();
    }

    /**
     * التحقق من توفر رصيد كافي
     */
    public function hasEnoughBalance(float $days): bool
    {
        return $this->remaining_days >= $days;
    }

    /**
     * خصم من الرصيد
     */
    public function deduct(float $days, ?string $userId = null): void
    {
        $this->used_days += $days;
        $this->remaining_days = $this->calculateRemaining();
        $this->last_updated_by = $userId;
        $this->save();
    }

    /**
     * إضافة للرصيد المعلق
     */
    public function addPending(float $days): void
    {
        $this->pending_days += $days;
        $this->remaining_days = $this->calculateRemaining();
        $this->save();
    }

    /**
     * تحويل المعلق إلى مستخدم
     */
    public function confirmPending(float $days, ?string $userId = null): void
    {
        $this->pending_days -= $days;
        $this->used_days += $days;
        $this->remaining_days = $this->calculateRemaining();
        $this->last_updated_by = $userId;
        $this->save();
    }

    /**
     * إلغاء المعلق
     */
    public function cancelPending(float $days): void
    {
        $this->pending_days -= $days;
        $this->remaining_days = $this->calculateRemaining();
        $this->save();
    }

    /**
     * Scope للسنة الحالية
     */
    public function scopeCurrentYear($query)
    {
        return $query->where('year', date('Y'));
    }

    /**
     * Scope حسب الموظف
     */
    public function scopeForEmployee($query, string $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    /**
     * Scope حسب نوع الإجازة
     */
    public function scopeForLeaveType($query, string $leaveTypeId)
    {
        return $query->where('leave_type_id', $leaveTypeId);
    }
}
