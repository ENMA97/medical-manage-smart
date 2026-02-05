<?php

namespace App\Services\Roster;

use App\Models\HR\Employee;
use App\Models\Roster\AttendanceRecord;
use App\Models\Roster\Roster;
use App\Models\Roster\RosterAssignment;
use App\Models\Roster\ShiftPattern;
use App\Models\Roster\ShiftSwapRequest;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Exception;

class RosterService
{
    /**
     * إنشاء جدول جديد
     */
    public function createRoster(
        string $departmentId,
        string $name,
        \DateTime $startDate,
        \DateTime $endDate,
        string $createdBy
    ): Roster {
        // التحقق من عدم وجود جدول متداخل
        $overlapping = Roster::where('department_id', $departmentId)
            ->where(function ($query) use ($startDate, $endDate) {
                $query->whereBetween('start_date', [$startDate, $endDate])
                    ->orWhereBetween('end_date', [$startDate, $endDate])
                    ->orWhere(function ($q) use ($startDate, $endDate) {
                        $q->where('start_date', '<=', $startDate)
                          ->where('end_date', '>=', $endDate);
                    });
            })
            ->whereNotIn('status', ['cancelled'])
            ->exists();

        if ($overlapping) {
            throw new Exception('يوجد جدول متداخل مع هذه الفترة');
        }

        return Roster::create([
            'name' => $name,
            'department_id' => $departmentId,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'status' => Roster::STATUS_DRAFT,
            'created_by' => $createdBy,
        ]);
    }

    /**
     * تعيين وردية لموظف
     */
    public function assignShift(
        string $rosterId,
        string $employeeId,
        string $shiftPatternId,
        \DateTime $date,
        string $createdBy
    ): RosterAssignment {
        $roster = Roster::findOrFail($rosterId);
        $shiftPattern = ShiftPattern::findOrFail($shiftPatternId);
        $employee = Employee::findOrFail($employeeId);

        // التحقق من التاريخ ضمن فترة الجدول
        if ($date < $roster->start_date || $date > $roster->end_date) {
            throw new Exception('التاريخ خارج نطاق الجدول');
        }

        // التحقق من عدم وجود تعيين سابق
        $existing = RosterAssignment::where('roster_id', $rosterId)
            ->where('employee_id', $employeeId)
            ->whereDate('assignment_date', $date)
            ->whereNotIn('status', [RosterAssignment::STATUS_SWAPPED])
            ->first();

        if ($existing) {
            throw new Exception('الموظف معين مسبقاً في هذا اليوم');
        }

        // حساب أوقات البداية والنهاية
        $scheduledStart = Carbon::parse($date->format('Y-m-d') . ' ' . $shiftPattern->start_time->format('H:i:s'));
        $scheduledEnd = Carbon::parse($date->format('Y-m-d') . ' ' . $shiftPattern->end_time->format('H:i:s'));

        if ($shiftPattern->is_overnight) {
            $scheduledEnd->addDay();
        }

        return RosterAssignment::create([
            'roster_id' => $rosterId,
            'employee_id' => $employeeId,
            'shift_pattern_id' => $shiftPatternId,
            'assignment_date' => $date,
            'scheduled_start' => $scheduledStart,
            'scheduled_end' => $scheduledEnd,
            'status' => RosterAssignment::STATUS_SCHEDULED,
            'created_by' => $createdBy,
        ]);
    }

    /**
     * تعيين ورديات جماعي
     */
    public function bulkAssign(
        string $rosterId,
        array $employeeIds,
        string $shiftPatternId,
        array $dates,
        string $createdBy
    ): array {
        $assignments = [];

        DB::transaction(function () use ($rosterId, $employeeIds, $shiftPatternId, $dates, $createdBy, &$assignments) {
            foreach ($employeeIds as $employeeId) {
                foreach ($dates as $date) {
                    try {
                        $assignments[] = $this->assignShift(
                            $rosterId,
                            $employeeId,
                            $shiftPatternId,
                            Carbon::parse($date),
                            $createdBy
                        );
                    } catch (Exception $e) {
                        // تسجيل الخطأ والاستمرار
                        $assignments[] = [
                            'error' => true,
                            'employee_id' => $employeeId,
                            'date' => $date,
                            'message' => $e->getMessage(),
                        ];
                    }
                }
            }
        });

        return $assignments;
    }

    /**
     * تسجيل حضور
     */
    public function recordAttendance(
        string $employeeId,
        string $recordType,
        \DateTime $recordTime,
        string $source,
        ?string $biometricDeviceId = null,
        ?float $latitude = null,
        ?float $longitude = null,
        ?string $ipAddress = null
    ): AttendanceRecord {
        // البحث عن التعيين المناسب
        $assignment = RosterAssignment::where('employee_id', $employeeId)
            ->whereDate('assignment_date', $recordTime->format('Y-m-d'))
            ->whereIn('status', [
                RosterAssignment::STATUS_SCHEDULED,
                RosterAssignment::STATUS_CONFIRMED,
                RosterAssignment::STATUS_IN_PROGRESS,
            ])
            ->first();

        $record = AttendanceRecord::create([
            'employee_id' => $employeeId,
            'roster_assignment_id' => $assignment?->id,
            'record_type' => $recordType,
            'record_time' => $recordTime,
            'source' => $source,
            'biometric_device_id' => $biometricDeviceId,
            'location_latitude' => $latitude,
            'location_longitude' => $longitude,
            'ip_address' => $ipAddress,
            'is_valid' => true,
        ]);

        // تحديث التعيين
        if ($assignment) {
            if ($recordType === AttendanceRecord::TYPE_CHECK_IN) {
                $assignment->checkIn($recordTime, $biometricDeviceId);
            } elseif ($recordType === AttendanceRecord::TYPE_CHECK_OUT) {
                $assignment->checkOut($recordTime);
            }
        }

        return $record;
    }

