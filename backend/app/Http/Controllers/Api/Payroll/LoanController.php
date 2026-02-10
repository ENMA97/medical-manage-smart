<?php

namespace App\Http\Controllers\Api\Payroll;

use App\Http\Controllers\Controller;
use App\Models\Payroll\EmployeeLoan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

class LoanController extends Controller
{
    /**
     * عرض السلف
     */
    public function index(Request $request): JsonResponse
    {
        // التحقق من صلاحية عرض السلف
        if (Gate::denies('payroll.view')) {
            return response()->json([
                'success' => false,
                'message' => 'ليس لديك صلاحية لعرض السلف',
            ], 403);
        }

        $query = EmployeeLoan::with(['employee:id,name_ar,name_en,employee_number']);

        if ($request->has('employee_id')) {
            $query->forEmployee($request->employee_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        $loans = $query->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 20);

        return response()->json([
            'success' => true,
            'data' => $loans,
        ]);
    }

    /**
     * عرض تفاصيل سلفة
     */
    public function show(EmployeeLoan $loan): JsonResponse
    {
        $loan->load(['employee', 'payments.payroll']);

        return response()->json([
            'success' => true,
            'data' => $loan,
        ]);
    }

    /**
     * طلب سلفة جديدة
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'employee_id' => 'required|uuid|exists:employees,id',
            'type' => 'required|in:loan,advance',
            'loan_amount' => 'required|numeric|min:100|max:100000',
            'total_installments' => 'required|integer|min:1|max:24',
            'reason' => 'required|string|max:500',
            'start_date' => 'nullable|date|after:today',
        ]);

        $validated['installment_amount'] = EmployeeLoan::calculateInstallment(
            $validated['loan_amount'],
            $validated['total_installments']
        );

        $validated['status'] = EmployeeLoan::STATUS_PENDING;

        $loan = EmployeeLoan::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'تم تقديم طلب السلفة بنجاح',
            'data' => $loan,
        ], 201);
    }

    /**
     * الموافقة على السلفة
     */
    public function approve(EmployeeLoan $loan, Request $request): JsonResponse
    {
        // التحقق من صلاحية الموافقة على السلف
        if (Gate::denies('payroll.approve')) {
            Log::warning('Unauthorized loan approval attempt', [
                'user_id' => $request->user()->id,
                'loan_id' => $loan->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'ليس لديك صلاحية للموافقة على السلف',
            ], 403);
        }

        if ($loan->status !== EmployeeLoan::STATUS_PENDING) {
            return response()->json([
                'success' => false,
                'message' => 'السلفة ليست قيد الانتظار',
            ], 400);
        }

        $loan->approve($request->user()->id);
        $loan->activate();

        Log::info('Loan approved', [
            'loan_id' => $loan->id,
            'approved_by' => $request->user()->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم اعتماد السلفة وتفعيلها',
            'data' => $loan,
        ]);
    }

    /**
     * رفض السلفة
     */
    public function reject(EmployeeLoan $loan, Request $request): JsonResponse
    {
        // التحقق من صلاحية رفض السلف
        if (Gate::denies('payroll.approve')) {
            Log::warning('Unauthorized loan rejection attempt', [
                'user_id' => $request->user()->id,
                'loan_id' => $loan->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'ليس لديك صلاحية لرفض السلف',
            ], 403);
        }

        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        if ($loan->status !== EmployeeLoan::STATUS_PENDING) {
            return response()->json([
                'success' => false,
                'message' => 'السلفة ليست قيد الانتظار',
            ], 400);
        }

        $loan->reject($request->user()->id, $request->reason);

        Log::info('Loan rejected', [
            'loan_id' => $loan->id,
            'rejected_by' => $request->user()->id,
            'reason' => $request->reason,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم رفض السلفة',
            'data' => $loan,
        ]);
    }

    /**
     * سجل الأقساط
     */
    public function payments(EmployeeLoan $loan): JsonResponse
    {
        $payments = $loan->payments()->with('payroll:id,payroll_number,period_year,period_month')->get();

        return response()->json([
            'success' => true,
            'data' => [
                'loan' => $loan,
                'payments' => $payments,
                'summary' => [
                    'total_paid' => $payments->sum('amount'),
                    'remaining' => $loan->remaining_amount,
                    'progress' => $loan->progress_percentage,
                ],
            ],
        ]);
    }

    /**
     * السلف النشطة للموظف
     */
    public function activeLoans(string $employeeId): JsonResponse
    {
        $loans = EmployeeLoan::forEmployee($employeeId)
            ->active()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $loans,
            'total_remaining' => $loans->sum('remaining_amount'),
        ]);
    }
}
