<?php

namespace App\Http\Controllers\Api\HR;

use App\Http\Controllers\Controller;
use App\Http\Requests\HR\StoreContractRequest;
use App\Http\Resources\HR\ContractResource;
use App\Models\HR\Contract;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class ContractController extends Controller
{
    /**
     * قائمة العقود
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Contract::with(['employee.department', 'employee.position'])
            ->when($request->employee_id, fn($q, $id) => $q->where('employee_id', $id))
            ->when($request->type, fn($q, $type) => $q->where('type', $type))
            ->when($request->is_active !== null, fn($q) => $q->where('is_active', $request->boolean('is_active')))
            ->when($request->search, function ($q, $search) {
                $q->where('contract_number', 'like', "%{$search}%")
                    ->orWhereHas('employee', function ($query) use ($search) {
                        $query->where('first_name_ar', 'like', "%{$search}%")
                            ->orWhere('last_name_ar', 'like', "%{$search}%")
                            ->orWhere('employee_number', 'like', "%{$search}%");
                    });
            })
            ->orderBy('start_date', 'desc');

        $contracts = $request->per_page 
            ? $query->paginate($request->per_page)
            : $query->get();

        return ContractResource::collection($contracts);
    }

    /**
     * العقود المنتهية قريباً
     */
    public function expiring(Request $request): AnonymousResourceCollection
    {
        $days = $request->get('days', 30);

        $contracts = Contract::with(['employee.department'])
            ->where('is_active', true)
            ->where('is_indefinite', false)
            ->whereNotNull('end_date')
            ->whereBetween('end_date', [now(), now()->addDays($days)])
            ->orderBy('end_date')
            ->get();

        return ContractResource::collection($contracts);
    }

    /**
     * إنشاء عقد جديد
     */
    public function store(StoreContractRequest $request): JsonResponse
    {
        if (Gate::denies('hr.manage')) {
            abort(403, 'غير مصرح لك بإنشاء عقود');
        }

        $data = $request->validated();

        // إنهاء العقد السابق إن وجد
        Contract::where('employee_id', $data['employee_id'])
            ->where('is_active', true)
            ->update([
                'is_active' => false,
                'termination_date' => now(),
                'termination_reason' => 'تم استبداله بعقد جديد',
            ]);

        $contract = Contract::create($data);

        return response()->json([
            'success' => true,
            'message' => 'تم إنشاء العقد بنجاح',
            'data' => new ContractResource($contract->load('employee')),
        ], 201);
    }

    /**
     * عرض عقد
     */
    public function show(Contract $contract): ContractResource
    {
        return new ContractResource($contract->load('employee.department'));
    }

    /**
     * تحديث عقد
     */
    public function update(Request $request, Contract $contract): JsonResponse
    {
        if (Gate::denies('hr.manage')) {
            abort(403, 'غير مصرح لك بتعديل العقود');
        }

        $validated = $request->validate([
            'basic_salary' => ['sometimes', 'numeric', 'min:0'],
            'housing_allowance' => ['nullable', 'numeric', 'min:0'],
            'transportation_allowance' => ['nullable', 'numeric', 'min:0'],
            'food_allowance' => ['nullable', 'numeric', 'min:0'],
            'phone_allowance' => ['nullable', 'numeric', 'min:0'],
            'other_allowances' => ['nullable', 'numeric', 'min:0'],
            'allowance_details' => ['nullable', 'array'],
            'working_hours_per_week' => ['sometimes', 'integer', 'min:1', 'max:60'],
            'working_days_per_week' => ['sometimes', 'integer', 'min:1', 'max:7'],
            'annual_leave_days' => ['sometimes', 'integer', 'min:21'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $contract->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث العقد بنجاح',
            'data' => new ContractResource($contract->fresh('employee')),
        ]);
    }

    /**
     * تجديد العقد
     */
    public function renew(Request $request, Contract $contract): JsonResponse
    {
        if (Gate::denies('hr.manage')) {
            abort(403, 'غير مصرح لك بتجديد العقود');
        }

        $validated = $request->validate([
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after:start_date'],
            'is_indefinite' => ['sometimes', 'boolean'],
            'basic_salary' => ['sometimes', 'numeric', 'min:0'],
            'housing_allowance' => ['nullable', 'numeric', 'min:0'],
            'transportation_allowance' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        // إنهاء العقد الحالي
        $contract->update([
            'is_active' => false,
            'termination_date' => $validated['start_date'],
            'termination_reason' => 'تم التجديد',
        ]);

        // إنشاء عقد جديد
        $newContract = Contract::create([
            'contract_number' => Contract::generateContractNumber(),
            'employee_id' => $contract->employee_id,
            'type' => $contract->type,
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'] ?? null,
            'is_indefinite' => $validated['is_indefinite'] ?? $contract->is_indefinite,
            'basic_salary' => $validated['basic_salary'] ?? $contract->basic_salary,
            'housing_allowance' => $validated['housing_allowance'] ?? $contract->housing_allowance,
            'transportation_allowance' => $validated['transportation_allowance'] ?? $contract->transportation_allowance,
            'food_allowance' => $contract->food_allowance,
            'phone_allowance' => $contract->phone_allowance,
            'other_allowances' => $contract->other_allowances,
            'working_hours_per_week' => $contract->working_hours_per_week,
            'working_days_per_week' => $contract->working_days_per_week,
            'annual_leave_days' => $contract->annual_leave_days,
            'sick_leave_days' => $contract->sick_leave_days,
            'is_active' => true,
            'notes' => $validated['notes'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم تجديد العقد بنجاح',
            'data' => new ContractResource($newContract->load('employee')),
        ], 201);
    }

    /**
     * إنهاء العقد
     */
    public function terminate(Request $request, Contract $contract): JsonResponse
    {
        if (Gate::denies('hr.manage')) {
            abort(403, 'غير مصرح لك بإنهاء العقود');
        }

        $validated = $request->validate([
            'termination_date' => ['required', 'date'],
            'termination_reason' => ['required', 'string', 'max:500'],
        ]);

        $contract->update([
            'is_active' => false,
            'termination_date' => $validated['termination_date'],
            'termination_reason' => $validated['termination_reason'],
            'terminated_by' => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم إنهاء العقد بنجاح',
            'data' => new ContractResource($contract->fresh('employee')),
        ]);
    }
}
