<?php

use App\Http\Controllers\Api\AuthController;
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

    // ─── باقي مسارات الـ API تُضاف هنا لاحقاً ───
    // Route::apiResource('employees', EmployeeController::class);
    // Route::apiResource('contracts', ContractController::class);
    // Route::apiResource('leave-requests', LeaveRequestController::class);
    // etc.
});
