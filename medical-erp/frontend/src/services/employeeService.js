import api from './api';
import { isDemoMode } from './demoAuth';
import { DEMO_STAFF } from './demoHrms';

const employeeService = {
  getAll: (params = {}) =>
    isDemoMode()
      ? Promise.resolve({ data: { success: true, data: { data: DEMO_STAFF, total: DEMO_STAFF.length } } })
      : api.get('/employees', { params }),
  getById: (id) => api.get(`/employees/${id}`),
  create: (data) => api.post('/employees', data),
  update: (id, data) => api.put(`/employees/${id}`, data),
  delete: (id) => api.delete(`/employees/${id}`),
  getDocuments: (id) => api.get(`/employees/${id}/documents`),
};

export default employeeService;
