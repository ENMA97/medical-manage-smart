import React, { createContext, useContext, useState, useEffect, useCallback, useRef } from 'react';
import { useNavigate } from 'react-router-dom';
import api, { STORAGE_KEYS } from '../services/api';

const AuthContext = createContext(null);

/**
 * مزود سياق المصادقة
 * Auth Context Provider with proper error handling and race condition prevention
 */
export function AuthProvider({ children }) {
  const [user, setUser] = useState(null);
  const [token, setToken] = useState(() => localStorage.getItem(STORAGE_KEYS.TOKEN));
  const [loading, setLoading] = useState(true);
  const [permissions, setPermissions] = useState([]);
  const [authError, setAuthError] = useState(null);
  const navigate = useNavigate();

  // Reference to track if component is mounted (prevent memory leaks)
  const isMounted = useRef(true);
  // AbortController for canceling pending requests
  const abortControllerRef = useRef(null);

  // Cleanup on unmount
  useEffect(() => {
    return () => {
      isMounted.current = false;
      if (abortControllerRef.current) {
        abortControllerRef.current.abort();
      }
    };
  }, []);

  // Check if user is authenticated on mount
  useEffect(() => {
    const initAuth = async () => {
      // Cancel any pending request
      if (abortControllerRef.current) {
        abortControllerRef.current.abort();
      }

      // Create new AbortController for this request
      abortControllerRef.current = new AbortController();

      if (token) {
        try {
          const response = await api.get('/auth/me', {
            signal: abortControllerRef.current.signal,
          });

          // Only update state if component is still mounted
          if (isMounted.current) {
            setUser(response.data.user);
            setPermissions(response.data.permissions || []);
            setAuthError(null);
          }
        } catch (error) {
          // Ignore abort errors
          if (error.name === 'AbortError' || error.name === 'CanceledError') {
            return;
          }

          // Token is invalid, clear it
          if (isMounted.current) {
            localStorage.removeItem(STORAGE_KEYS.TOKEN);
            setToken(null);
            setUser(null);
            setPermissions([]);
            setAuthError(error.response?.data?.message || 'فشل التحقق من المصادقة');
          }
        }
      }

      if (isMounted.current) {
        setLoading(false);
      }
    };

    initAuth();

    // Cleanup function to abort request on token change
    return () => {
      if (abortControllerRef.current) {
        abortControllerRef.current.abort();
      }
    };
  }, [token]);

  /**
   * تسجيل الدخول
   * Login with proper error handling
   */
  const login = useCallback(async (email, password) => {
    setAuthError(null);

    try {
      const response = await api.post('/auth/login', { email, password });
      const { token: newToken, user: userData, permissions: userPermissions } = response.data;

      localStorage.setItem(STORAGE_KEYS.TOKEN, newToken);
      setToken(newToken);
      setUser(userData);
      setPermissions(userPermissions || []);

      return { success: true, data: response.data };
    } catch (error) {
      const errorMessage = error.response?.data?.message || 'فشل تسجيل الدخول';
      const validationErrors = error.response?.data?.errors || {};

      setAuthError(errorMessage);

      return {
        success: false,
        error: errorMessage,
        validationErrors,
        status: error.response?.status,
      };
    }
  }, []);

  /**
   * تسجيل الخروج
   * Logout with proper cleanup
   */
  const logout = useCallback(async () => {
    try {
      await api.post('/auth/logout');
    } catch (error) {
      // Log but don't block logout
      console.error('Logout API error:', error);
    } finally {
      // Always clear local state
      localStorage.removeItem(STORAGE_KEYS.TOKEN);
      setToken(null);
      setUser(null);
      setPermissions([]);
      setAuthError(null);
      navigate('/login');
    }
  }, [navigate]);

  /**
   * تحديث بيانات المستخدم
   */
  const updateUser = useCallback((userData) => {
    setUser((prev) => ({ ...prev, ...userData }));
  }, []);

  /**
   * تحديث كلمة المرور
   */
  const updatePassword = useCallback(async (currentPassword, newPassword) => {
    try {
      await api.put('/auth/password', {
        current_password: currentPassword,
        password: newPassword,
        password_confirmation: newPassword,
      });
      return { success: true };
    } catch (error) {
      return {
        success: false,
        error: error.response?.data?.message || 'فشل تحديث كلمة المرور',
        validationErrors: error.response?.data?.errors || {},
      };
    }
  }, []);

  /**
   * التحقق من وجود صلاحية
   */
  const hasPermission = useCallback(
    (permission) => {
      // Strict null/undefined check
      if (permission === null || permission === undefined) {
        return false;
      }
      // Allow empty string to mean "no permission required"
      if (permission === '') {
        return true;
      }
      // Super admin has all permissions
      if (user?.is_super_admin) {
        return true;
      }
      // Check if permission exists in array
      if (!Array.isArray(permissions)) {
        return false;
      }
      return permissions.includes(permission);
    },
    [permissions, user]
  );

  /**
   * التحقق من وجود أي صلاحية من قائمة
   */
  const hasAnyPermission = useCallback(
    (permissionList) => {
      if (!permissionList || !Array.isArray(permissionList) || permissionList.length === 0) {
        return true;
      }
      if (user?.is_super_admin) {
        return true;
      }
      if (!Array.isArray(permissions)) {
        return false;
      }
      return permissionList.some((p) => permissions.includes(p));
    },
    [permissions, user]
  );

  /**
   * التحقق من وجود جميع الصلاحيات من قائمة
   */
  const hasAllPermissions = useCallback(
    (permissionList) => {
      if (!permissionList || !Array.isArray(permissionList) || permissionList.length === 0) {
        return true;
      }
      if (user?.is_super_admin) {
        return true;
      }
      if (!Array.isArray(permissions)) {
        return false;
      }
      return permissionList.every((p) => permissions.includes(p));
    },
    [permissions, user]
  );

  /**
   * التحقق من وجود دور
   */
  const hasRole = useCallback(
    (role) => {
      if (!role) return true;
      if (!user?.roles || !Array.isArray(user.roles)) {
        return false;
      }
      return user.roles.some((r) => r.name === role || r.slug === role);
    },
    [user]
  );

  /**
   * التحقق من وجود أي دور من قائمة
   */
  const hasAnyRole = useCallback(
    (roleList) => {
      if (!roleList || !Array.isArray(roleList) || roleList.length === 0) {
        return true;
      }
      if (!user?.roles || !Array.isArray(user.roles)) {
        return false;
      }
      return roleList.some((role) =>
        user.roles.some((r) => r.name === role || r.slug === role)
      );
    },
    [user]
  );

  /**
   * مسح خطأ المصادقة
   */
  const clearAuthError = useCallback(() => {
    setAuthError(null);
  }, []);

  /**
   * إعادة تحميل بيانات المستخدم
   */
  const refreshUser = useCallback(async () => {
    if (!token) return;

    try {
      const response = await api.get('/auth/me');
      setUser(response.data.user);
      setPermissions(response.data.permissions || []);
      return { success: true };
    } catch (error) {
      return {
        success: false,
        error: error.response?.data?.message || 'فشل تحديث بيانات المستخدم',
      };
    }
  }, [token]);

  const value = {
    // State
    user,
    token,
    loading,
    isAuthenticated: !!user && !!token,
    permissions,
    authError,

    // Actions
    login,
    logout,
    updateUser,
    updatePassword,
    refreshUser,
    clearAuthError,

    // Permission checks
    hasPermission,
    hasAnyPermission,
    hasAllPermissions,
    hasRole,
    hasAnyRole,
  };

  return (
    <AuthContext.Provider value={value}>
      {children}
    </AuthContext.Provider>
  );
}

/**
 * هوك استخدام سياق المصادقة
 */
export function useAuth() {
  const context = useContext(AuthContext);
  if (!context) {
    throw new Error('useAuth must be used within an AuthProvider');
  }
  return context;
}

export default AuthContext;
