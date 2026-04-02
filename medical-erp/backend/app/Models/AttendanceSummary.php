<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class AttendanceSummary extends Model
{
    use HasFactory, HasUuid;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'employee_id',
        'month',
        'year',
        'working_days',
        'present_days',
        'absent_days',
        'late_days',
        'early_departure_days',
        'leave_days',
        'holiday_days',
        'day_off_days',
        'total_working_hours',
        'total_overtime_hours',
        'total_late_minutes',
        'total_early_minutes',
        'total_permission_hours',
        'attendance_rate',
        'deduction_amount',
        'overtime_amount',
        'is_finalized',
        'finalized_by',
        'finalized_at',
    ];

    protected function casts(): array
    {
        return [
            'month' => 'integer',
            'year' => 'integer',
            'working_days' => 'integer',
            'present_days' => 'integer',
            'absent_days' => 'integer',
            'late_days' => 'integer',
            'early_departure_days' => 'integer',
            'leave_days' => 'integer',
            'holiday_days' => 'integer',
            'day_off_days' => 'integer',
            'total_working_hours' => 'decimal:2',
            'total_overtime_hours' => 'decimal:2',
            'total_late_minutes' => 'integer',
            'total_early_minutes' => 'integer',
            'total_permission_hours' => 'decimal:2',
            'attendance_rate' => 'decimal:2',
            'deduction_amount' => 'decimal:2',
            'overtime_amount' => 'decimal:2',
            'is_finalized' => 'boolean',
            'finalized_at' => 'datetime',
        ];
    }

    // ─── Relationships ───

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function finalizedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'finalized_by');
    }
}
