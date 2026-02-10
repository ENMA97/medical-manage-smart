import React, { createContext, useContext, useState, useEffect, useCallback } from 'react';
import { useNavigate } from 'react-router-dom';
import api from '../services/api';

const AuthContext = createContext(null);

export function AuthProvider({ children }) {
  const [user, setUser] = useState(null);
  const [token, setToken] = useState(() => localStorage.getItem('token'));
  const [loading, setLoading] = useState(true);
  const [permissions, setPermissions] = useState([]);
  const navigate = useNavigate();

  // Check if user is authenticated on mount
  useEffect(() => {
    const initAuth = async () => {
      if (token) {
        try {
          const response = await api.get('/auth/me');
          setUser(response.data.user);
          setPermissions(response.data.permissions || []);
        } catch (error) {
          // Token is invalid, clear it
          localStorage.removeItem('token');
          setToken(null);
          setUser(null);
          setPermissions([]);
        }
      }
      setLoading(false);
    };

    initAuth();
  }, [token]);

  const login = useCallback(async (email, password) => {
    const response = await api.post('/auth/login', { email, password });
    const { token: newToken, user: userData, permissions: userPermissions } = response.data;

    localStorage.setItem('token', newToken);
    setToken(newToken);
    setUser(userData);
    setPermissions(userPermissions || []);

    return response.data;
  }, []);

  const logout = useCallback(async () => {
    try {
      await api.post('/auth/logout');
    } catch (error) {
      // Ignore logout errors
    } finally {
      localStorage.removeItem('token');
      setToken(null);
      setUser(null);
      setPermissions([]);
      navigate('/login');
    }
  }, [navigate]);

  const updateUser = useCallback((userData) => {
    setUser((prev) => ({ ...prev, ...userData }));
  }, []);

  const hasPermission = useCallback(
    (permission) => {
      if (!permission) return true;
      if (user?.is_super_admin) return true;
      return permissions.includes(permission);
    },
    [permissions, user]
  );

  const hasAnyPermission = useCallback(
    (permissionList) => {
      if (!permissionList || permissionList.length === 0) return true;
      if (user?.is_super_admin) return true;
      return permissionList.some((p) => permissions.includes(p));
    },
    [permissions, user]
  );

  const hasAllPermissions = useCallback(
    (permissionList) => {
      if (!permissionList || permissionList.length === 0) return true;
      if (user?.is_super_admin) return true;
      return permissionList.every((p) => permissions.includes(p));
    },
    [permissions, user]
  );

  const hasRole = useCallback(
    (role) => {
      if (!role) return true;
      return user?.roles?.some((r) => r.name === role || r.slug === role);
    },
    [user]
  );

  const value = {
    user,
    token,
    loading,
    isAuthenticated: !!user,
    permissions,
    login,
    logout,
    updateUser,
    hasPermission,
    hasAnyPermission,
    hasAllPermissions,
    hasRole,
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

export default AuthContext;
