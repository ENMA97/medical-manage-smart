<?php

namespace App\Models\Roster;

use App\Models\HR\Employee;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class RosterAssignment extends Model
{
    use HasFactory, HasUuids;

    /**
     * أنواع التعيين
     */
    public const TYPE_REGULAR = 'regular';
    public const TYPE_OVERTIME = 'overtime';
    public const TYPE_ON_CALL = 'on_call';
    public const TYPE_OFF = 'off';

    public const TYPES = [
        self::TYPE_REGULAR => 'عادي',
        self::TYPE_OVERTIME => 'إضافي',
        self::TYPE_ON_CALL => 'تحت الطلب',
        self::TYPE_OFF => 'إجازة/راحة',
    ];

    /**
     * حالات التعيين
     */
    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_PRESENT = 'present';
    public const STATUS_ABSENT = 'absent';
    public const STATUS_LATE = 'late';
    public const STATUS_ON_LEAVE = 'on_leave';
    public const STATUS_SICK = 'sick';
    public const STATUS_COMPLETED = 'completed';

    public const STATUSES = [
        self::STATUS_SCHEDULED => 'مجدول',
        self::STATUS_PRESENT => 'حاضر',
        self::STATUS_ABSENT => 'غائب',
        self::STATUS_LATE => 'متأخر',
        self::STATUS_ON_LEAVE => 'في إجازة',
        self::STATUS_SICK => 'مريض',
        self::STATUS_COMPLETED => 'مكتمل',
    ];

    protected $fillable = [
        'roster_id',
        'employee_id',
        'shift_pattern_id',
        'assignment_date',
        'type',
        'scheduled_start',
        'scheduled_end',
        'scheduled_hours',
        'actual_start',
        'actual_end',
        'actual_hours',
        'late_minutes',
        'early_leave_minutes',
        'is_overtime',
        'overtime_hours',
        'overtime_rate',
        'status',
        'notes',
        'updated_by',
    ];

    protected $casts = [
        'assignment_date' => 'date',
        'scheduled_start' => 'datetime:H:i',
        'scheduled_end' => 'datetime:H:i',
        'scheduled_hours' => 'decimal:2',
        'actual_start' => 'datetime',
        'actual_end' => 'datetime',
        'actual_hours' => 'decimal:2',
        'late_minutes' => 'integer',
        'early_leave_minutes' => 'integer',
        'is_overtime' => 'boolean',
        'overtime_hours' => 'decimal:2',
        'overtime_rate' => 'decimal:2',
    ];

    // =============================================================================
    // Accessors
    // =============================================================================

    public function getStatusNameAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    /**
     * اسم نوع التعيين
     */
    public function getTypeNameAttribute(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    /**
     * هل متأخر
     */
    public function getIsLateAttribute(): bool
    {
        return ($this->late_minutes ?? 0) > 0;
    }

    /**
     * حساب ساعات العمل المجدولة ديناميكياً
     */
    public function calculateScheduledHours(): float
    {
        if ($this->scheduled_hours) {
            return (float) $this->scheduled_hours;
        }

        if (!$this->scheduled_start || !$this->scheduled_end) {
            return 0;
        }

        return round($this->scheduled_start->diffInMinutes($this->scheduled_end) / 60, 2);
    }

    /**
     * حساب ساعات العمل الفعلية ديناميكياً
     */
    public function calculateActualHours(): ?float
    {
        if ($this->actual_hours) {
            return (float) $this->actual_hours;
        }

        if (!$this->actual_start || !$this->actual_end) {
            return null;
        }

        return round($this->actual_start->diffInMinutes($this->actual_end) / 60, 2);
    }

    // =============================================================================
    // Relationships
    // =============================================================================

    public function roster(): BelongsTo
    {
        return $this->belongsTo(Roster::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function shiftPattern(): BelongsTo
    {
        return $this->belongsTo(ShiftPattern::class);
    }

    public function attendance(): HasOne
    {
        return $this->hasOne(AttendanceRecord::class);
    }

    public function swapRequest(): HasOne
    {
        return $this->hasOne(ShiftSwapRequest::class, 'from_assignment_id');
    }

    // =============================================================================
    // Scopes
    // =============================================================================

    public function scopeForDate($query, $date)
    {
        return $query->whereDate('assignment_date', $date);
    }

    public function scopeForEmployee($query, string $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    public function scopeInDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('assignment_date', [$startDate, $endDate]);
    }

    public function scopeScheduled($query)
    {
        return $query->where('status', self::STATUS_SCHEDULED);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopeAbsent($query)
    {
        return $query->where('status', self::STATUS_ABSENT);
    }

    // =============================================================================
    // Methods
    // =============================================================================

    /**
     * تسجيل الحضور
     */
    public function checkIn(\DateTime $time): bool
    {
        $this->actual_start = $time;
        $this->status = self::STATUS_PRESENT;

        // حساب التأخير
        if ($this->scheduled_start) {
            $scheduledStart = \Carbon\Carbon::parse($this->scheduled_start);
            $actualStart = \Carbon\Carbon::parse($time);
            if ($actualStart->gt($scheduledStart)) {
                $this->late_minutes = $scheduledStart->diffInMinutes($actualStart);
                $this->status = self::STATUS_LATE;
            }
        }

        return $this->save();
    }

    /**
     * تسجيل الانصراف
     */
    public function checkOut(\DateTime $time): bool
    {
        $this->actual_end = $time;
        $this->status = self::STATUS_COMPLETED;

        // حساب الخروج المبكر
        if ($this->scheduled_end) {
            $scheduledEnd = \Carbon\Carbon::parse($this->scheduled_end);
            $actualEnd = \Carbon\Carbon::parse($time);
            if ($actualEnd->lt($scheduledEnd)) {
                $this->early_leave_minutes = $actualEnd->diffInMinutes($scheduledEnd);
            }
        }

        // حساب ساعات العمل الفعلية
        if ($this->actual_start) {
            $this->actual_hours = $this->calculateActualHours();
        }

        // حساب الوقت الإضافي
        $scheduled = $this->scheduled_hours ?? 0;
        $actual = $this->actual_hours ?? 0;
        if ($actual > $scheduled) {
            $this->is_overtime = true;
            $this->overtime_hours = $actual - $scheduled;
        }

        return $this->save();
    }

    /**
     * تسجيل غياب
     */
    public function markAbsent(?string $notes = null): bool
    {
        $this->status = self::STATUS_ABSENT;
        $this->notes = $notes;
        return $this->save();
    }

    /**
     * تسجيل مرض
     */
    public function markSick(?string $notes = null): bool
    {
        $this->status = self::STATUS_SICK;
        $this->notes = $notes;
        return $this->save();
    }

    /**
     * تسجيل إجازة
     */
    public function markOnLeave(?string $leaveRequestId = null): bool
    {
        $this->status = self::STATUS_ON_LEAVE;
        $this->notes = $leaveRequestId ? "Leave Request: {$leaveRequestId}" : null;
        return $this->save();
    }
}
