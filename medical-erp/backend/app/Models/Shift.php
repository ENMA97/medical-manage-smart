<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shift extends Model
{
    use HasFactory, HasUuid;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'code',
        'name',
        'name_ar',
        'start_time',
        'end_time',
        'break_minutes',
        'grace_period_minutes',
        'is_overnight',
        'is_active',
        'description',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'break_minutes' => 'integer',
            'grace_period_minutes' => 'integer',
            'sort_order' => 'integer',
            'is_overnight' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    // ─── Relationships ───

    public function assignments(): HasMany
    {
        return $this->hasMany(ShiftAssignment::class);
    }

    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    // ─── Helpers ───

    /**
     * مدة الوردية بالدقائق (بعد خصم الاستراحة)
     */
    public function getDurationMinutesAttribute(): int
    {
        $start = strtotime($this->start_time);
        $end = strtotime($this->end_time);
        if ($end <= $start) {
            $end += 24 * 60 * 60; // وردية تمتد لليوم التالي
        }

        return max(0, intdiv($end - $start, 60) - $this->break_minutes);
    }
}
