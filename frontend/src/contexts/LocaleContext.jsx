import React, { createContext, useContext, useState, useEffect, useCallback } from 'react';

const LocaleContext = createContext(null);

// Translation dictionaries
const translations = {
  ar: {
    // Common
    'app.name': 'نظام تخطيط الموارد الطبية الذكي',
    'app.shortName': 'Medical ERP',
    'common.save': 'حفظ',
    'common.cancel': 'إلغاء',
    'common.delete': 'حذف',
    'common.edit': 'تعديل',
    'common.add': 'إضافة',
    'common.search': 'بحث',
    'common.filter': 'تصفية',
    'common.export': 'تصدير',
    'common.import': 'استيراد',
    'common.print': 'طباعة',
    'common.refresh': 'تحديث',
    'common.loading': 'جاري التحميل...',
    'common.noData': 'لا توجد بيانات',
    'common.confirm': 'تأكيد',
    'common.back': 'رجوع',
    'common.next': 'التالي',
    'common.previous': 'السابق',
    'common.submit': 'إرسال',
    'common.reset': 'إعادة تعيين',
    'common.close': 'إغلاق',
    'common.yes': 'نعم',
    'common.no': 'لا',
    'common.all': 'الكل',
    'common.actions': 'الإجراءات',
    'common.status': 'الحالة',
    'common.date': 'التاريخ',
    'common.view': 'عرض',
    'common.details': 'التفاصيل',
    'common.required': 'مطلوب',
    'common.optional': 'اختياري',

    // Auth
    'auth.login': 'تسجيل الدخول',
    'auth.logout': 'تسجيل الخروج',
    'auth.email': 'البريد الإلكتروني',
    'auth.password': 'كلمة المرور',
    'auth.rememberMe': 'تذكرني',
    'auth.forgotPassword': 'نسيت كلمة المرور؟',
    'auth.loginFailed': 'فشل تسجيل الدخول',
    'auth.invalidCredentials': 'بيانات الاعتماد غير صحيحة',

    // Navigation
    'nav.dashboard': 'لوحة التحكم',
    'nav.hr': 'الموارد البشرية',
    'nav.employees': 'الموظفين',
    'nav.departments': 'الأقسام',
    'nav.positions': 'المناصب',
    'nav.contracts': 'العقود',
    'nav.custodies': 'العهد',
    'nav.clearance': 'إخلاء الطرف',
    'nav.leaves': 'الإجازات',
    'nav.leaveRequests': 'طلبات الإجازة',
    'nav.leaveDecisions': 'قرارات الإجازة',
    'nav.leaveBalances': 'أرصدة الإجازات',
    'nav.leaveTypes': 'أنواع الإجازات',
    'nav.payroll': 'الرواتب',
    'nav.payrollList': 'قائمة الرواتب',
    'nav.loans': 'السلف',
    'nav.payrollSettings': 'إعدادات الرواتب',
    'nav.inventory': 'المخزون',
    'nav.warehouses': 'المستودعات',
    'nav.items': 'الأصناف',
    'nav.movements': 'الحركات',
    'nav.quotas': 'الحصص',
    'nav.purchases': 'طلبات الشراء',
    'nav.roster': 'الجداول',
    'nav.shifts': 'الورديات',
    'nav.attendance': 'الحضور',
    'nav.swaps': 'تبديل الورديات',
    'nav.finance': 'المالية',
    'nav.costCenters': 'مراكز التكلفة',
    'nav.doctors': 'الأطباء',
    'nav.services': 'الخدمات',
    'nav.claims': 'المطالبات',
    'nav.reports': 'التقارير',
    'nav.system': 'النظام',
    'nav.users': 'المستخدمين',
    'nav.roles': 'الأدوار',
    'nav.permissions': 'الصلاحيات',
    'nav.auditLogs': 'سجل المراجعة',
    'nav.settings': 'الإعدادات',

    // Statuses
    'status.draft': 'مسودة',
    'status.pending': 'قيد الانتظار',
    'status.approved': 'معتمد',
    'status.rejected': 'مرفوض',
    'status.cancelled': 'ملغي',
    'status.completed': 'مكتمل',
    'status.active': 'نشط',
    'status.inactive': 'غير نشط',

    // Errors
    'error.general': 'حدث خطأ. يرجى المحاولة مرة أخرى.',
    'error.network': 'خطأ في الاتصال بالشبكة',
    'error.unauthorized': 'غير مصرح لك بالوصول',
    'error.notFound': 'العنصر غير موجود',
    'error.validation': 'يرجى التحقق من البيانات المدخلة',

    // Success
    'success.saved': 'تم الحفظ بنجاح',
    'success.deleted': 'تم الحذف بنجاح',
    'success.updated': 'تم التحديث بنجاح',
    'success.created': 'تم الإنشاء بنجاح',
  },
  en: {
    // Common
    'app.name': 'Medical ERP Smart',
    'app.shortName': 'Medical ERP',
    'common.save': 'Save',
    'common.cancel': 'Cancel',
    'common.delete': 'Delete',
    'common.edit': 'Edit',
    'common.add': 'Add',
    'common.search': 'Search',
    'common.filter': 'Filter',
    'common.export': 'Export',
    'common.import': 'Import',
    'common.print': 'Print',
    'common.refresh': 'Refresh',
    'common.loading': 'Loading...',
    'common.noData': 'No data available',
    'common.confirm': 'Confirm',
    'common.back': 'Back',
    'common.next': 'Next',
    'common.previous': 'Previous',
    'common.submit': 'Submit',
    'common.reset': 'Reset',
    'common.close': 'Close',
    'common.yes': 'Yes',
    'common.no': 'No',
    'common.all': 'All',
    'common.actions': 'Actions',
    'common.status': 'Status',
    'common.date': 'Date',
    'common.view': 'View',
    'common.details': 'Details',
    'common.required': 'Required',
    'common.optional': 'Optional',

    // Auth
    'auth.login': 'Login',
    'auth.logout': 'Logout',
    'auth.email': 'Email',
    'auth.password': 'Password',
    'auth.rememberMe': 'Remember me',
    'auth.forgotPassword': 'Forgot password?',
    'auth.loginFailed': 'Login failed',
    'auth.invalidCredentials': 'Invalid credentials',

    // Navigation
    'nav.dashboard': 'Dashboard',
    'nav.hr': 'Human Resources',
    'nav.employees': 'Employees',
    'nav.departments': 'Departments',
    'nav.positions': 'Positions',
    'nav.contracts': 'Contracts',
    'nav.custodies': 'Custodies',
    'nav.clearance': 'Clearance',
    'nav.leaves': 'Leaves',
    'nav.leaveRequests': 'Leave Requests',
    'nav.leaveDecisions': 'Leave Decisions',
    'nav.leaveBalances': 'Leave Balances',
    'nav.leaveTypes': 'Leave Types',
    'nav.payroll': 'Payroll',
    'nav.payrollList': 'Payroll List',
    'nav.loans': 'Loans',
    'nav.payrollSettings': 'Payroll Settings',
    'nav.inventory': 'Inventory',
    'nav.warehouses': 'Warehouses',
    'nav.items': 'Items',
    'nav.movements': 'Movements',
    'nav.quotas': 'Quotas',
    'nav.purchases': 'Purchase Requests',
    'nav.roster': 'Roster',
    'nav.shifts': 'Shifts',
    'nav.attendance': 'Attendance',
    'nav.swaps': 'Shift Swaps',
    'nav.finance': 'Finance',
    'nav.costCenters': 'Cost Centers',
    'nav.doctors': 'Doctors',
    'nav.services': 'Services',
    'nav.claims': 'Claims',
    'nav.reports': 'Reports',
    'nav.system': 'System',
    'nav.users': 'Users',
    'nav.roles': 'Roles',
    'nav.permissions': 'Permissions',
    'nav.auditLogs': 'Audit Logs',
    'nav.settings': 'Settings',

    // Statuses
    'status.draft': 'Draft',
    'status.pending': 'Pending',
    'status.approved': 'Approved',
    'status.rejected': 'Rejected',
    'status.cancelled': 'Cancelled',
    'status.completed': 'Completed',
    'status.active': 'Active',
    'status.inactive': 'Inactive',

    // Errors
    'error.general': 'An error occurred. Please try again.',
    'error.network': 'Network connection error',
    'error.unauthorized': 'You are not authorized to access this',
    'error.notFound': 'Item not found',
    'error.validation': 'Please check your input',

    // Success
    'success.saved': 'Saved successfully',
    'success.deleted': 'Deleted successfully',
    'success.updated': 'Updated successfully',
    'success.created': 'Created successfully',
  },
};

