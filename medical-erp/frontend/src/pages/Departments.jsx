import { useState, useEffect, useCallback } from 'react';
import toast from 'react-hot-toast';
import departmentService from '../services/departmentService';

export default function Departments() {
  const [departments, setDepartments] = useState([]);
  const [loading, setLoading] = useState(true);
  const [showForm, setShowForm] = useState(false);
  const [editingId, setEditingId] = useState(null);
  const [form, setForm] = useState({ name_ar: '', name_en: '', code: '', description: '' });
  const [saving, setSaving] = useState(false);

  const fetchDepartments = useCallback(async () => {
    setLoading(true);
    try {
      const { data } = await departmentService.getAll();
      setDepartments(data.data);
    } catch {
      toast.error('حدث خطأ في تحميل الأقسام');
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    fetchDepartments();
  }, [fetchDepartments]);

  function openNew() {
    setForm({ name_ar: '', name_en: '', code: '', description: '' });
    setEditingId(null);
    setShowForm(true);
  }

  function openEdit(dept) {
    setForm({ name_ar: dept.name_ar, name_en: dept.name_en || '', code: dept.code || '', description: dept.description || '' });
    setEditingId(dept.id);
    setShowForm(true);
  }

  async function handleSubmit(e) {
    e.preventDefault();
    if (!form.name_ar.trim()) return toast.error('اسم القسم مطلوب');
    setSaving(true);
    try {
      if (editingId) {
        await departmentService.update(editingId, form);
        toast.success('تم تحديث القسم');
      } else {
        await departmentService.create(form);
        toast.success('تم إضافة القسم');
      }
      setShowForm(false);
      fetchDepartments();
    } catch (err) {
      toast.error(err.response?.data?.message || 'حدث خطأ');
    } finally {
      setSaving(false);
    }
  }

  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between">
        <h1 className="text-xl font-bold text-gray-800">الأقسام</h1>
        <button
          onClick={openNew}
          className="inline-flex items-center gap-2 bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-700 transition-colors"
        >
          <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 4v16m8-8H4" />
          </svg>
          إضافة قسم
        </button>
      </div>

      {/* Form Modal */}
      {showForm && (
        <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-4 sm:p-6">
          <h2 className="text-base font-semibold text-gray-800 mb-4">{editingId ? 'تعديل القسم' : 'إضافة قسم جديد'}</h2>
          <form onSubmit={handleSubmit} className="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">اسم القسم (عربي) *</label>
              <input type="text" value={form.name_ar} onChange={(e) => setForm({ ...form, name_ar: e.target.value })} className="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-blue-500" />
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">اسم القسم (إنجليزي)</label>
              <input type="text" value={form.name_en} onChange={(e) => setForm({ ...form, name_en: e.target.value })} className="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-blue-500" />
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">الرمز</label>
              <input type="text" value={form.code} onChange={(e) => setForm({ ...form, code: e.target.value })} className="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-blue-500" />
            </div>
            <div className="sm:col-span-2">
              <label className="block text-sm font-medium text-gray-700 mb-1">الوصف</label>
              <textarea value={form.description} onChange={(e) => setForm({ ...form, description: e.target.value })} rows={2} className="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-blue-500" />
            </div>
            <div className="sm:col-span-2 flex gap-2 justify-end">
              <button type="button" onClick={() => setShowForm(false)} className="px-4 py-2 text-sm text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50">إلغاء</button>
              <button type="submit" disabled={saving} className="px-4 py-2 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50">
                {saving ? 'جاري الحفظ...' : editingId ? 'تحديث' : 'إضافة'}
              </button>
            </div>
          </form>
        </div>
      )}

      {/* List */}
      <div className="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        {loading ? (
          <div className="flex items-center justify-center py-16">
            <div className="w-8 h-8 border-4 border-blue-200 border-t-blue-600 rounded-full animate-spin" />
          </div>
        ) : departments.length === 0 ? (
          <div className="text-center py-16 text-gray-500">لا يوجد أقسام</div>
        ) : (
          <div className="divide-y divide-gray-50">
            {departments.map((dept) => (
              <div key={dept.id} className="flex items-center justify-between px-4 py-3 hover:bg-gray-50">
                <div>
                  <p className="font-medium text-gray-800">{dept.name_ar}</p>
                  <p className="text-xs text-gray-500">{dept.code ? `${dept.code} — ` : ''}{dept.employees_count ?? 0} موظف</p>
                </div>
                <button onClick={() => openEdit(dept)} className="text-sm text-blue-600 hover:text-blue-800">تعديل</button>
              </div>
            ))}
          </div>
        )}
      </div>
    </div>
  );
}
