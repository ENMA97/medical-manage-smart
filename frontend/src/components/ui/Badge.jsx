import React from 'react';
import clsx from 'clsx';

/**
 * مكون الشارة
 * Badge Component
 */
export default function Badge({
  children,
  variant = 'default',
  size = 'md',
  dot = false,
  className = '',
}) {
  const variantClasses = {
    default: 'bg-gray-100 text-gray-800',
    primary: 'bg-primary-100 text-primary-800',
    success: 'bg-green-100 text-green-800',
    warning: 'bg-yellow-100 text-yellow-800',
    danger: 'bg-red-100 text-red-800',
    info: 'bg-blue-100 text-blue-800',
    purple: 'bg-purple-100 text-purple-800',
  };

  const sizeClasses = {
    sm: 'px-2 py-0.5 text-xs',
    md: 'px-2.5 py-0.5 text-xs',
    lg: 'px-3 py-1 text-sm',
  };

  return (
    <span
      className={clsx(
        'inline-flex items-center font-medium rounded-full',
        variantClasses[variant] || variantClasses.default,
        sizeClasses[size] || sizeClasses.md,
        className
      )}
    >
      {dot && (
        <span
          className={clsx(
            'w-1.5 h-1.5 rounded-full me-1.5',
            variant === 'success' && 'bg-green-500',
            variant === 'warning' && 'bg-yellow-500',
            variant === 'danger' && 'bg-red-500',
            variant === 'info' && 'bg-blue-500',
            variant === 'primary' && 'bg-primary-500',
            variant === 'default' && 'bg-gray-500'
          )}
        />
      )}
      {children}
    </span>
  );
}

// Pre-configured status badges
export function StatusBadge({ status, labels = {} }) {
  const defaultLabels = {
    draft: 'مسودة',
    pending: 'قيد الانتظار',
    pending_supervisor: 'بانتظار المشرف',
    pending_admin_manager: 'بانتظار المدير الإداري',
    pending_hr: 'بانتظار الموارد البشرية',
    pending_delegate: 'بانتظار القائم بالعمل',
    pending_medical_director: 'بانتظار المدير الطبي',
    pending_general_manager: 'بانتظار المدير العام',
    form_completed: 'اكتمل النموذج',
    approved: 'معتمد',
    rejected: 'مرفوض',
    cancelled: 'ملغي',
    completed: 'مكتمل',
    active: 'نشط',
    inactive: 'غير نشط',
    paid: 'مدفوع',
  };

  const statusVariants = {
    draft: 'default',
    pending: 'warning',
    pending_supervisor: 'warning',
    pending_admin_manager: 'warning',
    pending_hr: 'info',
    pending_delegate: 'purple',
    pending_medical_director: 'info',
    pending_general_manager: 'purple',
    form_completed: 'success',
    approved: 'success',
    rejected: 'danger',
    cancelled: 'default',
    completed: 'success',
    active: 'success',
    inactive: 'default',
    paid: 'success',
  };

  const label = labels[status] || defaultLabels[status] || status;
  const variant = statusVariants[status] || 'default';

  return (
    <Badge variant={variant} dot>
      {label}
    </Badge>
  );
}
