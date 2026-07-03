<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\Shift;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class AttendanceController extends Controller
{
    /**
     * POST /api/attendance/check-in
     * تسجيل حضور ذاتي للموظف الحالي
     */
    public function checkIn(Request $request): JsonResponse
    {
        $employee = $request->user()->employee;

        if (!$employee) {
            return response()->json([
                'success' => false,
                'message' => 'لا يوجد ملف موظف مرتبط بهذا المستخدم',
            ], 422);
        }

        $now = now();
        $today = $now->toDateString();

        $existing = AttendanceRecord::where('employee_id', $employee->id)
            ->whereDate('attendance_date', $today)
            ->first();

        if ($existing && $existing->check_in_time) {
            return response()->json([
                'success' => false,
                'message' => 'تم تسجيل حضورك مسبقاً لهذا اليوم',
                'data' => $existing,
            ], 422);
        }

        $shift = $employee->currentShift($today);
        [$status, $lateMinutes] = $this->computeLateness($shift, $now);

        $record = $existing ?: new AttendanceRecord([
            'employee_id' => $employee->id,
            'attendance_date' => $today,
        ]);
        $record->fill([
            'shift_id' => $shift?->id,
            'check_in_time' => $now,
            'status' => $status,
            'late_minutes' => $lateMinutes,
            'source' => 'self',
        ]);
        $record->save();

        return response()->json([
            'success' => true,
            'message' => $status === 'late'
                ? "تم تسجيل الحضور — متأخر {$lateMinutes} دقيقة"
                : 'تم تسجيل الحضور بنجاح',
            'data' => $record->load('shift'),
        ], 201);
    }

    /**
     * POST /api/attendance/check-out
     * تسجيل انصراف ذاتي للموظف الحالي
     */
    public function checkOut(Request $request): JsonResponse
    {
        $employee = $request->user()->employee;

        if (!$employee) {
            return response()->json([
                'success' => false,
                'message' => 'لا يوجد ملف موظف مرتبط بهذا المستخدم',
            ], 422);
        }

        $now = now();

        // آخر سجل بحضور دون انصراف (يشمل الورديات الليلية الممتدة من الأمس)
        $record = AttendanceRecord::where('employee_id', $employee->id)
            ->whereNotNull('check_in_time')
            ->whereNull('check_out_time')
            ->whereDate('attendance_date', '>=', $now->copy()->subDay()->toDateString())
            ->whereDate('attendance_date', '<=', $now->toDateString())
            ->orderByDesc('attendance_date')
            ->first();

        if (!$record) {
            return response()->json([
                'success' => false,
                'message' => 'لا يوجد تسجيل حضور مفتوح — يجب تسجيل الحضور أولاً',
            ], 422);
        }

        $record->fill($this->computeCheckOutMetrics($record, $now));
        $record->save();

        return response()->json([
            'success' => true,
            'message' => 'تم تسجيل الانصراف بنجاح',
            'data' => $record->load('shift'),
        ]);
    }

    /**
     * GET /api/attendance/my
     * سجلات حضور الموظف الحالي (شهرياً)
     */
    public function my(Request $request): JsonResponse
    {
        $employee = $request->user()->employee;

        if (!$employee) {
            return response()->json([
                'success' => false,
                'message' => 'لا يوجد ملف موظف مرتبط بهذا المستخدم',
            ], 422);
        }

        $year = (int) $request->input('year', now()->year);
        $month = (int) $request->input('month', now()->month);

        $records = AttendanceRecord::where('employee_id', $employee->id)
            ->forMonth($year, $month)
            ->with('shift:id,code,name,name_ar,start_time,end_time')
            ->orderByDesc('attendance_date')
            ->get();

        $todayRecord = AttendanceRecord::where('employee_id', $employee->id)
            ->whereDate('attendance_date', now()->toDateString())
            ->first();

        return response()->json([
            'success' => true,
            'message' => 'تم جلب سجلات الحضور بنجاح',
            'data' => [
                'records' => $records,
                'today' => $todayRecord,
                'current_shift' => $employee->currentShift(),
                'summary' => $this->summarizeRecords($records),
            ],
        ]);
    }

    /**
     * GET /api/attendance
     * قائمة سجلات الحضور (HR)
     */
    public function index(Request $request): JsonResponse
    {
        $records = AttendanceRecord::query()
            ->with([
                'employee:id,employee_number,first_name,first_name_ar,last_name,last_name_ar,department_id',
                'employee.department:id,name,name_ar',
                'shift:id,code,name,name_ar,start_time,end_time',
            ])
            ->when($request->filled('date'), fn($q) => $q->whereDate('attendance_date', $request->input('date')))
            ->when($request->filled('from'), fn($q) => $q->whereDate('attendance_date', '>=', $request->input('from')))
            ->when($request->filled('to'), fn($q) => $q->whereDate('attendance_date', '<=', $request->input('to')))
            ->when($request->filled('employee_id'), fn($q) => $q->where('employee_id', $request->input('employee_id')))
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->input('status')))
            ->when($request->filled('department_id'), function ($q) use ($request) {
                $q->whereHas('employee', fn($eq) => $eq->where('department_id', $request->input('department_id')));
            })
            ->orderByDesc('attendance_date')
            ->orderBy('check_in_time')
            ->paginate($request->input('per_page', 15));

        return response()->json([
            'success' => true,
            'message' => 'تم جلب سجلات الحضور بنجاح',
            'data' => $records,
        ]);
    }

    /**
     * POST /api/attendance
     * تسجيل يدوي لسجل حضور (HR)
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'employee_id' => 'required|uuid|exists:employees,id',
            'attendance_date' => 'required|date',
            'status' => 'required|in:present,late,absent,on_leave,holiday,mission',
            'check_in_time' => 'nullable|date',
            'check_out_time' => 'nullable|date|after:check_in_time',
            'notes' => 'nullable|string',
        ]);

        $exists = AttendanceRecord::where('employee_id', $request->input('employee_id'))
            ->whereDate('attendance_date', $request->input('attendance_date'))
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'يوجد سجل حضور مسبق لهذا الموظف في هذا اليوم',
            ], 422);
        }

        $employee = Employee::findOrFail($request->input('employee_id'));
        $date = Carbon::parse($request->input('attendance_date'))->toDateString();
        $shift = $employee->currentShift($date);

        $record = new AttendanceRecord($request->only([
            'employee_id', 'attendance_date', 'status',
            'check_in_time', 'check_out_time', 'notes',
        ]));
        $record->shift_id = $shift?->id;
        $record->source = 'manual';
        $record->recorded_by = $request->user()->id;

        // احتساب التأخير والدقائق تلقائياً عند توفر الأوقات
        if ($record->check_in_time && in_array($record->status, ['present', 'late'], true)) {
            [$status, $lateMinutes] = $this->computeLateness($shift, Carbon::parse($record->check_in_time));
            $record->status = $status;
            $record->late_minutes = $lateMinutes;
        }
        if ($record->check_in_time && $record->check_out_time) {
            $record->fill($this->computeCheckOutMetrics($record, Carbon::parse($record->check_out_time)));
        }

        $record->save();

        return response()->json([
            'success' => true,
            'message' => 'تم تسجيل الحضور بنجاح',
            'data' => $record->load('employee:id,employee_number,first_name_ar,last_name_ar', 'shift'),
        ], 201);
    }

    /**
     * PUT /api/attendance/{id}
     * تعديل سجل حضور (HR)
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $record = AttendanceRecord::findOrFail($id);

        $request->validate([
            'status' => 'sometimes|in:present,late,absent,on_leave,holiday,mission',
            'check_in_time' => 'nullable|date',
            'check_out_time' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        $record->fill($request->only(['status', 'check_in_time', 'check_out_time', 'notes']));
        $record->recorded_by = $request->user()->id;

        if ($record->check_in_time && $record->check_out_time) {
            $record->fill($this->computeCheckOutMetrics($record, Carbon::parse($record->check_out_time)));
        }

        $record->save();

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث سجل الحضور بنجاح',
            'data' => $record,
        ]);
    }

    /**
     * GET /api/attendance/daily-summary
     * ملخص الحضور ليوم محدد (HR)
     */
    public function dailySummary(Request $request): JsonResponse
    {
        $date = $request->input('date', now()->toDateString());

        $counts = AttendanceRecord::whereDate('attendance_date', $date)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $activeEmployees = Employee::active()->count();
        $recorded = (int) $counts->sum();

        return response()->json([
            'success' => true,
            'message' => 'تم جلب ملخص الحضور اليومي بنجاح',
            'data' => [
                'date' => $date,
                'active_employees' => $activeEmployees,
                'recorded' => $recorded,
                'not_recorded' => max(0, $activeEmployees - $recorded),
                'present' => (int) ($counts['present'] ?? 0),
                'late' => (int) ($counts['late'] ?? 0),
                'absent' => (int) ($counts['absent'] ?? 0),
                'on_leave' => (int) ($counts['on_leave'] ?? 0),
                'holiday' => (int) ($counts['holiday'] ?? 0),
                'mission' => (int) ($counts['mission'] ?? 0),
            ],
        ]);
    }

    /**
     * GET /api/attendance/monthly-report
     * تقرير شهري مجمع لكل موظف (HR) — يغذي مسير الرواتب
     */
    public function monthlyReport(Request $request): JsonResponse
    {
        $year = (int) $request->input('year', now()->year);
        $month = (int) $request->input('month', now()->month);

        $report = AttendanceRecord::query()
            ->forMonth($year, $month)
            ->when($request->filled('employee_id'), fn($q) => $q->where('employee_id', $request->input('employee_id')))
            ->when($request->filled('department_id'), function ($q) use ($request) {
                $q->whereHas('employee', fn($eq) => $eq->where('department_id', $request->input('department_id')));
            })
            ->selectRaw("
                employee_id,
                sum(case when status in ('present', 'late') then 1 else 0 end) as days_present,
                sum(case when status = 'late' then 1 else 0 end) as days_late,
                sum(case when status = 'absent' then 1 else 0 end) as days_absent,
                sum(case when status = 'on_leave' then 1 else 0 end) as days_on_leave,
                sum(case when status = 'mission' then 1 else 0 end) as days_mission,
                sum(late_minutes) as total_late_minutes,
                sum(early_leave_minutes) as total_early_leave_minutes,
                sum(overtime_minutes) as total_overtime_minutes,
                sum(worked_minutes) as total_worked_minutes
            ")
            ->groupBy('employee_id')
            ->with([
                'employee:id,employee_number,first_name,first_name_ar,last_name,last_name_ar,department_id',
                'employee.department:id,name,name_ar',
            ])
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'تم جلب التقرير الشهري بنجاح',
            'data' => [
                'year' => $year,
                'month' => $month,
                'report' => $report,
            ],
        ]);
    }

    // ─── Helpers ───

    /**
     * احتساب حالة التأخير: يُعد الموظف متأخراً إذا تجاوز بداية الوردية + السماحية
     *
     * @return array{0: string, 1: int} [الحالة، دقائق التأخير]
     */
    private function computeLateness(?Shift $shift, Carbon $checkIn): array
    {
        if (!$shift) {
            return ['present', 0];
        }

        $shiftStart = Carbon::parse($checkIn->toDateString() . ' ' . $shift->start_time);
        $minutesPastStart = $shiftStart->diffInMinutes($checkIn, false);

        if ($minutesPastStart > $shift->grace_period_minutes) {
            return ['late', (int) $minutesPastStart];
        }

        return ['present', 0];
    }

    /**
     * احتساب مقاييس الانصراف: دقائق العمل، الانصراف المبكر، العمل الإضافي
     */
    private function computeCheckOutMetrics(AttendanceRecord $record, Carbon $checkOut): array
    {
        $checkIn = Carbon::parse($record->check_in_time);
        $metrics = [
            'check_out_time' => $checkOut,
            'worked_minutes' => max(0, (int) $checkIn->diffInMinutes($checkOut, false)),
        ];

        $shift = $record->shift_id ? Shift::find($record->shift_id) : null;
        if ($shift) {
            $shiftEnd = Carbon::parse($record->attendance_date->toDateString() . ' ' . $shift->end_time);
            if ($shift->is_overnight || $shift->end_time <= $shift->start_time) {
                $shiftEnd->addDay();
            }
            $diff = (int) $shiftEnd->diffInMinutes($checkOut, false);
            $metrics['early_leave_minutes'] = max(0, -$diff);
            $metrics['overtime_minutes'] = max(0, $diff);
        }

        return $metrics;
    }

    /**
     * ملخص سجلات شهر واحد للموظف
     */
    private function summarizeRecords($records): array
    {
        return [
            'days_present' => $records->whereIn('status', ['present', 'late'])->count(),
            'days_late' => $records->where('status', 'late')->count(),
            'days_absent' => $records->where('status', 'absent')->count(),
            'days_on_leave' => $records->where('status', 'on_leave')->count(),
            'total_late_minutes' => (int) $records->sum('late_minutes'),
            'total_overtime_minutes' => (int) $records->sum('overtime_minutes'),
            'total_worked_minutes' => (int) $records->sum('worked_minutes'),
        ];
    }
}
