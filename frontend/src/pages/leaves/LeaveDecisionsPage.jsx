import React, { useState, useEffect } from 'react';
import { leaveDecisionsApi } from '../../services/leaveApi';

/**
 * صفحة قرارات الإجازة (المرحلة الثانية)
 * Leave Decisions Page (Phase 2)
 */
export default function LeaveDecisionsPage() {
  const [decisions, setDecisions] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [filters, setFilters] = useState({
    status: '',
  });

  useEffect(() => {
    loadData();
  }, [filters]);

  const loadData = async () => {
    try {
      setLoading(true);
      const response = await leaveDecisionsApi.getAll(filters);
      setDecisions(response.data || []);
    } catch (err) {
      setError(err.message);
    } finally {
      setLoading(false);
    }
  };

  const getStatusBadge = (status) => {
    const statusConfig = {
      draft: { label: 'مسودة', color: 'bg-gray-100 text-gray-800' },
      pending_admin_manager: { label: 'بانتظار المدير الإداري', color: 'bg-yellow-100 text-yellow-800' },
      pending_medical_director: { label: 'بانتظار المدير الطبي', color: 'bg-blue-100 text-blue-800' },
      pending_general_manager: { label: 'بانتظار المدير العام', color: 'bg-purple-100 text-purple-800' },
      approved: { label: 'معتمد', color: 'bg-green-100 text-green-800' },
      rejected: { label: 'مرفوض', color: 'bg-red-100 text-red-800' },
    };
    const config = statusConfig[status] || { label: status, color: 'bg-gray-100 text-gray-800' };
    return (
      <span className={`px-2 py-1 rounded-full text-xs font-medium ${config.color}`}>
        {config.label}
      </span>
    );
  };

  const getActionBadge = (action) => {
    if (!action) return '-';
    const actionConfig = {
      approve: { label: 'اعتماد', color: 'text-green-600' },
      forward_to_gm: { label: 'تحويل للمدير العام', color: 'text-blue-600' },
      reject: { label: 'رفض', color: 'text-red-600' },
    };
    const config = actionConfig[action] || { label: action, color: 'text-gray-600' };
    return <span className={config.color}>{config.label}</span>;
  };

  if (loading) {
    return (
      <div className="flex items-center justify-center h-64">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="bg-red-50 border border-red-200 rounded-lg p-4 text-red-700">
        <h3 className="font-bold">خطأ</h3>
        <p>{error}</p>
        <button onClick={loadData} className="mt-2 px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">
          إعادة المحاولة
        </button>
      </div>
    );
  }

  return (
    <div className="space-y-6" dir="rtl">
      {/* Header */}
      <div className="flex justify-between items-center">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">قرارات الإجازة</h1>
          <p className="text-gray-600 mt-1">المرحلة الثانية - اعتماد قرارات الإجازة</p>
        </div>
      </div>

      {/* Workflow Info */}
      <div className="bg-blue-50 border border-blue-200 rounded-lg p-4">
        <h3 className="font-semibold text-blue-800 mb-2">مسار الاعتماد</h3>
        <div className="flex items-center gap-2 text-sm text-blue-700 flex-wrap">
          <span className="bg-blue-100 px-2 py-1 rounded">إنشاء القرار</span>
          <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" />
          </svg>
          <span className="bg-blue-100 px-2 py-1 rounded">المدير الإداري / الطبي</span>
          <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" />
          </svg>
          <span className="bg-blue-100 px-2 py-1 rounded">المدير العام (اختياري)</span>
          <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" />
          </svg>
          <span className="bg-green-100 text-green-800 px-2 py-1 rounded">معتمد</span>
        </div>
      </div>

      {/* Filters */}
      <div className="bg-white rounded-lg shadow p-4">
        <div className="flex gap-4 items-center">
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">الحالة</label>
            <select
              value={filters.status}
              onChange={(e) => setFilters({ ...filters, status: e.target.value })}
              className="border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500"
            >
              <option value="">جميع الحالات</option>
              <option value="pending_admin_manager">بانتظار المدير الإداري</option>
              <option value="pending_medical_director">بانتظار المدير الطبي</option>
              <option value="pending_general_manager">بانتظار المدير العام</option>
              <option value="approved">معتمد</option>
              <option value="rejected">مرفوض</option>
            </select>
          </div>
        </div>
      </div>

      {/* Decisions Table */}
      <div className="bg-white rounded-lg shadow overflow-hidden">
        <table className="min-w-full divide-y divide-gray-200">
          <thead className="bg-gray-50">
            <tr>
              <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                رقم القرار
              </th>
              <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                الموظف
              </th>
              <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                نوع الإجازة
              </th>
              <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                الفترة
              </th>
              <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                إجراء المدير
              </th>
              <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                الحالة
              </th>
              <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                الإجراءات
              </th>
            </tr>
          </thead>
          <tbody className="bg-white divide-y divide-gray-200">
            {decisions.length === 0 ? (
              <tr>
                <td colSpan="7" className="px-6 py-12 text-center text-gray-500">
                  <svg className="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                  </svg>
                  <p className="mt-2">لا توجد قرارات إجازة</p>
                </td>
              </tr>
            ) : (
              decisions.map((decision) => (
                <tr key={decision.id} className="hover:bg-gray-50">
                  <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                    {decision.decision_number}
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                    {decision.leave_request?.employee?.name || '-'}
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                    {decision.leave_request?.leave_type?.name_ar || '-'}
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                    {decision.leave_request?.start_date} - {decision.leave_request?.end_date}
                    <br />
                    <span className="text-gray-500 text-xs">
                      ({decision.leave_request?.working_days} يوم)
                    </span>
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap text-sm">
                    {getActionBadge(decision.admin_manager_action || decision.medical_director_action)}
                    {decision.forwarded_to_gm && (
                      <span className="block text-xs text-purple-600 mt-1">
                        محول للمدير العام
                      </span>
                    )}
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap">
                    {getStatusBadge(decision.status)}
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap text-sm">
                    <button className="text-blue-600 hover:text-blue-900 ml-3">
                      عرض
                    </button>
                    {(decision.status === 'pending_admin_manager' ||
                      decision.status === 'pending_medical_director' ||
                      decision.status === 'pending_general_manager') && (
                      <button className="text-green-600 hover:text-green-900">
                        اعتماد
                      </button>
                    )}
                  </td>
                </tr>
              ))
            )}
          </tbody>
        </table>
      </div>
    </div>
  );
}
