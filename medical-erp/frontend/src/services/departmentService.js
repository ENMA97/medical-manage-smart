import api from './api';
import { isDemoMode } from './demoAuth';
import { DEMO_DEPARTMENTS } from './demoHrms';

const departmentService = {
  getAll: (params = {}) =>
    isDemoMode()
      ? Promise.resolve({ data: { success: true, data: { data: DEMO_DEPARTMENTS, total: DEMO_DEPARTMENTS.length } } })
      : api.get('/departments', { params }),
  getById: (id) => api.get(`/departments/${id}`),
  create: (data) => api.post('/departments', data),
  update: (id, data) => api.put(`/departments/${id}`, data),
  delete: (id) => api.delete(`/departments/${id}`),
};

export default departmentService;
