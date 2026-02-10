<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class MedicalService extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'code',
        'name_ar',
        'name_en',
        'description',
        'category',
        'cost_center_id',
        'base_price',
        'cost',
        'insurance_price',
        'doctor_commission_rate',
        'is_active',
        'requires_doctor',
        'duration_minutes',
    ];

    protected $casts = [
        'base_price' => 'decimal:2',
        'cost' => 'decimal:2',
        'insurance_price' => 'decimal:2',
        'doctor_commission_rate' => 'decimal:2',
        'is_active' => 'boolean',
        'requires_doctor' => 'boolean',
        'duration_minutes' => 'integer',
    ];

    // =============================================================================
    // Accessors
    // =============================================================================

    public function getNameAttribute(): string
    {
        $locale = app()->getLocale();
        return $locale === 'ar' ? $this->name_ar : ($this->name_en ?: $this->name_ar);
    }

    public function getProfitMarginAttribute(): float
    {
        if ($this->base_price <= 0) {
            return 0;
        }
        return round((($this->base_price - ($this->cost ?? 0)) / $this->base_price) * 100, 2);
    }

    // =============================================================================
    // Relationships
    // =============================================================================

    public function costCenter(): BelongsTo
    {
        return $this->belongsTo(CostCenter::class);
    }

    public function doctors(): BelongsToMany
    {
        return $this->belongsToMany(Doctor::class, 'doctor_services')
            ->withPivot('custom_fee', 'custom_commission_rate')
            ->withTimestamps();
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
}
