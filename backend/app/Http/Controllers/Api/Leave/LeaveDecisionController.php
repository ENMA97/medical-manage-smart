<?php

namespace App\Http\Controllers\Api\Leave;

use App\Http\Controllers\Controller;
use App\Http\Requests\Leave\CreateDecisionRequest;
use App\Http\Requests\Leave\ProcessDecisionRequest;
use App\Http\Resources\Leave\LeaveDecisionResource;
use App\Http\Resources\Leave\LeaveDecisionCollection;
use App\Models\Leave\LeaveDecision;
use App\Models\Leave\LeaveRequest;
use App\Services\Leave\LeaveDecisionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Exception;

class LeaveDecisionController extends Controller
{
    protected LeaveDecisionService $service;

    public function __construct(LeaveDecisionService $service)
    {
        $this->service = $service;
    }

    /**
     * التحقق من صلاحية اتخاذ القرار
     */
    protected function authorizeDecision(string $permission): ?JsonResponse
    {
        if (Gate::denies($permission)) {
            Log::warning('Unauthorized leave decision attempt', [
                'user_id' => auth()->id(),
                'permission' => $permission,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'ليس لديك صلاحية لهذا الإجراء',
            ], 403);
        }

        return null;
    }

    /**
     * عرض قائمة قرارات الإجازة
     */
    public function index(Request $request): LeaveDecisionCollection
    {
        $query = LeaveDecision::with(['leaveRequest.employee', 'leaveRequest.leaveType', 'approvedBy', 'gmApprovedBy']);

        // فلترة حسب الحالة
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // فلترة حسب طلب الإجازة
        if ($request->has('leave_request_id')) {
            $query->where('leave_request_id', $request->leave_request_id);
        }

        $decisions = $query->orderBy('created_at', 'desc')->paginate($request->per_page ?? 15);

        return new LeaveDecisionCollection($decisions);
    }

    /**
     * إنشاء قرار إجازة جديد
     */
    public function store(CreateDecisionRequest $request): JsonResponse
    {
        try {
            $leaveRequest = LeaveRequest::findOrFail($request->leave_request_id);

            $decision = $this->service->createDecision(
                $leaveRequest,
                $request->user()->id
            );

            return response()->json([
                'success' => true,
                'message' => 'تم إنشاء قرار الإجازة بنجاح',
                'data' => new LeaveDecisionResource($decision),
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * عرض تفاصيل قرار الإجازة
     */
    public function show(LeaveDecision $leaveDecision): LeaveDecisionResource
    {
        $leaveDecision->load(['leaveRequest.employee', 'leaveRequest.leaveType', 'approvedBy', 'gmApprovedBy']);

        return new LeaveDecisionResource($leaveDecision);
    }

    /**
     * معالجة موافقة المدير الإداري (للموظفين الإداريين)
     */
    public function processAdminManagerDecision(
        ProcessDecisionRequest $request,
        LeaveDecision $leaveDecision
    ): JsonResponse {
        // التحقق من صلاحية المدير الإداري
        if ($unauthorized = $this->authorizeDecision('leave.approve_manager')) {
            return $unauthorized;
        }

        try {
            $leaveDecision = $this->service->processAdminManagerDecision(
                $leaveDecision,
                $request->user()->id,
                $request->action,
                $request->comment,
                $request->ip()
            );

            $messages = [
                'approve' => 'تم اعتماد قرار الإجازة',
                'forward_to_gm' => 'تم تحويل القرار للمدير العام',
                'reject' => 'تم رفض قرار الإجازة',
            ];

            return response()->json([
                'success' => true,
                'message' => $messages[$request->action] ?? 'تمت المعالجة',
                'data' => new LeaveDecisionResource($leaveDecision),
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * معالجة موافقة المدير الطبي (للأطباء)
     */
    public function processMedicalDirectorDecision(
        ProcessDecisionRequest $request,
        LeaveDecision $leaveDecision
    ): JsonResponse {
        // التحقق من صلاحية المدير الطبي (نفس صلاحية المدير لكن للكادر الطبي)
        if ($unauthorized = $this->authorizeDecision('leave.approve_manager')) {
            return $unauthorized;
        }

        try {
            $leaveDecision = $this->service->processMedicalDirectorDecision(
                $leaveDecision,
                $request->user()->id,
                $request->action,
                $request->comment,
                $request->ip()
            );

            $messages = [
                'approve' => 'تم اعتماد قرار الإجازة',
                'forward_to_gm' => 'تم تحويل القرار للمدير العام',
                'reject' => 'تم رفض قرار الإجازة',
            ];

            return response()->json([
                'success' => true,
                'message' => $messages[$request->action] ?? 'تمت المعالجة',
                'data' => new LeaveDecisionResource($leaveDecision),
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * معالجة موافقة المدير العام
     */
    public function processGeneralManagerDecision(
        ProcessDecisionRequest $request,
        LeaveDecision $leaveDecision
    ): JsonResponse {
        // التحقق من صلاحية المدير العام
        if ($unauthorized = $this->authorizeDecision('leave.approve_gm')) {
            return $unauthorized;
        }

        try {
            $leaveDecision = $this->service->processGeneralManagerDecision(
                $leaveDecision,
                $request->user()->id,
                $request->action === 'approve',
                $request->comment,
                $request->ip()
            );

            return response()->json([
                'success' => true,
                'message' => $request->action === 'approve' ? 'تم اعتماد قرار الإجازة' : 'تم رفض القرار',
                'data' => new LeaveDecisionResource($leaveDecision),
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * القرارات المعلقة للمعتمد الحالي
     */
    public function pendingForMe(Request $request): LeaveDecisionCollection
    {
        $decisions = $this->service->getPendingDecisionsForApprover($request->user()->id);

        return new LeaveDecisionCollection($decisions);
    }

    /**
     * القرارات المعلقة للمدير العام
     */
    public function pendingForGM(Request $request): LeaveDecisionCollection|JsonResponse
    {
        // التحقق من صلاحية المدير العام
        if ($unauthorized = $this->authorizeDecision('leave.approve_gm')) {
            return $unauthorized;
        }

        $decisions = LeaveDecision::where('status', 'pending_general_manager')
            ->with(['leaveRequest.employee', 'leaveRequest.leaveType'])
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 15);

        return new LeaveDecisionCollection($decisions);
    }
}
