const presets = {
  active: { label: 'نشط', className: 'bg-green-100 text-green-700' },
  inactive: { label: 'غير نشط', className: 'bg-gray-100 text-gray-600' },
  suspended: { label: 'موقوف', className: 'bg-yellow-100 text-yellow-700' },
  terminated: { label: 'منتهي', className: 'bg-red-100 text-red-700' },
  pending: { label: 'قيد الانتظار', className: 'bg-yellow-100 text-yellow-700' },
  approved: { label: 'مقبول', className: 'bg-green-100 text-green-700' },
  rejected: { label: 'مرفوض', className: 'bg-red-100 text-red-700' },
  cancelled: { label: 'ملغي', className: 'bg-gray-100 text-gray-600' },
  draft: { label: 'مسودة', className: 'bg-yellow-100 text-yellow-700' },
  paid: { label: 'مدفوع', className: 'bg-teal-100 text-teal-700' },
  expired: { label: 'منتهي', className: 'bg-red-100 text-red-700' },
  renewed: { label: 'مجدد', className: 'bg-teal-100 text-teal-700' },
  assigned: { label: 'مسلّمة', className: 'bg-teal-100 text-teal-700' },
  returned: { label: 'مُرجعة', className: 'bg-green-100 text-green-700' },
};

export default function StatusBadge({ status, label, className }) {
  const preset = presets[status] || {};
  const displayLabel = label || preset.label || status;
  const displayClass = className || preset.className || 'bg-gray-100 text-gray-600';

  return (
    <span className={`inline-block px-2 py-0.5 rounded-full text-xs font-medium ${displayClass}`}>
      {displayLabel}
    </span>
  );
}
