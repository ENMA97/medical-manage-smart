import api from './api';

/**
 * Roster Module API Services
 * خدمات API لوحدة الجداول
 */

// ==================== Shift Patterns API ====================
// أنماط الورديات
export const shiftPatternsApi = {
  getAll: (params) => api.get('/shift-patterns', { params }),
  getActive: () => api.get('/shift-patterns/active'),
  getById: (id) => api.get(`/shift-patterns/${id}`),
  create: (data) => api.post('/shift-patterns', data),
  update: (id, data) => api.put(`/shift-patterns/${id}`, data),
  delete: (id) => api.delete(`/shift-patterns/${id}`),
  getByDepartment: (departmentId) => api.get(`/shift-patterns/department/${departmentId}`),
};

// ==================== Rosters API ====================
// الجداول الأسبوعية
export const rostersApi = {
  getAll: (params) => api.get('/rosters', { params }),
  getById: (id) => api.get(`/rosters/${id}`),
  create: (data) => api.post('/rosters', data),
  update: (id, data) => api.put(`/rosters/${id}`, data),
  delete: (id) => api.delete(`/rosters/${id}`),
  getByDepartment: (departmentId, params) => api.get(`/rosters/department/${departmentId}`, { params }),
  getByDateRange: (startDate, endDate, params) => api.get('/rosters/date-range', {
    params: { start_date: startDate, end_date: endDate, ...params }
  }),
  publish: (id) => api.post(`/rosters/${id}/publish`),
  unpublish: (id) => api.post(`/rosters/${id}/unpublish`),
  duplicate: (id, data) => api.post(`/rosters/${id}/duplicate`, data),
  exportList: (params) => api.get('/rosters/export', { params, responseType: 'blob' }),
};

// ==================== Roster Assignments API ====================
// تعيينات الجدول (الموظف + اليوم + الوردية)
export const rosterAssignmentsApi = {
  getAll: (params) => api.get('/roster-assignments', { params }),
  getById: (id) => api.get(`/roster-assignments/${id}`),
  create: (data) => api.post('/roster-assignments', data),
  update: (id, data) => api.put(`/roster-assignments/${id}`, data),
  delete: (id) => api.delete(`/roster-assignments/${id}`),
  getByRoster: (rosterId) => api.get(`/roster-assignments/roster/${rosterId}`),
  getByEmployee: (employeeId, params) => api.get(`/roster-assignments/employee/${employeeId}`, { params }),
  getByDate: (date, params) => api.get(`/roster-assignments/date/${date}`, { params }),
  bulkCreate: (data) => api.post('/roster-assignments/bulk', data),
  bulkUpdate: (data) => api.put('/roster-assignments/bulk', data),
  bulkDelete: (ids) => api.delete('/roster-assignments/bulk', { data: { ids } }),
  copyWeek: (data) => api.post('/roster-assignments/copy-week', data),
};

// ==================== Shift Swap Requests API ====================
// طلبات تبديل الورديات
export const shiftSwapRequestsApi = {
  getAll: (params) => api.get('/shift-swap-requests', { params }),
  getById: (id) => api.get(`/shift-swap-requests/${id}`),
  create: (data) => api.post('/shift-swap-requests', data),
  update: (id, data) => api.put(`/shift-swap-requests/${id}`, data),
  delete: (id) => api.delete(`/shift-swap-requests/${id}`),
  getMyRequests: () => api.get('/shift-swap-requests/my-requests'),
  getPendingForMe: () => api.get('/shift-swap-requests/pending-for-me'),
  getPendingApprovals: () => api.get('/shift-swap-requests/pending-approvals'),
  acceptByEmployee: (id) => api.post(`/shift-swap-requests/${id}/accept`),
  rejectByEmployee: (id, data) => api.post(`/shift-swap-requests/${id}/reject`, data),
  approveByManager: (id, data) => api.post(`/shift-swap-requests/${id}/approve`, data),
  rejectByManager: (id, data) => api.post(`/shift-swap-requests/${id}/manager-reject`, data),
  cancel: (id) => api.post(`/shift-swap-requests/${id}/cancel`),
};

// ==================== Attendance Records API ====================
// سجلات الحضور والانصراف
export const attendanceRecordsApi = {
  getAll: (params) => api.get('/attendance-records', { params }),
  getById: (id) => api.get(`/attendance-records/${id}`),
  create: (data) => api.post('/attendance-records', data),
  update: (id, data) => api.put(`/attendance-records/${id}`, data),
  delete: (id) => api.delete(`/attendance-records/${id}`),
  getByEmployee: (employeeId, params) => api.get(`/attendance-records/employee/${employeeId}`, { params }),
  getByDate: (date, params) => api.get(`/attendance-records/date/${date}`, { params }),
  getByDateRange: (startDate, endDate, params) => api.get('/attendance-records/date-range', {
    params: { start_date: startDate, end_date: endDate, ...params }
  }),
  checkIn: (data) => api.post('/attendance-records/check-in', data),
  checkOut: (id, data) => api.post(`/attendance-records/${id}/check-out`, data),
  manualEntry: (data) => api.post('/attendance-records/manual-entry', data),
  approveManual: (id) => api.post(`/attendance-records/${id}/approve`),
  rejectManual: (id, data) => api.post(`/attendance-records/${id}/reject`, data),
  getAbsentees: (date) => api.get(`/attendance-records/absentees/${date}`),
  getLateArrivals: (date) => api.get(`/attendance-records/late-arrivals/${date}`),
  exportList: (params) => api.get('/attendance-records/export', { params, responseType: 'blob' }),
};

