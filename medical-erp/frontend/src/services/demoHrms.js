/**
 * Demo HRMS Data Service
 * بيانات تجريبية تفاعلية لوحدة الحضور والورديات — تعمل بالكامل في المتصفح
 * Interactive in-browser mock for attendance & shifts when backend is unreachable
 */
import { getDemoUser } from './demoAuth';

const LS_KEY = 'demo_hrms_v1';

// ─── Demo departments ───
export const DEMO_DEPARTMENTS = [
  { id: 'demo-dept-nurs', name: 'Nursing', name_ar: 'التمريض', is_active: true },
  { id: 'demo-dept-med', name: 'Doctors', name_ar: 'الأطباء', is_active: true },
  { id: 'demo-dept-lab', name: 'Laboratory', name_ar: 'المختبر', is_active: true },
  { id: 'demo-dept-hr', name: 'Human Resources', name_ar: 'الموارد البشرية', is_active: true },
];

// ─── Demo staff (roster-style names) ───
export const DEMO_STAFF = [
  { id: 'demo-emp-1001', employee_number: '1001', first_name: 'Abdullah', first_name_ar: 'عبدالله', last_name: 'Al-Rashid', last_name_ar: 'الراشد', department_id: 'demo-dept-hr', department: DEMO_DEPARTMENTS[3], status: 'active' },
  { id: 'demo-emp-1002', employee_number: '1002', first_name: 'Nora', first_name_ar: 'نورة', last_name: 'Al-Fahd', last_name_ar: 'الفهد', department_id: 'demo-dept-hr', department: DEMO_DEPARTMENTS[3], status: 'active' },
  { id: 'demo-emp-2001', employee_number: '2001', first_name: 'Khalid', first_name_ar: 'خالد', last_name: 'Al-Otaibi', last_name_ar: 'العتيبي', department_id: 'demo-dept-med', department: DEMO_DEPARTMENTS[1], status: 'active' },
  { id: 'demo-emp-3001', employee_number: '3001', first_name: 'Maria', first_name_ar: 'ماريا', last_name: 'Santos', last_name_ar: 'سانتوس', department_id: 'demo-dept-nurs', department: DEMO_DEPARTMENTS[0], status: 'active' },
  { id: 'demo-emp-3002', employee_number: '3002', first_name: 'Marwa', first_name_ar: 'مروة', last_name: 'Essam', last_name_ar: 'عصام', department_id: 'demo-dept-nurs', department: DEMO_DEPARTMENTS[0], status: 'active' },
  { id: 'demo-emp-3003', employee_number: '3003', first_name: 'Yassmen', first_name_ar: 'ياسمين', last_name: 'Ali', last_name_ar: 'علي', department_id: 'demo-dept-nurs', department: DEMO_DEPARTMENTS[0], status: 'active' },
  { id: 'demo-emp-2002', employee_number: '2002', first_name: 'Ahmed', first_name_ar: 'أحمد', last_name: 'Samir', last_name_ar: 'سمير', department_id: 'demo-dept-med', department: DEMO_DEPARTMENTS[1], status: 'active' },
  { id: 'demo-emp-4001', employee_number: '4001', first_name: 'Zakaria', first_name_ar: 'زكريا', last_name: 'Hassan', last_name_ar: 'حسن', department_id: 'demo-dept-lab', department: DEMO_DEPARTMENTS[2], status: 'active' },
];

const DEFAULT_SHIFTS = [
  { id: 'demo-shift-admin', code: 'SH-ADMIN', name: 'Administrative Shift', name_ar: 'الوردية الإدارية', start_time: '08:00:00', end_time: '17:00:00', break_minutes: 60, grace_period_minutes: 15, is_overnight: false, is_active: true, description: 'وردية الموظفين الإداريين' },
  { id: 'demo-shift-morning', code: 'SH-MORNING', name: 'Morning Shift', name_ar: 'الوردية الصباحية', start_time: '08:00:00', end_time: '16:00:00', break_minutes: 30, grace_period_minutes: 10, is_overnight: false, is_active: true, description: 'الوردية الصباحية للكوادر الطبية' },
  { id: 'demo-shift-evening', code: 'SH-EVENING', name: 'Evening Shift', name_ar: 'الوردية المسائية', start_time: '16:00:00', end_time: '00:00:00', break_minutes: 30, grace_period_minutes: 10, is_overnight: true, is_active: true, description: 'الوردية المسائية للكوادر الطبية' },
  { id: 'demo-shift-night', code: 'SH-NIGHT', name: 'Night Shift', name_ar: 'الوردية الليلية', start_time: '00:00:00', end_time: '08:00:00', break_minutes: 30, grace_period_minutes: 10, is_overnight: false, is_active: true, description: 'الوردية الليلية للكوادر الطبية' },
];

