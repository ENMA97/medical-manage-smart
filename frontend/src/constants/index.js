/**
 * الثوابت المشتركة للتطبيق
 * Shared constants for the application
 */

// =============================================================================
// أنواع العقود - Contract Types
// =============================================================================
export const CONTRACT_TYPES = {
  full_time: { label_ar: 'دوام كامل', label_en: 'Full Time', color: 'bg-green-100 text-green-700' },
  part_time: { label_ar: 'دوام جزئي', label_en: 'Part Time', color: 'bg-blue-100 text-blue-700' },
  tamheer: { label_ar: 'تمهير', label_en: 'Tamheer', color: 'bg-purple-100 text-purple-700' },
  percentage: { label_ar: 'نسبة', label_en: 'Percentage', color: 'bg-yellow-100 text-yellow-700' },
  locum: { label_ar: 'مناوب', label_en: 'Locum', color: 'bg-orange-100 text-orange-700' },
};

// =============================================================================
// حالات الموظفين - Employee Status
// =============================================================================
export const EMPLOYEE_STATUS = {
  active: { label_ar: 'نشط', label_en: 'Active', color: 'bg-green-100 text-green-700' },
  inactive: { label_ar: 'غير نشط', label_en: 'Inactive', color: 'bg-gray-100 text-gray-700' },
  suspended: { label_ar: 'موقوف', label_en: 'Suspended', color: 'bg-red-100 text-red-700' },
  on_leave: { label_ar: 'في إجازة', label_en: 'On Leave', color: 'bg-yellow-100 text-yellow-700' },
  terminated: { label_ar: 'منتهي', label_en: 'Terminated', color: 'bg-red-100 text-red-700' },
};

// =============================================================================
// حالات العهد - Custody Status
// =============================================================================
export const CUSTODY_STATUS = {
  assigned: { label_ar: 'مُسلَّمة', label_en: 'Assigned', color: 'bg-blue-100 text-blue-700' },
  returned: { label_ar: 'مُستردة', label_en: 'Returned', color: 'bg-green-100 text-green-700' },
  lost: { label_ar: 'مفقودة', label_en: 'Lost', color: 'bg-red-100 text-red-700' },
  damaged: { label_ar: 'تالفة', label_en: 'Damaged', color: 'bg-orange-100 text-orange-700' },
};

// =============================================================================
// حالات إخلاء الطرف - Clearance Status
// =============================================================================
export const CLEARANCE_STATUS = {
  pending: { label_ar: 'قيد الانتظار', label_en: 'Pending', color: 'bg-yellow-100 text-yellow-700', icon: '⏳' },
  finance_approved: { label_ar: 'موافقة المالية', label_en: 'Finance Approved', color: 'bg-blue-100 text-blue-700', icon: '💰' },
  hr_approved: { label_ar: 'موافقة الموارد البشرية', label_en: 'HR Approved', color: 'bg-indigo-100 text-indigo-700', icon: '👥' },
  it_approved: { label_ar: 'موافقة تقنية المعلومات', label_en: 'IT Approved', color: 'bg-purple-100 text-purple-700', icon: '💻' },
  custody_cleared: { label_ar: 'تسليم العهد', label_en: 'Custody Cleared', color: 'bg-orange-100 text-orange-700', icon: '📦' },
  completed: { label_ar: 'مكتمل', label_en: 'Completed', color: 'bg-green-100 text-green-700', icon: '✅' },
  cancelled: { label_ar: 'ملغي', label_en: 'Cancelled', color: 'bg-red-100 text-red-700', icon: '❌' },
};

// =============================================================================
// أسباب إخلاء الطرف - Clearance Reasons
// =============================================================================
export const CLEARANCE_REASONS = {
  resignation: { label_ar: 'استقالة', label_en: 'Resignation' },
  termination: { label_ar: 'إنهاء خدمات', label_en: 'Termination' },
  end_of_contract: { label_ar: 'انتهاء العقد', label_en: 'End of Contract' },
  transfer: { label_ar: 'نقل', label_en: 'Transfer' },
  retirement: { label_ar: 'تقاعد', label_en: 'Retirement' },
};

