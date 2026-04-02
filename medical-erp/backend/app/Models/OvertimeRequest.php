<?php

namespace App\Models;

use App\Traits\Auditable;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class OvertimeRequest extends Model
{
    use Auditable, HasFactory, HasUuid;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'request_number',
        'employee_id',
        'date',
        'start_time',
        'end_time',
        'hours',
        'rate_multiplier',
        'reason',
        'reason_ar',
        'status',
        'approved_by',
        'approved_at',
        'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'hours' => 'decimal:2',
            'rate_multiplier' => 'decimal:2',
            'approved_at' => 'datetime',
        ];
    }

    // ─── Relationships ───

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function approvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
