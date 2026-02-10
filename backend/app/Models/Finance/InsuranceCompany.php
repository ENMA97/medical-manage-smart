<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class InsuranceCompany extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'code',
        'name_ar',
        'name_en',
        'type',
        'contact_person',
        'phone',
        'email',
        'address',
        'discount_rate',
        'payment_terms_days',
        'is_active',
        'notes',
        'contract_start_date',
        'contract_end_date',
    ];

    protected $casts = [
        'discount_rate' => 'decimal:2',
        'payment_terms_days' => 'integer',
        'is_active' => 'boolean',
        'contract_start_date' => 'date',
        'contract_end_date' => 'date',
    ];

    // =============================================================================
    // Accessors
    // =============================================================================

    public function getNameAttribute(): string
    {
        $locale = app()->getLocale();
        return $locale === 'ar' ? $this->name_ar : ($this->name_en ?: $this->name_ar);
    }

    public function getIsContractValidAttribute(): bool
    {
        if (!$this->contract_end_date) {
            return true;
        }
        return $this->contract_end_date->isFuture();
    }

    // =============================================================================
    // Relationships
    // =============================================================================

    public function claims(): HasMany
    {
        return $this->hasMany(InsuranceClaim::class);
    }

    // =============================================================================
    // Scopes
    // =============================================================================

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeWithValidContract($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('contract_end_date')
                ->orWhere('contract_end_date', '>=', now());
        });
    }
}
