<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class WorkShift extends Model
{
    use HasFactory, HasUuid;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'name',
        'name_ar',
        'code',
        'type',
        'start_time',
        'end_time',
        'break_start',
        'break_end',
        'break_duration_minutes',
        'total_hours',
        'grace_period_minutes',
        'early_departure_minutes',
        'next_day_end',
        'is_active',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'break_duration_minutes' => 'decimal:2',
            'total_hours' => 'decimal:2',
            'grace_period_minutes' => 'integer',
            'early_departure_minutes' => 'integer',
            'next_day_end' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    // ─── Relationships ───

    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class, 'work_shift_id');
    }

    // ─── Scopes ───

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
