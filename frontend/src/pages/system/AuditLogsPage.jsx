import React, { useState, useEffect } from 'react';

const mockLogs = [
  { id: 1, event_type: 'login', module: 'system', description_ar: 'تسجيل دخول ناجح', user_name: 'مدير النظام', ip_address: '192.168.1.10', user_agent: 'Chrome 120 / Windows 11', created_at: '2026-02-15 09:30:00' },
  { id: 2, event_type: 'created', module: 'hr', description_ar: 'إنشاء ملف موظف جديد: سارة القحطاني', user_name: 'فهد العمري', ip_address: '192.168.1.25', user_agent: 'Firefox 121 / macOS', created_at: '2026-02-15 09:15:00' },
  { id: 3, event_type: 'updated', module: 'inventory', description_ar: 'تحديث كمية المخزون: باراسيتامول 500mg (من 200 إلى 350)', user_name: 'خالد الغامدي', ip_address: '192.168.1.30', user_agent: 'Chrome 120 / Windows 10', created_at: '2026-02-15 08:45:00' },
  { id: 4, event_type: 'approved', module: 'leaves', description_ar: 'الموافقة على طلب إجازة سنوية للموظف: أحمد الشمري (5 أيام)', user_name: 'فهد العمري', ip_address: '192.168.1.25', user_agent: 'Firefox 121 / macOS', created_at: '2026-02-15 08:30:00' },
  { id: 5, event_type: 'rejected', module: 'finance', description_ar: 'رفض مطالبة تأمين رقم #INS-2026-0045 - مستندات ناقصة', user_name: 'نوال الحربي', ip_address: '192.168.1.15', user_agent: 'Edge 120 / Windows 11', created_at: '2026-02-15 08:00:00' },
  { id: 6, event_type: 'exported', module: 'payroll', description_ar: 'تصدير كشف رواتب شهر يناير 2026 (ملف WPS)', user_name: 'نوال الحربي', ip_address: '192.168.1.15', user_agent: 'Edge 120 / Windows 11', created_at: '2026-02-14 16:30:00' },
  { id: 7, event_type: 'deleted', module: 'inventory', description_ar: 'حذف صنف منتهي الصلاحية: محلول ملحي دفعة #B2025-089', user_name: 'خالد الغامدي', ip_address: '192.168.1.30', user_agent: 'Chrome 120 / Windows 10', created_at: '2026-02-14 15:20:00' },
  { id: 8, event_type: 'created', module: 'roster', description_ar: 'إنشاء جدول مناوبات أسبوع 10-16 فبراير لقسم التمريض', user_name: 'فهد العمري', ip_address: '192.168.1.25', user_agent: 'Firefox 121 / macOS', created_at: '2026-02-14 14:00:00' },
  { id: 9, event_type: 'logout', module: 'system', description_ar: 'تسجيل خروج', user_name: 'عبدالله الدوسري', ip_address: '192.168.1.40', user_agent: 'Safari 17 / iOS 17', created_at: '2026-02-14 13:00:00' },
  { id: 10, event_type: 'updated', module: 'hr', description_ar: 'تحديث عقد الموظف: محمد السعيد (تجديد لمدة سنة)', user_name: 'فهد العمري', ip_address: '192.168.1.25', user_agent: 'Firefox 121 / macOS', created_at: '2026-02-14 11:30:00' },
  { id: 11, event_type: 'approved', module: 'finance', description_ar: 'اعتماد طلب شراء رقم #PO-2026-0112 بقيمة 45,000 ريال', user_name: 'مدير النظام', ip_address: '192.168.1.10', user_agent: 'Chrome 120 / Windows 11', created_at: '2026-02-14 10:15:00' },
  { id: 12, event_type: 'created', module: 'finance', description_ar: 'إنشاء مطالبة تأمين جديدة للمريض: خالد العنزي (#INS-2026-0048)', user_name: 'نوال الحربي', ip_address: '192.168.1.15', user_agent: 'Edge 120 / Windows 11', created_at: '2026-02-14 09:45:00' },
  { id: 13, event_type: 'login', module: 'system', description_ar: 'تسجيل دخول ناجح', user_name: 'د. أحمد الشمري', ip_address: '192.168.1.50', user_agent: 'Chrome 120 / Android 14', created_at: '2026-02-14 07:00:00' },
  { id: 14, event_type: 'updated', module: 'system', description_ar: 'تعديل إعدادات النظام: تحديث مهلة الجلسة إلى 30 دقيقة', user_name: 'مدير النظام', ip_address: '192.168.1.10', user_agent: 'Chrome 120 / Windows 11', created_at: '2026-02-13 16:00:00' },
  { id: 15, event_type: 'exported', module: 'hr', description_ar: 'تصدير تقرير الحضور الشهري لشهر يناير 2026', user_name: 'فهد العمري', ip_address: '192.168.1.25', user_agent: 'Firefox 121 / macOS', created_at: '2026-02-13 14:30:00' },
  { id: 16, event_type: 'rejected', module: 'leaves', description_ar: 'رفض طلب إجازة طارئة للموظف: ليلى العنزي (تعارض مع الجدول)', user_name: 'فهد العمري', ip_address: '192.168.1.25', user_agent: 'Firefox 121 / macOS', created_at: '2026-02-13 11:00:00' },
  { id: 17, event_type: 'created', module: 'payroll', description_ar: 'إنشاء قسيمة راتب شهر فبراير 2026 (مسودة)', user_name: 'نوال الحربي', ip_address: '192.168.1.15', user_agent: 'Edge 120 / Windows 11', created_at: '2026-02-13 09:00:00' },
  { id: 18, event_type: 'deleted', module: 'hr', description_ar: 'حذف سجل عهدة: جهاز لابتوب (تم الإرجاع)', user_name: 'فهد العمري', ip_address: '192.168.1.25', user_agent: 'Firefox 121 / macOS', created_at: '2026-02-12 15:45:00' },
];

