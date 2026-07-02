<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class Resignation extends Model
{
    use HasFactory, HasUuid, SoftDeletes;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'employee_id',
        'contract_id',
        'type',
        'request_date',
        'last_working_day',
        'effective_date',
        'notice_period_days',
        'reason',
        'reason_ar',
        'status',
        'direct_manager_id',
        'manager_decision',
        'manager_decision_at',
        'manager_remarks',
        'hr_reviewer_id',
        'hr_decision',
        'hr_decision_at',
        'hr_remarks',
        'final_approver_id',
        'final_decision',
        'final_decision_at',
        'final_remarks',
        'custody_cleared',
        'financial_cleared',
        'it_cleared',
        'admin_cleared',
        'clearance_completed_at',
        'end_of_service_id',
        'notes',
        'attachment',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'request_date' => 'date',
            'last_working_day' => 'date',
            'effective_date' => 'date',
            'clearance_completed_at' => 'date',
            'manager_decision_at' => 'datetime',
            'hr_decision_at' => 'datetime',
            'final_decision_at' => 'datetime',
            'notice_period_days' => 'integer',
            'custody_cleared' => 'boolean',
            'financial_cleared' => 'boolean',
            'it_cleared' => 'boolean',
            'admin_cleared' => 'boolean',
        ];
    }

    // ─── Relationships ───

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }
}
