<?php

namespace App\Models\Roster;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ShiftPattern extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    /**
     * أنواع الورديات
     */
    public const TYPE_MORNING = 'morning';
    public const TYPE_EVENING = 'evening';
    public const TYPE_NIGHT = 'night';
    public const TYPE_ONCALL = 'on_call';

    public const TYPES = [
        self::TYPE_MORNING => 'صباحي',
        self::TYPE_EVENING => 'مسائي',
        self::TYPE_NIGHT => 'ليلي',
        self::TYPE_ONCALL => 'تحت الطلب',
    ];

    protected $fillable = [
        'code',
        'name_ar',
        'name_en',
        'type',
        'start_time',
        'end_time',
        'break_duration_minutes',
        'working_hours',
        'color_code',
        'is_overnight',
        'is_active',
        'applicable_days',
        'department_ids',
        'position_ids',
        'minimum_staff',
        'settings',
    ];

    protected $casts = [
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
        'break_duration_minutes' => 'integer',
        'working_hours' => 'decimal:2',
        'is_overnight' => 'boolean',
        'is_active' => 'boolean',
        'applicable_days' => 'array',
        'department_ids' => 'array',
        'position_ids' => 'array',
        'minimum_staff' => 'integer',
        'settings' => 'array',
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
        if (!$this->start_time || !$this->end_time) {
            return 0;
        }

        $start = \Carbon\Carbon::parse($this->start_time);
        $end = \Carbon\Carbon::parse($this->end_time);

        if ($this->is_overnight && $end->lt($start)) {
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

    public function scopeForDepartment($query, string $departmentId)
    {
        return $query->where(function ($q) use ($departmentId) {
            $q->whereNull('department_ids')
              ->orWhereJsonContains('department_ids', $departmentId);
        });
    }

    public function scopeForDay($query, string $day)
    {
        return $query->where(function ($q) use ($day) {
            $q->whereNull('applicable_days')
              ->orWhereJsonContains('applicable_days', $day);
        });
    }

    // =============================================================================
    // Methods
    // =============================================================================

    /**
     * التحقق من تطبيق الوردية على يوم معين
     */
    public function isApplicableOnDay(string $day): bool
    {
        if (empty($this->applicable_days)) {
            return true;
        }
        return in_array($day, $this->applicable_days);
    }

    /**
     * التحقق من تطبيق الوردية على قسم معين
     */
    public function isApplicableForDepartment(string $departmentId): bool
    {
        if (empty($this->department_ids)) {
            return true;
        }
        return in_array($departmentId, $this->department_ids);
    }
}
