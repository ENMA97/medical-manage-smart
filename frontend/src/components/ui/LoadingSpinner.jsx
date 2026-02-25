import React from 'react';
import clsx from 'clsx';

/**
 * مكون دائرة التحميل
 * Loading Spinner Component
 */
export default function LoadingSpinner({ size = 'md', className = '', color = 'primary', label }) {
  const sizeClasses = {
    sm: 'h-4 w-4 border-2',
    md: 'h-8 w-8 border-2',
    lg: 'h-12 w-12 border-3',
    xl: 'h-16 w-16 border-4',
  };

  const colorClasses = {
    primary: 'border-primary-600',
    white: 'border-white',
    gray: 'border-gray-600',
  };

  return (
    <div
      className={clsx(
        'animate-spin rounded-full border-t-transparent',
        sizeClasses[size] || sizeClasses.md,
        colorClasses[color] || colorClasses.primary,
        className
      )}
      role="status"
      aria-label={label || 'Loading'}
    >
      <span className="sr-only">{label || 'Loading...'}</span>
    </div>
  );
}
