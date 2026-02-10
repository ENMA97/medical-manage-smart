<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class LogApiRequest
{
    /**
     * المسارات المستثناة من التسجيل
     */
    protected array $excludePaths = [
        'api/health',
        'api/status',
    ];

    /**
     * الحقول الحساسة التي لا يتم تسجيلها
     */
    protected array $sensitiveFields = [
        'password',
        'password_confirmation',
        'current_password',
        'new_password',
        'token',
        'api_key',
        'secret',
        'credentials',
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);

        $response = $next($request);

        $this->logRequest($request, $response, $startTime);

        return $response;
    }

    /**
     * تسجيل الطلب
     */
    protected function logRequest(Request $request, Response $response, float $startTime): void
    {
        // تخطي المسارات المستثناة
        if ($this->shouldSkip($request)) {
            return;
        }

        $duration = round((microtime(true) - $startTime) * 1000, 2);

        $logData = [
            'method' => $request->method(),
            'path' => $request->path(),
            'status' => $response->getStatusCode(),
            'duration_ms' => $duration,
            'ip' => $request->ip(),
            'user_id' => $request->user()?->id,
            'user_agent' => substr($request->userAgent() ?? '', 0, 200),
        ];

        // إضافة البارامترات (مع إخفاء الحساسة)
        if ($request->method() !== 'GET') {
            $logData['params'] = $this->sanitizeInput($request->all());
        }

        // تحديد مستوى التسجيل حسب الحالة
        if ($response->getStatusCode() >= 500) {
            Log::error('API Request Error', $logData);
        } elseif ($response->getStatusCode() >= 400) {
            Log::warning('API Request Warning', $logData);
        } else {
            Log::info('API Request', $logData);
        }
    }

    /**
     * التحقق من تخطي المسار
     */
    protected function shouldSkip(Request $request): bool
    {
        foreach ($this->excludePaths as $path) {
            if ($request->is($path)) {
                return true;
            }
        }

        return false;
    }

    /**
     * إخفاء البيانات الحساسة
     */
    protected function sanitizeInput(array $input): array
    {
        foreach ($this->sensitiveFields as $field) {
            if (isset($input[$field])) {
                $input[$field] = '***HIDDEN***';
            }
        }

        return $input;
    }
}
