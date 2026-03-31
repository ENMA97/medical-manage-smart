import { useState, useEffect, useCallback } from 'react';
import toast from 'react-hot-toast';
import { leaveRequestService, leaveTypeService } from '../services/leaveService';
import employeeService from '../services/employeeService';
import { useAuth } from '../contexts/AuthContext';

const statusLabels = {
  submitted: 'قيد الانتظار',
  pending: 'قيد الانتظار',
  pending_substitute: 'بانتظار البديل',
  pending_supervisor: 'بانتظار المشرف',
  pending_hr: 'بانتظار الموارد البشرية',
  pending_admin_manager: 'بانتظار المدير الإداري',
  pending_general_manager: 'بانتظار المدير العام',
  approved: 'مقبول',
  rejected: 'مرفوض',
  cancelled: 'ملغي',
};
const statusColors = {
  submitted: 'bg-yellow-100 text-yellow-700',
  pending: 'bg-yellow-100 text-yellow-700',
  pending_substitute: 'bg-orange-100 text-orange-700',
  pending_supervisor: 'bg-orange-100 text-orange-700',
  pending_hr: 'bg-blue-100 text-blue-700',
  pending_admin_manager: 'bg-purple-100 text-purple-700',
  pending_general_manager: 'bg-purple-100 text-purple-700',
  approved: 'bg-green-100 text-green-700',
  rejected: 'bg-red-100 text-red-700',
  cancelled: 'bg-gray-100 text-gray-600',
};

