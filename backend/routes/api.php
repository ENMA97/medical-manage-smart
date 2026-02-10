<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group.
|
*/

// =============================================================================
// Leave Management Module Routes - مسارات وحدة الإجازات
// =============================================================================

Route::prefix('leaves')->middleware(['auth:sanctum'])->group(function () {

    // -------------------------------------------------------------------------
    // Leave Types - أنواع الإجازات
    // -------------------------------------------------------------------------
    Route::prefix('types')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\Leave\LeaveTypeController::class, 'index'])
            ->name('leave-types.index');
        Route::get('/active', [\App\Http\Controllers\Api\Leave\LeaveTypeController::class, 'active'])
            ->name('leave-types.active');
        Route::post('/', [\App\Http\Controllers\Api\Leave\LeaveTypeController::class, 'store'])
            ->name('leave-types.store');
        Route::get('/{leaveType}', [\App\Http\Controllers\Api\Leave\LeaveTypeController::class, 'show'])
            ->name('leave-types.show');
        Route::put('/{leaveType}', [\App\Http\Controllers\Api\Leave\LeaveTypeController::class, 'update'])
            ->name('leave-types.update');
        Route::delete('/{leaveType}', [\App\Http\Controllers\Api\Leave\LeaveTypeController::class, 'destroy'])
            ->name('leave-types.destroy');
    });

    // -------------------------------------------------------------------------
    // Leave Balances - أرصدة الإجازات
    // -------------------------------------------------------------------------
    Route::prefix('balances')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\Leave\LeaveBalanceController::class, 'index'])
            ->name('leave-balances.index');
        Route::get('/{leaveBalance}', [\App\Http\Controllers\Api\Leave\LeaveBalanceController::class, 'show'])
            ->name('leave-balances.show');
        Route::get('/employee/{employeeId}/summary', [\App\Http\Controllers\Api\Leave\LeaveBalanceController::class, 'employeeSummary'])
            ->name('leave-balances.employee-summary');
        Route::post('/initialize', [\App\Http\Controllers\Api\Leave\LeaveBalanceController::class, 'initialize'])
            ->name('leave-balances.initialize');
        Route::post('/initialize-for-employee', [\App\Http\Controllers\Api\Leave\LeaveBalanceController::class, 'initializeForEmployee'])
            ->name('leave-balances.initialize-for-employee');
        Route::post('/{leaveBalance}/adjust', [\App\Http\Controllers\Api\Leave\LeaveBalanceController::class, 'adjust'])
            ->name('leave-balances.adjust');
        Route::post('/{leaveBalance}/correct', [\App\Http\Controllers\Api\Leave\LeaveBalanceController::class, 'correct'])
            ->name('leave-balances.correct');
        Route::post('/carry-over', [\App\Http\Controllers\Api\Leave\LeaveBalanceController::class, 'carryOver'])
            ->name('leave-balances.carry-over');
        Route::get('/{leaveBalance}/history', [\App\Http\Controllers\Api\Leave\LeaveBalanceController::class, 'history'])
            ->name('leave-balances.history');
    });

    // -------------------------------------------------------------------------
    // Leave Requests - طلبات الإجازة (المرحلة الأولى)
    // -------------------------------------------------------------------------
    Route::prefix('requests')->group(function () {
        // CRUD Operations
        Route::get('/', [\App\Http\Controllers\Api\Leave\LeaveRequestController::class, 'index'])
            ->name('leave-requests.index');
        Route::post('/', [\App\Http\Controllers\Api\Leave\LeaveRequestController::class, 'store'])
            ->name('leave-requests.store');
        Route::get('/pending-for-me', [\App\Http\Controllers\Api\Leave\LeaveRequestController::class, 'pendingForMe'])
            ->name('leave-requests.pending-for-me');
        Route::get('/{leaveRequest}', [\App\Http\Controllers\Api\Leave\LeaveRequestController::class, 'show'])
            ->name('leave-requests.show');

        // Workflow Actions - إجراءات سير العمل
        Route::post('/{leaveRequest}/submit', [\App\Http\Controllers\Api\Leave\LeaveRequestController::class, 'submit'])
            ->name('leave-requests.submit');
        Route::post('/{leaveRequest}/cancel', [\App\Http\Controllers\Api\Leave\LeaveRequestController::class, 'cancel'])
            ->name('leave-requests.cancel');

        // Phase 1 Approvals - موافقات المرحلة الأولى
        Route::post('/{leaveRequest}/supervisor-recommendation', [\App\Http\Controllers\Api\Leave\LeaveRequestController::class, 'processSupervisorRecommendation'])
            ->name('leave-requests.supervisor-recommendation');
        Route::post('/{leaveRequest}/admin-manager-approval', [\App\Http\Controllers\Api\Leave\LeaveRequestController::class, 'processAdminManagerApproval'])
            ->name('leave-requests.admin-manager-approval');
        Route::post('/{leaveRequest}/hr-endorsement', [\App\Http\Controllers\Api\Leave\LeaveRequestController::class, 'processHrEndorsement'])
            ->name('leave-requests.hr-endorsement');
        Route::post('/{leaveRequest}/delegate-confirmation', [\App\Http\Controllers\Api\Leave\LeaveRequestController::class, 'processDelegateConfirmation'])
            ->name('leave-requests.delegate-confirmation');
    });

    // -------------------------------------------------------------------------
    // Leave Decisions - قرارات الإجازة (المرحلة الثانية)
    // -------------------------------------------------------------------------
    Route::prefix('decisions')->group(function () {
        // CRUD Operations
        Route::get('/', [\App\Http\Controllers\Api\Leave\LeaveDecisionController::class, 'index'])
            ->name('leave-decisions.index');
        Route::post('/', [\App\Http\Controllers\Api\Leave\LeaveDecisionController::class, 'store'])
            ->name('leave-decisions.store');
        Route::get('/pending-for-me', [\App\Http\Controllers\Api\Leave\LeaveDecisionController::class, 'pendingForMe'])
            ->name('leave-decisions.pending-for-me');
        Route::get('/{leaveDecision}', [\App\Http\Controllers\Api\Leave\LeaveDecisionController::class, 'show'])
            ->name('leave-decisions.show');

        // Phase 2 Approvals - موافقات المرحلة الثانية
        // المدير الإداري (للموظفين الإداريين)
        Route::post('/{leaveDecision}/admin-manager', [\App\Http\Controllers\Api\Leave\LeaveDecisionController::class, 'processAdminManager'])
            ->name('leave-decisions.admin-manager');

        // المدير الطبي (للأطباء والكادر الطبي)
        Route::post('/{leaveDecision}/medical-director', [\App\Http\Controllers\Api\Leave\LeaveDecisionController::class, 'processMedicalDirector'])
            ->name('leave-decisions.medical-director');

        // المدير العام (اختياري - حسب التحويل)
        Route::post('/{leaveDecision}/general-manager', [\App\Http\Controllers\Api\Leave\LeaveDecisionController::class, 'processGeneralManager'])
            ->name('leave-decisions.general-manager');
    });

    // -------------------------------------------------------------------------
    // Public Holidays - الإجازات الرسمية
    // -------------------------------------------------------------------------
    Route::prefix('holidays')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\Leave\PublicHolidayController::class, 'index'])
            ->name('public-holidays.index');
        Route::get('/year/{year}', [\App\Http\Controllers\Api\Leave\PublicHolidayController::class, 'byYear'])
            ->name('public-holidays.by-year');
        Route::post('/', [\App\Http\Controllers\Api\Leave\PublicHolidayController::class, 'store'])
            ->name('public-holidays.store');
        Route::get('/{publicHoliday}', [\App\Http\Controllers\Api\Leave\PublicHolidayController::class, 'show'])
            ->name('public-holidays.show');
        Route::put('/{publicHoliday}', [\App\Http\Controllers\Api\Leave\PublicHolidayController::class, 'update'])
            ->name('public-holidays.update');
        Route::delete('/{publicHoliday}', [\App\Http\Controllers\Api\Leave\PublicHolidayController::class, 'destroy'])
            ->name('public-holidays.destroy');
    });

    // -------------------------------------------------------------------------
    // Leave Policies - سياسات الإجازات
    // -------------------------------------------------------------------------
    Route::prefix('policies')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\Leave\LeavePolicyController::class, 'index'])
            ->name('leave-policies.index');
        Route::get('/contract-type/{contractType}', [\App\Http\Controllers\Api\Leave\LeavePolicyController::class, 'byContractType'])
            ->name('leave-policies.by-contract-type');
        Route::post('/', [\App\Http\Controllers\Api\Leave\LeavePolicyController::class, 'store'])
            ->name('leave-policies.store');
        Route::get('/{leavePolicy}', [\App\Http\Controllers\Api\Leave\LeavePolicyController::class, 'show'])
            ->name('leave-policies.show');
        Route::put('/{leavePolicy}', [\App\Http\Controllers\Api\Leave\LeavePolicyController::class, 'update'])
            ->name('leave-policies.update');
        Route::delete('/{leavePolicy}', [\App\Http\Controllers\Api\Leave\LeavePolicyController::class, 'destroy'])
            ->name('leave-policies.destroy');
    });

    // -------------------------------------------------------------------------
    // Department Leave Settings - إعدادات الإجازات للأقسام
    // -------------------------------------------------------------------------
    Route::prefix('department-settings')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\Leave\DepartmentLeaveSettingController::class, 'index'])
            ->name('department-leave-settings.index');
        Route::get('/department/{departmentId}', [\App\Http\Controllers\Api\Leave\DepartmentLeaveSettingController::class, 'byDepartment'])
            ->name('department-leave-settings.by-department');
        Route::post('/', [\App\Http\Controllers\Api\Leave\DepartmentLeaveSettingController::class, 'store'])
            ->name('department-leave-settings.store');
        Route::put('/{departmentLeaveSetting}', [\App\Http\Controllers\Api\Leave\DepartmentLeaveSettingController::class, 'update'])
            ->name('department-leave-settings.update');
        Route::delete('/{departmentLeaveSetting}', [\App\Http\Controllers\Api\Leave\DepartmentLeaveSettingController::class, 'destroy'])
            ->name('department-leave-settings.destroy');
    });

    // -------------------------------------------------------------------------
    // Reports & Statistics - التقارير والإحصائيات
    // -------------------------------------------------------------------------
    Route::prefix('reports')->group(function () {
        // تقرير أرصدة الموظفين
        Route::get('/balances', [\App\Http\Controllers\Api\Leave\LeaveReportController::class, 'balancesReport'])
            ->name('leave-reports.balances');

        // تقرير الإجازات المستهلكة
        Route::get('/consumption', [\App\Http\Controllers\Api\Leave\LeaveReportController::class, 'consumptionReport'])
            ->name('leave-reports.consumption');

        // تقرير الإجازات حسب القسم
        Route::get('/by-department', [\App\Http\Controllers\Api\Leave\LeaveReportController::class, 'byDepartmentReport'])
            ->name('leave-reports.by-department');

        // تقرير الغياب
        Route::get('/absence', [\App\Http\Controllers\Api\Leave\LeaveReportController::class, 'absenceReport'])
            ->name('leave-reports.absence');

        // إحصائيات الإجازات
        Route::get('/statistics', [\App\Http\Controllers\Api\Leave\LeaveReportController::class, 'statistics'])
            ->name('leave-reports.statistics');
    });
});

