<?php

namespace App\Http\Controllers\Api\Roster;

use App\Http\Controllers\Controller;
use App\Models\Roster\RosterAssignment;
use App\Models\Roster\ShiftSwapRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class ShiftSwapController extends Controller
{
    /**
     * قائمة طلبات التبديل
     */
    public function index(Request $request): JsonResponse
    {
        $query = ShiftSwapRequest::with([
            'fromAssignment.employee',
            'fromAssignment.shiftPattern',
            'toAssignment.employee',
            'toAssignment.shiftPattern',
            'requestedBy',
        ])
            ->when($request->status, fn($q, $status) => $q->where('status', $status))
            ->when($request->employee_id, function ($q, $id) {
                $q->where(function ($query) use ($id) {
                    $query->whereHas('fromAssignment', fn($aq) => $aq->where('employee_id', $id))
                        ->orWhereHas('toAssignment', fn($aq) => $aq->where('employee_id', $id));
                });
            })
            ->orderBy('created_at', 'desc');

        $requests = $request->per_page
            ? $query->paginate($request->per_page)
            : $query->get();

        return response()->json([
            'success' => true,
            'data' => $requests,
        ]);
    }

    /**
     * طلبات التبديل المنتظرة للمستخدم الحالي
     */
    public function pendingForMe(): JsonResponse
    {
        $user = auth()->user();

        if (!$user->employee) {
            return response()->json([
                'success' => true,
                'data' => [],
            ]);
        }

        $employeeId = $user->employee->id;

        $requests = ShiftSwapRequest::with([
            'fromAssignment.employee',
            'fromAssignment.shiftPattern',
            'toAssignment.employee',
            'toAssignment.shiftPattern',
        ])
            ->where('status', 'pending_target')
            ->whereHas('toAssignment', fn($q) => $q->where('employee_id', $employeeId))
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $requests,
        ]);
    }

    /**
     * إنشاء طلب تبديل
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'from_assignment_id' => ['required', 'uuid', 'exists:roster_assignments,id'],
            'to_assignment_id' => ['required', 'uuid', 'exists:roster_assignments,id', 'different:from_assignment_id'],
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $fromAssignment = RosterAssignment::with('employee')->findOrFail($validated['from_assignment_id']);
        $toAssignment = RosterAssignment::with('employee')->findOrFail($validated['to_assignment_id']);

        // التحقق من أن المستخدم هو صاحب التعيين الأول
        $user = auth()->user();
        if ($user->employee && $user->employee->id !== $fromAssignment->employee_id) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكنك طلب تبديل وردية لست معيناً فيها',
            ], 403);
        }

        // التحقق من أن التعيينات في نفس التاريخ أو يمكن تبديلها
        if ($fromAssignment->roster_id !== $toAssignment->roster_id) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن تبديل ورديات من جداول مختلفة',
            ], 422);
        }

        // التحقق من عدم وجود طلب معلق
        $existingRequest = ShiftSwapRequest::where('from_assignment_id', $validated['from_assignment_id'])
            ->whereIn('status', ['pending_target', 'pending_supervisor'])
            ->first();

        if ($existingRequest) {
            return response()->json([
                'success' => false,
                'message' => 'يوجد طلب تبديل معلق لهذه الوردية',
            ], 422);
        }

        $swapRequest = ShiftSwapRequest::create([
            'from_assignment_id' => $validated['from_assignment_id'],
            'to_assignment_id' => $validated['to_assignment_id'],
            'reason' => $validated['reason'],
            'status' => 'pending_target',
            'requested_by' => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم إنشاء طلب التبديل بنجاح',
            'data' => $swapRequest->load([
                'fromAssignment.employee',
                'toAssignment.employee',
            ]),
        ], 201);
    }

    /**
     * عرض طلب تبديل
     */
    public function show(ShiftSwapRequest $swapRequest): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $swapRequest->load([
                'fromAssignment.employee.department',
                'fromAssignment.shiftPattern',
                'toAssignment.employee.department',
                'toAssignment.shiftPattern',
                'requestedBy',
                'approvedBy',
            ]),
        ]);
    }

    /**
     * رد الموظف المستهدف
     */
    public function targetRespond(Request $request, ShiftSwapRequest $swapRequest): JsonResponse
    {
        $validated = $request->validate([
            'accepted' => ['required', 'boolean'],
            'response_notes' => ['nullable', 'string', 'max:500'],
        ]);

        if ($swapRequest->status !== 'pending_target') {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن الرد على هذا الطلب',
            ], 422);
        }

        // التحقق من أن المستخدم هو الموظف المستهدف
        $user = auth()->user();
        $toAssignment = $swapRequest->toAssignment;
        if ($user->employee && $user->employee->id !== $toAssignment->employee_id) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح لك بالرد على هذا الطلب',
            ], 403);
        }

        if ($validated['accepted']) {
            $swapRequest->update([
                'status' => 'pending_supervisor',
                'target_responded_at' => now(),
                'target_response_notes' => $validated['response_notes'] ?? null,
            ]);

            $message = 'تم قبول طلب التبديل وهو بانتظار موافقة المشرف';
        } else {
            $swapRequest->update([
                'status' => 'rejected_by_target',
                'target_responded_at' => now(),
                'target_response_notes' => $validated['response_notes'] ?? null,
            ]);

            $message = 'تم رفض طلب التبديل';
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $swapRequest->fresh(),
        ]);
    }

    /**
     * موافقة المشرف
     */
    public function supervisorApprove(Request $request, ShiftSwapRequest $swapRequest): JsonResponse
    {
        if (Gate::denies('roster.manage')) {
            abort(403, 'غير مصرح لك بالموافقة على طلبات التبديل');
        }

        $validated = $request->validate([
            'approved' => ['required', 'boolean'],
            'supervisor_notes' => ['nullable', 'string', 'max:500'],
        ]);

        if ($swapRequest->status !== 'pending_supervisor') {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن الموافقة على هذا الطلب',
            ], 422);
        }

        if ($validated['approved']) {
            DB::beginTransaction();
            try {
                // تبديل الموظفين في التعيينات
                $fromAssignment = $swapRequest->fromAssignment;
                $toAssignment = $swapRequest->toAssignment;

                $tempEmployeeId = $fromAssignment->employee_id;
                $fromAssignment->update(['employee_id' => $toAssignment->employee_id]);
                $toAssignment->update(['employee_id' => $tempEmployeeId]);

                $swapRequest->update([
                    'status' => 'approved',
                    'approved_by' => auth()->id(),
                    'approved_at' => now(),
                    'supervisor_notes' => $validated['supervisor_notes'] ?? null,
                ]);

                DB::commit();

                $message = 'تم الموافقة على طلب التبديل وتم تنفيذه';
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'حدث خطأ أثناء تنفيذ التبديل',
                ], 500);
            }
        } else {
            $swapRequest->update([
                'status' => 'rejected_by_supervisor',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
                'supervisor_notes' => $validated['supervisor_notes'] ?? null,
            ]);

            $message = 'تم رفض طلب التبديل من قبل المشرف';
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $swapRequest->fresh([
                'fromAssignment.employee',
                'toAssignment.employee',
            ]),
        ]);
    }

    /**
     * إلغاء طلب التبديل
     */
    public function cancel(ShiftSwapRequest $swapRequest): JsonResponse
    {
        // التحقق من أن المستخدم هو صاحب الطلب
        if ($swapRequest->requested_by !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح لك بإلغاء هذا الطلب',
            ], 403);
        }

        if (!in_array($swapRequest->status, ['pending_target', 'pending_supervisor'])) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن إلغاء هذا الطلب',
            ], 422);
        }

        $swapRequest->update([
            'status' => 'cancelled',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم إلغاء طلب التبديل',
        ]);
    }
}
