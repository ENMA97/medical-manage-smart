import { useState, useEffect, useCallback } from 'react';
import toast from 'react-hot-toast';
import loanService from '../services/loanService';
import employeeService from '../services/employeeService';
import Modal from '../components/ui/Modal';

const statusLabels = { pending: 'معلّقة', approved: 'معتمدة', rejected: 'مرفوضة', paid: 'مسددة' };
const statusColors = {
  pending: 'bg-yellow-100 text-yellow-700',
  approved: 'bg-green-100 text-green-700',
  rejected: 'bg-red-100 text-red-700',
  paid: 'bg-teal-100 text-teal-700',
};

const emptyForm = { employee_id: '', loan_amount: '', monthly_deduction: '', start_date: '', reason: '' };

export default function Loans() {
  const [loans, setLoans] = useState([]);
  const [loading, setLoading] = useState(true);
  const [showCreate, setShowCreate] = useState(false);
  const [form, setForm] = useState(emptyForm);
  const [saving, setSaving] = useState(false);
  const [employees, setEmployees] = useState([]);

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

  async function loadEmployees() {
    if (employees.length > 0) return;
    try {
      const { data } = await employeeService.getAll({ per_page: 200 });
      setEmployees(data.data?.data || data.data || []);
    } catch { /* silent */ }
  }

  function openCreate() {
    loadEmployees();
    setForm(emptyForm);
    setShowCreate(true);
  }

  async function handleCreate(e) {
    e.preventDefault();
    setSaving(true);
    try {
      await loanService.create({
        employee_id: form.employee_id,
        loan_amount: Number(form.loan_amount),
        monthly_deduction: Number(form.monthly_deduction),
        start_date: form.start_date,
        reason: form.reason || undefined,
      });
      toast.success('تم إنشاء السلفة بنجاح');
      setShowCreate(false);
      fetchLoans();
    } catch (err) {
      toast.error(err.response?.data?.message || 'حدث خطأ');
    } finally {
      setSaving(false);
    }
  }

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

  function set(key, value) { setForm((f) => ({ ...f, [key]: value })); }

  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between">
        <h1 className="text-xl font-bold text-gray-800">إدارة السلف</h1>
        <button onClick={openCreate} className="px-4 py-2 bg-teal-600 text-white text-sm rounded-lg hover:bg-teal-700 transition-colors">
          سلفة جديدة
        </button>
      </div>

      <div className="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        {loading ? (
          <div className="flex items-center justify-center py-16"><div className="w-8 h-8 border-4 border-teal-200 border-t-teal-600 rounded-full animate-spin" /></div>
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
                      {loan.employee?.full_name_ar || loan.employee?.full_name_en || loan.employee?.full_name || '—'}
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

      {/* Create Loan Modal */}
      <Modal open={showCreate} onClose={() => setShowCreate(false)} title="إنشاء سلفة جديدة">
        <form onSubmit={handleCreate} className="space-y-4">
          <div>
            <label className="block text-xs text-gray-600 mb-1">الموظف</label>
            <select
              value={form.employee_id} onChange={(e) => set('employee_id', e.target.value)}
              required
              className="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-teal-500 focus:border-transparent"
            >
              <option value="">اختر الموظف</option>
              {employees.map((emp) => (
                <option key={emp.id} value={emp.id}>
                  {emp.full_name || `${emp.first_name_ar || emp.first_name} ${emp.last_name_ar || emp.last_name}`} — {emp.employee_number}
                </option>
              ))}
            </select>
          </div>
          <div className="grid grid-cols-2 gap-3">
            <div>
              <label className="block text-xs text-gray-600 mb-1">مبلغ السلفة</label>
              <input type="number" min="100" value={form.loan_amount} onChange={(e) => set('loan_amount', e.target.value)}
                required className="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-teal-500 focus:border-transparent" />
            </div>
            <div>
              <label className="block text-xs text-gray-600 mb-1">القسط الشهري</label>
              <input type="number" min="50" value={form.monthly_deduction} onChange={(e) => set('monthly_deduction', e.target.value)}
                required className="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-teal-500 focus:border-transparent" />
            </div>
          </div>
          <div>
            <label className="block text-xs text-gray-600 mb-1">تاريخ البدء</label>
            <input type="date" value={form.start_date} onChange={(e) => set('start_date', e.target.value)}
              required className="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-teal-500 focus:border-transparent" />
          </div>
          <div>
            <label className="block text-xs text-gray-600 mb-1">السبب (اختياري)</label>
            <textarea value={form.reason} onChange={(e) => set('reason', e.target.value)} rows={2}
              className="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-teal-500 focus:border-transparent resize-none" />
          </div>
          {form.loan_amount && form.monthly_deduction && Number(form.monthly_deduction) > 0 && (
            <p className="text-xs text-gray-500">
              عدد الأقساط المتوقع: {Math.ceil(Number(form.loan_amount) / Number(form.monthly_deduction))} قسط
            </p>
          )}
          <div className="flex gap-2 justify-end pt-2">
            <button type="button" onClick={() => setShowCreate(false)} className="px-4 py-2 text-sm text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50">إلغاء</button>
            <button type="submit" disabled={saving} className="px-4 py-2 text-sm text-white bg-teal-600 rounded-lg hover:bg-teal-700 disabled:opacity-50">
              {saving ? 'جاري الحفظ...' : 'إنشاء السلفة'}
            </button>
          </div>
        </form>
      </Modal>
    </div>
  );
}
