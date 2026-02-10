<?php

namespace App\Models\Roster;

use App\Models\HR\Department;
use App\Models\HR\Position;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RosterValidationRule extends Model
{
    use HasFactory, HasUuids;

    /**
     * أنواع القواعد
     */
    public const TYPE_MAX_HOURS = 'max_hours';
    public const TYPE_MIN_REST = 'min_rest';
    public const TYPE_CONSECUTIVE_DAYS = 'consecutive_days';
    public const TYPE_SKILL_COVERAGE = 'skill_coverage';
    public const TYPE_OVERLAP = 'overlap';

    public const TYPES = [
        self::TYPE_MAX_HOURS => 'الحد الأقصى للساعات',
        self::TYPE_MIN_REST => 'الحد الأدنى للراحة',
        self::TYPE_CONSECUTIVE_DAYS => 'الأيام المتتالية',
        self::TYPE_SKILL_COVERAGE => 'تغطية المهارات',
        self::TYPE_OVERLAP => 'التداخل',
    ];

    /**
     * مستويات الخطورة
     */
    public const SEVERITY_ERROR = 'error';
    public const SEVERITY_WARNING = 'warning';
    public const SEVERITY_INFO = 'info';

    public const SEVERITIES = [
        self::SEVERITY_ERROR => 'خطأ',
        self::SEVERITY_WARNING => 'تحذير',
        self::SEVERITY_INFO => 'معلومات',
    ];

    protected $fillable = [
        'code',
        'name',
        'name_ar',
        'name_en',
        'description',
        'type',
        'rule_type',
        'rule_config',
        'parameters',
        'department_id',
        'position_id',
        'error_message_ar',
        'error_message_en',
        'severity',
        'priority',
        'is_active',
        'applies_to_positions',
        'applies_to_departments',
    ];

    protected $casts = [
        'rule_config' => 'array',
        'parameters' => 'array',
        'priority' => 'integer',
        'is_active' => 'boolean',
        'applies_to_positions' => 'array',
        'applies_to_departments' => 'array',
    ];

    // =============================================================================
    // Accessors
    // =============================================================================

    public function getNameAttribute(): string
    {
        $locale = app()->getLocale();
        return $locale === 'ar' ? ($this->name_ar ?? $this->attributes['name'] ?? '') : ($this->name_en ?? $this->name_ar ?? $this->attributes['name'] ?? '');
    }

    public function getTypeNameAttribute(): string
    {
        return self::TYPES[$this->type ?? $this->rule_type] ?? ($this->type ?? $this->rule_type);
    }

    public function getSeverityNameAttribute(): string
    {
        return self::SEVERITIES[$this->severity] ?? $this->severity;
    }

    public function getErrorMessageAttribute(): string
    {
        $locale = app()->getLocale();
        return $locale === 'ar' ? ($this->error_message_ar ?? '') : ($this->error_message_en ?? $this->error_message_ar ?? '');
    }

    // =============================================================================
    // Relationships
    // =============================================================================

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
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
        return $query->where('type', $type)->orWhere('rule_type', $type);
    }

    public function scopeErrors($query)
    {
        return $query->where('severity', self::SEVERITY_ERROR);
    }

    public function scopeWarnings($query)
    {
        return $query->where('severity', self::SEVERITY_WARNING);
    }

    public function scopeApplicableTo($query, ?string $departmentId = null, ?string $positionId = null)
    {
        return $query->where(function ($q) use ($departmentId, $positionId) {
            $q->whereNull('department_id')
                ->orWhere('department_id', $departmentId);
        })->where(function ($q) use ($positionId) {
            $q->whereNull('position_id')
                ->orWhere('position_id', $positionId);
        });
    }

    // =============================================================================
    // Methods
    // =============================================================================

    /**
     * التحقق من قاعدة
     */
    public function validate(RosterAssignment $assignment): ?array
    {
        $config = $this->rule_config ?? $this->parameters ?? [];

        switch ($this->type ?? $this->rule_type) {
            case self::TYPE_MAX_HOURS:
                return $this->validateMaxHours($assignment, $config);

            case self::TYPE_MIN_REST:
                return $this->validateMinRest($assignment, $config);

            case self::TYPE_CONSECUTIVE_DAYS:
                return $this->validateConsecutiveDays($assignment, $config);

            case self::TYPE_OVERLAP:
                return $this->validateOverlap($assignment);

            default:
                return null;
        }
    }

    protected function validateMaxHours(RosterAssignment $assignment, array $config): ?array
    {
        $maxHoursPerWeek = $config['max_hours_per_week'] ?? 48;

        $weekStart = $assignment->assignment_date->startOfWeek();
        $weekEnd = $assignment->assignment_date->endOfWeek();

        $totalHours = RosterAssignment::where('employee_id', $assignment->employee_id)
            ->whereBetween('assignment_date', [$weekStart, $weekEnd])
            ->sum('scheduled_hours');

        if ($totalHours > $maxHoursPerWeek) {
            return [
                'rule' => $this->code ?? $this->id,
                'severity' => $this->severity,
                'message' => $this->error_message,
                'data' => ['total_hours' => $totalHours, 'max_hours' => $maxHoursPerWeek],
            ];
        }

        return null;
    }

    protected function validateMinRest(RosterAssignment $assignment, array $config): ?array
    {
        $minRestHours = $config['min_rest_hours'] ?? 11;

        $previousAssignment = RosterAssignment::where('employee_id', $assignment->employee_id)
            ->where('assignment_date', $assignment->assignment_date->subDay())
            ->where('type', '!=', 'off')
            ->first();

        if (!$previousAssignment || !$previousAssignment->scheduled_end || !$assignment->scheduled_start) {
            return null;
        }

        $restHours = $previousAssignment->scheduled_end->diffInHours($assignment->scheduled_start);

        if ($restHours < $minRestHours) {
            return [
                'rule' => $this->code ?? $this->id,
                'severity' => $this->severity,
                'message' => $this->error_message,
                'data' => ['rest_hours' => $restHours, 'min_rest_hours' => $minRestHours],
            ];
        }

        return null;
    }

    protected function validateConsecutiveDays(RosterAssignment $assignment, array $config): ?array
    {
        $maxConsecutiveDays = $config['max_consecutive_days'] ?? 6;

        $consecutiveDays = 1;
        $checkDate = $assignment->assignment_date->copy()->subDay();

        while ($consecutiveDays <= $maxConsecutiveDays) {
            $exists = RosterAssignment::where('employee_id', $assignment->employee_id)
                ->whereDate('assignment_date', $checkDate)
                ->where('type', '!=', 'off')
                ->exists();

            if (!$exists) {
                break;
            }

            $consecutiveDays++;
            $checkDate->subDay();
        }

        if ($consecutiveDays > $maxConsecutiveDays) {
            return [
                'rule' => $this->code ?? $this->id,
                'severity' => $this->severity,
                'message' => $this->error_message,
                'data' => ['consecutive_days' => $consecutiveDays, 'max_days' => $maxConsecutiveDays],
            ];
        }

        return null;
    }

    protected function validateOverlap(RosterAssignment $assignment): ?array
    {
        if (!$assignment->scheduled_start || !$assignment->scheduled_end) {
            return null;
        }

        $overlapping = RosterAssignment::where('employee_id', $assignment->employee_id)
            ->whereDate('assignment_date', $assignment->assignment_date)
            ->where('id', '!=', $assignment->id)
            ->where(function ($q) use ($assignment) {
                $q->whereBetween('scheduled_start', [$assignment->scheduled_start, $assignment->scheduled_end])
                    ->orWhereBetween('scheduled_end', [$assignment->scheduled_start, $assignment->scheduled_end]);
            })
            ->exists();

        if ($overlapping) {
            return [
                'rule' => $this->code ?? $this->id,
                'severity' => $this->severity,
                'message' => $this->error_message,
            ];
        }

        return null;
    }
}
