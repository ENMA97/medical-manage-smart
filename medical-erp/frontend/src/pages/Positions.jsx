import { useState, useEffect, useCallback } from 'react';
import toast from 'react-hot-toast';
import positionService from '../services/positionService';
import departmentService from '../services/departmentService';

export default function Positions() {
  const [positions, setPositions] = useState([]);
  const [departments, setDepartments] = useState([]);
  const [loading, setLoading] = useState(true);
  const [showForm, setShowForm] = useState(false);
  const [editingId, setEditingId] = useState(null);
  const [saving, setSaving] = useState(false);
  const [filter, setFilter] = useState({ search: '', department_id: '' });
  const [form, setForm] = useState({
    code: '', title: '', title_ar: '', department_id: '',
    category: '', description: '', min_salary: '', max_salary: '', is_active: true,
  });

  const fetchPositions = useCallback(async () => {
    setLoading(true);
    try {
      const params = {};
      if (filter.search) params.search = filter.search;
      if (filter.department_id) params.department_id = filter.department_id;
      const { data } = await positionService.getAll(params);
      setPositions(data.data?.data || data.data || []);
    } catch {
      toast.error('حدث خطأ في تحميل المسميات الوظيفية');
    } finally {
      setLoading(false);
    }
  }, [filter]);

  const fetchDepartments = useCallback(async () => {
    try {
      const { data } = await departmentService.getAll({ per_page: 100 });
      setDepartments(data.data?.data || data.data || []);
    } catch { /* ignore */ }
  }, []);

  useEffect(() => {
    fetchDepartments();
  }, [fetchDepartments]);

  useEffect(() => {
    fetchPositions();
  }, [fetchPositions]);

  function openNew() {
    setForm({
      code: '', title: '', title_ar: '', department_id: '',
      category: '', description: '', min_salary: '', max_salary: '', is_active: true,
    });
    setEditingId(null);
    setShowForm(true);
  }

  function openEdit(pos) {
    setForm({
      code: pos.code || '',
      title: pos.title || '',
      title_ar: pos.title_ar || '',
      department_id: pos.department_id || '',
      category: pos.category || '',
      description: pos.description || '',
      min_salary: pos.min_salary || '',
      max_salary: pos.max_salary || '',
      is_active: pos.is_active ?? true,
    });
    setEditingId(pos.id);
    setShowForm(true);
  }

  async function handleSubmit(e) {
    e.preventDefault();
    if (!form.title_ar.trim()) return toast.error('المسمى الوظيفي بالعربي مطلوب');
    if (!form.code.trim()) return toast.error('الرمز مطلوب');
    if (!form.department_id) return toast.error('القسم مطلوب');
    setSaving(true);
    try {
      const payload = { ...form };
      if (payload.min_salary === '') delete payload.min_salary;
      if (payload.max_salary === '') delete payload.max_salary;

      if (editingId) {
        await positionService.update(editingId, payload);
        toast.success('تم تحديث المسمى الوظيفي');
      } else {
        await positionService.create(payload);
        toast.success('تم إضافة المسمى الوظيفي');
      }
      setShowForm(false);
      fetchPositions();
    } catch (err) {
      toast.error(err.response?.data?.message || 'حدث خطأ');
    } finally {
      setSaving(false);
    }
  }

  async function handleDelete(id) {
    if (!window.confirm('هل أنت متأكد من حذف هذا المسمى الوظيفي؟')) return;
    try {
      await positionService.delete(id);
      toast.success('تم حذف المسمى الوظيفي');
      fetchPositions();
    } catch (err) {
      toast.error(err.response?.data?.message || 'لا يمكن حذف المسمى');
    }
  }

  const getDeptName = (deptId) => {
    const dept = departments.find(d => d.id === deptId);
    return dept?.name_ar || dept?.name || '-';
  };

  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between">
        <h1 className="text-xl font-bold text-gray-800">المسميات الوظيفية</h1>
        <button
          onClick={openNew}
          className="inline-flex items-center gap-2 bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-700 transition-colors"
        >
          <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 4v16m8-8H4" />
          </svg>
          إضافة مسمى
        </button>
      </div>

      {/* Filters */}
      <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
        <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <input
            type="text"
            placeholder="بحث بالاسم أو الرمز..."
            value={filter.search}
            onChange={(e) => setFilter({ ...filter, search: e.target.value })}
            className="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-blue-500"
          />
          <select
            value={filter.department_id}
            onChange={(e) => setFilter({ ...filter, department_id: e.target.value })}
            className="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-blue-500"
          >
            <option value="">جميع الأقسام</option>
            {departments.map(d => (
              <option key={d.id} value={d.id}>{d.name_ar || d.name}</option>
            ))}
          </select>
        </div>
      </div>

      {/* Form Modal */}
      {showForm && (
        <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-4 sm:p-6">
          <h2 className="text-base font-semibold text-gray-800 mb-4">
            {editingId ? 'تعديل المسمى الوظيفي' : 'إضافة مسمى وظيفي جديد'}
          </h2>
          <form onSubmit={handleSubmit} className="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">الرمز *</label>
              <input type="text" value={form.code} onChange={(e) => setForm({ ...form, code: e.target.value })} className="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-blue-500" />
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">القسم *</label>
              <select value={form.department_id} onChange={(e) => setForm({ ...form, department_id: e.target.value })} className="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-blue-500">
                <option value="">اختر القسم</option>
                {departments.map(d => (
                  <option key={d.id} value={d.id}>{d.name_ar || d.name}</option>
                ))}
              </select>
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">المسمى (عربي) *</label>
              <input type="text" value={form.title_ar} onChange={(e) => setForm({ ...form, title_ar: e.target.value })} className="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-blue-500" />
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">المسمى (إنجليزي)</label>
              <input type="text" value={form.title} onChange={(e) => setForm({ ...form, title: e.target.value })} className="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-blue-500" />
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">التصنيف</label>
              <select value={form.category} onChange={(e) => setForm({ ...form, category: e.target.value })} className="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-blue-500">
                <option value="">اختر التصنيف</option>
                <option value="executive">تنفيذي</option>
                <option value="management">إداري</option>
                <option value="technical">تقني</option>
                <option value="operational">تشغيلي</option>
                <option value="support">دعم</option>
              </select>
            </div>
            <div className="flex items-center gap-4">
              <div className="flex-1">
                <label className="block text-sm font-medium text-gray-700 mb-1">الحد الأدنى للراتب</label>
                <input type="number" value={form.min_salary} onChange={(e) => setForm({ ...form, min_salary: e.target.value })} className="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-blue-500" />
              </div>
              <div className="flex-1">
                <label className="block text-sm font-medium text-gray-700 mb-1">الحد الأقصى للراتب</label>
                <input type="number" value={form.max_salary} onChange={(e) => setForm({ ...form, max_salary: e.target.value })} className="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-blue-500" />
              </div>
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
        ) : positions.length === 0 ? (
          <div className="text-center py-16 text-gray-500">لا يوجد مسميات وظيفية</div>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-sm">
              <thead className="bg-gray-50 text-gray-600">
                <tr>
                  <th className="text-right px-4 py-3 font-medium">الرمز</th>
                  <th className="text-right px-4 py-3 font-medium">المسمى</th>
                  <th className="text-right px-4 py-3 font-medium hidden sm:table-cell">القسم</th>
                  <th className="text-right px-4 py-3 font-medium hidden sm:table-cell">التصنيف</th>
                  <th className="text-center px-4 py-3 font-medium">الموظفين</th>
                  <th className="text-center px-4 py-3 font-medium">الحالة</th>
                  <th className="text-center px-4 py-3 font-medium">إجراءات</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-gray-50">
                {positions.map((pos) => (
                  <tr key={pos.id} className="hover:bg-gray-50">
                    <td className="px-4 py-3 text-gray-600">{pos.code}</td>
                    <td className="px-4 py-3">
                      <p className="font-medium text-gray-800">{pos.title_ar}</p>
                      {pos.title && <p className="text-xs text-gray-500">{pos.title}</p>}
                    </td>
                    <td className="px-4 py-3 text-gray-600 hidden sm:table-cell">
                      {pos.department?.name_ar || getDeptName(pos.department_id)}
                    </td>
                    <td className="px-4 py-3 text-gray-600 hidden sm:table-cell">{pos.category || '-'}</td>
                    <td className="px-4 py-3 text-center">{pos.employees_count ?? 0}</td>
                    <td className="px-4 py-3 text-center">
                      <span className={`inline-block px-2 py-0.5 rounded-full text-xs font-medium ${pos.is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600'}`}>
                        {pos.is_active ? 'نشط' : 'غير نشط'}
                      </span>
                    </td>
                    <td className="px-4 py-3 text-center">
                      <div className="flex items-center justify-center gap-2">
                        <button onClick={() => openEdit(pos)} className="text-blue-600 hover:text-blue-800 text-xs">تعديل</button>
                        <button onClick={() => handleDelete(pos.id)} className="text-red-600 hover:text-red-800 text-xs">حذف</button>
                      </div>
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
