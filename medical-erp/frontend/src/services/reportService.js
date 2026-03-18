import api from './api';

const reportService = {
  getEmployeeReport: (params = {}) => api.get('/dashboard/employee-stats', { params }),
  getLeaveReport: (params = {}) => api.get('/dashboard/leave-stats', { params }),
  getSummary: (params = {}) => api.get('/dashboard/summary', { params }),
  getAlerts: (params = {}) => api.get('/dashboard/alerts', { params }),

  exportPayroll: (payrollId) => api.get(`/payrolls/${payrollId}/export`, { responseType: 'blob' }),
};

export default reportService;
