import api from './api';

const aiService = {
  getDashboard: () => api.get('/ai/dashboard'),
  analyzeLeavePatterns: () => api.post('/ai/analyze/leave-patterns'),
  analyzeTurnoverRisk: () => api.post('/ai/analyze/turnover-risk'),
  getPredictions: (params = {}) => api.get('/ai/predictions', { params }),
  acknowledgePrediction: (id) => api.post(`/ai/predictions/${id}/acknowledge`),
  getRecommendations: (params = {}) => api.get('/ai/recommendations', { params }),
  reviewRecommendation: (id, data) => api.put(`/ai/recommendations/${id}/review`, data),
  getRiskScores: (params = {}) => api.get('/ai/risk-scores', { params }),
  getAnalysisLogs: (params = {}) => api.get('/ai/analysis-logs', { params }),
};

export default aiService;
