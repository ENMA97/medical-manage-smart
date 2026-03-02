<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(
        private readonly AuthService $authService
    ) {}

    /**
     * POST /api/auth/login
     * تسجيل الدخول بالرقم الوظيفي + رقم الهاتف
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->authService->authenticate(
            employeeNumber: $request->validated('employee_number'),
            phone: $request->validated('phone'),
            deviceName: $request->validated('device_name'),
        );

        // تحديث FCM token إن وُجد
        if ($request->filled('fcm_token')) {
            $this->authService->updateFcmToken(
                auth()->user() ?? \App\Models\User::find($result['user']['id']),
                $request->validated('fcm_token')
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'تم تسجيل الدخول بنجاح',
            'data' => $result,
        ]);
    }

    /**
     * POST /api/auth/logout
     * تسجيل الخروج (حذف التوكن الحالي)
     */
    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());

        return response()->json([
            'success' => true,
            'message' => 'تم تسجيل الخروج بنجاح',
        ]);
    }

    /**
     * POST /api/auth/logout-all
     * تسجيل الخروج من جميع الأجهزة
     */
    public function logoutAll(Request $request): JsonResponse
    {
        $this->authService->logoutAll($request->user());

        return response()->json([
            'success' => true,
            'message' => 'تم تسجيل الخروج من جميع الأجهزة',
        ]);
    }

    /**
     * GET /api/auth/me
     * الحصول على بيانات المستخدم الحالي
     */
    public function me(Request $request): JsonResponse
    {
        $profile = $this->authService->getProfile($request->user());

        return response()->json([
            'success' => true,
            'data' => $profile,
        ]);
    }

    /**
     * PUT /api/auth/fcm-token
     * تحديث FCM Token
     */
    public function updateFcmToken(Request $request): JsonResponse
    {
        $request->validate([
            'fcm_token' => ['required', 'string', 'max:500'],
        ]);

        $this->authService->updateFcmToken(
            $request->user(),
            $request->input('fcm_token')
        );

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث رمز الإشعارات',
        ]);
    }

    /**
     * PUT /api/auth/language
     * تغيير اللغة المفضلة
     */
    public function updateLanguage(Request $request): JsonResponse
    {
        $request->validate([
            'language' => ['required', 'in:ar,en'],
        ]);

        $request->user()->update([
            'preferred_language' => $request->input('language'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث اللغة',
            'data' => ['language' => $request->input('language')],
        ]);
    }
}
