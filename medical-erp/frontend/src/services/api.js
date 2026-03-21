import axios from 'axios';

// Use VITE_API_URL if explicitly set, otherwise use relative /api path
// In production, nginx proxies /api to the backend service
// Ensures /api suffix is always present regardless of how the env var is set
const rawUrl = import.meta.env.VITE_API_URL;
const baseURL = rawUrl
  ? (rawUrl.replace(/\/+$/, '').endsWith('/api')
    ? rawUrl.replace(/\/+$/, '')
    : `${rawUrl.replace(/\/+$/, '')}/api`)
  : '/api';

const api = axios.create({
  baseURL,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
  timeout: 30000, // 30 seconds
});

// Request interceptor — attach token + language
api.interceptors.request.use((config) => {
  const token = localStorage.getItem('auth_token');
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }

  const lang = localStorage.getItem('lang') || 'ar';
  config.headers['Accept-Language'] = lang;

  return config;
});

// Response interceptor — handle auth errors and network issues
api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      localStorage.removeItem('auth_token');
      localStorage.removeItem('user');
      window.location.href = '/login';
    }

    // Network error (offline)
    if (!error.response && error.message === 'Network Error') {
      error.message = 'لا يوجد اتصال بالإنترنت';
    }

    return Promise.reject(error);
  }
);

export default api;