export function LocaleProvider({ children }) {
  const [locale, setLocale] = useState(() => {
    const saved = localStorage.getItem('locale');
    return saved || 'ar';
  });

  const [direction, setDirection] = useState(locale === 'ar' ? 'rtl' : 'ltr');

  useEffect(() => {
    localStorage.setItem('locale', locale);
    setDirection(locale === 'ar' ? 'rtl' : 'ltr');
    document.documentElement.lang = locale;
    document.documentElement.dir = locale === 'ar' ? 'rtl' : 'ltr';
  }, [locale]);

  const t = useCallback(
    (key, params = {}) => {
      let text = translations[locale]?.[key] || translations['en']?.[key] || key;

      // Replace parameters
      Object.keys(params).forEach((param) => {
        text = text.replace(new RegExp(`{${param}}`, 'g'), params[param]);
      });

      return text;
    },
    [locale]
  );

  const toggleLocale = useCallback(() => {
    setLocale((prev) => (prev === 'ar' ? 'en' : 'ar'));
  }, []);

  const value = {
    locale,
    setLocale,
    direction,
    isRTL: direction === 'rtl',
    t,
    toggleLocale,
  };

  return (
    <LocaleContext.Provider value={value}>
      {children}
    </LocaleContext.Provider>
  );
}

export function useLocale() {
  const context = useContext(LocaleContext);
  if (!context) {
    throw new Error('useLocale must be used within a LocaleProvider');
  }
  return context;
}

export default LocaleContext;
