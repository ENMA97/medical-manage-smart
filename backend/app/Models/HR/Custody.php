<?php

namespace App\Models\HR;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Custody extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    /**
     * أنواع العهد
     */
    public const TYPE_EQUIPMENT = 'equipment';
    public const TYPE_VEHICLE = 'vehicle';
    public const TYPE_KEY = 'key';
    public const TYPE_UNIFORM = 'uniform';
    public const TYPE_DOCUMENT = 'document';
    public const TYPE_CASH = 'cash';
    public const TYPE_OTHER = 'other';

    public const TYPES = [
        self::TYPE_EQUIPMENT => 'معدات',
        self::TYPE_VEHICLE => 'مركبة',
        self::TYPE_KEY => 'مفاتيح',
        self::TYPE_UNIFORM => 'زي رسمي',
        self::TYPE_DOCUMENT => 'مستندات',
        self::TYPE_CASH => 'نقدية',
        self::TYPE_OTHER => 'أخرى',
    ];

    /**
     * حالات العهدة
     */
    public const STATUS_ACTIVE = 'active';
    public const STATUS_RETURNED = 'returned';
    public const STATUS_DAMAGED = 'damaged';
    public const STATUS_LOST = 'lost';

    public const STATUSES = [
        self::STATUS_ACTIVE => 'نشطة',
        self::STATUS_RETURNED => 'مسترجعة',
        self::STATUS_DAMAGED => 'تالفة',
        self::STATUS_LOST => 'مفقودة',
    ];

    protected $fillable = [
        'custody_number',
        'employee_id',
        'custody_type',
        'item_name_ar',
        'item_name_en',
        'description',
        'serial_number',
        'asset_tag',
        'value',
        'currency',
        'quantity',
        'unit',
        'issued_date',
        'issued_by',
        'expected_return_date',
        'returned_at',
        'received_by',
        'status',
        'condition_on_issue',
        'condition_on_return',
        'damage_amount',
        'deduction_amount',
        'notes',
        'attachment_path',
    ];

    protected $casts = [
        'issued_date' => 'date',
        'expected_return_date' => 'date',
        'returned_at' => 'datetime',
        'value' => 'decimal:2',
        'damage_amount' => 'decimal:2',
        'deduction_amount' => 'decimal:2',
    ];

    // =============================================================================
    // Accessors
    // =============================================================================

    /**
     * اسم العنصر حسب اللغة الحالية
     */
    public function getItemNameAttribute(): string
    {
        $locale = app()->getLocale();
        return $locale === 'ar' ? $this->item_name_ar : ($this->item_name_en ?: $this->item_name_ar);
    }

    /**
     * اسم نوع العهدة
     */
    public function getCustodyTypeNameAttribute(): string
    {
        return self::TYPES[$this->custody_type] ?? $this->custody_type;
    }

    /**
     * اسم الحالة
     */
    public function getStatusNameAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    /**
     * هل العهدة نشطة
     */
    public function getIsActiveAttribute(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * القيمة الإجمالية
     */
    public function getTotalValueAttribute(): float
    {
        return $this->value * $this->quantity;
    }

    /**
     * الأيام منذ الإصدار
     */
    public function getDaysSinceIssueAttribute(): int
    {
        return $this->issued_date ? $this->issued_date->diffInDays(now()) : 0;
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
     * مسلم العهدة
     */
    public function issuer(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'issued_by');
    }

    /**
     * مستلم العهدة عند الإرجاع
     */
    public function receiver(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'received_by');
    }

    // =============================================================================
    // Scopes
    // =============================================================================

    /**
     * العهد النشطة
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * العهد المسترجعة
     */
    public function scopeReturned($query)
    {
        return $query->where('status', self::STATUS_RETURNED);
    }

    /**
     * العهد المتأخرة
     */
    public function scopeOverdue($query)
    {
        return $query->where('status', self::STATUS_ACTIVE)
            ->whereNotNull('expected_return_date')
            ->where('expected_return_date', '<', now());
    }

    /**
     * حسب النوع
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('custody_type', $type);
    }

    // =============================================================================
    // Methods
    // =============================================================================

    /**
     * إرجاع العهدة
     */
    public function return(string $receivedBy, string $condition, ?float $damageAmount = null): bool
    {
        $this->status = $damageAmount > 0 ? self::STATUS_DAMAGED : self::STATUS_RETURNED;
        $this->returned_at = now();
        $this->received_by = $receivedBy;
        $this->condition_on_return = $condition;
        $this->damage_amount = $damageAmount;

        return $this->save();
    }

    /**
     * تسجيل فقدان العهدة
     */
    public function markAsLost(?float $deductionAmount = null): bool
    {
        $this->status = self::STATUS_LOST;
        $this->deduction_amount = $deductionAmount ?? $this->total_value;

        return $this->save();
    }

    /**
     * هل العهدة متأخرة
     */
    public function isOverdue(): bool
    {
        return $this->status === self::STATUS_ACTIVE &&
               $this->expected_return_date &&
               $this->expected_return_date->isPast();
    }
}