// =============================================================================
// Payroll Module Routes - مسارات وحدة الرواتب
// =============================================================================

Route::prefix('payroll')->middleware(['auth:sanctum'])->group(function () {

    // -------------------------------------------------------------------------
    // Payrolls - مسيرات الرواتب
    // -------------------------------------------------------------------------
    Route::prefix('payrolls')->group(function () {
        // قائمة المسيرات
        Route::get('/', [\App\Http\Controllers\Api\Payroll\PayrollController::class, 'index'])
            ->name('payrolls.index');

        // ملخص الفترة
        Route::get('/period-summary', [\App\Http\Controllers\Api\Payroll\PayrollController::class, 'periodSummary'])
            ->name('payrolls.period-summary');

        // توليد مسيرات شهرية
        Route::post('/generate', [\App\Http\Controllers\Api\Payroll\PayrollController::class, 'generate'])
            ->name('payrolls.generate');

        // اعتماد مجموعة
        Route::post('/bulk-approve', [\App\Http\Controllers\Api\Payroll\PayrollController::class, 'bulkApprove'])
            ->name('payrolls.bulk-approve');

        // تفاصيل مسير
        Route::get('/{payroll}', [\App\Http\Controllers\Api\Payroll\PayrollController::class, 'show'])
            ->name('payrolls.show');

        // إعادة حساب
        Route::post('/{payroll}/recalculate', [\App\Http\Controllers\Api\Payroll\PayrollController::class, 'recalculate'])
            ->name('payrolls.recalculate');

        // اعتماد المسير
        Route::post('/{payroll}/approve', [\App\Http\Controllers\Api\Payroll\PayrollController::class, 'approve'])
            ->name('payrolls.approve');

        // تسجيل الدفع
        Route::post('/{payroll}/mark-paid', [\App\Http\Controllers\Api\Payroll\PayrollController::class, 'markPaid'])
            ->name('payrolls.mark-paid');

        // قسيمة الراتب
        Route::get('/{payroll}/payslip', [\App\Http\Controllers\Api\Payroll\PayrollController::class, 'payslip'])
            ->name('payrolls.payslip');
    });

    // -------------------------------------------------------------------------
    // WPS - نظام حماية الأجور
    // -------------------------------------------------------------------------
    Route::prefix('wps')->group(function () {
        // ملخص WPS
        Route::get('/summary', [\App\Http\Controllers\Api\Payroll\PayrollController::class, 'wpsSummary'])
            ->name('wps.summary');

        // توليد ملف WPS
        Route::post('/generate', [\App\Http\Controllers\Api\Payroll\PayrollController::class, 'generateWPS'])
            ->name('wps.generate');
    });

    // -------------------------------------------------------------------------
    // Employee Loans - السلف والقروض
    // -------------------------------------------------------------------------
    Route::prefix('loans')->group(function () {
        // قائمة السلف
        Route::get('/', [\App\Http\Controllers\Api\Payroll\LoanController::class, 'index'])
            ->name('loans.index');

        // طلب سلفة جديدة
        Route::post('/', [\App\Http\Controllers\Api\Payroll\LoanController::class, 'store'])
            ->name('loans.store');

        // السلف النشطة للموظف
        Route::get('/employee/{employeeId}/active', [\App\Http\Controllers\Api\Payroll\LoanController::class, 'activeLoans'])
            ->name('loans.employee-active');

        // تفاصيل السلفة
        Route::get('/{loan}', [\App\Http\Controllers\Api\Payroll\LoanController::class, 'show'])
            ->name('loans.show');

        // الموافقة على السلفة
        Route::post('/{loan}/approve', [\App\Http\Controllers\Api\Payroll\LoanController::class, 'approve'])
            ->name('loans.approve');

        // رفض السلفة
        Route::post('/{loan}/reject', [\App\Http\Controllers\Api\Payroll\LoanController::class, 'reject'])
            ->name('loans.reject');

        // سجل الأقساط
        Route::get('/{loan}/payments', [\App\Http\Controllers\Api\Payroll\LoanController::class, 'payments'])
            ->name('loans.payments');
    });

    // -------------------------------------------------------------------------
    // Payroll Settings - إعدادات الرواتب
    // -------------------------------------------------------------------------
    Route::prefix('settings')->group(function () {
        // جميع الإعدادات
        Route::get('/', [\App\Http\Controllers\Api\Payroll\PayrollSettingsController::class, 'index'])
            ->name('payroll-settings.index');

        // تحديث إعداد
        Route::put('/{key}', [\App\Http\Controllers\Api\Payroll\PayrollSettingsController::class, 'update'])
            ->name('payroll-settings.update');

        // إعادة تعيين للافتراضي
        Route::post('/reset-defaults', [\App\Http\Controllers\Api\Payroll\PayrollSettingsController::class, 'resetDefaults'])
            ->name('payroll-settings.reset-defaults');
    });
});

