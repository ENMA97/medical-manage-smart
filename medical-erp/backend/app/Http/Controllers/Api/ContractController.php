<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Contract\StoreContractRequest;
use App\Models\Contract;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ContractController extends Controller
{
    /**
     * GET /api/contracts
     * قائمة العقود مع التصفية
     */
    public function index(Request $request): JsonResponse
    {
        $contracts = Contract::with(['employee'])
            ->when($request->filled('employee_id'), fn($q) => $q->where('employee_id', $request->input('employee_id')))
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->input('status')))
            ->when($request->filled('contract_type'), fn($q) => $q->where('contract_type', $request->input('contract_type')))
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('contract_number', 'like', "%{$search}%")
                      ->orWhereHas('employee', function ($eq) use ($search) {
                          $eq->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('employee_number', 'like', "%{$search}%");
                      });
                });
            })
            ->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 15));

        return response()->json([
            'success' => true,
            'message' => 'تم جلب قائمة العقود بنجاح',
            'data' => $contracts,
        ]);
    }

    /**
     * POST /api/contracts
     * إنشاء عقد جديد
     */
    public function store(StoreContractRequest $request): JsonResponse
    {
        try {
            $data = $request->only([
                'employee_id', 'contract_type', 'start_date', 'end_date',
                'basic_salary', 'housing_allowance', 'transport_allowance',
                'food_allowance', 'phone_allowance', 'other_allowances',
                'duration_months', 'probation_days', 'probation_end_date',
                'annual_leave_days', 'sick_leave_days', 'notice_period_days',
                'terms_and_conditions', 'benefits', 'special_clauses',
                'percentage_rate', 'previous_contract_id',
            ]);

            // حساب إجمالي الراتب
            $data['total_salary'] = ($data['basic_salary'] ?? 0)
                + ($data['housing_allowance'] ?? 0)
                + ($data['transport_allowance'] ?? 0)
                + ($data['food_allowance'] ?? 0)
                + ($data['phone_allowance'] ?? 0)
                + ($data['other_allowances'] ?? 0);

            // توليد رقم العقد
            $data['contract_number'] = 'CNT-' . date('Y') . '-' . str_pad(
                Contract::withTrashed()->count() + 1,
                5,
                '0',
                STR_PAD_LEFT
            );

            $data['status'] = 'draft';
            $data['created_by'] = auth()->id();

            $contract = Contract::create($data);
            $contract->load(['employee', 'alerts']);

            return response()->json([
                'success' => true,
                'message' => 'تم إنشاء العقد بنجاح',
                'data' => $contract,
            ], 201);
        } catch (\Illuminate\Database\QueryException $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إنشاء العقد',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * GET /api/contracts/{id}
     * عرض تفاصيل عقد
     */
    public function show(string $id): JsonResponse
    {
        $contract = Contract::with(['employee', 'alerts', 'renewals', 'previousContract'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'message' => 'تم جلب بيانات العقد بنجاح',
            'data' => $contract,
        ]);
    }

    /**
     * PUT /api/contracts/{id}
     * تحديث عقد
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $contract = Contract::findOrFail($id);

        $request->validate([
            'contract_type' => 'sometimes|in:full_time,part_time,temporary,tamheer,percentage,locum,probation',
            'start_date' => 'sometimes|date',
            'end_date' => 'nullable|date|after:start_date',
            'basic_salary' => 'sometimes|numeric|min:0',
            'housing_allowance' => 'nullable|numeric|min:0',
            'transport_allowance' => 'nullable|numeric|min:0',
            'food_allowance' => 'nullable|numeric|min:0',
            'phone_allowance' => 'nullable|numeric|min:0',
            'other_allowances' => 'nullable|numeric|min:0',
            'status' => 'sometimes|in:draft,pending_approval,active,expired,terminated,renewed,suspended',
        ]);

        try {
            $data = $request->only([
                'contract_type', 'status', 'start_date', 'end_date',
                'basic_salary', 'housing_allowance', 'transport_allowance',
                'food_allowance', 'phone_allowance', 'other_allowances',
                'duration_months', 'probation_days', 'probation_end_date',
                'annual_leave_days', 'sick_leave_days', 'notice_period_days',
                'terms_and_conditions', 'benefits', 'special_clauses',
                'percentage_rate',
            ]);

            // إعادة حساب إجمالي الراتب عند تغيير أي بدل
            $basicSalary = $data['basic_salary'] ?? $contract->basic_salary;
            $housing = $data['housing_allowance'] ?? $contract->housing_allowance;
            $transport = $data['transport_allowance'] ?? $contract->transport_allowance;
            $food = $data['food_allowance'] ?? $contract->food_allowance;
            $phone = $data['phone_allowance'] ?? $contract->phone_allowance;
            $other = $data['other_allowances'] ?? $contract->other_allowances;
            $data['total_salary'] = $basicSalary + $housing + $transport + $food + $phone + $other;

            $contract->update($data);
            $contract->load(['employee', 'alerts']);

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث العقد بنجاح',
                'data' => $contract,
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تحديث العقد',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * POST /api/contracts/{id}/renew
     * تجديد عقد (إنشاء عقد جديد مرتبط بالسابق)
     */
    public function renew(Request $request, string $id): JsonResponse
    {
        $contract = Contract::findOrFail($id);

        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after:start_date',
            'duration_months' => 'nullable|integer|min:1',
            'basic_salary' => 'nullable|numeric|min:0',
            'housing_allowance' => 'nullable|numeric|min:0',
            'transport_allowance' => 'nullable|numeric|min:0',
            'food_allowance' => 'nullable|numeric|min:0',
            'phone_allowance' => 'nullable|numeric|min:0',
            'other_allowances' => 'nullable|numeric|min:0',
        ]);

        try {
            $newData = [
                'employee_id' => $contract->employee_id,
                'contract_type' => $contract->contract_type,
                'start_date' => $request->input('start_date'),
                'end_date' => $request->input('end_date', $contract->end_date),
                'duration_months' => $request->input('duration_months', $contract->duration_months),
                'basic_salary' => $request->input('basic_salary', $contract->basic_salary),
                'housing_allowance' => $request->input('housing_allowance', $contract->housing_allowance),
                'transport_allowance' => $request->input('transport_allowance', $contract->transport_allowance),
                'food_allowance' => $request->input('food_allowance', $contract->food_allowance),
                'phone_allowance' => $request->input('phone_allowance', $contract->phone_allowance),
                'other_allowances' => $request->input('other_allowances', $contract->other_allowances),
                'annual_leave_days' => $contract->annual_leave_days,
                'sick_leave_days' => $contract->sick_leave_days,
                'notice_period_days' => $contract->notice_period_days,
                'probation_days' => 0,
                'previous_contract_id' => $contract->id,
                'status' => 'draft',
                'created_by' => auth()->id(),
                'contract_number' => 'CNT-' . date('Y') . '-' . str_pad(
                    Contract::withTrashed()->count() + 1,
                    5,
                    '0',
                    STR_PAD_LEFT
                ),
            ];

            $newData['total_salary'] = $newData['basic_salary']
                + $newData['housing_allowance']
                + $newData['transport_allowance']
                + $newData['food_allowance']
                + $newData['phone_allowance']
                + $newData['other_allowances'];

            $newContract = Contract::create($newData);

            // تحديث حالة العقد القديم
            $contract->update(['status' => 'renewed']);

            $newContract->load(['employee', 'previousContract']);

            return response()->json([
                'success' => true,
                'message' => 'تم تجديد العقد بنجاح',
                'data' => $newContract,
            ], 201);
        } catch (\Illuminate\Database\QueryException $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تجديد العقد',
                'error' => $e->getMessage(),
            ], 422);
        }
    }
}
