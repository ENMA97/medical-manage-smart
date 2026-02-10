import React, { useState, useEffect } from 'react';
import { payrollSettingsApi } from '../../services/payrollApi';

/**
 * صفحة إعدادات الرواتب
 * Payroll Settings Page
 */
export default function PayrollSettingsPage() {
  const [settings, setSettings] = useState({});
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState(null);
  const [editingKey, setEditingKey] = useState(null);
  const [editValue, setEditValue] = useState('');

  const categoryLabels = {
    gosi: 'التأمينات الاجتماعية (GOSI)',
    overtime: 'الوقت الإضافي',
    late: 'خصم التأخير',
    wps: 'نظام حماية الأجور (WPS)',
    currency: 'العملة',
    eos: 'مكافأة نهاية الخدمة',
  };

  const categorizeSettings = (settings) => {
    const categories = {
      gosi: [],
      overtime: [],
      late: [],
      wps: [],
      currency: [],
      eos: [],
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
    loadSettings();
  }, []);

  const loadSettings = async () => {
    try {
      setLoading(true);
      const response = await payrollSettingsApi.getAll();
      setSettings(response.data || {});
    } catch (err) {
      setError(err.message);
    } finally {
      setLoading(false);
    }
  };

  const handleEdit = (key, value) => {
    setEditingKey(key);
    setEditValue(value.toString());
  };

  const handleSave = async () => {
    try {
      setSaving(true);
      await payrollSettingsApi.update(editingKey, editValue);
      setSettings((prev) => ({
        ...prev,
        [editingKey]: { ...prev[editingKey], value: parseFloat(editValue) || editValue },
      }));
      setEditingKey(null);
      setEditValue('');
    } catch (err) {
      alert(err.message);
    } finally {
      setSaving(false);
    }
  };

  const handleReset = async () => {
    if (!confirm('هل تريد إعادة تعيين جميع الإعدادات للقيم الافتراضية؟')) return;

    try {
      await payrollSettingsApi.resetDefaults();
      loadSettings();
    } catch (err) {
      alert(err.message);
    }
  };

  const formatValue = (setting) => {
    switch (setting.type) {
      case 'percentage':
        return `${setting.value}%`;
      case 'multiplier':
        return `${setting.value}x`;
      case 'amount':
        return `${setting.value} ريال`;
      case 'number':
        return setting.value;
      default:
        return setting.value;
    }
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
          <p className="text-gray-600 mt-1">تخصيص إعدادات نظام الرواتب</p>
        </div>
        <button
          onClick={handleReset}
          className="px-4 py-2 border border-red-300 text-red-600 rounded-lg hover:bg-red-50"
        >
          إعادة تعيين الافتراضي
        </button>
      </div>

      {/* Settings Categories */}
      <div className="space-y-6">
        {Object.entries(categories).map(([category, items]) => {
          if (items.length === 0) return null;

          return (
            <div key={category} className="bg-white rounded-lg shadow overflow-hidden">
              <div className="px-6 py-4 bg-gray-50 border-b border-gray-200">
                <h2 className="text-lg font-semibold text-gray-900">
                  {categoryLabels[category] || category}
                </h2>
              </div>
              <div className="divide-y divide-gray-200">
                {items.map((setting) => (
                  <div
                    key={setting.key}
                    className="px-6 py-4 flex items-center justify-between hover:bg-gray-50"
                  >
                    <div className="flex-1">
                      <p className="font-medium text-gray-900">{setting.description_ar}</p>
                      <p className="text-sm text-gray-500">
                        {setting.key}
                        {setting.is_custom && (
                          <span className="mr-2 px-2 py-0.5 bg-blue-100 text-blue-600 text-xs rounded">
                            مخصص
                          </span>
                        )}
                      </p>
                    </div>
                    <div className="flex items-center gap-4">
                      {editingKey === setting.key ? (
                        <>
                          <input
                            type="text"
                            value={editValue}
                            onChange={(e) => setEditValue(e.target.value)}
                            className="border border-gray-300 rounded px-3 py-1 w-32 text-left"
                            dir="ltr"
                          />
                          <button
                            onClick={handleSave}
                            disabled={saving}
                            className="px-3 py-1 bg-green-600 text-white rounded hover:bg-green-700 disabled:opacity-50"
                          >
                            {saving ? '...' : 'حفظ'}
                          </button>
                          <button
                            onClick={() => setEditingKey(null)}
                            className="px-3 py-1 border border-gray-300 rounded hover:bg-gray-100"
                          >
                            إلغاء
                          </button>
                        </>
                      ) : (
                        <>
                          <span className="text-lg font-mono text-gray-700 bg-gray-100 px-3 py-1 rounded">
                            {formatValue(setting)}
                          </span>
                          <button
                            onClick={() => handleEdit(setting.key, setting.value)}
                            className="text-blue-600 hover:text-blue-900"
                          >
                            تعديل
                          </button>
                        </>
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
        <h3 className="text-lg font-semibold text-blue-900 mb-4">معلومات مهمة</h3>
        <ul className="list-disc list-inside space-y-2 text-blue-800">
          <li>نسب التأمينات الاجتماعية محدثة حسب أنظمة المؤسسة العامة للتأمينات الاجتماعية</li>
          <li>الحد الأقصى للراتب الخاضع للتأمينات: 45,000 ريال</li>
          <li>نسبة الموظف السعودي: 9.75% | نسبة صاحب العمل: 11.75%</li>
          <li>الموظف غير السعودي: 0% | نسبة صاحب العمل: 2%</li>
          <li>الوقت الإضافي: 150% للأيام العادية | 200% للإجازات والعطل</li>
        </ul>
      </div>
    </div>
  );
}
