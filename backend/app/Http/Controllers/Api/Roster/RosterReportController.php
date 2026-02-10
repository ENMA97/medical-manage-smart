<?php

namespace App\Http\Controllers\Api\Roster;

use App\Http\Controllers\Controller;
use App\Models\HR\Employee;
use App\Models\Roster\AttendanceRecord;
use App\Models\Roster\RosterAssignment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RosterReportController extends Controller
{
    /**
     * ملخص الحضور
     */
    public function attendanceSummary(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'date_from' => ['required', 'date'],
            'date_to' => ['required', 'date', 'after_or_equal:date_from'],
            'department_id' => ['nullable', 'uuid', 'exists:departments,id'],
        ]);

        $query = RosterAssignment::query()
            ->whereBetween('assignment_date', [$validated['date_from'], $validated['date_to']])
            ->when($validated['department_id'] ?? null, function ($q, $deptId) {
                $q->whereHas('employee', fn($eq) => $eq->where('department_id', $deptId));
            });

        $summary = [
            'total_assignments' => $query->count(),
            'completed' => (clone $query)->where('status', 'completed')->count(),
            'present' => (clone $query)->where('status', 'present')->count(),
            'absent' => (clone $query)->where('status', 'absent')->count(),
            'late' => (clone $query)->where('status', 'late')->count(),
            'on_leave' => (clone $query)->where('status', 'on_leave')->count(),
            'sick' => (clone $query)->where('status', 'sick')->count(),
            'scheduled' => (clone $query)->where('status', 'scheduled')->count(),
        ];

        // نسب الحضور
        $summary['attendance_rate'] = $summary['total_assignments'] > 0
            ? round((($summary['completed'] + $summary['present']) / $summary['total_assignments']) * 100, 2)
            : 0;

        $summary['absence_rate'] = $summary['total_assignments'] > 0
            ? round(($summary['absent'] / $summary['total_assignments']) * 100, 2)
            : 0;

        // إحصائيات يومية
        $dailyStats = RosterAssignment::query()
            ->whereBetween('assignment_date', [$validated['date_from'], $validated['date_to']])
            ->when($validated['department_id'] ?? null, function ($q, $deptId) {
                $q->whereHas('employee', fn($eq) => $eq->where('department_id', $deptId));
            })
            ->select(
                'assignment_date',
                DB::raw('count(*) as total'),
                DB::raw("sum(case when status in ('completed', 'present') then 1 else 0 end) as attended"),
                DB::raw("sum(case when status = 'absent' then 1 else 0 end) as absent"),
            )
            ->groupBy('assignment_date')
            ->orderBy('assignment_date')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'summary' => $summary,
                'daily_stats' => $dailyStats,
            ],
        ]);
    }

    /**
     * تقرير الوقت الإضافي
     */
    public function overtime(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'date_from' => ['required', 'date'],
            'date_to' => ['required', 'date', 'after_or_equal:date_from'],
            'department_id' => ['nullable', 'uuid', 'exists:departments,id'],
            'employee_id' => ['nullable', 'uuid', 'exists:employees,id'],
        ]);

        $query = RosterAssignment::with(['employee.department', 'employee.position'])
            ->whereBetween('assignment_date', [$validated['date_from'], $validated['date_to']])
            ->where('is_overtime', true)
            ->when($validated['department_id'] ?? null, function ($q, $deptId) {
                $q->whereHas('employee', fn($eq) => $eq->where('department_id', $deptId));
            })
            ->when($validated['employee_id'] ?? null, fn($q, $id) => $q->where('employee_id', $id));

        // ملخص إجمالي
        $totalOvertime = $query->sum('overtime_hours');

        // تفصيل حسب الموظف
        $byEmployee = RosterAssignment::query()
            ->whereBetween('assignment_date', [$validated['date_from'], $validated['date_to']])
            ->where('is_overtime', true)
            ->when($validated['department_id'] ?? null, function ($q, $deptId) {
                $q->whereHas('employee', fn($eq) => $eq->where('department_id', $deptId));
            })
            ->when($validated['employee_id'] ?? null, fn($q, $id) => $q->where('employee_id', $id))
            ->with('employee.department')
            ->select(
                'employee_id',
                DB::raw('count(*) as overtime_days'),
                DB::raw('sum(overtime_hours) as total_hours'),
                DB::raw('avg(overtime_rate) as avg_rate'),
            )
            ->groupBy('employee_id')
            ->orderByDesc('total_hours')
            ->get()
            ->map(function ($item) {
                return [
                    'employee' => $item->employee,
                    'overtime_days' => $item->overtime_days,
                    'total_hours' => round($item->total_hours, 2),
                    'avg_rate' => round($item->avg_rate, 2),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'total_overtime_hours' => round($totalOvertime, 2),
                'employees_count' => $byEmployee->count(),
                'by_employee' => $byEmployee,
            ],
        ]);
    }

    /**
     * تقرير الغياب
     */
    public function absences(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'date_from' => ['required', 'date'],
            'date_to' => ['required', 'date', 'after_or_equal:date_from'],
            'department_id' => ['nullable', 'uuid', 'exists:departments,id'],
        ]);

        // الموظفين الأكثر غياباً
        $topAbsent = RosterAssignment::query()
            ->whereBetween('assignment_date', [$validated['date_from'], $validated['date_to']])
            ->where('status', 'absent')
            ->when($validated['department_id'] ?? null, function ($q, $deptId) {
                $q->whereHas('employee', fn($eq) => $eq->where('department_id', $deptId));
            })
            ->with('employee.department')
            ->select(
                'employee_id',
                DB::raw('count(*) as absence_count'),
            )
            ->groupBy('employee_id')
            ->orderByDesc('absence_count')
            ->limit(20)
            ->get()
            ->map(function ($item) {
                return [
                    'employee' => $item->employee,
                    'absence_count' => $item->absence_count,
                ];
            });

        // الغياب حسب اليوم من الأسبوع
        $byDayOfWeek = RosterAssignment::query()
            ->whereBetween('assignment_date', [$validated['date_from'], $validated['date_to']])
            ->where('status', 'absent')
            ->when($validated['department_id'] ?? null, function ($q, $deptId) {
                $q->whereHas('employee', fn($eq) => $eq->where('department_id', $deptId));
            })
            ->select(
                DB::raw('EXTRACT(DOW FROM assignment_date) as day_of_week'),
                DB::raw('count(*) as count'),
            )
            ->groupBy(DB::raw('EXTRACT(DOW FROM assignment_date)'))
            ->orderBy('day_of_week')
            ->get();

        $daysArabic = ['الأحد', 'الاثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة', 'السبت'];
        $byDayOfWeek = $byDayOfWeek->map(function ($item) use ($daysArabic) {
            return [
                'day' => $daysArabic[$item->day_of_week] ?? $item->day_of_week,
                'day_index' => $item->day_of_week,
                'count' => $item->count,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'top_absent_employees' => $topAbsent,
                'by_day_of_week' => $byDayOfWeek,
            ],
        ]);
    }

    /**
     * تقرير التأخيرات
     */
    public function lateArrivals(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'date_from' => ['required', 'date'],
            'date_to' => ['required', 'date', 'after_or_equal:date_from'],
            'department_id' => ['nullable', 'uuid', 'exists:departments,id'],
            'min_late_minutes' => ['sometimes', 'integer', 'min:1'],
        ]);

        $minLateMinutes = $validated['min_late_minutes'] ?? 5;

        // الموظفين الأكثر تأخراً
        $topLate = RosterAssignment::query()
            ->whereBetween('assignment_date', [$validated['date_from'], $validated['date_to']])
            ->where('late_minutes', '>=', $minLateMinutes)
            ->when($validated['department_id'] ?? null, function ($q, $deptId) {
                $q->whereHas('employee', fn($eq) => $eq->where('department_id', $deptId));
            })
            ->with('employee.department')
            ->select(
                'employee_id',
                DB::raw('count(*) as late_count'),
                DB::raw('sum(late_minutes) as total_late_minutes'),
                DB::raw('avg(late_minutes) as avg_late_minutes'),
            )
            ->groupBy('employee_id')
            ->orderByDesc('total_late_minutes')
            ->limit(20)
            ->get()
            ->map(function ($item) {
                return [
                    'employee' => $item->employee,
                    'late_count' => $item->late_count,
                    'total_late_minutes' => $item->total_late_minutes,
                    'avg_late_minutes' => round($item->avg_late_minutes, 1),
                ];
            });

        // إحصائيات عامة
        $stats = RosterAssignment::query()
            ->whereBetween('assignment_date', [$validated['date_from'], $validated['date_to']])
            ->where('late_minutes', '>=', $minLateMinutes)
            ->when($validated['department_id'] ?? null, function ($q, $deptId) {
                $q->whereHas('employee', fn($eq) => $eq->where('department_id', $deptId));
            })
            ->selectRaw('count(*) as total_late_instances, sum(late_minutes) as total_late_minutes')
            ->first();

        return response()->json([
            'success' => true,
            'data' => [
                'total_late_instances' => $stats->total_late_instances ?? 0,
                'total_late_minutes' => $stats->total_late_minutes ?? 0,
                'top_late_employees' => $topLate,
            ],
        ]);
    }

    /**
     * تقرير التغطية
     */
    public function coverage(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'date' => ['required', 'date'],
            'department_id' => ['nullable', 'uuid', 'exists:departments,id'],
        ]);

        // التغطية حسب الفترة
        $byShift = RosterAssignment::with('shiftPattern')
            ->whereDate('assignment_date', $validated['date'])
            ->when($validated['department_id'] ?? null, function ($q, $deptId) {
                $q->whereHas('employee', fn($eq) => $eq->where('department_id', $deptId));
            })
            ->whereNotNull('shift_pattern_id')
            ->select(
                'shift_pattern_id',
                DB::raw('count(*) as assigned'),
                DB::raw("sum(case when status in ('completed', 'present', 'late') then 1 else 0 end) as actual"),
            )
            ->groupBy('shift_pattern_id')
            ->get()
            ->map(function ($item) {
                return [
                    'shift_pattern' => $item->shiftPattern,
                    'assigned' => $item->assigned,
                    'actual' => $item->actual,
                    'coverage_rate' => $item->assigned > 0
                        ? round(($item->actual / $item->assigned) * 100, 1)
                        : 0,
                ];
            });

        // إجمالي العاملين
        $totalAssigned = RosterAssignment::query()
            ->whereDate('assignment_date', $validated['date'])
            ->when($validated['department_id'] ?? null, function ($q, $deptId) {
                $q->whereHas('employee', fn($eq) => $eq->where('department_id', $deptId));
            })
            ->where('type', '!=', 'off')
            ->count();

        $totalPresent = RosterAssignment::query()
            ->whereDate('assignment_date', $validated['date'])
            ->when($validated['department_id'] ?? null, function ($q, $deptId) {
                $q->whereHas('employee', fn($eq) => $eq->where('department_id', $deptId));
            })
            ->whereIn('status', ['completed', 'present', 'late'])
            ->count();

        return response()->json([
            'success' => true,
            'data' => [
                'date' => $validated['date'],
                'total_assigned' => $totalAssigned,
                'total_present' => $totalPresent,
                'overall_coverage' => $totalAssigned > 0
                    ? round(($totalPresent / $totalAssigned) * 100, 1)
                    : 0,
                'by_shift' => $byShift,
            ],
        ]);
    }
}
