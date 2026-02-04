<?php

namespace App\Models\Leave;

use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DepartmentLeaveSetting extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'department_leave_settings';

    protected $fillable = [
        'department_id',
        'max_concurrent_leaves',
        'max_concurrent_percentage',
        'blackout_periods',
        'peak_periods',
        'require_coverage',
        'default_approver_id',
    ];

    protected $casts = [
        'max_concurrent_leaves' => 'integer',
        'max_concurrent_percentage' => 'decimal:2',
        'blackout_periods' => 'array',
        'peak_periods' => 'array',
        'require_coverage' => 'boolean',
    ];

    /**
     * العلاقة مع القسم
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * العلاقة مع المعتمد الافتراضي
     */
    public function defaultApprover(): BelongsTo
    {
        return $this->belongsTo(User::class, 'default_approver_id');
    }

    /**
     * التحقق من وجود فترة محظورة
     */
    public function isInBlackoutPeriod($date): bool
    {
        if (!$this->blackout_periods) {
            return false;
        }

        foreach ($this->blackout_periods as $period) {
            if ($date >= $period['start'] && $date <= $period['end']) {
                return true;
            }
        }

        return false;
    }

    /**
     * التحقق من وجود فترة ذروة
     */
    public function isInPeakPeriod($date): bool
    {
        if (!$this->peak_periods) {
            return false;
        }

        foreach ($this->peak_periods as $period) {
            if ($date >= $period['start'] && $date <= $period['end']) {
                return true;
            }
        }

        return false;
    }

    /**
     * التحقق من إمكانية طلب إجازة في القسم
     */
    public function canRequestLeave(int $currentLeaves, int $totalEmployees): bool
    {
        // التحقق من الحد الأقصى للإجازات المتزامنة
        if ($currentLeaves >= $this->max_concurrent_leaves) {
            return false;
        }

        // التحقق من نسبة الموظفين
        $percentage = ($currentLeaves / $totalEmployees) * 100;
        if ($percentage >= $this->max_concurrent_percentage) {
            return false;
        }

        return true;
    }
}
