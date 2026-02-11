/**
 * Finance Module API Services
 * خدمات API لوحدة المالية
 */

import api from './api';

// ==================== Cost Centers API ====================
export const costCentersApi = {
  getAll: (params) => api.get('/finance/cost-centers', { params }),
  getActive: () => api.get('/finance/cost-centers/active'),
  getById: (id) => api.get(`/finance/cost-centers/${id}`),
  create: (data) => api.post('/finance/cost-centers', data),
  update: (id, data) => api.put(`/finance/cost-centers/${id}`, data),
  delete: (id) => api.delete(`/finance/cost-centers/${id}`),
  getByDepartment: (departmentId) => api.get(`/finance/cost-centers/department/${departmentId}`),
  getHierarchy: () => api.get('/finance/cost-centers/hierarchy'),
  getAllocations: (id, params) => api.get(`/finance/cost-centers/${id}/allocations`, { params }),
  createAllocation: (id, data) => api.post(`/finance/cost-centers/${id}/allocations`, data),
  getBudget: (id, year) => api.get(`/finance/cost-centers/${id}/budget/${year}`),
  updateBudget: (id, year, data) => api.put(`/finance/cost-centers/${id}/budget/${year}`, data),
  getExpenses: (id, params) => api.get(`/finance/cost-centers/${id}/expenses`, { params }),
  exportList: (params) => api.get('/finance/cost-centers/export', { params, responseType: 'blob' })
};

// ==================== Doctors API ====================
export const doctorsApi = {
  getAll: (params) => api.get('/finance/doctors', { params }),
  getActive: () => api.get('/finance/doctors/active'),
  getById: (id) => api.get(`/finance/doctors/${id}`),
  create: (data) => api.post('/finance/doctors', data),
  update: (id, data) => api.put(`/finance/doctors/${id}`, data),
  delete: (id) => api.delete(`/finance/doctors/${id}`),
  getBySpecialty: (specialty) => api.get(`/finance/doctors/specialty/${specialty}`),
  getByDepartment: (departmentId) => api.get(`/finance/doctors/department/${departmentId}`),
  getServices: (id) => api.get(`/finance/doctors/${id}/services`),
  assignService: (id, serviceId, data) => api.post(`/finance/doctors/${id}/services/${serviceId}`, data),
  removeService: (id, serviceId) => api.delete(`/finance/doctors/${id}/services/${serviceId}`),
  getCommissions: (id, params) => api.get(`/finance/doctors/${id}/commissions`, { params }),
  getPerformance: (id, params) => api.get(`/finance/doctors/${id}/performance`, { params }),
  getProfitability: (id, params) => api.get(`/finance/doctors/${id}/profitability`, { params }),
  exportList: (params) => api.get('/finance/doctors/export', { params, responseType: 'blob' })
};

// ==================== Medical Services API ====================
export const medicalServicesApi = {
  getAll: (params) => api.get('/finance/services', { params }),
  getActive: () => api.get('/finance/services/active'),
  getById: (id) => api.get(`/finance/services/${id}`),
  create: (data) => api.post('/finance/services', data),
  update: (id, data) => api.put(`/finance/services/${id}`, data),
  delete: (id) => api.delete(`/finance/services/${id}`),
  getByCategory: (category) => api.get(`/finance/services/category/${category}`),
  getCategories: () => api.get('/finance/services/categories'),
  createCategory: (data) => api.post('/finance/services/categories', data),
  getPricing: (id) => api.get(`/finance/services/${id}/pricing`),
  updatePricing: (id, data) => api.put(`/finance/services/${id}/pricing`, data),
  getInsurancePricing: (id, insuranceId) => api.get(`/finance/services/${id}/insurance/${insuranceId}/pricing`),
  updateInsurancePricing: (id, insuranceId, data) => api.put(`/finance/services/${id}/insurance/${insuranceId}/pricing`, data),
  getCostBreakdown: (id) => api.get(`/finance/services/${id}/costs`),
  updateCostBreakdown: (id, data) => api.put(`/finance/services/${id}/costs`, data),
  getProfitability: (id, params) => api.get(`/finance/services/${id}/profitability`, { params }),
  exportList: (params) => api.get('/finance/services/export', { params, responseType: 'blob' })
};

