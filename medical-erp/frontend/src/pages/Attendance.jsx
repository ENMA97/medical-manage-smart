import { useState, useEffect, useCallback } from 'react';
import toast from 'react-hot-toast';
import attendanceService from '../services/attendanceService';
import employeeService from '../services/employeeService';
import departmentService from '../services/departmentService';
import { useAuth } from '../contexts/AuthContext';

const STATUS_LABELS = {
  present: 'حاضر',
  late: 'متأخر',
  absent: 'غائب',
  on_leave: 'في إجازة',
  holiday: 'عطلة',
  mission: 'مهمة عمل',
};

const STATUS_STYLES = {
  present: 'bg-green-100 text-green-700',
  late: 'bg-amber-100 text-amber-700',
  absent: 'bg-red-100 text-red-700',
  on_leave: 'bg-blue-100 text-blue-700',
  holiday: 'bg-gray-100 text-gray-600',
  mission: 'bg-purple-100 text-purple-700',
};

function StatusBadge({ status }) {
  return (
    <span className={`inline-block px-2 py-0.5 rounded-full text-xs font-medium ${STATUS_STYLES[status] || 'bg-gray-100 text-gray-600'}`}>
      {STATUS_LABELS[status] || status}
    </span>
  );
}

function formatTime(dt) {
  if (!dt) return '—';
  return new Date(dt).toLocaleTimeString('ar-SA', { hour: '2-digit', minute: '2-digit' });
}

