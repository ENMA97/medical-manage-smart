/**
 * Custom Hooks
 * هوكات مخصصة للاستخدام المشترك
 */

import { useState, useEffect, useCallback, useRef, useMemo } from 'react';

// =============================================================================
// useDebounce - تأخير القيمة
// =============================================================================
/**
 * تأخير تحديث القيمة حتى يتوقف المستخدم عن الكتابة
 * @param {any} value - القيمة المراد تأخيرها
 * @param {number} delay - وقت التأخير بالمللي ثانية
 */
export function useDebounce(value, delay = 300) {
  const [debouncedValue, setDebouncedValue] = useState(value);

  useEffect(() => {
    const handler = setTimeout(() => {
      setDebouncedValue(value);
    }, delay);

    return () => {
      clearTimeout(handler);
    };
  }, [value, delay]);

  return debouncedValue;
}

// =============================================================================
// useLocalStorage - التخزين المحلي
// =============================================================================
/**
 * حفظ واسترجاع القيم من التخزين المحلي
 * @param {string} key - مفتاح التخزين
 * @param {any} initialValue - القيمة الافتراضية
 */
export function useLocalStorage(key, initialValue) {
  const [storedValue, setStoredValue] = useState(() => {
    try {
      const item = window.localStorage.getItem(key);
      return item ? JSON.parse(item) : initialValue;
    } catch (error) {
      console.error(`Error reading localStorage key "${key}":`, error);
      return initialValue;
    }
  });

  const setValue = useCallback((value) => {
    try {
      const valueToStore = value instanceof Function ? value(storedValue) : value;
      setStoredValue(valueToStore);
      window.localStorage.setItem(key, JSON.stringify(valueToStore));
    } catch (error) {
      console.error(`Error setting localStorage key "${key}":`, error);
    }
  }, [key, storedValue]);

  const removeValue = useCallback(() => {
    try {
      window.localStorage.removeItem(key);
      setStoredValue(initialValue);
    } catch (error) {
      console.error(`Error removing localStorage key "${key}":`, error);
    }
  }, [key, initialValue]);

  return [storedValue, setValue, removeValue];
}

// =============================================================================
// usePagination - التقسيم إلى صفحات
// =============================================================================
/**
 * إدارة التقسيم إلى صفحات
 * @param {Object} options - خيارات التقسيم
 */
export function usePagination({ totalItems = 0, itemsPerPage = 10, initialPage = 1 }) {
  const [currentPage, setCurrentPage] = useState(initialPage);

  const totalPages = useMemo(() =>
    Math.ceil(totalItems / itemsPerPage),
    [totalItems, itemsPerPage]
  );

  const startIndex = useMemo(() =>
    (currentPage - 1) * itemsPerPage,
    [currentPage, itemsPerPage]
  );

  const endIndex = useMemo(() =>
    Math.min(startIndex + itemsPerPage, totalItems),
    [startIndex, itemsPerPage, totalItems]
  );

  const goToPage = useCallback((page) => {
    const pageNumber = Math.max(1, Math.min(page, totalPages));
    setCurrentPage(pageNumber);
  }, [totalPages]);

  const nextPage = useCallback(() => {
    goToPage(currentPage + 1);
  }, [currentPage, goToPage]);

  const prevPage = useCallback(() => {
    goToPage(currentPage - 1);
  }, [currentPage, goToPage]);

  const firstPage = useCallback(() => {
    goToPage(1);
  }, [goToPage]);

  const lastPage = useCallback(() => {
    goToPage(totalPages);
  }, [goToPage, totalPages]);

  // إعادة تعيين الصفحة عند تغيير إجمالي العناصر
  useEffect(() => {
    if (currentPage > totalPages && totalPages > 0) {
      setCurrentPage(totalPages);
    }
  }, [currentPage, totalPages]);

  return {
    currentPage,
    totalPages,
    startIndex,
    endIndex,
    goToPage,
    nextPage,
    prevPage,
    firstPage,
    lastPage,
    hasNextPage: currentPage < totalPages,
    hasPrevPage: currentPage > 1,
  };
}

// =============================================================================
// useForm - إدارة النماذج
// =============================================================================
/**
 * إدارة حالة النموذج والتحقق
 * @param {Object} initialValues - القيم الأولية
 * @param {Function} validate - دالة التحقق
 * @param {Function} onSubmit - دالة الإرسال
 */
