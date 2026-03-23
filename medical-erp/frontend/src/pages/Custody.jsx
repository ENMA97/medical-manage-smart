import { useState, useEffect, useCallback } from 'react';
import toast from 'react-hot-toast';
import custodyService from '../services/custodyService';

const typeLabels = { laptop: 'لابتوب', phone: 'هاتف', car: 'سيارة', key: 'مفتاح', badge: 'بطاقة', other: 'أخرى' };
const statusColors = { assigned: 'bg-teal-100 text-teal-700', returned: 'bg-green-100 text-green-700' };

export default function Custody() {
  const [items, setItems] = useState([]);
  const [loading, setLoading] = useState(true);

  const fetchItems = useCallback(async () => {
    setLoading(true);
    try {
      const { data } = await custodyService.getAll();
      setItems(data.data);
    } catch {
      toast.error('حدث خطأ في تحميل العهد');
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => { fetchItems(); }, [fetchItems]);

  async function handleReturn(id) {
    try {
      await custodyService.returnItem(id);
      toast.success('تم تسجيل إرجاع العهدة');
      fetchItems();
    } catch (err) {
      toast.error(err.response?.data?.message || 'حدث خطأ');
    }
  }

  return (
    <div className="space-y-4">
      <h1 className="text-xl font-bold text-gray-800">إدارة العهد</h1>

      <div className="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        {loading ? (
          <div className="flex items-center justify-center py-16"><div className="w-8 h-8 border-4 border-teal-200 border-t-teal-600 rounded-full animate-spin" /></div>
        ) : items.length === 0 ? (
          <div className="text-center py-16 text-gray-500">لا يوجد عهد مسجلة</div>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-sm">
              <thead className="bg-gray-50 border-b border-gray-100">
                <tr>
                  <th className="text-right px-4 py-3 font-medium text-gray-600">الموظف</th>
                  <th className="text-right px-4 py-3 font-medium text-gray-600">العهدة</th>
                  <th className="text-right px-4 py-3 font-medium text-gray-600 hidden sm:table-cell">النوع</th>
                  <th className="text-right px-4 py-3 font-medium text-gray-600">الحالة</th>
                  <th className="px-4 py-3"></th>
                </tr>
              </thead>
              <tbody className="divide-y divide-gray-50">
                {items.map((item) => (
                  <tr key={item.id} className="hover:bg-gray-50">
                    <td className="px-4 py-3 font-medium text-gray-800">{item.employee?.full_name_ar || item.employee?.full_name_en}</td>
                    <td className="px-4 py-3 text-gray-600">{item.item_name}</td>
                    <td className="px-4 py-3 text-gray-600 hidden sm:table-cell">{typeLabels[item.item_type] || item.item_type}</td>
                    <td className="px-4 py-3">
                      <span className={`inline-block px-2 py-0.5 rounded-full text-xs font-medium ${statusColors[item.status] || 'bg-gray-100 text-gray-600'}`}>
                        {item.status === 'assigned' ? 'مسلّمة' : 'مُرجعة'}
                      </span>
                    </td>
                    <td className="px-4 py-3">
                      {item.status === 'assigned' && (
                        <button onClick={() => handleReturn(item.id)} className="text-sm text-orange-600 hover:text-orange-800 font-medium">إرجاع</button>
                      )}
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </div>
    </div>
  );
}
