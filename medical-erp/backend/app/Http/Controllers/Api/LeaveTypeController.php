<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LeaveType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LeaveTypeController extends Controller
{
    /**
     * GET /api/leave-types
     * قائمة أنواع الإجازات
     */
    public function index(Request $request): JsonResponse
    {
        $leaveTypes = LeaveType::query()
            ->when($request->filled('is_active'), fn($q) => $q->where('is_active', $request->boolean('is_active')))
            ->when($request->filled('category'), fn($q) => $q->where('category', $request->input('category')))
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('name_ar', 'like', "%{$search}%")
                      ->orWhere('code', 'like', "%{$search}%");
                });
            })
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate($request->input('per_page', 15));

        return response()->json([
            'success' => true,
            'message' => 'تم جلب قائمة أنواع الإجازات بنجاح',
            'data' => $leaveTypes,
        ]);
    }

    /**
     * POST /api/leave-types
     * إنشاء نوع إجازة جديد
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'code' => 'required|string|unique:leave_types,code',
            'name' => 'required|string|max:255',
            'name_ar' => 'required|string|max:255',
            'category' => 'nullable|string',
            'default_days_per_year' => 'nullable|integer|min:0',
            'max_days_per_request' => 'nullable|integer|min:1',
            'min_days_per_request' => 'nullable|integer|min:1',
            'is_paid' => 'nullable|boolean',
            'pay_percentage' => 'nullable|numeric|min:0|max:100',
            'requires_attachment' => 'nullable|boolean',
            'requires_substitute' => 'nullable|boolean',
            'advance_notice_days' => 'nullable|integer|min:0',
            'carries_forward' => 'nullable|boolean',
            'max_carry_forward_days' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
            'description' => 'nullable|string',
            'policy_notes' => 'nullable|string',
            'sort_order' => 'nullable|integer',
        ]);

        try {
            $leaveType = LeaveType::create($request->only([
                'code', 'name', 'name_ar', 'category',
                'default_days_per_year', 'max_days_per_request', 'min_days_per_request',
                'is_paid', 'pay_percentage',
                'requires_attachment', 'requires_substitute', 'advance_notice_days',
                'carries_forward', 'max_carry_forward_days',
                'is_active', 'description', 'policy_notes', 'sort_order',
            ]));

            return response()->json([
                'success' => true,
                'message' => 'تم إنشاء نوع الإجازة بنجاح',
                'data' => $leaveType,
            ], 201);
        } catch (\Illuminate\Database\QueryException $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إنشاء نوع الإجازة',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * GET /api/leave-types/{id}
     * عرض نوع إجازة
     */
    public function show(string $id): JsonResponse
    {
        $leaveType = LeaveType::findOrFail($id);

        return response()->json([
            'success' => true,
            'message' => 'تم جلب بيانات نوع الإجازة بنجاح',
            'data' => $leaveType,
        ]);
    }

    /**
     * PUT /api/leave-types/{id}
     * تحديث نوع إجازة
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $leaveType = LeaveType::findOrFail($id);

        $request->validate([
            'code' => 'sometimes|string|unique:leave_types,code,' . $id,
            'name' => 'sometimes|string|max:255',
            'name_ar' => 'sometimes|string|max:255',
            'category' => 'nullable|string',
            'default_days_per_year' => 'nullable|integer|min:0',
            'max_days_per_request' => 'nullable|integer|min:1',
            'min_days_per_request' => 'nullable|integer|min:1',
            'is_paid' => 'nullable|boolean',
            'pay_percentage' => 'nullable|numeric|min:0|max:100',
            'requires_attachment' => 'nullable|boolean',
            'requires_substitute' => 'nullable|boolean',
            'advance_notice_days' => 'nullable|integer|min:0',
            'carries_forward' => 'nullable|boolean',
            'max_carry_forward_days' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
            'description' => 'nullable|string',
            'policy_notes' => 'nullable|string',
            'sort_order' => 'nullable|integer',
        ]);

        try {
            $leaveType->update($request->only([
                'code', 'name', 'name_ar', 'category',
                'default_days_per_year', 'max_days_per_request', 'min_days_per_request',
                'is_paid', 'pay_percentage',
                'requires_attachment', 'requires_substitute', 'advance_notice_days',
                'carries_forward', 'max_carry_forward_days',
                'is_active', 'description', 'policy_notes', 'sort_order',
            ]));

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث نوع الإجازة بنجاح',
                'data' => $leaveType,
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تحديث نوع الإجازة',
                'error' => $e->getMessage(),
            ], 422);
        }
    }
}
