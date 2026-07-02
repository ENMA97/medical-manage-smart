import { useState, useEffect } from 'react';
import toast from 'react-hot-toast';
import dashboardService from '../services/dashboardService';

const REPORT_TYPES = [
  { id: 'employees', label: 'تقرير الموظفين', icon: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z' },
  { id: 'leaves', label: 'تقرير الإجازات', icon: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z' },
  { id: 'payroll', label: 'تقرير الرواتب', icon: 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z' },
  { id: 'contracts', label: 'تقرير العقود', icon: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z' },
  { id: 'attendance', label: 'تقرير الحضور', icon: 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z' },
  { id: 'disciplinary', label: 'تقرير المخالفات', icon: 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z' },
];

export default function Reports() {
  const [selectedReport, setSelectedReport] = useState(null);
  const [loading, setLoading] = useState(false);
  const [stats, setStats] = useState(null);

  useEffect(() => {
    async function loadStats() {
      try {
        const { data } = await dashboardService.getSummary();
        setStats(data.data);
      } catch { /* ignore */ }
    }
    loadStats();
  }, []);

  async function generateReport(type) {
    setSelectedReport(type);
    setLoading(true);
    try {
      // Simulate report generation delay
      await new Promise(resolve => setTimeout(resolve, 1000));
      toast.success('تم تحميل التقرير بنجاح');
    } catch {
      toast.error('حدث خطأ في إنشاء التقرير');
    } finally {
      setLoading(false);
    }
  }

  return (
    <div className="space-y-6">
      <h1 className="text-xl font-bold text-gray-800">التقارير</h1>

      {/* Quick Stats */}
      {stats && (
        <div className="grid grid-cols-2 sm:grid-cols-4 gap-4">
          {[
            { label: 'إجمالي الموظفين', value: stats.total_employees ?? '-', colorClass: 'text-blue-600' },
            { label: 'الموظفين النشطين', value: stats.active_employees ?? '-', colorClass: 'text-green-600' },
            { label: 'إجازات قيد الانتظار', value: stats.pending_leaves ?? '-', colorClass: 'text-yellow-600' },
            { label: 'عقود منتهية قريباً', value: stats.expiring_contracts ?? '-', colorClass: 'text-red-600' },
          ].map((stat, i) => (
            <div key={i} className="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
              <p className="text-xs text-gray-500 mb-1">{stat.label}</p>
              <p className={`text-2xl font-bold ${stat.colorClass}`}>{stat.value}</p>
            </div>
          ))}
        </div>
      )}

      {/* Report Cards */}
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        {REPORT_TYPES.map((report) => (
          <div
            key={report.id}
            className={`bg-white rounded-xl shadow-sm border p-5 cursor-pointer transition-all hover:shadow-md ${
              selectedReport === report.id ? 'border-teal-500 ring-2 ring-teal-100' : 'border-gray-100'
            }`}
            onClick={() => generateReport(report.id)}
          >
            <div className="flex items-center gap-3 mb-3">
              <div className="w-10 h-10 bg-teal-50 rounded-lg flex items-center justify-center">
                <svg className="w-5 h-5 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d={report.icon} />
                </svg>
              </div>
              <h3 className="font-semibold text-gray-800">{report.label}</h3>
            </div>
            <p className="text-sm text-gray-500 mb-4">عرض وتصدير بيانات {report.label.replace('تقرير ', '')}</p>
            <button
              className="w-full px-4 py-2 text-sm bg-gray-50 text-gray-700 rounded-lg hover:bg-gray-100 transition-colors font-medium"
              disabled={loading && selectedReport === report.id}
            >
              {loading && selectedReport === report.id ? (
                <span className="flex items-center justify-center gap-2">
                  <div className="w-4 h-4 border-2 border-gray-300 border-t-gray-600 rounded-full animate-spin" />
                  جاري التحميل...
                </span>
              ) : 'عرض التقرير'}
            </button>
          </div>
        ))}
      </div>

      {/* Selected Report Preview */}
      {selectedReport && !loading && (
        <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
          <div className="flex items-center justify-between mb-4">
            <h2 className="text-base font-semibold text-gray-800">
              {REPORT_TYPES.find(r => r.id === selectedReport)?.label}
            </h2>
            <div className="flex gap-2">
              <button className="px-3 py-1.5 text-xs bg-green-600 text-white rounded-lg hover:bg-green-700">
                تصدير Excel
              </button>
              <button className="px-3 py-1.5 text-xs bg-red-600 text-white rounded-lg hover:bg-red-700">
                تصدير PDF
              </button>
            </div>
          </div>
          <div className="text-center py-12 text-gray-400 border-2 border-dashed border-gray-200 rounded-lg">
            <svg className="w-12 h-12 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <p className="text-sm">سيتم عرض بيانات التقرير هنا</p>
            <p className="text-xs mt-1">يمكنك التصدير بصيغة Excel أو PDF</p>
          </div>
        </div>
      )}
    </div>
  );
}
