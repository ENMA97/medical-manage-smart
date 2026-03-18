<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class Payroll extends Model
{
    use HasFactory, HasUuid;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'payroll_number',
        'month',
        'year',
        'status',
        'total_basic_salary',
        'total_allowances',
        'total_additions',
        'total_deductions',
        'total_overtime',
        'total_gosi_employee',
        'total_gosi_employer',
        'total_gross_salary',
        'total_net_salary',
        'employees_count',
        'payment_date',
        'notes',
        'created_by',
        'reviewed_by',
        'reviewed_at',
        'approved_by',
        'approved_at',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'month' => 'integer',
            'year' => 'integer',
            'employees_count' => 'integer',
            'payment_date' => 'date',
            'reviewed_at' => 'datetime',
            'approved_at' => 'datetime',
            'paid_at' => 'datetime',
            'total_basic_salary' => 'decimal:2',
            'total_allowances' => 'decimal:2',
            'total_additions' => 'decimal:2',
            'total_deductions' => 'decimal:2',
            'total_overtime' => 'decimal:2',
            'total_gosi_employee' => 'decimal:2',
            'total_gosi_employer' => 'decimal:2',
            'total_gross_salary' => 'decimal:2',
            'total_net_salary' => 'decimal:2',
        ];
    }

    // ─── Relationships ───

    public function items(): HasMany
    {
        return $this->hasMany(PayrollItem::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
