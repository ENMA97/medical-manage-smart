<?php

namespace App\Http\Controllers\Api\HR;

use App\Http\Controllers\Controller;
use App\Http\Resources\HR\DepartmentResource;
use App\Http\Resources\HR\EmployeeResource;
use App\Models\HR\Department;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class DepartmentController extends Controller
{
    /**
     * قائمة الأقسام
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Department::with(['parent', 'manager'])
            ->withCount('employees')
            ->when($request->is_active !== null, fn($q) => $q->where('is_active', $request->boolean('is_active')))
            ->when($request->search, function ($q, $search) {
                $q->where(function ($query) use ($search) {
                    $query->where('code', 'like', "%{$search}%")
                        ->orWhere('name_ar', 'like', "%{$search}%")
                        ->orWhere('name_en', 'like', "%{$search}%");
                });
            })
            ->orderBy('sort_order')
            ->orderBy('name_ar');

        $departments = $request->per_page 
            ? $query->paginate($request->per_page)
            : $query->get();

        return DepartmentResource::collection($departments);
    }

    /**
     * شجرة الأقسام
     */
    public function tree(): AnonymousResourceCollection
    {
        $departments = Department::with(['children.children', 'manager'])
            ->whereNull('parent_id')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        return DepartmentResource::collection($departments);
    }

    /**
     * الأقسام النشطة
     */
    public function active(): AnonymousResourceCollection
    {
        $departments = Department::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name_ar')
            ->get();

        return DepartmentResource::collection($departments);
    }

    /**
     * إنشاء قسم جديد
     */
    public function store(Request $request): JsonResponse
    {
        if (Gate::denies('hr.manage')) {
            abort(403, 'غير مصرح لك بإنشاء أقسام');
        }

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:20', 'unique:departments,code'],
            'name_ar' => ['required', 'string', 'max:100'],
            'name_en' => ['nullable', 'string', 'max:100'],
            'description_ar' => ['nullable', 'string', 'max:500'],
            'parent_id' => ['nullable', 'uuid', 'exists:departments,id'],
            'manager_id' => ['nullable', 'uuid', 'exists:employees,id'],
            'cost_center_code' => ['nullable', 'string', 'max:20'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer'],
        ]);

        $department = Department::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'تم إنشاء القسم بنجاح',
            'data' => new DepartmentResource($department->load(['parent', 'manager'])),
        ], 201);
    }

    /**
     * عرض قسم
     */
    public function show(Department $department): DepartmentResource
    {
        return new DepartmentResource(
            $department->load(['parent', 'children', 'manager'])
                ->loadCount('employees')
        );
    }

    /**
     * تحديث قسم
     */
    public function update(Request $request, Department $department): JsonResponse
    {
        if (Gate::denies('hr.manage')) {
            abort(403, 'غير مصرح لك بتعديل الأقسام');
        }

        $validated = $request->validate([
            'code' => ['sometimes', 'string', 'max:20', 'unique:departments,code,' . $department->id],
            'name_ar' => ['sometimes', 'string', 'max:100'],
            'name_en' => ['nullable', 'string', 'max:100'],
            'description_ar' => ['nullable', 'string', 'max:500'],
            'parent_id' => ['nullable', 'uuid', 'exists:departments,id'],
            'manager_id' => ['nullable', 'uuid', 'exists:employees,id'],
            'cost_center_code' => ['nullable', 'string', 'max:20'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer'],
        ]);

        // منع جعل القسم parent لنفسه
        if (isset($validated['parent_id']) && $validated['parent_id'] === $department->id) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن جعل القسم تابعاً لنفسه',
            ], 422);
        }

        $department->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث القسم بنجاح',
            'data' => new DepartmentResource($department->fresh(['parent', 'manager'])),
        ]);
    }

    /**
     * حذف قسم
     */
    public function destroy(Department $department): JsonResponse
    {
        if (Gate::denies('hr.manage')) {
            abort(403, 'غير مصرح لك بحذف الأقسام');
        }

        // التحقق من عدم وجود موظفين
        if ($department->employees()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن حذف القسم لوجود موظفين مرتبطين به',
            ], 422);
        }

        // التحقق من عدم وجود أقسام فرعية
        if ($department->children()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن حذف القسم لوجود أقسام فرعية',
            ], 422);
        }

        $department->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف القسم بنجاح',
        ]);
    }

    /**
     * موظفي القسم
     */
    public function employees(Department $department): AnonymousResourceCollection
    {
        $employees = $department->employees()
            ->with(['position'])
            ->where('is_active', true)
            ->orderBy('first_name_ar')
            ->get();

        return EmployeeResource::collection($employees);
    }
}
