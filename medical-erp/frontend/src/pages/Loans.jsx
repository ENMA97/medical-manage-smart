import { useState, useEffect, useCallback } from 'react';
import toast from 'react-hot-toast';
import loanService from '../services/loanService';

const statusLabels = { pending: 'معلّقة', approved: 'معتمدة', rejected: 'مرفوضة', paid: 'مسددة' };
const statusColors = {
  pending: 'bg-yellow-100 text-yellow-700',
  approved: 'bg-green-100 text-green-700',
  rejected: 'bg-red-100 text-red-700',
  paid: 'bg-blue-100 text-blue-700',
};

export default function Loans() {
  const [loans, setLoans] = useState([]);
  const [loading, setLoading] = useState(true);

  const fetchLoans = useCallback(async () => {
    setLoading(true);
    try {
      const { data } = await loanService.getAll();
      setLoans(data.data?.data || data.data || []);
    } catch {
      toast.error('حدث خطأ في تحميل السلف');
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => { fetchLoans(); }, [fetchLoans]);

  async function handleApprove(id) {
    try {
      await loanService.approve(id);
      toast.success('تم اعتماد السلفة');
      fetchLoans();
    } catch (err) {
      toast.error(err.response?.data?.message || 'حدث خطأ');
    }
  }

  async function handleReject(id) {
    try {
      await loanService.reject(id);
      toast.success('تم رفض السلفة');
      fetchLoans();
    } catch (err) {
      toast.error(err.response?.data?.message || 'حدث خطأ');
    }
  }

  return (
    <div className="space-y-4">
      <h1 className="text-xl font-bold text-gray-800">إدارة السلف</h1>

      <div className="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        {loading ? (
          <div className="flex items-center justify-center py-16"><div className="w-8 h-8 border-4 border-blue-200 border-t-blue-600 rounded-full animate-spin" /></div>
        ) : loans.length === 0 ? (
          <div className="text-center py-16 text-gray-500">لا يوجد سلف مسجلة</div>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-sm">
              <thead className="bg-gray-50 border-b border-gray-100">
                <tr>
                  <th className="text-right px-4 py-3 font-medium text-gray-600">رقم السلفة</th>
                  <th className="text-right px-4 py-3 font-medium text-gray-600">الموظف</th>
                  <th className="text-right px-4 py-3 font-medium text-gray-600 hidden sm:table-cell">المبلغ</th>
                  <th className="text-right px-4 py-3 font-medium text-gray-600 hidden md:table-cell">القسط الشهري</th>
                  <th className="text-right px-4 py-3 font-medium text-gray-600">الحالة</th>
                  <th className="px-4 py-3"></th>
                </tr>
              </thead>
              <tbody className="divide-y divide-gray-50">
                {loans.map((loan) => (
                  <tr key={loan.id} className="hover:bg-gray-50">
                    <td className="px-4 py-3 font-mono text-gray-700">{loan.loan_number}</td>
                    <td className="px-4 py-3 font-medium text-gray-800">
                      {loan.employee?.full_name_ar || loan.employee?.full_name_en || '—'}
                    </td>
                    <td className="px-4 py-3 text-gray-600 hidden sm:table-cell">
                      {Number(loan.loan_amount).toLocaleString('ar-SA')} ر.س
                    </td>
                    <td className="px-4 py-3 text-gray-600 hidden md:table-cell">
                      {Number(loan.monthly_deduction).toLocaleString('ar-SA')} ر.س
                    </td>
                    <td className="px-4 py-3">
                      <span className={`inline-block px-2 py-0.5 rounded-full text-xs font-medium ${statusColors[loan.status] || 'bg-gray-100 text-gray-600'}`}>
                        {statusLabels[loan.status] || loan.status}
                      </span>
                    </td>
                    <td className="px-4 py-3">
                      {loan.status === 'pending' && (
                        <div className="flex gap-2">
                          <button onClick={() => handleApprove(loan.id)} className="text-sm text-green-600 hover:text-green-800 font-medium">اعتماد</button>
                          <button onClick={() => handleReject(loan.id)} className="text-sm text-red-600 hover:text-red-800 font-medium">رفض</button>
                        </div>
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