export function useForm(initialValues = {}, validate = null, onSubmit = null) {
  const [values, setValues] = useState(initialValues);
  const [errors, setErrors] = useState({});
  const [touched, setTouched] = useState({});
  const [isSubmitting, setIsSubmitting] = useState(false);

  const handleChange = useCallback((e) => {
    const { name, value, type, checked } = e.target;
    const fieldValue = type === 'checkbox' ? checked : value;

    setValues(prev => ({ ...prev, [name]: fieldValue }));

    // مسح الخطأ عند التعديل
    if (errors[name]) {
      setErrors(prev => ({ ...prev, [name]: null }));
    }
  }, [errors]);

  const handleBlur = useCallback((e) => {
    const { name } = e.target;
    setTouched(prev => ({ ...prev, [name]: true }));

    // التحقق من الحقل عند المغادرة
    if (validate) {
      const fieldErrors = validate({ [name]: values[name] });
      if (fieldErrors[name]) {
        setErrors(prev => ({ ...prev, [name]: fieldErrors[name] }));
      }
    }
  }, [values, validate]);

  const setFieldValue = useCallback((name, value) => {
    setValues(prev => ({ ...prev, [name]: value }));
    if (errors[name]) {
      setErrors(prev => ({ ...prev, [name]: null }));
    }
  }, [errors]);

  const setFieldError = useCallback((name, error) => {
    setErrors(prev => ({ ...prev, [name]: error }));
  }, []);

  const resetForm = useCallback((newValues = initialValues) => {
    setValues(newValues);
    setErrors({});
    setTouched({});
    setIsSubmitting(false);
  }, [initialValues]);

  const handleSubmit = useCallback(async (e) => {
    e?.preventDefault();

    // التحقق من جميع الحقول
    if (validate) {
      const validationErrors = validate(values);
      if (Object.keys(validationErrors).length > 0) {
        setErrors(validationErrors);
        // تحديد جميع الحقول كـ touched
        const allTouched = Object.keys(values).reduce((acc, key) => {
          acc[key] = true;
          return acc;
        }, {});
        setTouched(allTouched);
        return false;
      }
    }

    if (onSubmit) {
      setIsSubmitting(true);
      try {
        await onSubmit(values);
        return true;
      } catch (error) {
        console.error('Form submission error:', error);
        return false;
      } finally {
        setIsSubmitting(false);
      }
    }

    return true;
  }, [values, validate, onSubmit]);

  const isValid = useMemo(() =>
    Object.keys(errors).length === 0,
    [errors]
  );

  return {
    values,
    errors,
    touched,
    isSubmitting,
    isValid,
    handleChange,
    handleBlur,
    handleSubmit,
    setFieldValue,
    setFieldError,
    resetForm,
    setValues,
    setErrors,
  };
}

// =============================================================================
// useTableFilters - فلاتر الجدول
// =============================================================================
/**
 * إدارة فلاتر الجدول
 * @param {Object} initialFilters - الفلاتر الأولية
 */
export function useTableFilters(initialFilters = {}) {
  const [filters, setFilters] = useState(initialFilters);

  const updateFilter = useCallback((key, value) => {
    setFilters(prev => ({ ...prev, [key]: value }));
  }, []);

  const resetFilters = useCallback(() => {
    setFilters(initialFilters);
  }, [initialFilters]);

  const clearFilter = useCallback((key) => {
    setFilters(prev => ({ ...prev, [key]: initialFilters[key] ?? '' }));
  }, [initialFilters]);

  const hasActiveFilters = useMemo(() => {
    return Object.entries(filters).some(([key, value]) => {
      const initialValue = initialFilters[key];
      return value !== initialValue && value !== '' && value !== null && value !== undefined;
    });
  }, [filters, initialFilters]);

  return {
    filters,
    setFilters,
    updateFilter,
    resetFilters,
    clearFilter,
    hasActiveFilters,
  };
}

// =============================================================================
// useClickOutside - النقر خارج العنصر
// =============================================================================
/**
 * استدعاء دالة عند النقر خارج العنصر
 * @param {Function} handler - الدالة المراد استدعاؤها
 */
export function useClickOutside(handler) {
  const ref = useRef(null);

  useEffect(() => {
    const listener = (event) => {
      if (!ref.current || ref.current.contains(event.target)) {
        return;
      }
      handler(event);
    };

    document.addEventListener('mousedown', listener);
    document.addEventListener('touchstart', listener);

    return () => {
      document.removeEventListener('mousedown', listener);
      document.removeEventListener('touchstart', listener);
    };
  }, [handler]);

  return ref;
}

// =============================================================================
// useAsync - العمليات غير المتزامنة
// =============================================================================
/**
 * إدارة العمليات غير المتزامنة
 * @param {Function} asyncFunction - الدالة غير المتزامنة
 * @param {boolean} immediate - تنفيذ فوري
 */
export function useAsync(asyncFunction, immediate = false) {
  const [status, setStatus] = useState('idle'); // idle, pending, success, error
  const [data, setData] = useState(null);
  const [error, setError] = useState(null);

  const execute = useCallback(async (...params) => {
    setStatus('pending');
    setData(null);
    setError(null);

    try {
      const response = await asyncFunction(...params);
      setData(response);
      setStatus('success');
      return response;
    } catch (err) {
      setError(err);
      setStatus('error');
      throw err;
    }
  }, [asyncFunction]);

  useEffect(() => {
    if (immediate) {
      execute();
    }
  }, [execute, immediate]);

  return {
    execute,
    status,
    data,
    error,
    isIdle: status === 'idle',
    isPending: status === 'pending',
    isSuccess: status === 'success',
    isError: status === 'error',
  };
}

// =============================================================================
// usePrevious - القيمة السابقة
// =============================================================================
/**
 * الحصول على القيمة السابقة
 * @param {any} value - القيمة الحالية
 */
export function usePrevious(value) {
  const ref = useRef();

  useEffect(() => {
    ref.current = value;
  }, [value]);

  return ref.current;
}

// =============================================================================
// useMediaQuery - استعلامات الوسائط
// =============================================================================
/**
 * التحقق من استعلام الوسائط
 * @param {string} query - استعلام CSS
 */
export function useMediaQuery(query) {
  const [matches, setMatches] = useState(() => {
    if (typeof window !== 'undefined') {
      return window.matchMedia(query).matches;
    }
    return false;
  });

  useEffect(() => {
    const mediaQuery = window.matchMedia(query);
    const handler = (event) => setMatches(event.matches);

    mediaQuery.addEventListener('change', handler);
    return () => mediaQuery.removeEventListener('change', handler);
  }, [query]);

  return matches;
}

// =============================================================================
// Predefined Media Queries
// =============================================================================
export function useIsMobile() {
  return useMediaQuery('(max-width: 768px)');
}

export function useIsTablet() {
  return useMediaQuery('(min-width: 769px) and (max-width: 1024px)');
}

export function useIsDesktop() {
  return useMediaQuery('(min-width: 1025px)');
}
