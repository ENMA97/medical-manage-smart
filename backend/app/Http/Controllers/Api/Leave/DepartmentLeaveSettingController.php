<?php

namespace App\Http\Controllers\Api\Leave;

use App\Http\Controllers\Controller;
use App\Models\Leave\DepartmentLeaveSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DepartmentLeaveSettingController extends Controller
{
    /**
     * عرض جميع إعدادات الأقسام
     */
    public function index(Request $request): JsonResponse
    {
        $query = DepartmentLeaveSetting::query();

        // فلترة حسب الحالة
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $settings = $query->orderBy('created_at', 'desc')->paginate($request->per_page ?? 15);

        return response()->json([
            'success' => true,
            'data' => $settings,
        ]);
    }

    /**
     * عرض إعدادات قسم معين
     */
    public function byDepartment(string $departmentId): JsonResponse
    {
        $settings = DepartmentLeaveSetting::where('department_id', $departmentId)
            ->where('is_active', true)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $settings,
        ]);
    }

    /**
     * إنشاء إعداد جديد
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'department_id' => 'required|uuid',
            'leave_type_id' => 'nullable|uuid|exists:leave_types,id',
            'max_concurrent_leaves' => 'required|integer|min:1|max:100',
            'max_concurrent_percentage' => 'nullable|numeric|min:1|max:100',
            'blackout_periods' => 'nullable|array',
            'blackout_periods.*.start_date' => 'required_with:blackout_periods|date',
            'blackout_periods.*.end_date' => 'required_with:blackout_periods|date|after:blackout_periods.*.start_date',
            'blackout_periods.*.reason' => 'nullable|string|max:200',
            'min_staff_required' => 'nullable|integer|min:1',
            'requires_coverage' => 'boolean',
            'auto_approve_short_leaves' => 'boolean',
            'auto_approve_max_days' => 'nullable|integer|min:1|max:5',
            'is_active' => 'boolean',
            'notes' => 'nullable|string|max:1000',
        ]);

        $setting = DepartmentLeaveSetting::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'تم إنشاء الإعداد بنجاح',
            'data' => $setting,
        ], 201);
    }

    /**
     * تحديث إعداد
     */
    public function update(Request $request, DepartmentLeaveSetting $departmentLeaveSetting): JsonResponse
    {
        $validated = $request->validate([
            'department_id' => 'sometimes|required|uuid',
            'leave_type_id' => 'nullable|uuid|exists:leave_types,id',
            'max_concurrent_leaves' => 'sometimes|required|integer|min:1|max:100',
            'max_concurrent_percentage' => 'nullable|numeric|min:1|max:100',
            'blackout_periods' => 'nullable|array',
            'blackout_periods.*.start_date' => 'required_with:blackout_periods|date',
            'blackout_periods.*.end_date' => 'required_with:blackout_periods|date|after:blackout_periods.*.start_date',
            'blackout_periods.*.reason' => 'nullable|string|max:200',
            'min_staff_required' => 'nullable|integer|min:1',
            'requires_coverage' => 'boolean',
            'auto_approve_short_leaves' => 'boolean',
            'auto_approve_max_days' => 'nullable|integer|min:1|max:5',
            'is_active' => 'boolean',
            'notes' => 'nullable|string|max:1000',
        ]);

        $departmentLeaveSetting->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث الإعداد بنجاح',
            'data' => $departmentLeaveSetting,
        ]);
    }

    /**
     * حذف إعداد
     */
    public function destroy(DepartmentLeaveSetting $departmentLeaveSetting): JsonResponse
    {
        $departmentLeaveSetting->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف الإعداد بنجاح',
        ]);
    }
}
