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
use Exception;

class LeaveRequestController extends Controller
{
    protected LeaveRequestService $service;

    public function __construct(LeaveRequestService $service)
    {
        $this->service = $service;
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
    public function submit(LeaveRequest $leaveRequest): JsonResponse
    {
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
     */
    public function processDelegateConfirmation(
        ProcessApprovalRequest $request,
        LeaveRequest $leaveRequest
    ): JsonResponse {
        try {
            $leaveRequest = $this->service->processDelegateConfirmation(
                $leaveRequest,
                $request->user()->id,
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
     */
    public function cancel(Request $request, LeaveRequest $leaveRequest): JsonResponse
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        try {
            $leaveRequest = $this->service->cancelRequest(
                $leaveRequest,
                $request->reason,
                $request->user()->id
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
