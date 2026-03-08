<?php

namespace App\Models;

use App\Traits\Auditable;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class LeaveRequest extends Model
{
    use Auditable, HasFactory, HasUuid, SoftDeletes;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'request_number',
        'employee_id',
        'leave_type_id',
        'leave_balance_id',
        'start_date',
        'end_date',
        'total_days',
        'resume_date',
        'substitute_employee_id',
        'substitute_approved',
        'reason',
        'reason_ar',
        'contact_during_leave',
        'address_during_leave',
        'status',
        'current_approval_step',
        'total_approval_steps',
        'cancelled_by',
        'cancelled_at',
        'cancellation_reason',
        'actual_return_date',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'resume_date' => 'date',
            'actual_return_date' => 'date',
            'cancelled_at' => 'datetime',
            'total_days' => 'integer',
            'current_approval_step' => 'integer',
            'total_approval_steps' => 'integer',
            'substitute_approved' => 'boolean',
        ];
    }

    // ─── Relationships ───

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }

    public function leaveBalance(): BelongsTo
    {
        return $this->belongsTo(LeaveBalance::class);
    }

    public function substituteEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'substitute_employee_id');
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(LeaveApproval::class);
    }
}
