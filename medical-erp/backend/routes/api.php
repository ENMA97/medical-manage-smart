<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ContractController;
use App\Http\Controllers\Api\CustodyController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\DepartmentController;
use App\Http\Controllers\Api\EmployeeController;
use App\Http\Controllers\Api\ImportController;
use App\Http\Controllers\Api\LeaveRequestController;
use App\Http\Controllers\Api\LeaveTypeController;
use App\Http\Controllers\Api\LetterController;
use App\Http\Controllers\Api\LoanController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\PayrollController;
use App\Http\Controllers\Api\ResignationController;
use App\Http\Controllers\Api\DisciplinaryController;
use App\Http\Controllers\Api\PositionController;
use App\Http\Controllers\Api\AiInsightsController;
use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\PerformanceController;
use App\Http\Controllers\Api\RecruitmentController;
use App\Http\Controllers\Api\TrainingController;
use App\Http\Controllers\Api\SystemSettingController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — نظام إدارة شؤون الموظفين (HRMS)
|--------------------------------------------------------------------------
|
| المصادقة: الرقم الوظيفي + رقم الهاتف
| الحماية: Laravel Sanctum (Bearer Token)
| الصلاحيات: role:super_admin,hr_manager,department_manager,employee
|
*/

// ─── Public Routes (بدون مصادقة) ───
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:10,1');
});

