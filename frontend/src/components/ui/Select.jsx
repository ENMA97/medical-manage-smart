import React, { forwardRef } from 'react';
import clsx from 'clsx';

/**
 * مكون القائمة المنسدلة
 * Select Component
 */
const Select = forwardRef(function Select(
  {
    id,
    name,
    label,
    value,
    onChange,
    onBlur,
    options = [],
    placeholder,
    error,
    hint,
    disabled = false,
    required = false,
    className = '',
    selectClassName = '',
    ...props
  },
  ref
) {
  const selectId = id || name;

  return (
    <div className={clsx('w-full', className)}>
      {label && (
        <label htmlFor={selectId} className="block text-sm font-medium text-gray-700 mb-1">
          {label}
          {required && <span className="text-red-500 mr-1">*</span>}
        </label>
      )}
      <select
        ref={ref}
        id={selectId}
        name={name}
        value={value}
        onChange={onChange}
        onBlur={onBlur}
        disabled={disabled}
        className={clsx(
          'block w-full rounded-lg border shadow-sm transition-colors duration-200',
          'focus:outline-none focus:ring-2 sm:text-sm',
          error
            ? 'border-red-300 focus:ring-red-500 focus:border-red-500'
            : 'border-gray-300 focus:ring-primary-500 focus:border-primary-500',
          disabled && 'bg-gray-50 text-gray-500 cursor-not-allowed',
          'px-3 py-2',
          selectClassName
        )}
        aria-invalid={error ? 'true' : 'false'}
        aria-describedby={error ? `${selectId}-error` : hint ? `${selectId}-hint` : undefined}
        {...props}
      >
        {placeholder && (
          <option value="" disabled>
            {placeholder}
          </option>
        )}
        {options.map((option) => (
          <option
            key={option.value}
            value={option.value}
            disabled={option.disabled}
          >
            {option.label}
          </option>
        ))}
      </select>
      {error && (
        <p id={`${selectId}-error`} className="mt-1 text-sm text-red-600">
          {error}
        </p>
      )}
      {hint && !error && (
        <p id={`${selectId}-hint`} className="mt-1 text-sm text-gray-500">
          {hint}
        </p>
      )}
    </div>
  );
});

export default Select;
