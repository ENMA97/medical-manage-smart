import React, { useState, useEffect } from 'react';

/**
 * صفحة السلف والقروض
 * Loans Page
 */

const mockLoans = [
  {
    id: 1, loan_number: 'LN-2026-001', type: 'loan', status: 'active',
    employee: { name_ar: 'أحمد محمد الشمري', employee_number: 'EMP-001', department: 'الباطنية' },
    loan_amount: 12000, installment_amount: 1000, total_installments: 12, paid_installments: 5,
    remaining_amount: 7000, start_date: '2025-10-01', reason: 'ظروف شخصية', approved_by: 'محمد المدير'
  },
  {
    id: 2, loan_number: 'LN-2026-002', type: 'advance', status: 'active',
    employee: { name_ar: 'سارة العلي القحطاني', employee_number: 'EMP-002', department: 'طب الأطفال' },
    loan_amount: 5000, installment_amount: 2500, total_installments: 2, paid_installments: 1,
    remaining_amount: 2500, start_date: '2026-01-01', reason: 'سلفة راتب', approved_by: 'محمد المدير'
  },
  {
    id: 3, loan_number: 'LN-2026-003', type: 'loan', status: 'pending',
    employee: { name_ar: 'خالد عبدالله العتيبي', employee_number: 'EMP-003', department: 'الجراحة' },
    loan_amount: 20000, installment_amount: 2000, total_installments: 10, paid_installments: 0,
    remaining_amount: 20000, start_date: '2026-03-01', reason: 'علاج طبي', approved_by: null
  },
  {
    id: 4, loan_number: 'LN-2026-004', type: 'loan', status: 'completed',
    employee: { name_ar: 'فاطمة أحمد المالكي', employee_number: 'EMP-004', department: 'النساء والتوليد' },
    loan_amount: 8000, installment_amount: 1000, total_installments: 8, paid_installments: 8,
    remaining_amount: 0, start_date: '2025-06-01', reason: 'تجهيزات منزلية', approved_by: 'محمد المدير'
  },
  {
    id: 5, loan_number: 'LN-2026-005', type: 'advance', status: 'pending',
    employee: { name_ar: 'محمد حسن الدوسري', employee_number: 'EMP-005', department: 'العظام' },
    loan_amount: 8000, installment_amount: 4000, total_installments: 2, paid_installments: 0,
    remaining_amount: 8000, start_date: '2026-03-01', reason: 'سلفة راتب شهرين', approved_by: null
  },
  {
    id: 6, loan_number: 'LN-2026-006', type: 'loan', status: 'active',
    employee: { name_ar: 'نورة سعد الحربي', employee_number: 'EMP-006', department: 'المختبرات' },
    loan_amount: 6000, installment_amount: 500, total_installments: 12, paid_installments: 3,
    remaining_amount: 4500, start_date: '2025-12-01', reason: 'ظروف عائلية', approved_by: 'محمد المدير'
  },
  {
    id: 7, loan_number: 'LN-2026-007', type: 'loan', status: 'rejected',
    employee: { name_ar: 'عبدالرحمن يوسف', employee_number: 'EMP-007', department: 'الأشعة' },
    loan_amount: 30000, installment_amount: 2500, total_installments: 12, paid_installments: 0,
    remaining_amount: 30000, start_date: '2026-02-01', reason: 'سداد ديون', approved_by: null,
    rejection_reason: 'المبلغ يتجاوز الحد المسموح (3 أضعاف الراتب)'
  },
  {
    id: 8, loan_number: 'LN-2026-008', type: 'advance', status: 'active',
    employee: { name_ar: 'هند ناصر الغامدي', employee_number: 'EMP-010', department: 'الصيدلية' },
    loan_amount: 5500, installment_amount: 2750, total_installments: 2, paid_installments: 0,
    remaining_amount: 5500, start_date: '2026-02-01', reason: 'سلفة راتب', approved_by: 'محمد المدير'
  }
];

