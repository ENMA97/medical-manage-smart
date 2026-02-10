<?php

namespace App\Models\Inventory;

use App\Models\HR\Department;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ItemQuota extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'item_id',
        'department_id',
        'period_type',
        'period',
        'quota_limit',
        'quota_amount',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'quota_limit' => 'decimal:2',
        'quota_amount' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    // =============================================================================
    // Accessors
    // =============================================================================

    public function getQuotaLimitAttribute($value)
    {
        return $value ?? $this->attributes['quota_amount'] ?? 0;
    }

    public function getPeriodTypeAttribute($value)
    {
        return $value ?? $this->attributes['period'] ?? 'monthly';
    }

    // =============================================================================
    // Relationships
    // =============================================================================

    public function item(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'item_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function consumptions(): HasMany
    {
        return $this->hasMany(QuotaConsumption::class, 'quota_id');
    }

    // =============================================================================
    // Scopes
    // =============================================================================

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForDepartment($query, string $departmentId)
    {
        return $query->where('department_id', $departmentId);
    }

    // =============================================================================
    // Methods
    // =============================================================================

    public function getCurrentPeriodStart(): \Carbon\Carbon
    {
        $periodType = $this->period_type ?? $this->period ?? 'monthly';

        return match ($periodType) {
            'daily' => now()->startOfDay(),
            'weekly' => now()->startOfWeek(),
            'monthly' => now()->startOfMonth(),
            default => now()->startOfMonth(),
        };
    }

    public function getCurrentConsumption(): float
    {
        return $this->consumptions()
            ->where('consumed_at', '>=', $this->getCurrentPeriodStart())
            ->sum('quantity');
    }

    public function getRemainingQuota(): float
    {
        $limit = $this->quota_limit ?? $this->quota_amount ?? 0;
        return max(0, $limit - $this->getCurrentConsumption());
    }

    public function canConsume(float $quantity): bool
    {
        return $this->getRemainingQuota() >= $quantity;
    }
}
