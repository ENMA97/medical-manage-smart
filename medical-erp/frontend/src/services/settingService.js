import api from './api';

const settingService = {
  getAll: (params = {}) => api.get('/settings', { params }),
  update: (id, data) => api.put(`/settings/${id}`, data),
};

export default settingService;
