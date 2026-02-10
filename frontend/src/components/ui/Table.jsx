import React from 'react';
import clsx from 'clsx';
import LoadingSpinner from './LoadingSpinner';

/**
 * مكون الجدول
 * Table Component
 */
export function Table({ children, className = '' }) {
  return (
    <div className="overflow-x-auto">
      <table className={clsx('min-w-full divide-y divide-gray-200', className)}>
        {children}
      </table>
    </div>
  );
}

export function TableHead({ children, className = '' }) {
  return <thead className={clsx('bg-gray-50', className)}>{children}</thead>;
}

export function TableBody({ children, className = '' }) {
  return (
    <tbody className={clsx('bg-white divide-y divide-gray-200', className)}>
      {children}
    </tbody>
  );
}

export function TableRow({ children, className = '', onClick, hoverable = true }) {
  return (
    <tr
      className={clsx(hoverable && 'hover:bg-gray-50', onClick && 'cursor-pointer', className)}
      onClick={onClick}
    >
      {children}
    </tr>
  );
}

export function TableHeader({ children, className = '', sortable = false, sorted, onSort }) {
  return (
    <th
      scope="col"
      className={clsx(
        'px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase tracking-wider',
        sortable && 'cursor-pointer select-none hover:bg-gray-100',
        className
      )}
      onClick={sortable ? onSort : undefined}
    >
      <div className="flex items-center gap-1">
        {children}
        {sortable && sorted && (
          <svg
            className={clsx('w-4 h-4', sorted === 'desc' && 'rotate-180')}
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 15l7-7 7 7" />
          </svg>
        )}
      </div>
    </th>
  );
}

export function TableCell({ children, className = '' }) {
  return (
    <td className={clsx('px-6 py-4 whitespace-nowrap text-sm text-gray-900', className)}>
      {children}
    </td>
  );
}

export function TableEmpty({ message = 'لا توجد بيانات', colSpan = 1 }) {
  return (
    <tr>
      <td colSpan={colSpan} className="px-6 py-12 text-center text-gray-500">
        <svg
          className="mx-auto h-12 w-12 text-gray-400"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            strokeLinecap="round"
            strokeLinejoin="round"
            strokeWidth={2}
            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
          />
        </svg>
        <p className="mt-2">{message}</p>
      </td>
    </tr>
  );
}

export function TableLoading({ colSpan = 1 }) {
  return (
    <tr>
      <td colSpan={colSpan} className="px-6 py-12 text-center">
        <LoadingSpinner size="lg" className="mx-auto" />
      </td>
    </tr>
  );
}

export default Table;