// ─── Protected Routes (تتطلب مصادقة) ───
Route::middleware(['auth:sanctum', 'active'])->group(function () {

    // ── Auth (جميع المستخدمين) ──
    Route::prefix('auth')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/logout-all', [AuthController::class, 'logoutAll']);
        Route::put('/fcm-token', [AuthController::class, 'updateFcmToken']);
        Route::put('/language', [AuthController::class, 'updateLanguage']);
    });

    // ── Notifications (جميع المستخدمين) ──
    Route::prefix('notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'index']);
        Route::put('/{notification}/read', [NotificationController::class, 'markAsRead']);
        Route::put('/read-all', [NotificationController::class, 'markAllAsRead']);
    });

    // ── Dashboard (جميع المستخدمين) ──
    Route::prefix('dashboard')->group(function () {
        Route::get('/summary', [DashboardController::class, 'summary']);
        Route::get('/employee-stats', [DashboardController::class, 'employeeStats']);
        Route::get('/leave-stats', [DashboardController::class, 'leaveStats']);
        Route::get('/alerts', [DashboardController::class, 'alerts']);
    });

    // ── Leave Requests — تقديم/إلغاء (جميع المستخدمين) ──
    Route::get('leave-requests', [LeaveRequestController::class, 'index']);
    Route::post('leave-requests', [LeaveRequestController::class, 'store']);
    Route::get('leave-requests/{leaveRequest}', [LeaveRequestController::class, 'show']);
    Route::post('leave-requests/{leaveRequest}/cancel', [LeaveRequestController::class, 'cancel']);

    // ── Resignations — تقديم (جميع المستخدمين) ──
    Route::get('resignations', [ResignationController::class, 'index']);
    Route::post('resignations', [ResignationController::class, 'store']);
    Route::get('resignations/{resignation}', [ResignationController::class, 'show']);

    // ─── HR & Admin Routes (مدير النظام + مدير الموارد البشرية) ───
    Route::middleware('role:super_admin,hr_manager')->group(function () {

        // ── Import ──
        Route::prefix('import')->group(function () {
            Route::post('/employees', [ImportController::class, 'importEmployees']);
            Route::get('/template', [ImportController::class, 'downloadTemplate']);
        });

        // ── Employees (CRUD كامل) ──
        Route::apiResource('employees', EmployeeController::class);
        Route::get('employees/{employee}/documents', [EmployeeController::class, 'documents']);

        // ── Departments ──
        Route::apiResource('departments', DepartmentController::class);

        // ── Positions (المسميات الوظيفية) ──
        Route::apiResource('positions', PositionController::class);

        // ── Contracts ──
        Route::apiResource('contracts', ContractController::class)->except(['destroy']);
        Route::post('contracts/{contract}/renew', [ContractController::class, 'renew']);

        // ── Leave Types ──
        Route::apiResource('leave-types', LeaveTypeController::class)->except(['destroy']);

        // ── Leave Approval (قبول/رفض) ──
        Route::post('leave-requests/{leaveRequest}/approve', [LeaveRequestController::class, 'approve']);
        Route::post('leave-requests/{leaveRequest}/reject', [LeaveRequestController::class, 'reject']);

        // ── Payroll ──
        Route::apiResource('payrolls', PayrollController::class)->only(['index', 'store', 'show']);
        Route::post('payrolls/{payroll}/approve', [PayrollController::class, 'approve']);
        Route::get('payrolls/{payroll}/export', [PayrollController::class, 'export']);

        // ── Custody ──
        Route::apiResource('custody', CustodyController::class)->only(['index', 'store', 'show']);
        Route::post('custody/{custody}/return', [CustodyController::class, 'return']);

        // ── Resignations Approval ──
        Route::post('resignations/{resignation}/approve', [ResignationController::class, 'approve']);
        Route::post('resignations/{resignation}/reject', [ResignationController::class, 'reject']);

        // ── Loans (السلف) ──
        Route::apiResource('loans', LoanController::class)->only(['index', 'store', 'show']);
        Route::post('loans/{loan}/approve', [LoanController::class, 'approve']);
        Route::post('loans/{loan}/reject', [LoanController::class, 'reject']);

        // ── Letters (الخطابات) ──
        Route::get('letter-templates', [LetterController::class, 'templates']);
        Route::apiResource('letters', LetterController::class)->only(['index', 'store', 'show']);
        Route::post('letters/{letter}/approve', [LetterController::class, 'approve']);

        // ── Disciplinary & Investigations (المساءلات والتحقيق) ──
        Route::get('violation-types', [DisciplinaryController::class, 'violationTypes']);
        Route::get('violation-types/{id}/suggest-penalty', [DisciplinaryController::class, 'suggestPenalty']);
        Route::apiResource('violations', DisciplinaryController::class)->only(['index', 'store', 'show']);
        Route::post('violations/{violation}/committee', [DisciplinaryController::class, 'formCommittee']);
        Route::get('committees/{committee}', [DisciplinaryController::class, 'showCommittee']);
        Route::post('committees/{committee}/sessions', [DisciplinaryController::class, 'addSession']);
        Route::get('decisions', [DisciplinaryController::class, 'decisions']);
        Route::get('decisions/{decision}', [DisciplinaryController::class, 'showDecision']);
        Route::post('violations/{violation}/decision', [DisciplinaryController::class, 'issueDecision']);
        Route::post('decisions/{decision}/approve', [DisciplinaryController::class, 'approveDecision']);

        // ── AI Insights (الذكاء الاصطناعي) ──
        Route::prefix('ai')->group(function () {
            Route::get('/dashboard', [AiInsightsController::class, 'dashboard']);
            Route::post('/analyze/leave-patterns', [AiInsightsController::class, 'analyzeLeavePatterns']);
            Route::post('/analyze/turnover-risk', [AiInsightsController::class, 'analyzeTurnoverRisk']);
            Route::get('/predictions', [AiInsightsController::class, 'predictions']);
            Route::post('/predictions/{prediction}/acknowledge', [AiInsightsController::class, 'acknowledgePrediction']);
            Route::get('/recommendations', [AiInsightsController::class, 'recommendations']);
            Route::put('/recommendations/{recommendation}/review', [AiInsightsController::class, 'reviewRecommendation']);
            Route::get('/risk-scores', [AiInsightsController::class, 'riskScores']);
            Route::get('/analysis-logs', [AiInsightsController::class, 'analysisLogs']);
        });

        // ── System Settings ──
        Route::prefix('settings')->group(function () {
            Route::get('/', [SystemSettingController::class, 'index']);
            Route::put('/{setting}', [SystemSettingController::class, 'update']);
        });

        // ── Attendance & Time Tracking (الحضور والانصراف) ──
        Route::prefix('attendance')->group(function () {
            // الورديات
            Route::get('/shifts', [AttendanceController::class, 'shifts']);
            Route::post('/shifts', [AttendanceController::class, 'storeShift']);
            // جداول الدوام
            Route::get('/schedules', [AttendanceController::class, 'schedules']);
            Route::post('/schedules', [AttendanceController::class, 'storeSchedule']);
            Route::post('/employee-schedules', [AttendanceController::class, 'assignSchedule']);
            // سجلات الحضور
            Route::get('/records', [AttendanceController::class, 'records']);
            Route::post('/check-in', [AttendanceController::class, 'checkIn']);
            Route::post('/check-out', [AttendanceController::class, 'checkOut']);
            Route::put('/records/{record}/adjust', [AttendanceController::class, 'adjustRecord']);
            // العمل الإضافي
            Route::get('/overtime', [AttendanceController::class, 'overtimeRequests']);
            Route::post('/overtime', [AttendanceController::class, 'storeOvertime']);
            Route::post('/overtime/{overtime}/approve', [AttendanceController::class, 'approveOvertime']);
            Route::post('/overtime/{overtime}/reject', [AttendanceController::class, 'rejectOvertime']);
            // الأذونات
            Route::get('/permissions', [AttendanceController::class, 'permissionRequests']);
            Route::post('/permissions', [AttendanceController::class, 'storePermission']);
            Route::post('/permissions/{permission}/approve', [AttendanceController::class, 'approvePermission']);
            // ملخصات الحضور
            Route::get('/summaries', [AttendanceController::class, 'summaries']);
            Route::post('/summaries/generate', [AttendanceController::class, 'generateSummary']);
        });

        // ── Performance Appraisal (تقييم الأداء) ──
        Route::prefix('performance')->group(function () {
            // دورات التقييم
            Route::get('/cycles', [PerformanceController::class, 'cycles']);
            Route::post('/cycles', [PerformanceController::class, 'storeCycle']);
            Route::get('/cycles/{cycle}', [PerformanceController::class, 'showCycle']);
            Route::put('/cycles/{cycle}', [PerformanceController::class, 'updateCycle']);
            // نماذج التقييم
            Route::get('/templates', [PerformanceController::class, 'templates']);
            Route::post('/templates', [PerformanceController::class, 'storeTemplate']);
            // تقييمات الموظفين
            Route::get('/appraisals', [PerformanceController::class, 'appraisals']);
            Route::post('/appraisals', [PerformanceController::class, 'storeAppraisal']);
            Route::get('/appraisals/{appraisal}', [PerformanceController::class, 'showAppraisal']);
            Route::put('/appraisals/{appraisal}/scores', [PerformanceController::class, 'updateScores']);
            Route::post('/appraisals/{appraisal}/complete', [PerformanceController::class, 'completeAppraisal']);
            // أهداف الموظفين
            Route::get('/goals', [PerformanceController::class, 'goals']);
            Route::post('/goals', [PerformanceController::class, 'storeGoal']);
            Route::put('/goals/{goal}', [PerformanceController::class, 'updateGoal']);
        });

        // ── Training & Development (التدريب والتطوير) ──
        Route::prefix('training')->group(function () {
            // البرامج التدريبية
            Route::get('/programs', [TrainingController::class, 'programs']);
            Route::post('/programs', [TrainingController::class, 'storeProgram']);
            Route::get('/programs/{program}', [TrainingController::class, 'showProgram']);
            // الدورات التدريبية
            Route::get('/courses', [TrainingController::class, 'courses']);
            Route::post('/courses', [TrainingController::class, 'storeCourse']);
            Route::get('/courses/{course}', [TrainingController::class, 'showCourse']);
            Route::post('/courses/{course}/enroll', [TrainingController::class, 'enroll']);
            // تسجيل الموظفين
            Route::put('/enrollments/{enrollment}', [TrainingController::class, 'updateEnrollment']);
            // الشهادات
            Route::get('/certificates', [TrainingController::class, 'certificates']);
            Route::post('/certificates', [TrainingController::class, 'storeCertificate']);
            // المهارات
            Route::get('/skill-categories', [TrainingController::class, 'skillCategories']);
            Route::get('/skills', [TrainingController::class, 'skills']);
            Route::post('/skills', [TrainingController::class, 'storeSkill']);
        });

        // ── Recruitment (التوظيف والاستقطاب) ──
        Route::prefix('recruitment')->group(function () {
            // طلبات التوظيف
            Route::get('/requisitions', [RecruitmentController::class, 'requisitions']);
            Route::post('/requisitions', [RecruitmentController::class, 'storeRequisition']);
            Route::get('/requisitions/{requisition}', [RecruitmentController::class, 'showRequisition']);
            Route::post('/requisitions/{requisition}/approve', [RecruitmentController::class, 'approveRequisition']);
            // الإعلانات الوظيفية
            Route::get('/postings', [RecruitmentController::class, 'postings']);
            Route::post('/postings', [RecruitmentController::class, 'storePosting']);
            Route::post('/postings/{posting}/publish', [RecruitmentController::class, 'publishPosting']);
            // المرشحون
            Route::get('/candidates', [RecruitmentController::class, 'candidates']);
            Route::post('/candidates', [RecruitmentController::class, 'storeCandidate']);
            Route::get('/candidates/{candidate}', [RecruitmentController::class, 'showCandidate']);
            // طلبات التقديم
            Route::get('/applications', [RecruitmentController::class, 'applications']);
            Route::post('/applications', [RecruitmentController::class, 'storeApplication']);
            Route::put('/applications/{application}/status', [RecruitmentController::class, 'updateApplicationStatus']);
            // المقابلات
            Route::get('/interviews', [RecruitmentController::class, 'interviews']);
            Route::post('/interviews', [RecruitmentController::class, 'storeInterview']);
            Route::put('/interviews/{interview}/feedback', [RecruitmentController::class, 'interviewFeedback']);
            // عروض العمل
            Route::post('/offers', [RecruitmentController::class, 'storeOffer']);
            Route::post('/offers/{offer}/approve', [RecruitmentController::class, 'approveOffer']);
            Route::post('/offers/{offer}/send', [RecruitmentController::class, 'sendOffer']);
            Route::post('/offers/{offer}/respond', [RecruitmentController::class, 'respondOffer']);
        });
    });
});
