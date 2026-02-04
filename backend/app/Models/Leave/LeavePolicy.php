<?php

namespace App\Models\Leave;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeavePolicy extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'leave_policies';

    protected $fillable = [
        'name',
        'name_ar',
        'contract_type',
        'leave_type_id',
        'days_per_year',
        'accrual_start_month',
        'accrual_method',
        'min_service_months',
        'additional_rules',
        'is_active',
    ];

    protected $casts = [
        'days_per_year' => 'integer',
        'accrual_start_month' => 'integer',
        'min_service_months' => 'integer',
        'additional_rules' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * أنواع العقود
     */
    public const CONTRACT_TYPES = [
        'full_time' => 'دوام كامل',
        'part_time' => 'دوام جزئي',
        'tamheer' => 'تمهير',
        'percentage' => 'نسبة',
        'locum' => 'بديل',
    ];

    /**
     * طرق الاستحقاق
     */
    public const ACCRUAL_METHODS = [
        'yearly' => 'سنوي',
        'monthly' => 'شهري',
        'daily' => 'يومي',
    ];

    /**
     * العلاقة مع نوع الإجازة
     */
    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }

    /**
     * Scope للسياسات النشطة
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope حسب نوع العقد
     */
    public function scopeForContractType($query, string $contractType)
    {
        return $query->where('contract_type', $contractType);
    }
}
