<?php

namespace App\Http\Controllers\Api\Leave;

use App\Http\Controllers\Controller;
use App\Models\Leave\LeavePolicy;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

class LeavePolicyController extends Controller
{
    /**
     * عرض جميع سياسات الإجازات
     */
    public function index(Request $request): JsonResponse
    {
        $query = LeavePolicy::with(['leaveType']);

        // فلترة حسب نوع العقد
        if ($request->has('contract_type')) {
            $query->where('contract_type', $request->contract_type);
        }

        // فلترة حسب نوع الإجازة
        if ($request->has('leave_type_id')) {
            $query->where('leave_type_id', $request->leave_type_id);
        }

        // فلترة حسب الحالة
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $policies = $query->orderBy('contract_type')->paginate($request->per_page ?? 15);

        return response()->json([
            'success' => true,
            'data' => $policies,
        ]);
    }

    /**
     * عرض السياسات حسب نوع العقد
     */
    public function byContractType(string $contractType): JsonResponse
    {
        $policies = LeavePolicy::with(['leaveType'])
            ->where('contract_type', $contractType)
            ->where('is_active', true)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $policies,
        ]);
    }

    /**
     * عرض تفاصيل سياسة معينة
     */
    public function show(LeavePolicy $leavePolicy): JsonResponse
    {
        $leavePolicy->load(['leaveType']);

        return response()->json([
            'success' => true,
            'data' => $leavePolicy,
        ]);
    }

    /**
     * إنشاء سياسة جديدة
     */
    public function store(Request $request): JsonResponse
    {
        // التحقق من صلاحية إدارة السياسات
        if (Gate::denies('leave.manage')) {
            Log::warning('Unauthorized leave policy creation attempt', [
                'user_id' => $request->user()->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'ليس لديك صلاحية لإنشاء سياسات الإجازات',
            ], 403);
        }

        $validated = $request->validate([
            'leave_type_id' => 'required|uuid|exists:leave_types,id',
            'contract_type' => 'required|in:full_time,part_time,tamheer,percentage,locum',
            'entitled_days' => 'required|integer|min:0|max:365',
            'accrual_rate' => 'nullable|numeric|min:0|max:31',
            'accrual_frequency' => 'nullable|in:daily,weekly,monthly,yearly',
            'waiting_period_days' => 'nullable|integer|min:0|max:365',
            'min_service_months' => 'nullable|integer|min:0|max:120',
            'max_consecutive_days' => 'nullable|integer|min:1|max:365',
            'requires_approval' => 'boolean',
            'can_be_encashed' => 'boolean',
            'encashment_percentage' => 'nullable|numeric|min:0|max:100',
            'is_active' => 'boolean',
            'effective_from' => 'required|date',
            'effective_to' => 'nullable|date|after:effective_from',
            'notes' => 'nullable|string|max:1000',
        ]);

        // التحقق من عدم وجود سياسة مكررة
        $exists = LeavePolicy::where('leave_type_id', $validated['leave_type_id'])
            ->where('contract_type', $validated['contract_type'])
            ->where('is_active', true)
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'توجد سياسة نشطة لهذا النوع من الإجازات ونوع العقد',
            ], 400);
        }

        $policy = LeavePolicy::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'تم إنشاء السياسة بنجاح',
            'data' => $policy->load('leaveType'),
        ], 201);
    }

    /**
     * تحديث سياسة
     */
    public function update(Request $request, LeavePolicy $leavePolicy): JsonResponse
    {
        // التحقق من صلاحية إدارة السياسات
        if (Gate::denies('leave.manage')) {
            Log::warning('Unauthorized leave policy update attempt', [
                'user_id' => $request->user()->id,
                'policy_id' => $leavePolicy->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'ليس لديك صلاحية لتعديل سياسات الإجازات',
            ], 403);
        }

        $validated = $request->validate([
            'leave_type_id' => 'sometimes|required|uuid|exists:leave_types,id',
            'contract_type' => 'sometimes|required|in:full_time,part_time,tamheer,percentage,locum',
            'entitled_days' => 'sometimes|required|integer|min:0|max:365',
            'accrual_rate' => 'nullable|numeric|min:0|max:31',
            'accrual_frequency' => 'nullable|in:daily,weekly,monthly,yearly',
            'waiting_period_days' => 'nullable|integer|min:0|max:365',
            'min_service_months' => 'nullable|integer|min:0|max:120',
            'max_consecutive_days' => 'nullable|integer|min:1|max:365',
            'requires_approval' => 'boolean',
            'can_be_encashed' => 'boolean',
            'encashment_percentage' => 'nullable|numeric|min:0|max:100',
            'is_active' => 'boolean',
            'effective_from' => 'sometimes|required|date',
            'effective_to' => 'nullable|date|after:effective_from',
            'notes' => 'nullable|string|max:1000',
        ]);

        $leavePolicy->update($validated);

        Log::info('Leave policy updated', [
            'policy_id' => $leavePolicy->id,
            'updated_by' => $request->user()->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث السياسة بنجاح',
            'data' => $leavePolicy->load('leaveType'),
        ]);
    }

    /**
     * حذف سياسة
     */
    public function destroy(Request $request, LeavePolicy $leavePolicy): JsonResponse
    {
        // التحقق من صلاحية إدارة السياسات
        if (Gate::denies('leave.manage')) {
            Log::warning('Unauthorized leave policy deletion attempt', [
                'user_id' => $request->user()->id,
                'policy_id' => $leavePolicy->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'ليس لديك صلاحية لحذف سياسات الإجازات',
            ], 403);
        }

        Log::info('Leave policy deleted', [
            'policy_id' => $leavePolicy->id,
            'deleted_by' => $request->user()->id,
        ]);

        $leavePolicy->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف السياسة بنجاح',
        ]);
    }
}
