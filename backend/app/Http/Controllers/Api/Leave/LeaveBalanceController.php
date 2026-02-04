<?php

namespace App\Http\Controllers\Api\Leave;

use App\Http\Controllers\Controller;
use App\Http\Requests\Leave\InitializeBalanceRequest;
use App\Http\Requests\Leave\AdjustBalanceRequest;
use App\Http\Resources\Leave\LeaveBalanceResource;
use App\Http\Resources\Leave\LeaveBalanceCollection;
use App\Http\Resources\Leave\LeaveBalanceAdjustmentCollection;
use App\Models\Leave\LeaveBalance;
use App\Services\Leave\LeaveBalanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Exception;

class LeaveBalanceController extends Controller
{
    protected LeaveBalanceService $service;

    public function __construct(LeaveBalanceService $service)
    {
        $this->service = $service;
    }

    /**
     * عرض أرصدة الموظفين
     */
    public function index(Request $request): LeaveBalanceCollection
    {
        $query = LeaveBalance::with(['employee', 'leaveType']);

        // فلترة حسب الموظف
        if ($request->has('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        // فلترة حسب نوع الإجازة
        if ($request->has('leave_type_id')) {
            $query->where('leave_type_id', $request->leave_type_id);
        }

        // فلترة حسب السنة
        if ($request->has('year')) {
            $query->where('year', $request->year);
        } else {
            $query->where('year', date('Y'));
        }

        $balances = $query->orderBy('created_at', 'desc')->paginate($request->per_page ?? 15);

        return new LeaveBalanceCollection($balances);
    }

    /**
     * عرض تفاصيل رصيد معين
     */
    public function show(LeaveBalance $leaveBalance): LeaveBalanceResource
    {
        $leaveBalance->load(['employee', 'leaveType', 'adjustments']);

        return new LeaveBalanceResource($leaveBalance);
    }

    /**
     * عرض ملخص أرصدة الموظف
     */
    public function employeeSummary(Request $request, string $employeeId): JsonResponse
    {
        $year = $request->year ?? date('Y');
        $summary = $this->service->getEmployeeBalanceSummary($employeeId, $year);

        return response()->json([
            'success' => true,
            'data' => $summary,
        ]);
    }

    /**
     * إنشاء رصيد أولي للموظف
     */
    public function initialize(InitializeBalanceRequest $request): JsonResponse
    {
        try {
            $balance = $this->service->initializeBalance(
                $request->employee_id,
                $request->leave_type_id,
                $request->year ?? date('Y'),
                $request->entitled_days,
                $request->user()->id
            );

            return response()->json([
                'success' => true,
                'message' => 'تم إنشاء الرصيد بنجاح',
                'data' => new LeaveBalanceResource($balance),
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * إنشاء أرصدة لموظف جديد حسب نوع العقد
     */
    public function initializeForEmployee(Request $request): JsonResponse
    {
        $request->validate([
            'employee_id' => 'required|uuid|exists:employees,id',
            'contract_type' => 'required|string',
            'year' => 'nullable|integer|min:2020|max:2100',
        ]);

        try {
            $balances = $this->service->initializeBalancesForEmployee(
                $request->employee_id,
                $request->contract_type,
                $request->user()->id,
                $request->year
            );

            return response()->json([
                'success' => true,
                'message' => 'تم إنشاء الأرصدة بنجاح',
                'data' => LeaveBalanceResource::collection($balances),
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * إضافة/خصم يدوي من الرصيد
     */
    public function adjust(AdjustBalanceRequest $request, LeaveBalance $leaveBalance): JsonResponse
    {
        try {
            $balance = $this->service->addManualAdjustment(
                $leaveBalance->id,
                $request->days,
                $request->reason,
                $request->user()->id
            );

            return response()->json([
                'success' => true,
                'message' => $request->days > 0 ? 'تمت الإضافة بنجاح' : 'تم الخصم بنجاح',
                'data' => new LeaveBalanceResource($balance),
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * تصحيح الرصيد
     */
    public function correct(Request $request, LeaveBalance $leaveBalance): JsonResponse
    {
        $request->validate([
            'new_remaining_days' => 'required|numeric|min:0',
            'reason' => 'required|string|max:500',
        ]);

        try {
            $balance = $this->service->correctBalance(
                $leaveBalance->id,
                $request->new_remaining_days,
                $request->reason,
                $request->user()->id
            );

            return response()->json([
                'success' => true,
                'message' => 'تم تصحيح الرصيد بنجاح',
                'data' => new LeaveBalanceResource($balance),
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * ترحيل الرصيد للسنة الجديدة
     */
    public function carryOver(Request $request): JsonResponse
    {
        $request->validate([
            'employee_id' => 'required|uuid|exists:employees,id',
            'leave_type_id' => 'required|uuid|exists:leave_types,id',
            'from_year' => 'required|integer|min:2020|max:2100',
        ]);

        try {
            $balance = $this->service->carryOverBalance(
                $request->employee_id,
                $request->leave_type_id,
                $request->from_year,
                $request->user()->id
            );

            if (!$balance) {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يوجد رصيد للترحيل أو نوع الإجازة لا يدعم الترحيل',
                ], 400);
            }

            return response()->json([
                'success' => true,
                'message' => 'تم ترحيل الرصيد بنجاح',
                'data' => new LeaveBalanceResource($balance),
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * سجل تعديلات الرصيد
     */
    public function history(LeaveBalance $leaveBalance): LeaveBalanceAdjustmentCollection
    {
        $adjustments = $this->service->getBalanceHistory($leaveBalance->id);

        return new LeaveBalanceAdjustmentCollection($adjustments);
    }
}
