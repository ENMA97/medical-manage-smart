<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureJsonResponse
{
    /**
     * Handle an incoming request.
     * التأكد من أن الطلبات والاستجابات بصيغة JSON
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // إجبار قبول JSON
        if (!$request->wantsJson() && $request->isMethod('GET') === false) {
            $request->headers->set('Accept', 'application/json');
        }

        $response = $next($request);

        // إضافة هيدرات الأمان
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-XSS-Protection', '1; mode=block');

        return $response;
    }
}
