<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class LoanInstallment extends Model
{
    use HasFactory, HasUuid;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'loan_id',
        'payroll_item_id',
        'installment_number',
        'amount',
        'remaining_after',
        'due_date',
        'paid_date',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'installment_number' => 'integer',
            'amount' => 'decimal:2',
            'remaining_after' => 'decimal:2',
            'due_date' => 'date',
            'paid_date' => 'date',
        ];
    }

    // ─── Relationships ───

    public function loan(): BelongsTo
    {
        return $this->belongsTo(EmployeeLoan::class, 'loan_id');
    }
}
