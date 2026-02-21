/**
 * Leave Module API Service
 * خدمة API لوحدة الإجازات
 */

const API_BASE = '/api/leaves';

/**
 * Helper function for API calls
 */
async function apiCall(endpoint, options = {}) {
  const token = localStorage.getItem('token');

  const config = {
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      'Accept-Language': localStorage.getItem('locale') || 'ar',
      ...(token && { 'Authorization': `Bearer ${token}` }),
      ...options.headers,
    },
    ...options,
  };

  const response = await fetch(`${API_BASE}${endpoint}`, config);

  if (!response.ok) {
    const error = await response.json();
    throw new Error(error.message || 'حدث خطأ في الاتصال');
  }

  return response.json();
}

// =============================================================================
// Leave Types - أنواع الإجازات
// =============================================================================

export const leaveTypesApi = {
  getAll: (params = {}) => {
    const query = new URLSearchParams(params).toString();
    return apiCall(`/types${query ? `?${query}` : ''}`);
  },

  getActive: () => apiCall('/types/active'),

  getById: (id) => apiCall(`/types/${id}`),

  create: (data) => apiCall('/types', {
    method: 'POST',
    body: JSON.stringify(data),
  }),

  update: (id, data) => apiCall(`/types/${id}`, {
    method: 'PUT',
    body: JSON.stringify(data),
  }),

  delete: (id) => apiCall(`/types/${id}`, {
    method: 'DELETE',
  }),
};

// =============================================================================
// Leave Balances - أرصدة الإجازات
// =============================================================================

export const leaveBalancesApi = {
  getAll: (params = {}) => {
    const query = new URLSearchParams(params).toString();
    return apiCall(`/balances${query ? `?${query}` : ''}`);
  },

  getById: (id) => apiCall(`/balances/${id}`),

  getEmployeeSummary: (employeeId, year) =>
    apiCall(`/balances/employee/${employeeId}/summary${year ? `?year=${year}` : ''}`),

  initialize: (data) => apiCall('/balances/initialize', {
    method: 'POST',
    body: JSON.stringify(data),
  }),

  initializeForEmployee: (data) => apiCall('/balances/initialize-for-employee', {
    method: 'POST',
    body: JSON.stringify(data),
  }),

  adjust: (id, data) => apiCall(`/balances/${id}/adjust`, {
    method: 'POST',
    body: JSON.stringify(data),
  }),

  correct: (id, data) => apiCall(`/balances/${id}/correct`, {
    method: 'POST',
    body: JSON.stringify(data),
  }),

  carryOver: (data) => apiCall('/balances/carry-over', {
    method: 'POST',
    body: JSON.stringify(data),
  }),

  getHistory: (id) => apiCall(`/balances/${id}/history`),
};

// =============================================================================
// Leave Requests - طلبات الإجازة
// =============================================================================

export const leaveRequestsApi = {
  getAll: (params = {}) => {
    const query = new URLSearchParams(params).toString();
    return apiCall(`/requests${query ? `?${query}` : ''}`);
  },

  getById: (id) => apiCall(`/requests/${id}`),

  getPendingForMe: () => apiCall('/requests/pending-for-me'),

  create: (data) => apiCall('/requests', {
    method: 'POST',
    body: JSON.stringify(data),
  }),

  submit: (id) => apiCall(`/requests/${id}/submit`, {
    method: 'POST',
  }),

  cancel: (id, reason) => apiCall(`/requests/${id}/cancel`, {
    method: 'POST',
    body: JSON.stringify({ cancellation_reason: reason }),
  }),

  // Phase 1 Approvals
  supervisorRecommendation: (id, data) => apiCall(`/requests/${id}/supervisor-recommendation`, {
    method: 'POST',
    body: JSON.stringify(data),
  }),

  adminManagerApproval: (id, data) => apiCall(`/requests/${id}/admin-manager-approval`, {
    method: 'POST',
    body: JSON.stringify(data),
  }),

  hrEndorsement: (id, data) => apiCall(`/requests/${id}/hr-endorsement`, {
    method: 'POST',
    body: JSON.stringify(data),
  }),

  delegateConfirmation: (id, data) => apiCall(`/requests/${id}/delegate-confirmation`, {
    method: 'POST',
    body: JSON.stringify(data),
  }),
};

