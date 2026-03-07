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
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\PayrollController;
use App\Http\Controllers\Api\ResignationController;
use App\Http\Controllers\Api\SystemSettingController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — نظام إدارة شؤون الموظفين (HRMS)
|--------------------------------------------------------------------------
|
| المصادقة: الرقم الوظيفي + رقم الهاتف
| الحماية: Laravel Sanctum (Bearer Token)
|
*/

// ─── Public Routes (بدون مصادقة) ───
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:10,1'); // 10 محاولات في الدقيقة
});

// ─── Protected Routes (تتطلب مصادقة) ───
Route::middleware(['auth:sanctum', 'active'])->group(function () {

    // ── Auth ──
    Route::prefix('auth')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/logout-all', [AuthController::class, 'logoutAll']);
        Route::put('/fcm-token', [AuthController::class, 'updateFcmToken']);
        Route::put('/language', [AuthController::class, 'updateLanguage']);
    });

    // ── Import (استيراد الملفات) ──
    Route::prefix('import')->group(function () {
        Route::post('/employees', [ImportController::class, 'importEmployees']);
        Route::get('/template', [ImportController::class, 'downloadTemplate']);
    });

    // ── Employees (الموظفون) ──
    Route::apiResource('employees', EmployeeController::class);
    Route::get('employees/{employee}/documents', [EmployeeController::class, 'documents']);

    // ── Departments (الأقسام) ──
    Route::apiResource('departments', DepartmentController::class);

    // ── Contracts (العقود) ──
    Route::apiResource('contracts', ContractController::class)->except(['destroy']);
    Route::post('contracts/{contract}/renew', [ContractController::class, 'renew']);

    // ── Leave Types (أنواع الإجازات) ──
    Route::apiResource('leave-types', LeaveTypeController::class)->except(['destroy']);

    // ── Leave Requests (طلبات الإجازة) ──
    Route::apiResource('leave-requests', LeaveRequestController::class)->only(['index', 'store', 'show']);
    Route::post('leave-requests/{leaveRequest}/approve', [LeaveRequestController::class, 'approve']);
    Route::post('leave-requests/{leaveRequest}/reject', [LeaveRequestController::class, 'reject']);
    Route::post('leave-requests/{leaveRequest}/cancel', [LeaveRequestController::class, 'cancel']);

    // ── Payroll (الرواتب) ──
    Route::apiResource('payrolls', PayrollController::class)->only(['index', 'store', 'show']);
    Route::post('payrolls/{payroll}/approve', [PayrollController::class, 'approve']);
    Route::get('payrolls/{payroll}/export', [PayrollController::class, 'export']);

    // ── Custody (العهد) ──
    Route::apiResource('custody', CustodyController::class)->only(['index', 'store', 'show']);
    Route::post('custody/{custody}/return', [CustodyController::class, 'return']);

    // ── Resignations (الاستقالات) ──
    Route::apiResource('resignations', ResignationController::class)->only(['index', 'store', 'show']);
    Route::post('resignations/{resignation}/approve', [ResignationController::class, 'approve']);
    Route::post('resignations/{resignation}/reject', [ResignationController::class, 'reject']);

    // ── Notifications (الإشعارات) ──
    Route::prefix('notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'index']);
        Route::put('/{notification}/read', [NotificationController::class, 'markAsRead']);
        Route::put('/read-all', [NotificationController::class, 'markAllAsRead']);
    });

    // ── Dashboard (لوحة التحكم) ──
    Route::prefix('dashboard')->group(function () {
        Route::get('/summary', [DashboardController::class, 'summary']);
        Route::get('/employee-stats', [DashboardController::class, 'employeeStats']);
        Route::get('/leave-stats', [DashboardController::class, 'leaveStats']);
        Route::get('/alerts', [DashboardController::class, 'alerts']);
    });

    // ── System Settings (إعدادات النظام) ──
    Route::prefix('settings')->group(function () {
        Route::get('/', [SystemSettingController::class, 'index']);
        Route::put('/{setting}', [SystemSettingController::class, 'update']);
    });
});
