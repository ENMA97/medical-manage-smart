import React, { useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import { Link } from 'react-router-dom';
import api from '../../services/api';

export default function EmployeeList() {
  const [search, setSearch] = useState('');
  const [statusFilter, setStatusFilter] = useState('');
  const [page, setPage] = useState(1);

  const { data, isLoading } = useQuery({
    queryKey: ['employees', { search, status: statusFilter, page }],
    queryFn: () =>
      api
        .get('/employees', {
          params: {
            search: search || undefined,
            status: statusFilter || undefined,
            page,
            per_page: 15,
          },
        })
        .then((r) => r.data),
  });

  const employees = data?.data || [];
  const lastPage = data?.last_page || 1;

  const statusBadge = (status) => {
    const colors = {
      active: 'bg-green-100 text-green-800',
      inactive: 'bg-gray-100 text-gray-800',
      suspended: 'bg-yellow-100 text-yellow-800',
      terminated: 'bg-red-100 text-red-800',
    };
    const labels = {
      active: 'نشط',
      inactive: 'غير نشط',
      suspended: 'موقوف',
      terminated: 'منتهي',
    };
    return (
      <span className={`px-2 py-0.5 rounded-full text-xs font-medium ${colors[status] || ''}`}>
        {labels[status] || status}
      </span>
    );
  };

  return (
    <div>
      <div className="flex items-center justify-between mb-6">
        <h1 className="text-2xl font-bold text-gray-900">الموظفون</h1>
        <Link
          to="/hr/employees/new"
          className="rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-blue-700"
        >
          + إضافة موظف
        </Link>
      </div>

      {/* Filters */}
      <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-4">
        <div className="flex gap-4 flex-wrap">
          <input
            type="text"
            placeholder="بحث بالاسم، الرقم الوظيفي، أو البريد..."
            value={search}
            onChange={(e) => { setSearch(e.target.value); setPage(1); }}
            className="flex-1 min-w-[250px] rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
          />
          <select
            value={statusFilter}
            onChange={(e) => { setStatusFilter(e.target.value); setPage(1); }}
            className="rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
          >
            <option value="">جميع الحالات</option>
            <option value="active">نشط</option>
            <option value="inactive">غير نشط</option>
            <option value="suspended">موقوف</option>
            <option value="terminated">منتهي</option>
          </select>
        </div>
      </div>

      {/* Table */}
      <div className="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        {isLoading ? (
          <div className="flex justify-center py-12">
            <div className="animate-spin rounded-full h-8 w-8 border-t-2 border-b-2 border-blue-600"></div>
          </div>
        ) : employees.length === 0 ? (
          <div className="text-center py-12 text-gray-500">
            <p className="text-lg">لا يوجد موظفون</p>
            <p className="text-sm mt-1">قم بإضافة موظف جديد للبدء</p>
          </div>
        ) : (
          <table className="min-w-full divide-y divide-gray-200">
            <thead className="bg-gray-50">
              <tr>
                <th className="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">الرقم</th>
                <th className="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">الاسم</th>
                <th className="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">البريد</th>
                <th className="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">المحافظة</th>
                <th className="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">الحالة</th>
                <th className="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">تاريخ التعيين</th>
                <th className="px-4 py-3"></th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-200">
              {employees.map((emp) => (
                <tr key={emp.id} className="hover:bg-gray-50">
                  <td className="px-4 py-3 text-sm font-mono text-gray-600">{emp.employee_number}</td>
                  <td className="px-4 py-3 text-sm">
                    <div className="font-medium text-gray-900">{emp.name_ar || emp.name}</div>
                    {emp.name_ar && <div className="text-xs text-gray-500">{emp.name}</div>}
                  </td>
                  <td className="px-4 py-3 text-sm text-gray-600" dir="ltr">{emp.email}</td>
                  <td className="px-4 py-3 text-sm text-gray-600">
                    {emp.county ? (
                      <div>
                        <div>{emp.county.name_ar}</div>
                        <div className="text-xs text-gray-400">{emp.county.region?.name_ar}</div>
                      </div>
                    ) : (
                      <span className="text-gray-400">-</span>
                    )}
                  </td>
                  <td className="px-4 py-3">{statusBadge(emp.status)}</td>
                  <td className="px-4 py-3 text-sm text-gray-600">{emp.hire_date}</td>
                  <td className="px-4 py-3">
                    <Link
                      to={`/hr/employees/${emp.id}`}
                      className="text-blue-600 hover:text-blue-800 text-sm"
                    >
                      تعديل
                    </Link>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        )}

        {/* Pagination */}
        {lastPage > 1 && (
          <div className="flex items-center justify-between px-4 py-3 border-t border-gray-200 bg-gray-50">
            <div className="text-sm text-gray-600">
              صفحة {page} من {lastPage}
            </div>
            <div className="flex gap-2">
              <button
                onClick={() => setPage((p) => Math.max(1, p - 1))}
                disabled={page === 1}
                className="px-3 py-1 rounded border border-gray-300 text-sm disabled:opacity-50 hover:bg-gray-100"
              >
                السابق
              </button>
              <button
                onClick={() => setPage((p) => Math.min(lastPage, p + 1))}
                disabled={page === lastPage}
                className="px-3 py-1 rounded border border-gray-300 text-sm disabled:opacity-50 hover:bg-gray-100"
              >
                التالي
              </button>
            </div>
          </div>
        )}
      </div>
    </div>
  );
}
