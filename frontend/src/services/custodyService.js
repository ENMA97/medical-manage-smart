import api from './api';

const custodyService = {
  getAll: (params = {}) => api.get('/custody', { params }),
  getById: (id) => api.get(`/custody/${id}`),
  create: (data) => api.post('/custody', data),
  returnItem: (id, data = {}) => api.post(`/custody/${id}/return`, data),
};

export default custodyService;
