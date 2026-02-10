<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WarehouseStock extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'warehouse_id',
        'item_id',
        'batch_number',
        'expiry_date',
        'quantity',
        'reserved_quantity',
        'available_quantity',
        'cost_price',
        'location_in_warehouse',
        'version',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'reserved_quantity' => 'decimal:2',
        'available_quantity' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'expiry_date' => 'date',
        'version' => 'integer',
    ];

    // =============================================================================
    // Accessors
    // =============================================================================

    /**
     * حساب الكمية المتاحة ديناميكياً
     */
    public function calculateAvailableQuantity(): float
    {
        return max(0, $this->quantity - ($this->reserved_quantity ?? 0));
    }

    /**
     * هل منتهي الصلاحية
     */
    public function getIsExpiredAttribute(): bool
    {
        return $this->expiry_date && $this->expiry_date->isPast();
    }

    /**
     * هل قريب من الانتهاء (30 يوم)
     */
    public function getIsExpiringAttribute(): bool
    {
        if (!$this->expiry_date) return false;
        return $this->expiry_date->isFuture() && $this->expiry_date->diffInDays(now()) <= 30;
    }

    /**
     * الأيام المتبقية للصلاحية
     */
    public function getDaysToExpiryAttribute(): ?int
    {
        if (!$this->expiry_date) return null;
        return max(0, now()->diffInDays($this->expiry_date, false));
    }

    /**
     * القيمة الإجمالية
     */
    public function getTotalValueAttribute(): float
    {
        return $this->quantity * ($this->cost_price ?? 0);
    }

    // =============================================================================
    // Relationships
    // =============================================================================

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'item_id');
    }

    // =============================================================================
    // Scopes
    // =============================================================================

    public function scopeWithStock($query)
    {
        return $query->where('quantity', '>', 0);
    }

    public function scopeExpired($query)
    {
        return $query->whereNotNull('expiry_date')
            ->where('expiry_date', '<', now());
    }

    public function scopeExpiringSoon($query, int $days = 30)
    {
        return $query->whereNotNull('expiry_date')
            ->where('expiry_date', '>', now())
            ->where('expiry_date', '<=', now()->addDays($days));
    }

    public function scopeByFefo($query)
    {
        return $query->orderBy('expiry_date', 'asc')
            ->orderBy('created_at', 'asc');
    }

    public function scopeByFifo($query)
    {
        return $query->orderBy('created_at', 'asc');
    }

    // =============================================================================
    // Methods
    // =============================================================================

    /**
     * حجز كمية
     */
    public function reserve(float $quantity): bool
    {
        if ($quantity > $this->available_quantity) {
            return false;
        }

        $this->reserved_quantity = ($this->reserved_quantity ?? 0) + $quantity;
        return $this->save();
    }

    /**
     * إلغاء حجز
     */
    public function unreserve(float $quantity): bool
    {
        $this->reserved_quantity = max(0, ($this->reserved_quantity ?? 0) - $quantity);
        return $this->save();
    }

    /**
     * إضافة كمية مع قفل تفاؤلي
     */
    public function addQuantity(float $quantity, ?int $expectedVersion = null): bool
    {
        if ($expectedVersion !== null && $this->version !== $expectedVersion) {
            throw new \Exception('تم تعديل الرصيد من قبل مستخدم آخر');
        }

        $this->quantity += $quantity;
        $this->version++;
        return $this->save();
    }

    /**
     * خصم كمية مع قفل تفاؤلي
     */
    public function subtractQuantity(float $quantity, ?int $expectedVersion = null): bool
    {
        if ($expectedVersion !== null && $this->version !== $expectedVersion) {
            throw new \Exception('تم تعديل الرصيد من قبل مستخدم آخر');
        }

        if ($quantity > $this->available_quantity) {
            throw new \Exception('الكمية المطلوبة أكبر من المتاح');
        }

        $this->quantity -= $quantity;
        $this->version++;
        return $this->save();
    }
}
