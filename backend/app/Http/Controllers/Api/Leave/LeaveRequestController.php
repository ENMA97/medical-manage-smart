<?php

namespace App\Http\Controllers\Api\Leave;

use App\Http\Controllers\Controller;
use App\Http\Requests\Leave\CreateLeaveRequestRequest;
use App\Http\Requests\Leave\ProcessApprovalRequest;
use App\Http\Resources\Leave\LeaveRequestResource;
use App\Http\Resources\Leave\LeaveRequestCollection;
use App\Models\Leave\LeaveRequest;
use App\Services\Leave\LeaveRequestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Exception;

class LeaveRequestController extends Controller
{
    protected LeaveRequestService $service;

    public function __construct(LeaveRequestService $service)
    {
        $this->service = $service;
    }

    /**
     * التحقق من صلاحية الموافقة
     */
    protected function authorizeApproval(string $permission, LeaveRequest $leaveRequest): ?JsonResponse
    {
        if (Gate::denies($permission)) {
            Log::warning('Unauthorized leave approval attempt', [
                'user_id' => auth()->id(),
                'permission' => $permission,
                'leave_request_id' => $leaveRequest->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'ليس لديك صلاحية لهذا الإجراء',
            ], 403);
        }

        return null;
    }

    /**
     * عرض قائمة طلبات الإجازة
     */
    public function index(Request $request): LeaveRequestCollection
    {
        $query = LeaveRequest::with(['employee', 'leaveType', 'delegate', 'approvals']);

        // فلترة حسب الموظف
        if ($request->has('employee_id')) {
            $query->forEmployee($request->employee_id);
        }

        // فلترة حسب الحالة
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // فلترة حسب نوع الإجازة
        if ($request->has('leave_type_id')) {
            $query->where('leave_type_id', $request->leave_type_id);
        }

        // فلترة حسب التاريخ
        if ($request->has('start_date') && $request->has('end_date')) {
            $query->inDateRange($request->start_date, $request->end_date);
        }

        $requests = $query->orderBy('created_at', 'desc')->paginate($request->per_page ?? 15);

        return new LeaveRequestCollection($requests);
    }

