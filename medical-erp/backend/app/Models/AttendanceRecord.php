<?php

namespace App\Models;

use App\Traits\Auditable;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class AttendanceRecord extends Model
{
    use Auditable, HasFactory, HasUuid;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'employee_id',
        'date',
        'work_shift_id',
        'status',
        'check_in',
        'check_out',
        'actual_hours',
        'overtime_hours',
        'late_minutes',
        'early_departure_minutes',
        'check_in_method',
        'check_out_method',
        'check_in_latitude',
        'check_in_longitude',
        'check_out_latitude',
        'check_out_longitude',
        'notes',
        'is_manually_adjusted',
        'adjusted_by',
        'adjustment_reason',
        'leave_request_id',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'check_in' => 'datetime',
            'check_out' => 'datetime',
            'actual_hours' => 'decimal:2',
            'overtime_hours' => 'decimal:2',
            'late_minutes' => 'integer',
            'early_departure_minutes' => 'integer',
            'check_in_latitude' => 'decimal:7',
            'check_in_longitude' => 'decimal:7',
            'check_out_latitude' => 'decimal:7',
            'check_out_longitude' => 'decimal:7',
            'is_manually_adjusted' => 'boolean',
        ];
    }

    // ─── Relationships ───

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function workShift(): BelongsTo
    {
        return $this->belongsTo(WorkShift::class);
    }

    public function adjustedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'adjusted_by');
    }

    public function leaveRequest(): BelongsTo
    {
        return $this->belongsTo(LeaveRequest::class);
    }

    // ─── Scopes ───

    public function scopeForDate($query, $date)
    {
        return $query->where('date', $date);
    }

    public function scopePresent($query)
    {
        return $query->where('status', 'present');
    }

    public function scopeAbsent($query)
    {
        return $query->where('status', 'absent');
    }
}
