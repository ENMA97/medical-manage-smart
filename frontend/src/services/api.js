import axios from 'axios';
import toast from 'react-hot-toast';

const API_BASE_URL = import.meta.env.VITE_API_URL || '/api';

// ثوابت التخزين المحلي
export const STORAGE_KEYS = {
  TOKEN: 'token',
  LOCALE: 'locale',
  CSRF_TOKEN: 'csrf_token',
};

// رسائل الخطأ حسب اللغة
const ERROR_MESSAGES = {
  ar: {
    network: 'خطأ في الاتصال بالشبكة',
    unauthorized: 'انتهت صلاحية الجلسة. يرجى تسجيل الدخول مجدداً',
    forbidden: 'غير مصرح لك بهذا الإجراء',
    notFound: 'العنصر غير موجود',
    tooManyRequests: 'عدد الطلبات كثير جداً. يرجى الانتظار',
    serverError: 'حدث خطأ في الخادم. يرجى المحاولة لاحقاً',
  },
  en: {
    network: 'Network connection error',
    unauthorized: 'Session expired. Please login again',
    forbidden: 'You are not authorized for this action',
    notFound: 'Item not found',
    tooManyRequests: 'Too many requests. Please wait',
    serverError: 'Server error. Please try again later',
  },
};

// الحصول على رسالة الخطأ حسب اللغة الحالية
const getErrorMessage = (key) => {
  const locale = localStorage.getItem(STORAGE_KEYS.LOCALE) || 'ar';
  return ERROR_MESSAGES[locale]?.[key] || ERROR_MESSAGES.ar[key];
};

const api = axios.create({
  baseURL: API_BASE_URL,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
  timeout: 30000,
  withCredentials: true, // مهم لـ CSRF cookies
});

// Request interceptor - add auth token and CSRF
api.interceptors.request.use(
  (config) => {
    // إضافة Token المصادقة
    const token = localStorage.getItem(STORAGE_KEYS.TOKEN);
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }

    // إضافة CSRF Token للطلبات غير الآمنة
    const csrfToken = getCsrfToken();
    if (csrfToken && ['post', 'put', 'patch', 'delete'].includes(config.method?.toLowerCase())) {
      config.headers['X-CSRF-TOKEN'] = csrfToken;
      config.headers['X-XSRF-TOKEN'] = csrfToken;
    }

    // إضافة اللغة
    const locale = localStorage.getItem(STORAGE_KEYS.LOCALE) || 'ar';
    config.headers['Accept-Language'] = locale;

    return config;
  },
  (error) => {
    return Promise.reject(error);
  }
);

// Response interceptor - handle errors
api.interceptors.response.use(
  (response) => {
    // تحديث CSRF token من الاستجابة إن وجد
    const newCsrfToken = response.headers['x-csrf-token'];
    if (newCsrfToken) {
      localStorage.setItem(STORAGE_KEYS.CSRF_TOKEN, newCsrfToken);
    }
    return response;
  },
  (error) => {
    const { response } = error;

    // خطأ في الشبكة
    if (!response) {
      toast.error(getErrorMessage('network'));
      return Promise.reject(error);
    }

    const { status, data } = response;

    switch (status) {
      case 401:
        // غير مصرح - مسح Token والتوجيه لتسجيل الدخول
        localStorage.removeItem(STORAGE_KEYS.TOKEN);
        if (window.location.pathname !== '/login') {
          // استخدام history API بدلاً من window.location لمنع فقدان الحالة
          const loginUrl = `/login?redirect=${encodeURIComponent(window.location.pathname)}`;
          window.location.href = loginUrl;
        }
        break;

      case 403:
        toast.error(data.message || getErrorMessage('forbidden'));
        break;

      case 404:
        toast.error(data.message || getErrorMessage('notFound'));
        break;

      case 419:
        // CSRF token mismatch - تحديث التوكن وإعادة المحاولة
        refreshCsrfToken();
        toast.error('يرجى المحاولة مرة أخرى');
        break;

      case 422:
        // أخطاء التحقق - يتركها للمكون
        break;

      case 429:
        toast.error(getErrorMessage('tooManyRequests'));
        break;

      case 500:
        toast.error(getErrorMessage('serverError'));
        break;

      default:
        if (data.message) {
          toast.error(data.message);
        }
    }

    return Promise.reject(error);
  }
);

/**
 * الحصول على CSRF token من الكوكيز أو التخزين المحلي
 */
function getCsrfToken() {
  // محاولة من الكوكيز أولاً (Laravel Sanctum)
  const cookies = document.cookie.split(';');
  for (const cookie of cookies) {
    const [name, value] = cookie.trim().split('=');
    if (name === 'XSRF-TOKEN') {
      return decodeURIComponent(value);
    }
  }
  // محاولة من التخزين المحلي
  return localStorage.getItem(STORAGE_KEYS.CSRF_TOKEN);
}

/**
 * تحديث CSRF token من الخادم
 */
async function refreshCsrfToken() {
  try {
    await axios.get(`${API_BASE_URL}/sanctum/csrf-cookie`, {
      withCredentials: true,
    });
  } catch (error) {
    console.error('Failed to refresh CSRF token:', error);
  }
}

/**
 * تهيئة CSRF token عند بدء التطبيق
 */
export async function initializeCsrf() {
  try {
    await refreshCsrfToken();
  } catch (error) {
    console.error('Failed to initialize CSRF:', error);
  }
}

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