// =============================================================================
// Authentication Routes - مسارات المصادقة
// =============================================================================

Route::prefix('auth')->group(function () {
    // تسجيل الدخول
    Route::post('/login', [\App\Http\Controllers\Api\Auth\AuthController::class, 'login'])
        ->name('auth.login');

    // تسجيل مستخدم جديد (للمشرفين فقط)
    Route::post('/register', [\App\Http\Controllers\Api\Auth\AuthController::class, 'register'])
        ->middleware(['auth:sanctum'])
        ->name('auth.register');

    // مسارات تتطلب مصادقة
    Route::middleware(['auth:sanctum'])->group(function () {
        // الملف الشخصي
        Route::get('/me', [\App\Http\Controllers\Api\Auth\AuthController::class, 'me'])
            ->name('auth.me');

        // تحديث الملف الشخصي
        Route::put('/profile', [\App\Http\Controllers\Api\Auth\AuthController::class, 'updateProfile'])
            ->name('auth.update-profile');

        // تغيير كلمة المرور
        Route::post('/change-password', [\App\Http\Controllers\Api\Auth\AuthController::class, 'changePassword'])
            ->name('auth.change-password');

        // تسجيل الخروج
        Route::post('/logout', [\App\Http\Controllers\Api\Auth\AuthController::class, 'logout'])
            ->name('auth.logout');

        // تسجيل الخروج من جميع الأجهزة
        Route::post('/logout-all', [\App\Http\Controllers\Api\Auth\AuthController::class, 'logoutAll'])
            ->name('auth.logout-all');

        // تحديث الـ Token
        Route::post('/refresh', [\App\Http\Controllers\Api\Auth\AuthController::class, 'refresh'])
            ->name('auth.refresh');
    });

    // استعادة كلمة المرور
    Route::post('/forgot-password', [\App\Http\Controllers\Api\Auth\PasswordResetController::class, 'sendResetLink'])
        ->name('auth.forgot-password');

    Route::post('/reset-password', [\App\Http\Controllers\Api\Auth\PasswordResetController::class, 'reset'])
        ->name('auth.reset-password');
});

// =============================================================================
// HR Module Routes - مسارات وحدة الموارد البشرية
// =============================================================================

Route::prefix('hr')->middleware(['auth:sanctum'])->group(function () {

    // -------------------------------------------------------------------------
    // Departments - الأقسام
    // -------------------------------------------------------------------------
    Route::prefix('departments')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\HR\DepartmentController::class, 'index'])
            ->name('departments.index');
        Route::get('/tree', [\App\Http\Controllers\Api\HR\DepartmentController::class, 'tree'])
            ->name('departments.tree');
        Route::get('/active', [\App\Http\Controllers\Api\HR\DepartmentController::class, 'active'])
            ->name('departments.active');
        Route::post('/', [\App\Http\Controllers\Api\HR\DepartmentController::class, 'store'])
            ->name('departments.store');
        Route::get('/{department}', [\App\Http\Controllers\Api\HR\DepartmentController::class, 'show'])
            ->name('departments.show');
        Route::put('/{department}', [\App\Http\Controllers\Api\HR\DepartmentController::class, 'update'])
            ->name('departments.update');
        Route::delete('/{department}', [\App\Http\Controllers\Api\HR\DepartmentController::class, 'destroy'])
            ->name('departments.destroy');
        Route::get('/{department}/employees', [\App\Http\Controllers\Api\HR\DepartmentController::class, 'employees'])
            ->name('departments.employees');
    });

    // -------------------------------------------------------------------------
    // Positions - المناصب الوظيفية
    // -------------------------------------------------------------------------
    Route::prefix('positions')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\HR\PositionController::class, 'index'])
            ->name('positions.index');
        Route::get('/active', [\App\Http\Controllers\Api\HR\PositionController::class, 'active'])
            ->name('positions.active');
        Route::get('/by-department/{departmentId}', [\App\Http\Controllers\Api\HR\PositionController::class, 'byDepartment'])
            ->name('positions.by-department');
        Route::post('/', [\App\Http\Controllers\Api\HR\PositionController::class, 'store'])
            ->name('positions.store');
        Route::get('/{position}', [\App\Http\Controllers\Api\HR\PositionController::class, 'show'])
            ->name('positions.show');
        Route::put('/{position}', [\App\Http\Controllers\Api\HR\PositionController::class, 'update'])
            ->name('positions.update');
        Route::delete('/{position}', [\App\Http\Controllers\Api\HR\PositionController::class, 'destroy'])
            ->name('positions.destroy');
    });

    // -------------------------------------------------------------------------
    // Employees - الموظفين
    // -------------------------------------------------------------------------
    Route::prefix('employees')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\HR\EmployeeController::class, 'index'])
            ->name('employees.index');
        Route::get('/active', [\App\Http\Controllers\Api\HR\EmployeeController::class, 'active'])
            ->name('employees.active');
        Route::get('/search', [\App\Http\Controllers\Api\HR\EmployeeController::class, 'search'])
            ->name('employees.search');
        Route::post('/', [\App\Http\Controllers\Api\HR\EmployeeController::class, 'store'])
            ->name('employees.store');
        Route::get('/{employee}', [\App\Http\Controllers\Api\HR\EmployeeController::class, 'show'])
            ->name('employees.show');
        Route::put('/{employee}', [\App\Http\Controllers\Api\HR\EmployeeController::class, 'update'])
            ->name('employees.update');
        Route::delete('/{employee}', [\App\Http\Controllers\Api\HR\EmployeeController::class, 'destroy'])
            ->name('employees.destroy');

        // Employee Details
        Route::get('/{employee}/contracts', [\App\Http\Controllers\Api\HR\EmployeeController::class, 'contracts'])
            ->name('employees.contracts');
        Route::get('/{employee}/custodies', [\App\Http\Controllers\Api\HR\EmployeeController::class, 'custodies'])
            ->name('employees.custodies');
        Route::get('/{employee}/documents', [\App\Http\Controllers\Api\HR\EmployeeController::class, 'documents'])
            ->name('employees.documents');
    });

    // -------------------------------------------------------------------------
    // Contracts - العقود
    // -------------------------------------------------------------------------
    Route::prefix('contracts')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\HR\ContractController::class, 'index'])
            ->name('contracts.index');
        Route::get('/expiring', [\App\Http\Controllers\Api\HR\ContractController::class, 'expiring'])
            ->name('contracts.expiring');
        Route::post('/', [\App\Http\Controllers\Api\HR\ContractController::class, 'store'])
            ->name('contracts.store');
        Route::get('/{contract}', [\App\Http\Controllers\Api\HR\ContractController::class, 'show'])
            ->name('contracts.show');
        Route::put('/{contract}', [\App\Http\Controllers\Api\HR\ContractController::class, 'update'])
            ->name('contracts.update');
        Route::post('/{contract}/renew', [\App\Http\Controllers\Api\HR\ContractController::class, 'renew'])
            ->name('contracts.renew');
        Route::post('/{contract}/terminate', [\App\Http\Controllers\Api\HR\ContractController::class, 'terminate'])
            ->name('contracts.terminate');
    });

    // -------------------------------------------------------------------------
    // Custodies - العهد
    // -------------------------------------------------------------------------
    Route::prefix('custodies')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\HR\CustodyController::class, 'index'])
            ->name('custodies.index');
        Route::get('/pending-return', [\App\Http\Controllers\Api\HR\CustodyController::class, 'pendingReturn'])
            ->name('custodies.pending-return');
        Route::post('/', [\App\Http\Controllers\Api\HR\CustodyController::class, 'store'])
            ->name('custodies.store');
        Route::get('/{custody}', [\App\Http\Controllers\Api\HR\CustodyController::class, 'show'])
            ->name('custodies.show');
        Route::put('/{custody}', [\App\Http\Controllers\Api\HR\CustodyController::class, 'update'])
            ->name('custodies.update');
        Route::post('/{custody}/return', [\App\Http\Controllers\Api\HR\CustodyController::class, 'markReturned'])
            ->name('custodies.return');
    });

    // -------------------------------------------------------------------------
    // Clearance Requests - طلبات إخلاء الطرف
    // -------------------------------------------------------------------------
    Route::prefix('clearance')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\HR\ClearanceController::class, 'index'])
            ->name('clearance.index');
        Route::get('/pending', [\App\Http\Controllers\Api\HR\ClearanceController::class, 'pending'])
            ->name('clearance.pending');
        Route::post('/', [\App\Http\Controllers\Api\HR\ClearanceController::class, 'store'])
            ->name('clearance.store');
        Route::get('/{clearance}', [\App\Http\Controllers\Api\HR\ClearanceController::class, 'show'])
            ->name('clearance.show');

        // Workflow Approvals
        Route::post('/{clearance}/finance-approve', [\App\Http\Controllers\Api\HR\ClearanceController::class, 'financeApprove'])
            ->name('clearance.finance-approve');
        Route::post('/{clearance}/hr-approve', [\App\Http\Controllers\Api\HR\ClearanceController::class, 'hrApprove'])
            ->name('clearance.hr-approve');
        Route::post('/{clearance}/it-approve', [\App\Http\Controllers\Api\HR\ClearanceController::class, 'itApprove'])
            ->name('clearance.it-approve');
        Route::post('/{clearance}/custody-clear', [\App\Http\Controllers\Api\HR\ClearanceController::class, 'custodyClear'])
            ->name('clearance.custody-clear');
        Route::post('/{clearance}/complete', [\App\Http\Controllers\Api\HR\ClearanceController::class, 'complete'])
            ->name('clearance.complete');
    });

    // -------------------------------------------------------------------------
    // HR Reports - تقارير الموارد البشرية
    // -------------------------------------------------------------------------
    Route::prefix('reports')->group(function () {
        Route::get('/headcount', [\App\Http\Controllers\Api\HR\HRReportController::class, 'headcount'])
            ->name('hr-reports.headcount');
        Route::get('/turnover', [\App\Http\Controllers\Api\HR\HRReportController::class, 'turnover'])
            ->name('hr-reports.turnover');
        Route::get('/contracts-expiring', [\App\Http\Controllers\Api\HR\HRReportController::class, 'contractsExpiring'])
            ->name('hr-reports.contracts-expiring');
        Route::get('/by-department', [\App\Http\Controllers\Api\HR\HRReportController::class, 'byDepartment'])
            ->name('hr-reports.by-department');
    });
});

