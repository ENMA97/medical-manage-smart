import Modal from './Modal';

export default function ConfirmDialog({ open, onClose, onConfirm, title = 'تأكيد', message, confirmLabel = 'تأكيد', confirmColor = 'red', loading = false }) {
  const colors = {
    red: 'bg-red-600 hover:bg-red-700',
    blue: 'bg-blue-600 hover:bg-blue-700',
    green: 'bg-green-600 hover:bg-green-700',
  };

  return (
    <Modal open={open} onClose={onClose} title={title} maxWidth="max-w-sm">
      <p className="text-sm text-gray-600 mb-4">{message}</p>
      <div className="flex gap-2 justify-end">
        <button onClick={onClose} disabled={loading} className="px-4 py-2 text-sm text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50">
          إلغاء
        </button>
        <button onClick={onConfirm} disabled={loading} className={`px-4 py-2 text-sm text-white rounded-lg disabled:opacity-50 ${colors[confirmColor] || colors.red}`}>
          {loading ? 'جاري...' : confirmLabel}
        </button>
      </div>
    </Modal>
  );
}
