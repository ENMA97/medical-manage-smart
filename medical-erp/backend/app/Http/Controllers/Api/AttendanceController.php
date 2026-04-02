<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSummary;
use App\Models\EmployeeSchedule;
use App\Models\OvertimeRequest;
use App\Models\PermissionRequest;
use App\Models\WorkSchedule;
use App\Models\WorkShift;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AttendanceController extends Controller
{
    // ─── Work Shifts ───

    /**
     * GET /api/attendance/shifts
     */
    public function shifts(Request $request): JsonResponse
    {
        $shifts = WorkShift::query()
            ->when($request->boolean('active_only'), fn($q) => $q->active())
            ->orderBy('name')
            ->get();

        return response()->json(['success' => true, 'data' => $shifts]);
    }

    /**
     * POST /api/attendance/shifts
     */
    public function storeShift(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'name_ar' => 'required|string|max:255',
            'code' => 'required|string|unique:work_shifts,code',
            'type' => 'required|in:fixed,flexible,rotating,split',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i',
            'break_start' => 'nullable|date_format:H:i',
            'break_end' => 'nullable|date_format:H:i',
            'break_duration_minutes' => 'numeric|min:0',
            'total_hours' => 'required|numeric|min:0',
            'grace_period_minutes' => 'integer|min:0',
            'early_departure_minutes' => 'integer|min:0',
            'next_day_end' => 'boolean',
        ]);

        $shift = WorkShift::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'تم إنشاء الوردية بنجاح',
            'data' => $shift,
        ], 201);
    }

    // ─── Work Schedules ───

    /**
     * GET /api/attendance/schedules
     */
    public function schedules(Request $request): JsonResponse
    {
        $schedules = WorkSchedule::query()
            ->when($request->boolean('active_only'), fn($q) => $q->active())
            ->orderBy('name')
            ->get();

        return response()->json(['success' => true, 'data' => $schedules]);
    }

    /**
     * POST /api/attendance/schedules
     */
    public function storeSchedule(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'name_ar' => 'required|string|max:255',
            'type' => 'required|in:weekly,rotating,custom',
            'rotation_days' => 'nullable|integer|min:1',
            'weekly_pattern' => 'nullable|array',
            'is_default' => 'boolean',
        ]);

        if (!empty($validated['is_default'])) {
            WorkSchedule::where('is_default', true)->update(['is_default' => false]);
        }

        $schedule = WorkSchedule::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'تم إنشاء جدول الدوام بنجاح',
            'data' => $schedule,
        ], 201);
    }

    /**
     * POST /api/attendance/employee-schedules
     * تعيين موظف لجدول دوام
     */
    public function assignSchedule(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'employee_id' => 'required|uuid|exists:employees,id',
            'work_schedule_id' => 'required|uuid|exists:work_schedules,id',
            'effective_from' => 'required|date',
            'effective_to' => 'nullable|date|after:effective_from',
        ]);

        // إلغاء الجدول الحالي
        EmployeeSchedule::where('employee_id', $validated['employee_id'])
            ->where('is_current', true)
            ->update(['is_current' => false]);

        $schedule = EmployeeSchedule::create([
            ...$validated,
            'is_current' => true,
            'assigned_by' => auth()->id(),
        ]);

        $schedule->load(['employee', 'workSchedule']);

        return response()->json([
            'success' => true,
            'message' => 'تم تعيين جدول الدوام للموظف',
            'data' => $schedule,
        ], 201);
    }

    // ─── Attendance Records ───

    /**
     * GET /api/attendance/records
     */
    public function records(Request $request): JsonResponse
    {
        $records = AttendanceRecord::with(['employee', 'workShift'])
            ->when($request->filled('employee_id'), fn($q) => $q->where('employee_id', $request->input('employee_id')))
            ->when($request->filled('date'), fn($q) => $q->where('date', $request->input('date')))
            ->when($request->filled('date_from'), fn($q) => $q->where('date', '>=', $request->input('date_from')))
            ->when($request->filled('date_to'), fn($q) => $q->where('date', '<=', $request->input('date_to')))
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->input('status')))
            ->when($request->filled('department_id'), function ($q) use ($request) {
                $q->whereHas('employee', fn($e) => $e->where('department_id', $request->input('department_id')));
            })
            ->orderBy('date', 'desc')
            ->paginate($request->input('per_page', 15));

        return response()->json(['success' => true, 'data' => $records]);
    }

    /**
     * POST /api/attendance/check-in
     * تسجيل حضور
     */
    public function checkIn(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'employee_id' => 'required|uuid|exists:employees,id',
            'check_in_method' => 'nullable|in:biometric,card,mobile_gps,web,manual,qr_code',
            'check_in_latitude' => 'nullable|numeric',
            'check_in_longitude' => 'nullable|numeric',
            'notes' => 'nullable|string',
        ]);

        $today = now()->toDateString();

        $existing = AttendanceRecord::where('employee_id', $validated['employee_id'])
            ->where('date', $today)
            ->first();

        if ($existing && $existing->check_in) {
            return response()->json([
                'success' => false,
                'message' => 'تم تسجيل الحضور مسبقاً لهذا اليوم',
            ], 422);
        }

        $record = AttendanceRecord::updateOrCreate(
            ['employee_id' => $validated['employee_id'], 'date' => $today],
            [
                'check_in' => now(),
                'status' => 'present',
                'check_in_method' => $validated['check_in_method'] ?? 'web',
                'check_in_latitude' => $validated['check_in_latitude'] ?? null,
                'check_in_longitude' => $validated['check_in_longitude'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'تم تسجيل الحضور بنجاح',
            'data' => $record,
        ], 201);
    }

    /**
     * POST /api/attendance/check-out
     * تسجيل انصراف
     */
    public function checkOut(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'employee_id' => 'required|uuid|exists:employees,id',
            'check_out_method' => 'nullable|in:biometric,card,mobile_gps,web,manual,qr_code',
            'check_out_latitude' => 'nullable|numeric',
            'check_out_longitude' => 'nullable|numeric',
        ]);

        $today = now()->toDateString();

        $record = AttendanceRecord::where('employee_id', $validated['employee_id'])
            ->where('date', $today)
            ->firstOrFail();

        if ($record->check_out) {
            return response()->json([
                'success' => false,
                'message' => 'تم تسجيل الانصراف مسبقاً',
            ], 422);
        }

        $checkOut = now();
        $actualHours = $record->check_in
            ? round($record->check_in->diffInMinutes($checkOut) / 60, 2)
            : 0;

        $record->update([
            'check_out' => $checkOut,
            'actual_hours' => $actualHours,
            'check_out_method' => $validated['check_out_method'] ?? 'web',
            'check_out_latitude' => $validated['check_out_latitude'] ?? null,
            'check_out_longitude' => $validated['check_out_longitude'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم تسجيل الانصراف بنجاح',
            'data' => $record,
        ]);
    }

    /**
     * PUT /api/attendance/records/{id}/adjust
     * تعديل سجل حضور يدوياً
     */
    public function adjustRecord(string $id, Request $request): JsonResponse
    {
        $record = AttendanceRecord::findOrFail($id);

        $validated = $request->validate([
            'check_in' => 'nullable|date',
            'check_out' => 'nullable|date',
            'status' => 'nullable|in:present,absent,late,early_departure,late_and_early,on_leave,holiday,day_off,business_trip,work_from_home',
            'adjustment_reason' => 'required|string',
        ]);

        $record->update([
            ...$validated,
            'is_manually_adjusted' => true,
            'adjusted_by' => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم تعديل السجل بنجاح',
            'data' => $record,
        ]);
    }

    // ─── Overtime Requests ───

    /**
     * GET /api/attendance/overtime
     */
    public function overtimeRequests(Request $request): JsonResponse
    {
        $requests = OvertimeRequest::with('employee')
            ->when($request->filled('employee_id'), fn($q) => $q->where('employee_id', $request->input('employee_id')))
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->input('status')))
            ->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 15));

        return response()->json(['success' => true, 'data' => $requests]);
    }

    /**
     * POST /api/attendance/overtime
     */
    public function storeOvertime(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'employee_id' => 'required|uuid|exists:employees,id',
            'date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i',
            'hours' => 'required|numeric|min:0.5',
            'rate_multiplier' => 'numeric|min:1',
            'reason' => 'required|string',
            'reason_ar' => 'nullable|string',
        ]);

        $requestNumber = 'OT-' . now()->format('Y') . '-' . str_pad(
            OvertimeRequest::count() + 1, 4, '0', STR_PAD_LEFT
        );

        $overtime = OvertimeRequest::create([
            ...$validated,
            'request_number' => $requestNumber,
            'status' => 'pending',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم تقديم طلب العمل الإضافي',
            'data' => $overtime->load('employee'),
        ], 201);
    }

    /**
     * POST /api/attendance/overtime/{id}/approve
     */
    public function approveOvertime(string $id): JsonResponse
    {
        $overtime = OvertimeRequest::findOrFail($id);

        if ($overtime->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن اعتماد هذا الطلب',
            ], 422);
        }

        $overtime->update([
            'status' => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم اعتماد طلب العمل الإضافي',
            'data' => $overtime,
        ]);
    }

    /**
     * POST /api/attendance/overtime/{id}/reject
     */
    public function rejectOvertime(string $id, Request $request): JsonResponse
    {
        $overtime = OvertimeRequest::findOrFail($id);

        if ($overtime->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن رفض هذا الطلب',
            ], 422);
        }

        $overtime->update([
            'status' => 'rejected',
            'rejection_reason' => $request->input('rejection_reason'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم رفض طلب العمل الإضافي',
            'data' => $overtime,
        ]);
    }

    // ─── Permission Requests ───

    /**
     * GET /api/attendance/permissions
     */
    public function permissionRequests(Request $request): JsonResponse
    {
        $requests = PermissionRequest::with('employee')
            ->when($request->filled('employee_id'), fn($q) => $q->where('employee_id', $request->input('employee_id')))
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->input('status')))
            ->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 15));

        return response()->json(['success' => true, 'data' => $requests]);
    }

    /**
     * POST /api/attendance/permissions
     */
    public function storePermission(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'employee_id' => 'required|uuid|exists:employees,id',
            'date' => 'required|date',
            'departure_time' => 'required|date_format:H:i',
            'return_time' => 'required|date_format:H:i',
            'hours' => 'required|numeric|min:0.25',
            'type' => 'required|in:personal,medical,official,family',
            'reason' => 'required|string',
            'reason_ar' => 'nullable|string',
        ]);

        $requestNumber = 'PRM-' . now()->format('Y') . '-' . str_pad(
            PermissionRequest::count() + 1, 4, '0', STR_PAD_LEFT
        );

        $permission = PermissionRequest::create([
            ...$validated,
            'request_number' => $requestNumber,
            'status' => 'pending',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم تقديم طلب الإذن',
            'data' => $permission->load('employee'),
        ], 201);
    }

    /**
     * POST /api/attendance/permissions/{id}/approve
     */
    public function approvePermission(string $id): JsonResponse
    {
        $permission = PermissionRequest::findOrFail($id);

        if ($permission->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن اعتماد هذا الطلب',
            ], 422);
        }

        $permission->update([
            'status' => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم اعتماد طلب الإذن',
            'data' => $permission,
        ]);
    }

    // ─── Attendance Summary ───

    /**
     * GET /api/attendance/summaries
     */
    public function summaries(Request $request): JsonResponse
    {
        $summaries = AttendanceSummary::with('employee')
            ->when($request->filled('employee_id'), fn($q) => $q->where('employee_id', $request->input('employee_id')))
            ->when($request->filled('month'), fn($q) => $q->where('month', $request->input('month')))
            ->when($request->filled('year'), fn($q) => $q->where('year', $request->input('year')))
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->paginate($request->input('per_page', 15));

        return response()->json(['success' => true, 'data' => $summaries]);
    }

    /**
     * POST /api/attendance/summaries/generate
     * توليد ملخص شهري
     */
    public function generateSummary(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'month' => 'required|integer|between:1,12',
            'year' => 'required|integer|min:2020',
            'employee_id' => 'nullable|uuid|exists:employees,id',
        ]);

        $query = AttendanceRecord::where('date', '>=', "{$validated['year']}-" . str_pad($validated['month'], 2, '0', STR_PAD_LEFT) . "-01")
            ->where('date', '<=', "{$validated['year']}-" . str_pad($validated['month'], 2, '0', STR_PAD_LEFT) . "-31");

        if (!empty($validated['employee_id'])) {
            $query->where('employee_id', $validated['employee_id']);
        }

        $records = $query->get()->groupBy('employee_id');
        $generated = 0;

        foreach ($records as $employeeId => $employeeRecords) {
            AttendanceSummary::updateOrCreate(
                [
                    'employee_id' => $employeeId,
                    'month' => $validated['month'],
                    'year' => $validated['year'],
                ],
                [
                    'present_days' => $employeeRecords->where('status', 'present')->count(),
                    'absent_days' => $employeeRecords->where('status', 'absent')->count(),
                    'late_days' => $employeeRecords->whereIn('status', ['late', 'late_and_early'])->count(),
                    'early_departure_days' => $employeeRecords->whereIn('status', ['early_departure', 'late_and_early'])->count(),
                    'leave_days' => $employeeRecords->where('status', 'on_leave')->count(),
                    'holiday_days' => $employeeRecords->where('status', 'holiday')->count(),
                    'day_off_days' => $employeeRecords->where('status', 'day_off')->count(),
                    'total_working_hours' => $employeeRecords->sum('actual_hours'),
                    'total_overtime_hours' => $employeeRecords->sum('overtime_hours'),
                    'total_late_minutes' => $employeeRecords->sum('late_minutes'),
                    'total_early_minutes' => $employeeRecords->sum('early_departure_minutes'),
                    'working_days' => $employeeRecords->count(),
                    'attendance_rate' => $employeeRecords->count() > 0
                        ? round(($employeeRecords->where('status', 'present')->count() / $employeeRecords->count()) * 100, 2)
                        : 0,
                ]
            );
            $generated++;
        }

        return response()->json([
            'success' => true,
            'message' => "تم توليد ملخصات الحضور لـ {$generated} موظف",
        ]);
    }
}
