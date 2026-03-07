import { useState, useEffect, useCallback } from 'react';
import toast from 'react-hot-toast';
import { leaveRequestService } from '../services/leaveService';

const statusLabels = { pending: 'قيد الانتظار', approved: 'مقبول', rejected: 'مرفوض', cancelled: 'ملغي' };
const statusColors = {
  pending: 'bg-yellow-100 text-yellow-700',
  approved: 'bg-green-100 text-green-700',
  rejected: 'bg-red-100 text-red-700',
  cancelled: 'bg-gray-100 text-gray-600',
};

export default function LeaveRequests() {
  const [requests, setRequests] = useState([]);
  const [loading, setLoading] = useState(true);
  const [page, setPage] = useState(1);
  const [meta, setMeta] = useState({});
  const [showForm, setShowForm] = useState(false);
  const [form, setForm] = useState({ leave_type_id: '', start_date: '', end_date: '', reason: '' });
  const [saving, setSaving] = useState(false);

  const fetchRequests = useCallback(async () => {
    setLoading(true);
    try {
      const { data } = await leaveRequestService.getAll({ page, per_page: 15 });
      setRequests(data.data);
      setMeta(data.meta || {});
    } catch {
      toast.error('حدث خطأ في تحميل طلبات الإجازة');
    } finally {
      setLoading(false);
    }
  }, [page]);

  useEffect(() => { fetchRequests(); }, [fetchRequests]);

  async function handleSubmit(e) {
    e.preventDefault();
    setSaving(true);
    try {
      await leaveRequestService.create(form);
      toast.success('تم تقديم طلب الإجازة');
      setShowForm(false);
      setForm({ leave_type_id: '', start_date: '', end_date: '', reason: '' });
      fetchRequests();
    } catch (err) {
      toast.error(err.response?.data?.message || 'حدث خطأ');
    } finally {
      setSaving(false);
    }
  }

  async function handleAction(id, action) {
    try {
      if (action === 'approve') await leaveRequestService.approve(id);
      else if (action === 'reject') await leaveRequestService.reject(id);
      else if (action === 'cancel') await leaveRequestService.cancel(id);
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
        <button onClick={() => setShowForm(!showForm)} className="inline-flex items-center gap-2 bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-700 transition-colors">
          <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 4v16m8-8H4" /></svg>
          طلب إجازة
        </button>
      </div>

      {showForm && (
        <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-4 sm:p-6">
          <h2 className="text-base font-semibold text-gray-800 mb-4">طلب إجازة جديد</h2>
          <form onSubmit={handleSubmit} className="grid grid-cols-1 sm:grid-cols-2 gap-4">
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
              <button type="submit" disabled={saving} className="px-4 py-2 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50">
                {saving ? 'جاري الإرسال...' : 'تقديم الطلب'}
              </button>
            </div>
          </form>
        </div>
      )}

      <div className="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        {loading ? (
          <div className="flex items-center justify-center py-16"><div className="w-8 h-8 border-4 border-blue-200 border-t-blue-600 rounded-full animate-spin" /></div>
        ) : requests.length === 0 ? (
          <div className="text-center py-16 text-gray-500">لا يوجد طلبات إجازة</div>
        ) : (
          <div className="divide-y divide-gray-50">
            {requests.map((req) => (
              <div key={req.id} className="px-4 py-3 hover:bg-gray-50">
                <div className="flex items-center justify-between">
                  <div>
                    <p className="font-medium text-gray-800 text-sm">{req.employee?.full_name || 'أنا'}</p>
                    <p className="text-xs text-gray-500 mt-0.5">{req.leave_type?.name_ar} — {req.start_date} إلى {req.end_date}</p>
                    {req.reason && <p className="text-xs text-gray-400 mt-0.5">{req.reason}</p>}
                  </div>
                  <div className="flex items-center gap-2">
                    <span className={`inline-block px-2 py-0.5 rounded-full text-xs font-medium ${statusColors[req.status]}`}>
                      {statusLabels[req.status] || req.status}
                    </span>
                    {req.status === 'pending' && (
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
