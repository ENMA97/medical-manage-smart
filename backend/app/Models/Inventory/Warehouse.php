<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Warehouse extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    /**
     * أنواع المستودعات
     */
    public const TYPE_MAIN = 'main';
    public const TYPE_PHARMACY = 'pharmacy';
    public const TYPE_DEPARTMENT = 'department';
    public const TYPE_CRASH_CART = 'crash_cart';
    public const TYPE_CONSIGNMENT = 'consignment';
    public const TYPE_QUARANTINE = 'quarantine';
    public const TYPE_EXPIRED = 'expired';

    public const TYPES = [
        self::TYPE_MAIN => 'المستودع الرئيسي',
        self::TYPE_PHARMACY => 'صيدلية',
        self::TYPE_DEPARTMENT => 'قسم',
        self::TYPE_CRASH_CART => 'عربة الطوارئ',
        self::TYPE_CONSIGNMENT => 'أمانة',
        self::TYPE_QUARANTINE => 'حجر صحي',
        self::TYPE_EXPIRED => 'منتهي الصلاحية',
    ];

    protected $fillable = [
        'code',
        'name_ar',
        'name_en',
        'description',
        'type',
        'location',
        'department_id',
        'manager_id',
        'is_active',
        'requires_approval',
        'track_batch',
        'track_expiry',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'requires_approval' => 'boolean',
        'track_batch' => 'boolean',
        'track_expiry' => 'boolean',
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

    // =============================================================================
    // Relationships
    // =============================================================================

    public function stocks(): HasMany
    {
        return $this->hasMany(WarehouseStock::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class, 'from_warehouse_id')
            ->orWhere('to_warehouse_id', $this->id);
    }

    public function outgoingMovements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class, 'from_warehouse_id');
    }

    public function incomingMovements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class, 'to_warehouse_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(\App\Models\HR\Department::class);
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(\App\Models\HR\Employee::class, 'manager_id');
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

    public function scopeCrashCarts($query)
    {
        return $query->where('type', self::TYPE_CRASH_CART);
    }

    public function scopeMain($query)
    {
        return $query->where('type', self::TYPE_MAIN);
    }

    // =============================================================================
    // Methods
    // =============================================================================

    /**
     * الحصول على رصيد صنف معين
     */
    public function getStock(string $itemId): ?WarehouseStock
    {
        return $this->stocks()->where('item_id', $itemId)->first();
    }

    /**
     * الحصول على كمية صنف معين
     */
    public function getQuantity(string $itemId): float
    {
        return $this->stocks()->where('item_id', $itemId)->sum('quantity');
    }

    /**
     * التحقق من توفر كمية
     */
    public function hasStock(string $itemId, float $quantity): bool
    {
        return $this->getQuantity($itemId) >= $quantity;
    }

    /**
     * الأصناف منتهية الصلاحية
     */
    public function getExpiredItems()
    {
        return $this->stocks()
            ->whereNotNull('expiry_date')
            ->where('expiry_date', '<', now())
            ->where('quantity', '>', 0)
            ->with('item')
            ->get();
    }

    /**
     * الأصناف قريبة من الانتهاء
     */
    public function getExpiringItems(int $days = 30)
    {
        return $this->stocks()
            ->whereNotNull('expiry_date')
            ->where('expiry_date', '>', now())
            ->where('expiry_date', '<=', now()->addDays($days))
            ->where('quantity', '>', 0)
            ->with('item')
            ->get();
    }

    /**
     * الأصناف تحت حد الطلب
     */
    public function getLowStockItems()
    {
        return $this->stocks()
            ->whereHas('item', function ($query) {
                $query->whereColumn('warehouse_stocks.quantity', '<=', 'inventory_items.reorder_level');
            })
            ->with('item')
            ->get();
    }
}
