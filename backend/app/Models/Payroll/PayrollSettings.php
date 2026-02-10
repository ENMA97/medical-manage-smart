<?php

namespace App\Models\Payroll;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayrollSettings extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'key',
        'value',
        'type',
        'description_ar',
        'description_en',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * الإعدادات الافتراضية لنظام الرواتب السعودي
     */
    public const DEFAULTS = [
        // نسب التأمينات الاجتماعية (GOSI)
        'gosi_employee_rate' => [
            'value' => '9.75',
            'type' => 'percentage',
            'description_ar' => 'نسبة التأمينات على الموظف السعودي',
        ],
        'gosi_employer_rate' => [
            'value' => '11.75',
            'type' => 'percentage',
            'description_ar' => 'نسبة التأمينات على صاحب العمل (سعودي)',
        ],
        'gosi_expat_employee_rate' => [
            'value' => '0',
            'type' => 'percentage',
            'description_ar' => 'نسبة التأمينات على الموظف غير السعودي',
        ],
        'gosi_expat_employer_rate' => [
            'value' => '2',
            'type' => 'percentage',
            'description_ar' => 'نسبة التأمينات على صاحب العمل (غير سعودي)',
        ],
        'gosi_max_salary' => [
            'value' => '45000',
            'type' => 'amount',
            'description_ar' => 'الحد الأقصى للراتب الخاضع للتأمينات',
        ],

        // الوقت الإضافي
        'overtime_rate_normal' => [
            'value' => '1.5',
            'type' => 'multiplier',
            'description_ar' => 'معامل الوقت الإضافي العادي (150%)',
        ],
        'overtime_rate_holiday' => [
            'value' => '2',
            'type' => 'multiplier',
            'description_ar' => 'معامل الوقت الإضافي في الإجازات (200%)',
        ],
        'working_days_per_month' => [
            'value' => '30',
            'type' => 'number',
            'description_ar' => 'عدد أيام العمل في الشهر',
        ],
        'working_hours_per_day' => [
            'value' => '8',
            'type' => 'number',
            'description_ar' => 'ساعات العمل اليومية',
        ],

        // خصم التأخير
        'late_deduction_per_minute' => [
            'value' => '0',
            'type' => 'amount',
            'description_ar' => 'خصم الدقيقة الواحدة للتأخير',
        ],
        'late_grace_minutes' => [
            'value' => '15',
            'type' => 'number',
            'description_ar' => 'دقائق السماح للتأخير',
        ],

        // إعدادات WPS
        'wps_employer_id' => [
            'value' => '',
            'type' => 'string',
            'description_ar' => 'رقم المنشأة في نظام حماية الأجور',
        ],
        'wps_mol_id' => [
            'value' => '',
            'type' => 'string',
            'description_ar' => 'رقم وزارة العمل',
        ],

        // العملة
        'default_currency' => [
            'value' => 'SAR',
            'type' => 'string',
            'description_ar' => 'العملة الافتراضية',
        ],

        // مكافأة نهاية الخدمة
        'eos_first_5_years_rate' => [
            'value' => '0.5',
            'type' => 'multiplier',
            'description_ar' => 'نسبة مكافأة نهاية الخدمة (أول 5 سنوات) - نصف شهر',
        ],
        'eos_after_5_years_rate' => [
            'value' => '1',
            'type' => 'multiplier',
            'description_ar' => 'نسبة مكافأة نهاية الخدمة (بعد 5 سنوات) - شهر كامل',
        ],
    ];

    // =============================================================================
    // Static Methods
    // =============================================================================

    /**
     * الحصول على قيمة إعداد
     */
    public static function getValue(string $key, $default = null)
    {
        $setting = self::where('key', $key)->where('is_active', true)->first();

        if (!$setting) {
            return self::DEFAULTS[$key]['value'] ?? $default;
        }

        return match ($setting->type) {
            'percentage', 'multiplier', 'amount' => (float) $setting->value,
            'number' => (int) $setting->value,
            'boolean' => filter_var($setting->value, FILTER_VALIDATE_BOOLEAN),
            default => $setting->value,
        };
    }

    /**
     * تحديث قيمة إعداد
     */
    public static function setValue(string $key, $value): bool
    {
        $default = self::DEFAULTS[$key] ?? null;

        return (bool) self::updateOrCreate(
            ['key' => $key],
            [
                'value' => (string) $value,
                'type' => $default['type'] ?? 'string',
                'description_ar' => $default['description_ar'] ?? $key,
                'is_active' => true,
            ]
        );
    }

    /**
     * تحميل جميع الإعدادات
     */
    public static function getAllSettings(): array
    {
        $settings = [];

        foreach (self::DEFAULTS as $key => $config) {
            $settings[$key] = self::getValue($key);
        }

        return $settings;
    }

    /**
     * إنشاء الإعدادات الافتراضية
     */
    public static function seedDefaults(): void
    {
        foreach (self::DEFAULTS as $key => $config) {
            self::firstOrCreate(
                ['key' => $key],
                [
                    'value' => $config['value'],
                    'type' => $config['type'],
                    'description_ar' => $config['description_ar'],
                    'is_active' => true,
                ]
            );
        }
    }
}
