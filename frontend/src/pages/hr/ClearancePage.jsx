import React, { useState, useEffect } from 'react';

// Clearance status flow: pending → finance_approved → hr_approved → it_approved → custody_cleared → completed
const CLEARANCE_STATUS = {
  pending: { label: 'قيد الانتظار', color: 'bg-yellow-100 text-yellow-700', icon: '⏳' },
  finance_approved: { label: 'موافقة المالية', color: 'bg-blue-100 text-blue-700', icon: '💰' },
  hr_approved: { label: 'موافقة الموارد البشرية', color: 'bg-indigo-100 text-indigo-700', icon: '👥' },
  it_approved: { label: 'موافقة تقنية المعلومات', color: 'bg-purple-100 text-purple-700', icon: '💻' },
  custody_cleared: { label: 'تسليم العهد', color: 'bg-orange-100 text-orange-700', icon: '📦' },
  completed: { label: 'مكتمل', color: 'bg-green-100 text-green-700', icon: '✅' },
  cancelled: { label: 'ملغي', color: 'bg-red-100 text-red-700', icon: '❌' },
};

const REASONS = {
  resignation: 'استقالة',
  termination: 'إنهاء خدمات',
  end_of_contract: 'انتهاء العقد',
  transfer: 'نقل',
  retirement: 'تقاعد',
};

const STEPS = [
  { key: 'pending', label: 'تقديم الطلب' },
  { key: 'finance_approved', label: 'المالية' },
  { key: 'hr_approved', label: 'الموارد البشرية' },
  { key: 'it_approved', label: 'تقنية المعلومات' },
  { key: 'custody_cleared', label: 'تسليم العهد' },
  { key: 'completed', label: 'مكتمل' },
];

const mockEmployees = [
  { id: '1', name: 'أحمد محمد الغامدي', department: 'الإدارة', position: 'مدير مشروع', employee_number: 'EMP-001' },
  { id: '2', name: 'فاطمة علي الزهراني', department: 'الموارد البشرية', position: 'أخصائية موارد بشرية', employee_number: 'EMP-002' },
  { id: '3', name: 'خالد عبدالله العتيبي', department: 'تقنية المعلومات', position: 'مطور أنظمة', employee_number: 'EMP-003' },
  { id: '4', name: 'نورة سعد القحطاني', department: 'المالية', position: 'محاسبة', employee_number: 'EMP-004' },
  { id: '5', name: 'محمد إبراهيم الدوسري', department: 'التمريض', position: 'ممرض أول', employee_number: 'EMP-005' },
  { id: '6', name: 'سارة يوسف المالكي', department: 'المختبر', position: 'فنية مختبر', employee_number: 'EMP-006' },
];

