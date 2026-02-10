<?php

namespace App\Models\Payroll;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollItem extends Model
{
    use HasFactory, HasUuids;

    /**
     * أنواع البنود
     */
    public const TYPE_EARNING = 'earning';
    public const TYPE_DEDUCTION = 'deduction';

    /**
     * رموز البنود الثابتة
     */
    // الإضافات
    public const CODE_BASIC_SALARY = 'BASIC';
    public const CODE_HOUSING = 'HOUSING';
    public const CODE_TRANSPORTATION = 'TRANSPORT';
    public const CODE_OVERTIME = 'OVERTIME';
    public const CODE_BONUS = 'BONUS';
    public const CODE_INCENTIVE = 'INCENTIVE';
    public const CODE_COMMISSION = 'COMMISSION';

    // الخصومات
    public const CODE_GOSI = 'GOSI';
    public const CODE_ABSENCE = 'ABSENCE';
    public const CODE_LATE = 'LATE';
    public const CODE_LOAN = 'LOAN';
    public const CODE_ADVANCE = 'ADVANCE';
    public const CODE_VIOLATION = 'VIOLATION';

    public const ITEM_NAMES = [
        self::CODE_BASIC_SALARY => 'الراتب الأساسي',
        self::CODE_HOUSING => 'بدل السكن',
        self::CODE_TRANSPORTATION => 'بدل النقل',
        self::CODE_OVERTIME => 'الوقت الإضافي',
        self::CODE_BONUS => 'مكافأة',
        self::CODE_INCENTIVE => 'حوافز',
        self::CODE_COMMISSION => 'عمولة',
        self::CODE_GOSI => 'التأمينات الاجتماعية',
        self::CODE_ABSENCE => 'خصم غياب',
        self::CODE_LATE => 'خصم تأخير',
        self::CODE_LOAN => 'قسط سلفة',
        self::CODE_ADVANCE => 'سلفة راتب',
        self::CODE_VIOLATION => 'جزاء/مخالفة',
    ];

    protected $fillable = [
        'payroll_id',
        'type',
        'code',
        'name_ar',
        'name_en',
        'description',
        'quantity',
        'rate',
        'amount',
        'is_taxable',
        'is_recurring',
        'reference_type',
        'reference_id',
        'sort_order',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'rate' => 'decimal:2',
        'amount' => 'decimal:2',
        'is_taxable' => 'boolean',
        'is_recurring' => 'boolean',
        'sort_order' => 'integer',
    ];

    // =============================================================================
    // Accessors
    // =============================================================================

    public function getNameAttribute(): string
    {
        $locale = app()->getLocale();
        return $locale === 'ar' ? $this->name_ar : ($this->name_en ?: $this->name_ar);
    }

    public function getTypeNameAttribute(): string
    {
        return $this->type === self::TYPE_EARNING ? 'إضافة' : 'خصم';
    }

    public function getIsEarningAttribute(): bool
    {
        return $this->type === self::TYPE_EARNING;
    }

    public function getIsDeductionAttribute(): bool
    {
        return $this->type === self::TYPE_DEDUCTION;
    }

    // =============================================================================
    // Relationships
    // =============================================================================

    public function payroll(): BelongsTo
    {
        return $this->belongsTo(Payroll::class);
    }

    // =============================================================================
    // Scopes
    // =============================================================================

    public function scopeEarnings($query)
    {
        return $query->where('type', self::TYPE_EARNING);
    }

    public function scopeDeductions($query)
    {
        return $query->where('type', self::TYPE_DEDUCTION);
    }

    public function scopeByCode($query, string $code)
    {
        return $query->where('code', $code);
    }

    // =============================================================================
    // Static Methods
    // =============================================================================

    /**
     * إنشاء بند إضافة
     */
    public static function createEarning(
        string $payrollId,
        string $code,
        float $amount,
        ?string $description = null,
        ?float $quantity = null,
        ?float $rate = null
    ): self {
        return self::create([
            'payroll_id' => $payrollId,
            'type' => self::TYPE_EARNING,
            'code' => $code,
            'name_ar' => self::ITEM_NAMES[$code] ?? $code,
            'name_en' => $code,
            'description' => $description,
            'quantity' => $quantity,
            'rate' => $rate,
            'amount' => $amount,
        ]);
    }

    /**
     * إنشاء بند خصم
     */
    public static function createDeduction(
        string $payrollId,
        string $code,
        float $amount,
        ?string $description = null,
        ?string $referenceType = null,
        ?string $referenceId = null
    ): self {
        return self::create([
            'payroll_id' => $payrollId,
            'type' => self::TYPE_DEDUCTION,
            'code' => $code,
            'name_ar' => self::ITEM_NAMES[$code] ?? $code,
            'name_en' => $code,
            'description' => $description,
            'amount' => $amount,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
        ]);
    }
}
