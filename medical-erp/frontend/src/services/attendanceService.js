import api from './api';

const attendanceService = {
  checkIn: () => api.post('/attendance/check-in'),
  checkOut: () => api.post('/attendance/check-out'),
  getMy: (params = {}) => api.get('/attendance/my', { params }),
  getAll: (params = {}) => api.get('/attendance', { params }),
  create: (data) => api.post('/attendance', data),
  update: (id, data) => api.put(`/attendance/${id}`, data),
  getDailySummary: (params = {}) => api.get('/attendance/daily-summary', { params }),
  getMonthlyReport: (params = {}) => api.get('/attendance/monthly-report', { params }),
};

export default attendanceService;
