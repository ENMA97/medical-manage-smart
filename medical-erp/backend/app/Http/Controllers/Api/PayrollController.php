<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\Employee;
use App\Models\Payroll;
use App\Models\PayrollItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PayrollController extends Controller
{
    /**
     * GET /api/payrolls
     * قائمة كشوف المرتبات مع التصفية
     */
    public function index(Request $request): JsonResponse
    {
        $payrolls = Payroll::query()
            ->when($request->filled('month'), fn($q) => $q->where('month', $request->input('month')))
            ->when($request->filled('year'), fn($q) => $q->where('year', $request->input('year')))
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->input('status')))
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->input('search');
                $query->where('payroll_number', 'like', "%{$search}%");
            })
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->paginate($request->input('per_page', 15));

        return response()->json([
            'success' => true,
            'message' => 'تم جلب قائمة كشوف المرتبات بنجاح',
            'data' => $payrolls,
        ]);
    }

    /**
     * POST /api/payrolls
     * إنشاء كشف مرتبات لموظف أو قسم
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2020',
            'employee_id' => 'nullable|exists:employees,id',
            'department_id' => 'nullable|exists:departments,id',
            'payment_date' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        try {
            $payroll = DB::transaction(function () use ($request) {
                // توليد رقم كشف المرتبات
                $payrollNumber = 'PAY-' . $request->input('year') . '-' . str_pad(
                    $request->input('month'),
                    2,
                    '0',
                    STR_PAD_LEFT
                ) . '-' . str_pad(
                    Payroll::count() + 1,
                    4,
                    '0',
                    STR_PAD_LEFT
                );

                $payroll = Payroll::create([
                    'payroll_number' => $payrollNumber,
                    'month' => $request->input('month'),
                    'year' => $request->input('year'),
                    'status' => 'draft',
                    'payment_date' => $request->input('payment_date'),
                    'notes' => $request->input('notes'),
                    'created_by' => auth()->id(),
                    'total_basic_salary' => 0,
                    'total_allowances' => 0,
                    'total_additions' => 0,
                    'total_deductions' => 0,
                    'total_overtime' => 0,
                    'total_gosi_employee' => 0,
                    'total_gosi_employer' => 0,
                    'total_gross_salary' => 0,
                    'total_net_salary' => 0,
                    'employees_count' => 0,
                ]);

                // جلب الموظفين حسب الفلتر
                $employeesQuery = Employee::active()->with('department');

                if ($request->filled('employee_id')) {
                    $employeesQuery->where('id', $request->input('employee_id'));
                } elseif ($request->filled('department_id')) {
                    $employeesQuery->where('department_id', $request->input('department_id'));
                }

                $employees = $employeesQuery->get();

                $totals = [
                    'basic_salary' => 0,
                    'allowances' => 0,
                    'gross_salary' => 0,
                    'deductions' => 0,
                    'net_salary' => 0,
                    'gosi_employee' => 0,
                    'gosi_employer' => 0,
                ];

                foreach ($employees as $employee) {
                    // جلب العقد النشط للموظف
                    $contract = Contract::where('employee_id', $employee->id)
                        ->where('status', 'active')
                        ->first();

                    if (!$contract) {
                        continue;
                    }

                    $basicSalary = $contract->basic_salary ?? 0;
                    $housing = $contract->housing_allowance ?? 0;
                    $transport = $contract->transport_allowance ?? 0;
                    $food = $contract->food_allowance ?? 0;
                    $phone = $contract->phone_allowance ?? 0;
                    $other = $contract->other_allowances ?? 0;
                    $totalAllowances = $housing + $transport + $food + $phone + $other;
                    $grossSalary = $basicSalary + $totalAllowances;
                    $gosiEmployee = $basicSalary * 0.0975; // 9.75%
                    $gosiEmployer = $basicSalary * 0.1175; // 11.75%
                    $totalDeductions = $gosiEmployee;
                    $netSalary = $grossSalary - $totalDeductions;

                    PayrollItem::create([
                        'payroll_id' => $payroll->id,
                        'employee_id' => $employee->id,
                        'contract_id' => $contract->id,
                        'basic_salary' => $basicSalary,
                        'housing_allowance' => $housing,
                        'transport_allowance' => $transport,
                        'food_allowance' => $food,
                        'phone_allowance' => $phone,
                        'other_allowances' => $other,
                        'gosi_employee' => $gosiEmployee,
                        'gosi_employer' => $gosiEmployer,
                        'gross_salary' => $grossSalary,
                        'total_deductions' => $totalDeductions,
                        'net_salary' => $netSalary,
                        'total_working_days' => 30,
                        'actual_working_days' => 30,
                        'bank_name' => $employee->bank_name,
                        'iban' => $employee->iban,
                    ]);

                    $totals['basic_salary'] += $basicSalary;
                    $totals['allowances'] += $totalAllowances;
                    $totals['gross_salary'] += $grossSalary;
                    $totals['deductions'] += $totalDeductions;
                    $totals['net_salary'] += $netSalary;
                    $totals['gosi_employee'] += $gosiEmployee;
                    $totals['gosi_employer'] += $gosiEmployer;
                }

                // تحديث إجماليات كشف المرتبات
                $payroll->update([
                    'total_basic_salary' => $totals['basic_salary'],
                    'total_allowances' => $totals['allowances'],
                    'total_gross_salary' => $totals['gross_salary'],
                    'total_deductions' => $totals['deductions'],
                    'total_net_salary' => $totals['net_salary'],
                    'total_gosi_employee' => $totals['gosi_employee'],
                    'total_gosi_employer' => $totals['gosi_employer'],
                    'employees_count' => $employees->count(),
                ]);

                return $payroll;
            });

            $payroll->load('items.employee');

            return response()->json([
                'success' => true,
                'message' => 'تم إنشاء كشف المرتبات بنجاح',
                'data' => $payroll,
            ], 201);
        } catch (\Illuminate\Database\QueryException $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إنشاء كشف المرتبات',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * GET /api/payrolls/{id}
     * عرض تفاصيل كشف مرتبات مع العناصر
     */
    public function show(string $id): JsonResponse
    {
        $payroll = Payroll::with(['items.employee'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'message' => 'تم جلب بيانات كشف المرتبات بنجاح',
            'data' => $payroll,
        ]);
    }

    /**
     * POST /api/payrolls/{id}/approve
     * اعتماد كشف مرتبات
     */
    public function approve(Request $request, string $id): JsonResponse
    {
        $payroll = Payroll::findOrFail($id);

        if ($payroll->status !== 'draft' && $payroll->status !== 'reviewed') {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن اعتماد كشف المرتبات في حالته الحالية',
            ], 422);
        }

        try {
            $payroll->update([
                'status' => 'approved',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'تم اعتماد كشف المرتبات بنجاح',
                'data' => $payroll,
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء اعتماد كشف المرتبات',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * GET /api/payrolls/{id}/export
     * تصدير كشف مرتبات (placeholder)
     */
    public function export(string $id): JsonResponse
    {
        $payroll = Payroll::with(['items.employee'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'message' => 'تم تجهيز ملف التصدير بنجاح',
            'data' => [
                'payroll_id' => $payroll->id,
                'payroll_number' => $payroll->payroll_number,
                'download_url' => null, // سيتم إضافة رابط التحميل لاحقاً
                'note' => 'خاصية التصدير قيد التطوير',
            ],
        ]);
    }
}
