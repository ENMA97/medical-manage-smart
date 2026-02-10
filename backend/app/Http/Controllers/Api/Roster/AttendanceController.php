<?php

namespace App\Http\Controllers\Api\Roster;

use App\Http\Controllers\Controller;
use App\Models\HR\Employee;
use App\Models\Roster\AttendanceRecord;
use App\Models\Roster\RosterAssignment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AttendanceController extends Controller
{
    /**
     * قائمة سجلات الحضور
     */
    public function index(Request $request): JsonResponse
    {
        $query = AttendanceRecord::with(['employee.department', 'device'])
            ->when($request->employee_id, fn($q, $id) => $q->where('employee_id', $id))
            ->when($request->device_id, fn($q, $id) => $q->where('device_id', $id))
            ->when($request->type, fn($q, $type) => $q->where('type', $type))
            ->when($request->source, fn($q, $source) => $q->where('source', $source))
            ->when($request->is_valid !== null, fn($q) => $q->where('is_valid', $request->boolean('is_valid')))
            ->when($request->date_from, fn($q, $date) => $q->where('punched_at', '>=', $date))
            ->when($request->date_to, fn($q, $date) => $q->where('punched_at', '<=', $date . ' 23:59:59'))
            ->orderBy('punched_at', 'desc');

        $records = $request->per_page
            ? $query->paginate($request->per_page)
            : $query->get();

        return response()->json([
            'success' => true,
            'data' => $records,
        ]);
    }

    /**
     * سجلات اليوم
     */
    public function today(Request $request): JsonResponse
    {
        $records = AttendanceRecord::with(['employee.department', 'employee.position'])
            ->whereDate('punched_at', now()->toDateString())
            ->when($request->department_id, function ($q, $deptId) {
                $q->whereHas('employee', fn($eq) => $eq->where('department_id', $deptId));
            })
            ->orderBy('punched_at', 'desc')
            ->get();

        // تجميع حسب الموظف
        $grouped = $records->groupBy('employee_id')->map(function ($employeeRecords) {
            $employee = $employeeRecords->first()->employee;
            $checkIn = $employeeRecords->where('type', 'check_in')->sortBy('punched_at')->first();
            $checkOut = $employeeRecords->where('type', 'check_out')->sortByDesc('punched_at')->first();

            return [
                'employee' => $employee,
                'check_in' => $checkIn?->punched_at,
                'check_out' => $checkOut?->punched_at,
                'records_count' => $employeeRecords->count(),
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data' => $grouped,
        ]);
    }

    /**
     * سجلات موظف معين
     */
    public function byEmployee(string $employeeId, Request $request): JsonResponse
    {
        $employee = Employee::findOrFail($employeeId);

        $records = AttendanceRecord::where('employee_id', $employeeId)
            ->when($request->date_from, fn($q, $date) => $q->where('punched_at', '>=', $date))
            ->when($request->date_to, fn($q, $date) => $q->where('punched_at', '<=', $date . ' 23:59:59'))
            ->orderBy('punched_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'employee' => $employee,
                'records' => $records,
            ],
        ]);
    }

    /**
     * تسجيل حضور
     */
    public function checkIn(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'employee_id' => ['required', 'uuid', 'exists:employees,id'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'location_name' => ['nullable', 'string', 'max:200'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        // التحقق من عدم وجود تسجيل حضور سابق لنفس اليوم
        $existingCheckIn = AttendanceRecord::where('employee_id', $validated['employee_id'])
            ->whereDate('punched_at', now()->toDateString())
            ->where('type', 'check_in')
            ->first();

        if ($existingCheckIn) {
            return response()->json([
                'success' => false,
                'message' => 'تم تسجيل الحضور مسبقاً اليوم',
                'data' => $existingCheckIn,
            ], 422);
        }

        $record = AttendanceRecord::create([
            'employee_id' => $validated['employee_id'],
            'type' => 'check_in',
            'punched_at' => now(),
            'source' => 'mobile',
            'latitude' => $validated['latitude'] ?? null,
            'longitude' => $validated['longitude'] ?? null,
            'location_name' => $validated['location_name'] ?? null,
            'is_valid' => true,
            'notes' => $validated['notes'] ?? null,
        ]);

        // تحديث تعيين الوردية إن وجد
        $assignment = RosterAssignment::where('employee_id', $validated['employee_id'])
            ->whereDate('assignment_date', now()->toDateString())
            ->where('status', 'scheduled')
            ->first();

        if ($assignment) {
            $assignment->checkIn(now());
        }

        return response()->json([
            'success' => true,
            'message' => 'تم تسجيل الحضور بنجاح',
            'data' => $record,
        ], 201);
    }

    /**
     * تسجيل انصراف
     */
    public function checkOut(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'employee_id' => ['required', 'uuid', 'exists:employees,id'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'location_name' => ['nullable', 'string', 'max:200'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        // التحقق من وجود تسجيل حضور لنفس اليوم
        $checkIn = AttendanceRecord::where('employee_id', $validated['employee_id'])
            ->whereDate('punched_at', now()->toDateString())
            ->where('type', 'check_in')
            ->first();

        if (!$checkIn) {
            return response()->json([
                'success' => false,
                'message' => 'لم يتم تسجيل الحضور اليوم',
            ], 422);
        }

        // التحقق من عدم وجود تسجيل انصراف سابق
        $existingCheckOut = AttendanceRecord::where('employee_id', $validated['employee_id'])
            ->whereDate('punched_at', now()->toDateString())
            ->where('type', 'check_out')
            ->first();

        if ($existingCheckOut) {
            return response()->json([
                'success' => false,
                'message' => 'تم تسجيل الانصراف مسبقاً اليوم',
                'data' => $existingCheckOut,
            ], 422);
        }

        $record = AttendanceRecord::create([
            'employee_id' => $validated['employee_id'],
            'type' => 'check_out',
            'punched_at' => now(),
            'source' => 'mobile',
            'latitude' => $validated['latitude'] ?? null,
            'longitude' => $validated['longitude'] ?? null,
            'location_name' => $validated['location_name'] ?? null,
            'is_valid' => true,
            'notes' => $validated['notes'] ?? null,
        ]);

        // تحديث تعيين الوردية إن وجد
        $assignment = RosterAssignment::where('employee_id', $validated['employee_id'])
            ->whereDate('assignment_date', now()->toDateString())
            ->whereIn('status', ['present', 'late'])
            ->first();

        if ($assignment) {
            $assignment->checkOut(now());
        }

        return response()->json([
            'success' => true,
            'message' => 'تم تسجيل الانصراف بنجاح',
            'data' => $record,
        ], 201);
    }

    /**
     * إدخال يدوي
     */
    public function manualEntry(Request $request): JsonResponse
    {
        if (Gate::denies('roster.manage')) {
            abort(403, 'غير مصرح لك بإدخال سجلات يدوية');
        }

        $validated = $request->validate([
            'employee_id' => ['required', 'uuid', 'exists:employees,id'],
            'type' => ['required', 'in:check_in,check_out,break_start,break_end'],
            'punched_at' => ['required', 'date'],
            'notes' => ['required', 'string', 'max:500'],
        ]);

        $record = AttendanceRecord::create([
            'employee_id' => $validated['employee_id'],
            'type' => $validated['type'],
            'punched_at' => $validated['punched_at'],
            'source' => 'manual',
            'is_valid' => true,
            'notes' => $validated['notes'],
            'processed_by' => auth()->id(),
            'processed_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم إنشاء السجل اليدوي بنجاح',
            'data' => $record->load('employee'),
        ], 201);
    }

    /**
     * معالجة سجل
     */
    public function process(Request $request, AttendanceRecord $record): JsonResponse
    {
        if (Gate::denies('roster.manage')) {
            abort(403, 'غير مصرح لك بمعالجة السجلات');
        }

        $validated = $request->validate([
            'is_valid' => ['required', 'boolean'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $record->update([
            'is_valid' => $validated['is_valid'],
            'notes' => $validated['notes'] ?? $record->notes,
            'processed_by' => auth()->id(),
            'processed_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم معالجة السجل بنجاح',
            'data' => $record->fresh(['employee']),
        ]);
    }
}