const mockEmployees = [
  { id: 'EMP-001', name_ar: 'أحمد محمد الشمري', basic_salary: 15000 },
  { id: 'EMP-002', name_ar: 'سارة العلي القحطاني', basic_salary: 12000 },
  { id: 'EMP-003', name_ar: 'خالد عبدالله العتيبي', basic_salary: 20000 },
  { id: 'EMP-005', name_ar: 'محمد حسن الدوسري', basic_salary: 16000 },
  { id: 'EMP-009', name_ar: 'عمر خالد السبيعي', basic_salary: 10000 },
];

export default function LoansPage() {
  const [loans, setLoans] = useState([]);
  const [loading, setLoading] = useState(true);
  const [filter, setFilter] = useState({ status: '', type: '' });
  const [searchTerm, setSearchTerm] = useState('');
  const [showNewLoanModal, setShowNewLoanModal] = useState(false);
  const [showDetailModal, setShowDetailModal] = useState(false);
  const [selectedLoan, setSelectedLoan] = useState(null);
  const [newLoan, setNewLoan] = useState({
    employee_id: '', type: 'loan', loan_amount: '', installments: 6, reason: ''
  });

  const statusColors = {
    pending: 'bg-yellow-100 text-yellow-800',
    approved: 'bg-blue-100 text-blue-800',
    rejected: 'bg-red-100 text-red-800',
    active: 'bg-green-100 text-green-800',
    completed: 'bg-purple-100 text-purple-800',
    cancelled: 'bg-gray-100 text-gray-800',
  };

  const statusLabels = {
    pending: 'قيد الانتظار', approved: 'معتمد', rejected: 'مرفوض',
    active: 'نشط', completed: 'مكتمل', cancelled: 'ملغي',
  };

  const typeLabels = { loan: 'سلفة', advance: 'سلفة راتب' };
  const typeColors = { loan: 'bg-blue-100 text-blue-700', advance: 'bg-orange-100 text-orange-700' };

  useEffect(() => {
    setLoading(true);
    setTimeout(() => {
      setLoans(mockLoans);
      setLoading(false);
    }, 400);
  }, []);

  const filteredLoans = loans.filter(loan => {
    if (filter.status && loan.status !== filter.status) return false;
    if (filter.type && loan.type !== filter.type) return false;
    if (searchTerm && !loan.employee.name_ar.includes(searchTerm) && !loan.loan_number.includes(searchTerm)) return false;
    return true;
  });

  const handleApprove = (id) => {
    if (!confirm('هل تريد الموافقة على هذه السلفة؟')) return;
    setLoans(prev => prev.map(l => l.id === id ? { ...l, status: 'active', approved_by: 'المستخدم الحالي' } : l));
  };

  const handleReject = (id) => {
    const reason = prompt('سبب الرفض:');
    if (!reason) return;
    setLoans(prev => prev.map(l => l.id === id ? { ...l, status: 'rejected', rejection_reason: reason } : l));
  };

  const handleCreateLoan = () => {
    if (!newLoan.employee_id || !newLoan.loan_amount || !newLoan.reason) {
      alert('الرجاء ملء جميع الحقول المطلوبة');
      return;
    }
    const employee = mockEmployees.find(e => e.id === newLoan.employee_id);
    const amount = parseFloat(newLoan.loan_amount);
    const installmentAmount = Math.ceil(amount / newLoan.installments);
    const newId = Math.max(...loans.map(l => l.id)) + 1;

    setLoans(prev => [...prev, {
      id: newId,
      loan_number: `LN-2026-${String(newId).padStart(3, '0')}`,
      type: newLoan.type,
      status: 'pending',
      employee: { name_ar: employee.name_ar, employee_number: employee.id, department: '-' },
      loan_amount: amount,
      installment_amount: installmentAmount,
      total_installments: newLoan.installments,
      paid_installments: 0,
      remaining_amount: amount,
      start_date: new Date().toISOString().split('T')[0],
      reason: newLoan.reason,
      approved_by: null
    }]);
    setShowNewLoanModal(false);
    setNewLoan({ employee_id: '', type: 'loan', loan_amount: '', installments: 6, reason: '' });
  };

  const formatCurrency = (amount) => {
    return new Intl.NumberFormat('ar-SA', {
      style: 'currency', currency: 'SAR', minimumFractionDigits: 0
    }).format(amount || 0);
  };

  const stats = {
    active: loans.filter(l => l.status === 'active'),
    pending: loans.filter(l => l.status === 'pending'),
    completed: loans.filter(l => l.status === 'completed'),
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
      <div className="flex justify-between items-center">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">السلف والقروض</h1>
          <p className="text-gray-600 mt-1">إدارة سلف وقروض الموظفين</p>
        </div>
        <button onClick={() => setShowNewLoanModal(true)}
          className="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm">
          + طلب سلفة جديدة
        </button>
      </div>

      {/* Summary Cards */}
      <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div className="bg-white rounded-lg shadow p-5">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm text-gray-600">السلف النشطة</p>
              <p className="text-2xl font-bold text-green-600">{stats.active.length}</p>
              <p className="text-xs text-gray-400 mt-1">
                {formatCurrency(stats.active.reduce((s, l) => s + l.remaining_amount, 0))} متبقي
              </p>
            </div>
            <div className="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
              <svg className="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
          </div>
        </div>

        <div className="bg-white rounded-lg shadow p-5">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm text-gray-600">قيد الانتظار</p>
              <p className="text-2xl font-bold text-yellow-600">{stats.pending.length}</p>
              <p className="text-xs text-gray-400 mt-1">
                {formatCurrency(stats.pending.reduce((s, l) => s + l.loan_amount, 0))} مطلوب
              </p>
            </div>
            <div className="w-12 h-12 bg-yellow-100 rounded-full flex items-center justify-center">
              <svg className="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
          </div>
        </div>

        <div className="bg-white rounded-lg shadow p-5">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm text-gray-600">إجمالي المبالغ النشطة</p>
              <p className="text-2xl font-bold text-blue-600">
                {formatCurrency(stats.active.reduce((s, l) => s + l.loan_amount, 0))}
              </p>
            </div>
            <div className="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
              <svg className="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
              </svg>
            </div>
          </div>
        </div>

        <div className="bg-white rounded-lg shadow p-5">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm text-gray-600">المكتملة</p>
              <p className="text-2xl font-bold text-purple-600">{stats.completed.length}</p>
            </div>
            <div className="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center">
              <svg className="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
          </div>
        </div>
      </div>

      {/* Filters */}
      <div className="bg-white rounded-lg shadow p-4 flex flex-wrap gap-4 items-center">
        <input type="text" placeholder="بحث بالاسم أو رقم السلفة..."
          value={searchTerm} onChange={(e) => setSearchTerm(e.target.value)}
          className="border border-gray-300 rounded-lg px-4 py-2 text-sm w-64" />
        <select value={filter.status} onChange={(e) => setFilter({ ...filter, status: e.target.value })}
          className="border border-gray-300 rounded-lg px-4 py-2 text-sm">
          <option value="">جميع الحالات</option>
          {Object.entries(statusLabels).map(([key, label]) => (
            <option key={key} value={key}>{label}</option>
          ))}
        </select>
        <select value={filter.type} onChange={(e) => setFilter({ ...filter, type: e.target.value })}
          className="border border-gray-300 rounded-lg px-4 py-2 text-sm">
          <option value="">جميع الأنواع</option>
          <option value="loan">سلفة</option>
          <option value="advance">سلفة راتب</option>
        </select>
      </div>

      {/* Loans Table */}
      <div className="bg-white rounded-lg shadow overflow-hidden">
        <div className="overflow-x-auto">
          <table className="min-w-full divide-y divide-gray-200">
            <thead className="bg-gray-50">
              <tr>
                <th className="px-4 py-3 text-right text-xs font-medium text-gray-500">رقم السلفة</th>
                <th className="px-4 py-3 text-right text-xs font-medium text-gray-500">الموظف</th>
                <th className="px-4 py-3 text-right text-xs font-medium text-gray-500">النوع</th>
                <th className="px-4 py-3 text-right text-xs font-medium text-gray-500">المبلغ</th>
                <th className="px-4 py-3 text-right text-xs font-medium text-gray-500">القسط</th>
                <th className="px-4 py-3 text-right text-xs font-medium text-gray-500">التقدم</th>
                <th className="px-4 py-3 text-right text-xs font-medium text-gray-500">المتبقي</th>
                <th className="px-4 py-3 text-right text-xs font-medium text-gray-500">الحالة</th>
                <th className="px-4 py-3 text-right text-xs font-medium text-gray-500">الإجراءات</th>
              </tr>
            </thead>
            <tbody className="bg-white divide-y divide-gray-200">
              {filteredLoans.length === 0 ? (
                <tr>
                  <td colSpan="9" className="px-6 py-12 text-center text-gray-500">
                    <svg className="w-12 h-12 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <p>لا توجد سلف لعرضها</p>
                  </td>
                </tr>
              ) : (
                filteredLoans.map(loan => (
                  <tr key={loan.id} className="hover:bg-gray-50">
                    <td className="px-4 py-4 text-sm font-mono text-gray-900">{loan.loan_number}</td>
                    <td className="px-4 py-4 text-sm">
                      <div className="font-medium text-gray-900">{loan.employee.name_ar}</div>
                      <div className="text-gray-500 text-xs">{loan.employee.employee_number}</div>
                    </td>
                    <td className="px-4 py-4">
                      <span className={`px-2 py-1 text-xs rounded-full ${typeColors[loan.type]}`}>
                        {typeLabels[loan.type]}
                      </span>
                    </td>
                    <td className="px-4 py-4 text-sm font-medium text-gray-900">{formatCurrency(loan.loan_amount)}</td>
                    <td className="px-4 py-4 text-sm text-gray-500">{formatCurrency(loan.installment_amount)}</td>
                    <td className="px-4 py-4 text-sm">
                      <div className="flex items-center gap-2">
                        <span className="text-xs text-gray-500">{loan.paid_installments}/{loan.total_installments}</span>
                        <div className="w-20 bg-gray-200 rounded-full h-2">
                          <div className="bg-green-600 h-2 rounded-full transition-all"
                            style={{ width: `${loan.total_installments > 0 ? (loan.paid_installments / loan.total_installments) * 100 : 0}%` }} />
                        </div>
                      </div>
                    </td>
                    <td className="px-4 py-4 text-sm font-medium text-red-600">{formatCurrency(loan.remaining_amount)}</td>
                    <td className="px-4 py-4">
                      <span className={`px-2 py-1 text-xs rounded-full ${statusColors[loan.status]}`}>
                        {statusLabels[loan.status]}
                      </span>
                    </td>
                    <td className="px-4 py-4 text-sm">
                      <div className="flex items-center gap-2">
                        <button onClick={() => { setSelectedLoan(loan); setShowDetailModal(true); }}
                          className="text-blue-600 hover:text-blue-900">عرض</button>
                        {loan.status === 'pending' && (
                          <>
                            <button onClick={() => handleApprove(loan.id)}
                              className="text-green-600 hover:text-green-900">موافقة</button>
                            <button onClick={() => handleReject(loan.id)}
                              className="text-red-600 hover:text-red-900">رفض</button>
                          </>
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

      {/* New Loan Modal */}
      {showNewLoanModal && (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
          <div className="bg-white rounded-xl shadow-2xl w-full max-w-lg" dir="rtl">
            <div className="p-6 border-b">
              <h2 className="text-lg font-bold">طلب سلفة جديدة</h2>
              <p className="text-sm text-gray-500 mt-1">تقديم طلب سلفة أو سلفة راتب</p>
            </div>
            <div className="p-6 space-y-4">
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">الموظف *</label>
                <select value={newLoan.employee_id} onChange={(e) => setNewLoan({ ...newLoan, employee_id: e.target.value })}
                  className="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                  <option value="">اختر الموظف</option>
                  {mockEmployees.map(emp => (
                    <option key={emp.id} value={emp.id}>{emp.name_ar} ({emp.id})</option>
                  ))}
                </select>
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">نوع السلفة *</label>
                <div className="grid grid-cols-2 gap-3">
                  <button onClick={() => setNewLoan({ ...newLoan, type: 'loan' })}
                    className={`p-3 rounded-lg border-2 text-center text-sm ${
                      newLoan.type === 'loan' ? 'border-blue-500 bg-blue-50' : 'border-gray-200'
                    }`}>
                    <span className="font-medium">سلفة</span>
                    <span className="block text-xs text-gray-500">أقساط شهرية</span>
                  </button>
                  <button onClick={() => setNewLoan({ ...newLoan, type: 'advance' })}
                    className={`p-3 rounded-lg border-2 text-center text-sm ${
                      newLoan.type === 'advance' ? 'border-orange-500 bg-orange-50' : 'border-gray-200'
                    }`}>
                    <span className="font-medium">سلفة راتب</span>
                    <span className="block text-xs text-gray-500">خصم من الراتب القادم</span>
                  </button>
                </div>
              </div>
              <div className="grid grid-cols-2 gap-4">
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">المبلغ (ريال) *</label>
                  <input type="number" value={newLoan.loan_amount}
                    onChange={(e) => setNewLoan({ ...newLoan, loan_amount: e.target.value })}
                    className="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" dir="ltr"
                    placeholder="0" />
                </div>
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">عدد الأقساط</label>
                  <select value={newLoan.installments}
                    onChange={(e) => setNewLoan({ ...newLoan, installments: Number(e.target.value) })}
                    className="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    {[1, 2, 3, 4, 6, 8, 10, 12].map(n => (
                      <option key={n} value={n}>{n} {n === 1 ? 'قسط' : 'أقساط'}</option>
                    ))}
                  </select>
                </div>
              </div>
              {newLoan.loan_amount && (
                <div className="bg-blue-50 rounded-lg p-3 text-sm">
                  <div className="flex justify-between">
                    <span className="text-blue-700">القسط الشهري:</span>
                    <span className="font-bold text-blue-800">
                      {formatCurrency(Math.ceil(parseFloat(newLoan.loan_amount) / newLoan.installments))}
                    </span>
                  </div>
                </div>
              )}
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">سبب الطلب *</label>
                <textarea value={newLoan.reason}
                  onChange={(e) => setNewLoan({ ...newLoan, reason: e.target.value })}
                  className="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" rows={3}
                  placeholder="اذكر سبب طلب السلفة..." />
              </div>
            </div>
            <div className="p-4 border-t flex justify-end gap-3">
              <button onClick={() => setShowNewLoanModal(false)}
                className="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 text-sm">إلغاء</button>
              <button onClick={handleCreateLoan}
                className="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm">تقديم الطلب</button>
            </div>
          </div>
        </div>
      )}

      {/* Detail Modal */}
      {showDetailModal && selectedLoan && (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
          <div className="bg-white rounded-xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto" dir="rtl">
            <div className="p-6 border-b flex justify-between items-center">
              <h2 className="text-lg font-bold">تفاصيل السلفة</h2>
              <span className={`px-3 py-1 text-xs rounded-full ${statusColors[selectedLoan.status]}`}>
                {statusLabels[selectedLoan.status]}
              </span>
            </div>
            <div className="p-6 space-y-4">
              <div className="grid grid-cols-2 gap-4 text-sm">
                <div className="bg-gray-50 p-3 rounded-lg">
                  <p className="text-gray-500 text-xs">رقم السلفة</p>
                  <p className="font-mono font-medium">{selectedLoan.loan_number}</p>
                </div>
                <div className="bg-gray-50 p-3 rounded-lg">
                  <p className="text-gray-500 text-xs">النوع</p>
                  <p><span className={`px-2 py-0.5 text-xs rounded-full ${typeColors[selectedLoan.type]}`}>
                    {typeLabels[selectedLoan.type]}
                  </span></p>
                </div>
                <div className="bg-gray-50 p-3 rounded-lg">
                  <p className="text-gray-500 text-xs">الموظف</p>
                  <p className="font-medium">{selectedLoan.employee.name_ar}</p>
                </div>
                <div className="bg-gray-50 p-3 rounded-lg">
                  <p className="text-gray-500 text-xs">القسم</p>
                  <p className="font-medium">{selectedLoan.employee.department}</p>
                </div>
              </div>

              <div className="border-t pt-4 space-y-3 text-sm">
                <div className="flex justify-between"><span className="text-gray-500">مبلغ السلفة</span><span className="font-bold">{formatCurrency(selectedLoan.loan_amount)}</span></div>
                <div className="flex justify-between"><span className="text-gray-500">القسط الشهري</span><span>{formatCurrency(selectedLoan.installment_amount)}</span></div>
                <div className="flex justify-between"><span className="text-gray-500">عدد الأقساط</span><span>{selectedLoan.paid_installments} / {selectedLoan.total_installments}</span></div>
                <div className="flex justify-between"><span className="text-gray-500">المتبقي</span><span className="font-bold text-red-600">{formatCurrency(selectedLoan.remaining_amount)}</span></div>
                <div className="flex justify-between"><span className="text-gray-500">تاريخ البداية</span><span>{selectedLoan.start_date}</span></div>
              </div>

              {/* Progress */}
              <div className="bg-gray-50 rounded-lg p-4">
                <div className="flex justify-between text-sm mb-2">
                  <span className="text-gray-500">نسبة السداد</span>
                  <span className="font-medium">
                    {selectedLoan.total_installments > 0
                      ? Math.round((selectedLoan.paid_installments / selectedLoan.total_installments) * 100)
                      : 0}%
                  </span>
                </div>
                <div className="w-full bg-gray-200 rounded-full h-3">
                  <div className="bg-green-600 h-3 rounded-full transition-all"
                    style={{ width: `${selectedLoan.total_installments > 0 ? (selectedLoan.paid_installments / selectedLoan.total_installments) * 100 : 0}%` }} />
                </div>
              </div>

              <div className="bg-blue-50 rounded-lg p-3">
                <p className="text-xs text-gray-500 mb-1">سبب الطلب</p>
                <p className="text-sm text-gray-800">{selectedLoan.reason}</p>
              </div>

              {selectedLoan.approved_by && (
                <div className="bg-green-50 rounded-lg p-3">
                  <p className="text-xs text-gray-500 mb-1">تمت الموافقة بواسطة</p>
                  <p className="text-sm text-green-800">{selectedLoan.approved_by}</p>
                </div>
              )}

              {selectedLoan.rejection_reason && (
                <div className="bg-red-50 rounded-lg p-3">
                  <p className="text-xs text-gray-500 mb-1">سبب الرفض</p>
                  <p className="text-sm text-red-800">{selectedLoan.rejection_reason}</p>
                </div>
              )}
            </div>
            <div className="p-4 border-t flex justify-end gap-3">
              <button onClick={() => setShowDetailModal(false)}
                className="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 text-sm">إغلاق</button>
              {selectedLoan.status === 'pending' && (
                <>
                  <button onClick={() => { handleReject(selectedLoan.id); setShowDetailModal(false); }}
                    className="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 text-sm">رفض</button>
                  <button onClick={() => { handleApprove(selectedLoan.id); setShowDetailModal(false); }}
                    className="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 text-sm">موافقة</button>
                </>
              )}
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
