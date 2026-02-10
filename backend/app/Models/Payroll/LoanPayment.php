<?php

namespace App\Models\Payroll;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoanPayment extends Model
{
    use HasFactory, HasUuids;

    // سجل غير قابل للتعديل (immutable)
    public $timestamps = true;
    const UPDATED_AT = null;

    protected $fillable = [
        'loan_id',
        'payroll_id',
        'amount',
        'payment_date',
        'remaining_after',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'date',
        'remaining_after' => 'decimal:2',
    ];

    // =============================================================================
    // Relationships
    // =============================================================================

    public function loan(): BelongsTo
    {
        return $this->belongsTo(EmployeeLoan::class, 'loan_id');
    }

    public function payroll(): BelongsTo
    {
        return $this->belongsTo(Payroll::class);
    }

    // =============================================================================
    // Scopes
    // =============================================================================

    public function scopeForLoan($query, string $loanId)
    {
        return $query->where('loan_id', $loanId);
    }

    public function scopeInPeriod($query, int $year, int $month)
    {
        return $query->whereYear('payment_date', $year)
            ->whereMonth('payment_date', $month);
    }
}
