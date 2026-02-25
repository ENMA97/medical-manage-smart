import React from 'react';
import clsx from 'clsx';
import { useLocale } from '../../contexts/LocaleContext';

/**
 * مكون الشارة الأساسي
 * Base Badge Component
 */
export default function Badge({
  children,
  variant = 'default',
  size = 'md',
  dot = false,
  icon = null,
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
    indigo: 'bg-indigo-100 text-indigo-800',
    pink: 'bg-pink-100 text-pink-800',
    orange: 'bg-orange-100 text-orange-800',
    teal: 'bg-teal-100 text-teal-800',
    cyan: 'bg-cyan-100 text-cyan-800',
  };

  const dotClasses = {
    default: 'bg-gray-500',
    primary: 'bg-primary-500',
    success: 'bg-green-500',
    warning: 'bg-yellow-500',
    danger: 'bg-red-500',
    info: 'bg-blue-500',
    purple: 'bg-purple-500',
    indigo: 'bg-indigo-500',
    pink: 'bg-pink-500',
    orange: 'bg-orange-500',
    teal: 'bg-teal-500',
    cyan: 'bg-cyan-500',
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
            dotClasses[variant] || dotClasses.default
          )}
          aria-hidden="true"
        />
      )}
      {icon && (
        <span className="me-1" aria-hidden="true">
          {icon}
        </span>
      )}
      {children}
    </span>
  );
}

/**
 * خريطة الحالات العامة مع الألوان
 * Comprehensive status mapping with colors and labels
 */
const STATUS_CONFIG = {
  // General statuses
  draft: { label_ar: 'مسودة', label_en: 'Draft', variant: 'default' },
  pending: { label_ar: 'قيد الانتظار', label_en: 'Pending', variant: 'warning' },
  approved: { label_ar: 'معتمد', label_en: 'Approved', variant: 'success' },
  rejected: { label_ar: 'مرفوض', label_en: 'Rejected', variant: 'danger' },
  cancelled: { label_ar: 'ملغي', label_en: 'Cancelled', variant: 'default' },
  completed: { label_ar: 'مكتمل', label_en: 'Completed', variant: 'success' },
  active: { label_ar: 'نشط', label_en: 'Active', variant: 'success' },
  inactive: { label_ar: 'غير نشط', label_en: 'Inactive', variant: 'default' },
  paid: { label_ar: 'مدفوع', label_en: 'Paid', variant: 'success' },

  // Leave request statuses
  pending_supervisor: { label_ar: 'بانتظار المشرف', label_en: 'Pending Supervisor', variant: 'warning' },
  pending_admin_manager: { label_ar: 'بانتظار المدير الإداري', label_en: 'Pending Admin Manager', variant: 'orange' },
  pending_hr: { label_ar: 'بانتظار الموارد البشرية', label_en: 'Pending HR', variant: 'info' },
  pending_delegate: { label_ar: 'بانتظار القائم بالعمل', label_en: 'Pending Delegate', variant: 'indigo' },
  pending_medical_director: { label_ar: 'بانتظار المدير الطبي', label_en: 'Pending Medical Director', variant: 'teal' },
  pending_general_manager: { label_ar: 'بانتظار المدير العام', label_en: 'Pending General Manager', variant: 'purple' },
  form_completed: { label_ar: 'اكتمل النموذج', label_en: 'Form Completed', variant: 'teal' },

  // Clearance statuses
  finance_approved: { label_ar: 'موافقة المالية', label_en: 'Finance Approved', variant: 'info' },
  hr_approved: { label_ar: 'موافقة الموارد البشرية', label_en: 'HR Approved', variant: 'indigo' },
  it_approved: { label_ar: 'موافقة تقنية المعلومات', label_en: 'IT Approved', variant: 'purple' },
  custody_cleared: { label_ar: 'تسليم العهد', label_en: 'Custody Cleared', variant: 'orange' },

  // Employee statuses
  suspended: { label_ar: 'موقوف', label_en: 'Suspended', variant: 'danger' },
  on_leave: { label_ar: 'في إجازة', label_en: 'On Leave', variant: 'warning' },
  terminated: { label_ar: 'منتهي', label_en: 'Terminated', variant: 'danger' },

  // Custody statuses
  assigned: { label_ar: 'مُسلَّمة', label_en: 'Assigned', variant: 'info' },
  returned: { label_ar: 'مُستردة', label_en: 'Returned', variant: 'success' },
  lost: { label_ar: 'مفقودة', label_en: 'Lost', variant: 'danger' },
  damaged: { label_ar: 'تالفة', label_en: 'Damaged', variant: 'orange' },

  // Inventory statuses
  in_stock: { label_ar: 'متوفر', label_en: 'In Stock', variant: 'success' },
  low_stock: { label_ar: 'مخزون منخفض', label_en: 'Low Stock', variant: 'warning' },
  out_of_stock: { label_ar: 'نفذ', label_en: 'Out of Stock', variant: 'danger' },
  expired: { label_ar: 'منتهي الصلاحية', label_en: 'Expired', variant: 'danger' },
  expiring_soon: { label_ar: 'قارب الانتهاء', label_en: 'Expiring Soon', variant: 'orange' },

  // Purchase request statuses
  manager_approved: { label_ar: 'موافقة المدير', label_en: 'Manager Approved', variant: 'info' },
  ceo_approved: { label_ar: 'موافقة المدير العام', label_en: 'CEO Approved', variant: 'purple' },

  // Insurance claim statuses
  submitted: { label_ar: 'مقدمة', label_en: 'Submitted', variant: 'info' },
  scrubbed: { label_ar: 'مراجعة', label_en: 'Scrubbed', variant: 'warning' },

  // Loan statuses
  overdue: { label_ar: 'متأخر', label_en: 'Overdue', variant: 'danger' },

  // Integration statuses
  connected: { label_ar: 'متصل', label_en: 'Connected', variant: 'success' },
  disconnected: { label_ar: 'غير متصل', label_en: 'Disconnected', variant: 'danger' },
  partial: { label_ar: 'متصل جزئياً', label_en: 'Partially Connected', variant: 'warning' },
};

