import axios from 'axios';
import toast from 'react-hot-toast';

const API_BASE_URL = import.meta.env.VITE_API_URL || '/api';

const api = axios.create({
  baseURL: API_BASE_URL,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
  timeout: 30000,
});

// Request interceptor - add auth token
api.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem('token');
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }

    // Add locale header
    const locale = localStorage.getItem('locale') || 'ar';
    config.headers['Accept-Language'] = locale;

    return config;
  },
  (error) => {
    return Promise.reject(error);
  }
);

// Response interceptor - handle errors
api.interceptors.response.use(
  (response) => response,
  (error) => {
    const { response } = error;

    if (!response) {
      toast.error('خطأ في الاتصال بالشبكة');
      return Promise.reject(error);
    }

    const { status, data } = response;

    switch (status) {
      case 401:
        // Unauthorized - clear token and redirect to login
        localStorage.removeItem('token');
        if (window.location.pathname !== '/login') {
          window.location.href = '/login';
        }
        break;

      case 403:
        toast.error(data.message || 'غير مصرح لك بهذا الإجراء');
        break;

      case 404:
        toast.error(data.message || 'العنصر غير موجود');
        break;

      case 422:
        // Validation errors - let the component handle it
        break;

      case 429:
        toast.error('عدد الطلبات كثير جداً. يرجى الانتظار.');
        break;

      case 500:
        toast.error('حدث خطأ في الخادم. يرجى المحاولة لاحقاً.');
        break;

      default:
        if (data.message) {
          toast.error(data.message);
        }
    }

    return Promise.reject(error);
  }
);

export default api;

// Helper functions for common API operations
export const apiGet = async (url, params = {}) => {
  const response = await api.get(url, { params });
  return response.data;
};

export const apiPost = async (url, data = {}) => {
  const response = await api.post(url, data);
  return response.data;
};

export const apiPut = async (url, data = {}) => {
  const response = await api.put(url, data);
  return response.data;
};

export const apiPatch = async (url, data = {}) => {
  const response = await api.patch(url, data);
  return response.data;
};

export const apiDelete = async (url) => {
  const response = await api.delete(url);
  return response.data;
};
