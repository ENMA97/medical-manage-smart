import React, { useState, useEffect } from 'react';
import { payrollsApi } from '../../services/payrollApi';

/**
 * صفحة مسيرات الرواتب
 * Payroll List Page
 */
export default function PayrollListPage() {
  const [payrolls, setPayrolls] = useState([]);
  const [summary, setSummary] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [selectedYear, setSelectedYear] = useState(new Date().getFullYear());
  const [selectedMonth, setSelectedMonth] = useState(new Date().getMonth() + 1);
  const [selectedPayrolls, setSelectedPayrolls] = useState([]);
  const [generating, setGenerating] = useState(false);

  const months = [
    { value: 1, label: 'يناير' },
    { value: 2, label: 'فبراير' },
    { value: 3, label: 'مارس' },
    { value: 4, label: 'أبريل' },
    { value: 5, label: 'مايو' },
    { value: 6, label: 'يونيو' },
    { value: 7, label: 'يوليو' },
    { value: 8, label: 'أغسطس' },
    { value: 9, label: 'سبتمبر' },
    { value: 10, label: 'أكتوبر' },
    { value: 11, label: 'نوفمبر' },
    { value: 12, label: 'ديسمبر' },
  ];

  const statusColors = {
    draft: 'bg-gray-100 text-gray-800',
    calculated: 'bg-blue-100 text-blue-800',
    reviewed: 'bg-yellow-100 text-yellow-800',
    approved: 'bg-green-100 text-green-800',
    paid: 'bg-purple-100 text-purple-800',
    cancelled: 'bg-red-100 text-red-800',
  };

  const statusLabels = {
    draft: 'مسودة',
    calculated: 'محسوب',
    reviewed: 'مراجع',
    approved: 'معتمد',
    paid: 'مدفوع',
    cancelled: 'ملغي',
  };

  useEffect(() => {
    loadData();
  }, [selectedYear, selectedMonth]);

  const loadData = async () => {
    try {
      setLoading(true);
      const [payrollsRes, summaryRes] = await Promise.all([
        payrollsApi.getAll({ year: selectedYear, month: selectedMonth }),
        payrollsApi.getPeriodSummary(selectedYear, selectedMonth),
      ]);
      setPayrolls(payrollsRes.data?.data || []);
      setSummary(summaryRes.data);
    } catch (err) {
      setError(err.message);
    } finally {
      setLoading(false);
    }
  };

  const handleGenerate = async () => {
    if (!confirm(`هل تريد توليد مسيرات الرواتب لشهر ${months[selectedMonth - 1].label} ${selectedYear}؟`)) {
      return;
    }

    try {
      setGenerating(true);
      const result = await payrollsApi.generate(selectedYear, selectedMonth);
      alert(`تم توليد ${result.data.success} مسير راتب بنجاح`);
      loadData();
    } catch (err) {
      alert(err.message);
    } finally {
      setGenerating(false);
    }
  };

  const handleBulkApprove = async () => {
    if (selectedPayrolls.length === 0) {
      alert('الرجاء تحديد المسيرات المراد اعتمادها');
      return;
    }

    try {
      const result = await payrollsApi.bulkApprove(selectedPayrolls);
      alert(`تم اعتماد ${result.approved} مسير بنجاح`);
      setSelectedPayrolls([]);
      loadData();
    } catch (err) {
      alert(err.message);
    }
  };

  const togglePayrollSelection = (id) => {
    setSelectedPayrolls((prev) =>
      prev.includes(id) ? prev.filter((p) => p !== id) : [...prev, id]
    );
  };

  const toggleSelectAll = () => {
    if (selectedPayrolls.length === payrolls.length) {
      setSelectedPayrolls([]);
    } else {
      setSelectedPayrolls(payrolls.map((p) => p.id));
    }
  };

  const formatCurrency = (amount) => {
    return new Intl.NumberFormat('ar-SA', {
      style: 'currency',
      currency: 'SAR',
    }).format(amount || 0);
  };

  const years = Array.from({ length: 5 }, (_, i) => new Date().getFullYear() - 2 + i);

  if (loading) {
    return (
      <div className="flex items-center justify-center h-64">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
      </div>
    );
  }

  return (
    <div className="space-y-6" dir="rtl">
      {/* Header */}
      <div className="flex justify-between items-center">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">مسيرات الرواتب</h1>
          <p className="text-gray-600 mt-1">إدارة رواتب الموظفين الشهرية</p>
        </div>
        <div className="flex items-center gap-4">
          <select
            value={selectedYear}
            onChange={(e) => setSelectedYear(Number(e.target.value))}
            className="border border-gray-300 rounded-lg px-4 py-2"
          >
            {years.map((year) => (
              <option key={year} value={year}>{year}</option>
            ))}
          </select>
          <select
            value={selectedMonth}
            onChange={(e) => setSelectedMonth(Number(e.target.value))}
            className="border border-gray-300 rounded-lg px-4 py-2"
          >
            {months.map((month) => (
              <option key={month.value} value={month.value}>{month.label}</option>
            ))}
          </select>
          <button
            onClick={handleGenerate}
            disabled={generating}
            className="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50"
          >
            {generating ? 'جاري التوليد...' : 'توليد المسيرات'}
          </button>
        </div>
      </div>

      {/* Summary Cards */}
      {summary && (
        <div className="grid grid-cols-1 md:grid-cols-5 gap-4">
          <div className="bg-white rounded-lg shadow p-6">
            <p className="text-sm text-gray-600">عدد الموظفين</p>
            <p className="text-2xl font-bold text-gray-900">{summary.total_employees}</p>
          </div>
          <div className="bg-white rounded-lg shadow p-6">
            <p className="text-sm text-gray-600">إجمالي الأساسي</p>
            <p className="text-2xl font-bold text-blue-600">{formatCurrency(summary.total_basic)}</p>
          </div>
          <div className="bg-white rounded-lg shadow p-6">
            <p className="text-sm text-gray-600">إجمالي البدلات</p>
            <p className="text-2xl font-bold text-green-600">{formatCurrency(summary.total_allowances)}</p>
          </div>
          <div className="bg-white rounded-lg shadow p-6">
            <p className="text-sm text-gray-600">إجمالي الخصومات</p>
            <p className="text-2xl font-bold text-red-600">{formatCurrency(summary.total_deductions)}</p>
          </div>
          <div className="bg-white rounded-lg shadow p-6">
            <p className="text-sm text-gray-600">صافي الرواتب</p>
            <p className="text-2xl font-bold text-purple-600">{formatCurrency(summary.total_net)}</p>
          </div>
        </div>
      )}

      {/* Actions */}
      {selectedPayrolls.length > 0 && (
        <div className="bg-blue-50 rounded-lg p-4 flex items-center justify-between">
          <span className="text-blue-700">تم تحديد {selectedPayrolls.length} مسير</span>
          <button
            onClick={handleBulkApprove}
            className="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700"
          >
            اعتماد المحدد
          </button>
        </div>
      )}

      {/* Payrolls Table */}
      <div className="bg-white rounded-lg shadow overflow-hidden">
        <table className="min-w-full divide-y divide-gray-200">
          <thead className="bg-gray-50">
            <tr>
              <th className="px-4 py-3 text-right">
                <input
                  type="checkbox"
                  checked={selectedPayrolls.length === payrolls.length && payrolls.length > 0}
                  onChange={toggleSelectAll}
                  className="rounded border-gray-300"
                />
              </th>
              <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">
                رقم المسير
              </th>
              <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">
                الموظف
              </th>
              <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">
                الأساسي
              </th>
              <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">
                البدلات
              </th>
              <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">
                الخصومات
              </th>
              <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">
                الصافي
              </th>
              <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">
                الحالة
              </th>
              <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">
                الإجراءات
              </th>
            </tr>
          </thead>
          <tbody className="bg-white divide-y divide-gray-200">
            {payrolls.length === 0 ? (
              <tr>
                <td colSpan="9" className="px-6 py-12 text-center text-gray-500">
                  <p>لا توجد مسيرات لهذه الفترة</p>
                  <button
                    onClick={handleGenerate}
                    className="mt-2 text-blue-600 hover:underline"
                  >
                    انقر لتوليد المسيرات
                  </button>
                </td>
              </tr>
            ) : (
              payrolls.map((payroll) => (
                <tr key={payroll.id} className="hover:bg-gray-50">
                  <td className="px-4 py-4">
                    <input
                      type="checkbox"
                      checked={selectedPayrolls.includes(payroll.id)}
                      onChange={() => togglePayrollSelection(payroll.id)}
                      className="rounded border-gray-300"
                    />
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                    {payroll.payroll_number}
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                    <div>
                      <div className="font-medium">{payroll.employee?.name_ar}</div>
                      <div className="text-gray-500 text-xs">{payroll.employee?.employee_number}</div>
                    </div>
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                    {formatCurrency(payroll.basic_salary)}
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap text-sm text-green-600">
                    {formatCurrency(payroll.total_allowances)}
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap text-sm text-red-600">
                    {formatCurrency(payroll.total_deductions)}
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap text-sm font-bold text-purple-600">
                    {formatCurrency(payroll.net_salary)}
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap">
                    <span className={`px-2 py-1 text-xs rounded-full ${statusColors[payroll.status]}`}>
                      {statusLabels[payroll.status]}
                    </span>
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap text-sm">
                    <button className="text-blue-600 hover:text-blue-900 ml-2">
                      عرض
                    </button>
                    <button className="text-green-600 hover:text-green-900 ml-2">
                      قسيمة
                    </button>
                    {payroll.status === 'calculated' && (
                      <button className="text-purple-600 hover:text-purple-900">
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
