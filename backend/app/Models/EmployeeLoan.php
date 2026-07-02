<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class EmployeeLoan extends Model
{
    use HasFactory, HasUuid;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'employee_id',
        'loan_number',
        'loan_amount',
        'monthly_deduction',
        'remaining_amount',
        'total_installments',
        'paid_installments',
        'remaining_installments',
        'start_date',
        'expected_end_date',
        'reason',
        'status',
        'approved_by',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'loan_amount' => 'decimal:2',
            'monthly_deduction' => 'decimal:2',
            'remaining_amount' => 'decimal:2',
            'total_installments' => 'integer',
            'paid_installments' => 'integer',
            'remaining_installments' => 'integer',
            'start_date' => 'date',
            'expected_end_date' => 'date',
            'approved_at' => 'datetime',
        ];
    }

    // ─── Relationships ───

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function installments(): HasMany
    {
        return $this->hasMany(LoanInstallment::class, 'loan_id');
    }
}