export default function Attendance() {
  const { user } = useAuth();
  const isHr = ['super_admin', 'hr_manager'].includes(user?.user_type);

  const [myData, setMyData] = useState(null);
  const [actionLoading, setActionLoading] = useState(false);

  // HR state
  const [records, setRecords] = useState([]);
  const [summary, setSummary] = useState(null);
  const [loading, setLoading] = useState(isHr);
  const [employees, setEmployees] = useState([]);
  const [departments, setDepartments] = useState([]);
  const [filter, setFilter] = useState({
    date: new Date().toISOString().slice(0, 10),
    status: '',
    department_id: '',
  });
  const [showForm, setShowForm] = useState(false);
  const [saving, setSaving] = useState(false);
  const [form, setForm] = useState({
    employee_id: '', attendance_date: new Date().toISOString().slice(0, 10),
    status: 'present', check_in_time: '', check_out_time: '', notes: '',
  });

  const fetchMy = useCallback(async () => {
    try {
      const { data } = await attendanceService.getMy();
      setMyData(data.data);
    } catch { /* silent */ }
  }, []);

  const fetchRecords = useCallback(async () => {
    if (!isHr) return;
    setLoading(true);
    try {
      const params = { per_page: 50 };
      if (filter.date) params.date = filter.date;
      if (filter.status) params.status = filter.status;
      if (filter.department_id) params.department_id = filter.department_id;
      const [recordsRes, summaryRes] = await Promise.all([
        attendanceService.getAll(params),
        attendanceService.getDailySummary({ date: filter.date }),
      ]);
      setRecords(recordsRes.data.data?.data || recordsRes.data.data || []);
      setSummary(summaryRes.data.data);
    } catch {
      toast.error('حدث خطأ في تحميل سجلات الحضور');
    } finally {
      setLoading(false);
    }
  }, [filter, isHr]);

  useEffect(() => {
    fetchMy();
  }, [fetchMy]);

  useEffect(() => {
    fetchRecords();
  }, [fetchRecords]);

  useEffect(() => {
    if (!isHr) return;
    Promise.all([
      employeeService.getAll({ per_page: 200 }),
      departmentService.getAll({ per_page: 100 }),
    ]).then(([empRes, deptRes]) => {
      setEmployees(empRes.data.data?.data || empRes.data.data || []);
      setDepartments(deptRes.data.data?.data || deptRes.data.data || []);
    }).catch(() => { /* silent */ });
  }, [isHr]);

  async function handleCheckIn() {
    setActionLoading(true);
    try {
      const { data } = await attendanceService.checkIn();
      toast.success(data.message || 'تم تسجيل الحضور');
      fetchMy();
      fetchRecords();
    } catch (err) {
      toast.error(err.response?.data?.message || 'حدث خطأ في تسجيل الحضور');
    } finally {
      setActionLoading(false);
    }
  }

  async function handleCheckOut() {
    setActionLoading(true);
    try {
      const { data } = await attendanceService.checkOut();
      toast.success(data.message || 'تم تسجيل الانصراف');
      fetchMy();
      fetchRecords();
    } catch (err) {
      toast.error(err.response?.data?.message || 'حدث خطأ في تسجيل الانصراف');
    } finally {
      setActionLoading(false);
    }
  }

  async function handleSubmit(e) {
    e.preventDefault();
    if (!form.employee_id) return toast.error('الموظف مطلوب');
    setSaving(true);
    try {
      const payload = { ...form };
      if (!payload.check_in_time) delete payload.check_in_time;
      else payload.check_in_time = `${form.attendance_date} ${payload.check_in_time}`;
      if (!payload.check_out_time) delete payload.check_out_time;
      else payload.check_out_time = `${form.attendance_date} ${payload.check_out_time}`;
      if (!payload.notes) delete payload.notes;

      await attendanceService.create(payload);
      toast.success('تم تسجيل الحضور بنجاح');
      setShowForm(false);
      fetchRecords();
    } catch (err) {
      toast.error(err.response?.data?.message || 'حدث خطأ');
    } finally {
      setSaving(false);
    }
  }

  const today = myData?.today;
  const currentShift = myData?.current_shift;
  const mySummary = myData?.summary;

  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between">
        <h1 className="text-xl font-bold text-gray-800">الحضور والانصراف</h1>
        {isHr && (
          <button
            onClick={() => setShowForm(!showForm)}
            className="inline-flex items-center gap-2 bg-teal-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-teal-700 transition-colors"
          >
            <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 4v16m8-8H4" />
            </svg>
            تسجيل يدوي
          </button>
        )}
      </div>

      {/* ── Self check-in/out card ── */}
      <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-4 sm:p-6">
        <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
          <div>
            <h2 className="text-base font-semibold text-gray-800">تسجيلي اليوم</h2>
            <p className="text-sm text-gray-500 mt-1">
              {currentShift
                ? `الوردية: ${currentShift.name_ar} (${currentShift.start_time?.slice(0, 5)} - ${currentShift.end_time?.slice(0, 5)})`
                : 'لا توجد وردية مسندة'}
            </p>
            <div className="flex items-center gap-4 mt-2 text-sm text-gray-600">
              <span>الحضور: <b>{formatTime(today?.check_in_time)}</b></span>
              <span>الانصراف: <b>{formatTime(today?.check_out_time)}</b></span>
              {today && <StatusBadge status={today.status} />}
            </div>
          </div>
          <div className="flex gap-2">
            <button
              onClick={handleCheckIn}
              disabled={actionLoading || !!today?.check_in_time}
              className="px-5 py-2.5 bg-green-600 text-white rounded-lg text-sm font-medium hover:bg-green-700 disabled:opacity-40 transition-colors"
            >
              تسجيل حضور
            </button>
            <button
              onClick={handleCheckOut}
              disabled={actionLoading || !today?.check_in_time || !!today?.check_out_time}
              className="px-5 py-2.5 bg-rose-600 text-white rounded-lg text-sm font-medium hover:bg-rose-700 disabled:opacity-40 transition-colors"
            >
              تسجيل انصراف
            </button>
          </div>
        </div>
        {mySummary && (
          <div className="grid grid-cols-2 sm:grid-cols-4 gap-3 mt-4 pt-4 border-t border-gray-100">
            <div className="text-center">
              <p className="text-lg font-bold text-gray-800">{mySummary.days_present}</p>
              <p className="text-xs text-gray-500">أيام حضور هذا الشهر</p>
            </div>
            <div className="text-center">
              <p className="text-lg font-bold text-amber-600">{mySummary.days_late}</p>
              <p className="text-xs text-gray-500">أيام تأخير</p>
            </div>
            <div className="text-center">
              <p className="text-lg font-bold text-red-600">{mySummary.days_absent}</p>
              <p className="text-xs text-gray-500">أيام غياب</p>
            </div>
            <div className="text-center">
              <p className="text-lg font-bold text-teal-600">{Math.round((mySummary.total_worked_minutes || 0) / 60)}</p>
              <p className="text-xs text-gray-500">ساعات عمل</p>
            </div>
          </div>
        )}
      </div>

      {/* ── HR: daily summary ── */}
      {isHr && summary && (
        <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
          {[
            { label: 'حاضر', value: summary.present, color: 'text-green-600' },
            { label: 'متأخر', value: summary.late, color: 'text-amber-600' },
            { label: 'غائب', value: summary.absent, color: 'text-red-600' },
            { label: 'في إجازة', value: summary.on_leave, color: 'text-blue-600' },
            { label: 'مهمة عمل', value: summary.mission, color: 'text-purple-600' },
            { label: 'غير مسجل', value: summary.not_recorded, color: 'text-gray-500' },
          ].map((item) => (
            <div key={item.label} className="bg-white rounded-xl shadow-sm border border-gray-100 p-3 text-center">
              <p className={`text-xl font-bold ${item.color}`}>{item.value}</p>
              <p className="text-xs text-gray-500 mt-0.5">{item.label}</p>
            </div>
          ))}
        </div>
      )}

      {/* ── HR: manual record form ── */}
      {isHr && showForm && (
        <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-4 sm:p-6">
          <h2 className="text-base font-semibold text-gray-800 mb-4">تسجيل حضور يدوي</h2>
          <form onSubmit={handleSubmit} className="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">الموظف *</label>
              <select value={form.employee_id} onChange={(e) => setForm({ ...form, employee_id: e.target.value })} className="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-teal-500">
                <option value="">اختر الموظف</option>
                {employees.map((emp) => (
                  <option key={emp.id} value={emp.id}>
                    {emp.employee_number} — {emp.first_name_ar} {emp.last_name_ar}
                  </option>
                ))}
              </select>
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">التاريخ *</label>
              <input type="date" value={form.attendance_date} onChange={(e) => setForm({ ...form, attendance_date: e.target.value })} className="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-teal-500" />
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">الحالة *</label>
              <select value={form.status} onChange={(e) => setForm({ ...form, status: e.target.value })} className="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-teal-500">
                {Object.entries(STATUS_LABELS).map(([value, label]) => (
                  <option key={value} value={value}>{label}</option>
                ))}
              </select>
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">وقت الحضور</label>
              <input type="time" value={form.check_in_time} onChange={(e) => setForm({ ...form, check_in_time: e.target.value })} className="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-teal-500" />
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">وقت الانصراف</label>
              <input type="time" value={form.check_out_time} onChange={(e) => setForm({ ...form, check_out_time: e.target.value })} className="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-teal-500" />
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">ملاحظات</label>
              <input type="text" value={form.notes} onChange={(e) => setForm({ ...form, notes: e.target.value })} className="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-teal-500" />
            </div>
            <div className="sm:col-span-3 flex gap-2 justify-end">
              <button type="button" onClick={() => setShowForm(false)} className="px-4 py-2 text-sm text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50">إلغاء</button>
              <button type="submit" disabled={saving} className="px-4 py-2 text-sm bg-teal-600 text-white rounded-lg hover:bg-teal-700 disabled:opacity-50">
                {saving ? 'جاري الحفظ...' : 'تسجيل'}
              </button>
            </div>
          </form>
        </div>
      )}

      {/* ── HR: filters + records table ── */}
      {isHr ? (
        <>
          <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
            <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
              <input
                type="date"
                value={filter.date}
                onChange={(e) => setFilter({ ...filter, date: e.target.value })}
                className="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-teal-500"
              />
              <select
                value={filter.status}
                onChange={(e) => setFilter({ ...filter, status: e.target.value })}
                className="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-teal-500"
              >
                <option value="">جميع الحالات</option>
                {Object.entries(STATUS_LABELS).map(([value, label]) => (
                  <option key={value} value={value}>{label}</option>
                ))}
              </select>
              <select
                value={filter.department_id}
                onChange={(e) => setFilter({ ...filter, department_id: e.target.value })}
                className="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-teal-500"
              >
                <option value="">جميع الأقسام</option>
                {departments.map((d) => (
                  <option key={d.id} value={d.id}>{d.name_ar || d.name}</option>
                ))}
              </select>
            </div>
          </div>

          <div className="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            {loading ? (
              <div className="flex items-center justify-center py-16">
                <div className="w-8 h-8 border-4 border-teal-200 border-t-teal-600 rounded-full animate-spin" />
              </div>
            ) : records.length === 0 ? (
              <div className="text-center py-16 text-gray-500">لا توجد سجلات حضور لهذا اليوم</div>
            ) : (
              <div className="overflow-x-auto">
                <table className="w-full text-sm">
                  <thead className="bg-gray-50 text-gray-600">
                    <tr>
                      <th className="text-right px-4 py-3 font-medium">الموظف</th>
                      <th className="text-right px-4 py-3 font-medium hidden sm:table-cell">القسم</th>
                      <th className="text-right px-4 py-3 font-medium hidden sm:table-cell">الوردية</th>
                      <th className="text-center px-4 py-3 font-medium">الحضور</th>
                      <th className="text-center px-4 py-3 font-medium">الانصراف</th>
                      <th className="text-center px-4 py-3 font-medium hidden sm:table-cell">تأخير (د)</th>
                      <th className="text-center px-4 py-3 font-medium hidden sm:table-cell">إضافي (د)</th>
                      <th className="text-center px-4 py-3 font-medium">الحالة</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-gray-50">
                    {records.map((rec) => (
                      <tr key={rec.id} className="hover:bg-gray-50">
                        <td className="px-4 py-3">
                          <p className="font-medium text-gray-800">
                            {rec.employee?.first_name_ar} {rec.employee?.last_name_ar}
                          </p>
                          <p className="text-xs text-gray-500">{rec.employee?.employee_number}</p>
                        </td>
                        <td className="px-4 py-3 text-gray-600 hidden sm:table-cell">
                          {rec.employee?.department?.name_ar || '—'}
                        </td>
                        <td className="px-4 py-3 text-gray-600 hidden sm:table-cell">
                          {rec.shift?.name_ar || '—'}
                        </td>
                        <td className="px-4 py-3 text-center">{formatTime(rec.check_in_time)}</td>
                        <td className="px-4 py-3 text-center">{formatTime(rec.check_out_time)}</td>
                        <td className="px-4 py-3 text-center hidden sm:table-cell">
                          {rec.late_minutes > 0 ? <span className="text-amber-600 font-medium">{rec.late_minutes}</span> : '—'}
                        </td>
                        <td className="px-4 py-3 text-center hidden sm:table-cell">
                          {rec.overtime_minutes > 0 ? <span className="text-teal-600 font-medium">{rec.overtime_minutes}</span> : '—'}
                        </td>
                        <td className="px-4 py-3 text-center"><StatusBadge status={rec.status} /></td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            )}
          </div>
        </>
      ) : (
        /* ── Employee: own records ── */
        <div className="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
          <div className="px-4 py-3 border-b border-gray-100">
            <h2 className="text-sm font-semibold text-gray-800">سجلاتي هذا الشهر</h2>
          </div>
          {!myData ? (
            <div className="flex items-center justify-center py-16">
              <div className="w-8 h-8 border-4 border-teal-200 border-t-teal-600 rounded-full animate-spin" />
            </div>
          ) : (myData.records || []).length === 0 ? (
            <div className="text-center py-16 text-gray-500">لا توجد سجلات حضور</div>
          ) : (
            <div className="overflow-x-auto">
              <table className="w-full text-sm">
                <thead className="bg-gray-50 text-gray-600">
                  <tr>
                    <th className="text-right px-4 py-3 font-medium">التاريخ</th>
                    <th className="text-center px-4 py-3 font-medium">الحضور</th>
                    <th className="text-center px-4 py-3 font-medium">الانصراف</th>
                    <th className="text-center px-4 py-3 font-medium hidden sm:table-cell">تأخير (د)</th>
                    <th className="text-center px-4 py-3 font-medium">الحالة</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-gray-50">
                  {myData.records.map((rec) => (
                    <tr key={rec.id} className="hover:bg-gray-50">
                      <td className="px-4 py-3 text-gray-800">
                        {new Date(rec.attendance_date).toLocaleDateString('ar-SA')}
                      </td>
                      <td className="px-4 py-3 text-center">{formatTime(rec.check_in_time)}</td>
                      <td className="px-4 py-3 text-center">{formatTime(rec.check_out_time)}</td>
                      <td className="px-4 py-3 text-center hidden sm:table-cell">
                        {rec.late_minutes > 0 ? <span className="text-amber-600 font-medium">{rec.late_minutes}</span> : '—'}
                      </td>
                      <td className="px-4 py-3 text-center"><StatusBadge status={rec.status} /></td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}
        </div>
      )}
    </div>
  );
}