// =============================================================================
// Leave Decisions - قرارات الإجازة
// =============================================================================

export const leaveDecisionsApi = {
  getAll: (params = {}) => {
    const query = new URLSearchParams(params).toString();
    return apiCall(`/decisions${query ? `?${query}` : ''}`);
  },

  getById: (id) => apiCall(`/decisions/${id}`),

  getPendingForMe: () => apiCall('/decisions/pending-for-me'),

  create: (leaveRequestId) => apiCall('/decisions', {
    method: 'POST',
    body: JSON.stringify({ leave_request_id: leaveRequestId }),
  }),

  // Phase 2 Approvals
  processAdminManager: (id, data) => apiCall(`/decisions/${id}/admin-manager`, {
    method: 'POST',
    body: JSON.stringify(data),
  }),

  processMedicalDirector: (id, data) => apiCall(`/decisions/${id}/medical-director`, {
    method: 'POST',
    body: JSON.stringify(data),
  }),

  processGeneralManager: (id, data) => apiCall(`/decisions/${id}/general-manager`, {
    method: 'POST',
    body: JSON.stringify(data),
  }),
};

// =============================================================================
// Public Holidays - الإجازات الرسمية
// =============================================================================

export const publicHolidaysApi = {
  getAll: (params = {}) => {
    const query = new URLSearchParams(params).toString();
    return apiCall(`/holidays${query ? `?${query}` : ''}`);
  },

  getByYear: (year) => apiCall(`/holidays/year/${year}`),

  getById: (id) => apiCall(`/holidays/${id}`),

  create: (data) => apiCall('/holidays', {
    method: 'POST',
    body: JSON.stringify(data),
  }),

  update: (id, data) => apiCall(`/holidays/${id}`, {
    method: 'PUT',
    body: JSON.stringify(data),
  }),

  delete: (id) => apiCall(`/holidays/${id}`, {
    method: 'DELETE',
  }),
};

// =============================================================================
// Leave Policies - سياسات الإجازات
// =============================================================================

export const leavePoliciesApi = {
  getAll: (params = {}) => {
    const query = new URLSearchParams(params).toString();
    return apiCall(`/policies${query ? `?${query}` : ''}`);
  },

  getByContractType: (contractType) => apiCall(`/policies/contract-type/${contractType}`),

  getById: (id) => apiCall(`/policies/${id}`),

  create: (data) => apiCall('/policies', {
    method: 'POST',
    body: JSON.stringify(data),
  }),

  update: (id, data) => apiCall(`/policies/${id}`, {
    method: 'PUT',
    body: JSON.stringify(data),
  }),

  delete: (id) => apiCall(`/policies/${id}`, {
    method: 'DELETE',
  }),
};

// =============================================================================
// Reports - التقارير
// =============================================================================

export const leaveReportsApi = {
  getBalancesReport: (params = {}) => {
    const query = new URLSearchParams(params).toString();
    return apiCall(`/reports/balances${query ? `?${query}` : ''}`);
  },

  getConsumptionReport: (params) => {
    const query = new URLSearchParams(params).toString();
    return apiCall(`/reports/consumption?${query}`);
  },

  getByDepartmentReport: (params = {}) => {
    const query = new URLSearchParams(params).toString();
    return apiCall(`/reports/by-department${query ? `?${query}` : ''}`);
  },

  getAbsenceReport: (params) => {
    const query = new URLSearchParams(params).toString();
    return apiCall(`/reports/absence?${query}`);
  },

  getStatistics: (params = {}) => {
    const query = new URLSearchParams(params).toString();
    return apiCall(`/reports/statistics${query ? `?${query}` : ''}`);
  },
};

export default {
  leaveTypes: leaveTypesApi,
  leaveBalances: leaveBalancesApi,
  leaveRequests: leaveRequestsApi,
  leaveDecisions: leaveDecisionsApi,
  publicHolidays: publicHolidaysApi,
  leavePolicies: leavePoliciesApi,
  leaveReports: leaveReportsApi,
};
