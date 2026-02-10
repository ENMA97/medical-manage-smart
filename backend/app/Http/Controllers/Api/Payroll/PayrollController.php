<?php

namespace App\Http\Controllers\Api\Payroll;

use App\Http\Controllers\Controller;
use App\Models\Payroll\Payroll;
use App\Services\Payroll\PayrollCalculationService;
use App\Services\Payroll\WPSService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Gate;
use Exception;

class PayrollController extends Controller
{
    public function __construct(
        protected PayrollCalculationService $calculationService,
        protected WPSService $wpsService
    ) {}

    /**
     * عرض مسيرات الرواتب
     */
    public function index(Request $request): JsonResponse
    {
        $query = Payroll::with(['employee:id,name_ar,name_en,employee_number']);

        if ($request->has('year') && $request->has('month')) {
            $query->forPeriod($request->year, $request->month);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('employee_id')) {
            $query->forEmployee($request->employee_id);
        }

        $payrolls = $query->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 20);

        return response()->json([
            'success' => true,
            'data' => $payrolls,
        ]);
    }

    /**
     * عرض تفاصيل مسير راتب
     */
    public function show(Payroll $payroll): JsonResponse
    {
        $payroll->load(['employee', 'items']);

        return response()->json([
            'success' => true,
            'data' => $payroll,
        ]);
    }

    /**
     * توليد مسيرات رواتب شهرية
     */
    public function generate(Request $request): JsonResponse
    {
        $request->validate([
            'year' => 'required|integer|min:2020|max:2100',
            'month' => 'required|integer|min:1|max:12',
        ]);

        try {
            $result = $this->calculationService->generateMonthlyPayroll(
                $request->year,
                $request->month,
                $request->user()->id
            );

            return response()->json([
                'success' => true,
                'message' => "تم توليد {$result['success']} مسير راتب",
                'data' => $result,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * إعادة حساب مسير راتب
     */
    public function recalculate(Payroll $payroll, Request $request): JsonResponse
    {
        try {
            $payroll = $this->calculationService->recalculatePayroll(
                $payroll,
                $request->user()->id
            );

            return response()->json([
                'success' => true,
                'message' => 'تم إعادة الحساب بنجاح',
                'data' => $payroll,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * اعتماد مسير راتب
     */
    public function approve(Payroll $payroll, Request $request): JsonResponse
    {
        if ($payroll->status !== Payroll::STATUS_CALCULATED &&
            $payroll->status !== Payroll::STATUS_REVIEWED) {
            return response()->json([
                'success' => false,
                'message' => 'المسير غير جاهز للاعتماد',
            ], 400);
        }

        $payroll->approve($request->user()->id);

        return response()->json([
            'success' => true,
            'message' => 'تم اعتماد المسير بنجاح',
            'data' => $payroll,
        ]);
    }

    /**
     * اعتماد مجموعة مسيرات
     */
    public function bulkApprove(Request $request): JsonResponse
    {
        // التحقق من الصلاحيات
        if (Gate::denies('approve-payroll')) {
            return response()->json([
                'success' => false,
                'message' => 'ليس لديك صلاحية اعتماد مسيرات الرواتب',
            ], 403);
        }

        $request->validate([
            'payroll_ids' => 'required|array|min:1',
            'payroll_ids.*' => 'uuid|exists:payrolls,id',
        ]);

        $approved = 0;
        $errors = [];
        $userId = $request->user()->id;

        // استخدام transaction لضمان اتساق البيانات
        DB::transaction(function () use ($request, &$approved, &$errors, $userId) {
            // قفل السجلات المحددة لمنع race condition
            $payrolls = Payroll::whereIn('id', $request->payroll_ids)
                ->lockForUpdate()
                ->get();

            foreach ($payrolls as $payroll) {
                if (in_array($payroll->status, [Payroll::STATUS_CALCULATED, Payroll::STATUS_REVIEWED])) {
                    $payroll->approve($userId);
                    $approved++;

                    Log::info('Payroll approved', [
                        'payroll_id' => $payroll->id,
                        'payroll_number' => $payroll->payroll_number,
                        'approved_by' => $userId,
                    ]);
                } else {
                    $errors[] = [
                        'id' => $payroll->id,
                        'payroll_number' => $payroll->payroll_number,
                        'reason' => "الحالة غير صالحة للاعتماد: {$payroll->status}",
                    ];
                }
            }
        });

        return response()->json([
            'success' => true,
            'message' => "تم اعتماد {$approved} مسير",
            'approved' => $approved,
            'errors' => $errors,
        ]);
    }

    /**
     * تسجيل الدفع
     */
    public function markPaid(Payroll $payroll, Request $request): JsonResponse
    {
        // التحقق من الصلاحيات - يحتاج صلاحية دفع الرواتب
        if (Gate::denies('pay-payroll')) {
            return response()->json([
                'success' => false,
                'message' => 'ليس لديك صلاحية تسجيل دفع الرواتب',
            ], 403);
        }

        if ($payroll->status !== Payroll::STATUS_APPROVED) {
            return response()->json([
                'success' => false,
                'message' => 'المسير غير معتمد',
            ], 400);
        }

        try {
            DB::transaction(function () use ($payroll, $request) {
                $userId = $request->user()->id;

                $payroll->markAsPaid($userId);

                // تسجيل أقساط السلف
                $loanItems = $payroll->items()
                    ->whereIn('code', ['LOAN', 'ADVANCE'])
                    ->whereNotNull('reference_id')
                    ->get();

                foreach ($loanItems as $item) {
                    $loan = \App\Models\Payroll\EmployeeLoan::find($item->reference_id);
                    if ($loan && $loan->status === \App\Models\Payroll\EmployeeLoan::STATUS_ACTIVE) {
                        $loan->recordPayment($item->amount, $payroll->id);
                    }
                }

                Log::info('Payroll marked as paid', [
                    'payroll_id' => $payroll->id,
                    'payroll_number' => $payroll->payroll_number,
                    'employee_id' => $payroll->employee_id,
                    'net_salary' => $payroll->net_salary,
                    'paid_by' => $userId,
                ]);
            });

            return response()->json([
                'success' => true,
                'message' => 'تم تسجيل الدفع بنجاح',
                'data' => $payroll->fresh(),
            ]);
        } catch (Exception $e) {
            Log::error('Failed to mark payroll as paid', [
                'payroll_id' => $payroll->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'فشل في تسجيل الدفع: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * توليد ملف WPS
     */
    public function generateWPS(Request $request): JsonResponse
    {
        $request->validate([
            'payroll_ids' => 'required|array|min:1',
            'payroll_ids.*' => 'uuid|exists:payrolls,id',
        ]);

        try {
            $result = $this->wpsService->generateWPSFile(
                $request->payroll_ids,
                $request->user()->id
            );

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => 'بعض البيانات غير مكتملة',
                    'errors' => $result['errors'],
                ], 400);
            }

            return response()->json([
                'success' => true,
                'message' => 'تم توليد ملف WPS بنجاح',
                'data' => $result,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * ملخص WPS لفترة معينة
     */
    public function wpsSummary(Request $request): JsonResponse
    {
        $request->validate([
            'year' => 'required|integer',
            'month' => 'required|integer|min:1|max:12',
        ]);

        $summary = $this->wpsService->getWPSSummary($request->year, $request->month);

        return response()->json([
            'success' => true,
            'data' => $summary,
        ]);
    }

    /**
     * ملخص الفترة
     */
    public function periodSummary(Request $request): JsonResponse
    {
        $request->validate([
            'year' => 'required|integer',
            'month' => 'required|integer|min:1|max:12',
        ]);

        $payrolls = Payroll::forPeriod($request->year, $request->month)->get();

        return response()->json([
            'success' => true,
            'data' => [
                'period' => "{$request->year}-" . str_pad($request->month, 2, '0', STR_PAD_LEFT),
                'total_employees' => $payrolls->count(),
                'total_basic' => $payrolls->sum('basic_salary'),
                'total_allowances' => $payrolls->sum('total_allowances'),
                'total_earnings' => $payrolls->sum('total_earnings'),
                'total_deductions' => $payrolls->sum('total_deductions'),
                'total_gosi_employee' => $payrolls->sum('gosi_employee'),
                'total_gosi_employer' => $payrolls->sum('gosi_employer'),
                'total_net' => $payrolls->sum('net_salary'),
                'by_status' => $payrolls->groupBy('status')->map->count(),
            ],
        ]);
    }

    /**
     * قسيمة راتب للطباعة
     */
    public function payslip(Payroll $payroll): JsonResponse
    {
        $payroll->load(['employee.department', 'employee.position', 'items']);

        return response()->json([
            'success' => true,
            'data' => [
                'payroll' => $payroll,
                'company' => [
                    'name' => config('app.name'),
                    'logo' => config('app.logo'),
                ],
                'generated_at' => now()->toIso8601String(),
            ],
        ]);
    }
}