// ==================== Biometric Devices API ====================
// أجهزة البصمة
export const biometricDevicesApi = {
  getAll: (params) => api.get('/biometric-devices', { params }),
  getActive: () => api.get('/biometric-devices/active'),
  getById: (id) => api.get(`/biometric-devices/${id}`),
  create: (data) => api.post('/biometric-devices', data),
  update: (id, data) => api.put(`/biometric-devices/${id}`, data),
  delete: (id) => api.delete(`/biometric-devices/${id}`),
  syncDevice: (id) => api.post(`/biometric-devices/${id}/sync`),
  testConnection: (id) => api.post(`/biometric-devices/${id}/test`),
  getLastSync: (id) => api.get(`/biometric-devices/${id}/last-sync`),
};

// ==================== On-Call Assignments API ====================
// تعيينات المناوبة
export const onCallAssignmentsApi = {
  getAll: (params) => api.get('/on-call-assignments', { params }),
  getById: (id) => api.get(`/on-call-assignments/${id}`),
  create: (data) => api.post('/on-call-assignments', data),
  update: (id, data) => api.put(`/on-call-assignments/${id}`, data),
  delete: (id) => api.delete(`/on-call-assignments/${id}`),
  getByEmployee: (employeeId, params) => api.get(`/on-call-assignments/employee/${employeeId}`, { params }),
  getByDate: (date, params) => api.get(`/on-call-assignments/date/${date}`, { params }),
  getByWeek: (weekStart, params) => api.get(`/on-call-assignments/week/${weekStart}`, { params }),
};

// ==================== Special Duties API ====================
// المهام الخاصة (التعقيم، غرفة العزل، إلخ)
export const specialDutiesApi = {
  getAll: (params) => api.get('/special-duties', { params }),
  getActive: () => api.get('/special-duties/active'),
  getById: (id) => api.get(`/special-duties/${id}`),
  create: (data) => api.post('/special-duties', data),
  update: (id, data) => api.put(`/special-duties/${id}`, data),
  delete: (id) => api.delete(`/special-duties/${id}`),
};

// ==================== Special Duty Assignments API ====================
// تعيينات المهام الخاصة
export const specialDutyAssignmentsApi = {
  getAll: (params) => api.get('/special-duty-assignments', { params }),
  getById: (id) => api.get(`/special-duty-assignments/${id}`),
  create: (data) => api.post('/special-duty-assignments', data),
  update: (id, data) => api.put(`/special-duty-assignments/${id}`, data),
  delete: (id) => api.delete(`/special-duty-assignments/${id}`),
  getByEmployee: (employeeId, params) => api.get(`/special-duty-assignments/employee/${employeeId}`, { params }),
  getByDuty: (dutyId, params) => api.get(`/special-duty-assignments/duty/${dutyId}`, { params }),
  getByWeek: (weekStart, params) => api.get(`/special-duty-assignments/week/${weekStart}`, { params }),
};

// ==================== Roster Reports API ====================
// تقارير الجداول
export const rosterReportsApi = {
  getWeeklySummary: (params) => api.get('/roster-reports/weekly-summary', { params }),
  getMonthlySummary: (params) => api.get('/roster-reports/monthly-summary', { params }),
  getEmployeeHours: (employeeId, params) => api.get(`/roster-reports/employee-hours/${employeeId}`, { params }),
  getDepartmentCoverage: (departmentId, params) => api.get(`/roster-reports/department-coverage/${departmentId}`, { params }),
  getOvertimeReport: (params) => api.get('/roster-reports/overtime', { params }),
  getAbsenceReport: (params) => api.get('/roster-reports/absence', { params }),
  getLateReport: (params) => api.get('/roster-reports/late-arrivals', { params }),
  getShiftDistribution: (params) => api.get('/roster-reports/shift-distribution', { params }),
  getGapAnalysis: (params) => api.get('/roster-reports/gap-analysis', { params }),
};

// ==================== Roster Validation API ====================
// التحقق من صحة الجدول
export const rosterValidationApi = {
  validateRoster: (rosterId) => api.post(`/roster-validation/validate/${rosterId}`),
  getValidationRules: () => api.get('/roster-validation/rules'),
  createRule: (data) => api.post('/roster-validation/rules', data),
  updateRule: (id, data) => api.put(`/roster-validation/rules/${id}`, data),
  deleteRule: (id) => api.delete(`/roster-validation/rules/${id}`),
  checkConflicts: (data) => api.post('/roster-validation/check-conflicts', data),
  checkOvertime: (employeeId, params) => api.get(`/roster-validation/check-overtime/${employeeId}`, { params }),
};

export default {
  shiftPatternsApi,
  rostersApi,
  rosterAssignmentsApi,
  shiftSwapRequestsApi,
  attendanceRecordsApi,
  biometricDevicesApi,
  onCallAssignmentsApi,
  specialDutiesApi,
  specialDutyAssignmentsApi,
  rosterReportsApi,
  rosterValidationApi,
};
