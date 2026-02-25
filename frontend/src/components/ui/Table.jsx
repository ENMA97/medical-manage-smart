import React from 'react';
import clsx from 'clsx';
import LoadingSpinner from './LoadingSpinner';

/**
 * مكون الجدول
 * Table Component
 */
export function Table({ children, className = '', caption, 'aria-label': ariaLabel }) {
  return (
    <div className="overflow-x-auto" role="region" aria-label={ariaLabel || caption}>
      <table className={clsx('min-w-full divide-y divide-gray-200', className)}>
        {caption && <caption className="sr-only">{caption}</caption>}
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

export function TableRow({ children, className = '', onClick, hoverable = true, selected = false }) {
  const isClickable = typeof onClick === 'function';

  return (
    <tr
      className={clsx(
        hoverable && 'hover:bg-gray-50',
        isClickable && 'cursor-pointer',
        selected && 'bg-primary-50',
        className
      )}
      onClick={onClick}
      tabIndex={isClickable ? 0 : undefined}
      role={isClickable ? 'button' : undefined}
      onKeyDown={isClickable ? (e) => {
        if (e.key === 'Enter' || e.key === ' ') {
          e.preventDefault();
          onClick(e);
        }
      } : undefined}
      aria-selected={selected || undefined}
    >
      {children}
    </tr>
  );
}

export function TableHeader({
  children,
  className = '',
  sortable = false,
  sorted,
  onSort,
  'aria-label': ariaLabel
}) {
  const getSortLabel = () => {
    if (!sortable) return undefined;
    if (sorted === 'asc') return 'مرتب تصاعدياً، اضغط للترتيب تنازلياً';
    if (sorted === 'desc') return 'مرتب تنازلياً، اضغط لإزالة الترتيب';
    return 'اضغط للترتيب تصاعدياً';
  };

  return (
    <th
      scope="col"
      className={clsx(
        'px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase tracking-wider',
        sortable && 'cursor-pointer select-none hover:bg-gray-100',
        className
      )}
      onClick={sortable ? onSort : undefined}
      tabIndex={sortable ? 0 : undefined}
      onKeyDown={sortable ? (e) => {
        if (e.key === 'Enter' || e.key === ' ') {
          e.preventDefault();
          onSort(e);
        }
      } : undefined}
      aria-sort={sorted ? (sorted === 'asc' ? 'ascending' : 'descending') : undefined}
      aria-label={ariaLabel || getSortLabel()}
      role={sortable ? 'columnheader button' : 'columnheader'}
    >
      <div className="flex items-center gap-1">
        {children}
        {sortable && (
          <svg
            className={clsx(
              'w-4 h-4 transition-transform',
              sorted === 'desc' && 'rotate-180',
              !sorted && 'opacity-0 group-hover:opacity-50'
            )}
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
            aria-hidden="true"
          >
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 15l7-7 7 7" />
          </svg>
        )}
      </div>
    </th>
  );
}

export function TableCell({ children, className = '', colSpan }) {
  return (
    <td
      className={clsx('px-6 py-4 whitespace-nowrap text-sm text-gray-900', className)}
      colSpan={colSpan}
    >
      {children}
    </td>
  );
}

export function TableEmpty({ message, colSpan = 1, icon }) {
  const defaultMessage = {
    ar: 'لا توجد بيانات',
    en: 'No data available',
  };

  return (
    <tr>
      <td colSpan={colSpan} className="px-6 py-12 text-center text-gray-500">
        {icon || (
          <svg
            className="mx-auto h-12 w-12 text-gray-400"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
            aria-hidden="true"
          >
            <path
              strokeLinecap="round"
              strokeLinejoin="round"
              strokeWidth={2}
              d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
            />
          </svg>
        )}
        <p className="mt-2">{message || defaultMessage.ar}</p>
      </td>
    </tr>
  );
}

export function TableLoading({ colSpan = 1, message }) {
  const defaultMessage = {
    ar: 'جاري التحميل...',
    en: 'Loading...',
  };

  return (
    <tr>
      <td colSpan={colSpan} className="px-6 py-12 text-center">
        <LoadingSpinner size="lg" className="mx-auto" label={message || defaultMessage.ar} />
        {message && <p className="mt-2 text-sm text-gray-500">{message}</p>}
      </td>
    </tr>
  );
}

export function TablePagination({
  currentPage,
  totalPages,
  totalItems,
  itemsPerPage,
  onPageChange,
  onItemsPerPageChange,
  itemsPerPageOptions = [10, 25, 50, 100],
}) {
  const startItem = (currentPage - 1) * itemsPerPage + 1;
  const endItem = Math.min(currentPage * itemsPerPage, totalItems);

  return (
    <div className="flex items-center justify-between px-6 py-3 bg-gray-50 border-t border-gray-200">
      <div className="flex items-center gap-4 text-sm text-gray-700">
        <span>
          عرض {startItem} - {endItem} من {totalItems}
        </span>
        {onItemsPerPageChange && (
          <select
            value={itemsPerPage}
            onChange={(e) => onItemsPerPageChange(Number(e.target.value))}
            className="rounded border-gray-300 text-sm focus:ring-primary-500 focus:border-primary-500"
            aria-label="عدد العناصر في الصفحة"
          >
            {itemsPerPageOptions.map((option) => (
              <option key={option} value={option}>
                {option} لكل صفحة
              </option>
            ))}
          </select>
        )}
      </div>

      <nav className="flex items-center gap-1" aria-label="تنقل بين الصفحات">
        <button
          onClick={() => onPageChange(currentPage - 1)}
          disabled={currentPage === 1}
          className="p-2 rounded hover:bg-gray-200 disabled:opacity-50 disabled:cursor-not-allowed"
          aria-label="الصفحة السابقة"
        >
          <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5l7 7-7 7" />
          </svg>
        </button>

        <span className="px-3 py-1 text-sm">
          صفحة {currentPage} من {totalPages}
        </span>

        <button
          onClick={() => onPageChange(currentPage + 1)}
          disabled={currentPage === totalPages}
          className="p-2 rounded hover:bg-gray-200 disabled:opacity-50 disabled:cursor-not-allowed"
          aria-label="الصفحة التالية"
        >
          <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" />
          </svg>
        </button>
      </nav>
    </div>
  );
}

export default Table;
