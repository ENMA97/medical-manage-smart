import api from './api';

const loanService = {
  getAll: (params = {}) => api.get('/loans', { params }),
  getById: (id) => api.get(`/loans/${id}`),
  create: (data) => api.post('/loans', data),
  approve: (id) => api.post(`/loans/${id}/approve`),
  reject: (id) => api.post(`/loans/${id}/reject`),
};

export default loanService;
