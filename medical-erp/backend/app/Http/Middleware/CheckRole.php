<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Usage: middleware('role:super_admin,hr_manager')
     * Allows access if user's user_type matches any of the given roles.
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'يجب تسجيل الدخول أولاً',
            ], 401);
        }

        if (!in_array($user->user_type, $roles, true)) {
            return response()->json([
                'success' => false,
                'message' => 'ليس لديك صلاحية للوصول لهذا المورد',
            ], 403);
        }

        return $next($request);
    }
}
