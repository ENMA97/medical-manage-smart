<?php

namespace App\Models\Leave;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveBalanceAdjustment extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'leave_balance_adjustments';

    /**
     * السجل غير قابل للتعديل
     */
    public $timestamps = false;

    protected $fillable = [
        'leave_balance_id',
        'leave_request_id',
        'adjustment_type',
        'days_amount',
        'balance_before',
        'balance_after',
        'reason',
        'performed_by',
        'created_at',
    ];

    protected $casts = [
        'days_amount' => 'decimal:2',
        'balance_before' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'created_at' => 'datetime',
    ];

    /**
     * أنواع التعديلات
     */
    public const ADJUSTMENT_TYPES = [
        'initial' => 'رصيد أولي',
        'accrual' => 'استحقاق دوري',
        'carry_over' => 'ترحيل',
        'used' => 'استخدام',
        'cancelled' => 'إلغاء طلب',
        'manual_add' => 'إضافة يدوية',
        'manual_deduct' => 'خصم يدوي',
        'expired' => 'انتهاء صلاحية',
        'correction' => 'تصحيح',
    ];

    /**
     * العلاقة مع رصيد الإجازة
     */
    public function leaveBalance(): BelongsTo
    {
        return $this->belongsTo(LeaveBalance::class);
    }

    /**
     * العلاقة مع طلب الإجازة
     */
    public function leaveRequest(): BelongsTo
    {
        return $this->belongsTo(LeaveRequest::class);
    }

    /**
     * العلاقة مع من نفذ التعديل
     */
    public function performedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    /**
     * الحصول على اسم نوع التعديل بالعربية
     */
    public function getAdjustmentTypeNameAttribute(): string
    {
        return self::ADJUSTMENT_TYPES[$this->adjustment_type] ?? $this->adjustment_type;
    }

    /**
     * التحقق من أن التعديل إضافة
     */
    public function isAddition(): bool
    {
        return $this->days_amount > 0;
    }

    /**
     * التحقق من أن التعديل خصم
     */
    public function isDeduction(): bool
    {
        return $this->days_amount < 0;
    }

    /**
     * إنشاء تعديل جديد
     */
    public static function createAdjustment(
        string $balanceId,
        string $type,
        float $amount,
        float $balanceBefore,
        string $performedBy,
        ?string $requestId = null,
        ?string $reason = null
    ): self {
        return self::create([
            'leave_balance_id' => $balanceId,
            'leave_request_id' => $requestId,
            'adjustment_type' => $type,
            'days_amount' => $amount,
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceBefore + $amount,
            'reason' => $reason,
            'performed_by' => $performedBy,
            'created_at' => now(),
        ]);
    }
}
