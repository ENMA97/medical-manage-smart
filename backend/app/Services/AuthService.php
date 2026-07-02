<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthService
{
    /**
     * المصادقة بالرقم الوظيفي + رقم الهاتف
     * Authenticate using employee_number + phone
     */
    public function authenticate(string $employeeNumber, string $phone, ?string $deviceName = null): array
    {
        // 1. البحث عن الموظف بالرقم الوظيفي
        $employee = Employee::where('employee_number', $employeeNumber)->first();

        if (!$employee) {
            throw ValidationException::withMessages([
                'employee_number' => [__('auth.employee_not_found')],
            ]);
        }

        // 2. التحقق من رقم الهاتف
        if (!$this->verifyPhone($employee, $phone)) {
            throw ValidationException::withMessages([
                'phone' => [__('auth.invalid_phone')],
            ]);
        }

        // 3. التحقق من حالة الموظف
        if (!in_array($employee->status, ['active', 'on_leave'])) {
            throw ValidationException::withMessages([
                'employee_number' => [__('auth.employee_inactive')],
            ]);
        }

        // 4. البحث عن حساب المستخدم المرتبط أو إنشاء واحد
        $user = $this->findOrCreateUser($employee);

        if (!$user->is_active) {
            throw ValidationException::withMessages([
                'employee_number' => [__('auth.account_disabled')],
            ]);
        }

        // 5. إنشاء التوكن
        $token = $user->createToken(
            $deviceName ?? 'hrms-app',
            ['*'],
            now()->addDays(30)
        );

        // 6. تحديث بيانات الدخول
        $user->update([
            'last_login_at' => now(),
            'last_login_ip' => request()->ip(),
        ]);

        return [
            'user' => $this->formatUserResponse($user, $employee),
            'token' => $token->plainTextToken,
            'expires_at' => $token->accessToken->expires_at,
        ];
    }

    /**
     * التحقق من OTP (مرحلة مستقبلية)
     */
    public function verifyOtp(string $employeeNumber, string $otp): array
    {
        // يمكن إضافة تحقق OTP عبر SMS لاحقاً
        throw ValidationException::withMessages([
            'otp' => ['OTP verification not implemented yet.'],
        ]);
    }

    /**
     * تسجيل الخروج — حذف التوكن الحالي
     */
    public function logout(User $user): void
    {
        $token = $user->currentAccessToken();
        if (method_exists($token, 'delete')) {
            $token->delete();
        }
    }

    /**
     * تسجيل الخروج من جميع الأجهزة
     */
    public function logoutAll(User $user): void
    {
        $user->tokens()->delete();
    }

    /**
     * تحديث FCM Token للإشعارات
     */
    public function updateFcmToken(User $user, string $fcmToken): void
    {
        $user->update(['fcm_token' => $fcmToken]);
    }

    /**
     * الحصول على الملف الشخصي الكامل
     */
    public function getProfile(User $user): array
    {
        $user->load('employee.department', 'employee.position');

        return $this->formatUserResponse($user, $user->employee);
    }

    // ─── Private Methods ───

    private function verifyPhone(Employee $employee, string $phone): bool
    {
        $normalizedInput = $this->normalizePhone($phone);
        $normalizedStored = $this->normalizePhone($employee->phone);

        return $normalizedInput === $normalizedStored;
    }

    /**
     * تطبيع رقم الهاتف — إزالة الفراغات ورمز الدولة
     * +966512345678 → 512345678
     * 0512345678 → 512345678
     * 512345678 → 512345678
     */
    private function normalizePhone(string $phone): string
    {
        $phone = preg_replace('/[\s\-\(\)]/', '', $phone);

        if (str_starts_with($phone, '+966')) {
            $phone = substr($phone, 4);
        } elseif (str_starts_with($phone, '00966')) {
            $phone = substr($phone, 5);
        } elseif (str_starts_with($phone, '0')) {
            $phone = substr($phone, 1);
        }

        return $phone;
    }

    private function findOrCreateUser(Employee $employee): User
    {
        $user = User::where('employee_id', $employee->id)->first();

        if ($user) {
            return $user;
        }

        return User::create([
            'username' => $employee->employee_number,
            'email' => $employee->email,
            'password' => Hash::make($employee->employee_number . $employee->phone),
            'phone' => $employee->phone,
            'full_name' => $employee->full_name_en,
            'full_name_ar' => $employee->full_name_ar,
            'user_type' => 'employee',
            'employee_id' => $employee->id,
            'preferred_language' => 'ar',
            'is_active' => true,
        ]);
    }

    private function formatUserResponse(User $user, ?Employee $employee): array
    {
        $data = [
            'id' => $user->id,
            'username' => $user->username,
            'email' => $user->email,
            'full_name' => $user->full_name,
            'full_name_ar' => $user->full_name_ar,
            'avatar' => $user->avatar,
            'user_type' => $user->user_type,
            'preferred_language' => $user->preferred_language,
            'receive_notifications' => $user->receive_notifications,
        ];

        if ($employee) {
            $data['employee'] = [
                'id' => $employee->id,
                'employee_number' => $employee->employee_number,
                'phone' => $employee->phone,
                'department' => $employee->department ? [
                    'id' => $employee->department->id,
                    'name' => $employee->department->name,
                    'name_ar' => $employee->department->name_ar,
                ] : null,
                'position' => $employee->position ? [
                    'id' => $employee->position->id,
                    'title' => $employee->position->title,
                    'title_ar' => $employee->position->title_ar,
                ] : null,
                'status' => $employee->status,
                'hire_date' => $employee->hire_date?->toDateString(),
                'photo' => $employee->photo,
            ];
        }

        return $data;
    }
}
