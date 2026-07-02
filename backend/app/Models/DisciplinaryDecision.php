<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class DisciplinaryDecision extends Model
{
    use HasFactory, HasUuids, Auditable, SoftDeletes;

    protected $fillable = [
        'decision_number', 'violation_id', 'committee_id', 'employee_id',
        'penalty_type', 'penalty_type_ar', 'penalty_details', 'penalty_details_ar',
        'deduction_amount', 'deduction_days', 'suspension_days',
        'effective_date', 'end_date', 'justification', 'justification_ar',
        'labor_law_reference', 'suggested_penalty', 'suggested_penalty_ar',
        'status', 'decided_by', 'decided_at', 'approved_by', 'approved_at',
        'notified_at', 'employee_acknowledged', 'acknowledged_at',
        'appeal_text', 'appeal_date', 'appeal_status', 'appeal_result',
        'notes',
    ];

    protected $casts = [
        'effective_date' => 'date',
        'end_date' => 'date',
        'decided_at' => 'datetime',
        'approved_at' => 'datetime',
        'notified_at' => 'datetime',
        'acknowledged_at' => 'datetime',
        'appeal_date' => 'datetime',
        'employee_acknowledged' => 'boolean',
        'deduction_amount' => 'decimal:2',
    ];

    public function violation(): BelongsTo
    {
        return $this->belongsTo(Violation::class);
    }

    public function committee(): BelongsTo
    {
        return $this->belongsTo(InvestigationCommittee::class, 'committee_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function decidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
