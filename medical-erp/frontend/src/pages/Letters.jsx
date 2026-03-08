import { useState, useEffect, useCallback } from 'react';
import toast from 'react-hot-toast';
import letterService from '../services/letterService';
import employeeService from '../services/employeeService';
import Modal from '../components/ui/Modal';

const statusLabels = { pending: 'معلّق', approved: 'معتمد', rejected: 'مرفوض' };
const statusColors = {
  pending: 'bg-yellow-100 text-yellow-700',
  approved: 'bg-green-100 text-green-700',
  rejected: 'bg-red-100 text-red-700',
};

export default function Letters() {
  const [letters, setLetters] = useState([]);
  const [loading, setLoading] = useState(true);
  const [showCreate, setShowCreate] = useState(false);
  const [templates, setTemplates] = useState([]);
  const [employees, setEmployees] = useState([]);
  const [form, setForm] = useState({ employee_id: '', template_id: '', notes: '' });
  const [saving, setSaving] = useState(false);

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

  async function loadFormData() {
    const promises = [];
    if (employees.length === 0) {
      promises.push(
        employeeService.getAll({ per_page: 200 }).then(({ data }) => {
          setEmployees(data.data?.data || data.data || []);
        }).catch(() => {})
      );
    }
    if (templates.length === 0) {
      promises.push(
        letterService.getTemplates().then(({ data }) => {
          setTemplates(data.data || []);
        }).catch(() => {})
      );
    }
    await Promise.all(promises);
  }

  function openCreate() {
    loadFormData();
    setForm({ employee_id: '', template_id: '', notes: '' });
    setShowCreate(true);
  }

  async function handleCreate(e) {
    e.preventDefault();
    setSaving(true);
    try {
      await letterService.create({
        employee_id: form.employee_id,
        template_id: form.template_id,
        notes: form.notes || undefined,
      });
      toast.success('تم إنشاء الخطاب بنجاح');
      setShowCreate(false);
      fetchLetters();
    } catch (err) {
      toast.error(err.response?.data?.message || 'حدث خطأ');
    } finally {
      setSaving(false);
    }
  }

  async function handleApprove(id) {
    try {
      await letterService.approve(id);
      toast.success('تم اعتماد الخطاب');
      fetchLetters();
    } catch (err) {
      toast.error(err.response?.data?.message || 'حدث خطأ');
    }
  }

  function set(key, value) { setForm((f) => ({ ...f, [key]: value })); }

  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between">
        <h1 className="text-xl font-bold text-gray-800">إدارة الخطابات</h1>
        <button onClick={openCreate} className="px-4 py-2 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700 transition-colors">
          خطاب جديد
        </button>
      </div>

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
                      {letter.employee?.full_name_ar || letter.employee?.full_name_en || letter.employee?.full_name || '—'}
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

      {/* Create Letter Modal */}
      <Modal open={showCreate} onClose={() => setShowCreate(false)} title="إنشاء خطاب جديد">
        <form onSubmit={handleCreate} className="space-y-4">
          <div>
            <label className="block text-xs text-gray-600 mb-1">الموظف</label>
            <select
              value={form.employee_id} onChange={(e) => set('employee_id', e.target.value)}
              required
              className="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            >
              <option value="">اختر الموظف</option>
              {employees.map((emp) => (
                <option key={emp.id} value={emp.id}>
                  {emp.full_name || `${emp.first_name_ar || emp.first_name} ${emp.last_name_ar || emp.last_name}`} — {emp.employee_number}
                </option>
              ))}
            </select>
          </div>
          <div>
            <label className="block text-xs text-gray-600 mb-1">قالب الخطاب</label>
            <select
              value={form.template_id} onChange={(e) => set('template_id', e.target.value)}
              required
              className="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            >
              <option value="">اختر القالب</option>
              {templates.map((t) => (
                <option key={t.id} value={t.id}>{t.name_ar || t.name} — {t.letter_type}</option>
              ))}
            </select>
          </div>
          <div>
            <label className="block text-xs text-gray-600 mb-1">ملاحظات (اختياري)</label>
            <textarea value={form.notes} onChange={(e) => set('notes', e.target.value)} rows={2}
              className="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-none" />
          </div>
          <div className="flex gap-2 justify-end pt-2">
            <button type="button" onClick={() => setShowCreate(false)} className="px-4 py-2 text-sm text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50">إلغاء</button>
            <button type="submit" disabled={saving} className="px-4 py-2 text-sm text-white bg-blue-600 rounded-lg hover:bg-blue-700 disabled:opacity-50">
              {saving ? 'جاري الإنشاء...' : 'إنشاء الخطاب'}
            </button>
          </div>
        </form>
      </Modal>
    </div>
  );
}
