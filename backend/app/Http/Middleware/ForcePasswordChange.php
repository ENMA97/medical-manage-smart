<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForcePasswordChange
{
    /**
     * المسارات المستثناة (تسمح بتغيير كلمة المرور)
     */
    protected array $excludePaths = [
        'api/auth/change-password',
        'api/auth/logout',
        'api/auth/me',
    ];

    /**
     * Handle an incoming request.
     * إجبار المستخدم على تغيير كلمة المرور
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return $next($request);
        }

        // تخطي المسارات المستثناة
        foreach ($this->excludePaths as $path) {
            if ($request->is($path)) {
                return $next($request);
            }
        }

        // التحقق من إجبار تغيير كلمة المرور
        if ($user->must_change_password) {
            return response()->json([
                'success' => false,
                'message' => 'يجب تغيير كلمة المرور قبل المتابعة',
                'action_required' => 'change_password',
            ], 403);
        }

        return $next($request);
    }
}
