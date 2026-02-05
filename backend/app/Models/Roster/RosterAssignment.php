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
     * حالات التعيين
     */
    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_ABSENT = 'absent';
    public const STATUS_ON_LEAVE = 'on_leave';
    public const STATUS_SWAPPED = 'swapped';

    public const STATUSES = [
        self::STATUS_SCHEDULED => 'مجدول',
        self::STATUS_CONFIRMED => 'مؤكد',
        self::STATUS_IN_PROGRESS => 'جاري',
        self::STATUS_COMPLETED => 'مكتمل',
        self::STATUS_ABSENT => 'غائب',
        self::STATUS_ON_LEAVE => 'في إجازة',
        self::STATUS_SWAPPED => 'تم التبديل',
    ];

    protected $fillable = [
        'roster_id',
        'employee_id',
        'shift_pattern_id',
        'assignment_date',
        'scheduled_start',
        'scheduled_end',
        'actual_start',
        'actual_end',
        'status',
        'is_overtime',
        'overtime_hours',
        'notes',
        'created_by',
        'modified_by',
    ];

    protected $casts = [
        'assignment_date' => 'date',
        'scheduled_start' => 'datetime',
        'scheduled_end' => 'datetime',
        'actual_start' => 'datetime',
        'actual_end' => 'datetime',
        'is_overtime' => 'boolean',
        'overtime_hours' => 'decimal:2',
    ];

    // =============================================================================
    // Accessors
    // =============================================================================

    public function getStatusNameAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    /**
     * ساعات العمل المجدولة
     */
    public function getScheduledHoursAttribute(): float
    {
        if (!$this->scheduled_start || !$this->scheduled_end) {
            return 0;
        }
        return round($this->scheduled_start->diffInMinutes($this->scheduled_end) / 60, 2);
    }

    /**
     * ساعات العمل الفعلية
     */
    public function getActualHoursAttribute(): ?float
    {
        if (!$this->actual_start || !$this->actual_end) {
            return null;
        }
        return round($this->actual_start->diffInMinutes($this->actual_end) / 60, 2);
    }

    /**
     * التأخير بالدقائق
     */
    public function getLateMinutesAttribute(): int
    {
        if (!$this->scheduled_start || !$this->actual_start) {
            return 0;
        }

        if ($this->actual_start->gt($this->scheduled_start)) {
            return $this->scheduled_start->diffInMinutes($this->actual_start);
        }
        return 0;
    }

    /**
     * الخروج المبكر بالدقائق
     */
    public function getEarlyLeaveMinutesAttribute(): int
    {
        if (!$this->scheduled_end || !$this->actual_end) {
            return 0;
        }

        if ($this->actual_end->lt($this->scheduled_end)) {
            return $this->actual_end->diffInMinutes($this->scheduled_end);
        }
        return 0;
    }

    /**
     * هل متأخر
     */
    public function getIsLateAttribute(): bool
    {
        return $this->late_minutes > 0;
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
    public function checkIn(\DateTime $time, ?string $biometricDeviceId = null): bool
    {
        $this->actual_start = $time;
        $this->status = self::STATUS_IN_PROGRESS;
        return $this->save();
    }

    /**
     * تسجيل الانصراف
     */
    public function checkOut(\DateTime $time): bool
    {
        $this->actual_end = $time;
        $this->status = self::STATUS_COMPLETED;

        // حساب الوقت الإضافي
        if ($this->actual_hours && $this->scheduled_hours) {
            $overtime = $this->actual_hours - $this->scheduled_hours;
            if ($overtime > 0) {
                $this->is_overtime = true;
                $this->overtime_hours = $overtime;
            }
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
     * تسجيل إجازة
     */
    public function markOnLeave(?string $leaveRequestId = null): bool
    {
        $this->status = self::STATUS_ON_LEAVE;
        $this->notes = $leaveRequestId ? "Leave Request: {$leaveRequestId}" : null;
        return $this->save();
    }
}
