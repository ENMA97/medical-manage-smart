import React, { useState, useEffect } from 'react';

const defaultSettings = {
  general: {
    facility_name_ar: 'المستشفى الطبي الذكي',
    facility_name_en: 'Smart Medical Hospital',
    timezone: 'Asia/Riyadh',
    default_language: 'ar',
    date_format: 'dd/mm/yyyy',
    currency: 'SAR',
    logo_url: '/logo.png',
  },
  notifications: {
    email_enabled: true,
    sms_enabled: true,
    push_enabled: false,
    smtp_host: 'smtp.medical.sa',
    smtp_port: '587',
    smtp_user: 'noreply@medical.sa',
    smtp_password: '********',
    sms_provider: 'unifonic',
    sms_api_key: '********',
  },
  security: {
    session_timeout: '30',
    max_login_attempts: '5',
    password_min_length: '8',
    password_require_uppercase: true,
    password_require_numbers: true,
    password_require_special: true,
    two_factor_enabled: false,
    ip_whitelist_enabled: false,
    ip_whitelist: '',
  },
  backup: {
    auto_backup_enabled: true,
    backup_frequency: 'daily',
    backup_time: '02:00',
    backup_retention_days: '30',
    backup_location: '/backups',
    last_backup: '2026-02-15 02:00:00',
    last_backup_size: '2.4 GB',
  },
};

const integrations = [
  { key: 'zkteco', name_ar: 'أجهزة البصمة ZKTeco', name_en: 'ZKTeco Biometric', status: 'connected', last_sync: '2026-02-15 08:00', devices: 4, color: 'green' },
  { key: 'gemini', name_ar: 'Google Gemini AI', name_en: 'Google Gemini AI', status: 'connected', last_sync: '2026-02-15 09:30', details: 'نموذج: gemini-pro', color: 'green' },
  { key: 'insurance', name_ar: 'واجهات التأمين', name_en: 'Insurance APIs', status: 'partial', last_sync: '2026-02-14 16:00', details: '3 من 5 شركات متصلة', color: 'yellow' },
  { key: 'payment', name_ar: 'بوابة الدفع', name_en: 'Payment Gateway', status: 'disconnected', last_sync: '-', details: 'لم يتم التكوين بعد', color: 'red' },
];

const categoryLabels = {
  general: 'الإعدادات العامة',
  notifications: 'الإشعارات',
  security: 'الأمان',
  backup: 'النسخ الاحتياطي',
  integrations: 'التكاملات',
};

const statusColors = {
  connected: 'bg-green-100 text-green-700',
  partial: 'bg-yellow-100 text-yellow-700',
  disconnected: 'bg-red-100 text-red-700',
};
const statusLabels = {
  connected: 'متصل',
  partial: 'متصل جزئياً',
  disconnected: 'غير متصل',
};