const mockClearances = [
  {
    id: '1', request_number: 'CLR-2026-001', employee_id: '1',
    employee: mockEmployees[0], reason: 'resignation',
    last_working_day: '2026-03-15', notes: 'استقالة بسبب فرصة عمل أفضل',
    status: 'completed',
    finance_approved_by: 'نورة القحطاني', finance_approved_at: '2026-02-10', finance_notes: 'لا يوجد مستحقات مالية معلقة',
    hr_approved_by: 'فاطمة الزهراني', hr_approved_at: '2026-02-11', hr_notes: 'تم احتساب رصيد الإجازات المتبقي',
    it_approved_by: 'خالد العتيبي', it_approved_at: '2026-02-12', it_notes: 'تم إلغاء صلاحيات الأنظمة',
    custody_cleared_by: 'محمد الشهري', custody_cleared_at: '2026-02-13', custody_notes: 'تم استلام جميع العهد',
    completed_by: 'فاطمة الزهراني', completed_at: '2026-02-14',
    final_settlement: 45000, end_of_service: 32000, leave_balance_amount: 8500, deductions: 2500,
    created_at: '2026-02-08',
  },
  {
    id: '2', request_number: 'CLR-2026-002', employee_id: '3',
    employee: mockEmployees[2], reason: 'transfer',
    last_working_day: '2026-04-01', notes: 'نقل إلى فرع جدة',
    status: 'it_approved',
    finance_approved_by: 'نورة القحطاني', finance_approved_at: '2026-02-15', finance_notes: 'تم تسوية جميع السلف',
    hr_approved_by: 'فاطمة الزهراني', hr_approved_at: '2026-02-16', hr_notes: 'تم تحديث ملف الموظف',
    it_approved_by: 'سعد المطيري', it_approved_at: '2026-02-17', it_notes: 'تم نقل الصلاحيات',
    custody_cleared_by: null, custody_cleared_at: null, custody_notes: null,
    completed_by: null, completed_at: null,
    final_settlement: null, end_of_service: null, leave_balance_amount: null, deductions: null,
    created_at: '2026-02-14',
  },
  {
    id: '3', request_number: 'CLR-2026-003', employee_id: '5',
    employee: mockEmployees[4], reason: 'end_of_contract',
    last_working_day: '2026-03-31', notes: 'انتهاء عقد سنتين',
    status: 'finance_approved',
    finance_approved_by: 'نورة القحطاني', finance_approved_at: '2026-02-18', finance_notes: 'تمت تسوية المستحقات',
    hr_approved_by: null, hr_approved_at: null, hr_notes: null,
    it_approved_by: null, it_approved_at: null, it_notes: null,
    custody_cleared_by: null, custody_cleared_at: null, custody_notes: null,
    completed_by: null, completed_at: null,
    final_settlement: 28000, end_of_service: 15000, leave_balance_amount: 4200, deductions: 0,
    created_at: '2026-02-17',
  },
  {
    id: '4', request_number: 'CLR-2026-004', employee_id: '4',
    employee: mockEmployees[3], reason: 'resignation',
    last_working_day: '2026-04-15', notes: '',
    status: 'pending',
    finance_approved_by: null, finance_approved_at: null, finance_notes: null,
    hr_approved_by: null, hr_approved_at: null, hr_notes: null,
    it_approved_by: null, it_approved_at: null, it_notes: null,
    custody_cleared_by: null, custody_cleared_at: null, custody_notes: null,
    completed_by: null, completed_at: null,
    final_settlement: null, end_of_service: null, leave_balance_amount: null, deductions: null,
    created_at: '2026-02-19',
  },
  {
    id: '5', request_number: 'CLR-2026-005', employee_id: '6',
    employee: mockEmployees[5], reason: 'termination',
    last_working_day: '2026-02-28', notes: 'إنهاء خدمات خلال فترة التجربة',
    status: 'cancelled',
    finance_approved_by: null, finance_approved_at: null, finance_notes: null,
    hr_approved_by: null, hr_approved_at: null, hr_notes: null,
    it_approved_by: null, it_approved_at: null, it_notes: null,
    custody_cleared_by: null, custody_cleared_at: null, custody_notes: null,
    completed_by: null, completed_at: null,
    final_settlement: null, end_of_service: null, leave_balance_amount: null, deductions: null,
    created_at: '2026-02-20',
  },
  {
    id: '6', request_number: 'CLR-2026-006', employee_id: '2',
    employee: mockEmployees[1], reason: 'retirement',
    last_working_day: '2026-06-30', notes: 'تقاعد مبكر بعد 25 سنة خدمة',
    status: 'hr_approved',
    finance_approved_by: 'نورة القحطاني', finance_approved_at: '2026-02-19', finance_notes: 'مستحقات نهاية خدمة كبيرة - يلزم موافقة المدير المالي',
    hr_approved_by: 'سارة العمري', hr_approved_at: '2026-02-20', hr_notes: 'تم احتساب مكافأة نهاية الخدمة لـ 25 سنة',
    it_approved_by: null, it_approved_at: null, it_notes: null,
    custody_cleared_by: null, custody_cleared_at: null, custody_notes: null,
    completed_by: null, completed_at: null,
    final_settlement: 120000, end_of_service: 95000, leave_balance_amount: 12000, deductions: 0,
    created_at: '2026-02-18',
  },
];

const mockCustodies = [
  { id: '1', employee_id: '1', item: 'لابتوب Dell Latitude', category: 'جهاز', status: 'returned' },
  { id: '2', employee_id: '1', item: 'بطاقة دخول المبنى', category: 'بطاقة', status: 'returned' },
  { id: '3', employee_id: '1', item: 'مفاتيح المكتب 205', category: 'مفتاح', status: 'returned' },
  { id: '4', employee_id: '3', item: 'لابتوب MacBook Pro', category: 'جهاز', status: 'assigned' },
  { id: '5', employee_id: '3', item: 'شاشة خارجية', category: 'معدات', status: 'assigned' },
  { id: '6', employee_id: '3', item: 'بطاقة دخول مركز البيانات', category: 'بطاقة', status: 'assigned' },
  { id: '7', employee_id: '5', item: 'جهاز قياس ضغط', category: 'معدات', status: 'assigned' },
  { id: '8', employee_id: '2', item: 'لابتوب HP ProBook', category: 'جهاز', status: 'assigned' },
  { id: '9', employee_id: '2', item: 'خزنة ملفات', category: 'معدات', status: 'assigned' },
];

const formatCurrency = (amount) => {
  if (amount == null) return '-';
  return new Intl.NumberFormat('ar-SA', { style: 'currency', currency: 'SAR', minimumFractionDigits: 0 }).format(amount);
};

