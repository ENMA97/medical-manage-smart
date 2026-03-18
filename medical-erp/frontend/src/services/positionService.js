import api from './api';

const positionService = {
  getAll: (params = {}) => api.get('/positions', { params }),
  getById: (id) => api.get(`/positions/${id}`),
  create: (data) => api.post('/positions', data),
  update: (id, data) => api.put(`/positions/${id}`, data),
  delete: (id) => api.delete(`/positions/${id}`),
};

export default positionService;