// =============================================================================
// Roster Module Routes - مسارات وحدة الجدولة
// =============================================================================

Route::prefix('roster')->middleware(['auth:sanctum'])->group(function () {

    // -------------------------------------------------------------------------
    // Shift Patterns - أنماط الورديات
    // -------------------------------------------------------------------------
    Route::prefix('shift-patterns')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\Roster\ShiftPatternController::class, 'index'])
            ->name('shift-patterns.index');
        Route::get('/active', [\App\Http\Controllers\Api\Roster\ShiftPatternController::class, 'active'])
            ->name('shift-patterns.active');
        Route::post('/', [\App\Http\Controllers\Api\Roster\ShiftPatternController::class, 'store'])
            ->name('shift-patterns.store');
        Route::get('/{shiftPattern}', [\App\Http\Controllers\Api\Roster\ShiftPatternController::class, 'show'])
            ->name('shift-patterns.show');
        Route::put('/{shiftPattern}', [\App\Http\Controllers\Api\Roster\ShiftPatternController::class, 'update'])
            ->name('shift-patterns.update');
        Route::delete('/{shiftPattern}', [\App\Http\Controllers\Api\Roster\ShiftPatternController::class, 'destroy'])
            ->name('shift-patterns.destroy');
    });

    // -------------------------------------------------------------------------
    // Rosters - الجداول الشهرية
    // -------------------------------------------------------------------------
    Route::prefix('rosters')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\Roster\RosterController::class, 'index'])
            ->name('rosters.index');
        Route::get('/current', [\App\Http\Controllers\Api\Roster\RosterController::class, 'current'])
            ->name('rosters.current');
        Route::post('/', [\App\Http\Controllers\Api\Roster\RosterController::class, 'store'])
            ->name('rosters.store');
        Route::get('/{roster}', [\App\Http\Controllers\Api\Roster\RosterController::class, 'show'])
            ->name('rosters.show');
        Route::put('/{roster}', [\App\Http\Controllers\Api\Roster\RosterController::class, 'update'])
            ->name('rosters.update');
        Route::post('/{roster}/publish', [\App\Http\Controllers\Api\Roster\RosterController::class, 'publish'])
            ->name('rosters.publish');
        Route::post('/{roster}/lock', [\App\Http\Controllers\Api\Roster\RosterController::class, 'lock'])
            ->name('rosters.lock');
        Route::post('/{roster}/validate', [\App\Http\Controllers\Api\Roster\RosterController::class, 'validateRoster'])
            ->name('rosters.validate');
        Route::get('/{roster}/assignments', [\App\Http\Controllers\Api\Roster\RosterController::class, 'assignments'])
            ->name('rosters.assignments');
    });

    // -------------------------------------------------------------------------
    // Roster Assignments - تعيينات الورديات
    // -------------------------------------------------------------------------
    Route::prefix('assignments')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\Roster\RosterAssignmentController::class, 'index'])
            ->name('roster-assignments.index');
        Route::get('/employee/{employeeId}', [\App\Http\Controllers\Api\Roster\RosterAssignmentController::class, 'byEmployee'])
            ->name('roster-assignments.by-employee');
        Route::get('/date/{date}', [\App\Http\Controllers\Api\Roster\RosterAssignmentController::class, 'byDate'])
            ->name('roster-assignments.by-date');
        Route::post('/', [\App\Http\Controllers\Api\Roster\RosterAssignmentController::class, 'store'])
            ->name('roster-assignments.store');
        Route::post('/bulk', [\App\Http\Controllers\Api\Roster\RosterAssignmentController::class, 'bulkStore'])
            ->name('roster-assignments.bulk-store');
        Route::get('/{assignment}', [\App\Http\Controllers\Api\Roster\RosterAssignmentController::class, 'show'])
            ->name('roster-assignments.show');
        Route::put('/{assignment}', [\App\Http\Controllers\Api\Roster\RosterAssignmentController::class, 'update'])
            ->name('roster-assignments.update');
        Route::delete('/{assignment}', [\App\Http\Controllers\Api\Roster\RosterAssignmentController::class, 'destroy'])
            ->name('roster-assignments.destroy');
    });

    // -------------------------------------------------------------------------
    // Shift Swap Requests - طلبات تبديل الورديات
    // -------------------------------------------------------------------------
    Route::prefix('swap-requests')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\Roster\ShiftSwapController::class, 'index'])
            ->name('shift-swaps.index');
        Route::get('/pending-for-me', [\App\Http\Controllers\Api\Roster\ShiftSwapController::class, 'pendingForMe'])
            ->name('shift-swaps.pending-for-me');
        Route::post('/', [\App\Http\Controllers\Api\Roster\ShiftSwapController::class, 'store'])
            ->name('shift-swaps.store');
        Route::get('/{swapRequest}', [\App\Http\Controllers\Api\Roster\ShiftSwapController::class, 'show'])
            ->name('shift-swaps.show');
        Route::post('/{swapRequest}/target-respond', [\App\Http\Controllers\Api\Roster\ShiftSwapController::class, 'targetRespond'])
            ->name('shift-swaps.target-respond');
        Route::post('/{swapRequest}/supervisor-approve', [\App\Http\Controllers\Api\Roster\ShiftSwapController::class, 'supervisorApprove'])
            ->name('shift-swaps.supervisor-approve');
        Route::post('/{swapRequest}/cancel', [\App\Http\Controllers\Api\Roster\ShiftSwapController::class, 'cancel'])
            ->name('shift-swaps.cancel');
    });

    // -------------------------------------------------------------------------
    // Attendance Records - سجلات الحضور
    // -------------------------------------------------------------------------
    Route::prefix('attendance')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\Roster\AttendanceController::class, 'index'])
            ->name('attendance.index');
        Route::get('/today', [\App\Http\Controllers\Api\Roster\AttendanceController::class, 'today'])
            ->name('attendance.today');
        Route::get('/employee/{employeeId}', [\App\Http\Controllers\Api\Roster\AttendanceController::class, 'byEmployee'])
            ->name('attendance.by-employee');
        Route::post('/check-in', [\App\Http\Controllers\Api\Roster\AttendanceController::class, 'checkIn'])
            ->name('attendance.check-in');
        Route::post('/check-out', [\App\Http\Controllers\Api\Roster\AttendanceController::class, 'checkOut'])
            ->name('attendance.check-out');
        Route::post('/manual', [\App\Http\Controllers\Api\Roster\AttendanceController::class, 'manualEntry'])
            ->name('attendance.manual');
        Route::post('/{record}/process', [\App\Http\Controllers\Api\Roster\AttendanceController::class, 'process'])
            ->name('attendance.process');
    });

    // -------------------------------------------------------------------------
    // Biometric Devices - أجهزة البصمة
    // -------------------------------------------------------------------------
    Route::prefix('devices')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\Roster\BiometricDeviceController::class, 'index'])
            ->name('biometric-devices.index');
        Route::get('/online', [\App\Http\Controllers\Api\Roster\BiometricDeviceController::class, 'online'])
            ->name('biometric-devices.online');
        Route::post('/', [\App\Http\Controllers\Api\Roster\BiometricDeviceController::class, 'store'])
            ->name('biometric-devices.store');
        Route::get('/{device}', [\App\Http\Controllers\Api\Roster\BiometricDeviceController::class, 'show'])
            ->name('biometric-devices.show');
        Route::put('/{device}', [\App\Http\Controllers\Api\Roster\BiometricDeviceController::class, 'update'])
            ->name('biometric-devices.update');
        Route::post('/{device}/sync', [\App\Http\Controllers\Api\Roster\BiometricDeviceController::class, 'sync'])
            ->name('biometric-devices.sync');
        Route::post('/{device}/test-connection', [\App\Http\Controllers\Api\Roster\BiometricDeviceController::class, 'testConnection'])
            ->name('biometric-devices.test-connection');
    });

    // -------------------------------------------------------------------------
    // Validation Rules - قواعد التحقق
    // -------------------------------------------------------------------------
    Route::prefix('validation-rules')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\Roster\ValidationRuleController::class, 'index'])
            ->name('roster-validation-rules.index');
        Route::post('/', [\App\Http\Controllers\Api\Roster\ValidationRuleController::class, 'store'])
            ->name('roster-validation-rules.store');
        Route::get('/{rule}', [\App\Http\Controllers\Api\Roster\ValidationRuleController::class, 'show'])
            ->name('roster-validation-rules.show');
        Route::put('/{rule}', [\App\Http\Controllers\Api\Roster\ValidationRuleController::class, 'update'])
            ->name('roster-validation-rules.update');
        Route::delete('/{rule}', [\App\Http\Controllers\Api\Roster\ValidationRuleController::class, 'destroy'])
            ->name('roster-validation-rules.destroy');
    });

    // -------------------------------------------------------------------------
    // Roster Reports - تقارير الجدولة
    // -------------------------------------------------------------------------
    Route::prefix('reports')->group(function () {
        Route::get('/attendance-summary', [\App\Http\Controllers\Api\Roster\RosterReportController::class, 'attendanceSummary'])
            ->name('roster-reports.attendance-summary');
        Route::get('/overtime', [\App\Http\Controllers\Api\Roster\RosterReportController::class, 'overtime'])
            ->name('roster-reports.overtime');
        Route::get('/absences', [\App\Http\Controllers\Api\Roster\RosterReportController::class, 'absences'])
            ->name('roster-reports.absences');
        Route::get('/late-arrivals', [\App\Http\Controllers\Api\Roster\RosterReportController::class, 'lateArrivals'])
            ->name('roster-reports.late-arrivals');
        Route::get('/coverage', [\App\Http\Controllers\Api\Roster\RosterReportController::class, 'coverage'])
            ->name('roster-reports.coverage');
    });
});

