<?php

namespace App\Models\Roster;

use App\Models\HR\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShiftSwapRequest extends Model
{
    use HasFactory, HasUuids;

    /**
     * حالات الطلب
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_TARGET_APPROVED = 'target_approved';
    public const STATUS_SUPERVISOR_APPROVED = 'supervisor_approved';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_CANCELLED = 'cancelled';

    // حالات إضافية للتوافق مع الكود
    public const STATUS_PENDING_TARGET = 'pending_target';
    public const STATUS_PENDING_SUPERVISOR = 'pending_supervisor';
    public const STATUS_REJECTED_BY_TARGET = 'rejected_by_target';
    public const STATUS_REJECTED_BY_SUPERVISOR = 'rejected_by_supervisor';

    public const STATUSES = [
        self::STATUS_PENDING => 'قيد الانتظار',
        self::STATUS_PENDING_TARGET => 'بانتظار رد الموظف الآخر',
        self::STATUS_PENDING_SUPERVISOR => 'بانتظار موافقة المشرف',
        self::STATUS_TARGET_APPROVED => 'وافق الطرف الآخر',
        self::STATUS_SUPERVISOR_APPROVED => 'وافق المشرف',
        self::STATUS_APPROVED => 'معتمد',
        self::STATUS_REJECTED => 'مرفوض',
        self::STATUS_REJECTED_BY_TARGET => 'مرفوض من الموظف',
        self::STATUS_REJECTED_BY_SUPERVISOR => 'مرفوض من المشرف',
        self::STATUS_CANCELLED => 'ملغي',
    ];

    protected $fillable = [
        'request_number',
        'requester_id',
        'requester_assignment_id',
        'from_assignment_id',
        'target_employee_id',
        'target_assignment_id',
        'to_assignment_id',
        'reason',
        'status',
        'target_response_by',
        'target_response_at',
        'target_responded_at',
        'target_response_notes',
        'supervisor_response_by',
        'supervisor_response_at',
        'supervisor_response_notes',
        'supervisor_notes',
        'requested_by',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'target_response_at' => 'datetime',
        'target_responded_at' => 'datetime',
        'supervisor_response_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    // =============================================================================
    // Boot Methods
    // =============================================================================

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($request) {
            if (empty($request->request_number)) {
                $request->request_number = self::generateRequestNumber();
            }
        });
    }

    // =============================================================================
    // Accessors
    // =============================================================================

    public function getStatusNameAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function getIsPendingAttribute(): bool
    {
        return in_array($this->status, [
            self::STATUS_PENDING,
            self::STATUS_PENDING_TARGET,
            self::STATUS_PENDING_SUPERVISOR,
            self::STATUS_TARGET_APPROVED,
        ]);
    }

    public function getIsApprovedAttribute(): bool
    {
        return in_array($this->status, [
            self::STATUS_APPROVED,
            self::STATUS_SUPERVISOR_APPROVED,
        ]);
    }

    public function getIsRejectedAttribute(): bool
    {
        return in_array($this->status, [
            self::STATUS_REJECTED,
            self::STATUS_REJECTED_BY_TARGET,
            self::STATUS_REJECTED_BY_SUPERVISOR,
        ]);
    }

    // =============================================================================
    // Relationships
    // =============================================================================

    public function requester(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'requester_id');
    }

    public function requesterAssignment(): BelongsTo
    {
        return $this->belongsTo(RosterAssignment::class, 'requester_assignment_id');
    }

    public function fromAssignment(): BelongsTo
    {
        return $this->belongsTo(RosterAssignment::class, 'from_assignment_id');
    }

    public function targetEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'target_employee_id');
    }

    public function targetAssignment(): BelongsTo
    {
        return $this->belongsTo(RosterAssignment::class, 'target_assignment_id');
    }

    public function toAssignment(): BelongsTo
    {
        return $this->belongsTo(RosterAssignment::class, 'to_assignment_id');
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function targetResponseBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'target_response_by');
    }

    public function supervisorResponseBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supervisor_response_by');
    }

    // =============================================================================
    // Scopes
    // =============================================================================

    public function scopePending($query)
    {
        return $query->whereIn('status', [
            self::STATUS_PENDING,
            self::STATUS_PENDING_TARGET,
            self::STATUS_PENDING_SUPERVISOR,
            self::STATUS_TARGET_APPROVED,
        ]);
    }

    public function scopeApproved($query)
    {
        return $query->whereIn('status', [
            self::STATUS_APPROVED,
            self::STATUS_SUPERVISOR_APPROVED,
        ]);
    }

    // =============================================================================
    // Static Methods
    // =============================================================================

    public static function generateRequestNumber(): string
    {
        $prefix = 'SWP';
        $date = now()->format('Ymd');
        $count = self::whereDate('created_at', today())->count() + 1;

        return "{$prefix}-{$date}-" . str_pad($count, 4, '0', STR_PAD_LEFT);
    }
}
