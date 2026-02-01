import api from './api';

export const locationService = {
  getRegions: (params = {}) => api.get('/regions', { params }).then((r) => r.data),
  getRegion: (id) => api.get(`/regions/${id}`).then((r) => r.data),
  getCounties: (params = {}) => api.get('/counties', { params }).then((r) => r.data),
  getCounty: (id) => api.get(`/counties/${id}`).then((r) => r.data),
};
