<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Employee\StoreEmployeeRequest;
use App\Http\Requests\Employee\UpdateEmployeeRequest;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class EmployeeController extends Controller
{
    /**
     * GET /api/employees
     * قائمة الموظفين مع البحث والتصفية والترقيم
     */
    public function index(Request $request): JsonResponse
    {
        $employees = Employee::with(['department', 'position'])
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                      ->orWhere('last_name', 'like', "%{$search}%")
                      ->orWhere('first_name_ar', 'like', "%{$search}%")
                      ->orWhere('last_name_ar', 'like', "%{$search}%")
                      ->orWhere('employee_number', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('department_id'), fn($q) => $q->where('department_id', $request->input('department_id')))
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->input('status')))
            ->when($request->filled('employment_type'), fn($q) => $q->where('employment_type', $request->input('employment_type')))
            ->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 15));

        return response()->json([
            'success' => true,
            'message' => 'تم جلب قائمة الموظفين بنجاح',
            'data' => $employees,
        ]);
    }

    /**
     * POST /api/employees
     * إنشاء موظف جديد مع حساب مستخدم
     */
    public function store(StoreEmployeeRequest $request): JsonResponse
    {
        try {
            $employee = DB::transaction(function () use ($request) {
                $employee = Employee::create($request->only([
                    'employee_number', 'first_name', 'second_name', 'third_name', 'last_name',
                    'first_name_ar', 'second_name_ar', 'third_name_ar', 'last_name_ar',
                    'email', 'phone', 'phone_secondary', 'personal_email',
                    'national_id', 'id_type', 'id_expiry_date',
                    'passport_number', 'passport_expiry_date',
                    'department_id', 'position_id', 'direct_manager_id',
                    'hire_date', 'actual_start_date', 'employment_type', 'status',
                    'gender', 'date_of_birth', 'place_of_birth', 'marital_status',
                    'dependents_count', 'nationality', 'nationality_ar',
                    'bank_name', 'bank_account_number', 'iban', 'gosi_number',
                    'address', 'city', 'postal_code',
                    'blood_type', 'medical_conditions', 'metadata',
                ]));

                // إنشاء حساب مستخدم مرتبط
                User::create([
                    'username' => $employee->employee_number,
                    'full_name' => $employee->full_name_en,
                    'full_name_ar' => $employee->full_name_ar,
                    'email' => $employee->email,
                    'phone' => $employee->phone,
                    'password' => Hash::make($employee->employee_number),
                    'employee_id' => $employee->id,
                    'user_type' => 'employee',
                    'is_active' => true,
                ]);

                return $employee;
            });

            $employee->load(['department', 'position', 'directManager']);

            return response()->json([
                'success' => true,
                'message' => 'تم إنشاء الموظف بنجاح',
                'data' => $employee,
            ], 201);
        } catch (\Illuminate\Database\QueryException $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إنشاء الموظف',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * GET /api/employees/{id}
     * عرض بيانات موظف مع العلاقات
     */
    public function show(string $id): JsonResponse
    {
        $employee = Employee::with(['department', 'position', 'directManager', 'user'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'message' => 'تم جلب بيانات الموظف بنجاح',
            'data' => $employee,
        ]);
    }

    /**
     * PUT /api/employees/{id}
     * تحديث بيانات موظف
     */
    public function update(UpdateEmployeeRequest $request, string $id): JsonResponse
    {
        $employee = Employee::findOrFail($id);

        try {
            $employee->update($request->only([
                'employee_number', 'first_name', 'second_name', 'third_name', 'last_name',
                'first_name_ar', 'second_name_ar', 'third_name_ar', 'last_name_ar',
                'email', 'phone', 'phone_secondary', 'personal_email',
                'national_id', 'id_type', 'id_expiry_date',
                'passport_number', 'passport_expiry_date',
                'department_id', 'position_id', 'direct_manager_id',
                'hire_date', 'actual_start_date', 'termination_date',
                'employment_type', 'status',
                'gender', 'date_of_birth', 'place_of_birth', 'marital_status',
                'dependents_count', 'nationality', 'nationality_ar',
                'bank_name', 'bank_account_number', 'iban', 'gosi_number',
                'address', 'city', 'postal_code',
                'blood_type', 'medical_conditions', 'metadata',
            ]));

            $employee->load(['department', 'position', 'directManager']);

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث بيانات الموظف بنجاح',
                'data' => $employee,
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تحديث بيانات الموظف',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * DELETE /api/employees/{id}
     * حذف موظف (حذف ناعم)
     */
    public function destroy(string $id): JsonResponse
    {
        $employee = Employee::findOrFail($id);

        try {
            $employee->delete();

            return response()->json([
                'success' => true,
                'message' => 'تم حذف الموظف بنجاح',
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء حذف الموظف',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * GET /api/employees/{id}/documents
     * جلب مستندات الموظف (placeholder)
     */
    public function documents(string $id): JsonResponse
    {
        $employee = Employee::findOrFail($id);

        return response()->json([
            'success' => true,
            'message' => 'تم جلب مستندات الموظف بنجاح',
            'data' => [],
        ]);
    }
}
