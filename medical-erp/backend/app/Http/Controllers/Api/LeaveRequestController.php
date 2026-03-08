<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Leave\StoreLeaveRequest;
use App\Models\LeaveApproval;
use App\Models\LeaveBalance;
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
    public function store(StoreLeaveRequest $request): JsonResponse
    {
        try {
            $data = $request->only([
                'employee_id', 'leave_type_id', 'leave_balance_id',
                'start_date', 'end_date', 'total_days',
                'reason', 'reason_ar',
                'substitute_employee_id', 'contact_during_leave', 'address_during_leave',
            ]);

            // التحقق من رصيد الإجازات
            $balance = LeaveBalance::where('employee_id', $data['employee_id'])
                ->where('leave_type_id', $data['leave_type_id'])
                ->where('year', date('Y'))
                ->first();

            if ($balance && $balance->remaining < $data['total_days']) {
                return response()->json([
                    'success' => false,
                    'message' => 'رصيد الإجازات غير كافٍ. المتبقي: ' . $balance->remaining . ' يوم',
                ], 422);
            }

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

            if ($balance) {
                $data['leave_balance_id'] = $balance->id;
            }

            DB::transaction(function () use ($data, $balance) {
                // تحديث الرصيد المعلق
                if ($balance) {
                    $balance->increment('pending', $data['total_days']);
                    $balance->decrement('remaining', $data['total_days']);
                }
            });

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

                    // نقل من معلّق إلى مستخدم في رصيد الإجازات
                    $balance = $leaveRequest->leaveBalance;
                    if ($balance) {
                        $balance->decrement('pending', $leaveRequest->total_days);
                        $balance->increment('used', $leaveRequest->total_days);
                    }
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

                // إعادة الرصيد المعلّق
                $balance = $leaveRequest->leaveBalance;
                if ($balance) {
                    $balance->decrement('pending', $leaveRequest->total_days);
                    $balance->increment('remaining', $leaveRequest->total_days);
                }
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
            DB::transaction(function () use ($leaveRequest, $request) {
                $wasApproved = $leaveRequest->status === 'approved';

                $leaveRequest->update([
                    'status' => 'cancelled',
                    'cancelled_by' => auth()->id(),
                    'cancelled_at' => now(),
                    'cancellation_reason' => $request->input('cancellation_reason'),
                ]);

                // إعادة الرصيد عند الإلغاء
                $balance = $leaveRequest->leaveBalance;
                if ($balance) {
                    if ($wasApproved) {
                        $balance->decrement('used', $leaveRequest->total_days);
                    } else {
                        $balance->decrement('pending', $leaveRequest->total_days);
                    }
                    $balance->increment('remaining', $leaveRequest->total_days);
                }
            });

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
