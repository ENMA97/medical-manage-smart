<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceRecord extends Model
{
    use HasFactory, HasUuid;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'employee_id',
        'shift_id',
        'attendance_date',
        'check_in_time',
        'check_out_time',
        'status',
        'late_minutes',
        'early_leave_minutes',
        'overtime_minutes',
        'worked_minutes',
        'source',
        'notes',
        'recorded_by',
    ];

    protected function casts(): array
    {
        return [
            'attendance_date' => 'date',
            'check_in_time' => 'datetime',
            'check_out_time' => 'datetime',
            'late_minutes' => 'integer',
            'early_leave_minutes' => 'integer',
            'overtime_minutes' => 'integer',
            'worked_minutes' => 'integer',
        ];
    }

    // ─── Relationships ───

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    // ─── Scopes ───

    public function scopeForMonth($query, int $year, int $month)
    {
        return $query->whereYear('attendance_date', $year)
            ->whereMonth('attendance_date', $month);
    }
}