// =============================================================================
// حالات الإجازات - Leave Status
// =============================================================================
export const LEAVE_STATUS = {
  draft: { label_ar: 'مسودة', label_en: 'Draft', color: 'bg-gray-100 text-gray-700' },
  pending_supervisor: { label_ar: 'بانتظار المشرف', label_en: 'Pending Supervisor', color: 'bg-yellow-100 text-yellow-700' },
  pending_admin_manager: { label_ar: 'بانتظار المدير الإداري', label_en: 'Pending Admin Manager', color: 'bg-orange-100 text-orange-700' },
  pending_hr: { label_ar: 'بانتظار الموارد البشرية', label_en: 'Pending HR', color: 'bg-blue-100 text-blue-700' },
  pending_delegate: { label_ar: 'بانتظار القائم بالعمل', label_en: 'Pending Delegate', color: 'bg-indigo-100 text-indigo-700' },
  form_completed: { label_ar: 'اكتمل النموذج', label_en: 'Form Completed', color: 'bg-teal-100 text-teal-700' },
  approved: { label_ar: 'معتمد', label_en: 'Approved', color: 'bg-green-100 text-green-700' },
  rejected: { label_ar: 'مرفوض', label_en: 'Rejected', color: 'bg-red-100 text-red-700' },
  cancelled: { label_ar: 'ملغي', label_en: 'Cancelled', color: 'bg-gray-100 text-gray-700' },
};

// =============================================================================
// أنواع الإجازات - Leave Types
// =============================================================================
export const LEAVE_TYPES = {
  annual: { label_ar: 'سنوية', label_en: 'Annual', color: 'bg-green-100 text-green-700' },
  sick: { label_ar: 'مرضية', label_en: 'Sick', color: 'bg-red-100 text-red-700' },
  emergency: { label_ar: 'طارئة', label_en: 'Emergency', color: 'bg-orange-100 text-orange-700' },
  unpaid: { label_ar: 'بدون راتب', label_en: 'Unpaid', color: 'bg-gray-100 text-gray-700' },
  maternity: { label_ar: 'أمومة', label_en: 'Maternity', color: 'bg-pink-100 text-pink-700' },
  paternity: { label_ar: 'أبوة', label_en: 'Paternity', color: 'bg-blue-100 text-blue-700' },
  hajj: { label_ar: 'حج', label_en: 'Hajj', color: 'bg-amber-100 text-amber-700' },
  marriage: { label_ar: 'زواج', label_en: 'Marriage', color: 'bg-rose-100 text-rose-700' },
  bereavement: { label_ar: 'وفاة', label_en: 'Bereavement', color: 'bg-slate-100 text-slate-700' },
  study: { label_ar: 'دراسية', label_en: 'Study', color: 'bg-indigo-100 text-indigo-700' },
  compensatory: { label_ar: 'تعويضية', label_en: 'Compensatory', color: 'bg-cyan-100 text-cyan-700' },
  other: { label_ar: 'أخرى', label_en: 'Other', color: 'bg-gray-100 text-gray-700' },
};

// =============================================================================
// حالات الرواتب - Payroll Status
// =============================================================================
export const PAYROLL_STATUS = {
  draft: { label_ar: 'مسودة', label_en: 'Draft', color: 'bg-gray-100 text-gray-700' },
  approved: { label_ar: 'معتمد', label_en: 'Approved', color: 'bg-blue-100 text-blue-700' },
  paid: { label_ar: 'مدفوع', label_en: 'Paid', color: 'bg-green-100 text-green-700' },
};

// =============================================================================
// حالات القروض - Loan Status
// =============================================================================
export const LOAN_STATUS = {
  pending: { label_ar: 'قيد الانتظار', label_en: 'Pending', color: 'bg-yellow-100 text-yellow-700' },
  active: { label_ar: 'نشط', label_en: 'Active', color: 'bg-blue-100 text-blue-700' },
  completed: { label_ar: 'مكتمل', label_en: 'Completed', color: 'bg-green-100 text-green-700' },
  rejected: { label_ar: 'مرفوض', label_en: 'Rejected', color: 'bg-red-100 text-red-700' },
};

// =============================================================================
// حالات مطالبات التأمين - Insurance Claim Status
// =============================================================================
export const INSURANCE_CLAIM_STATUS = {
  submitted: { label_ar: 'مقدمة', label_en: 'Submitted', color: 'bg-blue-100 text-blue-700' },
  scrubbed: { label_ar: 'مراجعة', label_en: 'Scrubbed', color: 'bg-yellow-100 text-yellow-700' },
  approved: { label_ar: 'موافق عليها', label_en: 'Approved', color: 'bg-green-100 text-green-700' },
  paid: { label_ar: 'مدفوعة', label_en: 'Paid', color: 'bg-emerald-100 text-emerald-700' },
  rejected: { label_ar: 'مرفوضة', label_en: 'Rejected', color: 'bg-red-100 text-red-700' },
};

