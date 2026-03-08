import { useState, useEffect } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import toast from 'react-hot-toast';
import payrollService from '../services/payrollService';

const statusLabels = { draft: 'مسودة', approved: 'معتمد', paid: 'مدفوع' };
const statusColors = { draft: 'bg-yellow-100 text-yellow-700', approved: 'bg-green-100 text-green-700', paid: 'bg-blue-100 text-blue-700' };

export default function PayrollDetail() {
  const { id } = useParams();
  const navigate = useNavigate();
  const [payroll, setPayroll] = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    async function load() {
      try {
        const { data } = await payrollService.getById(id);
        setPayroll(data.data);
      } catch {
        toast.error('حدث خطأ في تحميل بيانات كشف المرتبات');
        navigate('/payroll');
      } finally {
        setLoading(false);
      }
    }
    load();
  }, [id, navigate]);

  if (loading) {
    return (
      <div className="flex items-center justify-center py-20">
        <div className="w-8 h-8 border-4 border-blue-200 border-t-blue-600 rounded-full animate-spin" />
      </div>
    );
  }

  if (!payroll) return null;

  const months = ['يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو', 'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر'];
  const items = payroll.items || [];
  const fmt = (n) => n ? Number(n).toLocaleString('ar-SA', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '0.00';

  async function handleExport() {
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
      <button onClick={() => navigate('/payroll')} className="text-sm text-blue-600 hover:text-blue-800 flex items-center gap-1">
        <svg className="w-4 h-4 rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5l7 7-7 7" />
        </svg>
        العودة لمسيرات الرواتب
      </button>

      {/* Header */}
      <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-4 sm:p-6">
        <div className="flex items-center justify-between flex-wrap gap-3">
          <div>
            <h1 className="text-lg font-bold text-gray-800">
              مسير رواتب {months[(payroll.month || 1) - 1]} {payroll.year}
            </h1>
            <p className="text-sm text-gray-500 mt-1">{payroll.payroll_number}</p>
          </div>
          <div className="flex items-center gap-3">
            <span className={`inline-block px-3 py-1 rounded-full text-xs font-medium ${statusColors[payroll.status] || 'bg-gray-100 text-gray-600'}`}>
              {statusLabels[payroll.status] || payroll.status}
            </span>
            <button onClick={handleExport} className="px-3 py-1.5 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700">
              تصدير Excel
            </button>
          </div>
        </div>
      </div>

      {/* Totals Summary */}
      <div className="grid grid-cols-2 sm:grid-cols-4 gap-3">
        {[
          { label: 'عدد الموظفين', value: payroll.employees_count || items.length },
          { label: 'إجمالي الراتب الأساسي', value: `${fmt(payroll.total_basic_salary)} ر.س` },
          { label: 'إجمالي البدلات', value: `${fmt(payroll.total_allowances)} ر.س` },
          { label: 'صافي الرواتب', value: `${fmt(payroll.total_net_salary)} ر.س` },
        ].map((s) => (
          <div key={s.label} className="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
            <p className="text-xs text-gray-500">{s.label}</p>
            <p className="text-lg font-bold text-gray-800 mt-1">{s.value}</p>
          </div>
        ))}
      </div>

      {/* Items Table */}
      <div className="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div className="px-4 py-3 border-b border-gray-100">
          <h2 className="text-sm font-semibold text-gray-800">تفاصيل الموظفين ({items.length})</h2>
        </div>
        {items.length === 0 ? (
          <div className="text-center py-12 text-gray-400 text-sm">لا توجد بنود في هذا المسير</div>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-sm">
              <thead className="bg-gray-50 border-b border-gray-100">
                <tr>
                  <th className="text-right px-3 py-2.5 font-medium text-gray-600">الموظف</th>
                  <th className="text-right px-3 py-2.5 font-medium text-gray-600 hidden sm:table-cell">الأساسي</th>
                  <th className="text-right px-3 py-2.5 font-medium text-gray-600 hidden md:table-cell">بدل السكن</th>
                  <th className="text-right px-3 py-2.5 font-medium text-gray-600 hidden md:table-cell">بدل النقل</th>
                  <th className="text-right px-3 py-2.5 font-medium text-gray-600 hidden lg:table-cell">GOSI</th>
                  <th className="text-right px-3 py-2.5 font-medium text-gray-600">الإجمالي</th>
                  <th className="text-right px-3 py-2.5 font-medium text-gray-600 hidden sm:table-cell">الخصومات</th>
                  <th className="text-right px-3 py-2.5 font-medium text-gray-600">الصافي</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-gray-50">
                {items.map((item) => (
                  <tr key={item.id} className="hover:bg-gray-50">
                    <td className="px-3 py-2.5 font-medium text-gray-800">
                      {item.employee?.full_name || item.employee?.first_name_ar || '—'}
                    </td>
                    <td className="px-3 py-2.5 text-gray-600 hidden sm:table-cell">{fmt(item.basic_salary)}</td>
                    <td className="px-3 py-2.5 text-gray-600 hidden md:table-cell">{fmt(item.housing_allowance)}</td>
                    <td className="px-3 py-2.5 text-gray-600 hidden md:table-cell">{fmt(item.transport_allowance)}</td>
                    <td className="px-3 py-2.5 text-gray-600 hidden lg:table-cell">{fmt(item.gosi_employee)}</td>
                    <td className="px-3 py-2.5 text-gray-700 font-medium">{fmt(item.gross_salary)}</td>
                    <td className="px-3 py-2.5 text-red-600 hidden sm:table-cell">{fmt(item.total_deductions)}</td>
                    <td className="px-3 py-2.5 text-green-700 font-bold">{fmt(item.net_salary)}</td>
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
