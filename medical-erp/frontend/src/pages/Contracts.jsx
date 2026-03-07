import { useState, useEffect, useCallback } from 'react';
import toast from 'react-hot-toast';
import contractService from '../services/contractService';

const typeLabels = { permanent: 'دائم', temporary: 'مؤقت', part_time: 'دوام جزئي', probation: 'تجريبي' };
const statusLabels = { active: 'ساري', expired: 'منتهي', terminated: 'ملغي', renewed: 'مجدد' };
const statusColors = { active: 'bg-green-100 text-green-700', expired: 'bg-red-100 text-red-700', terminated: 'bg-gray-100 text-gray-600', renewed: 'bg-blue-100 text-blue-700' };

export default function Contracts() {
  const [contracts, setContracts] = useState([]);
  const [loading, setLoading] = useState(true);
  const [search, setSearch] = useState('');
  const [page, setPage] = useState(1);
  const [meta, setMeta] = useState({});

  const fetchContracts = useCallback(async () => {
    setLoading(true);
    try {
      const { data } = await contractService.getAll({ search, page, per_page: 15 });
      setContracts(data.data);
      setMeta(data.meta || {});
    } catch {
      toast.error('حدث خطأ في تحميل العقود');
    } finally {
      setLoading(false);
    }
  }, [search, page]);

  useEffect(() => { fetchContracts(); }, [fetchContracts]);
  useEffect(() => { setPage(1); }, [search]);

  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between">
        <h1 className="text-xl font-bold text-gray-800">العقود</h1>
      </div>

      <div className="relative">
        <input
          type="text" placeholder="البحث..." value={search} onChange={(e) => setSearch(e.target.value)}
          className="w-full sm:w-80 pl-4 pr-10 py-2.5 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
        />
      </div>

      <div className="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        {loading ? (
          <div className="flex items-center justify-center py-16">
            <div className="w-8 h-8 border-4 border-blue-200 border-t-blue-600 rounded-full animate-spin" />
          </div>
        ) : contracts.length === 0 ? (
          <div className="text-center py-16 text-gray-500">لا يوجد عقود</div>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-sm">
              <thead className="bg-gray-50 border-b border-gray-100">
                <tr>
                  <th className="text-right px-4 py-3 font-medium text-gray-600">الموظف</th>
                  <th className="text-right px-4 py-3 font-medium text-gray-600 hidden sm:table-cell">النوع</th>
                  <th className="text-right px-4 py-3 font-medium text-gray-600">من</th>
                  <th className="text-right px-4 py-3 font-medium text-gray-600">إلى</th>
                  <th className="text-right px-4 py-3 font-medium text-gray-600">الحالة</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-gray-50">
                {contracts.map((c) => (
                  <tr key={c.id} className="hover:bg-gray-50">
                    <td className="px-4 py-3 font-medium text-gray-800">{c.employee?.full_name}</td>
                    <td className="px-4 py-3 text-gray-600 hidden sm:table-cell">{typeLabels[c.type] || c.type}</td>
                    <td className="px-4 py-3 text-gray-600">{c.start_date}</td>
                    <td className="px-4 py-3 text-gray-600">{c.end_date || '—'}</td>
                    <td className="px-4 py-3">
                      <span className={`inline-block px-2 py-0.5 rounded-full text-xs font-medium ${statusColors[c.status] || 'bg-gray-100 text-gray-600'}`}>
                        {statusLabels[c.status] || c.status}
                      </span>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}

        {meta.last_page > 1 && (
          <div className="flex items-center justify-between px-4 py-3 border-t border-gray-100">
            <p className="text-xs text-gray-500">عرض {meta.from}–{meta.to} من {meta.total}</p>
            <div className="flex gap-1">
              <button disabled={page <= 1} onClick={() => setPage(page - 1)} className="px-3 py-1 text-sm rounded-lg border border-gray-200 disabled:opacity-50 hover:bg-gray-50">السابق</button>
              <button disabled={page >= meta.last_page} onClick={() => setPage(page + 1)} className="px-3 py-1 text-sm rounded-lg border border-gray-200 disabled:opacity-50 hover:bg-gray-50">التالي</button>
            </div>
          </div>
        )}
      </div>
    </div>
  );
}
