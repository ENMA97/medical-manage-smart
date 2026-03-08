<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EmployeeLoan;
use App\Models\LoanInstallment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LoanController extends Controller
{
    /**
     * GET /api/loans
     */
    public function index(Request $request): JsonResponse
    {
        $loans = EmployeeLoan::with('employee')
            ->when($request->filled('employee_id'), fn($q) => $q->where('employee_id', $request->input('employee_id')))
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->input('status')))
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = $request->input('search');
                $q->where('loan_number', 'like', "%{$search}%");
            })
            ->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $loans,
        ]);
    }

    /**
     * POST /api/loans
     * إنشاء سلفة جديدة
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'loan_amount' => 'required|numeric|min:100',
            'monthly_deduction' => 'required|numeric|min:50',
            'start_date' => 'required|date|after_or_equal:today',
            'reason' => 'nullable|string|max:500',
        ]);

        $loanAmount = $request->input('loan_amount');
        $monthlyDeduction = $request->input('monthly_deduction');
        $totalInstallments = (int) ceil($loanAmount / $monthlyDeduction);

        $loanNumber = 'LOAN-' . now()->format('Y') . '-' . str_pad(
            EmployeeLoan::count() + 1,
            4,
            '0',
            STR_PAD_LEFT
        );

        try {
            $loan = DB::transaction(function () use ($request, $loanNumber, $loanAmount, $monthlyDeduction, $totalInstallments) {
                $loan = EmployeeLoan::create([
                    'employee_id' => $request->input('employee_id'),
                    'loan_number' => $loanNumber,
                    'loan_amount' => $loanAmount,
                    'monthly_deduction' => $monthlyDeduction,
                    'remaining_amount' => $loanAmount,
                    'total_installments' => $totalInstallments,
                    'paid_installments' => 0,
                    'remaining_installments' => $totalInstallments,
                    'start_date' => $request->input('start_date'),
                    'expected_end_date' => now()->parse($request->input('start_date'))->addMonths($totalInstallments),
                    'reason' => $request->input('reason'),
                    'status' => 'pending',
                ]);

                // إنشاء جدول الأقساط
                $remaining = $loanAmount;
                $startDate = now()->parse($request->input('start_date'));

                for ($i = 1; $i <= $totalInstallments; $i++) {
                    $amount = ($i === $totalInstallments) ? $remaining : $monthlyDeduction;
                    $remaining -= $amount;

                    LoanInstallment::create([
                        'loan_id' => $loan->id,
                        'installment_number' => $i,
                        'amount' => $amount,
                        'remaining_after' => max(0, $remaining),
                        'due_date' => $startDate->copy()->addMonths($i - 1),
                        'status' => 'pending',
                    ]);
                }

                return $loan;
            });

            $loan->load(['employee', 'installments']);

            return response()->json([
                'success' => true,
                'message' => 'تم إنشاء السلفة بنجاح',
                'data' => $loan,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إنشاء السلفة',
            ], 500);
        }
    }

    /**
     * GET /api/loans/{id}
     */
    public function show(string $id): JsonResponse
    {
        $loan = EmployeeLoan::with(['employee', 'installments'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $loan,
        ]);
    }

    /**
     * POST /api/loans/{id}/approve
     */
    public function approve(string $id): JsonResponse
    {
        $loan = EmployeeLoan::findOrFail($id);

        if ($loan->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن اعتماد هذه السلفة',
            ], 422);
        }

        $loan->update([
            'status' => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم اعتماد السلفة',
            'data' => $loan,
        ]);
    }

    /**
     * POST /api/loans/{id}/reject
     */
    public function reject(string $id): JsonResponse
    {
        $loan = EmployeeLoan::findOrFail($id);

        if ($loan->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن رفض هذه السلفة',
            ], 422);
        }

        $loan->update(['status' => 'rejected']);

        return response()->json([
            'success' => true,
            'message' => 'تم رفض السلفة',
            'data' => $loan,
        ]);
    }
}