// =============================================================================
// Inventory Module Routes - مسارات وحدة المخزون
// =============================================================================

Route::prefix('inventory')->middleware(['auth:sanctum'])->group(function () {

    // -------------------------------------------------------------------------
    // Warehouses - المستودعات
    // -------------------------------------------------------------------------
    Route::prefix('warehouses')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\Inventory\WarehouseController::class, 'index'])
            ->name('warehouses.index');
        Route::get('/active', [\App\Http\Controllers\Api\Inventory\WarehouseController::class, 'active'])
            ->name('warehouses.active');
        Route::post('/', [\App\Http\Controllers\Api\Inventory\WarehouseController::class, 'store'])
            ->name('warehouses.store');
        Route::get('/{warehouse}', [\App\Http\Controllers\Api\Inventory\WarehouseController::class, 'show'])
            ->name('warehouses.show');
        Route::put('/{warehouse}', [\App\Http\Controllers\Api\Inventory\WarehouseController::class, 'update'])
            ->name('warehouses.update');
        Route::delete('/{warehouse}', [\App\Http\Controllers\Api\Inventory\WarehouseController::class, 'destroy'])
            ->name('warehouses.destroy');
        Route::get('/{warehouse}/stocks', [\App\Http\Controllers\Api\Inventory\WarehouseController::class, 'stocks'])
            ->name('warehouses.stocks');
        Route::get('/{warehouse}/movements', [\App\Http\Controllers\Api\Inventory\WarehouseController::class, 'movements'])
            ->name('warehouses.movements');
    });

    // -------------------------------------------------------------------------
    // Item Categories - فئات الأصناف
    // -------------------------------------------------------------------------
    Route::prefix('categories')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\Inventory\ItemCategoryController::class, 'index'])
            ->name('item-categories.index');
        Route::get('/tree', [\App\Http\Controllers\Api\Inventory\ItemCategoryController::class, 'tree'])
            ->name('item-categories.tree');
        Route::post('/', [\App\Http\Controllers\Api\Inventory\ItemCategoryController::class, 'store'])
            ->name('item-categories.store');
        Route::get('/{category}', [\App\Http\Controllers\Api\Inventory\ItemCategoryController::class, 'show'])
            ->name('item-categories.show');
        Route::put('/{category}', [\App\Http\Controllers\Api\Inventory\ItemCategoryController::class, 'update'])
            ->name('item-categories.update');
        Route::delete('/{category}', [\App\Http\Controllers\Api\Inventory\ItemCategoryController::class, 'destroy'])
            ->name('item-categories.destroy');
    });

    // -------------------------------------------------------------------------
    // Inventory Items - الأصناف
    // -------------------------------------------------------------------------
    Route::prefix('items')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\Inventory\InventoryItemController::class, 'index'])
            ->name('inventory-items.index');
        Route::get('/search', [\App\Http\Controllers\Api\Inventory\InventoryItemController::class, 'search'])
            ->name('inventory-items.search');
        Route::get('/low-stock', [\App\Http\Controllers\Api\Inventory\InventoryItemController::class, 'lowStock'])
            ->name('inventory-items.low-stock');
        Route::get('/expiring', [\App\Http\Controllers\Api\Inventory\InventoryItemController::class, 'expiring'])
            ->name('inventory-items.expiring');
        Route::post('/', [\App\Http\Controllers\Api\Inventory\InventoryItemController::class, 'store'])
            ->name('inventory-items.store');
        Route::get('/{item}', [\App\Http\Controllers\Api\Inventory\InventoryItemController::class, 'show'])
            ->name('inventory-items.show');
        Route::put('/{item}', [\App\Http\Controllers\Api\Inventory\InventoryItemController::class, 'update'])
            ->name('inventory-items.update');
        Route::delete('/{item}', [\App\Http\Controllers\Api\Inventory\InventoryItemController::class, 'destroy'])
            ->name('inventory-items.destroy');
        Route::get('/{item}/stocks', [\App\Http\Controllers\Api\Inventory\InventoryItemController::class, 'stocks'])
            ->name('inventory-items.stocks');
        Route::get('/{item}/movements', [\App\Http\Controllers\Api\Inventory\InventoryItemController::class, 'movements'])
            ->name('inventory-items.movements');
    });

    // -------------------------------------------------------------------------
    // Inventory Movements - حركات المخزون
    // -------------------------------------------------------------------------
    Route::prefix('movements')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\Inventory\InventoryMovementController::class, 'index'])
            ->name('inventory-movements.index');
        Route::post('/receive', [\App\Http\Controllers\Api\Inventory\InventoryMovementController::class, 'receive'])
            ->name('inventory-movements.receive');
        Route::post('/issue', [\App\Http\Controllers\Api\Inventory\InventoryMovementController::class, 'issue'])
            ->name('inventory-movements.issue');
        Route::post('/transfer', [\App\Http\Controllers\Api\Inventory\InventoryMovementController::class, 'transfer'])
            ->name('inventory-movements.transfer');
        Route::post('/adjust', [\App\Http\Controllers\Api\Inventory\InventoryMovementController::class, 'adjust'])
            ->name('inventory-movements.adjust');
        Route::post('/return', [\App\Http\Controllers\Api\Inventory\InventoryMovementController::class, 'returnItem'])
            ->name('inventory-movements.return');
        Route::get('/{movement}', [\App\Http\Controllers\Api\Inventory\InventoryMovementController::class, 'show'])
            ->name('inventory-movements.show');
        Route::post('/{movement}/approve', [\App\Http\Controllers\Api\Inventory\InventoryMovementController::class, 'approve'])
            ->name('inventory-movements.approve');
    });

    // -------------------------------------------------------------------------
    // Item Quotas - حصص الاستهلاك
    // -------------------------------------------------------------------------
    Route::prefix('quotas')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\Inventory\ItemQuotaController::class, 'index'])
            ->name('item-quotas.index');
        Route::get('/department/{departmentId}', [\App\Http\Controllers\Api\Inventory\ItemQuotaController::class, 'byDepartment'])
            ->name('item-quotas.by-department');
        Route::post('/', [\App\Http\Controllers\Api\Inventory\ItemQuotaController::class, 'store'])
            ->name('item-quotas.store');
        Route::get('/{quota}', [\App\Http\Controllers\Api\Inventory\ItemQuotaController::class, 'show'])
            ->name('item-quotas.show');
        Route::put('/{quota}', [\App\Http\Controllers\Api\Inventory\ItemQuotaController::class, 'update'])
            ->name('item-quotas.update');
        Route::delete('/{quota}', [\App\Http\Controllers\Api\Inventory\ItemQuotaController::class, 'destroy'])
            ->name('item-quotas.destroy');
        Route::get('/{quota}/consumption', [\App\Http\Controllers\Api\Inventory\ItemQuotaController::class, 'consumption'])
            ->name('item-quotas.consumption');
    });

    // -------------------------------------------------------------------------
    // Purchase Requests - طلبات الشراء
    // -------------------------------------------------------------------------
    Route::prefix('purchase-requests')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\Inventory\PurchaseRequestController::class, 'index'])
            ->name('purchase-requests.index');
        Route::get('/pending', [\App\Http\Controllers\Api\Inventory\PurchaseRequestController::class, 'pending'])
            ->name('purchase-requests.pending');
        Route::post('/', [\App\Http\Controllers\Api\Inventory\PurchaseRequestController::class, 'store'])
            ->name('purchase-requests.store');
        Route::get('/{purchaseRequest}', [\App\Http\Controllers\Api\Inventory\PurchaseRequestController::class, 'show'])
            ->name('purchase-requests.show');
        Route::put('/{purchaseRequest}', [\App\Http\Controllers\Api\Inventory\PurchaseRequestController::class, 'update'])
            ->name('purchase-requests.update');

        // Workflow
        Route::post('/{purchaseRequest}/submit', [\App\Http\Controllers\Api\Inventory\PurchaseRequestController::class, 'submit'])
            ->name('purchase-requests.submit');
        Route::post('/{purchaseRequest}/manager-approve', [\App\Http\Controllers\Api\Inventory\PurchaseRequestController::class, 'managerApprove'])
            ->name('purchase-requests.manager-approve');
        Route::post('/{purchaseRequest}/finance-approve', [\App\Http\Controllers\Api\Inventory\PurchaseRequestController::class, 'financeApprove'])
            ->name('purchase-requests.finance-approve');
        Route::post('/{purchaseRequest}/ceo-approve', [\App\Http\Controllers\Api\Inventory\PurchaseRequestController::class, 'ceoApprove'])
            ->name('purchase-requests.ceo-approve');
        Route::post('/{purchaseRequest}/reject', [\App\Http\Controllers\Api\Inventory\PurchaseRequestController::class, 'reject'])
            ->name('purchase-requests.reject');
        Route::post('/{purchaseRequest}/receive', [\App\Http\Controllers\Api\Inventory\PurchaseRequestController::class, 'receive'])
            ->name('purchase-requests.receive');
    });

    // -------------------------------------------------------------------------
    // Inventory Reports - تقارير المخزون
    // -------------------------------------------------------------------------
    Route::prefix('reports')->group(function () {
        Route::get('/stock-summary', [\App\Http\Controllers\Api\Inventory\InventoryReportController::class, 'stockSummary'])
            ->name('inventory-reports.stock-summary');
        Route::get('/valuation', [\App\Http\Controllers\Api\Inventory\InventoryReportController::class, 'valuation'])
            ->name('inventory-reports.valuation');
        Route::get('/movement-history', [\App\Http\Controllers\Api\Inventory\InventoryReportController::class, 'movementHistory'])
            ->name('inventory-reports.movement-history');
        Route::get('/expiry-report', [\App\Http\Controllers\Api\Inventory\InventoryReportController::class, 'expiryReport'])
            ->name('inventory-reports.expiry-report');
        Route::get('/consumption', [\App\Http\Controllers\Api\Inventory\InventoryReportController::class, 'consumption'])
            ->name('inventory-reports.consumption');
    });
});