const DEFAULT_ASSIGNMENTS = DEMO_STAFF.map((emp, i) => ({
  id: `demo-assign-${emp.id}`,
  employee_id: emp.id,
  shift_id: ['demo-dept-hr'].includes(emp.department_id)
    ? 'demo-shift-admin'
    : ['demo-shift-morning', 'demo-shift-evening'][i % 2],
  effective_from: '2026-01-01',
  effective_to: null,
}));

// ─── Store (localStorage) ───

function loadStore() {
  try {
    const raw = localStorage.getItem(LS_KEY);
    if (raw) return JSON.parse(raw);
  } catch { /* corrupted — reseed */ }
  const store = { shifts: DEFAULT_SHIFTS, assignments: DEFAULT_ASSIGNMENTS, myRecords: {} };
  saveStore(store);
  return store;
}

function saveStore(store) {
  try { localStorage.setItem(LS_KEY, JSON.stringify(store)); } catch { /* full — ignore */ }
}

// ─── Helpers ───

const ok = (data, message = 'تم بنجاح (وضع تجريبي)') =>
  Promise.resolve({ data: { success: true, message, data } });

const fail = (message, status = 422) =>
  Promise.reject({ response: { status, data: { success: false, message } } });

const todayStr = () => new Date().toISOString().slice(0, 10);

function minutesFromMidnight(timeStr) {
  const [h, m] = timeStr.split(':').map(Number);
  return h * 60 + m;
}

function nowMinutes() {
  const d = new Date();
  return d.getHours() * 60 + d.getMinutes();
}

function currentEmployee() {
  const user = getDemoUser();
  return user ? DEMO_STAFF.find((e) => e.id === user.employee?.id) || DEMO_STAFF[0] : DEMO_STAFF[0];
}

function shiftOf(store, employeeId) {
  const assignment = store.assignments.find((a) => a.employee_id === employeeId && !a.effective_to);
  return store.shifts.find((s) => s.id === assignment?.shift_id) || null;
}

function withRelations(store, assignment) {
  return {
    ...assignment,
    employee: DEMO_STAFF.find((e) => e.id === assignment.employee_id) || null,
    shift: store.shifts.find((s) => s.id === assignment.shift_id) || null,
  };
}

// سجلات ثابتة لبقية الطاقم — تُولَّد لأي يوم لعرض لوحة الموارد البشرية
const STAFF_DAY_PATTERN = {
  'demo-emp-1001': { status: 'present', in: '07:55', out: '17:05' },
  'demo-emp-3001': { status: 'present', in: '07:58', out: '16:02' },
  'demo-emp-3002': { status: 'late', in: '08:24', out: '17:10', late: 24, overtime: 70 },
  'demo-emp-3003': { status: 'on_leave' },
  'demo-emp-2001': { status: 'present', in: '08:03', out: '16:00' },
  'demo-emp-2002': { status: 'present', in: '07:50', out: '16:30', overtime: 30 },
  'demo-emp-4001': { status: 'absent' },
};

function staffRecordsFor(store, date) {
  const records = [];
  for (const emp of DEMO_STAFF) {
    const me = currentEmployee();
    if (emp.id === me.id) continue; // سجل المستخدم الحالي يأتي من تفاعله الفعلي
    const p = STAFF_DAY_PATTERN[emp.employee_number ? emp.id : emp.id];
    if (!p) continue;
    const shift = shiftOf(store, emp.id);
    records.push({
      id: `demo-rec-${emp.id}-${date}`,
      employee_id: emp.id,
      employee: emp,
      shift_id: shift?.id || null,
      shift,
      attendance_date: date,
      check_in_time: p.in ? `${date}T${p.in}:00` : null,
      check_out_time: p.out ? `${date}T${p.out}:00` : null,
      status: p.status,
      late_minutes: p.late || 0,
      early_leave_minutes: 0,
      overtime_minutes: p.overtime || 0,
      worked_minutes: p.in && p.out ? 480 : 0,
      source: 'manual',
      notes: null,
    });
  }
  return records;
}

