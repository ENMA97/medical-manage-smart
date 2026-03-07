<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\Department;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\Resignation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * GET /api/dashboard/summary
     * ملخص الإحصائيات الرئيسية
     */
    public function summary(): JsonResponse
    {
        $totalEmployees = Employee::count();
        $activeEmployees = Employee::where('status', 'active')->count();
        $onLeave = LeaveRequest::where('status', 'approved')
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->count();
        $departmentsCount = Department::where('is_active', true)->count();
        $pendingLeaveRequests = LeaveRequest::where('status', 'pending')->count();
        $pendingResignations = Resignation::where('status', 'pending')->count();

        return response()->json([
            'success' => true,
            'message' => 'تم جلب ملخص لوحة التحكم بنجاح',
            'data' => [
                'total_employees' => $totalEmployees,
                'active_employees' => $activeEmployees,
                'on_leave' => $onLeave,
                'departments_count' => $departmentsCount,
                'pending_leave_requests' => $pendingLeaveRequests,
                'pending_resignations' => $pendingResignations,
            ],
        ]);
    }

    /**
     * GET /api/dashboard/employee-stats
     * إحصائيات الموظفين (حسب القسم، نوع التوظيف، الحالة)
     */
    public function employeeStats(): JsonResponse
    {
        $byDepartment = Department::withCount('employees')
            ->where('is_active', true)
            ->orderBy('employees_count', 'desc')
            ->get()
            ->map(fn($dept) => [
                'id' => $dept->id,
                'name' => $dept->name,
                'name_ar' => $dept->name_ar,
                'employees_count' => $dept->employees_count,
            ]);

        $byEmploymentType = Employee::selectRaw('employment_type, count(*) as count')
            ->whereNotNull('employment_type')
            ->groupBy('employment_type')
            ->pluck('count', 'employment_type');

        $byStatus = Employee::selectRaw('status, count(*) as count')
            ->whereNotNull('status')
            ->groupBy('status')
            ->pluck('count', 'status');

        $byGender = Employee::selectRaw('gender, count(*) as count')
            ->whereNotNull('gender')
            ->groupBy('gender')
            ->pluck('count', 'gender');

        return response()->json([
            'success' => true,
            'message' => 'تم جلب إحصائيات الموظفين بنجاح',
            'data' => [
                'by_department' => $byDepartment,
                'by_employment_type' => $byEmploymentType,
                'by_status' => $byStatus,
                'by_gender' => $byGender,
            ],
        ]);
    }

    /**
     * GET /api/dashboard/leave-stats
     * ملخص الإجازات
     */
    public function leaveStats(): JsonResponse
    {
        $totalRequests = LeaveRequest::count();
        $pending = LeaveRequest::where('status', 'pending')->count();
        $approved = LeaveRequest::where('status', 'approved')->count();
        $rejected = LeaveRequest::where('status', 'rejected')->count();
        $cancelled = LeaveRequest::where('status', 'cancelled')->count();

        $currentlyOnLeave = LeaveRequest::where('status', 'approved')
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->with('employee:id,first_name,last_name,first_name_ar,last_name_ar,employee_number')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'تم جلب إحصائيات الإجازات بنجاح',
            'data' => [
                'total_requests' => $totalRequests,
                'pending' => $pending,
                'approved' => $approved,
                'rejected' => $rejected,
                'cancelled' => $cancelled,
                'currently_on_leave' => $currentlyOnLeave,
            ],
        ]);
    }

    /**
     * GET /api/dashboard/alerts
     * التنبيهات: عقود منتهية الصلاحية، موافقات معلقة
     */
    public function alerts(): JsonResponse
    {
        // عقود تنتهي خلال 30 يوم
        $expiringContracts = Contract::with('employee:id,first_name,last_name,first_name_ar,last_name_ar,employee_number')
            ->where('status', 'active')
            ->whereNotNull('end_date')
            ->where('end_date', '<=', now()->addDays(30))
            ->where('end_date', '>=', now())
            ->orderBy('end_date')
            ->get()
            ->map(fn($contract) => [
                'id' => $contract->id,
                'contract_number' => $contract->contract_number,
                'employee' => $contract->employee,
                'end_date' => $contract->end_date->format('Y-m-d'),
                'days_remaining' => now()->diffInDays($contract->end_date),
            ]);

        // طلبات إجازة معلقة
        $pendingLeaveRequests = LeaveRequest::with('employee:id,first_name,last_name,first_name_ar,last_name_ar,employee_number')
            ->where('status', 'pending')
            ->orderBy('created_at')
            ->limit(10)
            ->get();

        // استقالات معلقة
        $pendingResignations = Resignation::with('employee:id,first_name,last_name,first_name_ar,last_name_ar,employee_number')
            ->where('status', 'pending')
            ->orderBy('created_at')
            ->limit(10)
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'تم جلب التنبيهات بنجاح',
            'data' => [
                'expiring_contracts' => $expiringContracts,
                'pending_leave_requests' => $pendingLeaveRequests,
                'pending_resignations' => $pendingResignations,
            ],
        ]);
    }
}
