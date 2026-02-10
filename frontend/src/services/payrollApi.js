/**
 * Payroll API Service
 * خدمة API الرواتب
 */

const API_BASE = '/api/payroll';

// Helper for API calls
const fetchApi = async (url, options = {}) => {
  const token = localStorage.getItem('auth_token');
  const response = await fetch(url, {
    ...options,
    headers: {
      'Content-Type': 'application/json',
      'Authorization': `Bearer ${token}`,
      ...options.headers,
    },
  });

  if (!response.ok) {
    const error = await response.json().catch(() => ({ message: 'حدث خطأ' }));
    throw new Error(error.message || 'حدث خطأ في الاتصال');
  }

  return response.json();
};

// =============================================================================
// Payrolls API - مسيرات الرواتب
// =============================================================================

export const payrollsApi = {
  // قائمة المسيرات
  getAll: (params = {}) => {
    const query = new URLSearchParams(params).toString();
    return fetchApi(`${API_BASE}/payrolls${query ? `?${query}` : ''}`);
  },

  // تفاصيل مسير
  getById: (id) => fetchApi(`${API_BASE}/payrolls/${id}`),

  // ملخص الفترة
  getPeriodSummary: (year, month) =>
    fetchApi(`${API_BASE}/payrolls/period-summary?year=${year}&month=${month}`),

  // توليد مسيرات شهرية
  generate: (year, month) =>
    fetchApi(`${API_BASE}/payrolls/generate`, {
      method: 'POST',
      body: JSON.stringify({ year, month }),
    }),

  // إعادة حساب
  recalculate: (id) =>
    fetchApi(`${API_BASE}/payrolls/${id}/recalculate`, { method: 'POST' }),

  // اعتماد المسير
  approve: (id) =>
    fetchApi(`${API_BASE}/payrolls/${id}/approve`, { method: 'POST' }),

  // اعتماد مجموعة
  bulkApprove: (payrollIds) =>
    fetchApi(`${API_BASE}/payrolls/bulk-approve`, {
      method: 'POST',
      body: JSON.stringify({ payroll_ids: payrollIds }),
    }),

  // تسجيل الدفع
  markPaid: (id) =>
    fetchApi(`${API_BASE}/payrolls/${id}/mark-paid`, { method: 'POST' }),

  // قسيمة الراتب
  getPayslip: (id) => fetchApi(`${API_BASE}/payrolls/${id}/payslip`),
};

// =============================================================================
// WPS API - نظام حماية الأجور
// =============================================================================

export const wpsApi = {
  // ملخص WPS
  getSummary: (year, month) =>
    fetchApi(`${API_BASE}/wps/summary?year=${year}&month=${month}`),

  // توليد ملف WPS
  generate: (payrollIds) =>
    fetchApi(`${API_BASE}/wps/generate`, {
      method: 'POST',
      body: JSON.stringify({ payroll_ids: payrollIds }),
    }),
};

// =============================================================================
// Loans API - السلف والقروض
// =============================================================================

export const loansApi = {
  // قائمة السلف
  getAll: (params = {}) => {
    const query = new URLSearchParams(params).toString();
    return fetchApi(`${API_BASE}/loans${query ? `?${query}` : ''}`);
  },

  // تفاصيل سلفة
  getById: (id) => fetchApi(`${API_BASE}/loans/${id}`),

  // طلب سلفة جديدة
  create: (data) =>
    fetchApi(`${API_BASE}/loans`, {
      method: 'POST',
      body: JSON.stringify(data),
    }),

  // السلف النشطة للموظف
  getActiveForEmployee: (employeeId) =>
    fetchApi(`${API_BASE}/loans/employee/${employeeId}/active`),

  // الموافقة
  approve: (id) =>
    fetchApi(`${API_BASE}/loans/${id}/approve`, { method: 'POST' }),

  // الرفض
  reject: (id, reason) =>
    fetchApi(`${API_BASE}/loans/${id}/reject`, {
      method: 'POST',
      body: JSON.stringify({ reason }),
    }),

  // سجل الأقساط
  getPayments: (id) => fetchApi(`${API_BASE}/loans/${id}/payments`),
};

// =============================================================================
// Settings API - الإعدادات
// =============================================================================

export const payrollSettingsApi = {
  // جميع الإعدادات
  getAll: () => fetchApi(`${API_BASE}/settings`),

  // تحديث إعداد
  update: (key, value) =>
    fetchApi(`${API_BASE}/settings/${key}`, {
      method: 'PUT',
      body: JSON.stringify({ value }),
    }),

  // إعادة تعيين
  resetDefaults: () =>
    fetchApi(`${API_BASE}/settings/reset-defaults`, { method: 'POST' }),
};

export default {
  payrolls: payrollsApi,
  wps: wpsApi,
  loans: loansApi,
  settings: payrollSettingsApi,
};
