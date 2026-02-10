<?php

namespace App\Models\Finance;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommissionAdjustment extends Model
{
    use HasFactory, HasUuids;

    /**
     * أنواع التعديل
     */
    public const TYPE_CLAWBACK = 'clawback';
    public const TYPE_BONUS = 'bonus';
    public const TYPE_CORRECTION = 'correction';
    public const TYPE_PENALTY = 'penalty';

    public const TYPES = [
        self::TYPE_CLAWBACK => 'استرداد (Clawback)',
        self::TYPE_BONUS => 'مكافأة',
        self::TYPE_CORRECTION => 'تصحيح',
        self::TYPE_PENALTY => 'خصم',
    ];

    /**
     * حالات التعديل
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_APPLIED = 'applied';

    public const STATUSES = [
        self::STATUS_PENDING => 'قيد الانتظار',
        self::STATUS_APPROVED => 'معتمد',
        self::STATUS_REJECTED => 'مرفوض',
        self::STATUS_APPLIED => 'مطبق',
    ];

    protected $fillable = [
        'adjustment_number',
        'doctor_id',
        'type',
        'amount',
        'reason',
        'reference_type',
        'reference_id',
        'claim_id',
        'payroll_id',
        'status',
        'requested_by',
        'approved_by',
        'approved_at',
        'applied_at',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'approved_at' => 'datetime',
        'applied_at' => 'datetime',
    ];

    // =============================================================================
    // Boot
    // =============================================================================

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($adjustment) {
            if (empty($adjustment->adjustment_number)) {
                $adjustment->adjustment_number = self::generateAdjustmentNumber();
            }
        });
    }

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

    public function getIsDeductionAttribute(): bool
    {
        return in_array($this->type, [self::TYPE_CLAWBACK, self::TYPE_PENALTY]);
    }

    public function getEffectiveAmountAttribute(): float
    {
        return $this->is_deduction ? -abs($this->amount) : abs($this->amount);
    }

    // =============================================================================
    // Relationships
    // =============================================================================

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function claim(): BelongsTo
    {
        return $this->belongsTo(InsuranceClaim::class, 'claim_id');
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // =============================================================================
    // Scopes
    // =============================================================================

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function scopeForDoctor($query, string $doctorId)
    {
        return $query->where('doctor_id', $doctorId);
    }

    public function scopeClawbacks($query)
    {
        return $query->where('type', self::TYPE_CLAWBACK);
    }

    // =============================================================================
    // Static Methods
    // =============================================================================

    public static function generateAdjustmentNumber(): string
    {
        $prefix = 'ADJ';
        $date = now()->format('Ymd');
        $count = self::whereDate('created_at', today())->count() + 1;

        return "{$prefix}-{$date}-" . str_pad($count, 4, '0', STR_PAD_LEFT);
    }
}
