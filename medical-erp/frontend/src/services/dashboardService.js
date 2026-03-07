import api from './api';

const dashboardService = {
  getSummary: () => api.get('/dashboard/summary'),
  getEmployeeStats: () => api.get('/dashboard/employee-stats'),
  getLeaveStats: () => api.get('/dashboard/leave-stats'),
  getAlerts: () => api.get('/dashboard/alerts'),
};

export default dashboardService;
