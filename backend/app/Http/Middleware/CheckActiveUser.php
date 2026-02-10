<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckActiveUser
{
    /**
     * Handle an incoming request.
     * التحقق من أن المستخدم نشط
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح - يجب تسجيل الدخول',
            ], 401);
        }

        if (!$user->is_active) {
            // إلغاء التوكن الحالي
            $request->user()->currentAccessToken()?->delete();

            return response()->json([
                'success' => false,
                'message' => 'الحساب معطل - يرجى التواصل مع الإدارة',
            ], 403);
        }

        return $next($request);
    }
}