const eventLabels = {
  created: 'إنشاء', updated: 'تحديث', deleted: 'حذف', login: 'دخول',
  logout: 'خروج', approved: 'موافقة', rejected: 'رفض', exported: 'تصدير'
};
const eventColors = {
  created: 'bg-green-100 text-green-700', updated: 'bg-blue-100 text-blue-700', deleted: 'bg-red-100 text-red-700',
  login: 'bg-indigo-100 text-indigo-700', logout: 'bg-gray-100 text-gray-700', approved: 'bg-emerald-100 text-emerald-700',
  rejected: 'bg-orange-100 text-orange-700', exported: 'bg-purple-100 text-purple-700'
};

const moduleLabels = {
  hr: 'الموارد البشرية', inventory: 'المخزون', roster: 'الجداول',
  finance: 'المالية', payroll: 'الرواتب', system: 'النظام', leaves: 'الإجازات'
};
const moduleColors = {
  hr: 'bg-blue-50 text-blue-600', inventory: 'bg-amber-50 text-amber-600', roster: 'bg-pink-50 text-pink-600',
  finance: 'bg-green-50 text-green-600', payroll: 'bg-teal-50 text-teal-600', system: 'bg-gray-50 text-gray-600', leaves: 'bg-violet-50 text-violet-600'
};

