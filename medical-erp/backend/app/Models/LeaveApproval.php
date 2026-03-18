<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class LeaveApproval extends Model
{
    use HasFactory, HasUuid;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'leave_request_id',
        'step_order',
        'approval_role',
        'approver_id',
        'status',
        'comment',
        'comment_ar',
        'actioned_at',
        'balance_before',
        'balance_after',
        'balance_sufficient',
        'delegated_to',
        'delegated_by',
    ];

    protected function casts(): array
    {
        return [
            'actioned_at' => 'datetime',
            'step_order' => 'integer',
            'balance_before' => 'decimal:2',
            'balance_after' => 'decimal:2',
            'balance_sufficient' => 'boolean',
        ];
    }

    // ─── Relationships ───

    public function leaveRequest(): BelongsTo
    {
        return $this->belongsTo(LeaveRequest::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }
}
