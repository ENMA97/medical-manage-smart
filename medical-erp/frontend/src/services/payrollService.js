import api from './api';

const payrollService = {
  getAll: (params = {}) => api.get('/payrolls', { params }),
  getById: (id) => api.get(`/payrolls/${id}`),
  create: (data) => api.post('/payrolls', data),
  approve: (id, data = {}) => api.post(`/payrolls/${id}/approve`, data),
  export: (id) => api.get(`/payrolls/${id}/export`, { responseType: 'blob' }),
};

export default payrollService;
