import { useState, useEffect, useCallback } from 'react';
import { Link } from 'react-router-dom';
import toast from 'react-hot-toast';
import employeeService from '../services/employeeService';

const statusLabels = {
  active: 'نشط',
  inactive: 'غير نشط',
  suspended: 'موقوف',
  terminated: 'منتهي',
};

const statusColors = {
  active: 'bg-green-100 text-green-700',
  inactive: 'bg-gray-100 text-gray-600',
  suspended: 'bg-yellow-100 text-yellow-700',
  terminated: 'bg-red-100 text-red-700',
};

export default function Employees() {
  const [employees, setEmployees] = useState([]);
  const [loading, setLoading] = useState(true);
  const [search, setSearch] = useState('');
  const [page, setPage] = useState(1);
  const [meta, setMeta] = useState({});

  const fetchEmployees = useCallback(async () => {
    setLoading(true);
    try {
      const { data } = await employeeService.getAll({ search, page, per_page: 15 });
      setEmployees(data.data);
      setMeta(data.meta || {});
    } catch {
      toast.error('حدث خطأ في تحميل بيانات الموظفين');
    } finally {
      setLoading(false);
    }
  }, [search, page]);

  useEffect(() => {
    fetchEmployees();
  }, [fetchEmployees]);

  useEffect(() => {
    setPage(1);
  }, [search]);

  return (
    <div className="space-y-4">
      <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <h1 className="text-xl font-bold text-gray-800">الموظفون</h1>
        <Link
          to="/employees/new"
          className="inline-flex items-center gap-2 bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-700 transition-colors"
        >
          <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 4v16m8-8H4" />
          </svg>
          إضافة موظف
        </Link>
      </div>

      {/* Search */}
      <div className="relative">
        <input
          type="text"
          placeholder="البحث بالاسم أو الرقم الوظيفي..."
          value={search}
          onChange={(e) => setSearch(e.target.value)}
          className="w-full sm:w-80 pl-4 pr-10 py-2.5 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
        />
        <svg className="w-4 h-4 text-gray-400 absolute right-3 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
      </div>

      {/* Table */}
      <div className="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        {loading ? (
          <div className="flex items-center justify-center py-16">
            <div className="w-8 h-8 border-4 border-blue-200 border-t-blue-600 rounded-full animate-spin" />
          </div>
        ) : employees.length === 0 ? (
          <div className="text-center py-16 text-gray-500">
            <p className="text-lg">لا يوجد موظفون</p>
            <p className="text-sm mt-1">قم بإضافة موظفين جدد أو استيرادهم من ملف Excel</p>
          </div>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-sm">
              <thead className="bg-gray-50 border-b border-gray-100">
                <tr>
                  <th className="text-right px-4 py-3 font-medium text-gray-600">الرقم الوظيفي</th>
                  <th className="text-right px-4 py-3 font-medium text-gray-600">الاسم</th>
                  <th className="text-right px-4 py-3 font-medium text-gray-600 hidden sm:table-cell">القسم</th>
                  <th className="text-right px-4 py-3 font-medium text-gray-600 hidden md:table-cell">الهاتف</th>
                  <th className="text-right px-4 py-3 font-medium text-gray-600">الحالة</th>
                  <th className="px-4 py-3"></th>
                </tr>
              </thead>
              <tbody className="divide-y divide-gray-50">
                {employees.map((emp) => (
                  <tr key={emp.id} className="hover:bg-gray-50 transition-colors">
                    <td className="px-4 py-3 font-mono text-gray-500">{emp.employee_number}</td>
                    <td className="px-4 py-3 font-medium text-gray-800">{emp.full_name}</td>
                    <td className="px-4 py-3 text-gray-600 hidden sm:table-cell">{emp.department?.name_ar || '—'}</td>
                    <td className="px-4 py-3 text-gray-600 hidden md:table-cell" dir="ltr">{emp.user?.phone || '—'}</td>
                    <td className="px-4 py-3">
                      <span className={`inline-block px-2 py-0.5 rounded-full text-xs font-medium ${statusColors[emp.status] || 'bg-gray-100 text-gray-600'}`}>
                        {statusLabels[emp.status] || emp.status}
                      </span>
                    </td>
                    <td className="px-4 py-3">
                      <Link to={`/employees/${emp.id}`} className="text-blue-600 hover:text-blue-800 text-sm font-medium">
                        عرض
                      </Link>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}

        {/* Pagination */}
        {meta.last_page > 1 && (
          <div className="flex items-center justify-between px-4 py-3 border-t border-gray-100">
            <p className="text-xs text-gray-500">
              عرض {meta.from}–{meta.to} من {meta.total}
            </p>
            <div className="flex gap-1">
              <button
                disabled={page <= 1}
                onClick={() => setPage(page - 1)}
                className="px-3 py-1 text-sm rounded-lg border border-gray-200 disabled:opacity-50 hover:bg-gray-50"
              >
                السابق
              </button>
              <button
                disabled={page >= meta.last_page}
                onClick={() => setPage(page + 1)}
                className="px-3 py-1 text-sm rounded-lg border border-gray-200 disabled:opacity-50 hover:bg-gray-50"
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