function myRecordsList(store) {
  return Object.values(store.myRecords).sort((a, b) => b.attendance_date.localeCompare(a.attendance_date));
}

function summarize(records) {
  return {
    days_present: records.filter((r) => ['present', 'late'].includes(r.status)).length,
    days_late: records.filter((r) => r.status === 'late').length,
    days_absent: records.filter((r) => r.status === 'absent').length,
    days_on_leave: records.filter((r) => r.status === 'on_leave').length,
    total_late_minutes: records.reduce((s, r) => s + (r.late_minutes || 0), 0),
    total_overtime_minutes: records.reduce((s, r) => s + (r.overtime_minutes || 0), 0),
    total_worked_minutes: records.reduce((s, r) => s + (r.worked_minutes || 0), 0),
  };
}

// ─── Attendance API (demo) ───

export const demoAttendance = {
  checkIn() {
    const store = loadStore();
    const me = currentEmployee();
    const date = todayStr();
    const existing = store.myRecords[date];
    if (existing?.check_in_time) {
      return fail('تم تسجيل حضورك مسبقاً لهذا اليوم');
    }
    const shift = shiftOf(store, me.id);
    let status = 'present';
    let lateMinutes = 0;
    if (shift) {
      const past = nowMinutes() - minutesFromMidnight(shift.start_time);
      if (past > shift.grace_period_minutes) {
        status = 'late';
        lateMinutes = past;
      }
    }
    const now = new Date();
    const record = {
      id: `demo-rec-me-${date}`,
      employee_id: me.id,
      employee: me,
      shift_id: shift?.id || null,
      shift,
      attendance_date: date,
      check_in_time: now.toISOString(),
      check_out_time: null,
      status,
      late_minutes: lateMinutes,
      early_leave_minutes: 0,
      overtime_minutes: 0,
      worked_minutes: 0,
      source: 'self',
      notes: null,
    };
    store.myRecords[date] = record;
    saveStore(store);
    const message = status === 'late'
      ? `تم تسجيل الحضور — متأخر ${lateMinutes} دقيقة (وضع تجريبي)`
      : 'تم تسجيل الحضور بنجاح (وضع تجريبي)';
    return ok(record, message);
  },

  checkOut() {
    const store = loadStore();
    const date = todayStr();
    const record = store.myRecords[date];
    if (!record?.check_in_time || record.check_out_time) {
      return fail('لا يوجد تسجيل حضور مفتوح — يجب تسجيل الحضور أولاً');
    }
    const now = new Date();
    record.check_out_time = now.toISOString();
    record.worked_minutes = Math.max(0, Math.round((now - new Date(record.check_in_time)) / 60000));
    if (record.shift) {
      let end = minutesFromMidnight(record.shift.end_time);
      if (record.shift.is_overnight || end <= minutesFromMidnight(record.shift.start_time)) end += 24 * 60;
      const diff = nowMinutes() - end;
      record.overtime_minutes = Math.max(0, diff);
      record.early_leave_minutes = Math.max(0, -diff);
    }
    saveStore(store);
    return ok(record, 'تم تسجيل الانصراف بنجاح (وضع تجريبي)');
  },

  getMy() {
    const store = loadStore();
    const me = currentEmployee();
    const records = myRecordsList(store);
    return ok({
      records,
      today: store.myRecords[todayStr()] || null,
      current_shift: shiftOf(store, me.id),
      summary: summarize(records),
    });
  },

  getAll(params = {}) {
    const store = loadStore();
    const date = params.date || todayStr();
    let records = [...staffRecordsFor(store, date)];
    const mine = store.myRecords[date];
    if (mine) records.unshift(mine);
    if (params.status) records = records.filter((r) => r.status === params.status);
    if (params.department_id) records = records.filter((r) => r.employee?.department_id === params.department_id);
    return ok({ data: records, total: records.length });
  },

  create(data) {
    const emp = DEMO_STAFF.find((e) => e.id === data.employee_id);
    return ok({
      ...data,
      id: `demo-rec-manual-${Date.now()}`,
      employee: emp || null,
      source: 'manual',
    }, 'تم تسجيل الحضور (وضع تجريبي — لن يُحفظ)');
  },

  update(id, data) {
    return ok({ id, ...data }, 'تم التحديث (وضع تجريبي — لن يُحفظ)');
  },

  getDailySummary(params = {}) {
    const store = loadStore();
    const date = params.date || todayStr();
    const records = [...staffRecordsFor(store, date)];
    const mine = store.myRecords[date];
    if (mine) records.push(mine);
    const count = (s) => records.filter((r) => r.status === s).length;
    return ok({
      date,
      active_employees: DEMO_STAFF.length,
      recorded: records.length,
      not_recorded: Math.max(0, DEMO_STAFF.length - records.length),
      present: count('present'),
      late: count('late'),
      absent: count('absent'),
      on_leave: count('on_leave'),
      holiday: count('holiday'),
      mission: count('mission'),
    });
  },

  getMonthlyReport(params = {}) {
    const store = loadStore();
    const records = myRecordsList(store);
    const me = currentEmployee();
    return ok({
      year: params.year || new Date().getFullYear(),
      month: params.month || new Date().getMonth() + 1,
      report: [{ employee_id: me.id, employee: me, ...summarize(records) }],
    });
  },
};