// =============================================================================
// أنواع المستودعات - Warehouse Types
// =============================================================================
export const WAREHOUSE_TYPES = {
  main: { label_ar: 'مستودع رئيسي', label_en: 'Main Warehouse', color: 'bg-blue-100 text-blue-700' },
  sub: { label_ar: 'مستودع فرعي', label_en: 'Sub Warehouse', color: 'bg-gray-100 text-gray-700' },
  pharmacy: { label_ar: 'صيدلية', label_en: 'Pharmacy', color: 'bg-green-100 text-green-700' },
  lab: { label_ar: 'مختبر', label_en: 'Laboratory', color: 'bg-purple-100 text-purple-700' },
  emergency: { label_ar: 'طوارئ', label_en: 'Emergency', color: 'bg-red-100 text-red-700' },
  crash_cart: { label_ar: 'عربة الطوارئ', label_en: 'Crash Cart', color: 'bg-orange-100 text-orange-700' },
  cold_storage: { label_ar: 'تخزين بارد', label_en: 'Cold Storage', color: 'bg-cyan-100 text-cyan-700' },
};

// =============================================================================
// أنواع حركة المخزون - Inventory Movement Types
// =============================================================================
export const MOVEMENT_TYPES = {
  in: { label_ar: 'وارد', label_en: 'In', color: 'bg-green-100 text-green-700' },
  out: { label_ar: 'صادر', label_en: 'Out', color: 'bg-red-100 text-red-700' },
  transfer: { label_ar: 'نقل', label_en: 'Transfer', color: 'bg-blue-100 text-blue-700' },
  adjustment: { label_ar: 'تعديل', label_en: 'Adjustment', color: 'bg-yellow-100 text-yellow-700' },
  return: { label_ar: 'مرتجع', label_en: 'Return', color: 'bg-orange-100 text-orange-700' },
};

// =============================================================================
// حالات طلبات الشراء - Purchase Request Status
// =============================================================================
export const PURCHASE_REQUEST_STATUS = {
  pending: { label_ar: 'قيد الانتظار', label_en: 'Pending', color: 'bg-yellow-100 text-yellow-700' },
  manager_approved: { label_ar: 'موافقة المدير', label_en: 'Manager Approved', color: 'bg-blue-100 text-blue-700' },
  finance_approved: { label_ar: 'موافقة المالية', label_en: 'Finance Approved', color: 'bg-indigo-100 text-indigo-700' },
  ceo_approved: { label_ar: 'موافقة المدير العام', label_en: 'CEO Approved', color: 'bg-purple-100 text-purple-700' },
  completed: { label_ar: 'مكتمل', label_en: 'Completed', color: 'bg-green-100 text-green-700' },
  rejected: { label_ar: 'مرفوض', label_en: 'Rejected', color: 'bg-red-100 text-red-700' },
};

// =============================================================================
// أنواع الورديات - Shift Types
// =============================================================================
export const SHIFT_TYPES = {
  morning: { label_ar: 'صباحي', label_en: 'Morning', color: 'bg-yellow-100 text-yellow-700' },
  evening: { label_ar: 'مسائي', label_en: 'Evening', color: 'bg-orange-100 text-orange-700' },
  night: { label_ar: 'ليلي', label_en: 'Night', color: 'bg-indigo-100 text-indigo-700' },
  full_day: { label_ar: 'يوم كامل', label_en: 'Full Day', color: 'bg-blue-100 text-blue-700' },
};

// =============================================================================
// أحداث سجل المراجعة - Audit Log Events
// =============================================================================
export const AUDIT_EVENTS = {
  created: { label_ar: 'إنشاء', label_en: 'Created', color: 'bg-green-100 text-green-700' },
  updated: { label_ar: 'تحديث', label_en: 'Updated', color: 'bg-blue-100 text-blue-700' },
  deleted: { label_ar: 'حذف', label_en: 'Deleted', color: 'bg-red-100 text-red-700' },
  login: { label_ar: 'دخول', label_en: 'Login', color: 'bg-teal-100 text-teal-700' },
  logout: { label_ar: 'خروج', label_en: 'Logout', color: 'bg-gray-100 text-gray-700' },
  approved: { label_ar: 'اعتماد', label_en: 'Approved', color: 'bg-emerald-100 text-emerald-700' },
  rejected: { label_ar: 'رفض', label_en: 'Rejected', color: 'bg-rose-100 text-rose-700' },
  exported: { label_ar: 'تصدير', label_en: 'Exported', color: 'bg-purple-100 text-purple-700' },
};

