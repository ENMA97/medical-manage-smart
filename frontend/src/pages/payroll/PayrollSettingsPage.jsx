import React, { useState, useEffect } from 'react';

/**
 * صفحة إعدادات الرواتب
 * Payroll Settings Page
 */

const mockSettings = {
  gosi_employee_rate: { value: 9.75, type: 'percentage', description_ar: 'نسبة اشتراك الموظف السعودي', is_custom: false },
  gosi_employer_rate: { value: 11.75, type: 'percentage', description_ar: 'نسبة اشتراك صاحب العمل (سعودي)', is_custom: false },
  gosi_non_saudi_employer: { value: 2, type: 'percentage', description_ar: 'نسبة اشتراك صاحب العمل (غير سعودي)', is_custom: false },
  gosi_max_salary: { value: 45000, type: 'amount', description_ar: 'الحد الأقصى للراتب الخاضع للتأمينات', is_custom: false },
  gosi_saned_rate: { value: 1.5, type: 'percentage', description_ar: 'نسبة اشتراك ساند (التعطل عن العمل)', is_custom: false },
  overtime_normal_rate: { value: 1.5, type: 'multiplier', description_ar: 'معامل الوقت الإضافي (أيام عادية)', is_custom: false },
  overtime_holiday_rate: { value: 2, type: 'multiplier', description_ar: 'معامل الوقت الإضافي (إجازات وعطل)', is_custom: false },
  working_hours_per_day: { value: 8, type: 'number', description_ar: 'ساعات العمل اليومية', is_custom: false },
  working_days_per_month: { value: 30, type: 'number', description_ar: 'أيام العمل الشهرية', is_custom: false },
  late_deduction_per_minute: { value: 5, type: 'amount', description_ar: 'خصم التأخير لكل دقيقة (ريال)', is_custom: true },
  late_grace_minutes: { value: 15, type: 'number', description_ar: 'فترة السماح للتأخير (دقائق)', is_custom: true },
  wps_enabled: { value: 'نعم', type: 'text', description_ar: 'تفعيل نظام حماية الأجور', is_custom: false },
  wps_bank_code: { value: 'RJHI', type: 'text', description_ar: 'رمز البنك في نظام WPS', is_custom: true },
  wps_file_format: { value: 'SIF', type: 'text', description_ar: 'صيغة ملف WPS', is_custom: false },
  currency_primary: { value: 'SAR', type: 'text', description_ar: 'العملة الأساسية', is_custom: false },
  currency_decimal_places: { value: 2, type: 'number', description_ar: 'عدد الخانات العشرية', is_custom: false },
  eos_first_5_years_rate: { value: 0.5, type: 'multiplier', description_ar: 'معامل مكافأة نهاية الخدمة (أول 5 سنوات)', is_custom: false },
  eos_after_5_years_rate: { value: 1, type: 'multiplier', description_ar: 'معامل مكافأة نهاية الخدمة (بعد 5 سنوات)', is_custom: false },
  eos_resignation_less_2: { value: 0, type: 'percentage', description_ar: 'نسبة المكافأة عند الاستقالة (أقل من سنتين)', is_custom: false },
  eos_resignation_2_to_5: { value: 33.33, type: 'percentage', description_ar: 'نسبة المكافأة عند الاستقالة (2-5 سنوات)', is_custom: false },
  eos_resignation_5_to_10: { value: 66.67, type: 'percentage', description_ar: 'نسبة المكافأة عند الاستقالة (5-10 سنوات)', is_custom: false },
  eos_resignation_over_10: { value: 100, type: 'percentage', description_ar: 'نسبة المكافأة عند الاستقالة (أكثر من 10 سنوات)', is_custom: false },
};

const categoryLabels = {
  gosi: 'التأمينات الاجتماعية (GOSI)',
  overtime: 'الوقت الإضافي',
  late: 'خصم التأخير',
  wps: 'نظام حماية الأجور (WPS)',
  currency: 'العملة',
  eos: 'مكافأة نهاية الخدمة',
};

const categoryIcons = {
  gosi: '🏛️',
  overtime: '⏰',
  late: '⏳',
  wps: '🏦',
  currency: '💰',
  eos: '🎁',
};