    /**
     * إنشاء طلب إجازة جديد
     */
    public function store(CreateLeaveRequestRequest $request): JsonResponse
    {
        try {
            $leaveRequest = $this->service->createRequest(
                $request->validated(),
                $request->user()->id
            );

            return response()->json([
                'success' => true,
                'message' => 'تم إنشاء طلب الإجازة بنجاح',
                'data' => new LeaveRequestResource($leaveRequest),
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * عرض تفاصيل طلب الإجازة
     */
    public function show(LeaveRequest $leaveRequest): LeaveRequestResource
    {
        $leaveRequest->load(['employee', 'leaveType', 'delegate', 'approvals.approver', 'decision']);

        return new LeaveRequestResource($leaveRequest);
    }

    /**
     * تقديم الطلب للموافقة
     */
    public function submit(Request $request, LeaveRequest $leaveRequest): JsonResponse
    {
        // التحقق من أن المستخدم هو صاحب الطلب
        $userId = $request->user()->id;
        if ($leaveRequest->employee_id !== $userId) {
            Log::warning('Unauthorized leave submit attempt', [
                'user_id' => $userId,
                'leave_request_id' => $leaveRequest->id,
                'employee_id' => $leaveRequest->employee_id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'لا يمكنك تقديم طلب ليس لك',
            ], 403);
        }

        try {
            $leaveRequest = $this->service->submitRequest($leaveRequest);

            return response()->json([
                'success' => true,
                'message' => 'تم تقديم الطلب بنجاح',
                'data' => new LeaveRequestResource($leaveRequest),
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * معالجة توصية المشرف
     */
    public function processSupervisorRecommendation(
        ProcessApprovalRequest $request,
        LeaveRequest $leaveRequest
    ): JsonResponse {
        // التحقق من أن المستخدم هو مشرف الموظف
        if ($unauthorized = $this->authorizeApproval('leave.approve_supervisor', $leaveRequest)) {
            return $unauthorized;
        }

        try {
            $leaveRequest = $this->service->processSupervisorRecommendation(
                $leaveRequest,
                $request->user()->id,
                $request->approved,
                $request->comment,
                $request->job_tasks,
                $request->ip()
            );

            return response()->json([
                'success' => true,
                'message' => $request->approved ? 'تمت التوصية بالموافقة' : 'تم رفض الطلب',
                'data' => new LeaveRequestResource($leaveRequest),
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * معالجة موافقة المدير الإداري
     */
    public function processAdminManagerApproval(
        ProcessApprovalRequest $request,
        LeaveRequest $leaveRequest
    ): JsonResponse {
        // التحقق من صلاحية المدير الإداري
        if ($unauthorized = $this->authorizeApproval('leave.approve_manager', $leaveRequest)) {
            return $unauthorized;
        }

        try {
            $leaveRequest = $this->service->processAdminManagerApproval(
                $leaveRequest,
                $request->user()->id,
                $request->approved,
                $request->comment,
                $request->ip()
            );

            return response()->json([
                'success' => true,
                'message' => $request->approved ? 'تمت الموافقة' : 'تم رفض الطلب',
                'data' => new LeaveRequestResource($leaveRequest),
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * معالجة تعميد الموارد البشرية
     */
    public function processHrEndorsement(
        ProcessApprovalRequest $request,
        LeaveRequest $leaveRequest
    ): JsonResponse {
        // التحقق من صلاحية الموارد البشرية
        if ($unauthorized = $this->authorizeApproval('leave.approve_hr', $leaveRequest)) {
            return $unauthorized;
        }

        try {
            $leaveRequest = $this->service->processHrEndorsement(
                $leaveRequest,
                $request->user()->id,
                $request->approved,
                $request->comment,
                $request->ip()
            );

            return response()->json([
                'success' => true,
                'message' => $request->approved ? 'تم التعميد' : 'تم رفض الطلب',
                'data' => new LeaveRequestResource($leaveRequest),
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * معالجة تأكيد القائم بالعمل
     * ملاحظة: التحقق من هوية القائم بالعمل يتم في Service layer
     */
    public function processDelegateConfirmation(
        ProcessApprovalRequest $request,
        LeaveRequest $leaveRequest
    ): JsonResponse {
        // التحقق من أن المستخدم هو القائم بالعمل المحدد
        $userId = $request->user()->id;
        if ($leaveRequest->delegate_id !== $userId) {
            Log::warning('Unauthorized delegate confirmation attempt', [
                'user_id' => $userId,
                'expected_delegate_id' => $leaveRequest->delegate_id,
                'leave_request_id' => $leaveRequest->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'لست القائم بالعمل المحدد لهذا الطلب',
            ], 403);
        }

        try {
            $leaveRequest = $this->service->processDelegateConfirmation(
                $leaveRequest,
                $userId,
                $request->approved,
                $request->comment,
                $request->ip()
            );

            return response()->json([
                'success' => true,
                'message' => $request->approved ? 'تم تأكيد التغطية' : 'تم رفض الطلب',
                'data' => new LeaveRequestResource($leaveRequest),
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * إلغاء طلب الإجازة
     * يمكن للموظف إلغاء طلبه أو للموارد البشرية إلغاء أي طلب
     */
    public function cancel(Request $request, LeaveRequest $leaveRequest): JsonResponse
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $userId = $request->user()->id;

        // التحقق من أن المستخدم هو صاحب الطلب أو لديه صلاحية الإلغاء
        if ($leaveRequest->employee_id !== $userId && Gate::denies('leave.approve_hr')) {
            Log::warning('Unauthorized leave cancellation attempt', [
                'user_id' => $userId,
                'leave_request_id' => $leaveRequest->id,
                'employee_id' => $leaveRequest->employee_id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'ليس لديك صلاحية لإلغاء هذا الطلب',
            ], 403);
        }

        try {
            $leaveRequest = $this->service->cancelRequest(
                $leaveRequest,
                $request->reason,
                $userId
            );

            return response()->json([
                'success' => true,
                'message' => 'تم إلغاء الطلب بنجاح',
                'data' => new LeaveRequestResource($leaveRequest),
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * الطلبات المعلقة للمعتمد الحالي
     */
    public function pendingForMe(Request $request): LeaveRequestCollection
    {
        $requests = $this->service->getPendingRequestsForApprover($request->user()->id);

        return new LeaveRequestCollection($requests);
    }
}
