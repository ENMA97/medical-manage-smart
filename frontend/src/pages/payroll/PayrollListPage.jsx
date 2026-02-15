import React, { useState, useEffect } from 'react';

/**
 * صفحة مسيرات الرواتب
 * Payroll List Page
 */

// Mock payroll data
const mockPayrolls = [
  {
    id: 1, payroll_number: 'PAY-2026-02-001', status: 'paid',
    employee: { name_ar: 'أحمد محمد الشمري', employee_number: 'EMP-001', department: 'الباطنية', nationality: 'سعودي' },
    basic_salary: 15000, housing_allowance: 3750, transportation_allowance: 1500, other_allowances: 500,
    total_allowances: 5750, gosi_deduction: 1462.50, absence_deduction: 0, loan_deduction: 500, other_deductions: 0,
    total_deductions: 1962.50, net_salary: 18787.50, working_days: 30, absence_days: 0, overtime_hours: 8, overtime_amount: 750
  },
  {
    id: 2, payroll_number: 'PAY-2026-02-002', status: 'approved',
    employee: { name_ar: 'سارة العلي القحطاني', employee_number: 'EMP-002', department: 'طب الأطفال', nationality: 'سعودي' },
    basic_salary: 12000, housing_allowance: 3000, transportation_allowance: 1200, other_allowances: 300,
    total_allowances: 4500, gosi_deduction: 1170, absence_deduction: 0, loan_deduction: 0, other_deductions: 200,
    total_deductions: 1370, net_salary: 15130, working_days: 30, absence_days: 0, overtime_hours: 0, overtime_amount: 0
  },
  {
    id: 3, payroll_number: 'PAY-2026-02-003', status: 'calculated',
    employee: { name_ar: 'خالد عبدالله العتيبي', employee_number: 'EMP-003', department: 'الجراحة', nationality: 'سعودي' },
    basic_salary: 20000, housing_allowance: 5000, transportation_allowance: 2000, other_allowances: 1000,
    total_allowances: 8000, gosi_deduction: 1950, absence_deduction: 0, loan_deduction: 1000, other_deductions: 0,
    total_deductions: 2950, net_salary: 25050, working_days: 30, absence_days: 0, overtime_hours: 12, overtime_amount: 1500
  },
  {
    id: 4, payroll_number: 'PAY-2026-02-004', status: 'calculated',
    employee: { name_ar: 'فاطمة أحمد المالكي', employee_number: 'EMP-004', department: 'النساء والتوليد', nationality: 'سعودي' },
    basic_salary: 18000, housing_allowance: 4500, transportation_allowance: 1800, other_allowances: 800,
    total_allowances: 7100, gosi_deduction: 1755, absence_deduction: 600, loan_deduction: 0, other_deductions: 0,
    total_deductions: 2355, net_salary: 22745, working_days: 29, absence_days: 1, overtime_hours: 0, overtime_amount: 0
  },
  {
    id: 5, payroll_number: 'PAY-2026-02-005', status: 'paid',
    employee: { name_ar: 'محمد حسن الدوسري', employee_number: 'EMP-005', department: 'العظام', nationality: 'سعودي' },
    basic_salary: 16000, housing_allowance: 4000, transportation_allowance: 1600, other_allowances: 600,
    total_allowances: 6200, gosi_deduction: 1560, absence_deduction: 0, loan_deduction: 800, other_deductions: 150,
    total_deductions: 2510, net_salary: 19690, working_days: 30, absence_days: 0, overtime_hours: 4, overtime_amount: 400
  },
  {
    id: 6, payroll_number: 'PAY-2026-02-006', status: 'approved',
    employee: { name_ar: 'نورة سعد الحربي', employee_number: 'EMP-006', department: 'المختبرات', nationality: 'سعودي' },
    basic_salary: 9000, housing_allowance: 2250, transportation_allowance: 900, other_allowances: 200,
    total_allowances: 3350, gosi_deduction: 877.50, absence_deduction: 0, loan_deduction: 0, other_deductions: 0,
    total_deductions: 877.50, net_salary: 11472.50, working_days: 30, absence_days: 0, overtime_hours: 6, overtime_amount: 340
  },
  {
    id: 7, payroll_number: 'PAY-2026-02-007', status: 'calculated',
    employee: { name_ar: 'عبدالرحمن يوسف', employee_number: 'EMP-007', department: 'الأشعة', nationality: 'مصري' },
    basic_salary: 8000, housing_allowance: 2000, transportation_allowance: 800, other_allowances: 200,
    total_allowances: 3000, gosi_deduction: 0, absence_deduction: 266.67, loan_deduction: 500, other_deductions: 0,
    total_deductions: 766.67, net_salary: 10233.33, working_days: 29, absence_days: 1, overtime_hours: 0, overtime_amount: 0
  },
  {
    id: 8, payroll_number: 'PAY-2026-02-008', status: 'paid',
    employee: { name_ar: 'ليلى محمود حسين', employee_number: 'EMP-008', department: 'التمريض', nationality: 'فلبيني' },
    basic_salary: 5000, housing_allowance: 1250, transportation_allowance: 500, other_allowances: 100,
    total_allowances: 1850, gosi_deduction: 0, absence_deduction: 0, loan_deduction: 300, other_deductions: 0,
    total_deductions: 300, net_salary: 6550, working_days: 30, absence_days: 0, overtime_hours: 16, overtime_amount: 625
  },
  {
    id: 9, payroll_number: 'PAY-2026-02-009', status: 'draft',
    employee: { name_ar: 'عمر خالد السبيعي', employee_number: 'EMP-009', department: 'الإدارة', nationality: 'سعودي' },
    basic_salary: 10000, housing_allowance: 2500, transportation_allowance: 1000, other_allowances: 300,
    total_allowances: 3800, gosi_deduction: 975, absence_deduction: 0, loan_deduction: 0, other_deductions: 0,
    total_deductions: 975, net_salary: 12825, working_days: 30, absence_days: 0, overtime_hours: 0, overtime_amount: 0
  },
  {
    id: 10, payroll_number: 'PAY-2026-02-010', status: 'approved',
    employee: { name_ar: 'هند ناصر الغامدي', employee_number: 'EMP-010', department: 'الصيدلية', nationality: 'سعودي' },
    basic_salary: 11000, housing_allowance: 2750, transportation_allowance: 1100, other_allowances: 400,
    total_allowances: 4250, gosi_deduction: 1072.50, absence_deduction: 0, loan_deduction: 0, other_deductions: 100,
    total_deductions: 1172.50, net_salary: 14077.50, working_days: 30, absence_days: 0, overtime_hours: 2, overtime_amount: 140
  }
];

