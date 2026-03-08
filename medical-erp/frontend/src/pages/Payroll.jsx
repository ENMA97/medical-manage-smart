import { useState, useEffect, useCallback } from 'react';
import { useNavigate } from 'react-router-dom';
import toast from 'react-hot-toast';
import payrollService from '../services/payrollService';

const statusLabels = { draft: 'مسودة', approved: 'معتمد', paid: 'مدفوع' };
const statusColors = { draft: 'bg-yellow-100 text-yellow-700', approved: 'bg-green-100 text-green-700', paid: 'bg-blue-100 text-blue-700' };

export default function Payroll() {
  const navigate = useNavigate();
  const [payrolls, setPayrolls] = useState([]);
  const [loading, setLoading] = useState(true);
  const [page, setPage] = useState(1);
  const [meta, setMeta] = useState({});

  const fetchPayrolls = useCallback(async () => {
    setLoading(true);
    try {
      const { data } = await payrollService.getAll({ page, per_page: 15 });
      setPayrolls(data.data);
      setMeta(data.meta || {});
    } catch {
      toast.error('حدث خطأ في تحميل بيانات الرواتب');
    } finally {
      setLoading(false);
    }
  }, [page]);

  useEffect(() => { fetchPayrolls(); }, [fetchPayrolls]);

  const months = ['يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو', 'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر'];

  async function handleApprove(e, id) {
    e.stopPropagation();
    try {
      await payrollService.approve(id);
      toast.success('تم اعتماد المسير');
      fetchPayrolls();
    } catch (err) {
      toast.error(err.response?.data?.message || 'حدث خطأ');
    }
  }

  async function handleExport(e, id) {
    e.stopPropagation();
    try {
      const { data } = await payrollService.export(id);
      const url = window.URL.createObjectURL(new Blob([data]));
      const a = document.createElement('a');
      a.href = url;
      a.download = `payroll-${id}.xlsx`;
      a.click();
      window.URL.revokeObjectURL(url);
    } catch {
      toast.error('حدث خطأ في التصدير');
    }
  }

  return (
    <div className="space-y-4">
      <h1 className="text-xl font-bold text-gray-800">مسيرات الرواتب</h1>

      <div className="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        {loading ? (
          <div className="flex items-center justify-center py-16"><div className="w-8 h-8 border-4 border-blue-200 border-t-blue-600 rounded-full animate-spin" /></div>
        ) : payrolls.length === 0 ? (
          <div className="text-center py-16 text-gray-500">لا يوجد مسيرات رواتب</div>
        ) : (
          <div className="divide-y divide-gray-50">
            {payrolls.map((p) => (
              <div
                key={p.id}
                onClick={() => navigate(`/payroll/${p.id}`)}
                className="px-4 py-3 hover:bg-gray-50 flex items-center justify-between cursor-pointer"
              >
                <div>
                  <p className="font-medium text-gray-800">{months[(p.month || 1) - 1]} {p.year}</p>
                  <p className="text-xs text-gray-500 mt-0.5">
                    {p.total_amount ? `${Number(p.total_amount).toLocaleString()} ريال` : p.total_net_salary ? `${Number(p.total_net_salary).toLocaleString()} ريال` : '—'} — {p.employees_count || p.items_count || 0} موظف
                  </p>
                </div>
                <div className="flex items-center gap-2">
                  <span className={`inline-block px-2 py-0.5 rounded-full text-xs font-medium ${statusColors[p.status] || 'bg-gray-100 text-gray-600'}`}>
                    {statusLabels[p.status] || p.status}
                  </span>
                  {p.status === 'draft' && (
                    <button onClick={(e) => handleApprove(e, p.id)} className="text-xs text-green-600 hover:text-green-800 font-medium">اعتماد</button>
                  )}
                  <button onClick={(e) => handleExport(e, p.id)} className="text-xs text-blue-600 hover:text-blue-800 font-medium">تصدير</button>
                </div>
              </div>
            ))}
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
