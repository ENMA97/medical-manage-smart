<?php

namespace App\Models\Finance;

use App\Models\HR\Department;
use App\Models\HR\Employee;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Doctor extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'employee_id',
        'doctor_number',
        'license_number',
        'specialization_ar',
        'specialization_en',
        'qualification',
        'department_id',
        'commission_rate',
        'consultation_fee',
        'is_consultant',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'commission_rate' => 'decimal:2',
        'consultation_fee' => 'decimal:2',
        'is_consultant' => 'boolean',
        'is_active' => 'boolean',
    ];

    // =============================================================================
    // Accessors
    // =============================================================================

    public function getFullNameAttribute(): string
    {
        return $this->employee?->full_name_ar ?? $this->doctor_number;
    }

    public function getSpecializationAttribute(): string
    {
        $locale = app()->getLocale();
        return $locale === 'ar' ? $this->specialization_ar : ($this->specialization_en ?: $this->specialization_ar);
    }

    // =============================================================================
    // Relationships
    // =============================================================================

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function services(): BelongsToMany
    {
        return $this->belongsToMany(MedicalService::class, 'doctor_services')
            ->withPivot('custom_fee', 'custom_commission_rate')
            ->withTimestamps();
    }

    public function commissions(): HasMany
    {
        return $this->hasMany(CommissionAdjustment::class);
    }

    // =============================================================================
    // Scopes
    // =============================================================================

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeConsultants($query)
    {
        return $query->where('is_consultant', true);
    }
}
