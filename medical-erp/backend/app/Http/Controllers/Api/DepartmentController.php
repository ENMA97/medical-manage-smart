<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Department\StoreDepartmentRequest;
use App\Http\Requests\Department\UpdateDepartmentRequest;
use App\Models\Department;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    /**
     * GET /api/departments
     * قائمة الأقسام مع عدد الموظفين
     */
    public function index(Request $request): JsonResponse
    {
        $departments = Department::withCount('employees')
            ->with(['parent', 'manager'])
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('name_ar', 'like', "%{$search}%")
                      ->orWhere('code', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('is_active'), fn($q) => $q->where('is_active', $request->boolean('is_active')))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate($request->input('per_page', 15));

        return response()->json([
            'success' => true,
            'message' => 'تم جلب قائمة الأقسام بنجاح',
            'data' => $departments,
        ]);
    }

    /**
     * POST /api/departments
     * إنشاء قسم جديد
     */
    public function store(StoreDepartmentRequest $request): JsonResponse
    {
        $request->validate([
            'code' => 'required|string|unique:departments,code',
            'name' => 'required|string|max:255',
            'name_ar' => 'required|string|max:255',
            'parent_id' => 'nullable|exists:departments,id',
            'manager_id' => 'nullable|exists:employees,id',
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
            'sort_order' => 'nullable|integer',
        ]);

        try {
            $department = Department::create($request->only([
                'code', 'name', 'name_ar', 'parent_id', 'manager_id',
                'description', 'is_active', 'sort_order',
            ]));

            $department->load(['parent', 'manager']);

            return response()->json([
                'success' => true,
                'message' => 'تم إنشاء القسم بنجاح',
                'data' => $department,
            ], 201);
        } catch (\Illuminate\Database\QueryException $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إنشاء القسم',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * GET /api/departments/{id}
     * عرض قسم مع الموظفين
     */
    public function show(string $id): JsonResponse
    {
        $department = Department::with(['employees', 'parent', 'manager', 'children'])
            ->withCount('employees')
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'message' => 'تم جلب بيانات القسم بنجاح',
            'data' => $department,
        ]);
    }

    /**
     * PUT /api/departments/{id}
     * تحديث قسم
     */
    public function update(UpdateDepartmentRequest $request, string $id): JsonResponse
    {
        $department = Department::findOrFail($id);

        $request->validate([
            'code' => 'sometimes|string|unique:departments,code,' . $id,
            'name' => 'sometimes|string|max:255',
            'name_ar' => 'sometimes|string|max:255',
            'parent_id' => 'nullable|exists:departments,id',
            'manager_id' => 'nullable|exists:employees,id',
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
            'sort_order' => 'nullable|integer',
        ]);

        try {
            $department->update($request->only([
                'code', 'name', 'name_ar', 'parent_id', 'manager_id',
                'description', 'is_active', 'sort_order',
            ]));

            $department->load(['parent', 'manager']);

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث القسم بنجاح',
                'data' => $department,
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تحديث القسم',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * DELETE /api/departments/{id}
     * حذف قسم (حذف ناعم)
     */
    public function destroy(string $id): JsonResponse
    {
        $department = Department::findOrFail($id);

        try {
            $department->delete();

            return response()->json([
                'success' => true,
                'message' => 'تم حذف القسم بنجاح',
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء حذف القسم',
                'error' => $e->getMessage(),
            ], 422);
        }
    }
}
