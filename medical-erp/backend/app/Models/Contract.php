<?php

namespace App\Models;

use App\Traits\Auditable;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class Contract extends Model
{
    use Auditable, HasFactory, HasUuid, SoftDeletes;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'employee_id',
        'contract_number',
        'contract_type',
        'status',
        'start_date',
        'end_date',
        'duration_months',
        'probation_days',
        'probation_end_date',
        'basic_salary',
        'housing_allowance',
        'transport_allowance',
        'food_allowance',
        'phone_allowance',
        'other_allowances',
        'total_salary',
        'percentage_rate',
        'annual_leave_days',
        'sick_leave_days',
        'notice_period_days',
        'terms_and_conditions',
        'benefits',
        'special_clauses',
        'contract_file',
        'created_by',
        'approved_by',
        'approved_at',
        'previous_contract_id',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'probation_end_date' => 'date',
            'approved_at' => 'datetime',
            'basic_salary' => 'decimal:2',
            'housing_allowance' => 'decimal:2',
            'transport_allowance' => 'decimal:2',
            'food_allowance' => 'decimal:2',
            'phone_allowance' => 'decimal:2',
            'other_allowances' => 'decimal:2',
            'total_salary' => 'decimal:2',
            'percentage_rate' => 'decimal:2',
            'benefits' => 'array',
            'duration_months' => 'integer',
            'probation_days' => 'integer',
            'annual_leave_days' => 'integer',
            'sick_leave_days' => 'integer',
            'notice_period_days' => 'integer',
        ];
    }

    // ─── Relationships ───

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function previousContract(): BelongsTo
    {
        return $this->belongsTo(Contract::class, 'previous_contract_id');
    }

    public function alerts(): HasMany
    {
        return $this->hasMany(ContractAlert::class);
    }

    public function renewals(): HasMany
    {
        return $this->hasMany(ContractRenewal::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
