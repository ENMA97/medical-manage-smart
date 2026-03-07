<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Resignation\StoreResignationRequest;
use App\Models\Resignation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ResignationController extends Controller
{
    /**
     * GET /api/resignations
     * قائمة الاستقالات
     */
    public function index(Request $request): JsonResponse
    {
        $resignations = Resignation::with(['employee', 'contract'])
            ->when($request->filled('employee_id'), fn($q) => $q->where('employee_id', $request->input('employee_id')))
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->input('status')))
            ->when($request->filled('type'), fn($q) => $q->where('type', $request->input('type')))
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->input('search');
                $query->whereHas('employee', function ($eq) use ($search) {
                    $eq->where('first_name', 'like', "%{$search}%")
                      ->orWhere('last_name', 'like', "%{$search}%")
                      ->orWhere('first_name_ar', 'like', "%{$search}%")
                      ->orWhere('last_name_ar', 'like', "%{$search}%")
                      ->orWhere('employee_number', 'like', "%{$search}%");
                });
            })
            ->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 15));

        return response()->json([
            'success' => true,
            'message' => 'تم جلب قائمة الاستقالات بنجاح',
            'data' => $resignations,
        ]);
    }

    /**
     * POST /api/resignations
     * تقديم استقالة
     */
    public function store(StoreResignationRequest $request): JsonResponse
    {
        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'contract_id' => 'nullable|exists:contracts,id',
            'type' => 'nullable|string',
            'request_date' => 'required|date',
            'last_working_day' => 'required|date|after_or_equal:request_date',
            'effective_date' => 'nullable|date|after_or_equal:last_working_day',
            'notice_period_days' => 'nullable|integer|min:0',
            'reason' => 'required|string',
            'reason_ar' => 'nullable|string',
            'direct_manager_id' => 'nullable|exists:employees,id',
            'notes' => 'nullable|string',
        ]);

        try {
            $data = $request->only([
                'employee_id', 'contract_id', 'type',
                'request_date', 'last_working_day', 'effective_date',
                'notice_period_days', 'reason', 'reason_ar',
                'direct_manager_id', 'notes',
            ]);

            $data['status'] = 'pending';
            $data['created_by'] = auth()->id();

            $resignation = Resignation::create($data);
            $resignation->load(['employee', 'contract']);

            return response()->json([
                'success' => true,
                'message' => 'تم تقديم الاستقالة بنجاح',
                'data' => $resignation,
            ], 201);
        } catch (\Illuminate\Database\QueryException $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تقديم الاستقالة',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * GET /api/resignations/{id}
     * عرض تفاصيل استقالة
     */
    public function show(string $id): JsonResponse
    {
        $resignation = Resignation::with(['employee', 'contract'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'message' => 'تم جلب بيانات الاستقالة بنجاح',
            'data' => $resignation,
        ]);
    }

    /**
     * POST /api/resignations/{id}/approve
     * الموافقة على استقالة
     */
    public function approve(Request $request, string $id): JsonResponse
    {
        $resignation = Resignation::findOrFail($id);

        if ($resignation->status !== 'pending' && $resignation->status !== 'under_review') {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن الموافقة على هذه الاستقالة في حالتها الحالية',
            ], 422);
        }

        $request->validate([
            'remarks' => 'nullable|string',
            'effective_date' => 'nullable|date',
        ]);

        try {
            $updateData = [
                'status' => 'approved',
                'final_approver_id' => auth()->id(),
                'final_decision' => 'approved',
                'final_decision_at' => now(),
                'final_remarks' => $request->input('remarks'),
            ];

            if ($request->filled('effective_date')) {
                $updateData['effective_date'] = $request->input('effective_date');
            }

            $resignation->update($updateData);
            $resignation->load(['employee', 'contract']);

            return response()->json([
                'success' => true,
                'message' => 'تم الموافقة على الاستقالة بنجاح',
                'data' => $resignation,
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء الموافقة على الاستقالة',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * POST /api/resignations/{id}/reject
     * رفض استقالة
     */
    public function reject(Request $request, string $id): JsonResponse
    {
        $resignation = Resignation::findOrFail($id);

        if ($resignation->status !== 'pending' && $resignation->status !== 'under_review') {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن رفض هذه الاستقالة في حالتها الحالية',
            ], 422);
        }

        $request->validate([
            'remarks' => 'required|string',
        ]);

        try {
            $resignation->update([
                'status' => 'rejected',
                'final_approver_id' => auth()->id(),
                'final_decision' => 'rejected',
                'final_decision_at' => now(),
                'final_remarks' => $request->input('remarks'),
            ]);

            $resignation->load(['employee', 'contract']);

            return response()->json([
                'success' => true,
                'message' => 'تم رفض الاستقالة',
                'data' => $resignation,
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء رفض الاستقالة',
                'error' => $e->getMessage(),
            ], 422);
        }
    }
}
