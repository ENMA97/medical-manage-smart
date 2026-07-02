import api from './api';

export const leaveTypeService = {
  getAll: (params = {}) => api.get('/leave-types', { params }),
  getById: (id) => api.get(`/leave-types/${id}`),
  create: (data) => api.post('/leave-types', data),
  update: (id, data) => api.put(`/leave-types/${id}`, data),
};

export const leaveRequestService = {
  getAll: (params = {}) => api.get('/leave-requests', { params }),
  getById: (id) => api.get(`/leave-requests/${id}`),
  create: (data) => api.post('/leave-requests', data),
  approve: (id, data = {}) => api.post(`/leave-requests/${id}/approve`, data),
  reject: (id, data = {}) => api.post(`/leave-requests/${id}/reject`, data),
  cancel: (id, data = {}) => api.post(`/leave-requests/${id}/cancel`, data),
};
