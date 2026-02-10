import React from 'react';
import { Navigate, useLocation } from 'react-router-dom';
import { useAuth } from '../contexts/AuthContext';
import LoadingSpinner from './ui/LoadingSpinner';

/**
 * مكون الحماية للمسارات المحمية
 * Protected Route Component
 */
export default function ProtectedRoute({
  children,
  permission,
  permissions,
  requireAll = false,
  roles,
  fallback = '/login',
}) {
  const { isAuthenticated, loading, hasPermission, hasAnyPermission, hasAllPermissions, hasRole } =
    useAuth();
  const location = useLocation();

  // Show loading spinner while checking auth
  if (loading) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-gray-50">
        <LoadingSpinner size="lg" />
      </div>
    );
  }

  // Redirect to login if not authenticated
  if (!isAuthenticated) {
    return <Navigate to="/login" state={{ from: location }} replace />;
  }

  // Check single permission
  if (permission && !hasPermission(permission)) {
    return <Navigate to={fallback} replace />;
  }

  // Check multiple permissions
  if (permissions && permissions.length > 0) {
    const hasAccess = requireAll
      ? hasAllPermissions(permissions)
      : hasAnyPermission(permissions);

    if (!hasAccess) {
      return <Navigate to={fallback} replace />;
    }
  }

  // Check roles
  if (roles && roles.length > 0) {
    const hasRoleAccess = roles.some((role) => hasRole(role));
    if (!hasRoleAccess) {
      return <Navigate to={fallback} replace />;
    }
  }

  return children;
}
