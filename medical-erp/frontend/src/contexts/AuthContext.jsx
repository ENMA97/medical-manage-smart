import { createContext, useContext, useState, useEffect } from 'react';
import api from '../services/api';
import { demoLogin, getDemoUser, isDemoMode } from '../services/demoAuth';

const AuthContext = createContext(null);

export function AuthProvider({ children }) {
  const [user, setUser] = useState(null);
  const [loading, setLoading] = useState(true);
  const [demoMode, setDemoMode] = useState(false);

  useEffect(() => {
    const token = localStorage.getItem('auth_token');
    if (token) {
      // Check if this is a demo token
      if (isDemoMode()) {
        const demoUser = getDemoUser();
        if (demoUser) {
          setUser(demoUser);
          setDemoMode(true);
        } else {
          localStorage.removeItem('auth_token');
          localStorage.removeItem('user');
        }
        setLoading(false);
      } else {
        fetchUser();
      }
    } else {
      setLoading(false);
    }
  }, []);

  async function fetchUser() {
    try {
      const { data } = await api.get('/auth/me');
      setUser(data.data);
      setDemoMode(false);
    } catch {
      localStorage.removeItem('auth_token');
      localStorage.removeItem('user');
      setUser(null);
    } finally {
      setLoading(false);
    }
  }

  async function login(employeeNumber, phone) {
    // Try real API first
    try {
      const { data } = await api.post('/auth/login', {
        employee_number: employeeNumber,
        phone,
      });

      localStorage.setItem('auth_token', data.data.token);
      localStorage.setItem('user', JSON.stringify(data.data.user));
      setUser(data.data.user);
      setDemoMode(false);

      return data;
    } catch (apiError) {
      // If backend is unreachable (network error, 404, 502, etc.), try demo login
      const isServerUnreachable =
        !apiError.response ||
        apiError.response.status === 502 ||
        apiError.response.status === 503 ||
        apiError.response.status === 0;

      if (isServerUnreachable) {
        const result = demoLogin(employeeNumber, phone);

        if (result.success) {
          localStorage.setItem('auth_token', result.data.token);
          localStorage.setItem('user', JSON.stringify(result.data.user));
          setUser(result.data.user);
          setDemoMode(true);
          return result;
        }

        // Demo login failed (wrong credentials) — throw with demo error message
        const demoError = new Error(result.message);
        demoError.response = {
          status: 422,
          data: { message: result.message },
        };
        throw demoError;
      }

      // Backend returned a real error (422 validation, 429 rate limit, etc.) — re-throw
      throw apiError;
    }
  }

  async function logout() {
    try {
      if (!isDemoMode()) {
        await api.post('/auth/logout');
      }
    } finally {
      localStorage.removeItem('auth_token');
      localStorage.removeItem('user');
      setUser(null);
      setDemoMode(false);
    }
  }

  async function logoutAll() {
    try {
      if (!isDemoMode()) {
        await api.post('/auth/logout-all');
      }
    } finally {
      localStorage.removeItem('auth_token');
      localStorage.removeItem('user');
      setUser(null);
      setDemoMode(false);
    }
  }

  const value = {
    user,
    loading,
    login,
    logout,
    logoutAll,
    isAuthenticated: !!user,
    demoMode,
  };

  return (
    <AuthContext.Provider value={value}>
      {children}
    </AuthContext.Provider>
  );
}

export function useAuth() {
  const context = useContext(AuthContext);
  if (!context) {
    throw new Error('useAuth must be used within an AuthProvider');
  }
  return context;
}
