export default function Pagination({ page, lastPage, from, to, total, onChange }) {
  if (!lastPage || lastPage <= 1) return null;

  return (
    <div className="flex items-center justify-between px-4 py-3 border-t border-gray-100">
      <p className="text-xs text-gray-500">
        عرض {from}–{to} من {total}
      </p>
      <div className="flex gap-1">
        <button
          disabled={page <= 1}
          onClick={() => onChange(page - 1)}
          className="px-3 py-1 text-sm rounded-lg border border-gray-200 disabled:opacity-50 hover:bg-gray-50 transition-colors"
        >
          السابق
        </button>
        <span className="px-3 py-1 text-sm text-gray-500">
          {page} / {lastPage}
        </span>
        <button
          disabled={page >= lastPage}
          onClick={() => onChange(page + 1)}
          className="px-3 py-1 text-sm rounded-lg border border-gray-200 disabled:opacity-50 hover:bg-gray-50 transition-colors"
        >
          التالي
        </button>
      </div>
    </div>
  );
}