export default function SystemSettingsPage() {
  const [settings, setSettings] = useState({});
  const [loading, setLoading] = useState(true);
  const [activeCategory, setActiveCategory] = useState('general');
  const [savedMsg, setSavedMsg] = useState('');
  const [editingField, setEditingField] = useState(null);

  useEffect(() => {
    setTimeout(() => { setSettings(defaultSettings); setLoading(false); }, 400);
  }, []);

  const updateSetting = (category, key, value) => {
    setSettings(prev => ({
      ...prev,
      [category]: { ...prev[category], [key]: value }
    }));
  };

  const handleSave = () => {
    setSavedMsg('تم حفظ الإعدادات بنجاح');
    setTimeout(() => setSavedMsg(''), 3000);
  };

  const renderField = (category, key, value, label, type = 'text', options = null) => {
    const isEditing = editingField === `${category}.${key}`;
    const fieldId = `${category}.${key}`;

    if (type === 'boolean') {
      return (
        <div key={key} className="flex items-center justify-between py-3 border-b border-gray-100 last:border-0">
          <div>
            <p className="text-sm font-medium text-gray-700">{label}</p>
          </div>
          <button onClick={() => updateSetting(category, key, !value)}
            className={`relative inline-flex h-6 w-11 items-center rounded-full transition-colors ${value ? 'bg-blue-600' : 'bg-gray-300'}`}>
            <span className={`inline-block h-4 w-4 transform rounded-full bg-white transition-transform ${value ? 'translate-x-1' : 'translate-x-6'}`} />
          </button>
        </div>
      );
    }

    if (type === 'select') {
      return (
        <div key={key} className="flex items-center justify-between py-3 border-b border-gray-100 last:border-0">
          <div>
            <p className="text-sm font-medium text-gray-700">{label}</p>
          </div>
          <select value={value} onChange={e => updateSetting(category, key, e.target.value)}
            className="border rounded-lg px-3 py-1.5 text-sm w-48">
            {options.map(o => <option key={o.value} value={o.value}>{o.label}</option>)}
          </select>
        </div>
      );
    }

    if (type === 'readonly') {
      return (
        <div key={key} className="flex items-center justify-between py-3 border-b border-gray-100 last:border-0">
          <div>
            <p className="text-sm font-medium text-gray-700">{label}</p>
          </div>
          <p className="text-sm text-gray-500">{value}</p>
        </div>
      );
    }

    return (
      <div key={key} className="flex items-center justify-between py-3 border-b border-gray-100 last:border-0">
        <div>
          <p className="text-sm font-medium text-gray-700">{label}</p>
        </div>
        {isEditing ? (
          <div className="flex items-center gap-2">
            <input type={type === 'password' ? 'password' : 'text'} value={value}
              onChange={e => updateSetting(category, key, e.target.value)}
              className="border rounded-lg px-3 py-1.5 text-sm w-48" dir={type === 'password' || key.includes('_en') || key.includes('host') || key.includes('port') || key.includes('user') || key.includes('api') || key.includes('url') || key.includes('ip') || key.includes('location') ? 'ltr' : 'rtl'}
              autoFocus />
            <button onClick={() => setEditingField(null)} className="text-blue-600 hover:text-blue-800 text-sm">تم</button>
          </div>
        ) : (
          <div className="flex items-center gap-2">
            <p className="text-sm text-gray-500" dir={key.includes('_en') || key.includes('host') || key.includes('port') || key.includes('url') || key.includes('location') ? 'ltr' : 'rtl'}>
              {type === 'password' ? '********' : value}
            </p>
            <button onClick={() => setEditingField(fieldId)} className="text-blue-600 hover:text-blue-800 text-xs">تعديل</button>
          </div>
        )}
      </div>
    );
  };

  if (loading) return <div className="flex items-center justify-center h-64"><div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div></div>;

  return (
    <div className="space-y-6" dir="rtl">
      <div className="flex justify-between items-center">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">إعدادات النظام</h1>
          <p className="text-gray-600 mt-1">تكوين إعدادات النظام العامة</p>
        </div>
        <button onClick={handleSave} className="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm">حفظ الإعدادات</button>
      </div>

      {savedMsg && (
        <div className="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg text-sm">{savedMsg}</div>
      )}

      {/* Category Tabs */}
      <div className="bg-white rounded-lg shadow">
        <div className="flex border-b overflow-x-auto">
          {Object.entries(categoryLabels).map(([key, label]) => (
            <button key={key} onClick={() => setActiveCategory(key)}
              className={`px-6 py-3 text-sm font-medium whitespace-nowrap border-b-2 transition-colors ${activeCategory === key ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'}`}>
              {label}
            </button>
          ))}
        </div>
      </div>

      {/* General Settings */}
      {activeCategory === 'general' && (
        <div className="bg-white rounded-lg shadow p-6">
          <h3 className="font-bold text-gray-800 mb-4">الإعدادات العامة</h3>
          <div className="divide-y-0">
            {renderField('general', 'facility_name_ar', settings.general?.facility_name_ar, 'اسم المنشأة (عربي)')}
            {renderField('general', 'facility_name_en', settings.general?.facility_name_en, 'اسم المنشأة (English)')}
            {renderField('general', 'timezone', settings.general?.timezone, 'المنطقة الزمنية', 'select', [
              { value: 'Asia/Riyadh', label: 'الرياض (GMT+3)' },
              { value: 'Asia/Dubai', label: 'دبي (GMT+4)' },
              { value: 'Asia/Kuwait', label: 'الكويت (GMT+3)' },
              { value: 'Africa/Cairo', label: 'القاهرة (GMT+2)' },
            ])}
            {renderField('general', 'default_language', settings.general?.default_language, 'اللغة الافتراضية', 'select', [
              { value: 'ar', label: 'العربية' },
              { value: 'en', label: 'English' },
            ])}
            {renderField('general', 'date_format', settings.general?.date_format, 'تنسيق التاريخ', 'select', [
              { value: 'dd/mm/yyyy', label: 'يوم/شهر/سنة' },
              { value: 'mm/dd/yyyy', label: 'شهر/يوم/سنة' },
              { value: 'yyyy-mm-dd', label: 'سنة-شهر-يوم' },
            ])}
            {renderField('general', 'currency', settings.general?.currency, 'العملة', 'select', [
              { value: 'SAR', label: 'ريال سعودي (SAR)' },
              { value: 'AED', label: 'درهم إماراتي (AED)' },
              { value: 'USD', label: 'دولار أمريكي (USD)' },
            ])}
            {renderField('general', 'logo_url', settings.general?.logo_url, 'رابط الشعار')}
          </div>
        </div>
      )}

      {/* Notifications Settings */}
      {activeCategory === 'notifications' && (
        <div className="space-y-4">
          <div className="bg-white rounded-lg shadow p-6">
            <h3 className="font-bold text-gray-800 mb-4">إعدادات الإشعارات</h3>
            <div className="divide-y-0">
              {renderField('notifications', 'email_enabled', settings.notifications?.email_enabled, 'تفعيل البريد الإلكتروني', 'boolean')}
              {renderField('notifications', 'sms_enabled', settings.notifications?.sms_enabled, 'تفعيل الرسائل النصية', 'boolean')}
              {renderField('notifications', 'push_enabled', settings.notifications?.push_enabled, 'تفعيل الإشعارات الفورية', 'boolean')}
            </div>
          </div>
          <div className="bg-white rounded-lg shadow p-6">
            <h3 className="font-bold text-gray-800 mb-4">إعدادات البريد (SMTP)</h3>
            <div className="divide-y-0">
              {renderField('notifications', 'smtp_host', settings.notifications?.smtp_host, 'خادم SMTP')}
              {renderField('notifications', 'smtp_port', settings.notifications?.smtp_port, 'منفذ SMTP')}
              {renderField('notifications', 'smtp_user', settings.notifications?.smtp_user, 'مستخدم SMTP')}
              {renderField('notifications', 'smtp_password', settings.notifications?.smtp_password, 'كلمة مرور SMTP', 'password')}
            </div>
          </div>
          <div className="bg-white rounded-lg shadow p-6">
            <h3 className="font-bold text-gray-800 mb-4">إعدادات الرسائل النصية</h3>
            <div className="divide-y-0">
              {renderField('notifications', 'sms_provider', settings.notifications?.sms_provider, 'مزود الخدمة', 'select', [
                { value: 'unifonic', label: 'Unifonic' },
                { value: 'twilio', label: 'Twilio' },
                { value: 'msegat', label: 'Msegat' },
              ])}
              {renderField('notifications', 'sms_api_key', settings.notifications?.sms_api_key, 'مفتاح API', 'password')}
            </div>
          </div>
        </div>
      )}

      {/* Security Settings */}
      {activeCategory === 'security' && (
        <div className="bg-white rounded-lg shadow p-6">
          <h3 className="font-bold text-gray-800 mb-4">إعدادات الأمان</h3>
          <div className="divide-y-0">
            {renderField('security', 'session_timeout', settings.security?.session_timeout, 'مهلة الجلسة (دقائق)', 'select', [
              { value: '15', label: '15 دقيقة' },
              { value: '30', label: '30 دقيقة' },
              { value: '60', label: '60 دقيقة' },
              { value: '120', label: '120 دقيقة' },
            ])}
            {renderField('security', 'max_login_attempts', settings.security?.max_login_attempts, 'أقصى محاولات دخول', 'select', [
              { value: '3', label: '3 محاولات' },
              { value: '5', label: '5 محاولات' },
              { value: '10', label: '10 محاولات' },
            ])}
            {renderField('security', 'password_min_length', settings.security?.password_min_length, 'أقل طول لكلمة المرور', 'select', [
              { value: '6', label: '6 أحرف' },
              { value: '8', label: '8 أحرف' },
              { value: '10', label: '10 أحرف' },
              { value: '12', label: '12 حرف' },
            ])}
            {renderField('security', 'password_require_uppercase', settings.security?.password_require_uppercase, 'يتطلب أحرف كبيرة', 'boolean')}
            {renderField('security', 'password_require_numbers', settings.security?.password_require_numbers, 'يتطلب أرقام', 'boolean')}
            {renderField('security', 'password_require_special', settings.security?.password_require_special, 'يتطلب رموز خاصة', 'boolean')}
            {renderField('security', 'two_factor_enabled', settings.security?.two_factor_enabled, 'المصادقة الثنائية', 'boolean')}
            {renderField('security', 'ip_whitelist_enabled', settings.security?.ip_whitelist_enabled, 'تفعيل قائمة IP المسموحة', 'boolean')}
            {settings.security?.ip_whitelist_enabled && renderField('security', 'ip_whitelist', settings.security?.ip_whitelist, 'عناوين IP المسموحة')}
          </div>
        </div>
      )}

      {/* Backup Settings */}
      {activeCategory === 'backup' && (
        <div className="space-y-4">
          <div className="bg-white rounded-lg shadow p-6">
            <h3 className="font-bold text-gray-800 mb-4">إعدادات النسخ الاحتياطي</h3>
            <div className="divide-y-0">
              {renderField('backup', 'auto_backup_enabled', settings.backup?.auto_backup_enabled, 'النسخ التلقائي', 'boolean')}
              {renderField('backup', 'backup_frequency', settings.backup?.backup_frequency, 'تكرار النسخ', 'select', [
                { value: 'hourly', label: 'كل ساعة' },
                { value: 'daily', label: 'يومياً' },
                { value: 'weekly', label: 'أسبوعياً' },
                { value: 'monthly', label: 'شهرياً' },
              ])}
              {renderField('backup', 'backup_time', settings.backup?.backup_time, 'وقت النسخ')}
              {renderField('backup', 'backup_retention_days', settings.backup?.backup_retention_days, 'مدة الاحتفاظ (أيام)', 'select', [
                { value: '7', label: '7 أيام' },
                { value: '14', label: '14 يوم' },
                { value: '30', label: '30 يوم' },
                { value: '60', label: '60 يوم' },
                { value: '90', label: '90 يوم' },
              ])}
              {renderField('backup', 'backup_location', settings.backup?.backup_location, 'مسار التخزين')}
            </div>
          </div>
          <div className="bg-white rounded-lg shadow p-6">
            <h3 className="font-bold text-gray-800 mb-4">حالة النسخ الاحتياطي</h3>
            <div className="grid grid-cols-2 gap-4">
              <div className="bg-gray-50 p-4 rounded-lg">
                <p className="text-xs text-gray-500">آخر نسخ احتياطي</p>
                <p className="text-sm font-medium mt-1">{settings.backup?.last_backup}</p>
              </div>
              <div className="bg-gray-50 p-4 rounded-lg">
                <p className="text-xs text-gray-500">حجم النسخة</p>
                <p className="text-sm font-medium mt-1">{settings.backup?.last_backup_size}</p>
              </div>
            </div>
            <button onClick={() => alert('جاري إنشاء نسخة احتياطية... (محاكاة)')} className="mt-4 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 text-sm w-full">
              إنشاء نسخة احتياطية الآن
            </button>
          </div>
        </div>
      )}

      {/* Integrations */}
      {activeCategory === 'integrations' && (
        <div className="space-y-4">
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            {integrations.map(intg => (
              <div key={intg.key} className="bg-white rounded-lg shadow p-5">
                <div className="flex justify-between items-start mb-3">
                  <div>
                    <h4 className="font-bold text-gray-800">{intg.name_ar}</h4>
                    <p className="text-xs text-gray-500" dir="ltr">{intg.name_en}</p>
                  </div>
                  <span className={`px-2 py-1 text-xs rounded-full ${statusColors[intg.status]}`}>{statusLabels[intg.status]}</span>
                </div>
                <div className="space-y-2 text-sm">
                  <div className="flex justify-between">
                    <span className="text-gray-500">آخر مزامنة:</span>
                    <span className="text-gray-700">{intg.last_sync}</span>
                  </div>
                  {intg.devices && (
                    <div className="flex justify-between">
                      <span className="text-gray-500">الأجهزة:</span>
                      <span className="text-gray-700">{intg.devices} أجهزة</span>
                    </div>
                  )}
                  {intg.details && (
                    <div className="flex justify-between">
                      <span className="text-gray-500">تفاصيل:</span>
                      <span className="text-gray-700">{intg.details}</span>
                    </div>
                  )}
                </div>
                <div className="mt-4 pt-3 border-t flex gap-2">
                  {intg.status === 'connected' ? (
                    <>
                      <button className="px-3 py-1.5 text-xs bg-blue-50 text-blue-700 rounded-lg hover:bg-blue-100">إعادة مزامنة</button>
                      <button className="px-3 py-1.5 text-xs bg-gray-50 text-gray-700 rounded-lg hover:bg-gray-100">إعدادات</button>
                      <button className="px-3 py-1.5 text-xs bg-red-50 text-red-700 rounded-lg hover:bg-red-100">قطع الاتصال</button>
                    </>
                  ) : intg.status === 'partial' ? (
                    <>
                      <button className="px-3 py-1.5 text-xs bg-blue-50 text-blue-700 rounded-lg hover:bg-blue-100">إعدادات</button>
                      <button className="px-3 py-1.5 text-xs bg-yellow-50 text-yellow-700 rounded-lg hover:bg-yellow-100">إكمال التكوين</button>
                    </>
                  ) : (
                    <button className="px-3 py-1.5 text-xs bg-green-50 text-green-700 rounded-lg hover:bg-green-100">تكوين وتوصيل</button>
                  )}
                </div>
              </div>
            ))}
          </div>

          {/* Integration Summary */}
          <div className="bg-white rounded-lg shadow p-6">
            <h3 className="font-bold text-gray-800 mb-4">ملخص التكاملات</h3>
            <div className="grid grid-cols-3 gap-4">
              <div className="bg-green-50 p-4 rounded-lg text-center">
                <p className="text-2xl font-bold text-green-700">{integrations.filter(i => i.status === 'connected').length}</p>
                <p className="text-xs text-green-600 mt-1">متصل</p>
              </div>
              <div className="bg-yellow-50 p-4 rounded-lg text-center">
                <p className="text-2xl font-bold text-yellow-700">{integrations.filter(i => i.status === 'partial').length}</p>
                <p className="text-xs text-yellow-600 mt-1">متصل جزئياً</p>
              </div>
              <div className="bg-red-50 p-4 rounded-lg text-center">
                <p className="text-2xl font-bold text-red-700">{integrations.filter(i => i.status === 'disconnected').length}</p>
                <p className="text-xs text-red-600 mt-1">غير متصل</p>
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