// =============================================================================
// Finance Module Routes - مسارات وحدة المالية
// =============================================================================

Route::prefix('finance')->middleware(['auth:sanctum'])->group(function () {

    // -------------------------------------------------------------------------
    // Cost Centers - مراكز التكلفة
    // -------------------------------------------------------------------------
    Route::prefix('cost-centers')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\Finance\CostCenterController::class, 'index'])
            ->name('cost-centers.index');
        Route::get('/active', [\App\Http\Controllers\Api\Finance\CostCenterController::class, 'active'])
            ->name('cost-centers.active');
        Route::post('/', [\App\Http\Controllers\Api\Finance\CostCenterController::class, 'store'])
            ->name('cost-centers.store');
        Route::get('/{costCenter}', [\App\Http\Controllers\Api\Finance\CostCenterController::class, 'show'])
            ->name('cost-centers.show');
        Route::put('/{costCenter}', [\App\Http\Controllers\Api\Finance\CostCenterController::class, 'update'])
            ->name('cost-centers.update');
        Route::delete('/{costCenter}', [\App\Http\Controllers\Api\Finance\CostCenterController::class, 'destroy'])
            ->name('cost-centers.destroy');
    });

    // -------------------------------------------------------------------------
    // Doctors - الأطباء
    // -------------------------------------------------------------------------
    Route::prefix('doctors')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\Finance\DoctorController::class, 'index'])
            ->name('doctors.index');
        Route::get('/active', [\App\Http\Controllers\Api\Finance\DoctorController::class, 'active'])
            ->name('doctors.active');
        Route::post('/', [\App\Http\Controllers\Api\Finance\DoctorController::class, 'store'])
            ->name('doctors.store');
        Route::get('/{doctor}', [\App\Http\Controllers\Api\Finance\DoctorController::class, 'show'])
            ->name('doctors.show');
        Route::put('/{doctor}', [\App\Http\Controllers\Api\Finance\DoctorController::class, 'update'])
            ->name('doctors.update');
        Route::delete('/{doctor}', [\App\Http\Controllers\Api\Finance\DoctorController::class, 'destroy'])
            ->name('doctors.destroy');
        Route::get('/{doctor}/services', [\App\Http\Controllers\Api\Finance\DoctorController::class, 'services'])
            ->name('doctors.services');
        Route::get('/{doctor}/commissions', [\App\Http\Controllers\Api\Finance\DoctorController::class, 'commissions'])
            ->name('doctors.commissions');
    });

    // -------------------------------------------------------------------------
    // Medical Services - الخدمات الطبية
    // -------------------------------------------------------------------------
    Route::prefix('services')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\Finance\MedicalServiceController::class, 'index'])
            ->name('medical-services.index');
        Route::get('/active', [\App\Http\Controllers\Api\Finance\MedicalServiceController::class, 'active'])
            ->name('medical-services.active');
        Route::get('/by-category/{category}', [\App\Http\Controllers\Api\Finance\MedicalServiceController::class, 'byCategory'])
            ->name('medical-services.by-category');
        Route::post('/', [\App\Http\Controllers\Api\Finance\MedicalServiceController::class, 'store'])
            ->name('medical-services.store');
        Route::get('/{service}', [\App\Http\Controllers\Api\Finance\MedicalServiceController::class, 'show'])
            ->name('medical-services.show');
        Route::put('/{service}', [\App\Http\Controllers\Api\Finance\MedicalServiceController::class, 'update'])
            ->name('medical-services.update');
        Route::delete('/{service}', [\App\Http\Controllers\Api\Finance\MedicalServiceController::class, 'destroy'])
            ->name('medical-services.destroy');
    });

    // -------------------------------------------------------------------------
    // Insurance Companies - شركات التأمين
    // -------------------------------------------------------------------------
    Route::prefix('insurance-companies')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\Finance\InsuranceCompanyController::class, 'index'])
            ->name('insurance-companies.index');
        Route::get('/active', [\App\Http\Controllers\Api\Finance\InsuranceCompanyController::class, 'active'])
            ->name('insurance-companies.active');
        Route::post('/', [\App\Http\Controllers\Api\Finance\InsuranceCompanyController::class, 'store'])
            ->name('insurance-companies.store');
        Route::get('/{company}', [\App\Http\Controllers\Api\Finance\InsuranceCompanyController::class, 'show'])
            ->name('insurance-companies.show');
        Route::put('/{company}', [\App\Http\Controllers\Api\Finance\InsuranceCompanyController::class, 'update'])
            ->name('insurance-companies.update');
        Route::delete('/{company}', [\App\Http\Controllers\Api\Finance\InsuranceCompanyController::class, 'destroy'])
            ->name('insurance-companies.destroy');
    });

    // -------------------------------------------------------------------------
    // Insurance Claims - مطالبات التأمين
    // -------------------------------------------------------------------------
    Route::prefix('claims')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\Finance\InsuranceClaimController::class, 'index'])
            ->name('insurance-claims.index');
        Route::get('/pending', [\App\Http\Controllers\Api\Finance\InsuranceClaimController::class, 'pending'])
            ->name('insurance-claims.pending');
        Route::get('/aging', [\App\Http\Controllers\Api\Finance\InsuranceClaimController::class, 'aging'])
            ->name('insurance-claims.aging');
        Route::post('/', [\App\Http\Controllers\Api\Finance\InsuranceClaimController::class, 'store'])
            ->name('insurance-claims.store');
        Route::get('/{claim}', [\App\Http\Controllers\Api\Finance\InsuranceClaimController::class, 'show'])
            ->name('insurance-claims.show');
        Route::put('/{claim}', [\App\Http\Controllers\Api\Finance\InsuranceClaimController::class, 'update'])
            ->name('insurance-claims.update');

        // Workflow
        Route::post('/{claim}/scrub', [\App\Http\Controllers\Api\Finance\InsuranceClaimController::class, 'scrub'])
            ->name('insurance-claims.scrub');
        Route::post('/{claim}/submit', [\App\Http\Controllers\Api\Finance\InsuranceClaimController::class, 'submit'])
            ->name('insurance-claims.submit');
        Route::post('/{claim}/approve', [\App\Http\Controllers\Api\Finance\InsuranceClaimController::class, 'approve'])
            ->name('insurance-claims.approve');
        Route::post('/{claim}/reject', [\App\Http\Controllers\Api\Finance\InsuranceClaimController::class, 'reject'])
            ->name('insurance-claims.reject');
        Route::post('/{claim}/mark-paid', [\App\Http\Controllers\Api\Finance\InsuranceClaimController::class, 'markPaid'])
            ->name('insurance-claims.mark-paid');
    });

    // -------------------------------------------------------------------------
    // Commission Adjustments - تعديلات العمولات
    // -------------------------------------------------------------------------
    Route::prefix('commission-adjustments')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\Finance\CommissionAdjustmentController::class, 'index'])
            ->name('commission-adjustments.index');
        Route::get('/pending', [\App\Http\Controllers\Api\Finance\CommissionAdjustmentController::class, 'pending'])
            ->name('commission-adjustments.pending');
        Route::post('/', [\App\Http\Controllers\Api\Finance\CommissionAdjustmentController::class, 'store'])
            ->name('commission-adjustments.store');
        Route::get('/{adjustment}', [\App\Http\Controllers\Api\Finance\CommissionAdjustmentController::class, 'show'])
            ->name('commission-adjustments.show');
        Route::post('/{adjustment}/approve', [\App\Http\Controllers\Api\Finance\CommissionAdjustmentController::class, 'approve'])
            ->name('commission-adjustments.approve');
        Route::post('/{adjustment}/reject', [\App\Http\Controllers\Api\Finance\CommissionAdjustmentController::class, 'reject'])
            ->name('commission-adjustments.reject');
    });

    // -------------------------------------------------------------------------
    // Finance Reports - التقارير المالية
    // -------------------------------------------------------------------------
    Route::prefix('reports')->group(function () {
        Route::get('/profitability', [\App\Http\Controllers\Api\Finance\FinanceReportController::class, 'profitability'])
            ->name('finance-reports.profitability');
        Route::get('/revenue-by-service', [\App\Http\Controllers\Api\Finance\FinanceReportController::class, 'revenueByService'])
            ->name('finance-reports.revenue-by-service');
        Route::get('/revenue-by-doctor', [\App\Http\Controllers\Api\Finance\FinanceReportController::class, 'revenueByDoctor'])
            ->name('finance-reports.revenue-by-doctor');
        Route::get('/claims-summary', [\App\Http\Controllers\Api\Finance\FinanceReportController::class, 'claimsSummary'])
            ->name('finance-reports.claims-summary');
        Route::get('/aging-report', [\App\Http\Controllers\Api\Finance\FinanceReportController::class, 'agingReport'])
            ->name('finance-reports.aging-report');
        Route::get('/cost-analysis', [\App\Http\Controllers\Api\Finance\FinanceReportController::class, 'costAnalysis'])
            ->name('finance-reports.cost-analysis');
    });
});