// =============================================================================
// أدوار المستخدمين - User Roles
// =============================================================================
export const USER_ROLES = {
  admin: { label_ar: 'مدير النظام', label_en: 'Admin', color: 'bg-red-100 text-red-700' },
  hr_manager: { label_ar: 'مدير الموارد البشرية', label_en: 'HR Manager', color: 'bg-blue-100 text-blue-700' },
  finance_manager: { label_ar: 'مدير المالية', label_en: 'Finance Manager', color: 'bg-green-100 text-green-700' },
  doctor: { label_ar: 'طبيب', label_en: 'Doctor', color: 'bg-teal-100 text-teal-700' },
  nurse: { label_ar: 'ممرض', label_en: 'Nurse', color: 'bg-pink-100 text-pink-700' },
  pharmacist: { label_ar: 'صيدلي', label_en: 'Pharmacist', color: 'bg-purple-100 text-purple-700' },
  lab_technician: { label_ar: 'فني مختبر', label_en: 'Lab Technician', color: 'bg-indigo-100 text-indigo-700' },
  receptionist: { label_ar: 'موظف استقبال', label_en: 'Receptionist', color: 'bg-orange-100 text-orange-700' },
};

// =============================================================================
// الأشهر الهجرية - Hijri Months
// =============================================================================
export const HIJRI_MONTHS = {
  1: { ar: 'محرم', en: 'Muharram' },
  2: { ar: 'صفر', en: 'Safar' },
  3: { ar: 'ربيع الأول', en: 'Rabi al-Awwal' },
  4: { ar: 'ربيع الثاني', en: 'Rabi al-Thani' },
  5: { ar: 'جمادى الأولى', en: 'Jumada al-Awwal' },
  6: { ar: 'جمادى الآخرة', en: 'Jumada al-Thani' },
  7: { ar: 'رجب', en: 'Rajab' },
  8: { ar: 'شعبان', en: 'Shaban' },
  9: { ar: 'رمضان', en: 'Ramadan' },
  10: { ar: 'شوال', en: 'Shawwal' },
  11: { ar: 'ذو القعدة', en: 'Dhu al-Qadah' },
  12: { ar: 'ذو الحجة', en: 'Dhu al-Hijjah' },
};

// =============================================================================
// إعدادات GOSI (التأمينات الاجتماعية)
// =============================================================================
export const GOSI_SETTINGS = {
  saudi_employee_rate: 9.75, // نسبة الموظف السعودي
  saudi_employer_rate: 11.75, // نسبة صاحب العمل للسعودي
  non_saudi_employer_rate: 2, // نسبة صاحب العمل لغير السعودي
  max_salary: 45000, // الحد الأقصى للراتب الخاضع
};

// =============================================================================
// دوال مساعدة - Helper Functions
// =============================================================================

/**
 * تنسيق المبلغ كعملة
 */
export const formatCurrency = (amount, currency = 'SAR', locale = 'ar-SA') => {
  if (amount == null || isNaN(amount)) return '-';
  return new Intl.NumberFormat(locale, {
    style: 'currency',
    currency,
    minimumFractionDigits: 0,
    maximumFractionDigits: 2,
  }).format(amount);
};

/**
 * تنسيق التاريخ
 */
export const formatDate = (date, locale = 'ar-SA', options = {}) => {
  if (!date) return '-';
  const defaultOptions = {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    ...options,
  };
  return new Date(date).toLocaleDateString(locale, defaultOptions);
};

/**
 * تنسيق التاريخ والوقت
 */
export const formatDateTime = (date, locale = 'ar-SA') => {
  if (!date) return '-';
  return new Date(date).toLocaleString(locale, {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
};

/**
 * تنسيق الرقم
 */
export const formatNumber = (num, locale = 'ar-SA') => {
  if (num == null || isNaN(num)) return '-';
  return new Intl.NumberFormat(locale).format(num);
};

/**
 * تنسيق النسبة المئوية
 */
export const formatPercent = (value, locale = 'ar-SA', decimals = 1) => {
  if (value == null || isNaN(value)) return '-';
  return new Intl.NumberFormat(locale, {
    style: 'percent',
    minimumFractionDigits: decimals,
    maximumFractionDigits: decimals,
  }).format(value / 100);
};

/**
 * الحصول على تسمية الحالة
 */
export const getStatusLabel = (statusMap, status, locale = 'ar') => {
  const statusInfo = statusMap[status];
  if (!statusInfo) return status;
  return locale === 'ar' ? statusInfo.label_ar : statusInfo.label_en;
};

/**
 * الحصول على لون الحالة
 */
export const getStatusColor = (statusMap, status) => {
  return statusMap[status]?.color || 'bg-gray-100 text-gray-700';
};
