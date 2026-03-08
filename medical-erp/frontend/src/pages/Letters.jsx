import { useState, useEffect, useCallback } from 'react';
import toast from 'react-hot-toast';
import letterService from '../services/letterService';

const statusLabels = { pending: 'معلّق', approved: 'معتمد', rejected: 'مرفوض' };
const statusColors = {
  pending: 'bg-yellow-100 text-yellow-700',
  approved: 'bg-green-100 text-green-700',
  rejected: 'bg-red-100 text-red-700',
};

export default function Letters() {
  const [letters, setLetters] = useState([]);
  const [loading, setLoading] = useState(true);

  const fetchLetters = useCallback(async () => {
    setLoading(true);
    try {
      const { data } = await letterService.getAll();
      setLetters(data.data?.data || data.data || []);
    } catch {
      toast.error('حدث خطأ في تحميل الخطابات');
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => { fetchLetters(); }, [fetchLetters]);

  async function handleApprove(id) {
    try {
      await letterService.approve(id);
      toast.success('تم اعتماد الخطاب');
      fetchLetters();
    } catch (err) {
      toast.error(err.response?.data?.message || 'حدث خطأ');
    }
  }

  return (
    <div className="space-y-4">
      <h1 className="text-xl font-bold text-gray-800">إدارة الخطابات</h1>

      <div className="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        {loading ? (
          <div className="flex items-center justify-center py-16"><div className="w-8 h-8 border-4 border-blue-200 border-t-blue-600 rounded-full animate-spin" /></div>
        ) : letters.length === 0 ? (
          <div className="text-center py-16 text-gray-500">لا يوجد خطابات مسجلة</div>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-sm">
              <thead className="bg-gray-50 border-b border-gray-100">
                <tr>
                  <th className="text-right px-4 py-3 font-medium text-gray-600">رقم الخطاب</th>
                  <th className="text-right px-4 py-3 font-medium text-gray-600">الموظف</th>
                  <th className="text-right px-4 py-3 font-medium text-gray-600 hidden sm:table-cell">النوع</th>
                  <th className="text-right px-4 py-3 font-medium text-gray-600 hidden md:table-cell">القالب</th>
                  <th className="text-right px-4 py-3 font-medium text-gray-600">الحالة</th>
                  <th className="px-4 py-3"></th>
                </tr>
              </thead>
              <tbody className="divide-y divide-gray-50">
                {letters.map((letter) => (
                  <tr key={letter.id} className="hover:bg-gray-50">
                    <td className="px-4 py-3 font-mono text-gray-700">{letter.letter_number}</td>
                    <td className="px-4 py-3 font-medium text-gray-800">
                      {letter.employee?.full_name_ar || letter.employee?.full_name_en || '—'}
                    </td>
                    <td className="px-4 py-3 text-gray-600 hidden sm:table-cell">{letter.letter_type || '—'}</td>
                    <td className="px-4 py-3 text-gray-600 hidden md:table-cell">{letter.template?.name_ar || '—'}</td>
                    <td className="px-4 py-3">
                      <span className={`inline-block px-2 py-0.5 rounded-full text-xs font-medium ${statusColors[letter.status] || 'bg-gray-100 text-gray-600'}`}>
                        {statusLabels[letter.status] || letter.status}
                      </span>
                    </td>
                    <td className="px-4 py-3">
                      {letter.status === 'pending' && (
                        <button onClick={() => handleApprove(letter.id)} className="text-sm text-green-600 hover:text-green-800 font-medium">اعتماد</button>
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