// =============================================================================
// System Module Routes - مسارات النظام
// =============================================================================

Route::prefix('system')->middleware(['auth:sanctum'])->group(function () {

    // -------------------------------------------------------------------------
    // Users - المستخدمين
    // -------------------------------------------------------------------------
    Route::prefix('users')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\System\UserController::class, 'index'])
            ->name('users.index');
        Route::get('/active', [\App\Http\Controllers\Api\System\UserController::class, 'active'])
            ->name('users.active');
        Route::post('/', [\App\Http\Controllers\Api\System\UserController::class, 'store'])
            ->name('users.store');
        Route::get('/{user}', [\App\Http\Controllers\Api\System\UserController::class, 'show'])
            ->name('users.show');
        Route::put('/{user}', [\App\Http\Controllers\Api\System\UserController::class, 'update'])
            ->name('users.update');
        Route::delete('/{user}', [\App\Http\Controllers\Api\System\UserController::class, 'destroy'])
            ->name('users.destroy');
        Route::post('/{user}/activate', [\App\Http\Controllers\Api\System\UserController::class, 'activate'])
            ->name('users.activate');
        Route::post('/{user}/deactivate', [\App\Http\Controllers\Api\System\UserController::class, 'deactivate'])
            ->name('users.deactivate');
        Route::post('/{user}/reset-password', [\App\Http\Controllers\Api\System\UserController::class, 'resetPassword'])
            ->name('users.reset-password');
    });

    // -------------------------------------------------------------------------
    // Roles - الأدوار
    // -------------------------------------------------------------------------
    Route::prefix('roles')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\System\RoleController::class, 'index'])
            ->name('roles.index');
        Route::post('/', [\App\Http\Controllers\Api\System\RoleController::class, 'store'])
            ->name('roles.store');
        Route::get('/{role}', [\App\Http\Controllers\Api\System\RoleController::class, 'show'])
            ->name('roles.show');
        Route::put('/{role}', [\App\Http\Controllers\Api\System\RoleController::class, 'update'])
            ->name('roles.update');
        Route::delete('/{role}', [\App\Http\Controllers\Api\System\RoleController::class, 'destroy'])
            ->name('roles.destroy');
        Route::post('/{role}/assign-permissions', [\App\Http\Controllers\Api\System\RoleController::class, 'assignPermissions'])
            ->name('roles.assign-permissions');
    });

    // -------------------------------------------------------------------------
    // Permissions - الصلاحيات
    // -------------------------------------------------------------------------
    Route::prefix('permissions')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\System\PermissionController::class, 'index'])
            ->name('permissions.index');
        Route::get('/by-module', [\App\Http\Controllers\Api\System\PermissionController::class, 'byModule'])
            ->name('permissions.by-module');
    });

    // -------------------------------------------------------------------------
    // Audit Logs - سجلات المراجعة
    // -------------------------------------------------------------------------
    Route::prefix('audit-logs')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\System\AuditLogController::class, 'index'])
            ->name('audit-logs.index');
        Route::get('/user/{userId}', [\App\Http\Controllers\Api\System\AuditLogController::class, 'byUser'])
            ->name('audit-logs.by-user');
        Route::get('/entity/{entityType}/{entityId}', [\App\Http\Controllers\Api\System\AuditLogController::class, 'byEntity'])
            ->name('audit-logs.by-entity');
        Route::get('/{auditLog}', [\App\Http\Controllers\Api\System\AuditLogController::class, 'show'])
            ->name('audit-logs.show');
    });

    // -------------------------------------------------------------------------
    // System Settings - إعدادات النظام
    // -------------------------------------------------------------------------
    Route::prefix('settings')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\System\SystemSettingController::class, 'index'])
            ->name('system-settings.index');
        Route::get('/group/{group}', [\App\Http\Controllers\Api\System\SystemSettingController::class, 'byGroup'])
            ->name('system-settings.by-group');
        Route::get('/{key}', [\App\Http\Controllers\Api\System\SystemSettingController::class, 'show'])
            ->name('system-settings.show');
        Route::put('/{key}', [\App\Http\Controllers\Api\System\SystemSettingController::class, 'update'])
            ->name('system-settings.update');
        Route::post('/bulk-update', [\App\Http\Controllers\Api\System\SystemSettingController::class, 'bulkUpdate'])
            ->name('system-settings.bulk-update');
    });

    // -------------------------------------------------------------------------
    // Notifications - الإشعارات
    // -------------------------------------------------------------------------
    Route::prefix('notifications')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\System\NotificationController::class, 'index'])
            ->name('notifications.index');
        Route::get('/unread', [\App\Http\Controllers\Api\System\NotificationController::class, 'unread'])
            ->name('notifications.unread');
        Route::get('/unread-count', [\App\Http\Controllers\Api\System\NotificationController::class, 'unreadCount'])
            ->name('notifications.unread-count');
        Route::post('/{notification}/mark-read', [\App\Http\Controllers\Api\System\NotificationController::class, 'markRead'])
            ->name('notifications.mark-read');
        Route::post('/mark-all-read', [\App\Http\Controllers\Api\System\NotificationController::class, 'markAllRead'])
            ->name('notifications.mark-all-read');
        Route::delete('/{notification}', [\App\Http\Controllers\Api\System\NotificationController::class, 'destroy'])
            ->name('notifications.destroy');
    });

    // -------------------------------------------------------------------------
    // Integration Configs - إعدادات التكامل
    // -------------------------------------------------------------------------
    Route::prefix('integrations')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\System\IntegrationController::class, 'index'])
            ->name('integrations.index');
        Route::get('/{integration}', [\App\Http\Controllers\Api\System\IntegrationController::class, 'show'])
            ->name('integrations.show');
        Route::put('/{integration}', [\App\Http\Controllers\Api\System\IntegrationController::class, 'update'])
            ->name('integrations.update');
        Route::post('/{integration}/test', [\App\Http\Controllers\Api\System\IntegrationController::class, 'test'])
            ->name('integrations.test');
        Route::post('/{integration}/sync', [\App\Http\Controllers\Api\System\IntegrationController::class, 'sync'])
            ->name('integrations.sync');
    });

    // -------------------------------------------------------------------------
    // Dashboard - لوحة المعلومات
    // -------------------------------------------------------------------------
    Route::prefix('dashboard')->group(function () {
        Route::get('/summary', [\App\Http\Controllers\Api\System\DashboardController::class, 'summary'])
            ->name('dashboard.summary');
        Route::get('/hr-metrics', [\App\Http\Controllers\Api\System\DashboardController::class, 'hrMetrics'])
            ->name('dashboard.hr-metrics');
        Route::get('/finance-metrics', [\App\Http\Controllers\Api\System\DashboardController::class, 'financeMetrics'])
            ->name('dashboard.finance-metrics');
        Route::get('/inventory-alerts', [\App\Http\Controllers\Api\System\DashboardController::class, 'inventoryAlerts'])
            ->name('dashboard.inventory-alerts');
        Route::get('/pending-approvals', [\App\Http\Controllers\Api\System\DashboardController::class, 'pendingApprovals'])
            ->name('dashboard.pending-approvals');
    });
});
