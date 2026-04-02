<?php

namespace App\Models;

use App\Traits\Auditable;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class PermissionRequest extends Model
{
    use Auditable, HasFactory, HasUuid;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'request_number',
        'employee_id',
        'date',
        'departure_time',
        'return_time',
        'actual_return_time',
        'hours',
        'type',
        'reason',
        'reason_ar',
        'status',
        'approved_by',
        'approved_at',
        'rejection_reason',
        'monthly_total_hours',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'hours' => 'decimal:2',
            'monthly_total_hours' => 'decimal:2',
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