export default function ClearancePage() {
  const [clearances, setClearances] = useState([]);
  const [loading, setLoading] = useState(true);
  const [search, setSearch] = useState('');
  const [statusFilter, setStatusFilter] = useState('all');
  const [reasonFilter, setReasonFilter] = useState('all');
  const [showCreateModal, setShowCreateModal] = useState(false);
  const [showDetailModal, setShowDetailModal] = useState(null);
  const [showApproveModal, setShowApproveModal] = useState(null);
  const [successMsg, setSuccessMsg] = useState('');

  // Create form state
  const [form, setForm] = useState({
    employee_id: '', reason: 'resignation', last_working_day: '', notes: '',
  });

  // Approval form state
  const [approvalNotes, setApprovalNotes] = useState('');
  const [settlementForm, setSettlementForm] = useState({
    final_settlement: '', end_of_service: '', leave_balance_amount: '', deductions: '',
  });

  useEffect(() => {
    setTimeout(() => { setClearances(mockClearances); setLoading(false); }, 400);
  }, []);

  const showSuccess = (msg) => {
    setSuccessMsg(msg);
    setTimeout(() => setSuccessMsg(''), 3000);
  };

  const getStepIndex = (status) => {
    if (status === 'cancelled') return -1;
    const idx = STEPS.findIndex(s => s.key === status);
    return idx >= 0 ? idx : 0;
  };

  const getNextAction = (status) => {
    switch (status) {
      case 'pending': return { label: 'موافقة المالية', nextStatus: 'finance_approved', dept: 'المالية' };
      case 'finance_approved': return { label: 'موافقة الموارد البشرية', nextStatus: 'hr_approved', dept: 'الموارد البشرية' };
      case 'hr_approved': return { label: 'موافقة تقنية المعلومات', nextStatus: 'it_approved', dept: 'تقنية المعلومات' };
      case 'it_approved': return { label: 'تأكيد تسليم العهد', nextStatus: 'custody_cleared', dept: 'العهد' };
      case 'custody_cleared': return { label: 'إكمال الإخلاء', nextStatus: 'completed', dept: 'الإدارة' };
      default: return null;
    }
  };

  const handleCreate = () => {
    if (!form.employee_id || !form.last_working_day) return;
    const emp = mockEmployees.find(e => e.id === form.employee_id);
    const newClearance = {
      id: String(clearances.length + 1),
      request_number: `CLR-2026-${String(clearances.length + 1).padStart(3, '0')}`,
      employee_id: form.employee_id,
      employee: emp,
      reason: form.reason,
      last_working_day: form.last_working_day,
      notes: form.notes,
      status: 'pending',
      finance_approved_by: null, finance_approved_at: null, finance_notes: null,
      hr_approved_by: null, hr_approved_at: null, hr_notes: null,
      it_approved_by: null, it_approved_at: null, it_notes: null,
      custody_cleared_by: null, custody_cleared_at: null, custody_notes: null,
      completed_by: null, completed_at: null,
      final_settlement: null, end_of_service: null, leave_balance_amount: null, deductions: null,
      created_at: new Date().toISOString().split('T')[0],
    };
    setClearances(prev => [newClearance, ...prev]);
    setShowCreateModal(false);
    setForm({ employee_id: '', reason: 'resignation', last_working_day: '', notes: '' });
    showSuccess('تم إنشاء طلب إخلاء الطرف بنجاح');
  };

  const handleApprove = (clearance) => {
    const action = getNextAction(clearance.status);
    if (!action) return;
    const today = new Date().toISOString().split('T')[0];
    const updated = clearances.map(c => {
      if (c.id !== clearance.id) return c;
      const upd = { ...c, status: action.nextStatus };
      if (action.nextStatus === 'finance_approved') {
        upd.finance_approved_by = 'المستخدم الحالي';
        upd.finance_approved_at = today;
        upd.finance_notes = approvalNotes;
        if (settlementForm.final_settlement) upd.final_settlement = parseFloat(settlementForm.final_settlement);
        if (settlementForm.end_of_service) upd.end_of_service = parseFloat(settlementForm.end_of_service);
        if (settlementForm.leave_balance_amount) upd.leave_balance_amount = parseFloat(settlementForm.leave_balance_amount);
        if (settlementForm.deductions) upd.deductions = parseFloat(settlementForm.deductions);
      } else if (action.nextStatus === 'hr_approved') {
        upd.hr_approved_by = 'المستخدم الحالي'; upd.hr_approved_at = today; upd.hr_notes = approvalNotes;
      } else if (action.nextStatus === 'it_approved') {
        upd.it_approved_by = 'المستخدم الحالي'; upd.it_approved_at = today; upd.it_notes = approvalNotes;
      } else if (action.nextStatus === 'custody_cleared') {
        upd.custody_cleared_by = 'المستخدم الحالي'; upd.custody_cleared_at = today; upd.custody_notes = approvalNotes;
      } else if (action.nextStatus === 'completed') {
        upd.completed_by = 'المستخدم الحالي'; upd.completed_at = today;
      }
      return upd;
    });
    setClearances(updated);
    setShowApproveModal(null);
    setApprovalNotes('');
    setSettlementForm({ final_settlement: '', end_of_service: '', leave_balance_amount: '', deductions: '' });
    showSuccess(`تم ${action.label} بنجاح`);
  };

  const handleCancel = (id) => {
    setClearances(prev => prev.map(c => c.id === id ? { ...c, status: 'cancelled' } : c));
    setShowDetailModal(null);
    showSuccess('تم إلغاء طلب إخلاء الطرف');
  };

  const filtered = clearances.filter(c => {
    const matchSearch = !search || c.employee?.name.includes(search) || c.request_number.includes(search);
    const matchStatus = statusFilter === 'all' || c.status === statusFilter;
    const matchReason = reasonFilter === 'all' || c.reason === reasonFilter;
    return matchSearch && matchStatus && matchReason;
  });

  const stats = {
    total: clearances.length,
    active: clearances.filter(c => !['completed', 'cancelled'].includes(c.status)).length,
    completed: clearances.filter(c => c.status === 'completed').length,
    cancelled: clearances.filter(c => c.status === 'cancelled').length,
  };

  const employeeCustodies = (employeeId) => mockCustodies.filter(c => c.employee_id === employeeId);

  if (loading) return <div className="flex items-center justify-center h-64"><div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div></div>;

  return (
    <div className="space-y-6" dir="rtl">
      {/* Header */}
      <div className="flex justify-between items-center">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">إخلاء الطرف</h1>
          <p className="text-gray-600 mt-1">إدارة طلبات إخلاء الطرف والموافقات متعددة المراحل</p>
        </div>
        <button onClick={() => setShowCreateModal(true)} className="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm flex items-center gap-2">
          <span>+</span> طلب إخلاء طرف جديد
        </button>
      </div>

      {successMsg && (
        <div className="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg text-sm">{successMsg}</div>
      )}

      {/* Stats */}
      <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div className="bg-white rounded-lg shadow p-4">
          <p className="text-xs text-gray-500">إجمالي الطلبات</p>
          <p className="text-2xl font-bold text-gray-800 mt-1">{stats.total}</p>
        </div>
        <div className="bg-white rounded-lg shadow p-4">
          <p className="text-xs text-gray-500">طلبات نشطة</p>
          <p className="text-2xl font-bold text-blue-600 mt-1">{stats.active}</p>
        </div>
        <div className="bg-white rounded-lg shadow p-4">
          <p className="text-xs text-gray-500">مكتملة</p>
          <p className="text-2xl font-bold text-green-600 mt-1">{stats.completed}</p>
        </div>
        <div className="bg-white rounded-lg shadow p-4">
          <p className="text-xs text-gray-500">ملغاة</p>
          <p className="text-2xl font-bold text-red-600 mt-1">{stats.cancelled}</p>
        </div>
      </div>

      {/* Filters */}
      <div className="bg-white rounded-lg shadow p-4">
        <div className="flex flex-wrap gap-3">
          <input type="text" placeholder="بحث بالاسم أو رقم الطلب..." value={search} onChange={e => setSearch(e.target.value)}
            className="flex-1 min-w-[200px] border rounded-lg px-3 py-2 text-sm" />
          <select value={statusFilter} onChange={e => setStatusFilter(e.target.value)} className="border rounded-lg px-3 py-2 text-sm">
            <option value="all">جميع الحالات</option>
            {Object.entries(CLEARANCE_STATUS).map(([k, v]) => <option key={k} value={k}>{v.label}</option>)}
          </select>
          <select value={reasonFilter} onChange={e => setReasonFilter(e.target.value)} className="border rounded-lg px-3 py-2 text-sm">
            <option value="all">جميع الأسباب</option>
            {Object.entries(REASONS).map(([k, v]) => <option key={k} value={k}>{v}</option>)}
          </select>
        </div>
      </div>

      {/* Clearance List */}
      <div className="space-y-4">
        {filtered.length === 0 ? (
          <div className="bg-white rounded-lg shadow p-12 text-center text-gray-500">
            <p className="text-4xl mb-4">📋</p>
            <p>لا توجد طلبات إخلاء طرف</p>
          </div>
        ) : (
          filtered.map(clearance => {
            const stepIdx = getStepIndex(clearance.status);
            const action = getNextAction(clearance.status);
            const custodies = employeeCustodies(clearance.employee_id);
            const unreturned = custodies.filter(c => c.status === 'assigned').length;

            return (
              <div key={clearance.id} className="bg-white rounded-lg shadow overflow-hidden">
                {/* Card Header */}
                <div className="p-5 border-b">
                  <div className="flex justify-between items-start">
                    <div className="flex gap-4">
                      <div className="w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center text-blue-700 font-bold text-lg">
                        {clearance.employee?.name.charAt(0)}
                      </div>
                      <div>
                        <h3 className="font-bold text-gray-800">{clearance.employee?.name}</h3>
                        <p className="text-sm text-gray-500">{clearance.employee?.position} - {clearance.employee?.department}</p>
                        <div className="flex items-center gap-3 mt-1">
                          <span className="text-xs text-gray-400">{clearance.request_number}</span>
                          <span className="text-xs text-gray-400">{clearance.employee?.employee_number}</span>
                        </div>
                      </div>
                    </div>
                    <div className="flex items-center gap-2">
                      <span className={`px-3 py-1 text-xs rounded-full ${CLEARANCE_STATUS[clearance.status]?.color}`}>
                        {CLEARANCE_STATUS[clearance.status]?.icon} {CLEARANCE_STATUS[clearance.status]?.label}
                      </span>
                      <span className="px-3 py-1 text-xs rounded-full bg-gray-100 text-gray-600">
                        {REASONS[clearance.reason]}
                      </span>
                    </div>
                  </div>
                </div>

                {/* Progress Steps */}
                {clearance.status !== 'cancelled' && (
                  <div className="px-5 py-4 bg-gray-50">
                    <div className="flex items-center justify-between">
                      {STEPS.map((step, idx) => {
                        const isActive = idx === stepIdx;
                        const isDone = idx < stepIdx || clearance.status === 'completed';
                        return (
                          <React.Fragment key={step.key}>
                            <div className="flex flex-col items-center gap-1">
                              <div className={`w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold
                                ${isDone ? 'bg-green-500 text-white' : isActive ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-500'}`}>
                                {isDone ? '✓' : idx + 1}
                              </div>
                              <span className={`text-xs ${isActive ? 'text-blue-600 font-medium' : isDone ? 'text-green-600' : 'text-gray-400'}`}>
                                {step.label}
                              </span>
                            </div>
                            {idx < STEPS.length - 1 && (
                              <div className={`flex-1 h-0.5 mx-1 ${idx < stepIdx || clearance.status === 'completed' ? 'bg-green-400' : 'bg-gray-200'}`} />
                            )}
                          </React.Fragment>
                        );
                      })}
                    </div>
                  </div>
                )}

                {/* Card Footer */}
                <div className="px-5 py-3 flex justify-between items-center border-t">
                  <div className="flex items-center gap-4 text-xs text-gray-500">
                    <span>آخر يوم عمل: <strong className="text-gray-700">{clearance.last_working_day}</strong></span>
                    {unreturned > 0 && (
                      <span className="text-orange-600">
                        {unreturned} عهدة غير مستردة
                      </span>
                    )}
                    {clearance.final_settlement != null && (
                      <span>التسوية: <strong className="text-gray-700">{formatCurrency(clearance.final_settlement)}</strong></span>
                    )}
                  </div>
                  <div className="flex gap-2">
                    <button onClick={() => setShowDetailModal(clearance)} className="px-3 py-1.5 text-xs bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200">
                      التفاصيل
                    </button>
                    {action && (
                      <button onClick={() => { setShowApproveModal(clearance); setApprovalNotes(''); }}
                        className="px-3 py-1.5 text-xs bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        {action.label}
                      </button>
                    )}
                  </div>
                </div>
              </div>
            );
          })
        )}
      </div>

      {/* Create Modal */}
      {showCreateModal && (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
          <div className="bg-white rounded-xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
            <div className="p-6 border-b">
              <div className="flex justify-between items-center">
                <h2 className="text-lg font-bold">طلب إخلاء طرف جديد</h2>
                <button onClick={() => setShowCreateModal(false)} className="text-gray-400 hover:text-gray-600 text-xl">&times;</button>
              </div>
            </div>
            <div className="p-6 space-y-4">
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">الموظف <span className="text-red-500">*</span></label>
                <select value={form.employee_id} onChange={e => setForm(f => ({ ...f, employee_id: e.target.value }))}
                  className="w-full border rounded-lg px-3 py-2 text-sm">
                  <option value="">اختر الموظف</option>
                  {mockEmployees.map(e => <option key={e.id} value={e.id}>{e.name} ({e.employee_number})</option>)}
                </select>
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">سبب الإخلاء <span className="text-red-500">*</span></label>
                <select value={form.reason} onChange={e => setForm(f => ({ ...f, reason: e.target.value }))}
                  className="w-full border rounded-lg px-3 py-2 text-sm">
                  {Object.entries(REASONS).map(([k, v]) => <option key={k} value={k}>{v}</option>)}
                </select>
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">آخر يوم عمل <span className="text-red-500">*</span></label>
                <input type="date" value={form.last_working_day} onChange={e => setForm(f => ({ ...f, last_working_day: e.target.value }))}
                  className="w-full border rounded-lg px-3 py-2 text-sm" dir="ltr" />
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">ملاحظات</label>
                <textarea value={form.notes} onChange={e => setForm(f => ({ ...f, notes: e.target.value }))}
                  className="w-full border rounded-lg px-3 py-2 text-sm" rows={3} />
              </div>

              {form.employee_id && (
                <div className="bg-gray-50 rounded-lg p-4">
                  <h4 className="text-sm font-bold text-gray-700 mb-2">عهد الموظف</h4>
                  {employeeCustodies(form.employee_id).length === 0 ? (
                    <p className="text-xs text-gray-500">لا توجد عهد مسجلة</p>
                  ) : (
                    <div className="space-y-2">
                      {employeeCustodies(form.employee_id).map(c => (
                        <div key={c.id} className="flex justify-between items-center text-xs">
                          <span>{c.item} ({c.category})</span>
                          <span className={c.status === 'returned' ? 'text-green-600' : 'text-orange-600'}>
                            {c.status === 'returned' ? 'مُستردة' : 'غير مُستردة'}
                          </span>
                        </div>
                      ))}
                    </div>
                  )}
                </div>
              )}
            </div>
            <div className="p-6 border-t flex justify-end gap-3">
              <button onClick={() => setShowCreateModal(false)} className="px-4 py-2 border rounded-lg text-sm hover:bg-gray-50">إلغاء</button>
              <button onClick={handleCreate} disabled={!form.employee_id || !form.last_working_day}
                className="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700 disabled:opacity-50">
                إنشاء الطلب
              </button>
            </div>
          </div>
        </div>
      )}

      {/* Detail Modal */}
      {showDetailModal && (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
          <div className="bg-white rounded-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
            <div className="p-6 border-b">
              <div className="flex justify-between items-center">
                <h2 className="text-lg font-bold">تفاصيل إخلاء الطرف</h2>
                <button onClick={() => setShowDetailModal(null)} className="text-gray-400 hover:text-gray-600 text-xl">&times;</button>
              </div>
            </div>
            <div className="p-6 space-y-6">
              {/* Employee Info */}
              <div className="bg-gray-50 rounded-lg p-4">
                <div className="flex items-center gap-4">
                  <div className="w-14 h-14 rounded-full bg-blue-100 flex items-center justify-center text-blue-700 font-bold text-xl">
                    {showDetailModal.employee?.name.charAt(0)}
                  </div>
                  <div>
                    <h3 className="font-bold text-gray-800">{showDetailModal.employee?.name}</h3>
                    <p className="text-sm text-gray-500">{showDetailModal.employee?.position} - {showDetailModal.employee?.department}</p>
                    <div className="flex gap-3 mt-1">
                      <span className="text-xs text-gray-400">{showDetailModal.request_number}</span>
                      <span className={`text-xs px-2 py-0.5 rounded-full ${CLEARANCE_STATUS[showDetailModal.status]?.color}`}>
                        {CLEARANCE_STATUS[showDetailModal.status]?.label}
                      </span>
                    </div>
                  </div>
                </div>
              </div>

              {/* Request Info */}
              <div className="grid grid-cols-2 gap-4">
                <div className="bg-white border rounded-lg p-3">
                  <p className="text-xs text-gray-500">السبب</p>
                  <p className="text-sm font-medium mt-1">{REASONS[showDetailModal.reason]}</p>
                </div>
                <div className="bg-white border rounded-lg p-3">
                  <p className="text-xs text-gray-500">آخر يوم عمل</p>
                  <p className="text-sm font-medium mt-1">{showDetailModal.last_working_day}</p>
                </div>
                <div className="bg-white border rounded-lg p-3">
                  <p className="text-xs text-gray-500">تاريخ الطلب</p>
                  <p className="text-sm font-medium mt-1">{showDetailModal.created_at}</p>
                </div>
                <div className="bg-white border rounded-lg p-3">
                  <p className="text-xs text-gray-500">ملاحظات</p>
                  <p className="text-sm font-medium mt-1">{showDetailModal.notes || 'لا توجد'}</p>
                </div>
              </div>

              {/* Approval Timeline */}
              <div>
                <h4 className="font-bold text-gray-800 mb-3">سجل الموافقات</h4>
                <div className="space-y-3">
                  {[
                    { label: 'المالية', by: showDetailModal.finance_approved_by, at: showDetailModal.finance_approved_at, notes: showDetailModal.finance_notes },
                    { label: 'الموارد البشرية', by: showDetailModal.hr_approved_by, at: showDetailModal.hr_approved_at, notes: showDetailModal.hr_notes },
                    { label: 'تقنية المعلومات', by: showDetailModal.it_approved_by, at: showDetailModal.it_approved_at, notes: showDetailModal.it_notes },
                    { label: 'تسليم العهد', by: showDetailModal.custody_cleared_by, at: showDetailModal.custody_cleared_at, notes: showDetailModal.custody_notes },
                    { label: 'إكمال الإخلاء', by: showDetailModal.completed_by, at: showDetailModal.completed_at, notes: null },
                  ].map((step, idx) => (
                    <div key={idx} className={`flex items-start gap-3 p-3 rounded-lg ${step.by ? 'bg-green-50 border border-green-100' : 'bg-gray-50 border border-gray-100'}`}>
                      <div className={`w-6 h-6 rounded-full flex items-center justify-center text-xs mt-0.5 ${step.by ? 'bg-green-500 text-white' : 'bg-gray-300 text-white'}`}>
                        {step.by ? '✓' : idx + 1}
                      </div>
                      <div className="flex-1">
                        <div className="flex justify-between items-center">
                          <span className="text-sm font-medium text-gray-800">{step.label}</span>
                          {step.at && <span className="text-xs text-gray-400">{step.at}</span>}
                        </div>
                        {step.by && <p className="text-xs text-gray-500 mt-0.5">بواسطة: {step.by}</p>}
                        {step.notes && <p className="text-xs text-gray-600 mt-1 bg-white p-2 rounded">{step.notes}</p>}
                        {!step.by && <p className="text-xs text-gray-400 mt-0.5">في الانتظار</p>}
                      </div>
                    </div>
                  ))}
                </div>
              </div>

              {/* Settlement */}
              {(showDetailModal.final_settlement != null || showDetailModal.end_of_service != null) && (
                <div>
                  <h4 className="font-bold text-gray-800 mb-3">المستحقات النهائية</h4>
                  <div className="grid grid-cols-2 gap-3">
                    <div className="bg-blue-50 p-3 rounded-lg">
                      <p className="text-xs text-blue-600">التسوية النهائية</p>
                      <p className="text-lg font-bold text-blue-800 mt-1">{formatCurrency(showDetailModal.final_settlement)}</p>
                    </div>
                    <div className="bg-green-50 p-3 rounded-lg">
                      <p className="text-xs text-green-600">مكافأة نهاية الخدمة</p>
                      <p className="text-lg font-bold text-green-800 mt-1">{formatCurrency(showDetailModal.end_of_service)}</p>
                    </div>
                    <div className="bg-indigo-50 p-3 rounded-lg">
                      <p className="text-xs text-indigo-600">رصيد الإجازات</p>
                      <p className="text-lg font-bold text-indigo-800 mt-1">{formatCurrency(showDetailModal.leave_balance_amount)}</p>
                    </div>
                    <div className="bg-red-50 p-3 rounded-lg">
                      <p className="text-xs text-red-600">الخصومات</p>
                      <p className="text-lg font-bold text-red-800 mt-1">{formatCurrency(showDetailModal.deductions)}</p>
                    </div>
                  </div>
                  {showDetailModal.final_settlement != null && (
                    <div className="mt-3 bg-gray-800 text-white p-4 rounded-lg flex justify-between items-center">
                      <span className="text-sm">صافي المستحقات</span>
                      <span className="text-xl font-bold">
                        {formatCurrency(
                          (showDetailModal.final_settlement || 0) + (showDetailModal.end_of_service || 0) +
                          (showDetailModal.leave_balance_amount || 0) - (showDetailModal.deductions || 0)
                        )}
                      </span>
                    </div>
                  )}
                </div>
              )}

              {/* Custodies */}
              <div>
                <h4 className="font-bold text-gray-800 mb-3">العهد</h4>
                {employeeCustodies(showDetailModal.employee_id).length === 0 ? (
                  <p className="text-sm text-gray-500 bg-gray-50 p-4 rounded-lg text-center">لا توجد عهد مسجلة لهذا الموظف</p>
                ) : (
                  <div className="border rounded-lg overflow-hidden">
                    <table className="w-full text-sm">
                      <thead className="bg-gray-50">
                        <tr>
                          <th className="text-right px-4 py-2 text-xs font-medium text-gray-500">العهدة</th>
                          <th className="text-right px-4 py-2 text-xs font-medium text-gray-500">التصنيف</th>
                          <th className="text-right px-4 py-2 text-xs font-medium text-gray-500">الحالة</th>
                        </tr>
                      </thead>
                      <tbody className="divide-y">
                        {employeeCustodies(showDetailModal.employee_id).map(c => (
                          <tr key={c.id}>
                            <td className="px-4 py-2 text-gray-800">{c.item}</td>
                            <td className="px-4 py-2 text-gray-500">{c.category}</td>
                            <td className="px-4 py-2">
                              <span className={`px-2 py-0.5 text-xs rounded-full ${c.status === 'returned' ? 'bg-green-100 text-green-700' : 'bg-orange-100 text-orange-700'}`}>
                                {c.status === 'returned' ? 'مُستردة' : 'غير مُستردة'}
                              </span>
                            </td>
                          </tr>
                        ))}
                      </tbody>
                    </table>
                  </div>
                )}
              </div>
            </div>
            <div className="p-6 border-t flex justify-between">
              {!['completed', 'cancelled'].includes(showDetailModal.status) && (
                <button onClick={() => handleCancel(showDetailModal.id)} className="px-4 py-2 text-red-600 border border-red-200 rounded-lg text-sm hover:bg-red-50">
                  إلغاء الطلب
                </button>
              )}
              <div className="flex-1" />
              <button onClick={() => setShowDetailModal(null)} className="px-4 py-2 border rounded-lg text-sm hover:bg-gray-50">إغلاق</button>
            </div>
          </div>
        </div>
      )}

      {/* Approve Modal */}
      {showApproveModal && (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
          <div className="bg-white rounded-xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
            <div className="p-6 border-b bg-blue-50">
              <div className="flex justify-between items-center">
                <h2 className="text-lg font-bold text-blue-800">{getNextAction(showApproveModal.status)?.label}</h2>
                <button onClick={() => setShowApproveModal(null)} className="text-gray-400 hover:text-gray-600 text-xl">&times;</button>
              </div>
              <p className="text-sm text-blue-600 mt-1">
                {showApproveModal.employee?.name} - {showApproveModal.request_number}
              </p>
            </div>
            <div className="p-6 space-y-4">
              {/* Settlement fields for finance step */}
              {showApproveModal.status === 'pending' && (
                <div className="space-y-3">
                  <h4 className="text-sm font-bold text-gray-700">المستحقات المالية</h4>
                  <div className="grid grid-cols-2 gap-3">
                    <div>
                      <label className="block text-xs text-gray-500 mb-1">التسوية النهائية</label>
                      <input type="number" value={settlementForm.final_settlement}
                        onChange={e => setSettlementForm(f => ({ ...f, final_settlement: e.target.value }))}
                        className="w-full border rounded-lg px-3 py-2 text-sm" dir="ltr" placeholder="0.00" />
                    </div>
                    <div>
                      <label className="block text-xs text-gray-500 mb-1">مكافأة نهاية الخدمة</label>
                      <input type="number" value={settlementForm.end_of_service}
                        onChange={e => setSettlementForm(f => ({ ...f, end_of_service: e.target.value }))}
                        className="w-full border rounded-lg px-3 py-2 text-sm" dir="ltr" placeholder="0.00" />
                    </div>
                    <div>
                      <label className="block text-xs text-gray-500 mb-1">رصيد الإجازات</label>
                      <input type="number" value={settlementForm.leave_balance_amount}
                        onChange={e => setSettlementForm(f => ({ ...f, leave_balance_amount: e.target.value }))}
                        className="w-full border rounded-lg px-3 py-2 text-sm" dir="ltr" placeholder="0.00" />
                    </div>
                    <div>
                      <label className="block text-xs text-gray-500 mb-1">الخصومات</label>
                      <input type="number" value={settlementForm.deductions}
                        onChange={e => setSettlementForm(f => ({ ...f, deductions: e.target.value }))}
                        className="w-full border rounded-lg px-3 py-2 text-sm" dir="ltr" placeholder="0.00" />
                    </div>
                  </div>
                </div>
              )}

              {/* Custody check for custody step */}
              {showApproveModal.status === 'it_approved' && (
                <div className="bg-orange-50 border border-orange-200 rounded-lg p-4">
                  <h4 className="text-sm font-bold text-orange-800 mb-2">العهد المطلوب استردادها</h4>
                  {employeeCustodies(showApproveModal.employee_id).filter(c => c.status === 'assigned').length === 0 ? (
                    <p className="text-xs text-green-600">جميع العهد مُستردة</p>
                  ) : (
                    <div className="space-y-2">
                      {employeeCustodies(showApproveModal.employee_id).filter(c => c.status === 'assigned').map(c => (
                        <div key={c.id} className="flex justify-between items-center text-xs bg-white p-2 rounded">
                          <span>{c.item}</span>
                          <span className="text-orange-600">غير مُستردة</span>
                        </div>
                      ))}
                    </div>
                  )}
                </div>
              )}

              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">ملاحظات الموافقة</label>
                <textarea value={approvalNotes} onChange={e => setApprovalNotes(e.target.value)}
                  className="w-full border rounded-lg px-3 py-2 text-sm" rows={3} placeholder="أدخل ملاحظاتك هنا..." />
              </div>
            </div>
            <div className="p-6 border-t flex justify-end gap-3">
              <button onClick={() => setShowApproveModal(null)} className="px-4 py-2 border rounded-lg text-sm hover:bg-gray-50">إلغاء</button>
              <button onClick={() => handleApprove(showApproveModal)}
                className="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700">
                تأكيد الموافقة
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
