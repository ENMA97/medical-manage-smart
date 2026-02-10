<?php

namespace App\Models\Roster;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
class ShiftPattern extends Model
{
    use HasFactory, HasUuids;

    /**
     * أنواع الورديات
     */
    public const TYPE_MORNING = 'morning';
    public const TYPE_EVENING = 'evening';
    public const TYPE_NIGHT = 'night';
    public const TYPE_SPLIT = 'split';

    public const TYPES = [
        self::TYPE_MORNING => 'صباحي',
        self::TYPE_EVENING => 'مسائي',
        self::TYPE_NIGHT => 'ليلي',
        self::TYPE_SPLIT => 'متقطع',
    ];

    protected $fillable = [
        'code',
        'name_ar',
        'name_en',
        'description',
        'type',
        'start_time',
        'end_time',
        'break_start',
        'break_end',
        'break_duration_minutes',
        'scheduled_hours',
        'color_code',
        'is_active',
    ];

    protected $casts = [
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
        'break_start' => 'datetime:H:i',
        'break_end' => 'datetime:H:i',
        'break_duration_minutes' => 'decimal:2',
        'scheduled_hours' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    // =============================================================================
    // Accessors
    // =============================================================================

    public function getNameAttribute(): string
    {
        $locale = app()->getLocale();
        return $locale === 'ar' ? $this->name_ar : ($this->name_en ?: $this->name_ar);
    }

    public function getTypeNameAttribute(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    /**
     * مدة الوردية بالساعات
     */
    public function getDurationHoursAttribute(): float
    {
        if ($this->scheduled_hours) {
            return (float) $this->scheduled_hours;
        }

        if (!$this->start_time || !$this->end_time) {
            return 0;
        }

        $start = \Carbon\Carbon::parse($this->start_time);
        $end = \Carbon\Carbon::parse($this->end_time);

        // إذا كانت نهاية الوردية قبل بدايتها فهي وردية ليلية
        if ($end->lt($start)) {
            $end->addDay();
        }

        $totalMinutes = $start->diffInMinutes($end);
        $workMinutes = $totalMinutes - ($this->break_duration_minutes ?? 0);

        return round($workMinutes / 60, 2);
    }

    /**
     * وصف الوردية
     */
    public function getDisplayNameAttribute(): string
    {
        $start = $this->start_time ? \Carbon\Carbon::parse($this->start_time)->format('H:i') : '';
        $end = $this->end_time ? \Carbon\Carbon::parse($this->end_time)->format('H:i') : '';
        return "{$this->name} ({$start} - {$end})";
    }

    // =============================================================================
    // Relationships
    // =============================================================================

    public function assignments(): HasMany
    {
        return $this->hasMany(RosterAssignment::class, 'shift_pattern_id');
    }

    // =============================================================================
    // Scopes
    // =============================================================================

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

}
