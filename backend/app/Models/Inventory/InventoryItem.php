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
     * فئات الأصناف
     */
    public const CATEGORY_MEDICATION = 'medication';
    public const CATEGORY_CONSUMABLE = 'consumable';
    public const CATEGORY_EQUIPMENT = 'equipment';
    public const CATEGORY_REAGENT = 'reagent';
    public const CATEGORY_IMPLANT = 'implant';
    public const CATEGORY_SURGICAL = 'surgical';

    public const CATEGORIES = [
        self::CATEGORY_MEDICATION => 'أدوية',
        self::CATEGORY_CONSUMABLE => 'مستهلكات',
        self::CATEGORY_EQUIPMENT => 'معدات',
        self::CATEGORY_REAGENT => 'كواشف',
        self::CATEGORY_IMPLANT => 'غرسات',
        self::CATEGORY_SURGICAL => 'أدوات جراحية',
    ];

    protected $fillable = [
        'code',
        'barcode',
        'name_ar',
        'name_en',
        'description',
        'category',
        'subcategory_id',
        'unit_of_measure',
        'secondary_unit',
        'conversion_factor',
        'manufacturer',
        'supplier_id',
        'unit_cost',
        'unit_price',
        'currency',
        'reorder_level',
        'minimum_stock',
        'maximum_stock',
        'is_controlled_substance',
        'requires_prescription',
        'requires_cold_chain',
        'storage_conditions',
        'shelf_life_days',
        'is_active',
        'is_serialized',
        'is_lot_tracked',
        'image_path',
        'specifications',
    ];

    protected $casts = [
        'unit_cost' => 'decimal:4',
        'unit_price' => 'decimal:4',
        'conversion_factor' => 'decimal:4',
        'reorder_level' => 'decimal:2',
        'minimum_stock' => 'decimal:2',
        'maximum_stock' => 'decimal:2',
        'shelf_life_days' => 'integer',
        'is_controlled_substance' => 'boolean',
        'requires_prescription' => 'boolean',
        'requires_cold_chain' => 'boolean',
        'is_active' => 'boolean',
        'is_serialized' => 'boolean',
        'is_lot_tracked' => 'boolean',
        'specifications' => 'array',
    ];

    // =============================================================================
    // Accessors
    // =============================================================================

    public function getNameAttribute(): string
    {
        $locale = app()->getLocale();
        return $locale === 'ar' ? $this->name_ar : ($this->name_en ?: $this->name_ar);
    }

    public function getCategoryNameAttribute(): string
    {
        return self::CATEGORIES[$this->category] ?? $this->category;
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

    public function scopeOfCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopeControlledSubstances($query)
    {
        return $query->where('is_controlled_substance', true);
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
        if (!$this->conversion_factor || $this->conversion_factor == 0) {
            return $quantity;
        }
        return $quantity * $this->conversion_factor;
    }

    /**
     * تحويل الكمية للوحدة الرئيسية
     */
    public function convertToPrimaryUnit(float $quantity): float
    {
        if (!$this->conversion_factor || $this->conversion_factor == 0) {
            return $quantity;
        }
        return $quantity / $this->conversion_factor;
    }
}
