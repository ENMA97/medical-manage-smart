import api from './api';

const disciplinaryService = {
  // Violation Types
  getViolationTypes: (params = {}) => api.get('/violation-types', { params }),
  suggestPenalty: (typeId, params = {}) => api.get(`/violation-types/${typeId}/suggest-penalty`, { params }),

  // Violations
  getViolations: (params = {}) => api.get('/violations', { params }),
  getViolation: (id) => api.get(`/violations/${id}`),
  createViolation: (data) => api.post('/violations', data),

  // Committees
  formCommittee: (violationId, data) => api.post(`/violations/${violationId}/committee`, data),
  getCommittee: (id) => api.get(`/committees/${id}`),
  addSession: (committeeId, data) => api.post(`/committees/${committeeId}/sessions`, data),

  // Decisions
  getDecisions: (params = {}) => api.get('/decisions', { params }),
  getDecision: (id) => api.get(`/decisions/${id}`),
  issueDecision: (violationId, data) => api.post(`/violations/${violationId}/decision`, data),
  approveDecision: (id) => api.post(`/decisions/${id}/approve`),
};

export default disciplinaryService;
