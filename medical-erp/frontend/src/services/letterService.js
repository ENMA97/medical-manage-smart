import api from './api';

const letterService = {
  getTemplates: (params = {}) => api.get('/letter-templates', { params }),
  getAll: (params = {}) => api.get('/letters', { params }),
  getById: (id) => api.get(`/letters/${id}`),
  create: (data) => api.post('/letters', data),
  approve: (id) => api.post(`/letters/${id}/approve`),
};

export default letterService;
