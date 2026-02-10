<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class InventoryItem extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    /**
     * أنواع الأصناف
     */
    public const TYPE_MEDICINE = 'medicine';
    public const TYPE_CONSUMABLE = 'consumable';
    public const TYPE_EQUIPMENT = 'equipment';
    public const TYPE_SURGICAL = 'surgical';
    public const TYPE_LABORATORY = 'laboratory';
    public const TYPE_RADIOLOGY = 'radiology';
    public const TYPE_OTHER = 'other';

    public const TYPES = [
        self::TYPE_MEDICINE => 'دواء',
        self::TYPE_CONSUMABLE => 'مستهلك',
        self::TYPE_EQUIPMENT => 'معدات',
        self::TYPE_SURGICAL => 'جراحي',
        self::TYPE_LABORATORY => 'مختبري',
        self::TYPE_RADIOLOGY => 'أشعة',
        self::TYPE_OTHER => 'أخرى',
    ];

    protected $fillable = [
        'code',
        'barcode',
        'name_ar',
        'name_en',
        'description',
        'category_id',
        'type',
        'unit',
        'secondary_unit',
        'conversion_rate',
        'generic_name',
        'manufacturer',
        'strength',
        'dosage_form',
        'reorder_level',
        'max_stock',
        'min_stock',
        'cost_price',
        'selling_price',
        'track_batch',
        'track_expiry',
        'is_controlled',
        'requires_prescription',
        'is_active',
    ];

    protected $casts = [
        'conversion_rate' => 'decimal:2',
        'reorder_level' => 'decimal:2',
        'max_stock' => 'decimal:2',
        'min_stock' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'track_batch' => 'boolean',
        'track_expiry' => 'boolean',
        'is_controlled' => 'boolean',
        'requires_prescription' => 'boolean',
        'is_active' => 'boolean',
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
        return self::TYPES[$this->type] ?? $this->type;
    }

    /**
     * إجمالي الرصيد في جميع المستودعات
     */
    public function getTotalStockAttribute(): float
    {
        return $this->stocks()->sum('quantity');
    }

    /**
     * هل الصنف تحت حد الطلب
     */
    public function getIsLowStockAttribute(): bool
    {
        return $this->total_stock <= $this->reorder_level;
    }

    /**
     * هل الصنف نفد
     */
    public function getIsOutOfStockAttribute(): bool
    {
        return $this->total_stock <= 0;
    }

    // =============================================================================
    // Relationships
    // =============================================================================

    public function category(): BelongsTo
    {
        return $this->belongsTo(ItemCategory::class, 'category_id');
    }

    public function stocks(): HasMany
    {
        return $this->hasMany(WarehouseStock::class, 'item_id');
    }

    public function movements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class, 'item_id');
    }

    public function quotas(): HasMany
    {
        return $this->hasMany(ItemQuota::class, 'item_id');
    }

    // =============================================================================
    // Scopes
    // =============================================================================

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeControlled($query)
    {
        return $query->where('is_controlled', true);
    }

    public function scopeLowStock($query)
    {
        return $query->whereHas('stocks', function ($q) {
            $q->havingRaw('SUM(quantity) <= inventory_items.reorder_level');
        });
    }

    public function scopeSearch($query, string $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('code', 'like', "%{$term}%")
              ->orWhere('barcode', 'like', "%{$term}%")
              ->orWhere('name_ar', 'like', "%{$term}%")
              ->orWhere('name_en', 'like', "%{$term}%");
        });
    }

    // =============================================================================
    // Methods
    // =============================================================================

    /**
     * الحصول على رصيد في مستودع معين
     */
    public function getStockInWarehouse(string $warehouseId): float
    {
        return $this->stocks()->where('warehouse_id', $warehouseId)->sum('quantity');
    }

    /**
     * أقرب تاريخ انتهاء صلاحية
     */
    public function getNearestExpiryDate()
    {
        return $this->stocks()
            ->whereNotNull('expiry_date')
            ->where('quantity', '>', 0)
            ->min('expiry_date');
    }

    /**
     * تحويل الكمية للوحدة الثانوية
     */
    public function convertToSecondaryUnit(float $quantity): float
    {
        if (!$this->conversion_rate || $this->conversion_rate == 0) {
            return $quantity;
        }
        return $quantity * $this->conversion_rate;
    }

    /**
     * تحويل الكمية للوحدة الرئيسية
     */
    public function convertToPrimaryUnit(float $quantity): float
    {
        if (!$this->conversion_rate || $this->conversion_rate == 0) {
            return $quantity;
        }
        return $quantity / $this->conversion_rate;
    }
}
