<?php

namespace App\Http\Controllers\Api\Leave;

use App\Http\Controllers\Controller;
use App\Models\Leave\LeaveBalance;
use App\Models\Leave\LeaveRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LeaveReportController extends Controller
{
    /**
     * تقرير أرصدة الموظفين
     */
    public function balancesReport(Request $request): JsonResponse
    {
        $request->validate([
            'year' => 'nullable|integer|min:2020|max:2100',
            'department_id' => 'nullable|uuid',
            'leave_type_id' => 'nullable|uuid|exists:leave_types,id',
        ]);

        $year = $request->year ?? date('Y');

        $query = LeaveBalance::with(['employee', 'leaveType'])
            ->where('year', $year);

        if ($request->has('leave_type_id')) {
            $query->where('leave_type_id', $request->leave_type_id);
        }

        $balances = $query->get();

        // تجميع البيانات
        $summary = [
            'total_employees' => $balances->pluck('employee_id')->unique()->count(),
            'total_entitled_days' => $balances->sum('entitled_days'),
            'total_used_days' => $balances->sum('used_days'),
            'total_remaining_days' => $balances->sum('remaining_days'),
            'by_leave_type' => $balances->groupBy('leave_type_id')->map(function ($group) {
                return [
                    'leave_type' => $group->first()->leaveType?->name_ar,
                    'total_entitled' => $group->sum('entitled_days'),
                    'total_used' => $group->sum('used_days'),
                    'total_remaining' => $group->sum('remaining_days'),
                ];
            })->values(),
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'year' => $year,
                'summary' => $summary,
                'details' => $balances,
            ],
        ]);
    }

    /**
     * تقرير الإجازات المستهلكة
     */
    public function consumptionReport(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'department_id' => 'nullable|uuid',
            'leave_type_id' => 'nullable|uuid|exists:leave_types,id',
        ]);

        $query = LeaveRequest::with(['employee', 'leaveType'])
            ->where('status', 'approved')
            ->where(function ($q) use ($request) {
                $q->whereBetween('start_date', [$request->start_date, $request->end_date])
                    ->orWhereBetween('end_date', [$request->start_date, $request->end_date]);
            });

        if ($request->has('leave_type_id')) {
            $query->where('leave_type_id', $request->leave_type_id);
        }

        $requests = $query->get();

        // تجميع البيانات
        $summary = [
            'total_requests' => $requests->count(),
            'total_days' => $requests->sum('working_days'),
            'by_leave_type' => $requests->groupBy('leave_type_id')->map(function ($group) {
                return [
                    'leave_type' => $group->first()->leaveType?->name_ar,
                    'count' => $group->count(),
                    'total_days' => $group->sum('working_days'),
                ];
            })->values(),
            'by_month' => $requests->groupBy(function ($item) {
                return $item->start_date->format('Y-m');
            })->map(function ($group, $month) {
                return [
                    'month' => $month,
                    'count' => $group->count(),
                    'total_days' => $group->sum('working_days'),
                ];
            })->values(),
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'period' => [
                    'start_date' => $request->start_date,
                    'end_date' => $request->end_date,
                ],
                'summary' => $summary,
            ],
        ]);
    }

    /**
     * تقرير الإجازات حسب القسم
     */
    public function byDepartmentReport(Request $request): JsonResponse
    {
        $request->validate([
            'year' => 'nullable|integer|min:2020|max:2100',
        ]);

        $year = $request->year ?? date('Y');

        // هذا التقرير يحتاج ربط مع جدول الموظفين والأقسام
        // سيتم تفعيله بعد إضافة العلاقات الكاملة
        $data = LeaveRequest::with(['employee', 'leaveType'])
            ->whereYear('start_date', $year)
            ->where('status', 'approved')
            ->get()
            ->groupBy(function ($item) {
                return $item->employee?->department_id ?? 'unknown';
            });

        return response()->json([
            'success' => true,
            'data' => [
                'year' => $year,
                'by_department' => $data->map(function ($group, $deptId) {
                    return [
                        'department_id' => $deptId,
                        'total_requests' => $group->count(),
                        'total_days' => $group->sum('working_days'),
                        'employees_count' => $group->pluck('employee_id')->unique()->count(),
                    ];
                })->values(),
            ],
        ]);
    }

    /**
     * تقرير الغياب
     */
    public function absenceReport(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'department_id' => 'nullable|uuid',
        ]);

        // إجازات معتمدة في الفترة
        $approvedLeaves = LeaveRequest::with(['employee', 'leaveType'])
            ->where('status', 'approved')
            ->where(function ($q) use ($request) {
                $q->whereBetween('start_date', [$request->start_date, $request->end_date])
                    ->orWhereBetween('end_date', [$request->start_date, $request->end_date]);
            })
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'period' => [
                    'start_date' => $request->start_date,
                    'end_date' => $request->end_date,
                ],
                'approved_leaves' => [
                    'count' => $approvedLeaves->count(),
                    'total_days' => $approvedLeaves->sum('working_days'),
                    'details' => $approvedLeaves,
                ],
            ],
        ]);
    }

    /**
     * إحصائيات الإجازات
     */
    public function statistics(Request $request): JsonResponse
    {
        $request->validate([
            'year' => 'nullable|integer|min:2020|max:2100',
        ]);

        $year = $request->year ?? date('Y');

        // إحصائيات الطلبات
        $requestStats = LeaveRequest::whereYear('created_at', $year)
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status');

        // إحصائيات الأرصدة
        $balanceStats = LeaveBalance::where('year', $year)
            ->select(
                DB::raw('SUM(entitled_days) as total_entitled'),
                DB::raw('SUM(used_days) as total_used'),
                DB::raw('SUM(remaining_days) as total_remaining'),
                DB::raw('COUNT(DISTINCT employee_id) as employees_count')
            )
            ->first();

        // أكثر أنواع الإجازات استخداماً
        $topLeaveTypes = LeaveRequest::whereYear('start_date', $year)
            ->where('status', 'approved')
            ->select('leave_type_id', DB::raw('count(*) as count'), DB::raw('SUM(working_days) as total_days'))
            ->groupBy('leave_type_id')
            ->orderByDesc('count')
            ->limit(5)
            ->with('leaveType:id,name_ar,name_en')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'year' => $year,
                'requests' => [
                    'total' => array_sum($requestStats->toArray()),
                    'by_status' => $requestStats,
                ],
                'balances' => $balanceStats,
                'top_leave_types' => $topLeaveTypes,
            ],
        ]);
    }
}
