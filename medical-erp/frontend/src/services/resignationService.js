import api from './api';

const resignationService = {
  getAll: (params = {}) => api.get('/resignations', { params }),
  getById: (id) => api.get(`/resignations/${id}`),
  create: (data) => api.post('/resignations', data),
  approve: (id, data = {}) => api.post(`/resignations/${id}/approve`, data),
  reject: (id, data = {}) => api.post(`/resignations/${id}/reject`, data),
};

export default resignationService;
