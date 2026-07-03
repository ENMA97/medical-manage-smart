import { useState, useEffect, useCallback } from 'react';
import toast from 'react-hot-toast';
import shiftService from '../services/shiftService';
import employeeService from '../services/employeeService';

const EMPTY_FORM = {
  code: '', name: '', name_ar: '', start_time: '08:00', end_time: '16:00',
  break_minutes: 30, grace_period_minutes: 15, is_overnight: false,
  is_active: true, description: '',
};

export default function Shifts() {
  const [shifts, setShifts] = useState([]);
  const [assignments, setAssignments] = useState([]);
  const [employees, setEmployees] = useState([]);
  const [loading, setLoading] = useState(true);
  const [showForm, setShowForm] = useState(false);
  const [editingId, setEditingId] = useState(null);
  const [saving, setSaving] = useState(false);
  const [form, setForm] = useState(EMPTY_FORM);
  const [assignShift, setAssignShift] = useState(null);
  const [assignForm, setAssignForm] = useState({
    employee_ids: [],
    effective_from: new Date().toISOString().slice(0, 10),
  });

  const fetchShifts = useCallback(async () => {
    setLoading(true);
    try {
      const [shiftsRes, assignRes] = await Promise.all([
        shiftService.getAll({ per_page: 100 }),
        shiftService.getAssignments({ per_page: 100, active_only: true }),
      ]);
      setShifts(shiftsRes.data.data?.data || shiftsRes.data.data || []);
      setAssignments(assignRes.data.data?.data || assignRes.data.data || []);
    } catch {
      toast.error('حدث خطأ في تحميل الورديات');
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    fetchShifts();
    employeeService.getAll({ per_page: 200 })
      .then(({ data }) => setEmployees(data.data?.data || data.data || []))
      .catch(() => { /* silent */ });
  }, [fetchShifts]);

  function openNew() {
    setForm(EMPTY_FORM);
    setEditingId(null);
    setShowForm(true);
    setAssignShift(null);
  }

  function openEdit(shift) {
    setForm({
      code: shift.code || '',
      name: shift.name || '',
      name_ar: shift.name_ar || '',
      start_time: shift.start_time?.slice(0, 5) || '08:00',
      end_time: shift.end_time?.slice(0, 5) || '16:00',
      break_minutes: shift.break_minutes ?? 30,
      grace_period_minutes: shift.grace_period_minutes ?? 15,
      is_overnight: shift.is_overnight ?? false,
      is_active: shift.is_active ?? true,
      description: shift.description || '',
    });
    setEditingId(shift.id);
    setShowForm(true);
    setAssignShift(null);
  }

  async function handleSubmit(e) {
    e.preventDefault();
    if (!form.name_ar.trim()) return toast.error('اسم الوردية بالعربي مطلوب');
    if (!form.code.trim()) return toast.error('الرمز مطلوب');
    setSaving(true);
    try {
      const payload = { ...form, name: form.name || form.name_ar };
      if (editingId) {
        await shiftService.update(editingId, payload);
        toast.success('تم تحديث الوردية');
      } else {
        await shiftService.create(payload);
        toast.success('تم إضافة الوردية');
      }
      setShowForm(false);
      fetchShifts();
    } catch (err) {
      toast.error(err.response?.data?.message || 'حدث خطأ');
    } finally {
      setSaving(false);
    }
  }

  async function handleDelete(id) {
    if (!window.confirm('هل أنت متأكد من حذف هذه الوردية؟')) return;
    try {
      await shiftService.delete(id);
      toast.success('تم حذف الوردية');
      fetchShifts();
    } catch (err) {
      toast.error(err.response?.data?.message || 'لا يمكن حذف الوردية');
    }
  }

  async function handleAssign(e) {
    e.preventDefault();
    if (assignForm.employee_ids.length === 0) return toast.error('اختر موظفاً واحداً على الأقل');
    setSaving(true);
    try {
      await shiftService.assign(assignShift.id, assignForm);
      toast.success('تم إسناد الوردية للموظفين');
      setAssignShift(null);
      setAssignForm({ employee_ids: [], effective_from: new Date().toISOString().slice(0, 10) });
      fetchShifts();
    } catch (err) {
      toast.error(err.response?.data?.message || 'حدث خطأ في الإسناد');
    } finally {
      setSaving(false);
    }
  }

  async function handleUnassign(assignmentId) {
    if (!window.confirm('هل أنت متأكد من إلغاء هذا الإسناد؟')) return;
    try {
      await shiftService.unassign(assignmentId);
      toast.success('تم إلغاء الإسناد');
      fetchShifts();
    } catch (err) {
      toast.error(err.response?.data?.message || 'حدث خطأ');
    }
  }

  function toggleEmployee(id) {
    setAssignForm((prev) => ({
      ...prev,
      employee_ids: prev.employee_ids.includes(id)
        ? prev.employee_ids.filter((e) => e !== id)
        : [...prev.employee_ids, id],
    }));
  }

  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between">
        <h1 className="text-xl font-bold text-gray-800">الورديات</h1>
        <button
          onClick={openNew}
          className="inline-flex items-center gap-2 bg-teal-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-teal-700 transition-colors"
        >
          <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 4v16m8-8H4" />
          </svg>
          إضافة وردية
        </button>
      </div>

      {/* Form */}
      {showForm && (
        <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-4 sm:p-6">
          <h2 className="text-base font-semibold text-gray-800 mb-4">
            {editingId ? 'تعديل الوردية' : 'إضافة وردية جديدة'}
          </h2>
          <form onSubmit={handleSubmit} className="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">الرمز *</label>
              <input type="text" value={form.code} onChange={(e) => setForm({ ...form, code: e.target.value })} className="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-teal-500" />
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">الاسم (عربي) *</label>
              <input type="text" value={form.name_ar} onChange={(e) => setForm({ ...form, name_ar: e.target.value })} className="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-teal-500" />
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">الاسم (إنجليزي)</label>
              <input type="text" value={form.name} onChange={(e) => setForm({ ...form, name: e.target.value })} className="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-teal-500" />
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">وقت البداية *</label>
              <input type="time" value={form.start_time} onChange={(e) => setForm({ ...form, start_time: e.target.value })} className="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-teal-500" />
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">وقت النهاية *</label>
              <input type="time" value={form.end_time} onChange={(e) => setForm({ ...form, end_time: e.target.value })} className="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-teal-500" />
            </div>
            <div className="flex items-center gap-4">
              <div className="flex-1">
                <label className="block text-sm font-medium text-gray-700 mb-1">استراحة (د)</label>
                <input type="number" min="0" value={form.break_minutes} onChange={(e) => setForm({ ...form, break_minutes: Number(e.target.value) })} className="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-teal-500" />
              </div>
              <div className="flex-1">
                <label className="block text-sm font-medium text-gray-700 mb-1">سماحية (د)</label>
                <input type="number" min="0" value={form.grace_period_minutes} onChange={(e) => setForm({ ...form, grace_period_minutes: Number(e.target.value) })} className="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-teal-500" />
              </div>
            </div>
            <div className="sm:col-span-2">
              <label className="block text-sm font-medium text-gray-700 mb-1">الوصف</label>
              <input type="text" value={form.description} onChange={(e) => setForm({ ...form, description: e.target.value })} className="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-teal-500" />
            </div>
            <div className="flex items-center gap-4 pt-6">
              <label className="flex items-center gap-2 text-sm text-gray-700">
                <input type="checkbox" checked={form.is_overnight} onChange={(e) => setForm({ ...form, is_overnight: e.target.checked })} className="rounded text-teal-600 focus:ring-teal-500" />
                تمتد لليوم التالي
              </label>
              <label className="flex items-center gap-2 text-sm text-gray-700">
                <input type="checkbox" checked={form.is_active} onChange={(e) => setForm({ ...form, is_active: e.target.checked })} className="rounded text-teal-600 focus:ring-teal-500" />
                نشطة
              </label>
            </div>
            <div className="sm:col-span-3 flex gap-2 justify-end">
              <button type="button" onClick={() => setShowForm(false)} className="px-4 py-2 text-sm text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50">إلغاء</button>
              <button type="submit" disabled={saving} className="px-4 py-2 text-sm bg-teal-600 text-white rounded-lg hover:bg-teal-700 disabled:opacity-50">
                {saving ? 'جاري الحفظ...' : editingId ? 'تحديث' : 'إضافة'}
              </button>
            </div>
          </form>
        </div>
      )}

      {/* Assign panel */}
      {assignShift && (
        <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-4 sm:p-6">
          <h2 className="text-base font-semibold text-gray-800 mb-4">
            إسناد موظفين إلى: {assignShift.name_ar}
          </h2>
          <form onSubmit={handleAssign} className="space-y-4">
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">تاريخ بداية السريان *</label>
              <input
                type="date"
                value={assignForm.effective_from}
                onChange={(e) => setAssignForm({ ...assignForm, effective_from: e.target.value })}
                className="w-full sm:w-64 px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-teal-500"
              />
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-2">
                الموظفون ({assignForm.employee_ids.length} محدد)
              </label>
              <div className="max-h-56 overflow-y-auto border border-gray-200 rounded-lg divide-y divide-gray-50">
                {employees.map((emp) => (
                  <label key={emp.id} className="flex items-center gap-3 px-3 py-2 hover:bg-gray-50 cursor-pointer">
                    <input
                      type="checkbox"
                      checked={assignForm.employee_ids.includes(emp.id)}
                      onChange={() => toggleEmployee(emp.id)}
                      className="rounded text-teal-600 focus:ring-teal-500"
                    />
                    <span className="text-sm text-gray-800">
                      {emp.employee_number} — {emp.first_name_ar} {emp.last_name_ar}
                    </span>
                  </label>
                ))}
              </div>
            </div>
            <div className="flex gap-2 justify-end">
              <button type="button" onClick={() => setAssignShift(null)} className="px-4 py-2 text-sm text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50">إلغاء</button>
              <button type="submit" disabled={saving} className="px-4 py-2 text-sm bg-teal-600 text-white rounded-lg hover:bg-teal-700 disabled:opacity-50">
                {saving ? 'جاري الإسناد...' : 'إسناد'}
              </button>
            </div>
          </form>
        </div>
      )}

      {/* Shifts list */}
      <div className="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        {loading ? (
          <div className="flex items-center justify-center py-16">
            <div className="w-8 h-8 border-4 border-teal-200 border-t-teal-600 rounded-full animate-spin" />
          </div>
        ) : shifts.length === 0 ? (
          <div className="text-center py-16 text-gray-500">لا توجد ورديات — أضف وردية جديدة</div>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-sm">
              <thead className="bg-gray-50 text-gray-600">
                <tr>
                  <th className="text-right px-4 py-3 font-medium">الرمز</th>
                  <th className="text-right px-4 py-3 font-medium">الوردية</th>
                  <th className="text-center px-4 py-3 font-medium">التوقيت</th>
                  <th className="text-center px-4 py-3 font-medium hidden sm:table-cell">سماحية (د)</th>
                  <th className="text-center px-4 py-3 font-medium">الموظفون</th>
                  <th className="text-center px-4 py-3 font-medium">الحالة</th>
                  <th className="text-center px-4 py-3 font-medium">إجراءات</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-gray-50">
                {shifts.map((shift) => (
                  <tr key={shift.id} className="hover:bg-gray-50">
                    <td className="px-4 py-3 text-gray-600">{shift.code}</td>
                    <td className="px-4 py-3">
                      <p className="font-medium text-gray-800">{shift.name_ar}</p>
                      {shift.is_overnight && <p className="text-xs text-purple-500">تمتد لليوم التالي</p>}
                    </td>
                    <td className="px-4 py-3 text-center text-gray-600" dir="ltr">
                      {shift.start_time?.slice(0, 5)} - {shift.end_time?.slice(0, 5)}
                    </td>
                    <td className="px-4 py-3 text-center text-gray-600 hidden sm:table-cell">
                      {shift.grace_period_minutes}
                    </td>
                    <td className="px-4 py-3 text-center">{shift.assignments_count ?? 0}</td>
                    <td className="px-4 py-3 text-center">
                      <span className={`inline-block px-2 py-0.5 rounded-full text-xs font-medium ${shift.is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600'}`}>
                        {shift.is_active ? 'نشطة' : 'غير نشطة'}
                      </span>
                    </td>
                    <td className="px-4 py-3 text-center">
                      <div className="flex items-center justify-center gap-2">
                        <button onClick={() => { setAssignShift(shift); setShowForm(false); }} className="text-blue-600 hover:text-blue-800 text-xs">إسناد</button>
                        <button onClick={() => openEdit(shift)} className="text-teal-600 hover:text-teal-800 text-xs">تعديل</button>
                        <button onClick={() => handleDelete(shift.id)} className="text-red-600 hover:text-red-800 text-xs">حذف</button>
                      </div>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </div>

      {/* Active assignments */}
      <div className="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div className="px-4 py-3 border-b border-gray-100">
          <h2 className="text-sm font-semibold text-gray-800">الإسنادات السارية</h2>
        </div>
        {assignments.length === 0 ? (
          <div className="text-center py-10 text-gray-500 text-sm">لا توجد إسنادات سارية</div>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-sm">
              <thead className="bg-gray-50 text-gray-600">
                <tr>
                  <th className="text-right px-4 py-3 font-medium">الموظف</th>
                  <th className="text-right px-4 py-3 font-medium hidden sm:table-cell">القسم</th>
                  <th className="text-right px-4 py-3 font-medium">الوردية</th>
                  <th className="text-center px-4 py-3 font-medium hidden sm:table-cell">من تاريخ</th>
                  <th className="text-center px-4 py-3 font-medium">إجراءات</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-gray-50">
                {assignments.map((a) => (
                  <tr key={a.id} className="hover:bg-gray-50">
                    <td className="px-4 py-3">
                      <p className="font-medium text-gray-800">
                        {a.employee?.first_name_ar} {a.employee?.last_name_ar}
                      </p>
                      <p className="text-xs text-gray-500">{a.employee?.employee_number}</p>
                    </td>
                    <td className="px-4 py-3 text-gray-600 hidden sm:table-cell">
                      {a.employee?.department?.name_ar || '—'}
                    </td>
                    <td className="px-4 py-3 text-gray-600">{a.shift?.name_ar}</td>
                    <td className="px-4 py-3 text-center text-gray-600 hidden sm:table-cell">
                      {a.effective_from ? new Date(a.effective_from).toLocaleDateString('ar-SA') : '—'}
                    </td>
                    <td className="px-4 py-3 text-center">
                      <button onClick={() => handleUnassign(a.id)} className="text-red-600 hover:text-red-800 text-xs">إلغاء الإسناد</button>
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
