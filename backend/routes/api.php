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
