import api from './api';
import { isDemoMode } from './demoAuth';
import { demoAttendance } from './demoHrms';

const attendanceService = {
  checkIn: () => (isDemoMode() ? demoAttendance.checkIn() : api.post('/attendance/check-in')),
  checkOut: () => (isDemoMode() ? demoAttendance.checkOut() : api.post('/attendance/check-out')),
  getMy: (params = {}) => (isDemoMode() ? demoAttendance.getMy(params) : api.get('/attendance/my', { params })),
  getAll: (params = {}) => (isDemoMode() ? demoAttendance.getAll(params) : api.get('/attendance', { params })),
  create: (data) => (isDemoMode() ? demoAttendance.create(data) : api.post('/attendance', data)),
  update: (id, data) => (isDemoMode() ? demoAttendance.update(id, data) : api.put(`/attendance/${id}`, data)),
  getDailySummary: (params = {}) => (isDemoMode() ? demoAttendance.getDailySummary(params) : api.get('/attendance/daily-summary', { params })),
  getMonthlyReport: (params = {}) => (isDemoMode() ? demoAttendance.getMonthlyReport(params) : api.get('/attendance/monthly-report', { params })),
};

export default attendanceService;
