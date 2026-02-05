<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryMovement extends Model
{
    use HasFactory, HasUuids;

    /**
     * أنواع الحركات
     */
    public const TYPE_RECEIVE = 'receive';
    public const TYPE_ISSUE = 'issue';
    public const TYPE_TRANSFER = 'transfer';
    public const TYPE_ADJUSTMENT = 'adjustment';
    public const TYPE_RETURN = 'return';
    public const TYPE_DISPOSAL = 'disposal';
    public const TYPE_EXPIRED = 'expired';

    public const TYPES = [
        self::TYPE_RECEIVE => 'استلام',
        self::TYPE_ISSUE => 'صرف',
        self::TYPE_TRANSFER => 'تحويل',
        self::TYPE_ADJUSTMENT => 'تسوية',
        self::TYPE_RETURN => 'مرتجع',
        self::TYPE_DISPOSAL => 'إتلاف',
        self::TYPE_EXPIRED => 'منتهي الصلاحية',
    ];

    /**
     * حالات الحركة
     */
    public const STATUS_DRAFT = 'draft';
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    // الجدول غير قابل للتعديل (immutable audit)
    public $timestamps = true;
    const UPDATED_AT = null;

    protected $fillable = [
        'movement_number',
        'type',
        'status',
        'item_id',
        'from_warehouse_id',
        'to_warehouse_id',
        'quantity',
        'unit_cost',
        'total_cost',
        'batch_number',
        'lot_number',
        'expiry_date',
        'reference_type',
        'reference_id',
        'reason',
        'notes',
        'created_by',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'unit_cost' => 'decimal:4',
        'total_cost' => 'decimal:4',
        'expiry_date' => 'date',
        'approved_at' => 'datetime',
    ];

    // =============================================================================
    // Accessors
    // =============================================================================

    public function getTypeNameAttribute(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    /**
     * وصف الحركة
     */
    public function getDescriptionAttribute(): string
    {
        $item = $this->item?->name ?? 'صنف';
        $qty = $this->quantity;

        return match ($this->type) {
            self::TYPE_RECEIVE => "استلام {$qty} من {$item}",
            self::TYPE_ISSUE => "صرف {$qty} من {$item}",
            self::TYPE_TRANSFER => "تحويل {$qty} من {$item}",
            self::TYPE_ADJUSTMENT => "تسوية {$qty} من {$item}",
            self::TYPE_RETURN => "مرتجع {$qty} من {$item}",
            self::TYPE_DISPOSAL => "إتلاف {$qty} من {$item}",
            self::TYPE_EXPIRED => "انتهاء صلاحية {$qty} من {$item}",
            default => "حركة {$qty} من {$item}",
        };
    }

    // =============================================================================
    // Relationships
    // =============================================================================

    public function item(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'item_id');
    }

    public function fromWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'from_warehouse_id');
    }

    public function toWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'to_warehouse_id');
    }

    // =============================================================================
    // Scopes
    // =============================================================================

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeInDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    public function scopeForWarehouse($query, string $warehouseId)
    {
        return $query->where(function ($q) use ($warehouseId) {
            $q->where('from_warehouse_id', $warehouseId)
              ->orWhere('to_warehouse_id', $warehouseId);
        });
    }

    // =============================================================================
    // Boot
    // =============================================================================

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($movement) {
            if (!$movement->movement_number) {
                $movement->movement_number = self::generateMovementNumber($movement->type);
            }
            if (!$movement->total_cost && $movement->unit_cost && $movement->quantity) {
                $movement->total_cost = $movement->unit_cost * $movement->quantity;
            }
        });
    }

    protected static function generateMovementNumber(string $type): string
    {
        $prefix = match ($type) {
            self::TYPE_RECEIVE => 'RCV',
            self::TYPE_ISSUE => 'ISS',
            self::TYPE_TRANSFER => 'TRF',
            self::TYPE_ADJUSTMENT => 'ADJ',
            self::TYPE_RETURN => 'RTN',
            self::TYPE_DISPOSAL => 'DSP',
            self::TYPE_EXPIRED => 'EXP',
            default => 'MOV',
        };

        $date = date('Ymd');
        $count = self::where('movement_number', 'like', "{$prefix}{$date}%")->count() + 1;

        return $prefix . $date . str_pad($count, 4, '0', STR_PAD_LEFT);
    }
}