// ==================== Insurance Companies API ====================
export const insuranceCompaniesApi = {
  getAll: (params) => api.get('/finance/insurance-companies', { params }),
  getActive: () => api.get('/finance/insurance-companies/active'),
  getById: (id) => api.get(`/finance/insurance-companies/${id}`),
  create: (data) => api.post('/finance/insurance-companies', data),
  update: (id, data) => api.put(`/finance/insurance-companies/${id}`, data),
  delete: (id) => api.delete(`/finance/insurance-companies/${id}`),
  getContracts: (id) => api.get(`/finance/insurance-companies/${id}/contracts`),
  createContract: (id, data) => api.post(`/finance/insurance-companies/${id}/contracts`, data),
  getServicePrices: (id) => api.get(`/finance/insurance-companies/${id}/service-prices`),
  updateServicePrices: (id, data) => api.put(`/finance/insurance-companies/${id}/service-prices`, data),
  getClaimsSummary: (id, params) => api.get(`/finance/insurance-companies/${id}/claims-summary`, { params }),
  exportList: (params) => api.get('/finance/insurance-companies/export', { params, responseType: 'blob' })
};

// ==================== Insurance Claims API ====================
export const insuranceClaimsApi = {
  getAll: (params) => api.get('/finance/claims', { params }),
  getById: (id) => api.get(`/finance/claims/${id}`),
  create: (data) => api.post('/finance/claims', data),
  update: (id, data) => api.put(`/finance/claims/${id}`, data),
  delete: (id) => api.delete(`/finance/claims/${id}`),

  // Claim lifecycle
  submit: (id) => api.post(`/finance/claims/${id}/submit`),
  scrub: (id) => api.post(`/finance/claims/${id}/scrub`),
  approve: (id, data) => api.post(`/finance/claims/${id}/approve`, data),
  reject: (id, data) => api.post(`/finance/claims/${id}/reject`, data),
  resubmit: (id, data) => api.post(`/finance/claims/${id}/resubmit`, data),
  markPaid: (id, data) => api.post(`/finance/claims/${id}/mark-paid`, data),

  // Batch operations
  batchSubmit: (ids) => api.post('/finance/claims/batch/submit', { claim_ids: ids }),
  batchScrub: (ids) => api.post('/finance/claims/batch/scrub', { claim_ids: ids }),

  // Queries
  getByInsurance: (insuranceId, params) => api.get(`/finance/claims/insurance/${insuranceId}`, { params }),
  getByPatient: (patientId, params) => api.get(`/finance/claims/patient/${patientId}`, { params }),
  getByDoctor: (doctorId, params) => api.get(`/finance/claims/doctor/${doctorId}`, { params }),
  getByStatus: (status, params) => api.get(`/finance/claims/status/${status}`, { params }),
  getPending: (params) => api.get('/finance/claims/pending', { params }),
  getRejected: (params) => api.get('/finance/claims/rejected', { params }),

  // Scrubbing
  getScrubErrors: (id) => api.get(`/finance/claims/${id}/scrub-errors`),
  fixScrubError: (id, errorId, data) => api.post(`/finance/claims/${id}/scrub-errors/${errorId}/fix`, data),

  // Attachments
  getAttachments: (id) => api.get(`/finance/claims/${id}/attachments`),
  addAttachment: (id, formData) => api.post(`/finance/claims/${id}/attachments`, formData, {
    headers: { 'Content-Type': 'multipart/form-data' }
  }),
  removeAttachment: (id, attachmentId) => api.delete(`/finance/claims/${id}/attachments/${attachmentId}`),

  // Export
  exportList: (params) => api.get('/finance/claims/export', { params, responseType: 'blob' }),
  exportToInsurance: (insuranceId, params) => api.get(`/finance/claims/export/insurance/${insuranceId}`, { params, responseType: 'blob' })
};

// ==================== Commission Adjustments API ====================
export const commissionAdjustmentsApi = {
  getAll: (params) => api.get('/finance/commission-adjustments', { params }),
  getById: (id) => api.get(`/finance/commission-adjustments/${id}`),
  create: (data) => api.post('/finance/commission-adjustments', data),
  update: (id, data) => api.put(`/finance/commission-adjustments/${id}`, data),
  delete: (id) => api.delete(`/finance/commission-adjustments/${id}`),
  approve: (id, data) => api.post(`/finance/commission-adjustments/${id}/approve`, data),
  reject: (id, data) => api.post(`/finance/commission-adjustments/${id}/reject`, data),
  getByDoctor: (doctorId, params) => api.get(`/finance/commission-adjustments/doctor/${doctorId}`, { params }),
  getPending: (params) => api.get('/finance/commission-adjustments/pending', { params }),
  // Clawback for rejected claims
  createClawback: (claimId, data) => api.post(`/finance/commission-adjustments/clawback/${claimId}`, data)
};

// ==================== Aging Snapshots API ====================
export const agingSnapshotsApi = {
  getAll: (params) => api.get('/finance/aging-snapshots', { params }),
  getById: (id) => api.get(`/finance/aging-snapshots/${id}`),
  create: () => api.post('/finance/aging-snapshots'),
  getLatest: () => api.get('/finance/aging-snapshots/latest'),
  getByInsurance: (insuranceId, params) => api.get(`/finance/aging-snapshots/insurance/${insuranceId}`, { params }),
  compare: (id1, id2) => api.get(`/finance/aging-snapshots/compare/${id1}/${id2}`),
  exportSnapshot: (id) => api.get(`/finance/aging-snapshots/${id}/export`, { responseType: 'blob' })
};