export default function AuditLogsPage() {
  const [logs, setLogs] = useState([]);
  const [loading, setLoading] = useState(true);
  const [searchTerm, setSearchTerm] = useState('');
  const [filterEvent, setFilterEvent] = useState('');
  const [filterModule, setFilterModule] = useState('');
  const [filterUser, setFilterUser] = useState('');
  const [filterDateFrom, setFilterDateFrom] = useState('');
  const [filterDateTo, setFilterDateTo] = useState('');
  const [showDetailModal, setShowDetailModal] = useState(false);
  const [selectedLog, setSelectedLog] = useState(null);

  useEffect(() => {
    setTimeout(() => { setLogs(mockLogs); setLoading(false); }, 400);
  }, []);

  const uniqueUsers = [...new Set(mockLogs.map(l => l.user_name))];

  const filtered = logs.filter(l => {
    if (filterEvent && l.event_type !== filterEvent) return false;
    if (filterModule && l.module !== filterModule) return false;
    if (filterUser && l.user_name !== filterUser) return false;
    if (searchTerm && !l.description_ar.includes(searchTerm) && !l.user_name.includes(searchTerm)) return false;
    if (filterDateFrom && l.created_at < filterDateFrom) return false;
    if (filterDateTo && l.created_at > filterDateTo + ' 23:59:59') return false;
    return true;
  });

  const handleExport = () => {
    alert('تم تصدير السجل بنجاح (محاكاة)');
  };

  if (loading) return <div className="flex items-center justify-center h-64"><div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div></div>;

  return (
    <div className="space-y-6" dir="rtl">
      <div className="flex justify-between items-center">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">سجل المراجعة</h1>
          <p className="text-gray-600 mt-1">سجل جميع العمليات في النظام (للقراءة فقط)</p>
        </div>
        <button onClick={handleExport} className="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 text-sm">تصدير السجل</button>
      </div>

      {/* Stats */}
      <div className="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-8 gap-3">
        {Object.entries(eventLabels).map(([key, label]) => (
          <div key={key} className="bg-white rounded-lg shadow p-3 cursor-pointer hover:shadow-md" onClick={() => setFilterEvent(filterEvent === key ? '' : key)}>
            <p className="text-xs text-gray-500">{label}</p>
            <p className={`text-xl font-bold mt-1 ${filterEvent === key ? 'text-blue-600' : ''}`}>{logs.filter(l => l.event_type === key).length}</p>
          </div>
        ))}
      </div>

      {/* Filters */}
      <div className="bg-white rounded-lg shadow p-4 space-y-3">
        <div className="flex flex-wrap gap-4">
          <input type="text" placeholder="بحث في الوصف..." value={searchTerm} onChange={e => setSearchTerm(e.target.value)} className="border rounded-lg px-4 py-2 text-sm w-64" />
          <select value={filterEvent} onChange={e => setFilterEvent(e.target.value)} className="border rounded-lg px-4 py-2 text-sm">
            <option value="">جميع الأحداث</option>
            {Object.entries(eventLabels).map(([k, v]) => <option key={k} value={k}>{v}</option>)}
          </select>
          <select value={filterModule} onChange={e => setFilterModule(e.target.value)} className="border rounded-lg px-4 py-2 text-sm">
            <option value="">جميع الوحدات</option>
            {Object.entries(moduleLabels).map(([k, v]) => <option key={k} value={k}>{v}</option>)}
          </select>
          <select value={filterUser} onChange={e => setFilterUser(e.target.value)} className="border rounded-lg px-4 py-2 text-sm">
            <option value="">جميع المستخدمين</option>
            {uniqueUsers.map(u => <option key={u} value={u}>{u}</option>)}
          </select>
        </div>
        <div className="flex flex-wrap gap-4 items-center">
          <label className="text-sm text-gray-600">من:</label>
          <input type="date" value={filterDateFrom} onChange={e => setFilterDateFrom(e.target.value)} className="border rounded-lg px-3 py-2 text-sm" />
          <label className="text-sm text-gray-600">إلى:</label>
          <input type="date" value={filterDateTo} onChange={e => setFilterDateTo(e.target.value)} className="border rounded-lg px-3 py-2 text-sm" />
          {(searchTerm || filterEvent || filterModule || filterUser || filterDateFrom || filterDateTo) && (
            <button onClick={() => { setSearchTerm(''); setFilterEvent(''); setFilterModule(''); setFilterUser(''); setFilterDateFrom(''); setFilterDateTo(''); }}
              className="text-sm text-red-600 hover:text-red-800">مسح الفلاتر</button>
          )}
          <span className="text-xs text-gray-400 mr-auto">{filtered.length} من {logs.length} سجل</span>
        </div>
      </div>

      {/* Table */}
      <div className="bg-white rounded-lg shadow overflow-hidden">
        <div className="overflow-x-auto">
          <table className="min-w-full divide-y divide-gray-200">
            <thead className="bg-gray-50">
              <tr>
                <th className="px-4 py-3 text-right text-xs font-medium text-gray-500">#</th>
                <th className="px-4 py-3 text-right text-xs font-medium text-gray-500">الحدث</th>
                <th className="px-4 py-3 text-right text-xs font-medium text-gray-500">الوحدة</th>
                <th className="px-4 py-3 text-right text-xs font-medium text-gray-500">الوصف</th>
                <th className="px-4 py-3 text-right text-xs font-medium text-gray-500">المستخدم</th>
                <th className="px-4 py-3 text-right text-xs font-medium text-gray-500">IP</th>
                <th className="px-4 py-3 text-right text-xs font-medium text-gray-500">التاريخ</th>
                <th className="px-4 py-3 text-right text-xs font-medium text-gray-500">التفاصيل</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-200">
              {filtered.map(log => (
                <tr key={log.id} className="hover:bg-gray-50">
                  <td className="px-4 py-3 text-xs text-gray-400">{log.id}</td>
                  <td className="px-4 py-3"><span className={`px-2 py-1 text-xs rounded-full ${eventColors[log.event_type]}`}>{eventLabels[log.event_type]}</span></td>
                  <td className="px-4 py-3"><span className={`px-2 py-1 text-xs rounded ${moduleColors[log.module]}`}>{moduleLabels[log.module]}</span></td>
                  <td className="px-4 py-3 text-sm text-gray-700 max-w-xs truncate">{log.description_ar}</td>
                  <td className="px-4 py-3 text-sm">
                    <div className="flex items-center gap-2">
                      <div className="w-7 h-7 bg-blue-100 text-blue-700 rounded-full flex items-center justify-center font-bold text-xs">{log.user_name.charAt(0)}</div>
                      <span className="text-gray-700">{log.user_name}</span>
                    </div>
                  </td>
                  <td className="px-4 py-3 text-xs text-gray-500 font-mono" dir="ltr">{log.ip_address}</td>
                  <td className="px-4 py-3 text-xs text-gray-500">{log.created_at}</td>
                  <td className="px-4 py-3">
                    <button onClick={() => { setSelectedLog(log); setShowDetailModal(true); }} className="text-blue-600 hover:text-blue-900 text-sm">عرض</button>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
        {filtered.length === 0 && (
          <div className="text-center py-12 text-gray-500">
            <p className="text-lg mb-1">لا توجد نتائج</p>
            <p className="text-sm">جرب تغيير معايير البحث</p>
          </div>
        )}
      </div>

      {/* Detail Modal */}
      {showDetailModal && selectedLog && (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
          <div className="bg-white rounded-xl shadow-2xl w-full max-w-lg" dir="rtl">
            <div className="p-6 border-b flex justify-between items-center">
              <h2 className="text-lg font-bold">تفاصيل السجل #{selectedLog.id}</h2>
              <span className={`px-3 py-1 text-xs rounded-full ${eventColors[selectedLog.event_type]}`}>{eventLabels[selectedLog.event_type]}</span>
            </div>
            <div className="p-6 space-y-4">
              <div className="bg-gray-50 p-4 rounded-lg">
                <p className="text-xs text-gray-500 mb-1">الوصف</p>
                <p className="text-sm font-medium">{selectedLog.description_ar}</p>
              </div>
              <div className="grid grid-cols-2 gap-3 text-sm">
                <div className="bg-gray-50 p-3 rounded-lg">
                  <p className="text-xs text-gray-500">نوع الحدث</p>
                  <p><span className={`px-2 py-0.5 text-xs rounded-full ${eventColors[selectedLog.event_type]}`}>{eventLabels[selectedLog.event_type]}</span></p>
                </div>
                <div className="bg-gray-50 p-3 rounded-lg">
                  <p className="text-xs text-gray-500">الوحدة</p>
                  <p><span className={`px-2 py-0.5 text-xs rounded ${moduleColors[selectedLog.module]}`}>{moduleLabels[selectedLog.module]}</span></p>
                </div>
                <div className="bg-gray-50 p-3 rounded-lg">
                  <p className="text-xs text-gray-500">المستخدم</p>
                  <p className="font-medium">{selectedLog.user_name}</p>
                </div>
                <div className="bg-gray-50 p-3 rounded-lg">
                  <p className="text-xs text-gray-500">عنوان IP</p>
                  <p className="font-medium font-mono" dir="ltr">{selectedLog.ip_address}</p>
                </div>
                <div className="bg-gray-50 p-3 rounded-lg col-span-2">
                  <p className="text-xs text-gray-500">متصفح المستخدم</p>
                  <p className="font-medium text-xs" dir="ltr">{selectedLog.user_agent}</p>
                </div>
                <div className="bg-gray-50 p-3 rounded-lg col-span-2">
                  <p className="text-xs text-gray-500">التاريخ والوقت</p>
                  <p className="font-medium">{selectedLog.created_at}</p>
                </div>
              </div>
              <div className="bg-yellow-50 border border-yellow-200 rounded-lg p-3">
                <p className="text-xs text-yellow-700">هذا السجل غير قابل للتعديل أو الحذف (سجل ثابت)</p>
              </div>
            </div>
            <div className="p-4 border-t flex justify-end">
              <button onClick={() => setShowDetailModal(false)} className="px-4 py-2 border rounded-lg hover:bg-gray-50 text-sm">إغلاق</button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