export default function PayrollSettingsPage() {
  const [settings, setSettings] = useState({});
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [editingKey, setEditingKey] = useState(null);
  const [editValue, setEditValue] = useState('');
  const [successMessage, setSuccessMessage] = useState('');

  const categorizeSettings = (settings) => {
    const categories = {
      gosi: [], overtime: [], late: [], wps: [], currency: [], eos: [],
    };

    Object.entries(settings).forEach(([key, setting]) => {
      if (key.startsWith('gosi_')) categories.gosi.push({ key, ...setting });
      else if (key.includes('overtime') || key.includes('working')) categories.overtime.push({ key, ...setting });
      else if (key.includes('late')) categories.late.push({ key, ...setting });
      else if (key.includes('wps')) categories.wps.push({ key, ...setting });
      else if (key.includes('currency')) categories.currency.push({ key, ...setting });
      else if (key.includes('eos')) categories.eos.push({ key, ...setting });
    });

    return categories;
  };

  useEffect(() => {
    setLoading(true);
    setTimeout(() => {
      setSettings(mockSettings);
      setLoading(false);
    }, 400);
  }, []);

  const handleEdit = (key, value) => {
    setEditingKey(key);
    setEditValue(value.toString());
  };

  const handleSave = () => {
    setSaving(true);
    setTimeout(() => {
      setSettings(prev => ({
        ...prev,
        [editingKey]: { ...prev[editingKey], value: isNaN(editValue) ? editValue : parseFloat(editValue), is_custom: true },
      }));
      setEditingKey(null);
      setEditValue('');
      setSaving(false);
      setSuccessMessage('تم حفظ الإعداد بنجاح');
      setTimeout(() => setSuccessMessage(''), 3000);
    }, 500);
  };

  const handleReset = () => {
    if (!confirm('هل تريد إعادة تعيين جميع الإعدادات للقيم الافتراضية؟')) return;
    setSettings(mockSettings);
    setSuccessMessage('تم إعادة تعيين الإعدادات');
    setTimeout(() => setSuccessMessage(''), 3000);
  };

  const formatValue = (setting) => {
    switch (setting.type) {
      case 'percentage': return `${setting.value}%`;
      case 'multiplier': return `${setting.value}x`;
      case 'amount': return `${new Intl.NumberFormat('ar-SA').format(setting.value)} ريال`;
      case 'number': return setting.value;
      default: return setting.value;
    }
  };

  const getInputType = (type) => {
    if (type === 'text') return 'text';
    return 'number';
  };

  const categories = categorizeSettings(settings);

  if (loading) {
    return (
      <div className="flex items-center justify-center h-64">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
      </div>
    );
  }

  return (
    <div className="space-y-6" dir="rtl">
      {/* Header */}
      <div className="flex justify-between items-center">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">إعدادات الرواتب</h1>
          <p className="text-gray-600 mt-1">تخصيص إعدادات نظام الرواتب والتأمينات</p>
        </div>
        <button onClick={handleReset}
          className="px-4 py-2 border border-red-300 text-red-600 rounded-lg hover:bg-red-50 text-sm">
          إعادة تعيين الافتراضي
        </button>
      </div>

      {/* Success Message */}
      {successMessage && (
        <div className="bg-green-50 border border-green-200 rounded-lg p-4 flex items-center gap-3">
          <svg className="w-5 h-5 text-green-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <span className="text-green-800 text-sm">{successMessage}</span>
        </div>
      )}

      {/* Settings Categories */}
      <div className="space-y-6">
        {Object.entries(categories).map(([category, items]) => {
          if (items.length === 0) return null;

          return (
            <div key={category} className="bg-white rounded-lg shadow overflow-hidden">
              <div className="px-6 py-4 bg-gray-50 border-b border-gray-200 flex items-center gap-3">
                <span className="text-xl">{categoryIcons[category]}</span>
                <div>
                  <h2 className="text-lg font-semibold text-gray-900">
                    {categoryLabels[category]}
                  </h2>
                  <p className="text-xs text-gray-500">{items.length} إعداد</p>
                </div>
              </div>
              <div className="divide-y divide-gray-200">
                {items.map(setting => (
                  <div key={setting.key}
                    className="px-6 py-4 flex flex-col sm:flex-row sm:items-center justify-between hover:bg-gray-50 gap-3">
                    <div className="flex-1">
                      <div className="flex items-center gap-2">
                        <p className="font-medium text-gray-900">{setting.description_ar}</p>
                        {setting.is_custom && (
                          <span className="px-2 py-0.5 bg-blue-100 text-blue-600 text-xs rounded">مخصص</span>
                        )}
                      </div>
                      <p className="text-xs text-gray-400 font-mono mt-1">{setting.key}</p>
                    </div>
                    <div className="flex items-center gap-3">
                      {editingKey === setting.key ? (
                        <div className="flex items-center gap-2">
                          <input
                            type={getInputType(setting.type)}
                            value={editValue}
                            onChange={(e) => setEditValue(e.target.value)}
                            className="border border-blue-300 rounded-lg px-3 py-1.5 w-32 text-left text-sm focus:ring-2 focus:ring-blue-500"
                            dir="ltr" autoFocus step="any"
                          />
                          <button onClick={handleSave} disabled={saving}
                            className="px-3 py-1.5 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:opacity-50 text-sm">
                            {saving ? '...' : 'حفظ'}
                          </button>
                          <button onClick={() => setEditingKey(null)}
                            className="px-3 py-1.5 border border-gray-300 rounded-lg hover:bg-gray-100 text-sm">
                            إلغاء
                          </button>
                        </div>
                      ) : (
                        <div className="flex items-center gap-3">
                          <span className="text-lg font-mono text-gray-700 bg-gray-100 px-4 py-1.5 rounded-lg min-w-[80px] text-center">
                            {formatValue(setting)}
                          </span>
                          <button onClick={() => handleEdit(setting.key, setting.value)}
                            className="p-1.5 text-blue-600 hover:text-blue-900 hover:bg-blue-50 rounded-lg transition-colors">
                            <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                            </svg>
                          </button>
                        </div>
                      )}
                    </div>
                  </div>
                ))}
              </div>
            </div>
          );
        })}
      </div>

      {/* Info Section */}
      <div className="bg-blue-50 rounded-lg p-6">
        <h3 className="text-lg font-semibold text-blue-900 mb-4">معلومات مهمة - نظام العمل السعودي</h3>
        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <h4 className="font-medium text-blue-800 mb-2">التأمينات الاجتماعية</h4>
            <ul className="list-disc list-inside space-y-1 text-sm text-blue-800">
              <li>الحد الأقصى للراتب الخاضع: 45,000 ريال</li>
              <li>نسبة الموظف السعودي: 9.75%</li>
              <li>نسبة صاحب العمل (سعودي): 11.75%</li>
              <li>نسبة صاحب العمل (غير سعودي): 2%</li>
              <li>اشتراك ساند: 1.5% (الموظف + صاحب العمل)</li>
            </ul>
          </div>
          <div>
            <h4 className="font-medium text-blue-800 mb-2">مكافأة نهاية الخدمة</h4>
            <ul className="list-disc list-inside space-y-1 text-sm text-blue-800">
              <li>أول 5 سنوات: نصف راتب شهر عن كل سنة</li>
              <li>بعد 5 سنوات: راتب شهر عن كل سنة</li>
              <li>استقالة أقل من سنتين: لا مكافأة</li>
              <li>استقالة 2-5 سنوات: ثلث المكافأة</li>
              <li>استقالة 5-10 سنوات: ثلثي المكافأة</li>
              <li>استقالة أكثر من 10: المكافأة كاملة</li>
            </ul>
          </div>
        </div>
      </div>

      {/* WPS Info */}
      <div className="bg-yellow-50 rounded-lg p-6">
        <h3 className="text-lg font-semibold text-yellow-900 mb-3">نظام حماية الأجور (WPS)</h3>
        <p className="text-sm text-yellow-800 mb-3">
          نظام حماية الأجور هو نظام إلكتروني يتيح لوزارة الموارد البشرية والتنمية الاجتماعية التأكد من التزام المنشآت بدفع الأجور في الوقت المحدد.
        </p>
        <div className="flex flex-wrap gap-4 text-sm">
          <div className="bg-white rounded-lg p-3 border border-yellow-200">
            <p className="text-gray-500 text-xs">صيغة الملف</p>
            <p className="font-medium">SIF (Salary Information File)</p>
          </div>
          <div className="bg-white rounded-lg p-3 border border-yellow-200">
            <p className="text-gray-500 text-xs">الموعد النهائي</p>
            <p className="font-medium">آخر يوم عمل في الشهر</p>
          </div>
          <div className="bg-white rounded-lg p-3 border border-yellow-200">
            <p className="text-gray-500 text-xs">الحالة</p>
            <p className="font-medium text-green-600">مفعّل</p>
          </div>
        </div>
      </div>
    </div>
  );
}
