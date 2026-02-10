import api from './api';

/**
 * خدمات API للأقسام
 * Departments API Services
 */
export const departmentsApi = {
  getAll: (params = {}) => api.get('/hr/departments', { params }),
  getActive: () => api.get('/hr/departments/active'),
  getById: (id) => api.get(`/hr/departments/${id}`),
  create: (data) => api.post('/hr/departments', data),
  update: (id, data) => api.put(`/hr/departments/${id}`, data),
  delete: (id) => api.delete(`/hr/departments/${id}`),
  getEmployees: (id) => api.get(`/hr/departments/${id}/employees`),
  getPositions: (id) => api.get(`/hr/departments/${id}/positions`),
  getTree: () => api.get('/hr/departments/tree'),
};

/**
 * خدمات API للمناصب
 * Positions API Services
 */
export const positionsApi = {
  getAll: (params = {}) => api.get('/hr/positions', { params }),
  getActive: () => api.get('/hr/positions/active'),
  getById: (id) => api.get(`/hr/positions/${id}`),
  create: (data) => api.post('/hr/positions', data),
  update: (id, data) => api.put(`/hr/positions/${id}`, data),
  delete: (id) => api.delete(`/hr/positions/${id}`),
  getByDepartment: (departmentId) => api.get(`/hr/departments/${departmentId}/positions`),
};

/**
 * خدمات API للموظفين
 * Employees API Services
 */
export const employeesApi = {
  getAll: (params = {}) => api.get('/hr/employees', { params }),
  getActive: () => api.get('/hr/employees/active'),
  getById: (id) => api.get(`/hr/employees/${id}`),
  create: (data) => api.post('/hr/employees', data),
  update: (id, data) => api.put(`/hr/employees/${id}`, data),
  delete: (id) => api.delete(`/hr/employees/${id}`),

  // Profile & Documents
  getProfile: (id) => api.get(`/hr/employees/${id}/profile`),
  updateProfile: (id, data) => api.put(`/hr/employees/${id}/profile`, data),
  uploadDocument: (id, formData) => api.post(`/hr/employees/${id}/documents`, formData, {
    headers: { 'Content-Type': 'multipart/form-data' },
  }),
  getDocuments: (id) => api.get(`/hr/employees/${id}/documents`),
  deleteDocument: (id, docId) => api.delete(`/hr/employees/${id}/documents/${docId}`),

  // Contracts & Employment
  getContracts: (id) => api.get(`/hr/employees/${id}/contracts`),
  getCurrentContract: (id) => api.get(`/hr/employees/${id}/contracts/current`),

  // Leaves
  getLeaveBalance: (id) => api.get(`/hr/employees/${id}/leave-balance`),
  getLeaveHistory: (id) => api.get(`/hr/employees/${id}/leave-history`),

  // Custodies
  getCustodies: (id) => api.get(`/hr/employees/${id}/custodies`),

  // Reports
  exportList: (params = {}) => api.get('/hr/employees/export', { params, responseType: 'blob' }),
  getStatistics: () => api.get('/hr/employees/statistics'),

  // Search
  search: (query) => api.get('/hr/employees/search', { params: { q: query } }),
};

/**
 * خدمات API للعقود
 * Contracts API Services
 */
export const contractsApi = {
  getAll: (params = {}) => api.get('/hr/contracts', { params }),
  getById: (id) => api.get(`/hr/contracts/${id}`),
  create: (data) => api.post('/hr/contracts', data),
  update: (id, data) => api.put(`/hr/contracts/${id}`, data),
  delete: (id) => api.delete(`/hr/contracts/${id}`),

  // Contract Lifecycle
  activate: (id) => api.post(`/hr/contracts/${id}/activate`),
  terminate: (id, data) => api.post(`/hr/contracts/${id}/terminate`, data),
  renew: (id, data) => api.post(`/hr/contracts/${id}/renew`, data),

  // Contract Types
  getTypes: () => api.get('/hr/contracts/types'),

  // Expiring contracts
  getExpiring: (days = 30) => api.get('/hr/contracts/expiring', { params: { days } }),

  // Export
  exportList: (params = {}) => api.get('/hr/contracts/export', { params, responseType: 'blob' }),
};

/**
 * خدمات API للعهد
 * Custodies API Services
 */
export const custodiesApi = {
  getAll: (params = {}) => api.get('/hr/custodies', { params }),
  getById: (id) => api.get(`/hr/custodies/${id}`),
  create: (data) => api.post('/hr/custodies', data),
  update: (id, data) => api.put(`/hr/custodies/${id}`, data),
  delete: (id) => api.delete(`/hr/custodies/${id}`),

  // Custody Lifecycle
  handover: (id, data) => api.post(`/hr/custodies/${id}/handover`, data),
  receive: (id, data) => api.post(`/hr/custodies/${id}/receive`, data),
  return: (id, data) => api.post(`/hr/custodies/${id}/return`, data),

  // By Employee
  getByEmployee: (employeeId) => api.get(`/hr/employees/${employeeId}/custodies`),
  getPendingReturn: (employeeId) => api.get(`/hr/employees/${employeeId}/custodies/pending`),

  // Categories
  getCategories: () => api.get('/hr/custodies/categories'),

  // Export
  exportList: (params = {}) => api.get('/hr/custodies/export', { params, responseType: 'blob' }),
};

/**
 * خدمات API لإخلاء الطرف
 * Clearance API Services
 */
export const clearanceApi = {
  getAll: (params = {}) => api.get('/hr/clearance', { params }),
  getById: (id) => api.get(`/hr/clearance/${id}`),
  create: (data) => api.post('/hr/clearance', data),
  update: (id, data) => api.put(`/hr/clearance/${id}`, data),
  delete: (id) => api.delete(`/hr/clearance/${id}`),

  // Workflow Actions
  submit: (id) => api.post(`/hr/clearance/${id}/submit`),

  // Department Approvals
  approveFinance: (id, data) => api.post(`/hr/clearance/${id}/approve/finance`, data),
  approveHR: (id, data) => api.post(`/hr/clearance/${id}/approve/hr`, data),
  approveIT: (id, data) => api.post(`/hr/clearance/${id}/approve/it`, data),
  approveCustody: (id, data) => api.post(`/hr/clearance/${id}/approve/custody`, data),

  // Reject
  reject: (id, data) => api.post(`/hr/clearance/${id}/reject`, data),

  // Complete
  complete: (id, data) => api.post(`/hr/clearance/${id}/complete`, data),

  // Get Pending Approvals
  getPendingByDepartment: (department) => api.get(`/hr/clearance/pending/${department}`),

  // Checklist
  getChecklist: (id) => api.get(`/hr/clearance/${id}/checklist`),
  updateChecklistItem: (id, itemId, data) => api.put(`/hr/clearance/${id}/checklist/${itemId}`, data),

  // Export
  exportList: (params = {}) => api.get('/hr/clearance/export', { params, responseType: 'blob' }),
};

/**
 * خدمات تقارير الموارد البشرية
 * HR Reports API Services
 */
export const hrReportsApi = {
  getEmployeeSummary: () => api.get('/hr/reports/employee-summary'),
  getContractStatistics: () => api.get('/hr/reports/contract-statistics'),
  getCustodyReport: (params = {}) => api.get('/hr/reports/custody', { params }),
  getClearanceReport: (params = {}) => api.get('/hr/reports/clearance', { params }),
  getTurnoverReport: (params = {}) => api.get('/hr/reports/turnover', { params }),
  getHeadcountReport: (params = {}) => api.get('/hr/reports/headcount', { params }),
};
