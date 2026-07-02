import api from './api';

const shiftService = {
  getAll: (params = {}) => api.get('/shifts', { params }),
  getById: (id) => api.get(`/shifts/${id}`),
  create: (data) => api.post('/shifts', data),
  update: (id, data) => api.put(`/shifts/${id}`, data),
  delete: (id) => api.delete(`/shifts/${id}`),
  assign: (id, data) => api.post(`/shifts/${id}/assign`, data),
  getAssignments: (params = {}) => api.get('/shift-assignments', { params }),
  unassign: (assignmentId) => api.delete(`/shift-assignments/${assignmentId}`),
};

export default shiftService;
