<?php

namespace App\Http\Controllers\Api\Roster;

use App\Http\Controllers\Controller;
use App\Http\Resources\Roster\RosterAssignmentResource;
use App\Http\Resources\Roster\RosterResource;
use App\Models\Roster\Roster;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class RosterController extends Controller
{
    /**
     * قائمة الجداول
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Roster::with(['department', 'createdBy'])
            ->withCount('assignments')
            ->when($request->department_id, fn($q, $id) => $q->where('department_id', $id))
            ->when($request->status, fn($q, $status) => $q->where('status', $status))
            ->when($request->year, fn($q, $year) => $q->where('year', $year))
            ->when($request->month, fn($q, $month) => $q->where('month', $month))
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc');

        $rosters = $request->per_page
            ? $query->paginate($request->per_page)
            : $query->get();

        return RosterResource::collection($rosters);
    }

    /**
     * الجدول الحالي
     */
    public function current(Request $request): JsonResponse
    {
        $departmentId = $request->get('department_id');

        $roster = Roster::with(['department', 'assignments.employee', 'assignments.shiftPattern'])
            ->where('year', now()->year)
            ->where('month', now()->month)
            ->when($departmentId, fn($q, $id) => $q->where('department_id', $id))
            ->first();

        if (!$roster) {
            return response()->json([
                'success' => false,
                'message' => 'لا يوجد جدول للشهر الحالي',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new RosterResource($roster),
        ]);
    }

    /**
     * إنشاء جدول جديد
     */
    public function store(Request $request): JsonResponse
    {
        if (Gate::denies('roster.manage')) {
            abort(403, 'غير مصرح لك بإنشاء جداول');
        }

        $validated = $request->validate([
            'department_id' => ['required', 'uuid', 'exists:departments,id'],
            'year' => ['required', 'integer', 'min:2020', 'max:2100'],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
            'name' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        // التحقق من عدم وجود جدول للنفس الفترة
        $exists = Roster::where('department_id', $validated['department_id'])
            ->where('year', $validated['year'])
            ->where('month', $validated['month'])
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'يوجد جدول مسبق لهذا القسم في هذه الفترة',
            ], 422);
        }

        $validated['created_by'] = auth()->id();
        $validated['status'] = 'draft';

        $roster = Roster::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'تم إنشاء الجدول بنجاح',
            'data' => new RosterResource($roster->load('department')),
        ], 201);
    }

    /**
     * عرض جدول
     */
    public function show(Roster $roster): RosterResource
    {
        return new RosterResource(
            $roster->load([
                'department',
                'createdBy',
                'assignments.employee.position',
                'assignments.shiftPattern',
            ])->loadCount('assignments')
        );
    }

    /**
     * تحديث جدول
     */
    public function update(Request $request, Roster $roster): JsonResponse
    {
        if (Gate::denies('roster.manage')) {
            abort(403, 'غير مصرح لك بتعديل الجداول');
        }

        if ($roster->is_locked) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن تعديل جدول مقفل',
            ], 422);
        }

        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $roster->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث الجدول بنجاح',
            'data' => new RosterResource($roster->fresh('department')),
        ]);
    }

    /**
     * نشر الجدول
     */
    public function publish(Roster $roster): JsonResponse
    {
        if (Gate::denies('roster.manage')) {
            abort(403, 'غير مصرح لك بنشر الجداول');
        }

        if ($roster->status === 'published') {
            return response()->json([
                'success' => false,
                'message' => 'الجدول منشور مسبقاً',
            ], 422);
        }

        $roster->update([
            'status' => 'published',
            'published_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم نشر الجدول بنجاح',
            'data' => new RosterResource($roster->fresh()),
        ]);
    }

    /**
     * قفل الجدول
     */
    public function lock(Roster $roster): JsonResponse
    {
        if (Gate::denies('roster.manage')) {
            abort(403, 'غير مصرح لك بقفل الجداول');
        }

        if ($roster->is_locked) {
            return response()->json([
                'success' => false,
                'message' => 'الجدول مقفل مسبقاً',
            ], 422);
        }

        $roster->update([
            'is_locked' => true,
            'locked_at' => now(),
            'locked_by' => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم قفل الجدول بنجاح',
            'data' => new RosterResource($roster->fresh()),
        ]);
    }

    /**
     * التحقق من صحة الجدول
     */
    public function validateRoster(Roster $roster): JsonResponse
    {
        $violations = [];

        // التحقق من عدم وجود موظفين بدون تعيينات
        $assignedEmployeeIds = $roster->assignments()->pluck('employee_id')->unique();

        // التحقق من عدم تجاوز ساعات العمل الأسبوعية
        $weeklyHoursViolations = $this->checkWeeklyHoursViolations($roster);
        if (!empty($weeklyHoursViolations)) {
            $violations = array_merge($violations, $weeklyHoursViolations);
        }

        // التحقق من الفجوات في التغطية
        $coverageGaps = $this->checkCoverageGaps($roster);
        if (!empty($coverageGaps)) {
            $violations = array_merge($violations, $coverageGaps);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'is_valid' => empty($violations),
                'violations' => $violations,
                'assignments_count' => $roster->assignments()->count(),
            ],
        ]);
    }

    /**
     * تعيينات الجدول
     */
    public function assignments(Roster $roster): AnonymousResourceCollection
    {
        $assignments = $roster->assignments()
            ->with(['employee.position', 'shiftPattern'])
            ->orderBy('assignment_date')
            ->orderBy('scheduled_start')
            ->get();

        return RosterAssignmentResource::collection($assignments);
    }

    /**
     * التحقق من تجاوز ساعات العمل الأسبوعية
     */
    protected function checkWeeklyHoursViolations(Roster $roster): array
    {
        $violations = [];
        $maxWeeklyHours = 48; // الحد الأقصى للساعات الأسبوعية

        $assignments = $roster->assignments()
            ->with('employee')
            ->get()
            ->groupBy('employee_id');

        foreach ($assignments as $employeeId => $employeeAssignments) {
            $weeklyHours = $employeeAssignments->groupBy(function ($assignment) {
                return $assignment->assignment_date->startOfWeek()->format('Y-m-d');
            });

            foreach ($weeklyHours as $weekStart => $weekAssignments) {
                $totalHours = $weekAssignments->sum('scheduled_hours');
                if ($totalHours > $maxWeeklyHours) {
                    $employee = $employeeAssignments->first()->employee;
                    $violations[] = [
                        'type' => 'weekly_hours_exceeded',
                        'message' => "الموظف {$employee->full_name_ar} تجاوز ساعات العمل الأسبوعية ({$totalHours} ساعة)",
                        'employee_id' => $employeeId,
                        'week_start' => $weekStart,
                        'hours' => $totalHours,
                    ];
                }
            }
        }

        return $violations;
    }

    /**
     * التحقق من الفجوات في التغطية
     */
    protected function checkCoverageGaps(Roster $roster): array
    {
        // يمكن تطوير هذه الدالة لاحقاً للتحقق من وجود تغطية كافية
        return [];
    }
}