export default function LeaveRequests() {
  const { user } = useAuth();
  const [requests, setRequests] = useState([]);
  const [loading, setLoading] = useState(true);
  const [page, setPage] = useState(1); // eslint-disable-line no-unused-vars
  const [meta, setMeta] = useState({}); // eslint-disable-line no-unused-vars
  const [showForm, setShowForm] = useState(false);
  const [form, setForm] = useState({ employee_id: '', leave_type_id: '', start_date: '', end_date: '', reason: '' });
  const [saving, setSaving] = useState(false);
  const [leaveTypes, setLeaveTypes] = useState([]);
  const [employees, setEmployees] = useState([]);

  const fetchRequests = useCallback(async () => {
    setLoading(true);
    try {
      const { data } = await leaveRequestService.getAll({ page, per_page: 15 });
      setRequests(data.data?.data || data.data || []);
      setMeta(data.data || {});
    } catch {
      toast.error('حدث خطأ في تحميل طلبات الإجازة');
    } finally {
      setLoading(false);
    }
  }, [page]);

  useEffect(() => { fetchRequests(); }, [fetchRequests]);

  useEffect(() => {
    leaveTypeService.getAll().then(({ data }) => setLeaveTypes(data.data?.data || data.data || [])).catch(() => {});
    employeeService.getAll({ per_page: 200 }).then(({ data }) => setEmployees(data.data?.data || data.data || [])).catch(() => {});
  }, []);

  function calcDays(start, end) {
    if (!start || !end) return 0;
    const diff = new Date(end) - new Date(start);
    return Math.max(1, Math.ceil(diff / (1000 * 60 * 60 * 24)) + 1);
  }

  async function handleSubmit(e) {
    e.preventDefault();
    setSaving(true);
    const employeeId = form.employee_id || user?.employee?.id;
    if (!employeeId) {
      toast.error('يرجى اختيار الموظف');
      setSaving(false);
      return;
    }
    const totalDays = calcDays(form.start_date, form.end_date);
    try {
      await leaveRequestService.create({ ...form, employee_id: employeeId, total_days: totalDays });
      toast.success('تم تقديم طلب الإجازة');
      setShowForm(false);
      setForm({ employee_id: '', leave_type_id: '', start_date: '', end_date: '', reason: '' });
      fetchRequests();
    } catch (err) {
      toast.error(err.response?.data?.message || 'حدث خطأ');
    } finally {
      setSaving(false);
    }
  }

  async function handleAction(id, action) {
    try {
      if (action === 'approve') {
        await leaveRequestService.approve(id);
      } else if (action === 'reject') {
        const comment = prompt('سبب الرفض:');
        if (!comment) return;
        await leaveRequestService.reject(id, { comment });
      } else if (action === 'cancel') {
        const reason = prompt('سبب الإلغاء:');
        if (!reason) return;
        await leaveRequestService.cancel(id, { cancellation_reason: reason });
      }
      toast.success('تم تحديث الطلب');
      fetchRequests();
    } catch (err) {
      toast.error(err.response?.data?.message || 'حدث خطأ');
    }
  }

  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between">
        <h1 className="text-xl font-bold text-gray-800">طلبات الإجازة</h1>
        <button onClick={() => setShowForm(!showForm)} className="inline-flex items-center gap-2 bg-teal-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-teal-700 transition-colors">
          <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 4v16m8-8H4" /></svg>
          طلب إجازة
        </button>
      </div>

      {showForm && (
        <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-4 sm:p-6">
          <h2 className="text-base font-semibold text-gray-800 mb-4">طلب إجازة جديد</h2>
          <form onSubmit={handleSubmit} className="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div className="sm:col-span-2">
              <label className="block text-sm font-medium text-gray-700 mb-1">الموظف *</label>
              <select value={form.employee_id} onChange={(e) => setForm({ ...form, employee_id: e.target.value })} required className="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm">
                <option value="">اختر الموظف</option>
                {employees.map((emp) => (
                  <option key={emp.id} value={emp.id}>{emp.full_name_ar || emp.full_name_en} — {emp.employee_number}</option>
                ))}
              </select>
            </div>
            <div className="sm:col-span-2">
              <label className="block text-sm font-medium text-gray-700 mb-1">نوع الإجازة *</label>
              <select value={form.leave_type_id} onChange={(e) => setForm({ ...form, leave_type_id: e.target.value })} required className="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm">
                <option value="">اختر نوع الإجازة</option>
                {leaveTypes.map((lt) => (
                  <option key={lt.id} value={lt.id}>{lt.name_ar || lt.name}</option>
                ))}
              </select>
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">تاريخ البداية *</label>
              <input type="date" value={form.start_date} onChange={(e) => setForm({ ...form, start_date: e.target.value })} required className="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm" />
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">تاريخ النهاية *</label>
              <input type="date" value={form.end_date} onChange={(e) => setForm({ ...form, end_date: e.target.value })} required className="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm" />
            </div>
            <div className="sm:col-span-2">
              <label className="block text-sm font-medium text-gray-700 mb-1">السبب</label>
              <textarea value={form.reason} onChange={(e) => setForm({ ...form, reason: e.target.value })} rows={2} className="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm" />
            </div>
            <div className="sm:col-span-2 flex gap-2 justify-end">
              <button type="button" onClick={() => setShowForm(false)} className="px-4 py-2 text-sm text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50">إلغاء</button>
              <button type="submit" disabled={saving} className="px-4 py-2 text-sm bg-teal-600 text-white rounded-lg hover:bg-teal-700 disabled:opacity-50">
                {saving ? 'جاري الإرسال...' : 'تقديم الطلب'}
              </button>
            </div>
          </form>
        </div>
      )}

      <div className="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        {loading ? (
          <div className="flex items-center justify-center py-16"><div className="w-8 h-8 border-4 border-teal-200 border-t-teal-600 rounded-full animate-spin" /></div>
        ) : requests.length === 0 ? (
          <div className="text-center py-16 text-gray-500">لا يوجد طلبات إجازة</div>
        ) : (
          <div className="divide-y divide-gray-50">
            {requests.map((req) => (
              <div key={req.id} className="px-4 py-3 hover:bg-gray-50">
                <div className="flex items-center justify-between">
                  <div>
                    <p className="font-medium text-gray-800 text-sm">{req.employee?.full_name_ar || req.employee?.full_name_en || 'أنا'}</p>
                    <p className="text-xs text-gray-500 mt-0.5">{req.leave_type?.name_ar} — {req.start_date} إلى {req.end_date}</p>
                    {req.reason && <p className="text-xs text-gray-400 mt-0.5">{req.reason}</p>}
                  </div>
                  <div className="flex items-center gap-2">
                    <span className={`inline-block px-2 py-0.5 rounded-full text-xs font-medium ${statusColors[req.status]}`}>
                      {statusLabels[req.status] || req.status}
                    </span>
                    {['submitted', 'pending', 'pending_substitute', 'pending_supervisor', 'pending_hr', 'pending_admin_manager', 'pending_general_manager'].includes(req.status) && (
                      <div className="flex gap-1">
                        <button onClick={() => handleAction(req.id, 'approve')} className="text-xs text-green-600 hover:text-green-800 font-medium">قبول</button>
                        <button onClick={() => handleAction(req.id, 'reject')} className="text-xs text-red-600 hover:text-red-800 font-medium">رفض</button>
                      </div>
                    )}
                  </div>
                </div>
              </div>
            ))}
          </div>
        )}
      </div>
    </div>
  );
}
