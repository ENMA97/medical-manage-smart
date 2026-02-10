<?php

namespace App\Http\Controllers\Api\HR;

use App\Http\Controllers\Controller;
use App\Http\Requests\HR\StoreEmployeeRequest;
use App\Http\Requests\HR\UpdateEmployeeRequest;
use App\Http\Resources\HR\ContractResource;
use App\Http\Resources\HR\EmployeeResource;
use App\Models\HR\Employee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class EmployeeController extends Controller
{
    /**
     * قائمة الموظفين
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Employee::with(['department', 'position'])
            ->when($request->department_id, fn($q, $id) => $q->where('department_id', $id))
            ->when($request->position_id, fn($q, $id) => $q->where('position_id', $id))
            ->when($request->employee_type, fn($q, $type) => $q->where('employee_type', $type))
            ->when($request->status, fn($q, $status) => $q->where('status', $status))
            ->when($request->is_active !== null, fn($q) => $q->where('is_active', $request->boolean('is_active')))
            ->when($request->search, function ($q, $search) {
                $q->where(function ($query) use ($search) {
                    $query->where('employee_number', 'like', "%{$search}%")
                        ->orWhere('first_name_ar', 'like', "%{$search}%")
                        ->orWhere('last_name_ar', 'like', "%{$search}%")
                        ->orWhere('first_name_en', 'like', "%{$search}%")
                        ->orWhere('last_name_en', 'like', "%{$search}%")
                        ->orWhere('national_id', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->orderBy($request->sort_by ?? 'created_at', $request->sort_dir ?? 'desc');

        $employees = $request->per_page 
            ? $query->paginate($request->per_page)
            : $query->get();

        return EmployeeResource::collection($employees);
    }

    /**
     * الموظفين النشطين
     */
    public function active(): AnonymousResourceCollection
    {
        $employees = Employee::with(['department', 'position'])
            ->where('is_active', true)
            ->orderBy('first_name_ar')
            ->get();

        return EmployeeResource::collection($employees);
    }

    /**
     * البحث عن موظف
     */
    public function search(Request $request): AnonymousResourceCollection
    {
        $query = $request->get('q', '');

        $employees = Employee::with(['department', 'position'])
            ->where('is_active', true)
            ->where(function ($q) use ($query) {
                $q->where('employee_number', 'like', "%{$query}%")
                    ->orWhere('first_name_ar', 'like', "%{$query}%")
                    ->orWhere('last_name_ar', 'like', "%{$query}%")
                    ->orWhere('first_name_en', 'like', "%{$query}%")
                    ->orWhere('last_name_en', 'like', "%{$query}%");
            })
            ->limit(20)
            ->get();

        return EmployeeResource::collection($employees);
    }

    /**
     * إنشاء موظف جديد
     */
    public function store(StoreEmployeeRequest $request): JsonResponse
    {
        if (Gate::denies('hr.manage')) {
            abort(403, 'غير مصرح لك بإضافة موظفين');
        }

        $employee = Employee::create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'تم إنشاء الموظف بنجاح',
            'data' => new EmployeeResource($employee->load(['department', 'position'])),
        ], 201);
    }

    /**
     * عرض موظف
     */
    public function show(Employee $employee): EmployeeResource
    {
        return new EmployeeResource(
            $employee->load([
                'department',
                'position',
                'activeContract',
                'activeCustodies',
            ])
        );
    }

    /**
     * تحديث موظف
     */
    public function update(UpdateEmployeeRequest $request, Employee $employee): JsonResponse
    {
        if (Gate::denies('hr.manage')) {
            abort(403, 'غير مصرح لك بتعديل بيانات الموظفين');
        }

        $employee->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث بيانات الموظف بنجاح',
            'data' => new EmployeeResource($employee->fresh(['department', 'position'])),
        ]);
    }

    /**
     * حذف موظف (soft delete)
     */
    public function destroy(Employee $employee): JsonResponse
    {
        if (Gate::denies('hr.manage')) {
            abort(403, 'غير مصرح لك بحذف الموظفين');
        }

        // التحقق من عدم وجود عهد نشطة
        if ($employee->activeCustodies()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن حذف الموظف لوجود عهد نشطة',
            ], 422);
        }

        $employee->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف الموظف بنجاح',
        ]);
    }

    /**
     * عقود الموظف
     */
    public function contracts(Employee $employee): AnonymousResourceCollection
    {
        $contracts = $employee->contracts()
            ->orderBy('start_date', 'desc')
            ->get();

        return ContractResource::collection($contracts);
    }

    /**
     * عهد الموظف
     */
    public function custodies(Employee $employee): JsonResponse
    {
        $custodies = $employee->custodies()
            ->orderBy('assigned_date', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $custodies,
        ]);
    }

    /**
     * مستندات الموظف
     */
    public function documents(Employee $employee): JsonResponse
    {
        // يمكن إضافة جدول documents لاحقاً
        return response()->json([
            'success' => true,
            'data' => [],
        ]);
    }
}
