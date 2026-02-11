import React, { useState, useEffect, useCallback } from 'react';
import toast from 'react-hot-toast';
import {
  rostersApi,
  rosterAssignmentsApi,
  shiftPatternsApi,
  onCallAssignmentsApi,
} from '../../services/rosterApi';
import { departmentsApi, employeesApi } from '../../services/hrApi';
import {
  Button,
  Input,
  Select,
  Modal,
  Card,
  CardHeader,
  Badge,
} from '../../components/ui';
import {
  HiPlus,
  HiPencil,
  HiCalendar,
  HiRefresh,
  HiChevronRight,
  HiChevronLeft,
  HiDownload,
  HiDuplicate,
  HiCheck,
  HiX,
  HiUserGroup,
  HiPhone,
} from 'react-icons/hi';

/**
 * صفحة الجداول الأسبوعية
 * Weekly Rosters Management Page
 */
export default function RostersPage() {
  const [roster, setRoster] = useState(null);
  const [assignments, setAssignments] = useState([]);
  const [employees, setEmployees] = useState([]);
  const [shiftPatterns, setShiftPatterns] = useState([]);
  const [departments, setDepartments] = useState([]);
  const [onCallAssignments, setOnCallAssignments] = useState([]);
  const [loading, setLoading] = useState(true);
  const [departmentFilter, setDepartmentFilter] = useState('');
  const [currentWeekStart, setCurrentWeekStart] = useState(getWeekStart(new Date()));
  const [showAssignModal, setShowAssignModal] = useState(false);
  const [showOnCallModal, setShowOnCallModal] = useState(false);
  const [selectedCell, setSelectedCell] = useState(null);
  const [formData, setFormData] = useState({
    shift_pattern_id: '',
    notes: '',
  });
  const [onCallData, setOnCallData] = useState({
    employee_id: '',
    day: '',
    notes: '',
  });
  const [submitting, setSubmitting] = useState(false);

  // Days of the week (Saturday to Friday - Saudi week)
  const weekDays = [
    { key: 'saturday', label: 'السبت', labelEn: 'Sat' },
    { key: 'sunday', label: 'الأحد', labelEn: 'Sun' },
    { key: 'monday', label: 'الاثنين', labelEn: 'Mon' },
    { key: 'tuesday', label: 'الثلاثاء', labelEn: 'Tue' },
    { key: 'wednesday', label: 'الأربعاء', labelEn: 'Wed' },
    { key: 'thursday', label: 'الخميس', labelEn: 'Thu' },
    { key: 'friday', label: 'الجمعة', labelEn: 'Fri' },
  ];

  // Get week start (Saturday)
  function getWeekStart(date) {
    const d = new Date(date);
    const day = d.getDay();
    const diff = d.getDate() - day - (day === 0 ? 6 : -1); // Adjust for Saturday start
    const saturday = day === 6 ? d.getDate() : diff - 1;
    d.setDate(saturday);
    d.setHours(0, 0, 0, 0);
    return d;
  }

  // Get week end (Friday)
  function getWeekEnd(startDate) {
    const d = new Date(startDate);
    d.setDate(d.getDate() + 6);
    return d;
  }

  // Format date
  const formatDate = (date) => {
    return new Date(date).toLocaleDateString('ar-SA', {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
    });
  };

  // Format date for API
  const formatDateForApi = (date) => {
    const d = new Date(date);
    return d.toISOString().split('T')[0];
  };

  // Get date for specific day of week
  const getDateForDay = (dayIndex) => {
    const d = new Date(currentWeekStart);
    d.setDate(d.getDate() + dayIndex);
    return d;
  };

  // Load departments
  const loadDepartments = useCallback(async () => {
    try {
      const response = await departmentsApi.getActive();
      setDepartments(response.data?.data || response.data || []);
    } catch (error) {
      console.error('Error loading departments:', error);
    }
  }, []);

  // Load employees
  const loadEmployees = useCallback(async () => {
    try {
      const params = departmentFilter ? { department_id: departmentFilter } : {};
      const response = await employeesApi.getActive(params);
      setEmployees(response.data?.data || response.data || []);
    } catch (error) {
      console.error('Error loading employees:', error);
    }
  }, [departmentFilter]);

  // Load shift patterns
  const loadShiftPatterns = useCallback(async () => {
    try {
      const response = await shiftPatternsApi.getActive();
      setShiftPatterns(response.data?.data || response.data || []);
    } catch (error) {
      console.error('Error loading shift patterns:', error);
    }
  }, []);

  // Load roster and assignments
  const loadRoster = useCallback(async () => {
    try {
      setLoading(true);
      const startDate = formatDateForApi(currentWeekStart);
      const endDate = formatDateForApi(getWeekEnd(currentWeekStart));

      // Load roster
      const rosterResponse = await rostersApi.getByDateRange(startDate, endDate, {
        department_id: departmentFilter,
      });
      const rosters = rosterResponse.data?.data || rosterResponse.data || [];
      setRoster(rosters[0] || null);

      // Load assignments
      if (rosters[0]) {
        const assignmentsResponse = await rosterAssignmentsApi.getByRoster(rosters[0].id);
        setAssignments(assignmentsResponse.data?.data || assignmentsResponse.data || []);
      } else {
        // Load by date range if no roster found
        const assignmentsResponse = await rosterAssignmentsApi.getAll({
          start_date: startDate,
          end_date: endDate,
          department_id: departmentFilter,
        });
        setAssignments(assignmentsResponse.data?.data || assignmentsResponse.data || []);
      }

      // Load on-call assignments
      const onCallResponse = await onCallAssignmentsApi.getByWeek(startDate, {
        department_id: departmentFilter,
      });
      setOnCallAssignments(onCallResponse.data?.data || onCallResponse.data || []);
    } catch (error) {
      console.error('Error loading roster:', error);
      setAssignments([]);
      setOnCallAssignments([]);
    } finally {
      setLoading(false);
    }
  }, [currentWeekStart, departmentFilter]);

  useEffect(() => {
    loadDepartments();
    loadShiftPatterns();
  }, [loadDepartments, loadShiftPatterns]);

  useEffect(() => {
    loadEmployees();
  }, [loadEmployees]);

  useEffect(() => {
    loadRoster();
  }, [loadRoster]);

  // Navigate weeks
  const goToPreviousWeek = () => {
    const newDate = new Date(currentWeekStart);
    newDate.setDate(newDate.getDate() - 7);
    setCurrentWeekStart(newDate);
  };

  const goToNextWeek = () => {
    const newDate = new Date(currentWeekStart);
    newDate.setDate(newDate.getDate() + 7);
    setCurrentWeekStart(newDate);
  };

  const goToCurrentWeek = () => {
    setCurrentWeekStart(getWeekStart(new Date()));
  };

  // Get assignment for employee on specific day
  const getAssignment = (employeeId, dayIndex) => {
    const date = formatDateForApi(getDateForDay(dayIndex));
    return assignments.find(
      (a) => a.employee_id === employeeId && a.date === date
    );
  };

  // Get on-call assignment for employee
  const getOnCallDay = (employeeId) => {
    const assignment = onCallAssignments.find((a) => a.employee_id === employeeId);
    if (!assignment) return null;
    const dayIndex = weekDays.findIndex((d) => d.key === assignment.day_of_week);
    return dayIndex >= 0 ? weekDays[dayIndex].labelEn.toLowerCase() : null;
  };

  // Open assign modal
  const handleCellClick = (employeeId, dayIndex) => {
    const assignment = getAssignment(employeeId, dayIndex);
    setSelectedCell({ employeeId, dayIndex, assignment });
    setFormData({
      shift_pattern_id: assignment?.shift_pattern_id || '',
      notes: assignment?.notes || '',
    });
    setShowAssignModal(true);
  };

  // Open on-call modal
  const handleOnCallClick = (employeeId) => {
    const currentOnCall = onCallAssignments.find((a) => a.employee_id === employeeId);
    setOnCallData({
      employee_id: employeeId,
      day: currentOnCall?.day_of_week || '',
      notes: currentOnCall?.notes || '',
    });
    setShowOnCallModal(true);
  };

  // Save assignment
  const handleSaveAssignment = async () => {
    if (!selectedCell) return;

    try {
      setSubmitting(true);
      const date = formatDateForApi(getDateForDay(selectedCell.dayIndex));
      const data = {
        employee_id: selectedCell.employeeId,
        date,
        shift_pattern_id: formData.shift_pattern_id || null,
        notes: formData.notes || null,
        roster_id: roster?.id,
      };

      if (selectedCell.assignment) {
        if (formData.shift_pattern_id) {
          await rosterAssignmentsApi.update(selectedCell.assignment.id, data);
          toast.success('تم تحديث الوردية');
        } else {
          await rosterAssignmentsApi.delete(selectedCell.assignment.id);
          toast.success('تم حذف الوردية');
        }
      } else if (formData.shift_pattern_id) {
        await rosterAssignmentsApi.create(data);
        toast.success('تم تعيين الوردية');
      }

      setShowAssignModal(false);
      loadRoster();
    } catch (error) {
      toast.error(error.response?.data?.message || 'فشل في حفظ الوردية');
    } finally {
      setSubmitting(false);
    }
  };

  // Save on-call assignment
  const handleSaveOnCall = async () => {
    try {
      setSubmitting(true);
      const weekStart = formatDateForApi(currentWeekStart);

      // Find existing on-call for this employee this week
      const existing = onCallAssignments.find(
        (a) => a.employee_id === onCallData.employee_id
      );

      if (existing) {
        if (onCallData.day) {
          await onCallAssignmentsApi.update(existing.id, {
            day_of_week: onCallData.day,
            week_start: weekStart,
            notes: onCallData.notes,
          });
          toast.success('تم تحديث المناوبة');
        } else {
          await onCallAssignmentsApi.delete(existing.id);
          toast.success('تم إلغاء المناوبة');
        }
      } else if (onCallData.day) {
        await onCallAssignmentsApi.create({
          employee_id: onCallData.employee_id,
          day_of_week: onCallData.day,
          week_start: weekStart,
          notes: onCallData.notes,
        });
        toast.success('تم تعيين المناوبة');
      }

      setShowOnCallModal(false);
      loadRoster();
    } catch (error) {
      toast.error(error.response?.data?.message || 'فشل في حفظ المناوبة');
    } finally {
      setSubmitting(false);
    }
  };

  // Get shift pattern display
  const getShiftDisplay = (assignment) => {
    if (!assignment || !assignment.shift_pattern) {
      return null;
    }

    const pattern = assignment.shift_pattern;
    if (pattern.is_off_day || pattern.type === 'off') {
      return {
        text: 'إجازة',
        color: '#FEF3C7',
        textColor: '#92400E',
      };
    }

    let timeText = '';
    if (pattern.start_time_1 && pattern.end_time_1) {
      timeText = `من ${pattern.start_time_1.slice(0, 5)}-${pattern.end_time_1.slice(0, 5)}`;
      if (pattern.is_split && pattern.start_time_2 && pattern.end_time_2) {
        timeText += ` ومن${pattern.start_time_2.slice(0, 5)}-${pattern.end_time_2.slice(0, 5)}`;
      }
    }

    return {
      text: timeText || pattern.name_ar,
      color: pattern.color || '#E0E7FF',
      textColor: '#1E40AF',
    };
  };

  // Export roster
  const handleExport = async () => {
    try {
      const response = await rostersApi.exportList({
        start_date: formatDateForApi(currentWeekStart),
        end_date: formatDateForApi(getWeekEnd(currentWeekStart)),
        department_id: departmentFilter,
      });
      const url = window.URL.createObjectURL(new Blob([response.data]));
      const link = document.createElement('a');
      link.href = url;
      link.setAttribute('download', `roster-${formatDateForApi(currentWeekStart)}.xlsx`);
      document.body.appendChild(link);
      link.click();
      link.remove();
      toast.success('تم تصدير الجدول');
    } catch (error) {
      toast.error('فشل في تصدير الجدول');
    }
  };

  return (
    <div className="space-y-6" dir="rtl">
      {/* Header */}
      <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">جدول الدوام الأسبوعي</h1>
          <p className="text-gray-600 mt-1">إدارة جداول الموظفين والورديات</p>
        </div>
        <div className="flex gap-2">
          <Button variant="secondary" icon={HiDownload} onClick={handleExport}>
            تصدير
          </Button>
        </div>
      </div>

      {/* Week Navigation */}
      <Card>
        <div className="p-4">
          <div className="flex flex-col sm:flex-row items-center justify-between gap-4">
            <div className="flex items-center gap-2">
              <Button variant="secondary" size="sm" onClick={goToPreviousWeek}>
                <HiChevronRight className="w-5 h-5" />
              </Button>
              <div className="text-center min-w-[200px]">
                <span className="font-medium">
                  {formatDate(currentWeekStart)} - {formatDate(getWeekEnd(currentWeekStart))}
                </span>
              </div>
              <Button variant="secondary" size="sm" onClick={goToNextWeek}>
                <HiChevronLeft className="w-5 h-5" />
              </Button>
              <Button variant="ghost" size="sm" onClick={goToCurrentWeek}>
                اليوم
              </Button>
            </div>
            <div className="flex items-center gap-4">
              <Select
                value={departmentFilter}
                onChange={(e) => setDepartmentFilter(e.target.value)}
                options={[
                  { value: '', label: 'جميع الأقسام' },
                  ...departments.map((d) => ({ value: d.id, label: d.name_ar })),
                ]}
                className="w-48"
              />
              <Button variant="secondary" icon={HiRefresh} onClick={loadRoster}>
                تحديث
              </Button>
            </div>
          </div>
        </div>
      </Card>

      {/* Roster Grid */}
      <Card>
        <CardHeader
          title="جدول الورديات"
          subtitle={`${employees.length} موظف`}
          icon={HiCalendar}
        />
        <div className="overflow-x-auto">
          {loading ? (
            <div className="flex items-center justify-center py-20">
              <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600"></div>
            </div>
          ) : employees.length === 0 ? (
            <div className="text-center py-20 text-gray-500">
              <HiUserGroup className="w-12 h-12 mx-auto mb-4 text-gray-400" />
              <p>لا يوجد موظفين في هذا القسم</p>
            </div>
          ) : (
            <table className="w-full min-w-[900px]">
              <thead>
                <tr className="bg-gray-800 text-white">
                  <th className="px-4 py-3 text-right font-medium w-48">الاسم</th>
                  {weekDays.map((day, index) => (
                    <th key={day.key} className="px-2 py-3 text-center font-medium">
                      <div>{day.label}</div>
                      <div className="text-xs text-gray-300">
                        {formatDate(getDateForDay(index)).split(' ')[0]}
                      </div>
                    </th>
                  ))}
                  <th className="px-2 py-3 text-center font-medium w-20">
                    <HiPhone className="w-4 h-4 mx-auto" title="ON CALL" />
                  </th>
                </tr>
              </thead>
              <tbody>
                {employees.map((employee, empIndex) => {
                  const onCallDay = getOnCallDay(employee.id);
                  return (
                    <tr
                      key={employee.id}
                      className={empIndex % 2 === 0 ? 'bg-white' : 'bg-gray-50'}
                    >
                      <td className="px-4 py-2 border-b">
                        <div className="font-medium text-sm">
                          {employee.full_name_ar || employee.name}
                        </div>
                        {employee.position && (
                          <div className="text-xs text-gray-500">
                            {employee.position.name_ar}
                          </div>
                        )}
                      </td>
                      {weekDays.map((day, dayIndex) => {
                        const assignment = getAssignment(employee.id, dayIndex);
                        const display = getShiftDisplay(assignment);
                        const isToday =
                          formatDateForApi(getDateForDay(dayIndex)) ===
                          formatDateForApi(new Date());

                        return (
                          <td
                            key={day.key}
                            className={`px-1 py-1 border-b border-l text-center cursor-pointer hover:bg-blue-50 transition-colors ${
                              isToday ? 'bg-blue-50' : ''
                            }`}
                            onClick={() => handleCellClick(employee.id, dayIndex)}
                          >
                            {display ? (
                              <div
                                className="px-1 py-1 rounded text-xs leading-tight min-h-[40px] flex items-center justify-center"
                                style={{
                                  backgroundColor: display.color,
                                  color: display.textColor,
                                }}
                              >
                                {display.text}
                              </div>
                            ) : (
                              <div className="min-h-[40px] flex items-center justify-center text-gray-300">
                                -
                              </div>
                            )}
                          </td>
                        );
                      })}
                      <td
                        className="px-2 py-1 border-b text-center cursor-pointer hover:bg-yellow-50"
                        onClick={() => handleOnCallClick(employee.id)}
                      >
                        {onCallDay ? (
                          <Badge variant="warning" size="sm">
                            {onCallDay}
                          </Badge>
                        ) : (
                          <span className="text-gray-300">-</span>
                        )}
                      </td>
                    </tr>
                  );
                })}
              </tbody>
            </table>
          )}
        </div>
      </Card>

      {/* Legend */}
      <Card>
        <div className="p-4">
          <h3 className="font-medium mb-3">مفتاح الألوان</h3>
          <div className="flex flex-wrap gap-4">
            {shiftPatterns.slice(0, 6).map((pattern) => (
              <div key={pattern.id} className="flex items-center gap-2">
                <div
                  className="w-4 h-4 rounded"
                  style={{ backgroundColor: pattern.color || '#E0E7FF' }}
                />
                <span className="text-sm">{pattern.name_ar}</span>
              </div>
            ))}
            <div className="flex items-center gap-2">
              <div
                className="w-4 h-4 rounded"
                style={{ backgroundColor: '#FEF3C7' }}
              />
              <span className="text-sm">إجازة</span>
            </div>
          </div>
        </div>
      </Card>

      {/* Assign Shift Modal */}
      <Modal
        isOpen={showAssignModal}
        onClose={() => setShowAssignModal(false)}
        title="تعيين الوردية"
        size="sm"
      >
        <div className="space-y-4">
          {selectedCell && (
            <div className="p-3 bg-gray-50 rounded-lg text-sm">
              <p>
                <strong>الموظف:</strong>{' '}
                {employees.find((e) => e.id === selectedCell.employeeId)?.full_name_ar}
              </p>
              <p>
                <strong>التاريخ:</strong>{' '}
                {formatDate(getDateForDay(selectedCell.dayIndex))}
              </p>
            </div>
          )}

          <Select
            label="نمط الوردية"
            value={formData.shift_pattern_id}
            onChange={(e) =>
              setFormData((prev) => ({ ...prev, shift_pattern_id: e.target.value }))
            }
            options={[
              { value: '', label: 'بدون وردية' },
              ...shiftPatterns.map((p) => ({
                value: p.id,
                label: `${p.name_ar} ${p.is_off_day ? '' : `(${p.start_time_1?.slice(0, 5) || ''}-${p.end_time_1?.slice(0, 5) || ''})`}`,
              })),
            ]}
          />

          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
              ملاحظات
            </label>
            <textarea
              value={formData.notes}
              onChange={(e) =>
                setFormData((prev) => ({ ...prev, notes: e.target.value }))
              }
              rows={2}
              className="w-full rounded-lg border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
              placeholder="ملاحظات إضافية..."
            />
          </div>

          <div className="flex justify-end gap-3 pt-4 border-t">
            <Button
              variant="secondary"
              onClick={() => setShowAssignModal(false)}
              disabled={submitting}
            >
              إلغاء
            </Button>
            <Button onClick={handleSaveAssignment} loading={submitting}>
              حفظ
            </Button>
          </div>
        </div>
      </Modal>

      {/* On-Call Assignment Modal */}
      <Modal
        isOpen={showOnCallModal}
        onClose={() => setShowOnCallModal(false)}
        title="تعيين المناوبة (ON CALL)"
        size="sm"
      >
        <div className="space-y-4">
          <div className="p-3 bg-gray-50 rounded-lg text-sm">
            <p>
              <strong>الموظف:</strong>{' '}
              {employees.find((e) => e.id === onCallData.employee_id)?.full_name_ar}
            </p>
          </div>

          <Select
            label="يوم المناوبة"
            value={onCallData.day}
            onChange={(e) =>
              setOnCallData((prev) => ({ ...prev, day: e.target.value }))
            }
            options={[
              { value: '', label: 'بدون مناوبة' },
              ...weekDays.map((d) => ({ value: d.key, label: d.label })),
            ]}
          />

          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
              ملاحظات
            </label>
            <textarea
              value={onCallData.notes}
              onChange={(e) =>
                setOnCallData((prev) => ({ ...prev, notes: e.target.value }))
              }
              rows={2}
              className="w-full rounded-lg border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
              placeholder="ملاحظات إضافية..."
            />
          </div>

          <div className="flex justify-end gap-3 pt-4 border-t">
            <Button
              variant="secondary"
              onClick={() => setShowOnCallModal(false)}
              disabled={submitting}
            >
              إلغاء
            </Button>
            <Button onClick={handleSaveOnCall} loading={submitting}>
              حفظ
            </Button>
          </div>
        </div>
      </Modal>
    </div>
  );
}
