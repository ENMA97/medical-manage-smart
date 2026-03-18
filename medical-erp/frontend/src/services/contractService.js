import api from './api';

const contractService = {
  getAll: (params = {}) => api.get('/contracts', { params }),
  getById: (id) => api.get(`/contracts/${id}`),
  create: (data) => api.post('/contracts', data),
  update: (id, data) => api.put(`/contracts/${id}`, data),
  renew: (id, data) => api.post(`/contracts/${id}/renew`, data),
};

export default contractService;
