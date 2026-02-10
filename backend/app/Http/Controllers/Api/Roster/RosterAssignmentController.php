<?php

namespace App\Http\Controllers\Api\Roster;

use App\Http\Controllers\Controller;
use App\Http\Requests\Roster\StoreRosterAssignmentRequest;
use App\Http\Resources\Roster\RosterAssignmentResource;
use App\Models\Roster\Roster;
use App\Models\Roster\RosterAssignment;
use App\Models\Roster\ShiftPattern;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class RosterAssignmentController extends Controller
{
    /**
     * قائمة التعيينات
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = RosterAssignment::with(['employee.department', 'shiftPattern', 'roster'])
            ->when($request->roster_id, fn($q, $id) => $q->where('roster_id', $id))
            ->when($request->employee_id, fn($q, $id) => $q->where('employee_id', $id))
            ->when($request->status, fn($q, $status) => $q->where('status', $status))
            ->when($request->type, fn($q, $type) => $q->where('type', $type))
            ->when($request->date_from, fn($q, $date) => $q->where('assignment_date', '>=', $date))
            ->when($request->date_to, fn($q, $date) => $q->where('assignment_date', '<=', $date))
            ->orderBy('assignment_date')
            ->orderBy('scheduled_start');

        $assignments = $request->per_page
            ? $query->paginate($request->per_page)
            : $query->get();

        return RosterAssignmentResource::collection($assignments);
    }

    /**
     * تعيينات موظف معين
     */
    public function byEmployee(string $employeeId, Request $request): AnonymousResourceCollection
    {
        $assignments = RosterAssignment::with(['shiftPattern', 'roster'])
            ->where('employee_id', $employeeId)
            ->when($request->date_from, fn($q, $date) => $q->where('assignment_date', '>=', $date))
            ->when($request->date_to, fn($q, $date) => $q->where('assignment_date', '<=', $date))
            ->orderBy('assignment_date')
            ->get();

        return RosterAssignmentResource::collection($assignments);
    }

    /**
     * تعيينات تاريخ معين
     */
    public function byDate(string $date, Request $request): AnonymousResourceCollection
    {
        $assignments = RosterAssignment::with(['employee.department', 'employee.position', 'shiftPattern'])
            ->whereDate('assignment_date', $date)
            ->when($request->department_id, function ($q, $deptId) {
                $q->whereHas('employee', fn($eq) => $eq->where('department_id', $deptId));
            })
            ->orderBy('scheduled_start')
            ->get();

        return RosterAssignmentResource::collection($assignments);
    }

    /**
     * إنشاء تعيين جديد
     */
    public function store(StoreRosterAssignmentRequest $request): JsonResponse
    {
        if (Gate::denies('roster.manage')) {
            abort(403, 'غير مصرح لك بإنشاء تعيينات');
        }

        $data = $request->validated();

        // التحقق من عدم وجود تعيين مسبق لنفس الموظف في نفس اليوم والفترة
        $exists = RosterAssignment::where('employee_id', $data['employee_id'])
            ->whereDate('assignment_date', $data['assignment_date'])
            ->where(function ($q) use ($data) {
                $q->whereBetween('scheduled_start', [$data['scheduled_start'], $data['scheduled_end']])
                    ->orWhereBetween('scheduled_end', [$data['scheduled_start'], $data['scheduled_end']]);
            })
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'يوجد تعيين متداخل للموظف في نفس الفترة',
            ], 422);
        }

        // جلب ساعات الوردية من النمط إذا لم تُحدد
        if (empty($data['scheduled_hours']) && !empty($data['shift_pattern_id'])) {
            $pattern = ShiftPattern::find($data['shift_pattern_id']);
            if ($pattern) {
                $data['scheduled_hours'] = $pattern->scheduled_hours ?? $pattern->duration_hours;
            }
        }

        $assignment = RosterAssignment::create($data);

        return response()->json([
            'success' => true,
            'message' => 'تم إنشاء التعيين بنجاح',
            'data' => new RosterAssignmentResource($assignment->load(['employee', 'shiftPattern'])),
        ], 201);
    }

    /**
     * إنشاء تعيينات متعددة
     */
    public function bulkStore(Request $request): JsonResponse
    {
        if (Gate::denies('roster.manage')) {
            abort(403, 'غير مصرح لك بإنشاء تعيينات');
        }

        $validated = $request->validate([
            'roster_id' => ['required', 'uuid', 'exists:rosters,id'],
            'assignments' => ['required', 'array', 'min:1'],
            'assignments.*.employee_id' => ['required', 'uuid', 'exists:employees,id'],
            'assignments.*.shift_pattern_id' => ['required', 'uuid', 'exists:shift_patterns,id'],
            'assignments.*.assignment_date' => ['required', 'date'],
            'assignments.*.type' => ['sometimes', 'in:regular,overtime,on_call,off'],
            'assignments.*.notes' => ['nullable', 'string', 'max:500'],
        ]);

        $roster = Roster::findOrFail($validated['roster_id']);

        if ($roster->is_locked) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن إضافة تعيينات لجدول مقفل',
            ], 422);
        }

        $createdCount = 0;
        $skippedCount = 0;
        $errors = [];

        DB::beginTransaction();
        try {
            foreach ($validated['assignments'] as $index => $assignmentData) {
                $pattern = ShiftPattern::find($assignmentData['shift_pattern_id']);

                // التحقق من عدم وجود تعيين مسبق
                $exists = RosterAssignment::where('employee_id', $assignmentData['employee_id'])
                    ->whereDate('assignment_date', $assignmentData['assignment_date'])
                    ->where('roster_id', $validated['roster_id'])
                    ->exists();

                if ($exists) {
                    $skippedCount++;
                    $errors[] = "التعيين رقم " . ($index + 1) . " متكرر";
                    continue;
                }

                RosterAssignment::create([
                    'roster_id' => $validated['roster_id'],
                    'employee_id' => $assignmentData['employee_id'],
                    'shift_pattern_id' => $assignmentData['shift_pattern_id'],
                    'assignment_date' => $assignmentData['assignment_date'],
                    'type' => $assignmentData['type'] ?? 'regular',
                    'scheduled_start' => $pattern?->start_time,
                    'scheduled_end' => $pattern?->end_time,
                    'scheduled_hours' => $pattern?->scheduled_hours ?? $pattern?->duration_hours,
                    'status' => 'scheduled',
                    'notes' => $assignmentData['notes'] ?? null,
                ]);

                $createdCount++;
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "تم إنشاء {$createdCount} تعيين بنجاح" . ($skippedCount > 0 ? " (تم تخطي {$skippedCount})" : ''),
                'data' => [
                    'created' => $createdCount,
                    'skipped' => $skippedCount,
                    'errors' => $errors,
                ],
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إنشاء التعيينات',
            ], 500);
        }
    }

    /**
     * عرض تعيين
     */
    public function show(RosterAssignment $assignment): RosterAssignmentResource
    {
        return new RosterAssignmentResource(
            $assignment->load(['employee.department', 'employee.position', 'shiftPattern', 'roster', 'attendance'])
        );
    }

    /**
     * تحديث تعيين
     */
    public function update(Request $request, RosterAssignment $assignment): JsonResponse
    {
        if (Gate::denies('roster.manage')) {
            abort(403, 'غير مصرح لك بتعديل التعيينات');
        }

        // التحقق من أن الجدول غير مقفل
        if ($assignment->roster && $assignment->roster->is_locked) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن تعديل تعيين في جدول مقفل',
            ], 422);
        }

        $validated = $request->validate([
            'shift_pattern_id' => ['sometimes', 'uuid', 'exists:shift_patterns,id'],
            'type' => ['sometimes', 'in:regular,overtime,on_call,off'],
            'scheduled_start' => ['sometimes', 'date_format:H:i'],
            'scheduled_end' => ['sometimes', 'date_format:H:i'],
            'scheduled_hours' => ['nullable', 'numeric', 'min:0', 'max:24'],
            'status' => ['sometimes', 'in:scheduled,present,absent,late,on_leave,sick,completed'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $validated['updated_by'] = auth()->id();

        $assignment->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث التعيين بنجاح',
            'data' => new RosterAssignmentResource($assignment->fresh(['employee', 'shiftPattern'])),
        ]);
    }

    /**
     * حذف تعيين
     */
    public function destroy(RosterAssignment $assignment): JsonResponse
    {
        if (Gate::denies('roster.manage')) {
            abort(403, 'غير مصرح لك بحذف التعيينات');
        }

        // التحقق من أن الجدول غير مقفل
        if ($assignment->roster && $assignment->roster->is_locked) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن حذف تعيين من جدول مقفل',
            ], 422);
        }

        // التحقق من عدم وجود سجل حضور
        if ($assignment->attendance()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن حذف تعيين له سجل حضور',
            ], 422);
        }

        $assignment->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف التعيين بنجاح',
        ]);
    }
}
