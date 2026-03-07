import { useState, useEffect, useCallback } from 'react';
import toast from 'react-hot-toast';
import resignationService from '../services/resignationService';

const statusLabels = { pending: 'قيد المراجعة', approved: 'مقبولة', rejected: 'مرفوضة' };
const statusColors = { pending: 'bg-yellow-100 text-yellow-700', approved: 'bg-green-100 text-green-700', rejected: 'bg-red-100 text-red-700' };

export default function Resignations() {
  const [resignations, setResignations] = useState([]);
  const [loading, setLoading] = useState(true);
  const [showForm, setShowForm] = useState(false);
  const [form, setForm] = useState({ reason: '', requested_last_day: '', notes: '' });
  const [saving, setSaving] = useState(false);

  const fetchResignations = useCallback(async () => {
    setLoading(true);
    try {
      const { data } = await resignationService.getAll();
      setResignations(data.data);
    } catch {
      toast.error('حدث خطأ في تحميل الاستقالات');
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => { fetchResignations(); }, [fetchResignations]);

  async function handleSubmit(e) {
    e.preventDefault();
    setSaving(true);
    try {
      await resignationService.create(form);
      toast.success('تم تقديم الاستقالة');
      setShowForm(false);
      setForm({ reason: '', requested_last_day: '', notes: '' });
      fetchResignations();
    } catch (err) {
      toast.error(err.response?.data?.message || 'حدث خطأ');
    } finally {
      setSaving(false);
    }
  }

  async function handleAction(id, action) {
    try {
      if (action === 'approve') await resignationService.approve(id);
      else await resignationService.reject(id);
      toast.success('تم تحديث الطلب');
      fetchResignations();
    } catch (err) {
      toast.error(err.response?.data?.message || 'حدث خطأ');
    }
  }

  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between">
        <h1 className="text-xl font-bold text-gray-800">الاستقالات</h1>
        <button onClick={() => setShowForm(!showForm)} className="inline-flex items-center gap-2 bg-red-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-red-700 transition-colors">
          تقديم استقالة
        </button>
      </div>

      {showForm && (
        <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-4 sm:p-6">
          <form onSubmit={handleSubmit} className="space-y-4">
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">سبب الاستقالة *</label>
              <textarea value={form.reason} onChange={(e) => setForm({ ...form, reason: e.target.value })} required rows={3} className="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm" />
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">آخر يوم عمل مطلوب *</label>
              <input type="date" value={form.requested_last_day} onChange={(e) => setForm({ ...form, requested_last_day: e.target.value })} required className="w-full sm:w-64 px-3 py-2 border border-gray-200 rounded-lg text-sm" />
            </div>
            <div className="flex gap-2 justify-end">
              <button type="button" onClick={() => setShowForm(false)} className="px-4 py-2 text-sm text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50">إلغاء</button>
              <button type="submit" disabled={saving} className="px-4 py-2 text-sm bg-red-600 text-white rounded-lg hover:bg-red-700 disabled:opacity-50">
                {saving ? 'جاري الإرسال...' : 'تقديم الاستقالة'}
              </button>
            </div>
          </form>
        </div>
      )}

      <div className="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        {loading ? (
          <div className="flex items-center justify-center py-16"><div className="w-8 h-8 border-4 border-blue-200 border-t-blue-600 rounded-full animate-spin" /></div>
        ) : resignations.length === 0 ? (
          <div className="text-center py-16 text-gray-500">لا يوجد استقالات</div>
        ) : (
          <div className="divide-y divide-gray-50">
            {resignations.map((r) => (
              <div key={r.id} className="px-4 py-3 hover:bg-gray-50">
                <div className="flex items-center justify-between">
                  <div>
                    <p className="font-medium text-gray-800 text-sm">{r.employee?.full_name}</p>
                    <p className="text-xs text-gray-500 mt-0.5">آخر يوم: {r.requested_last_day}</p>
                    <p className="text-xs text-gray-400 mt-0.5">{r.reason}</p>
                  </div>
                  <div className="flex items-center gap-2">
                    <span className={`inline-block px-2 py-0.5 rounded-full text-xs font-medium ${statusColors[r.status]}`}>
                      {statusLabels[r.status] || r.status}
                    </span>
                    {r.status === 'pending' && (
                      <div className="flex gap-1">
                        <button onClick={() => handleAction(r.id, 'approve')} className="text-xs text-green-600 hover:text-green-800 font-medium">قبول</button>
                        <button onClick={() => handleAction(r.id, 'reject')} className="text-xs text-red-600 hover:text-red-800 font-medium">رفض</button>
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
