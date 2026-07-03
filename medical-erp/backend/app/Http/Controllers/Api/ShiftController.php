<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Shift;
use App\Models\ShiftAssignment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ShiftController extends Controller
{
    /**
     * GET /api/shifts
     * قائمة الورديات
     */
    public function index(Request $request): JsonResponse
    {
        $shifts = Shift::query()
            ->withCount('assignments')
            ->when($request->filled('is_active'), fn($q) => $q->where('is_active', $request->boolean('is_active')))
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('name_ar', 'like', "%{$search}%")
                      ->orWhere('code', 'like', "%{$search}%");
                });
            })
            ->orderBy('sort_order')
            ->orderBy('start_time')
            ->paginate($request->input('per_page', 15));

        return response()->json([
            'success' => true,
            'message' => 'تم جلب قائمة الورديات بنجاح',
            'data' => $shifts,
        ]);
    }

    /**
     * POST /api/shifts
     * إنشاء وردية جديدة
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'code' => 'required|string|unique:shifts,code',
            'name' => 'required|string|max:255',
            'name_ar' => 'required|string|max:255',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i',
            'break_minutes' => 'nullable|integer|min:0',
            'grace_period_minutes' => 'nullable|integer|min:0',
            'is_overnight' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'description' => 'nullable|string',
            'sort_order' => 'nullable|integer',
        ]);

        try {
            $shift = Shift::create($request->only([
                'code', 'name', 'name_ar', 'start_time', 'end_time',
                'break_minutes', 'grace_period_minutes', 'is_overnight',
                'is_active', 'description', 'sort_order',
            ]));

            return response()->json([
                'success' => true,
                'message' => 'تم إنشاء الوردية بنجاح',
                'data' => $shift,
            ], 201);
        } catch (\Illuminate\Database\QueryException $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إنشاء الوردية',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * GET /api/shifts/{id}
     * عرض وردية مع الإسنادات السارية
     */
    public function show(string $id): JsonResponse
    {
        $shift = Shift::with([
            'assignments' => fn($q) => $q->activeOn(now()->toDateString())
                ->with('employee:id,employee_number,first_name,first_name_ar,last_name,last_name_ar,department_id'),
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'message' => 'تم جلب بيانات الوردية بنجاح',
            'data' => $shift,
        ]);
    }

    /**
     * PUT /api/shifts/{id}
     * تحديث وردية
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $shift = Shift::findOrFail($id);

        $request->validate([
            'code' => 'sometimes|string|unique:shifts,code,' . $shift->id,
            'name' => 'sometimes|string|max:255',
            'name_ar' => 'sometimes|string|max:255',
            'start_time' => 'sometimes|date_format:H:i',
            'end_time' => 'sometimes|date_format:H:i',
            'break_minutes' => 'nullable|integer|min:0',
            'grace_period_minutes' => 'nullable|integer|min:0',
            'is_overnight' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'description' => 'nullable|string',
            'sort_order' => 'nullable|integer',
        ]);

        $shift->update($request->only([
            'code', 'name', 'name_ar', 'start_time', 'end_time',
            'break_minutes', 'grace_period_minutes', 'is_overnight',
            'is_active', 'description', 'sort_order',
        ]));

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث الوردية بنجاح',
            'data' => $shift,
        ]);
    }

    /**
     * DELETE /api/shifts/{id}
     * حذف وردية (لا يمكن إذا كانت مسندة لموظفين)
     */
    public function destroy(string $id): JsonResponse
    {
        $shift = Shift::withCount(['assignments', 'attendanceRecords'])->findOrFail($id);

        if ($shift->assignments_count > 0 || $shift->attendance_records_count > 0) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن حذف الوردية لوجود إسنادات أو سجلات حضور مرتبطة بها',
            ], 422);
        }

        $shift->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف الوردية بنجاح',
        ]);
    }

    /**
     * POST /api/shifts/{id}/assign
     * إسناد موظفين إلى الوردية (يغلق الإسناد المفتوح السابق تلقائياً)
     */
    public function assign(Request $request, string $id): JsonResponse
    {
        $shift = Shift::findOrFail($id);

        $request->validate([
            'employee_ids' => 'required|array|min:1',
            'employee_ids.*' => 'required|uuid|exists:employees,id',
            'effective_from' => 'required|date',
            'effective_to' => 'nullable|date|after_or_equal:effective_from',
            'notes' => 'nullable|string',
        ]);

        $effectiveFrom = Carbon::parse($request->input('effective_from'));
        $assignments = [];

        foreach ($request->input('employee_ids') as $employeeId) {
            // إغلاق أي إسناد مفتوح للموظف قبل بداية الإسناد الجديد
            ShiftAssignment::where('employee_id', $employeeId)
                ->whereNull('effective_to')
                ->whereDate('effective_from', '<', $effectiveFrom->toDateString())
                ->update(['effective_to' => $effectiveFrom->copy()->subDay()->toDateString()]);

            // حذف الإسنادات التي تبدأ في نفس التاريخ (استبدال)
            ShiftAssignment::where('employee_id', $employeeId)
                ->whereDate('effective_from', $effectiveFrom->toDateString())
                ->delete();

            $assignments[] = ShiftAssignment::create([
                'employee_id' => $employeeId,
                'shift_id' => $shift->id,
                'effective_from' => $request->input('effective_from'),
                'effective_to' => $request->input('effective_to'),
                'notes' => $request->input('notes'),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم إسناد الوردية للموظفين بنجاح',
            'data' => $assignments,
        ], 201);
    }

    /**
     * GET /api/shift-assignments
     * قائمة إسنادات الورديات
     */
    public function assignments(Request $request): JsonResponse
    {
        $assignments = ShiftAssignment::query()
            ->with([
                'shift:id,code,name,name_ar,start_time,end_time',
                'employee:id,employee_number,first_name,first_name_ar,last_name,last_name_ar,department_id',
                'employee.department:id,name,name_ar',
            ])
            ->when($request->filled('shift_id'), fn($q) => $q->where('shift_id', $request->input('shift_id')))
            ->when($request->filled('employee_id'), fn($q) => $q->where('employee_id', $request->input('employee_id')))
            ->when($request->boolean('active_only'), fn($q) => $q->activeOn(now()->toDateString()))
            ->orderByDesc('effective_from')
            ->paginate($request->input('per_page', 15));

        return response()->json([
            'success' => true,
            'message' => 'تم جلب إسنادات الورديات بنجاح',
            'data' => $assignments,
        ]);
    }

    /**
     * DELETE /api/shift-assignments/{id}
     * إلغاء إسناد وردية
     */
    public function unassign(string $assignmentId): JsonResponse
    {
        $assignment = ShiftAssignment::findOrFail($assignmentId);
        $assignment->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم إلغاء إسناد الوردية بنجاح',
        ]);
    }
}
