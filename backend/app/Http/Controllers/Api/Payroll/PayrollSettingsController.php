<?php

namespace App\Http\Controllers\Api\Payroll;

use App\Http\Controllers\Controller;
use App\Models\Payroll\PayrollSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PayrollSettingsController extends Controller
{
    /**
     * عرض جميع الإعدادات
     */
    public function index(): JsonResponse
    {
        $settings = [];

        foreach (PayrollSettings::DEFAULTS as $key => $config) {
            $dbSetting = PayrollSettings::where('key', $key)->first();

            $settings[$key] = [
                'key' => $key,
                'value' => PayrollSettings::getValue($key),
                'type' => $config['type'],
                'description_ar' => $config['description_ar'],
                'is_custom' => $dbSetting !== null,
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $settings,
        ]);
    }

    /**
     * تحديث إعداد
     */
    public function update(string $key, Request $request): JsonResponse
    {
        if (!array_key_exists($key, PayrollSettings::DEFAULTS)) {
            return response()->json([
                'success' => false,
                'message' => 'إعداد غير معروف',
            ], 404);
        }

        $request->validate([
            'value' => 'required',
        ]);

        $config = PayrollSettings::DEFAULTS[$key];

        // التحقق من نوع القيمة
        $value = $request->value;
        $isValid = match ($config['type']) {
            'percentage' => is_numeric($value) && $value >= 0 && $value <= 100,
            'multiplier' => is_numeric($value) && $value >= 0,
            'amount' => is_numeric($value) && $value >= 0,
            'number' => is_numeric($value) && $value >= 0,
            default => true,
        };

        if (!$isValid) {
            return response()->json([
                'success' => false,
                'message' => 'قيمة غير صالحة لهذا النوع',
            ], 400);
        }

        PayrollSettings::setValue($key, $value);

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث الإعداد بنجاح',
            'data' => [
                'key' => $key,
                'value' => PayrollSettings::getValue($key),
            ],
        ]);
    }

    /**
     * إعادة تعيين الإعدادات الافتراضية
     */
    public function resetDefaults(): JsonResponse
    {
        PayrollSettings::truncate();
        PayrollSettings::seedDefaults();

        return response()->json([
            'success' => true,
            'message' => 'تم إعادة تعيين الإعدادات الافتراضية',
        ]);
    }
}
