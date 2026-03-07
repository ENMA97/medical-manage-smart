<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LeaveApproval;
use App\Models\LeaveRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LeaveRequestController extends Controller
{
    /**
     * GET /api/leave-requests
     * قائمة طلبات الإجازة مع التصفية
     */
    public function index(Request $request): JsonResponse
    {
        $leaveRequests = LeaveRequest::with(['employee', 'leaveType'])
            ->when($request->filled('employee_id'), fn($q) => $q->where('employee_id', $request->input('employee_id')))
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->input('status')))
            ->when($request->filled('leave_type_id'), fn($q) => $q->where('leave_type_id', $request->input('leave_type_id')))
            ->when($request->filled('start_date'), fn($q) => $q->where('start_date', '>=', $request->input('start_date')))
            ->when($request->filled('end_date'), fn($q) => $q->where('end_date', '<=', $request->input('end_date')))
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('request_number', 'like', "%{$search}%")
                      ->orWhereHas('employee', function ($eq) use ($search) {
                          $eq->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('employee_number', 'like', "%{$search}%");
                      });
                });
            })
            ->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 15));

        return response()->json([
            'success' => true,
            'message' => 'تم جلب قائمة طلبات الإجازة بنجاح',
            'data' => $leaveRequests,
        ]);
    }

    /**
     * POST /api/leave-requests
     * إنشاء طلب إجازة جديد
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'leave_type_id' => 'required|exists:leave_types,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'total_days' => 'required|integer|min:1',
            'reason' => 'nullable|string',
            'reason_ar' => 'nullable|string',
            'substitute_employee_id' => 'nullable|exists:employees,id',
            'contact_during_leave' => 'nullable|string',
            'address_during_leave' => 'nullable|string',
            'leave_balance_id' => 'nullable|exists:leave_balances,id',
        ]);

        try {
            $data = $request->only([
                'employee_id', 'leave_type_id', 'leave_balance_id',
                'start_date', 'end_date', 'total_days',
                'reason', 'reason_ar',
                'substitute_employee_id', 'contact_during_leave', 'address_during_leave',
            ]);

            // توليد رقم الطلب
            $data['request_number'] = 'LR-' . date('Y') . '-' . str_pad(
                LeaveRequest::withTrashed()->count() + 1,
                5,
                '0',
                STR_PAD_LEFT
            );

            $data['status'] = 'pending';
            $data['current_approval_step'] = 1;
            $data['total_approval_steps'] = 2;

            $leaveRequest = LeaveRequest::create($data);
            $leaveRequest->load(['employee', 'leaveType']);

            return response()->json([
                'success' => true,
                'message' => 'تم إنشاء طلب الإجازة بنجاح',
                'data' => $leaveRequest,
            ], 201);
        } catch (\Illuminate\Database\QueryException $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إنشاء طلب الإجازة',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * GET /api/leave-requests/{id}
     * عرض تفاصيل طلب إجازة مع الموافقات
     */
    public function show(string $id): JsonResponse
    {
        $leaveRequest = LeaveRequest::with(['employee', 'leaveType', 'approvals.approver', 'substituteEmployee'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'message' => 'تم جلب بيانات طلب الإجازة بنجاح',
            'data' => $leaveRequest,
        ]);
    }

    /**
     * POST /api/leave-requests/{id}/approve
     * الموافقة على طلب إجازة
     */
    public function approve(Request $request, string $id): JsonResponse
    {
        $leaveRequest = LeaveRequest::findOrFail($id);

        if ($leaveRequest->status !== 'pending' && $leaveRequest->status !== 'partially_approved') {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن الموافقة على هذا الطلب في حالته الحالية',
            ], 422);
        }

        $request->validate([
            'comment' => 'nullable|string',
            'comment_ar' => 'nullable|string',
        ]);

        try {
            DB::transaction(function () use ($leaveRequest, $request) {
                // إنشاء سجل الموافقة
                LeaveApproval::create([
                    'leave_request_id' => $leaveRequest->id,
                    'step_order' => $leaveRequest->current_approval_step,
                    'approval_role' => 'manager',
                    'approver_id' => auth()->id(),
                    'status' => 'approved',
                    'comment' => $request->input('comment'),
                    'comment_ar' => $request->input('comment_ar'),
                    'actioned_at' => now(),
                ]);

                // التحقق من اكتمال جميع خطوات الموافقة
                if ($leaveRequest->current_approval_step >= $leaveRequest->total_approval_steps) {
                    $leaveRequest->update(['status' => 'approved']);
                } else {
                    $leaveRequest->update([
                        'status' => 'partially_approved',
                        'current_approval_step' => $leaveRequest->current_approval_step + 1,
                    ]);
                }
            });

            $leaveRequest->load(['employee', 'leaveType', 'approvals']);

            return response()->json([
                'success' => true,
                'message' => 'تم الموافقة على طلب الإجازة بنجاح',
                'data' => $leaveRequest,
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء الموافقة على الطلب',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * POST /api/leave-requests/{id}/reject
     * رفض طلب إجازة
     */
    public function reject(Request $request, string $id): JsonResponse
    {
        $leaveRequest = LeaveRequest::findOrFail($id);

        if ($leaveRequest->status !== 'pending' && $leaveRequest->status !== 'partially_approved') {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن رفض هذا الطلب في حالته الحالية',
            ], 422);
        }

        $request->validate([
            'comment' => 'required|string',
            'comment_ar' => 'nullable|string',
        ]);

        try {
            DB::transaction(function () use ($leaveRequest, $request) {
                LeaveApproval::create([
                    'leave_request_id' => $leaveRequest->id,
                    'step_order' => $leaveRequest->current_approval_step,
                    'approval_role' => 'manager',
                    'approver_id' => auth()->id(),
                    'status' => 'rejected',
                    'comment' => $request->input('comment'),
                    'comment_ar' => $request->input('comment_ar'),
                    'actioned_at' => now(),
                ]);

                $leaveRequest->update(['status' => 'rejected']);
            });

            $leaveRequest->load(['employee', 'leaveType', 'approvals']);

            return response()->json([
                'success' => true,
                'message' => 'تم رفض طلب الإجازة',
                'data' => $leaveRequest,
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء رفض الطلب',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * POST /api/leave-requests/{id}/cancel
     * إلغاء طلب الإجازة
     */
    public function cancel(Request $request, string $id): JsonResponse
    {
        $leaveRequest = LeaveRequest::findOrFail($id);

        if (!in_array($leaveRequest->status, ['pending', 'partially_approved', 'approved'])) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن إلغاء هذا الطلب في حالته الحالية',
            ], 422);
        }

        $request->validate([
            'cancellation_reason' => 'required|string',
        ]);

        try {
            $leaveRequest->update([
                'status' => 'cancelled',
                'cancelled_by' => auth()->id(),
                'cancelled_at' => now(),
                'cancellation_reason' => $request->input('cancellation_reason'),
            ]);

            $leaveRequest->load(['employee', 'leaveType']);

            return response()->json([
                'success' => true,
                'message' => 'تم إلغاء طلب الإجازة بنجاح',
                'data' => $leaveRequest,
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إلغاء الطلب',
                'error' => $e->getMessage(),
            ], 422);
        }
    }
}
