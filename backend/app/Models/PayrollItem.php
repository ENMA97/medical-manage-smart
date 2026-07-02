<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class PayrollItem extends Model
{
    use HasFactory, HasUuid;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'payroll_id',
        'employee_id',
        'contract_id',
        'basic_salary',
        'housing_allowance',
        'transport_allowance',
        'food_allowance',
        'phone_allowance',
        'other_allowances',
        'overtime_amount',
        'bonus',
        'commission',
        'custom_additions',
        'gosi_employee',
        'gosi_employer',
        'absence_deduction',
        'late_deduction',
        'loan_deduction',
        'other_deductions',
        'custom_deductions',
        'gross_salary',
        'total_deductions',
        'net_salary',
        'total_working_days',
        'actual_working_days',
        'absent_days',
        'late_days',
        'overtime_hours',
        'bank_name',
        'iban',
        'calculation_details',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'basic_salary' => 'decimal:2',
            'housing_allowance' => 'decimal:2',
            'transport_allowance' => 'decimal:2',
            'food_allowance' => 'decimal:2',
            'phone_allowance' => 'decimal:2',
            'other_allowances' => 'decimal:2',
            'overtime_amount' => 'decimal:2',
            'bonus' => 'decimal:2',
            'commission' => 'decimal:2',
            'custom_additions' => 'decimal:2',
            'gosi_employee' => 'decimal:2',
            'gosi_employer' => 'decimal:2',
            'absence_deduction' => 'decimal:2',
            'late_deduction' => 'decimal:2',
            'loan_deduction' => 'decimal:2',
            'other_deductions' => 'decimal:2',
            'custom_deductions' => 'decimal:2',
            'gross_salary' => 'decimal:2',
            'total_deductions' => 'decimal:2',
            'net_salary' => 'decimal:2',
            'total_working_days' => 'integer',
            'actual_working_days' => 'integer',
            'absent_days' => 'integer',
            'late_days' => 'integer',
            'overtime_hours' => 'integer',
            'calculation_details' => 'array',
        ];
    }

    // ─── Relationships ───

    public function payroll(): BelongsTo
    {
        return $this->belongsTo(Payroll::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
