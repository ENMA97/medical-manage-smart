import React from 'react';
import { Navigate, useLocation } from 'react-router-dom';
import { useAuth } from '../contexts/AuthContext';
import LoadingSpinner from './ui/LoadingSpinner';

/**
 * مكون الحماية للمسارات المحمية
 * Protected Route Component
 *
 * @param {Object} props
 * @param {React.ReactNode} props.children - المحتوى المحمي
 * @param {string} [props.permission] - صلاحية واحدة مطلوبة
 * @param {string[]} [props.permissions] - قائمة صلاحيات مطلوبة
 * @param {boolean} [props.requireAll=false] - هل يجب توفر جميع الصلاحيات
 * @param {string[]} [props.roles] - قائمة الأدوار المسموح بها
 * @param {string} [props.fallback] - مسار إعادة التوجيه عند عدم الصلاحية
 * @param {boolean} [props.showUnauthorized=true] - عرض صفحة عدم الصلاحية بدلاً من إعادة التوجيه
 */
export default function ProtectedRoute({
  children,
  permission,
  permissions,
  requireAll = false,
  roles,
  fallback,
  showUnauthorized = true,
}) {
  const {
    isAuthenticated,
    loading,
    hasPermission,
    hasAnyPermission,
    hasAllPermissions,
    hasRole,
    hasAnyRole,
  } = useAuth();
  const location = useLocation();

  // Validate permission prop
  const validPermission = typeof permission === 'string' && permission.trim() !== '';

  // Validate permissions array prop
  const validPermissions = Array.isArray(permissions) && permissions.length > 0 &&
    permissions.every((p) => typeof p === 'string' && p.trim() !== '');

  // Validate roles array prop
  const validRoles = Array.isArray(roles) && roles.length > 0 &&
    roles.every((r) => typeof r === 'string' && r.trim() !== '');

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

  // Check authorization
  let isAuthorized = true;
  let authCheckPerformed = false;

  // Check single permission
  if (validPermission) {
    authCheckPerformed = true;
    if (!hasPermission(permission)) {
      isAuthorized = false;
    }
  }

  // Check multiple permissions
  if (isAuthorized && validPermissions) {
    authCheckPerformed = true;
    const hasAccess = requireAll
      ? hasAllPermissions(permissions)
      : hasAnyPermission(permissions);

    if (!hasAccess) {
      isAuthorized = false;
    }
  }

  // Check roles
  if (isAuthorized && validRoles) {
    authCheckPerformed = true;
    // Use hasAnyRole if available, otherwise fallback to checking each role
    const hasRoleAccess = hasAnyRole
      ? hasAnyRole(roles)
      : roles.some((role) => hasRole(role));

    if (!hasRoleAccess) {
      isAuthorized = false;
    }
  }

  // Handle unauthorized access
  if (!isAuthorized) {
    // If fallback is provided, redirect to it
    if (fallback) {
      return <Navigate to={fallback} state={{ from: location }} replace />;
    }

    // Show unauthorized page
    if (showUnauthorized) {
      return <Navigate to="/unauthorized" state={{ from: location }} replace />;
    }

    // Default: redirect to home
    return <Navigate to="/" replace />;
  }

  return children;
}

/**
 * مكون تحقق من صلاحية واحدة
 * Single Permission Check Component
 */
export function RequirePermission({ permission, children, fallback }) {
  return (
    <ProtectedRoute permission={permission} fallback={fallback}>
      {children}
    </ProtectedRoute>
  );
}

/**
 * مكون تحقق من أي صلاحية من القائمة
 * Any Permission Check Component
 */
export function RequireAnyPermission({ permissions, children, fallback }) {
  return (
    <ProtectedRoute permissions={permissions} requireAll={false} fallback={fallback}>
      {children}
    </ProtectedRoute>
  );
}

/**
 * مكون تحقق من جميع الصلاحيات
 * All Permissions Check Component
 */
export function RequireAllPermissions({ permissions, children, fallback }) {
  return (
    <ProtectedRoute permissions={permissions} requireAll={true} fallback={fallback}>
      {children}
    </ProtectedRoute>
  );
}

/**
 * مكون تحقق من دور معين
 * Role Check Component
 */
export function RequireRole({ role, children, fallback }) {
  return (
    <ProtectedRoute roles={[role]} fallback={fallback}>
      {children}
    </ProtectedRoute>
  );
}

/**
 * مكون تحقق من أي دور من القائمة
 * Any Role Check Component
 */
export function RequireAnyRole({ roles, children, fallback }) {
  return (
    <ProtectedRoute roles={roles} fallback={fallback}>
      {children}
    </ProtectedRoute>
  );
}
