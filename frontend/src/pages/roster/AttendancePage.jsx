import React, { useState, useEffect, useMemo } from 'react';
import { attendanceRecordsApi } from '../../services/rosterApi';
import { Button, LoadingSpinner, Modal, EmptyState } from '../../components/ui';

/**
 * صفحة الحضور والانصراف
 * Attendance Records Page - Track employee check-in/check-out
 */
export default function AttendancePage() {
  // State
  const [records, setRecords] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [selectedDate, setSelectedDate] = useState(new Date().toISOString().split('T')[0]);
  const [filterDepartment, setFilterDepartment] = useState('all');
  const [filterStatus, setFilterStatus] = useState('all');
  const [searchQuery, setSearchQuery] = useState('');

  // Modal states
  const [showManualEntryModal, setShowManualEntryModal] = useState(false);
  const [showCheckOutModal, setShowCheckOutModal] = useState(false);
  const [selectedRecord, setSelectedRecord] = useState(null);
  const [saving, setSaving] = useState(false);

  // Form state for manual entry
  const [manualEntry, setManualEntry] = useState({
    employee_id: '',
    date: new Date().toISOString().split('T')[0],
    check_in_time: '',
    check_out_time: '',
    notes: '',
    reason: ''
  });

  // Mock departments
  const departments = [
    { id: 1, name: 'التمريض', name_en: 'Nursing' },
    { id: 2, name: 'الأطباء', name_en: 'Doctors' },
    { id: 3, name: 'المختبر', name_en: 'Laboratory' },
    { id: 4, name: 'الاستقبال', name_en: 'Reception' },
    { id: 5, name: 'الصيدلية', name_en: 'Pharmacy' }
  ];

  // Mock employees
  const employees = [
    { id: 1, name: 'أحمد محمد', employee_number: 'EMP001', department_id: 1 },
    { id: 2, name: 'فاطمة علي', employee_number: 'EMP002', department_id: 1 },
    { id: 3, name: 'محمد خالد', employee_number: 'EMP003', department_id: 2 },
    { id: 4, name: 'سارة أحمد', employee_number: 'EMP004', department_id: 3 },
    { id: 5, name: 'عبدالله سعيد', employee_number: 'EMP005', department_id: 4 }
  ];

  // Mock attendance data
  const mockRecords = [
    {
      id: 1,
      employee_id: 1,
      employee_name: 'أحمد محمد',
      employee_number: 'EMP001',
      department: 'التمريض',
      date: selectedDate,
      scheduled_shift: 'صباحي - 9:00 - 12:00 + 17:00 - 22:00',
      check_in_time: '08:55',
      check_out_time: '22:05',
      status: 'present',
      is_late: false,
      late_minutes: 0,
      overtime_minutes: 5,
      source: 'biometric',
      notes: ''
    },
    {
      id: 2,
      employee_id: 2,
      employee_name: 'فاطمة علي',
      employee_number: 'EMP002',
      department: 'التمريض',
      date: selectedDate,
      scheduled_shift: 'مسائي - 16:00 - 00:00',
      check_in_time: '16:15',
      check_out_time: null,
      status: 'checked_in',
      is_late: true,
      late_minutes: 15,
      overtime_minutes: 0,
      source: 'biometric',
      notes: ''
    },
    {
      id: 3,
      employee_id: 3,
      employee_name: 'محمد خالد',
      employee_number: 'EMP003',
      department: 'الأطباء',
      date: selectedDate,
      scheduled_shift: 'صباحي - 9:00 - 17:00',
      check_in_time: null,
      check_out_time: null,
      status: 'absent',
      is_late: false,
      late_minutes: 0,
      overtime_minutes: 0,
      source: null,
      notes: 'غير مسجل'
    },
    {
      id: 4,
      employee_id: 4,
      employee_name: 'سارة أحمد',
      employee_number: 'EMP004',
      department: 'المختبر',
      date: selectedDate,
      scheduled_shift: 'ليلي - 00:00 - 09:00',
      check_in_time: '00:00',
      check_out_time: '09:00',
      status: 'present',
      is_late: false,
      late_minutes: 0,
      overtime_minutes: 0,
      source: 'biometric',
      notes: ''
    },
    {
      id: 5,
      employee_id: 5,
      employee_name: 'عبدالله سعيد',
      employee_number: 'EMP005',
      department: 'الاستقبال',
      date: selectedDate,
      scheduled_shift: 'راحة',
      check_in_time: null,
      check_out_time: null,
      status: 'day_off',
      is_late: false,
      late_minutes: 0,
      overtime_minutes: 0,
      source: null,
      notes: 'يوم راحة'
    }
  ];

  // Load attendance records
  useEffect(() => {
    loadRecords();
  }, [selectedDate]);

  const loadRecords = async () => {
    try {
      setLoading(true);
      setError(null);
      // const response = await attendanceRecordsApi.getByDate(selectedDate);
      // setRecords(response.data);

      // Using mock data
      setTimeout(() => {
        setRecords(mockRecords);
        setLoading(false);
      }, 500);
    } catch (err) {
      setError('فشل في تحميل سجلات الحضور');
      setLoading(false);
    }
  };

  // Filter records
  const filteredRecords = useMemo(() => {
    return records.filter(record => {
      const matchesDepartment = filterDepartment === 'all' ||
        departments.find(d => d.name === record.department)?.id.toString() === filterDepartment;
      const matchesStatus = filterStatus === 'all' || record.status === filterStatus;
      const matchesSearch = !searchQuery ||
        record.employee_name.toLowerCase().includes(searchQuery.toLowerCase()) ||
        record.employee_number.toLowerCase().includes(searchQuery.toLowerCase());

      return matchesDepartment && matchesStatus && matchesSearch;
    });
  }, [records, filterDepartment, filterStatus, searchQuery]);

  // Statistics
  const stats = useMemo(() => {
    const total = records.length;
    const present = records.filter(r => r.status === 'present').length;
    const checkedIn = records.filter(r => r.status === 'checked_in').length;
    const absent = records.filter(r => r.status === 'absent').length;
    const late = records.filter(r => r.is_late).length;
    const dayOff = records.filter(r => r.status === 'day_off').length;

    return { total, present, checkedIn, absent, late, dayOff };
  }, [records]);

  // Handle manual entry
  const handleManualEntry = async (e) => {
    e.preventDefault();
    try {
      setSaving(true);
      // await attendanceRecordsApi.manualEntry(manualEntry);

      // Mock save
      setTimeout(() => {
        const newRecord = {
          id: records.length + 1,
          employee_id: parseInt(manualEntry.employee_id),
          employee_name: employees.find(e => e.id === parseInt(manualEntry.employee_id))?.name || '',
          employee_number: employees.find(e => e.id === parseInt(manualEntry.employee_id))?.employee_number || '',
          department: departments.find(d => d.id === employees.find(e => e.id === parseInt(manualEntry.employee_id))?.department_id)?.name || '',
          date: manualEntry.date,
          scheduled_shift: 'يدوي',
          check_in_time: manualEntry.check_in_time,
          check_out_time: manualEntry.check_out_time,
          status: manualEntry.check_out_time ? 'present' : 'checked_in',
          is_late: false,
          late_minutes: 0,
          overtime_minutes: 0,
          source: 'manual',
          notes: manualEntry.notes,
          pending_approval: true
        };
        setRecords([...records, newRecord]);
        setShowManualEntryModal(false);
        setManualEntry({
          employee_id: '',
          date: new Date().toISOString().split('T')[0],
          check_in_time: '',
          check_out_time: '',
          notes: '',
          reason: ''
        });
        setSaving(false);
      }, 500);
    } catch (err) {
      setError('فشل في إضافة السجل');
      setSaving(false);
    }
  };

  // Handle check out
  const handleCheckOut = async () => {
    if (!selectedRecord) return;

    try {
      setSaving(true);
      const currentTime = new Date().toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit' });
      // await attendanceRecordsApi.checkOut(selectedRecord.id, { check_out_time: currentTime });

      // Mock update
      setTimeout(() => {
        setRecords(records.map(r =>
          r.id === selectedRecord.id
            ? { ...r, check_out_time: currentTime, status: 'present' }
            : r
        ));
        setShowCheckOutModal(false);
        setSelectedRecord(null);
        setSaving(false);
      }, 500);
    } catch (err) {
      setError('فشل في تسجيل الانصراف');
      setSaving(false);
    }
  };

  // Get status badge
  const getStatusBadge = (status, isLate) => {
    const statusConfig = {
      present: { label: 'حاضر', color: 'bg-green-100 text-green-800' },
      checked_in: { label: 'مسجل دخول', color: 'bg-blue-100 text-blue-800' },
      absent: { label: 'غائب', color: 'bg-red-100 text-red-800' },
      day_off: { label: 'يوم راحة', color: 'bg-gray-100 text-gray-800' },
      leave: { label: 'إجازة', color: 'bg-purple-100 text-purple-800' }
    };

    const config = statusConfig[status] || { label: status, color: 'bg-gray-100 text-gray-800' };

    return (
      <div className="flex items-center gap-2">
        <span className={`px-2 py-1 text-xs font-medium rounded-full ${config.color}`}>
          {config.label}
        </span>
        {isLate && (
          <span className="px-2 py-1 text-xs font-medium rounded-full bg-orange-100 text-orange-800">
            متأخر
          </span>
        )}
      </div>
    );
  };

  // Get source badge
  const getSourceBadge = (source) => {
    const sourceConfig = {
      biometric: { label: 'بصمة', icon: '👆' },
      manual: { label: 'يدوي', icon: '✍️' },
      system: { label: 'نظام', icon: '🖥️' }
    };

    const config = sourceConfig[source] || { label: source || '-', icon: '' };

    return (
      <span className="text-xs text-gray-500">
        {config.icon} {config.label}
      </span>
    );
  };

  // Navigate date
  const navigateDate = (direction) => {
    const date = new Date(selectedDate);
    date.setDate(date.getDate() + direction);
    setSelectedDate(date.toISOString().split('T')[0]);
  };

  if (loading && records.length === 0) {
    return (
      <div className="flex items-center justify-center min-h-[400px]">
        <LoadingSpinner size="lg" />
      </div>
    );
  }

  return (
    <div className="space-y-6" dir="rtl">
      {/* Header */}
      <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">الحضور والانصراف</h1>
          <p className="text-gray-600 mt-1">متابعة تسجيل الدخول والخروج للموظفين</p>
        </div>
        <div className="flex gap-2">
          <Button
            variant="secondary"
            onClick={() => {/* Export functionality */}}
          >
            📥 تصدير
          </Button>
          <Button onClick={() => setShowManualEntryModal(true)}>
            ✍️ إدخال يدوي
          </Button>
        </div>
      </div>

      {/* Statistics Cards */}
      <div className="grid grid-cols-2 md:grid-cols-6 gap-4">
        <div className="bg-white rounded-lg shadow p-4 text-center">
          <div className="text-3xl font-bold text-gray-900">{stats.total}</div>
          <div className="text-sm text-gray-600">إجمالي</div>
        </div>
        <div className="bg-white rounded-lg shadow p-4 text-center">
          <div className="text-3xl font-bold text-green-600">{stats.present}</div>
          <div className="text-sm text-gray-600">حاضر</div>
        </div>
        <div className="bg-white rounded-lg shadow p-4 text-center">
          <div className="text-3xl font-bold text-blue-600">{stats.checkedIn}</div>
          <div className="text-sm text-gray-600">مسجل دخول</div>
        </div>
        <div className="bg-white rounded-lg shadow p-4 text-center">
          <div className="text-3xl font-bold text-red-600">{stats.absent}</div>
          <div className="text-sm text-gray-600">غائب</div>
        </div>
        <div className="bg-white rounded-lg shadow p-4 text-center">
          <div className="text-3xl font-bold text-orange-600">{stats.late}</div>
          <div className="text-sm text-gray-600">متأخر</div>
        </div>
        <div className="bg-white rounded-lg shadow p-4 text-center">
          <div className="text-3xl font-bold text-gray-600">{stats.dayOff}</div>
          <div className="text-sm text-gray-600">راحة</div>
        </div>
      </div>

      {/* Date Navigation & Filters */}
      <div className="bg-white rounded-lg shadow p-4">
        <div className="flex flex-col md:flex-row gap-4">
          {/* Date Navigation */}
          <div className="flex items-center gap-2">
            <Button variant="secondary" size="sm" onClick={() => navigateDate(-1)}>
              ▶
            </Button>
            <input
              type="date"
              value={selectedDate}
              onChange={(e) => setSelectedDate(e.target.value)}
              className="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            />
            <Button variant="secondary" size="sm" onClick={() => navigateDate(1)}>
              ◀
            </Button>
            <Button
              variant="ghost"
              size="sm"
              onClick={() => setSelectedDate(new Date().toISOString().split('T')[0])}
            >
              اليوم
            </Button>
          </div>

          {/* Filters */}
          <div className="flex flex-1 gap-4">
            <input
              type="text"
              placeholder="بحث بالاسم أو الرقم..."
              value={searchQuery}
              onChange={(e) => setSearchQuery(e.target.value)}
              className="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            />
            <select
              value={filterDepartment}
              onChange={(e) => setFilterDepartment(e.target.value)}
              className="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            >
              <option value="all">جميع الأقسام</option>
              {departments.map(dept => (
                <option key={dept.id} value={dept.id}>{dept.name}</option>
              ))}
            </select>
            <select
              value={filterStatus}
              onChange={(e) => setFilterStatus(e.target.value)}
              className="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            >
              <option value="all">جميع الحالات</option>
              <option value="present">حاضر</option>
              <option value="checked_in">مسجل دخول</option>
              <option value="absent">غائب</option>
              <option value="day_off">راحة</option>
            </select>
          </div>
        </div>
      </div>

      {/* Error Message */}
      {error && (
        <div className="bg-red-50 text-red-600 p-4 rounded-lg">
          {error}
        </div>
      )}

      {/* Attendance Table */}
      <div className="bg-white rounded-lg shadow overflow-hidden">
        {filteredRecords.length === 0 ? (
          <EmptyState
            title="لا توجد سجلات"
            description="لا توجد سجلات حضور لهذا اليوم"
            icon="📋"
          />
        ) : (
          <div className="overflow-x-auto">
            <table className="min-w-full divide-y divide-gray-200">
              <thead className="bg-gray-50">
                <tr>
                  <th className="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">الموظف</th>
                  <th className="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">القسم</th>
                  <th className="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">الوردية المجدولة</th>
                  <th className="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">تسجيل الدخول</th>
                  <th className="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">تسجيل الخروج</th>
                  <th className="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">الحالة</th>
                  <th className="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">المصدر</th>
                  <th className="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">تأخير/إضافي</th>
                  <th className="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">إجراءات</th>
                </tr>
              </thead>
              <tbody className="bg-white divide-y divide-gray-200">
                {filteredRecords.map((record) => (
                  <tr key={record.id} className="hover:bg-gray-50">
                    <td className="px-4 py-4 whitespace-nowrap">
                      <div>
                        <div className="font-medium text-gray-900">{record.employee_name}</div>
                        <div className="text-sm text-gray-500">{record.employee_number}</div>
                      </div>
                    </td>
                    <td className="px-4 py-4 whitespace-nowrap text-sm text-gray-600">
                      {record.department}
                    </td>
                    <td className="px-4 py-4 whitespace-nowrap text-sm text-gray-600">
                      {record.scheduled_shift}
                    </td>
                    <td className="px-4 py-4 whitespace-nowrap text-center">
                      {record.check_in_time ? (
                        <span className="text-green-600 font-medium">{record.check_in_time}</span>
                      ) : (
                        <span className="text-gray-400">--:--</span>
                      )}
                    </td>
                    <td className="px-4 py-4 whitespace-nowrap text-center">
                      {record.check_out_time ? (
                        <span className="text-red-600 font-medium">{record.check_out_time}</span>
                      ) : (
                        <span className="text-gray-400">--:--</span>
                      )}
                    </td>
                    <td className="px-4 py-4 whitespace-nowrap text-center">
                      {getStatusBadge(record.status, record.is_late)}
                    </td>
                    <td className="px-4 py-4 whitespace-nowrap text-center">
                      {getSourceBadge(record.source)}
                    </td>
                    <td className="px-4 py-4 whitespace-nowrap text-center">
                      <div className="flex flex-col items-center text-xs">
                        {record.late_minutes > 0 && (
                          <span className="text-orange-600">تأخير: {record.late_minutes} د</span>
                        )}
                        {record.overtime_minutes > 0 && (
                          <span className="text-blue-600">إضافي: {record.overtime_minutes} د</span>
                        )}
                        {record.late_minutes === 0 && record.overtime_minutes === 0 && (
                          <span className="text-gray-400">-</span>
                        )}
                      </div>
                    </td>
                    <td className="px-4 py-4 whitespace-nowrap text-center">
                      <div className="flex justify-center gap-1">
                        {record.status === 'checked_in' && !record.check_out_time && (
                          <Button
                            variant="secondary"
                            size="sm"
                            onClick={() => {
                              setSelectedRecord(record);
                              setShowCheckOutModal(true);
                            }}
                          >
                            تسجيل خروج
                          </Button>
                        )}
                        {record.pending_approval && (
                          <span className="px-2 py-1 text-xs bg-yellow-100 text-yellow-800 rounded-full">
                            بانتظار الموافقة
                          </span>
                        )}
                      </div>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </div>

      {/* Manual Entry Modal */}
      <Modal
        isOpen={showManualEntryModal}
        onClose={() => setShowManualEntryModal(false)}
        title="إدخال يدوي للحضور"
        size="md"
      >
        <form onSubmit={handleManualEntry} className="space-y-4">
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
              الموظف <span className="text-red-500">*</span>
            </label>
            <select
              value={manualEntry.employee_id}
              onChange={(e) => setManualEntry({ ...manualEntry, employee_id: e.target.value })}
              className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              required
            >
              <option value="">اختر الموظف</option>
              {employees.map(emp => (
                <option key={emp.id} value={emp.id}>
                  {emp.name} ({emp.employee_number})
                </option>
              ))}
            </select>
          </div>

          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
              التاريخ <span className="text-red-500">*</span>
            </label>
            <input
              type="date"
              value={manualEntry.date}
              onChange={(e) => setManualEntry({ ...manualEntry, date: e.target.value })}
              className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              required
            />
          </div>

          <div className="grid grid-cols-2 gap-4">
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">
                وقت الدخول <span className="text-red-500">*</span>
              </label>
              <input
                type="time"
                value={manualEntry.check_in_time}
                onChange={(e) => setManualEntry({ ...manualEntry, check_in_time: e.target.value })}
                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                required
              />
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">
                وقت الخروج
              </label>
              <input
                type="time"
                value={manualEntry.check_out_time}
                onChange={(e) => setManualEntry({ ...manualEntry, check_out_time: e.target.value })}
                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              />
            </div>
          </div>

          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
              سبب الإدخال اليدوي <span className="text-red-500">*</span>
            </label>
            <select
              value={manualEntry.reason}
              onChange={(e) => setManualEntry({ ...manualEntry, reason: e.target.value })}
              className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              required
            >
              <option value="">اختر السبب</option>
              <option value="device_failure">عطل جهاز البصمة</option>
              <option value="forgot_badge">نسيان البطاقة</option>
              <option value="fingerprint_issue">مشكلة في البصمة</option>
              <option value="system_error">خطأ في النظام</option>
              <option value="other">سبب آخر</option>
            </select>
          </div>

          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
              ملاحظات
            </label>
            <textarea
              value={manualEntry.notes}
              onChange={(e) => setManualEntry({ ...manualEntry, notes: e.target.value })}
              rows={3}
              className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              placeholder="أي ملاحظات إضافية..."
            />
          </div>

          <div className="bg-yellow-50 p-3 rounded-lg text-sm text-yellow-800">
            ⚠️ الإدخال اليدوي يتطلب موافقة المشرف
          </div>

          <div className="flex justify-end gap-3 pt-4">
            <Button
              type="button"
              variant="secondary"
              onClick={() => setShowManualEntryModal(false)}
            >
              إلغاء
            </Button>
            <Button type="submit" disabled={saving}>
              {saving ? 'جاري الحفظ...' : 'حفظ'}
            </Button>
          </div>
        </form>
      </Modal>

      {/* Check Out Modal */}
      <Modal
        isOpen={showCheckOutModal}
        onClose={() => setShowCheckOutModal(false)}
        title="تسجيل الخروج"
        size="sm"
      >
        <div className="space-y-4">
          <p className="text-gray-600">
            هل تريد تسجيل خروج <strong>{selectedRecord?.employee_name}</strong>؟
          </p>
          <p className="text-sm text-gray-500">
            سيتم تسجيل وقت الخروج: {new Date().toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit' })}
          </p>

          <div className="flex justify-end gap-3 pt-4">
            <Button
              variant="secondary"
              onClick={() => setShowCheckOutModal(false)}
            >
              إلغاء
            </Button>
            <Button onClick={handleCheckOut} disabled={saving}>
              {saving ? 'جاري التسجيل...' : 'تأكيد الخروج'}
            </Button>
          </div>
        </div>
      </Modal>
    </div>
  );
}