export default function PayrollListPage() {
  const [payrolls, setPayrolls] = useState([]);
  const [summary, setSummary] = useState(null);
  const [loading, setLoading] = useState(true);
  const [selectedYear, setSelectedYear] = useState(new Date().getFullYear());
  const [selectedMonth, setSelectedMonth] = useState(new Date().getMonth() + 1);
  const [selectedPayrolls, setSelectedPayrolls] = useState([]);
  const [generating, setGenerating] = useState(false);
  const [showPayslipModal, setShowPayslipModal] = useState(false);
  const [showDetailModal, setShowDetailModal] = useState(false);
  const [selectedPayroll, setSelectedPayroll] = useState(null);
  const [filterStatus, setFilterStatus] = useState('');
  const [searchTerm, setSearchTerm] = useState('');

  const months = [
    { value: 1, label: 'يناير' }, { value: 2, label: 'فبراير' }, { value: 3, label: 'مارس' },
    { value: 4, label: 'أبريل' }, { value: 5, label: 'مايو' }, { value: 6, label: 'يونيو' },
    { value: 7, label: 'يوليو' }, { value: 8, label: 'أغسطس' }, { value: 9, label: 'سبتمبر' },
    { value: 10, label: 'أكتوبر' }, { value: 11, label: 'نوفمبر' }, { value: 12, label: 'ديسمبر' },
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
    draft: 'مسودة', calculated: 'محسوب', reviewed: 'مراجع',
    approved: 'معتمد', paid: 'مدفوع', cancelled: 'ملغي',
  };

  useEffect(() => {
    loadData();
  }, [selectedYear, selectedMonth]);

  const loadData = async () => {
    setLoading(true);
    setTimeout(() => {
      setPayrolls(mockPayrolls);
      const totals = mockPayrolls.reduce((acc, p) => ({
        total_employees: acc.total_employees + 1,
        total_basic: acc.total_basic + p.basic_salary,
        total_allowances: acc.total_allowances + p.total_allowances,
        total_deductions: acc.total_deductions + p.total_deductions,
        total_net: acc.total_net + p.net_salary,
      }), { total_employees: 0, total_basic: 0, total_allowances: 0, total_deductions: 0, total_net: 0 });
      setSummary(totals);
      setLoading(false);
    }, 500);
  };

  const handleGenerate = () => {
    if (!confirm(`هل تريد توليد مسيرات الرواتب لشهر ${months[selectedMonth - 1].label} ${selectedYear}؟`)) return;
    setGenerating(true);
    setTimeout(() => {
      setGenerating(false);
      alert(`تم توليد ${mockPayrolls.length} مسير راتب بنجاح`);
    }, 1000);
  };

  const handleBulkApprove = () => {
    if (selectedPayrolls.length === 0) {
      alert('الرجاء تحديد المسيرات المراد اعتمادها');
      return;
    }
    setPayrolls(prev => prev.map(p =>
      selectedPayrolls.includes(p.id) && p.status === 'calculated'
        ? { ...p, status: 'approved' } : p
    ));
    alert(`تم اعتماد ${selectedPayrolls.length} مسير بنجاح`);
    setSelectedPayrolls([]);
  };

  const handleApprove = (id) => {
    setPayrolls(prev => prev.map(p => p.id === id ? { ...p, status: 'approved' } : p));
  };

  const handleMarkPaid = (id) => {
    setPayrolls(prev => prev.map(p => p.id === id ? { ...p, status: 'paid' } : p));
  };

  const togglePayrollSelection = (id) => {
    setSelectedPayrolls(prev =>
      prev.includes(id) ? prev.filter(p => p !== id) : [...prev, id]
    );
  };

  const toggleSelectAll = () => {
    if (selectedPayrolls.length === filteredPayrolls.length) {
      setSelectedPayrolls([]);
    } else {
      setSelectedPayrolls(filteredPayrolls.map(p => p.id));
    }
  };

  const formatCurrency = (amount) => {
    return new Intl.NumberFormat('ar-SA', {
      style: 'currency', currency: 'SAR', minimumFractionDigits: 0
    }).format(amount || 0);
  };

  const years = Array.from({ length: 5 }, (_, i) => new Date().getFullYear() - 2 + i);

  const filteredPayrolls = payrolls.filter(p => {
    if (filterStatus && p.status !== filterStatus) return false;
    if (searchTerm && !p.employee.name_ar.includes(searchTerm) && !p.employee.employee_number.includes(searchTerm)) return false;
    return true;
  });

  const openPayslip = (payroll) => {
    setSelectedPayroll(payroll);
    setShowPayslipModal(true);
  };

  const openDetail = (payroll) => {
    setSelectedPayroll(payroll);
    setShowDetailModal(true);
  };

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
      <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">مسيرات الرواتب</h1>
          <p className="text-gray-600 mt-1">إدارة رواتب الموظفين الشهرية</p>
        </div>
        <div className="flex flex-wrap items-center gap-3">
          <select value={selectedYear} onChange={(e) => setSelectedYear(Number(e.target.value))}
            className="border border-gray-300 rounded-lg px-3 py-2 text-sm">
            {years.map(year => <option key={year} value={year}>{year}</option>)}
          </select>
          <select value={selectedMonth} onChange={(e) => setSelectedMonth(Number(e.target.value))}
            className="border border-gray-300 rounded-lg px-3 py-2 text-sm">
            {months.map(month => <option key={month.value} value={month.value}>{month.label}</option>)}
          </select>
          <button onClick={handleGenerate} disabled={generating}
            className="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 text-sm">
            {generating ? 'جاري التوليد...' : 'توليد المسيرات'}
          </button>
        </div>
      </div>

      {/* Summary Cards */}
      {summary && (
        <div className="grid grid-cols-2 md:grid-cols-5 gap-4">
          <div className="bg-white rounded-lg shadow p-4">
            <p className="text-xs text-gray-500">عدد الموظفين</p>
            <p className="text-xl font-bold text-gray-900 mt-1">{summary.total_employees}</p>
          </div>
          <div className="bg-white rounded-lg shadow p-4">
            <p className="text-xs text-gray-500">إجمالي الأساسي</p>
            <p className="text-xl font-bold text-blue-600 mt-1">{formatCurrency(summary.total_basic)}</p>
          </div>
          <div className="bg-white rounded-lg shadow p-4">
            <p className="text-xs text-gray-500">إجمالي البدلات</p>
            <p className="text-xl font-bold text-green-600 mt-1">{formatCurrency(summary.total_allowances)}</p>
          </div>
          <div className="bg-white rounded-lg shadow p-4">
            <p className="text-xs text-gray-500">إجمالي الخصومات</p>
            <p className="text-xl font-bold text-red-600 mt-1">{formatCurrency(summary.total_deductions)}</p>
          </div>
          <div className="bg-white rounded-lg shadow p-4">
            <p className="text-xs text-gray-500">صافي الرواتب</p>
            <p className="text-xl font-bold text-purple-600 mt-1">{formatCurrency(summary.total_net)}</p>
          </div>
        </div>
      )}

      {/* Status Overview */}
      <div className="flex flex-wrap gap-3">
        {Object.entries(statusLabels).map(([key, label]) => {
          const count = payrolls.filter(p => p.status === key).length;
          if (count === 0) return null;
          return (
            <button key={key} onClick={() => setFilterStatus(filterStatus === key ? '' : key)}
              className={`px-4 py-2 rounded-full text-sm font-medium transition-all ${
                filterStatus === key ? 'ring-2 ring-offset-2 ring-blue-500' : ''
              } ${statusColors[key]}`}>
              {label}: {count}
            </button>
          );
        })}
        {filterStatus && (
          <button onClick={() => setFilterStatus('')}
            className="px-4 py-2 rounded-full text-sm text-gray-600 bg-gray-100 hover:bg-gray-200">
            عرض الكل
          </button>
        )}
      </div>

      {/* Search and Actions */}
      <div className="bg-white rounded-lg shadow p-4 flex flex-wrap items-center justify-between gap-4">
        <input
          type="text" placeholder="بحث بالاسم أو الرقم الوظيفي..."
          value={searchTerm} onChange={(e) => setSearchTerm(e.target.value)}
          className="border border-gray-300 rounded-lg px-4 py-2 text-sm w-64"
        />
        {selectedPayrolls.length > 0 && (
          <div className="flex items-center gap-3">
            <span className="text-sm text-blue-700">تم تحديد {selectedPayrolls.length} مسير</span>
            <button onClick={handleBulkApprove}
              className="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 text-sm">
              اعتماد المحدد
            </button>
          </div>
        )}
      </div>

      {/* Payrolls Table */}
      <div className="bg-white rounded-lg shadow overflow-hidden">
        <div className="overflow-x-auto">
          <table className="min-w-full divide-y divide-gray-200">
            <thead className="bg-gray-50">
              <tr>
                <th className="px-3 py-3 text-right">
                  <input type="checkbox"
                    checked={selectedPayrolls.length === filteredPayrolls.length && filteredPayrolls.length > 0}
                    onChange={toggleSelectAll} className="rounded border-gray-300" />
                </th>
                <th className="px-4 py-3 text-right text-xs font-medium text-gray-500">رقم المسير</th>
                <th className="px-4 py-3 text-right text-xs font-medium text-gray-500">الموظف</th>
                <th className="px-4 py-3 text-right text-xs font-medium text-gray-500">القسم</th>
                <th className="px-4 py-3 text-right text-xs font-medium text-gray-500">الأساسي</th>
                <th className="px-4 py-3 text-right text-xs font-medium text-gray-500">البدلات</th>
                <th className="px-4 py-3 text-right text-xs font-medium text-gray-500">الخصومات</th>
                <th className="px-4 py-3 text-right text-xs font-medium text-gray-500">الصافي</th>
                <th className="px-4 py-3 text-right text-xs font-medium text-gray-500">الحالة</th>
                <th className="px-4 py-3 text-right text-xs font-medium text-gray-500">الإجراءات</th>
              </tr>
            </thead>
            <tbody className="bg-white divide-y divide-gray-200">
              {filteredPayrolls.length === 0 ? (
                <tr>
                  <td colSpan="10" className="px-6 py-12 text-center text-gray-500">
                    <svg className="w-12 h-12 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <p>لا توجد مسيرات لهذه الفترة</p>
                    <button onClick={handleGenerate} className="mt-2 text-blue-600 hover:underline text-sm">
                      انقر لتوليد المسيرات
                    </button>
                  </td>
                </tr>
              ) : (
                filteredPayrolls.map(payroll => (
                  <tr key={payroll.id} className="hover:bg-gray-50">
                    <td className="px-3 py-4">
                      <input type="checkbox" checked={selectedPayrolls.includes(payroll.id)}
                        onChange={() => togglePayrollSelection(payroll.id)} className="rounded border-gray-300" />
                    </td>
                    <td className="px-4 py-4 text-sm font-mono text-gray-900">{payroll.payroll_number}</td>
                    <td className="px-4 py-4 text-sm">
                      <div className="font-medium text-gray-900">{payroll.employee.name_ar}</div>
                      <div className="text-gray-500 text-xs">{payroll.employee.employee_number}</div>
                    </td>
                    <td className="px-4 py-4 text-sm text-gray-500">{payroll.employee.department}</td>
                    <td className="px-4 py-4 text-sm text-gray-900">{formatCurrency(payroll.basic_salary)}</td>
                    <td className="px-4 py-4 text-sm text-green-600">+{formatCurrency(payroll.total_allowances)}</td>
                    <td className="px-4 py-4 text-sm text-red-600">-{formatCurrency(payroll.total_deductions)}</td>
                    <td className="px-4 py-4 text-sm font-bold text-purple-700">{formatCurrency(payroll.net_salary)}</td>
                    <td className="px-4 py-4">
                      <span className={`px-2 py-1 text-xs rounded-full ${statusColors[payroll.status]}`}>
                        {statusLabels[payroll.status]}
                      </span>
                    </td>
                    <td className="px-4 py-4 text-sm">
                      <div className="flex items-center gap-2">
                        <button onClick={() => openDetail(payroll)} className="text-blue-600 hover:text-blue-900">عرض</button>
                        <button onClick={() => openPayslip(payroll)} className="text-green-600 hover:text-green-900">قسيمة</button>
                        {payroll.status === 'calculated' && (
                          <button onClick={() => handleApprove(payroll.id)} className="text-purple-600 hover:text-purple-900">اعتماد</button>
                        )}
                        {payroll.status === 'approved' && (
                          <button onClick={() => handleMarkPaid(payroll.id)} className="text-indigo-600 hover:text-indigo-900">صرف</button>
                        )}
                      </div>
                    </td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </div>
      </div>

      {/* Payslip Modal */}
      {showPayslipModal && selectedPayroll && (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
          <div className="bg-white rounded-xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto" dir="rtl">
            {/* Payslip Header */}
            <div className="bg-gradient-to-l from-blue-600 to-blue-800 text-white p-6">
              <div className="flex justify-between items-start">
                <div>
                  <h2 className="text-xl font-bold">قسيمة راتب</h2>
                  <p className="text-blue-100 mt-1">الفترة: {months[selectedMonth - 1].label} {selectedYear}</p>
                </div>
                <div className="text-left">
                  <p className="text-sm text-blue-100">المنشأة الطبية الذكية</p>
                  <p className="text-sm text-blue-100">{selectedPayroll.payroll_number}</p>
                </div>
              </div>
            </div>

            {/* Employee Info */}
            <div className="p-6 border-b">
              <div className="grid grid-cols-2 gap-4">
                <div>
                  <p className="text-xs text-gray-500">اسم الموظف</p>
                  <p className="font-bold text-gray-900">{selectedPayroll.employee.name_ar}</p>
                </div>
                <div>
                  <p className="text-xs text-gray-500">الرقم الوظيفي</p>
                  <p className="font-medium text-gray-700">{selectedPayroll.employee.employee_number}</p>
                </div>
                <div>
                  <p className="text-xs text-gray-500">القسم</p>
                  <p className="font-medium text-gray-700">{selectedPayroll.employee.department}</p>
                </div>
                <div>
                  <p className="text-xs text-gray-500">الجنسية</p>
                  <p className="font-medium text-gray-700">{selectedPayroll.employee.nationality}</p>
                </div>
              </div>
            </div>

            {/* Earnings & Deductions */}
            <div className="p-6 grid grid-cols-2 gap-6">
              {/* Earnings */}
              <div>
                <h3 className="font-bold text-green-700 mb-3 border-b pb-2">المستحقات</h3>
                <div className="space-y-2 text-sm">
                  <div className="flex justify-between"><span>الراتب الأساسي</span><span>{formatCurrency(selectedPayroll.basic_salary)}</span></div>
                  <div className="flex justify-between"><span>بدل السكن</span><span>{formatCurrency(selectedPayroll.housing_allowance)}</span></div>
                  <div className="flex justify-between"><span>بدل النقل</span><span>{formatCurrency(selectedPayroll.transportation_allowance)}</span></div>
                  <div className="flex justify-between"><span>بدلات أخرى</span><span>{formatCurrency(selectedPayroll.other_allowances)}</span></div>
                  {selectedPayroll.overtime_amount > 0 && (
                    <div className="flex justify-between"><span>الوقت الإضافي ({selectedPayroll.overtime_hours} ساعة)</span><span>{formatCurrency(selectedPayroll.overtime_amount)}</span></div>
                  )}
                  <div className="flex justify-between font-bold border-t pt-2 text-green-700">
                    <span>إجمالي المستحقات</span>
                    <span>{formatCurrency(selectedPayroll.basic_salary + selectedPayroll.total_allowances + selectedPayroll.overtime_amount)}</span>
                  </div>
                </div>
              </div>

              {/* Deductions */}
              <div>
                <h3 className="font-bold text-red-700 mb-3 border-b pb-2">الاستقطاعات</h3>
                <div className="space-y-2 text-sm">
                  {selectedPayroll.gosi_deduction > 0 && (
                    <div className="flex justify-between"><span>التأمينات الاجتماعية</span><span>{formatCurrency(selectedPayroll.gosi_deduction)}</span></div>
                  )}
                  {selectedPayroll.absence_deduction > 0 && (
                    <div className="flex justify-between"><span>خصم الغياب ({selectedPayroll.absence_days} يوم)</span><span>{formatCurrency(selectedPayroll.absence_deduction)}</span></div>
                  )}
                  {selectedPayroll.loan_deduction > 0 && (
                    <div className="flex justify-between"><span>قسط السلفة</span><span>{formatCurrency(selectedPayroll.loan_deduction)}</span></div>
                  )}
                  {selectedPayroll.other_deductions > 0 && (
                    <div className="flex justify-between"><span>خصومات أخرى</span><span>{formatCurrency(selectedPayroll.other_deductions)}</span></div>
                  )}
                  {selectedPayroll.total_deductions === 0 && (
                    <div className="text-gray-400 text-center py-2">لا توجد استقطاعات</div>
                  )}
                  <div className="flex justify-between font-bold border-t pt-2 text-red-700">
                    <span>إجمالي الاستقطاعات</span>
                    <span>{formatCurrency(selectedPayroll.total_deductions)}</span>
                  </div>
                </div>
              </div>
            </div>

            {/* Net Salary */}
            <div className="mx-6 mb-4 bg-purple-50 rounded-lg p-4">
              <div className="flex justify-between items-center">
                <span className="text-lg font-bold text-purple-800">صافي الراتب المستحق</span>
                <span className="text-2xl font-bold text-purple-700">{formatCurrency(selectedPayroll.net_salary)}</span>
              </div>
            </div>

            {/* Working Days */}
            <div className="mx-6 mb-4 bg-gray-50 rounded-lg p-3 flex justify-around text-sm">
              <div className="text-center">
                <p className="text-gray-500">أيام العمل</p>
                <p className="font-bold">{selectedPayroll.working_days}</p>
              </div>
              <div className="text-center">
                <p className="text-gray-500">أيام الغياب</p>
                <p className="font-bold text-red-600">{selectedPayroll.absence_days}</p>
              </div>
              <div className="text-center">
                <p className="text-gray-500">ساعات إضافية</p>
                <p className="font-bold text-blue-600">{selectedPayroll.overtime_hours}</p>
              </div>
            </div>

            {/* Actions */}
            <div className="p-4 border-t flex justify-end gap-3">
              <button onClick={() => setShowPayslipModal(false)}
                className="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 text-sm">إغلاق</button>
              <button className="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm">
                طباعة القسيمة
              </button>
            </div>
          </div>
        </div>
      )}

      {/* Detail Modal */}
      {showDetailModal && selectedPayroll && (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
          <div className="bg-white rounded-xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto" dir="rtl">
            <div className="p-6 border-b flex justify-between items-center">
              <h2 className="text-lg font-bold">تفاصيل المسير</h2>
              <span className={`px-3 py-1 text-xs rounded-full ${statusColors[selectedPayroll.status]}`}>
                {statusLabels[selectedPayroll.status]}
              </span>
            </div>
            <div className="p-6 space-y-4">
              <div className="grid grid-cols-2 gap-4 text-sm">
                <div className="bg-gray-50 p-3 rounded-lg">
                  <p className="text-gray-500 text-xs">رقم المسير</p>
                  <p className="font-mono font-medium">{selectedPayroll.payroll_number}</p>
                </div>
                <div className="bg-gray-50 p-3 rounded-lg">
                  <p className="text-gray-500 text-xs">الموظف</p>
                  <p className="font-medium">{selectedPayroll.employee.name_ar}</p>
                </div>
                <div className="bg-gray-50 p-3 rounded-lg">
                  <p className="text-gray-500 text-xs">الرقم الوظيفي</p>
                  <p className="font-medium">{selectedPayroll.employee.employee_number}</p>
                </div>
                <div className="bg-gray-50 p-3 rounded-lg">
                  <p className="text-gray-500 text-xs">القسم</p>
                  <p className="font-medium">{selectedPayroll.employee.department}</p>
                </div>
              </div>

              <div className="border-t pt-4 space-y-2 text-sm">
                <div className="flex justify-between"><span className="text-gray-500">الراتب الأساسي</span><span>{formatCurrency(selectedPayroll.basic_salary)}</span></div>
                <div className="flex justify-between text-green-600"><span>+ البدلات</span><span>{formatCurrency(selectedPayroll.total_allowances)}</span></div>
                {selectedPayroll.overtime_amount > 0 && (
                  <div className="flex justify-between text-blue-600"><span>+ الوقت الإضافي</span><span>{formatCurrency(selectedPayroll.overtime_amount)}</span></div>
                )}
                <div className="flex justify-between text-red-600"><span>- الاستقطاعات</span><span>{formatCurrency(selectedPayroll.total_deductions)}</span></div>
                <div className="flex justify-between font-bold text-lg border-t pt-2">
                  <span>الصافي</span><span className="text-purple-700">{formatCurrency(selectedPayroll.net_salary)}</span>
                </div>
              </div>
            </div>
            <div className="p-4 border-t flex justify-end gap-3">
              <button onClick={() => setShowDetailModal(false)}
                className="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 text-sm">إغلاق</button>
              {selectedPayroll.status === 'calculated' && (
                <button onClick={() => { handleApprove(selectedPayroll.id); setShowDetailModal(false); }}
                  className="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 text-sm">اعتماد</button>
              )}
              {selectedPayroll.status === 'approved' && (
                <button onClick={() => { handleMarkPaid(selectedPayroll.id); setShowDetailModal(false); }}
                  className="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 text-sm">تأكيد الصرف</button>
              )}
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
