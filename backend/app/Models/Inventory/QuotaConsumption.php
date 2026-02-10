<?php

namespace App\Models\Inventory;

use App\Models\HR\Employee;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuotaConsumption extends Model
{
    use HasFactory, HasUuids;

    // لا يوجد updated_at - السجل غير قابل للتعديل
    public $timestamps = true;
    const UPDATED_AT = null;

    protected $fillable = [
        'quota_id',
        'movement_id',
        'employee_id',
        'period_start',
        'period_end',
        'consumed_amount',
        'quantity',
        'consumed_at',
        'notes',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'consumed_amount' => 'decimal:2',
        'quantity' => 'decimal:2',
        'consumed_at' => 'datetime',
    ];

    // =============================================================================
    // Accessors
    // =============================================================================

    public function getQuantityAttribute($value)
    {
        return $value ?? $this->attributes['consumed_amount'] ?? 0;
    }

    // =============================================================================
    // Relationships
    // =============================================================================

    public function quota(): BelongsTo
    {
        return $this->belongsTo(ItemQuota::class, 'quota_id');
    }

    public function movement(): BelongsTo
    {
        return $this->belongsTo(InventoryMovement::class, 'movement_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    // =============================================================================
    // Scopes
    // =============================================================================

    public function scopeInPeriod($query, $startDate, $endDate)
    {
        return $query->whereBetween('consumed_at', [$startDate, $endDate]);
    }

    public function scopeForQuota($query, string $quotaId)
    {
        return $query->where('quota_id', $quotaId);
    }
}
