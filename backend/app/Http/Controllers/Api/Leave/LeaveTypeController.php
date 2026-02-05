<?php

namespace App\Http\Controllers\Api\Leave;

use App\Http\Controllers\Controller;
use App\Http\Resources\Leave\LeaveTypeResource;
use App\Http\Resources\Leave\LeaveTypeCollection;
use App\Models\Leave\LeaveType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LeaveTypeController extends Controller
{
    /**
     * عرض جميع أنواع الإجازات
     */
    public function index(Request $request): LeaveTypeCollection
    {
        $query = LeaveType::query();

        // فلترة حسب الفئة
        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        // فلترة حسب الحالة
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $types = $query->orderBy('sort_order')->orderBy('name_ar')->paginate($request->per_page ?? 15);

        return new LeaveTypeCollection($types);
    }

    /**
     * عرض أنواع الإجازات النشطة فقط
     */
    public function active(): LeaveTypeCollection
    {
        $types = LeaveType::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name_ar')
            ->get();

        return new LeaveTypeCollection($types);
    }

    /**
     * عرض تفاصيل نوع إجازة معين
     */
    public function show(LeaveType $leaveType): LeaveTypeResource
    {
        return new LeaveTypeResource($leaveType);
    }

    /**
     * إنشاء نوع إجازة جديد
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|max:20|unique:leave_types,code',
            'name_ar' => 'required|string|max:100',
            'name_en' => 'required|string|max:100',
            'category' => 'required|in:annual,sick,emergency,unpaid,maternity,paternity,hajj,marriage,bereavement,study,compensatory,other',
            'description_ar' => 'nullable|string|max:500',
            'description_en' => 'nullable|string|max:500',
            'default_days' => 'required|integer|min:0|max:365',
            'max_days_per_request' => 'nullable|integer|min:1|max:365',
            'min_days_per_request' => 'nullable|integer|min:1|max:365',
            'requires_attachment' => 'boolean',
            'requires_medical_certificate' => 'boolean',
            'is_paid' => 'boolean',
            'affects_annual_leave' => 'boolean',
            'can_be_carried_over' => 'boolean',
            'max_carry_over_days' => 'nullable|integer|min:0|max:365',
            'carry_over_expires_after_months' => 'nullable|integer|min:1|max:24',
            'advance_notice_days' => 'nullable|integer|min:0|max:90',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer|min:0',
            'eligibility_rules' => 'nullable|array',
            'color_code' => 'nullable|string|max:7',
        ]);

        $leaveType = LeaveType::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'تم إنشاء نوع الإجازة بنجاح',
            'data' => new LeaveTypeResource($leaveType),
        ], 201);
    }

    /**
     * تحديث نوع إجازة
     */
    public function update(Request $request, LeaveType $leaveType): JsonResponse
    {
        $validated = $request->validate([
            'code' => 'sometimes|required|string|max:20|unique:leave_types,code,' . $leaveType->id,
            'name_ar' => 'sometimes|required|string|max:100',
            'name_en' => 'sometimes|required|string|max:100',
            'category' => 'sometimes|required|in:annual,sick,emergency,unpaid,maternity,paternity,hajj,marriage,bereavement,study,compensatory,other',
            'description_ar' => 'nullable|string|max:500',
            'description_en' => 'nullable|string|max:500',
            'default_days' => 'sometimes|required|integer|min:0|max:365',
            'max_days_per_request' => 'nullable|integer|min:1|max:365',
            'min_days_per_request' => 'nullable|integer|min:1|max:365',
            'requires_attachment' => 'boolean',
            'requires_medical_certificate' => 'boolean',
            'is_paid' => 'boolean',
            'affects_annual_leave' => 'boolean',
            'can_be_carried_over' => 'boolean',
            'max_carry_over_days' => 'nullable|integer|min:0|max:365',
            'carry_over_expires_after_months' => 'nullable|integer|min:1|max:24',
            'advance_notice_days' => 'nullable|integer|min:0|max:90',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer|min:0',
            'eligibility_rules' => 'nullable|array',
            'color_code' => 'nullable|string|max:7',
        ]);

        $leaveType->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث نوع الإجازة بنجاح',
            'data' => new LeaveTypeResource($leaveType),
        ]);
    }

    /**
     * حذف نوع إجازة
     */
    public function destroy(LeaveType $leaveType): JsonResponse
    {
        // التحقق من عدم وجود طلبات إجازة مرتبطة
        if ($leaveType->leaveRequests()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن حذف نوع الإجازة لوجود طلبات مرتبطة به',
            ], 400);
        }

        $leaveType->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف نوع الإجازة بنجاح',
        ]);
    }
}