    /**
     * طلب تبديل وردية
     */
    public function requestSwap(
        string $fromAssignmentId,
        string $toEmployeeId,
        ?string $toAssignmentId,
        string $reason
    ): ShiftSwapRequest {
        $fromAssignment = RosterAssignment::findOrFail($fromAssignmentId);

        // التحقق من أن التعيين لم يبدأ بعد
        if ($fromAssignment->status !== RosterAssignment::STATUS_SCHEDULED) {
            throw new Exception('لا يمكن تبديل وردية بدأت أو انتهت');
        }

        return ShiftSwapRequest::create([
            'from_assignment_id' => $fromAssignmentId,
            'from_employee_id' => $fromAssignment->employee_id,
            'to_employee_id' => $toEmployeeId,
            'to_assignment_id' => $toAssignmentId,
            'status' => ShiftSwapRequest::STATUS_PENDING,
            'reason' => $reason,
        ]);
    }

    /**
     * الموافقة على تبديل وردية
     */
    public function approveSwap(string $swapRequestId, string $approverId): ShiftSwapRequest
    {
        return DB::transaction(function () use ($swapRequestId, $approverId) {
            $swap = ShiftSwapRequest::findOrFail($swapRequestId);

            if ($swap->status !== ShiftSwapRequest::STATUS_PENDING) {
                throw new Exception('الطلب ليس قيد الانتظار');
            }

            $fromAssignment = $swap->fromAssignment;
            $toAssignment = $swap->toAssignment;

            // تبديل الموظفين
            $tempEmployee = $fromAssignment->employee_id;
            $fromAssignment->employee_id = $swap->to_employee_id;
            $fromAssignment->save();

            if ($toAssignment) {
                $toAssignment->employee_id = $tempEmployee;
                $toAssignment->save();
            }

            // تحديث الطلب
            $swap->status = ShiftSwapRequest::STATUS_APPROVED;
            $swap->approved_by = $approverId;
            $swap->approved_at = now();
            $swap->save();

            return $swap->fresh();
        });
    }

    /**
     * تحليل الفجوات في الجدول
     */
    public function analyzeGaps(string $rosterId): array
    {
        $roster = Roster::with('assignments.shiftPattern')->findOrFail($rosterId);
        $gaps = [];

        $currentDate = Carbon::parse($roster->start_date);
        $endDate = Carbon::parse($roster->end_date);

        while ($currentDate->lte($endDate)) {
            $dayAssignments = $roster->assignments
                ->where('assignment_date', $currentDate->format('Y-m-d'));

            // التحقق من الحد الأدنى للموظفين
            foreach (ShiftPattern::active()->get() as $shift) {
                $shiftAssignments = $dayAssignments
                    ->where('shift_pattern_id', $shift->id)
                    ->count();

                if ($shift->minimum_staff && $shiftAssignments < $shift->minimum_staff) {
                    $gaps[] = [
                        'date' => $currentDate->format('Y-m-d'),
                        'shift' => $shift->name,
                        'shift_id' => $shift->id,
                        'required' => $shift->minimum_staff,
                        'assigned' => $shiftAssignments,
                        'shortage' => $shift->minimum_staff - $shiftAssignments,
                    ];
                }
            }

            $currentDate->addDay();
        }

        return $gaps;
    }

    /**
     * إحصائيات الحضور
     */
    public function getAttendanceStatistics(\DateTime $startDate, \DateTime $endDate, ?string $departmentId = null): array
    {
        $query = RosterAssignment::inDateRange($startDate, $endDate);

        if ($departmentId) {
            $query->whereHas('roster', fn($q) => $q->where('department_id', $departmentId));
        }

        $total = $query->count();
        $completed = (clone $query)->completed()->count();
        $absent = (clone $query)->absent()->count();

        $lateCount = RosterAssignment::inDateRange($startDate, $endDate)
            ->completed()
            ->whereNotNull('actual_start')
            ->whereRaw('actual_start > scheduled_start')
            ->count();

        return [
            'total_shifts' => $total,
            'completed' => $completed,
            'absent' => $absent,
            'late' => $lateCount,
            'attendance_rate' => $total > 0 ? round(($completed / $total) * 100, 2) : 0,
            'absence_rate' => $total > 0 ? round(($absent / $total) * 100, 2) : 0,
            'late_rate' => $completed > 0 ? round(($lateCount / $completed) * 100, 2) : 0,
        ];
    }
}