// ==================== Service Profitability API ====================
export const serviceProfitabilityApi = {
  getAll: (params) => api.get('/finance/profitability', { params }),
  getByService: (serviceId, params) => api.get(`/finance/profitability/service/${serviceId}`, { params }),
  getByDoctor: (doctorId, params) => api.get(`/finance/profitability/doctor/${doctorId}`, { params }),
  getByCostCenter: (costCenterId, params) => api.get(`/finance/profitability/cost-center/${costCenterId}`, { params }),
  getByInsurance: (insuranceId, params) => api.get(`/finance/profitability/insurance/${insuranceId}`, { params }),
  getSummary: (params) => api.get('/finance/profitability/summary', { params }),
  getTopServices: (params) => api.get('/finance/profitability/top-services', { params }),
  getTopDoctors: (params) => api.get('/finance/profitability/top-doctors', { params }),
  getLossServices: (params) => api.get('/finance/profitability/loss-services', { params }),
  exportReport: (params) => api.get('/finance/profitability/export', { params, responseType: 'blob' })
};

// ==================== Finance Reports API ====================
export const financeReportsApi = {
  // Revenue reports
  getRevenueReport: (params) => api.get('/finance/reports/revenue', { params }),
  getRevenueByService: (params) => api.get('/finance/reports/revenue/by-service', { params }),
  getRevenueByDoctor: (params) => api.get('/finance/reports/revenue/by-doctor', { params }),
  getRevenueByInsurance: (params) => api.get('/finance/reports/revenue/by-insurance', { params }),
  getRevenueTrend: (params) => api.get('/finance/reports/revenue/trend', { params }),

  // Expense reports
  getExpenseReport: (params) => api.get('/finance/reports/expenses', { params }),
  getExpenseByCostCenter: (params) => api.get('/finance/reports/expenses/by-cost-center', { params }),
  getExpenseByCategory: (params) => api.get('/finance/reports/expenses/by-category', { params }),

  // Insurance reports
  getInsuranceReport: (params) => api.get('/finance/reports/insurance', { params }),
  getClaimsAgingReport: (params) => api.get('/finance/reports/claims-aging', { params }),
  getClaimsRejectionReport: (params) => api.get('/finance/reports/claims-rejection', { params }),
  getInsurancePerformance: (params) => api.get('/finance/reports/insurance-performance', { params }),

  // Commission reports
  getCommissionReport: (params) => api.get('/finance/reports/commissions', { params }),
  getCommissionByDoctor: (params) => api.get('/finance/reports/commissions/by-doctor', { params }),
  getClawbackReport: (params) => api.get('/finance/reports/clawbacks', { params }),

  // ABC Costing reports
  getAbcCostingReport: (params) => api.get('/finance/reports/abc-costing', { params }),
  getCostAllocationReport: (params) => api.get('/finance/reports/cost-allocation', { params }),

  // Dashboard
  getDashboardSummary: (params) => api.get('/finance/reports/dashboard', { params }),
  getKPIs: (params) => api.get('/finance/reports/kpis', { params }),

  // Export
  exportReport: (reportType, params) => api.get(`/finance/reports/${reportType}/export`, { params, responseType: 'blob' })
};

// ==================== Budget API ====================
export const budgetApi = {
  getAll: (year) => api.get(`/finance/budgets/${year}`),
  getById: (id) => api.get(`/finance/budgets/item/${id}`),
  create: (data) => api.post('/finance/budgets', data),
  update: (id, data) => api.put(`/finance/budgets/item/${id}`, data),
  delete: (id) => api.delete(`/finance/budgets/item/${id}`),
  getByCostCenter: (costCenterId, year) => api.get(`/finance/budgets/cost-center/${costCenterId}/${year}`),
  getVarianceReport: (year, params) => api.get(`/finance/budgets/${year}/variance`, { params }),
  importBudget: (year, formData) => api.post(`/finance/budgets/${year}/import`, formData, {
    headers: { 'Content-Type': 'multipart/form-data' }
  }),
  exportBudget: (year) => api.get(`/finance/budgets/${year}/export`, { responseType: 'blob' })
};

export default {
  costCenters: costCentersApi,
  doctors: doctorsApi,
  medicalServices: medicalServicesApi,
  insuranceCompanies: insuranceCompaniesApi,
  insuranceClaims: insuranceClaimsApi,
  commissionAdjustments: commissionAdjustmentsApi,
  agingSnapshots: agingSnapshotsApi,
  serviceProfitability: serviceProfitabilityApi,
  reports: financeReportsApi,
  budget: budgetApi
};
