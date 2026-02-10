<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * الللغات المدعومة
     */
    protected array $supportedLocales = ['ar', 'en'];

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->getLocale($request);

        App::setLocale($locale);

        $response = $next($request);

        // إضافة هيدر اللغة في الاستجابة
        if ($response instanceof Response) {
            $response->headers->set('Content-Language', $locale);
        }

        return $response;
    }

    /**
     * تحديد اللغة من الطلب
     */
    protected function getLocale(Request $request): string
    {
        // أولاً: من هيدر Accept-Language
        $locale = $request->header('Accept-Language');

        if ($locale && in_array($locale, $this->supportedLocales)) {
            return $locale;
        }

        // ثانياً: من كويري بارامتر
        $locale = $request->query('lang');

        if ($locale && in_array($locale, $this->supportedLocales)) {
            return $locale;
        }

        // ثالثاً: من إعدادات المستخدم
        if ($request->user() && $request->user()->preferred_locale) {
            $userLocale = $request->user()->preferred_locale;
            if (in_array($userLocale, $this->supportedLocales)) {
                return $userLocale;
            }
        }

        // الافتراضي: العربية
        return 'ar';
    }
}
