<?php

namespace App\Models\System;

use App\Models\Inventory\InventoryItem;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseRequestItem extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'purchase_request_id',
        'item_id',
        'requested_quantity',
        'quantity',
        'approved_quantity',
        'received_quantity',
        'estimated_unit_price',
        'actual_unit_price',
        'total_price',
        'notes',
    ];

    protected $casts = [
        'requested_quantity' => 'decimal:2',
        'quantity' => 'decimal:2',
        'approved_quantity' => 'decimal:2',
        'received_quantity' => 'decimal:2',
        'estimated_unit_price' => 'decimal:2',
        'actual_unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
    ];

    // =============================================================================
    // Accessors
    // =============================================================================

    public function getQuantityAttribute($value)
    {
        return $value ?? $this->attributes['requested_quantity'] ?? 0;
    }

    public function getEstimatedTotalAttribute(): float
    {
        $qty = $this->requested_quantity ?? $this->quantity ?? 0;
        $price = $this->estimated_unit_price ?? 0;
        return $qty * $price;
    }

    public function getActualTotalAttribute(): float
    {
        $qty = $this->received_quantity ?? $this->approved_quantity ?? $this->requested_quantity ?? 0;
        $price = $this->actual_unit_price ?? $this->estimated_unit_price ?? 0;
        return $qty * $price;
    }

    // =============================================================================
    // Relationships
    // =============================================================================

    public function purchaseRequest(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequest::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'item_id');
    }
}