/**
 * مكون شارة الحالة الموحد
 * Unified Status Badge Component
 */
export function StatusBadge({
  status,
  type = null,
  labels = {},
  size = 'md',
  showDot = true,
  className = '',
}) {
  const { locale } = useLocale();

  // Get status configuration
  const statusConfig = STATUS_CONFIG[status];

  // Determine label
  let label;
  if (labels[status]) {
    label = labels[status];
  } else if (statusConfig) {
    label = locale === 'ar' ? statusConfig.label_ar : statusConfig.label_en;
  } else {
    label = status;
  }

  // Determine variant
  const variant = statusConfig?.variant || 'default';

  return (
    <Badge
      variant={variant}
      size={size}
      dot={showDot}
      className={className}
    >
      {label}
    </Badge>
  );
}

/**
 * مكون شارة نوع العقد
 * Contract Type Badge Component
 */
export function ContractTypeBadge({ type, size = 'md' }) {
  const { locale } = useLocale();

  const typeConfig = {
    full_time: { label_ar: 'دوام كامل', label_en: 'Full Time', variant: 'success' },
    part_time: { label_ar: 'دوام جزئي', label_en: 'Part Time', variant: 'info' },
    tamheer: { label_ar: 'تمهير', label_en: 'Tamheer', variant: 'purple' },
    percentage: { label_ar: 'نسبة', label_en: 'Percentage', variant: 'warning' },
    locum: { label_ar: 'مناوب', label_en: 'Locum', variant: 'orange' },
  };

  const config = typeConfig[type];
  if (!config) return null;

  return (
    <Badge variant={config.variant} size={size}>
      {locale === 'ar' ? config.label_ar : config.label_en}
    </Badge>
  );
}

/**
 * مكون شارة الأولوية
 * Priority Badge Component
 */
export function PriorityBadge({ priority, size = 'md' }) {
  const { locale } = useLocale();

  const priorityConfig = {
    low: { label_ar: 'منخفضة', label_en: 'Low', variant: 'default' },
    medium: { label_ar: 'متوسطة', label_en: 'Medium', variant: 'warning' },
    high: { label_ar: 'عالية', label_en: 'High', variant: 'orange' },
    urgent: { label_ar: 'عاجلة', label_en: 'Urgent', variant: 'danger' },
  };

  const config = priorityConfig[priority];
  if (!config) return null;

  return (
    <Badge variant={config.variant} size={size} dot>
      {locale === 'ar' ? config.label_ar : config.label_en}
    </Badge>
  );
}

/**
 * مكون شارة نوع المستودع
 * Warehouse Type Badge Component
 */
export function WarehouseTypeBadge({ type, size = 'md' }) {
  const { locale } = useLocale();

  const typeConfig = {
    main: { label_ar: 'رئيسي', label_en: 'Main', variant: 'primary' },
    sub: { label_ar: 'فرعي', label_en: 'Sub', variant: 'default' },
    pharmacy: { label_ar: 'صيدلية', label_en: 'Pharmacy', variant: 'success' },
    lab: { label_ar: 'مختبر', label_en: 'Laboratory', variant: 'purple' },
    emergency: { label_ar: 'طوارئ', label_en: 'Emergency', variant: 'danger' },
    crash_cart: { label_ar: 'عربة طوارئ', label_en: 'Crash Cart', variant: 'orange' },
    cold_storage: { label_ar: 'تخزين بارد', label_en: 'Cold Storage', variant: 'cyan' },
  };

  const config = typeConfig[type];
  if (!config) return null;

  return (
    <Badge variant={config.variant} size={size}>
      {locale === 'ar' ? config.label_ar : config.label_en}
    </Badge>
  );
}

/**
 * مكون شارة الدور
 * Role Badge Component
 */
export function RoleBadge({ role, size = 'md' }) {
  const { locale } = useLocale();

  const roleConfig = {
    admin: { label_ar: 'مدير النظام', label_en: 'Admin', variant: 'danger' },
    hr_manager: { label_ar: 'مدير الموارد البشرية', label_en: 'HR Manager', variant: 'info' },
    finance_manager: { label_ar: 'مدير المالية', label_en: 'Finance Manager', variant: 'success' },
    doctor: { label_ar: 'طبيب', label_en: 'Doctor', variant: 'teal' },
    nurse: { label_ar: 'ممرض', label_en: 'Nurse', variant: 'pink' },
    pharmacist: { label_ar: 'صيدلي', label_en: 'Pharmacist', variant: 'purple' },
    lab_technician: { label_ar: 'فني مختبر', label_en: 'Lab Technician', variant: 'indigo' },
    receptionist: { label_ar: 'موظف استقبال', label_en: 'Receptionist', variant: 'orange' },
  };

  const config = roleConfig[role];
  if (!config) {
    return <Badge size={size}>{role}</Badge>;
  }

  return (
    <Badge variant={config.variant} size={size}>
      {locale === 'ar' ? config.label_ar : config.label_en}
    </Badge>
  );
}
