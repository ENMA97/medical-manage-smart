import React from 'react';
import clsx from 'clsx';

/**
 * مكون البطاقة
 * Card Component
 */
export function Card({ children, className = '', padding = true }) {
  return (
    <div
      className={clsx(
        'bg-white rounded-lg shadow-sm border border-gray-200',
        padding && 'p-6',
        className
      )}
    >
      {children}
    </div>
  );
}

export function CardHeader({ children, className = '', title, subtitle, action }) {
  if (title || subtitle || action) {
    return (
      <div
        className={clsx(
          'flex items-center justify-between pb-4 mb-4 border-b border-gray-200',
          className
        )}
      >
        <div>
          {title && <h3 className="text-lg font-semibold text-gray-900">{title}</h3>}
          {subtitle && <p className="mt-1 text-sm text-gray-500">{subtitle}</p>}
        </div>
        {action && <div>{action}</div>}
      </div>
    );
  }

  return (
    <div
      className={clsx('pb-4 mb-4 border-b border-gray-200', className)}
    >
      {children}
    </div>
  );
}

export function CardBody({ children, className = '' }) {
  return <div className={className}>{children}</div>;
}

export function CardFooter({ children, className = '' }) {
  return (
    <div
      className={clsx(
        'pt-4 mt-4 border-t border-gray-200 flex items-center justify-end gap-3',
        className
      )}
    >
      {children}
    </div>
  );
}

export default Card;