// ─── Shifts API (demo) ───

export const demoShifts = {
  getAll() {
    const store = loadStore();
    const shifts = store.shifts.map((s) => ({
      ...s,
      assignments_count: store.assignments.filter((a) => a.shift_id === s.id && !a.effective_to).length,
    }));
    return ok({ data: shifts, total: shifts.length });
  },

  getById(id) {
    const store = loadStore();
    const shift = store.shifts.find((s) => s.id === id);
    if (!shift) return fail('الوردية غير موجودة', 404);
    return ok({
      ...shift,
      assignments: store.assignments.filter((a) => a.shift_id === id && !a.effective_to).map((a) => withRelations(store, a)),
    });
  },

  create(data) {
    const store = loadStore();
    const shift = {
      id: `demo-shift-${Date.now()}`,
      is_active: true,
      is_overnight: false,
      break_minutes: 0,
      grace_period_minutes: 15,
      ...data,
      start_time: (data.start_time || '08:00') + ':00',
      end_time: (data.end_time || '16:00') + ':00',
    };
    store.shifts.push(shift);
    saveStore(store);
    return ok(shift, 'تم إنشاء الوردية (وضع تجريبي)');
  },

  update(id, data) {
    const store = loadStore();
    const shift = store.shifts.find((s) => s.id === id);
    if (!shift) return fail('الوردية غير موجودة', 404);
    Object.assign(shift, data);
    if (data.start_time && data.start_time.length === 5) shift.start_time = data.start_time + ':00';
    if (data.end_time && data.end_time.length === 5) shift.end_time = data.end_time + ':00';
    saveStore(store);
    return ok(shift, 'تم تحديث الوردية (وضع تجريبي)');
  },

  delete(id) {
    const store = loadStore();
    if (store.assignments.some((a) => a.shift_id === id)) {
      return fail('لا يمكن حذف الوردية لوجود إسنادات مرتبطة بها');
    }
    store.shifts = store.shifts.filter((s) => s.id !== id);
    saveStore(store);
    return ok(null, 'تم حذف الوردية (وضع تجريبي)');
  },

  assign(id, data) {
    const store = loadStore();
    for (const empId of data.employee_ids || []) {
      store.assignments = store.assignments.filter((a) => a.employee_id !== empId);
      store.assignments.push({
        id: `demo-assign-${empId}-${Date.now()}`,
        employee_id: empId,
        shift_id: id,
        effective_from: data.effective_from || todayStr(),
        effective_to: null,
      });
    }
    saveStore(store);
    return ok(null, 'تم إسناد الوردية للموظفين (وضع تجريبي)');
  },

  getAssignments() {
    const store = loadStore();
    return ok({
      data: store.assignments.filter((a) => !a.effective_to).map((a) => withRelations(store, a)),
    });
  },

  unassign(assignmentId) {
    const store = loadStore();
    store.assignments = store.assignments.filter((a) => a.id !== assignmentId);
    saveStore(store);
    return ok(null, 'تم إلغاء الإسناد (وضع تجريبي)');
  },
};
