<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class LeaveBalance extends Model
{
    use HasFactory, HasUuid;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'employee_id',
        'leave_type_id',
        'year',
        'total_entitled',
        'carried_forward',
        'additional_granted',
        'used',
        'pending',
        'remaining',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'total_entitled' => 'decimal:2',
            'carried_forward' => 'decimal:2',
            'additional_granted' => 'decimal:2',
            'used' => 'decimal:2',
            'pending' => 'decimal:2',
            'remaining' => 'decimal:2',
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
}
