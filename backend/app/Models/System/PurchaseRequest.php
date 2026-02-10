<?php

namespace App\Models\System;

use App\Models\HR\Department;
use App\Models\Inventory\Warehouse;
use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseRequest extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    /**
     * حالات الطلب
     */
    public const STATUS_DRAFT = 'draft';
    public const STATUS_PENDING = 'pending';
    public const STATUS_PENDING_MANAGER = 'pending_manager';
    public const STATUS_PENDING_FINANCE = 'pending_finance';
    public const STATUS_PENDING_CEO = 'pending_ceo';
    public const STATUS_MANAGER_APPROVED = 'manager_approved';
    public const STATUS_FINANCE_APPROVED = 'finance_approved';
    public const STATUS_CEO_APPROVED = 'ceo_approved';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_ORDERED = 'ordered';
    public const STATUS_RECEIVED = 'received';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_DRAFT => 'مسودة',
        self::STATUS_PENDING => 'قيد الانتظار',
        self::STATUS_PENDING_MANAGER => 'بانتظار موافقة المدير',
        self::STATUS_PENDING_FINANCE => 'بانتظار موافقة المالية',
        self::STATUS_PENDING_CEO => 'بانتظار موافقة المدير التنفيذي',
        self::STATUS_MANAGER_APPROVED => 'موافقة المدير',
        self::STATUS_FINANCE_APPROVED => 'موافقة المالية',
        self::STATUS_CEO_APPROVED => 'موافقة المدير التنفيذي',
        self::STATUS_APPROVED => 'معتمد',
        self::STATUS_ORDERED => 'تم الطلب',
        self::STATUS_RECEIVED => 'تم الاستلام',
        self::STATUS_COMPLETED => 'مكتمل',
        self::STATUS_REJECTED => 'مرفوض',
        self::STATUS_CANCELLED => 'ملغي',
    ];

    public const PRIORITY_LOW = 'low';
    public const PRIORITY_MEDIUM = 'medium';
    public const PRIORITY_HIGH = 'high';
    public const PRIORITY_URGENT = 'urgent';

    public const PRIORITIES = [
        self::PRIORITY_LOW => 'منخفضة',
        self::PRIORITY_MEDIUM => 'متوسطة',
        self::PRIORITY_HIGH => 'عالية',
        self::PRIORITY_URGENT => 'عاجلة',
    ];

    protected $fillable = [
        'request_number',
        'warehouse_id',
        'department_id',
        'status',
        'priority',
        'purpose',
        'justification',
        'needed_by',
        'estimated_total',
        'total_estimated_amount',
        'approved_total',
        'requested_by',
        'requested_at',
        'request_date',
        'submitted_at',
        'manager_approved_by',
        'manager_approved_at',
        'manager_notes',
        'finance_approved_by',
        'finance_approved_at',
        'finance_notes',
        'ceo_approved_by',
        'ceo_approved_at',
        'ceo_notes',
        'rejected_by',
        'rejected_at',
        'rejection_reason',
        'received_at',
        'received_by',
        'notes',
    ];

    protected $casts = [
        'needed_by' => 'date',
        'estimated_total' => 'decimal:2',
        'total_estimated_amount' => 'decimal:2',
        'approved_total' => 'decimal:2',
        'requested_at' => 'datetime',
        'request_date' => 'datetime',
        'submitted_at' => 'datetime',
        'manager_approved_at' => 'datetime',
        'finance_approved_at' => 'datetime',
        'ceo_approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'received_at' => 'datetime',
    ];

    // =============================================================================
    // Boot
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

    public function getPriorityNameAttribute(): string
    {
        return self::PRIORITIES[$this->priority] ?? $this->priority;
    }

    public function getIsPendingAttribute(): bool
    {
        return in_array($this->status, [
            self::STATUS_PENDING,
            self::STATUS_PENDING_MANAGER,
            self::STATUS_PENDING_FINANCE,
            self::STATUS_PENDING_CEO,
        ]);
    }

    public function getIsApprovedAttribute(): bool
    {
        return in_array($this->status, [
            self::STATUS_APPROVED,
            self::STATUS_ORDERED,
            self::STATUS_RECEIVED,
            self::STATUS_COMPLETED,
        ]);
    }

    // =============================================================================
    // Relationships
    // =============================================================================

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function managerApprovedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_approved_by');
    }

    public function financeApprovedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'finance_approved_by');
    }

    public function ceoApprovedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ceo_approved_by');
    }

    public function rejectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseRequestItem::class);
    }

    // =============================================================================
    // Scopes
    // =============================================================================

    public function scopePending($query)
    {
        return $query->whereIn('status', [
            self::STATUS_PENDING,
            self::STATUS_PENDING_MANAGER,
            self::STATUS_PENDING_FINANCE,
            self::STATUS_PENDING_CEO,
        ]);
    }

    public function scopeApproved($query)
    {
        return $query->whereIn('status', [
            self::STATUS_APPROVED,
            self::STATUS_ORDERED,
            self::STATUS_RECEIVED,
            self::STATUS_COMPLETED,
        ]);
    }

    // =============================================================================
    // Static Methods
    // =============================================================================

    public static function generateRequestNumber(): string
    {
        $prefix = 'PR';
        $date = now()->format('Ymd');
        $count = self::whereDate('created_at', today())->count() + 1;

        return "{$prefix}-{$date}-" . str_pad($count, 4, '0', STR_PAD_LEFT);
    }
}
