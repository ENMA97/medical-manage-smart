import React, { forwardRef } from 'react';
import clsx from 'clsx';

/**
 * مكون حقل الإدخال
 * Input Component
 */
const Input = forwardRef(function Input(
  {
    id,
    name,
    type = 'text',
    label,
    placeholder,
    value,
    onChange,
    onBlur,
    error,
    hint,
    disabled = false,
    required = false,
    readOnly = false,
    className = '',
    inputClassName = '',
    icon: Icon,
    iconPosition = 'start',
    ...props
  },
  ref
) {
  const inputId = id || name;

  return (
    <div className={clsx('w-full', className)}>
      {label && (
        <label htmlFor={inputId} className="block text-sm font-medium text-gray-700 mb-1">
          {label}
          {required && <span className="text-red-500 mr-1">*</span>}
        </label>
      )}
      <div className="relative">
        {Icon && iconPosition === 'start' && (
          <div className="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
            <Icon className="w-5 h-5 text-gray-400" />
          </div>
        )}
        <input
          ref={ref}
          type={type}
          id={inputId}
          name={name}
          value={value}
          onChange={onChange}
          onBlur={onBlur}
          disabled={disabled}
          readOnly={readOnly}
          placeholder={placeholder}
          className={clsx(
            'block w-full rounded-lg border shadow-sm transition-colors duration-200',
            'placeholder-gray-400 focus:outline-none focus:ring-2 sm:text-sm',
            Icon && iconPosition === 'start' && 'ps-10',
            Icon && iconPosition === 'end' && 'pe-10',
            error
              ? 'border-red-300 focus:ring-red-500 focus:border-red-500'
              : 'border-gray-300 focus:ring-primary-500 focus:border-primary-500',
            disabled && 'bg-gray-50 text-gray-500 cursor-not-allowed',
            readOnly && 'bg-gray-50',
            'px-3 py-2',
            inputClassName
          )}
          aria-invalid={error ? 'true' : 'false'}
          aria-describedby={error ? `${inputId}-error` : hint ? `${inputId}-hint` : undefined}
          {...props}
        />
        {Icon && iconPosition === 'end' && (
          <div className="absolute inset-y-0 end-0 flex items-center pe-3 pointer-events-none">
            <Icon className="w-5 h-5 text-gray-400" />
          </div>
        )}
      </div>
      {error && (
        <p id={`${inputId}-error`} className="mt-1 text-sm text-red-600">
          {error}
        </p>
      )}
      {hint && !error && (
        <p id={`${inputId}-hint`} className="mt-1 text-sm text-gray-500">
          {hint}
        </p>
      )}
    </div>
  );
});

export default Input;
