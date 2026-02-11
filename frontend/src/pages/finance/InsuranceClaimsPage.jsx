import React, { useState, useEffect, useMemo } from 'react';
import { insuranceClaimsApi } from '../../services/financeApi';
import { Button, LoadingSpinner, Modal, EmptyState } from '../../components/ui';

/**
 * صفحة مطالبات التأمين
 * Insurance Claims Page - Claims management with scrubbing and approval workflow
 */
export default function InsuranceClaimsPage() {
  // State
  const [claims, setClaims] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [searchQuery, setSearchQuery] = useState('');
  const [filterInsurance, setFilterInsurance] = useState('all');
  const [filterStatus, setFilterStatus] = useState('all');
  const [dateRange, setDateRange] = useState({ from: '', to: '' });

  // Modal states
  const [showDetailsModal, setShowDetailsModal] = useState(false);
  const [showActionModal, setShowActionModal] = useState(false);
  const [selectedClaim, setSelectedClaim] = useState(null);
  const [actionType, setActionType] = useState(null);
  const [actionData, setActionData] = useState({ notes: '', amount: '' });
  const [saving, setSaving] = useState(false);

  // Insurance companies
  const insuranceCompanies = [
    { id: 1, name: 'التعاونية للتأمين' },
    { id: 2, name: 'بوبا العربية' },
    { id: 3, name: 'ميدغلف' },
    { id: 4, name: 'تكافل الراجحي' },
    { id: 5, name: 'AXA' }
  ];

  // Mock data
  const mockClaims = [
    {
      id: 1,
      claim_number: 'CLM-2025-0001',
      patient_name: 'محمد أحمد السعيد',
      patient_id: 'P001',
      insurance_id: 1,
      insurance_name: 'التعاونية للتأمين',
      policy_number: 'POL-123456',
      doctor_name: 'د. أحمد الشمري',
      service_date: '2025-01-15',
      submission_date: '2025-01-16',
      total_amount: 1500,
      approved_amount: null,
      patient_share: 150,
      insurance_share: 1350,
      status: 'submitted',
      services: [
        { name: 'كشف طبيب باطنية', amount: 150 },
        { name: 'تحليل CBC', amount: 80 },
        { name: 'أشعة صدر', amount: 200 },
        { name: 'أدوية', amount: 1070 }
      ],
      scrub_errors: [],
      notes: ''
    },
    {
      id: 2,
      claim_number: 'CLM-2025-0002',
      patient_name: 'فاطمة علي الحربي',
      patient_id: 'P002',
      insurance_id: 2,
      insurance_name: 'بوبا العربية',
      policy_number: 'POL-234567',
      doctor_name: 'د. فاطمة الحربي',
      service_date: '2025-01-14',
      submission_date: '2025-01-15',
      total_amount: 850,
      approved_amount: 800,
      patient_share: 85,
      insurance_share: 715,
      status: 'approved',
      services: [
        { name: 'كشف طبيب أطفال', amount: 150 },
        { name: 'تطعيم', amount: 200 },
        { name: 'أدوية', amount: 500 }
      ],
      scrub_errors: [],
      notes: 'تمت الموافقة مع خصم 50 ريال للتطعيم'
    },
    {
      id: 3,
      claim_number: 'CLM-2025-0003',
      patient_name: 'خالد سعود العتيبي',
      patient_id: 'P003',
      insurance_id: 1,
      insurance_name: 'التعاونية للتأمين',
      policy_number: 'POL-345678',
      doctor_name: 'د. خالد العتيبي',
      service_date: '2025-01-13',
      submission_date: '2025-01-14',
      total_amount: 5000,
      approved_amount: null,
      patient_share: 500,
      insurance_share: 4500,
      status: 'scrubbed',
      services: [
        { name: 'عملية جراحية', amount: 4000 },
        { name: 'تخدير', amount: 500 },
        { name: 'أدوية', amount: 500 }
      ],
      scrub_errors: [
        { code: 'E001', message: 'رقم البوليصة غير مطابق' }
      ],
      notes: ''
    },
    {
      id: 4,
      claim_number: 'CLM-2025-0004',
      patient_name: 'نورة عبدالله القحطاني',
      patient_id: 'P004',
      insurance_id: 3,
      insurance_name: 'ميدغلف',
      policy_number: 'POL-456789',
      doctor_name: 'د. نورة القحطاني',
      service_date: '2025-01-12',
      submission_date: '2025-01-13',
      total_amount: 2200,
      approved_amount: null,
      patient_share: 220,
      insurance_share: 1980,
      status: 'rejected',
      services: [
        { name: 'كشف نساء وولادة', amount: 200 },
        { name: 'سونار', amount: 300 },
        { name: 'تحاليل', amount: 700 },
        { name: 'أدوية', amount: 1000 }
      ],
      scrub_errors: [],
      rejection_reason: 'الخدمة غير مغطاة بالبوليصة',
      notes: ''
    },
    {
      id: 5,
      claim_number: 'CLM-2025-0005',
      patient_name: 'عبدالله محمد السبيعي',
      patient_id: 'P005',
      insurance_id: 4,
      insurance_name: 'تكافل الراجحي',
      policy_number: 'POL-567890',
      doctor_name: 'د. محمد السبيعي',
      service_date: '2025-01-10',
      submission_date: '2025-01-11',
      total_amount: 3500,
      approved_amount: 3500,
      patient_share: 350,
      insurance_share: 3150,
      status: 'paid',
      payment_date: '2025-01-20',
      payment_reference: 'PAY-2025-001',
      services: [
        { name: 'قسطرة قلب', amount: 3000 },
        { name: 'أدوية', amount: 500 }
      ],
      scrub_errors: [],
      notes: ''
    }
  ];

  // Load claims
  useEffect(() => {
    loadClaims();
  }, []);

  const loadClaims = async () => {
    try {
      setLoading(true);
      setError(null);

      setTimeout(() => {
        setClaims(mockClaims);
        setLoading(false);
      }, 500);
    } catch (err) {
      setError('فشل في تحميل المطالبات');
      setLoading(false);
    }
  };

  // Filter claims
  const filteredClaims = useMemo(() => {
    return claims.filter(claim => {
      const matchesSearch = !searchQuery ||
        claim.claim_number.toLowerCase().includes(searchQuery.toLowerCase()) ||
        claim.patient_name.toLowerCase().includes(searchQuery.toLowerCase()) ||
        claim.patient_id.toLowerCase().includes(searchQuery.toLowerCase());
      const matchesInsurance = filterInsurance === 'all' ||
        claim.insurance_id.toString() === filterInsurance;
      const matchesStatus = filterStatus === 'all' || claim.status === filterStatus;

      return matchesSearch && matchesInsurance && matchesStatus;
    });
  }, [claims, searchQuery, filterInsurance, filterStatus]);

  // Statistics
  const stats = useMemo(() => {
    const total = claims.length;
    const totalAmount = claims.reduce((sum, c) => sum + c.total_amount, 0);
    const pending = claims.filter(c => ['submitted', 'scrubbed'].includes(c.status)).length;
    const approved = claims.filter(c => c.status === 'approved').length;
    const paid = claims.filter(c => c.status === 'paid').length;
    const rejected = claims.filter(c => c.status === 'rejected').length;
    const paidAmount = claims.filter(c => c.status === 'paid').reduce((sum, c) => sum + (c.approved_amount || 0), 0);

    return { total, totalAmount, pending, approved, paid, rejected, paidAmount };
  }, [claims]);

  // Handle action
  const handleAction = async () => {
    if (!selectedClaim || !actionType) return;

    try {
      setSaving(true);

      setTimeout(() => {
        let updatedClaim = { ...selectedClaim };

        switch (actionType) {
          case 'scrub':
            updatedClaim.status = 'scrubbed';
            break;
          case 'approve':
            updatedClaim.status = 'approved';
            updatedClaim.approved_amount = parseFloat(actionData.amount) || updatedClaim.total_amount;
            updatedClaim.notes = actionData.notes;
            break;
          case 'reject':
            updatedClaim.status = 'rejected';
            updatedClaim.rejection_reason = actionData.notes;
            break;
          case 'mark_paid':
            updatedClaim.status = 'paid';
            updatedClaim.payment_date = new Date().toISOString().split('T')[0];
            updatedClaim.payment_reference = actionData.notes;
            break;
          case 'resubmit':
            updatedClaim.status = 'submitted';
            updatedClaim.scrub_errors = [];
            break;
        }

        setClaims(claims.map(c =>
          c.id === selectedClaim.id ? updatedClaim : c
        ));

        setShowActionModal(false);
        setSelectedClaim(null);
        setActionType(null);
        setActionData({ notes: '', amount: '' });
        setSaving(false);
      }, 500);
    } catch (err) {
      setError('فشل في تنفيذ الإجراء');
      setSaving(false);
    }
  };

  // Get status badge
  const getStatusBadge = (status) => {
    const statusConfig = {
      draft: { label: 'مسودة', color: 'bg-gray-100 text-gray-800' },
      submitted: { label: 'مقدمة', color: 'bg-blue-100 text-blue-800' },
      scrubbed: { label: 'تحتاج مراجعة', color: 'bg-yellow-100 text-yellow-800' },
      approved: { label: 'معتمدة', color: 'bg-green-100 text-green-800' },
      rejected: { label: 'مرفوضة', color: 'bg-red-100 text-red-800' },
      paid: { label: 'مدفوعة', color: 'bg-purple-100 text-purple-800' }
    };

    const config = statusConfig[status] || { label: status, color: 'bg-gray-100 text-gray-800' };

    return (
      <span className={`px-2 py-1 text-xs font-medium rounded-full ${config.color}`}>
        {config.label}
      </span>
    );
  };

  // Open action modal
  const openActionModal = (claim, type) => {
    setSelectedClaim(claim);
    setActionType(type);
    setActionData({
      notes: '',
      amount: claim.total_amount?.toString() || ''
    });
    setShowActionModal(true);
  };

  // Get action modal config
  const getActionConfig = () => {
    const configs = {
      scrub: {
        title: 'فحص المطالبة',
        message: 'سيتم فحص المطالبة للتحقق من صحة البيانات',
        confirmText: 'فحص',
        showAmount: false,
        showNotes: false
      },
      approve: {
        title: 'اعتماد المطالبة',
        message: 'أدخل المبلغ المعتمد وأي ملاحظات',
        confirmText: 'اعتماد',
        showAmount: true,
        showNotes: true,
        notesLabel: 'ملاحظات'
      },
      reject: {
        title: 'رفض المطالبة',
        message: 'يرجى تحديد سبب الرفض',
        confirmText: 'رفض',
        showAmount: false,
        showNotes: true,
        notesLabel: 'سبب الرفض',
        notesRequired: true
      },
      mark_paid: {
        title: 'تأكيد الدفع',
        message: 'أدخل رقم مرجع الدفع',
        confirmText: 'تأكيد الدفع',
        showAmount: false,
        showNotes: true,
        notesLabel: 'رقم المرجع'
      },
      resubmit: {
        title: 'إعادة تقديم المطالبة',
        message: 'سيتم إعادة تقديم المطالبة بعد تصحيح الأخطاء',
        confirmText: 'إعادة تقديم',
        showAmount: false,
        showNotes: false
      }
    };

    return configs[actionType] || {};
  };

  // Format currency
  const formatCurrency = (amount) => {
    return new Intl.NumberFormat('ar-SA', {
      style: 'currency',
      currency: 'SAR',
      minimumFractionDigits: 0
    }).format(amount || 0);
  };

  // Format date
  const formatDate = (dateString) => {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleDateString('ar-SA');
  };

  if (loading && claims.length === 0) {
    return (
      <div className="flex items-center justify-center min-h-[400px]">
        <LoadingSpinner size="lg" />
      </div>
    );
  }

  return (
    <div className="space-y-6" dir="rtl">
      {/* Header */}
      <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">مطالبات التأمين</h1>
          <p className="text-gray-600 mt-1">إدارة المطالبات والفحص والتحصيل</p>
        </div>
        <div className="flex gap-2">
          <Button variant="secondary" onClick={() => {/* Export */}}>
            📥 تصدير
          </Button>
          <Button variant="secondary" onClick={() => {/* Batch submit */}}>
            📤 إرسال دفعة
          </Button>
        </div>
      </div>

      {/* Statistics Cards */}
      <div className="grid grid-cols-2 md:grid-cols-6 gap-4">
        <div className="bg-white rounded-lg shadow p-4 text-center">
          <div className="text-3xl font-bold text-gray-900">{stats.total}</div>
          <div className="text-sm text-gray-600">إجمالي المطالبات</div>
        </div>
        <div className="bg-white rounded-lg shadow p-4 text-center">
          <div className="text-2xl font-bold text-blue-600">{formatCurrency(stats.totalAmount)}</div>
          <div className="text-sm text-gray-600">إجمالي المبلغ</div>
        </div>
        <div className="bg-white rounded-lg shadow p-4 text-center">
          <div className="text-3xl font-bold text-yellow-600">{stats.pending}</div>
          <div className="text-sm text-gray-600">قيد المعالجة</div>
        </div>
        <div className="bg-white rounded-lg shadow p-4 text-center">
          <div className="text-3xl font-bold text-green-600">{stats.approved}</div>
          <div className="text-sm text-gray-600">معتمدة</div>
        </div>
        <div className="bg-white rounded-lg shadow p-4 text-center">
          <div className="text-3xl font-bold text-purple-600">{stats.paid}</div>
          <div className="text-sm text-gray-600">مدفوعة</div>
        </div>
        <div className="bg-white rounded-lg shadow p-4 text-center">
          <div className="text-3xl font-bold text-red-600">{stats.rejected}</div>
          <div className="text-sm text-gray-600">مرفوضة</div>
        </div>
      </div>

      {/* Filters */}
      <div className="bg-white rounded-lg shadow p-4">
        <div className="flex flex-col md:flex-row gap-4">
          <input
            type="text"
            placeholder="بحث برقم المطالبة أو اسم المريض..."
            value={searchQuery}
            onChange={(e) => setSearchQuery(e.target.value)}
            className="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
          />
          <select
            value={filterInsurance}
            onChange={(e) => setFilterInsurance(e.target.value)}
            className="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
          >
            <option value="all">جميع شركات التأمين</option>
            {insuranceCompanies.map(ins => (
              <option key={ins.id} value={ins.id}>{ins.name}</option>
            ))}
          </select>
          <select
            value={filterStatus}
            onChange={(e) => setFilterStatus(e.target.value)}
            className="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
          >
            <option value="all">جميع الحالات</option>
            <option value="submitted">مقدمة</option>
            <option value="scrubbed">تحتاج مراجعة</option>
            <option value="approved">معتمدة</option>
            <option value="rejected">مرفوضة</option>
            <option value="paid">مدفوعة</option>
          </select>
        </div>
      </div>

      {/* Error Message */}
      {error && (
        <div className="bg-red-50 text-red-600 p-4 rounded-lg">
          {error}
        </div>
      )}

      {/* Claims Table */}
      <div className="bg-white rounded-lg shadow overflow-hidden">
        {filteredClaims.length === 0 ? (
          <EmptyState
            title="لا توجد مطالبات"
            description="لم يتم العثور على مطالبات مطابقة للبحث"
            icon="📋"
          />
        ) : (
          <div className="overflow-x-auto">
            <table className="min-w-full divide-y divide-gray-200">
              <thead className="bg-gray-50">
                <tr>
                  <th className="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">رقم المطالبة</th>
                  <th className="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">المريض</th>
                  <th className="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">شركة التأمين</th>
                  <th className="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">تاريخ الخدمة</th>
                  <th className="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">المبلغ</th>
                  <th className="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">الحالة</th>
                  <th className="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">إجراءات</th>
                </tr>
              </thead>
              <tbody className="bg-white divide-y divide-gray-200">
                {filteredClaims.map((claim) => (
                  <tr key={claim.id} className="hover:bg-gray-50">
                    <td className="px-4 py-4 whitespace-nowrap">
                      <span className="font-mono text-sm bg-gray-100 px-2 py-1 rounded">
                        {claim.claim_number}
                      </span>
                    </td>
                    <td className="px-4 py-4 whitespace-nowrap">
                      <div>
                        <div className="font-medium text-gray-900">{claim.patient_name}</div>
                        <div className="text-sm text-gray-500">{claim.patient_id}</div>
                      </div>
                    </td>
                    <td className="px-4 py-4 whitespace-nowrap">
                      <div>
                        <div className="text-sm">{claim.insurance_name}</div>
                        <div className="text-xs text-gray-500">{claim.policy_number}</div>
                      </div>
                    </td>
                    <td className="px-4 py-4 whitespace-nowrap text-sm">
                      {formatDate(claim.service_date)}
                    </td>
                    <td className="px-4 py-4 whitespace-nowrap">
                      <div>
                        <div className="font-medium text-green-600">{formatCurrency(claim.total_amount)}</div>
                        {claim.approved_amount && claim.approved_amount !== claim.total_amount && (
                          <div className="text-xs text-gray-500">
                            معتمد: {formatCurrency(claim.approved_amount)}
                          </div>
                        )}
                      </div>
                    </td>
                    <td className="px-4 py-4 whitespace-nowrap text-center">
                      <div className="flex flex-col items-center gap-1">
                        {getStatusBadge(claim.status)}
                        {claim.scrub_errors?.length > 0 && (
                          <span className="text-xs text-red-600">
                            {claim.scrub_errors.length} خطأ
                          </span>
                        )}
                      </div>
                    </td>
                    <td className="px-4 py-4 whitespace-nowrap text-center">
                      <div className="flex justify-center gap-1">
                        <Button
                          variant="ghost"
                          size="sm"
                          onClick={() => {
                            setSelectedClaim(claim);
                            setShowDetailsModal(true);
                          }}
                        >
                          👁️
                        </Button>

                        {claim.status === 'submitted' && (
                          <Button
                            variant="ghost"
                            size="sm"
                            onClick={() => openActionModal(claim, 'scrub')}
                          >
                            🔍
                          </Button>
                        )}

                        {claim.status === 'scrubbed' && (
                          <>
                            <Button
                              variant="ghost"
                              size="sm"
                              onClick={() => openActionModal(claim, 'approve')}
                            >
                              ✅
                            </Button>
                            <Button
                              variant="ghost"
                              size="sm"
                              onClick={() => openActionModal(claim, 'reject')}
                            >
                              ❌
                            </Button>
                            <Button
                              variant="ghost"
                              size="sm"
                              onClick={() => openActionModal(claim, 'resubmit')}
                            >
                              🔄
                            </Button>
                          </>
                        )}

                        {claim.status === 'approved' && (
                          <Button
                            variant="ghost"
                            size="sm"
                            onClick={() => openActionModal(claim, 'mark_paid')}
                          >
                            💰
                          </Button>
                        )}
                      </div>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </div>

      {/* Details Modal */}
      <Modal
        isOpen={showDetailsModal}
        onClose={() => setShowDetailsModal(false)}
        title={`تفاصيل المطالبة ${selectedClaim?.claim_number}`}
        size="lg"
      >
        {selectedClaim && (
          <div className="space-y-6">
            {/* Patient & Insurance Info */}
            <div className="grid grid-cols-2 gap-4">
              <div className="bg-blue-50 p-4 rounded-lg">
                <h4 className="font-medium text-blue-800 mb-2">معلومات المريض</h4>
                <div className="space-y-1 text-sm">
                  <div><span className="text-gray-500">الاسم:</span> {selectedClaim.patient_name}</div>
                  <div><span className="text-gray-500">الرقم:</span> {selectedClaim.patient_id}</div>
                  <div><span className="text-gray-500">الطبيب:</span> {selectedClaim.doctor_name}</div>
                </div>
              </div>
              <div className="bg-green-50 p-4 rounded-lg">
                <h4 className="font-medium text-green-800 mb-2">معلومات التأمين</h4>
                <div className="space-y-1 text-sm">
                  <div><span className="text-gray-500">الشركة:</span> {selectedClaim.insurance_name}</div>
                  <div><span className="text-gray-500">البوليصة:</span> {selectedClaim.policy_number}</div>
                  <div><span className="text-gray-500">تاريخ الخدمة:</span> {formatDate(selectedClaim.service_date)}</div>
                </div>
              </div>
            </div>

            {/* Services */}
            <div>
              <h4 className="font-medium text-gray-900 mb-2">الخدمات</h4>
              <div className="border rounded-lg overflow-hidden">
                <table className="min-w-full divide-y divide-gray-200">
                  <thead className="bg-gray-50">
                    <tr>
                      <th className="px-4 py-2 text-right text-xs font-medium text-gray-500">الخدمة</th>
                      <th className="px-4 py-2 text-left text-xs font-medium text-gray-500">المبلغ</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-gray-200">
                    {selectedClaim.services?.map((service, index) => (
                      <tr key={index}>
                        <td className="px-4 py-2 text-sm">{service.name}</td>
                        <td className="px-4 py-2 text-sm text-left">{formatCurrency(service.amount)}</td>
                      </tr>
                    ))}
                  </tbody>
                  <tfoot className="bg-gray-50">
                    <tr>
                      <td className="px-4 py-2 font-medium">الإجمالي</td>
                      <td className="px-4 py-2 font-medium text-left text-green-600">
                        {formatCurrency(selectedClaim.total_amount)}
                      </td>
                    </tr>
                  </tfoot>
                </table>
              </div>
            </div>

            {/* Financial Summary */}
            <div className="grid grid-cols-3 gap-4">
              <div className="bg-gray-50 p-3 rounded-lg text-center">
                <div className="text-lg font-bold text-gray-900">{formatCurrency(selectedClaim.total_amount)}</div>
                <div className="text-xs text-gray-500">إجمالي المطالبة</div>
              </div>
              <div className="bg-gray-50 p-3 rounded-lg text-center">
                <div className="text-lg font-bold text-blue-600">{formatCurrency(selectedClaim.patient_share)}</div>
                <div className="text-xs text-gray-500">حصة المريض</div>
              </div>
              <div className="bg-gray-50 p-3 rounded-lg text-center">
                <div className="text-lg font-bold text-green-600">{formatCurrency(selectedClaim.insurance_share)}</div>
                <div className="text-xs text-gray-500">حصة التأمين</div>
              </div>
            </div>

            {/* Scrub Errors */}
            {selectedClaim.scrub_errors?.length > 0 && (
              <div className="bg-red-50 p-4 rounded-lg">
                <h4 className="font-medium text-red-800 mb-2">أخطاء الفحص</h4>
                <ul className="space-y-1">
                  {selectedClaim.scrub_errors.map((error, index) => (
                    <li key={index} className="text-sm text-red-700">
                      <span className="font-mono">[{error.code}]</span> {error.message}
                    </li>
                  ))}
                </ul>
              </div>
            )}

            {/* Rejection Reason */}
            {selectedClaim.rejection_reason && (
              <div className="bg-red-50 p-4 rounded-lg">
                <h4 className="font-medium text-red-800 mb-2">سبب الرفض</h4>
                <p className="text-sm text-red-700">{selectedClaim.rejection_reason}</p>
              </div>
            )}

            {/* Payment Info */}
            {selectedClaim.status === 'paid' && (
              <div className="bg-purple-50 p-4 rounded-lg">
                <h4 className="font-medium text-purple-800 mb-2">معلومات الدفع</h4>
                <div className="space-y-1 text-sm">
                  <div><span className="text-gray-500">تاريخ الدفع:</span> {formatDate(selectedClaim.payment_date)}</div>
                  <div><span className="text-gray-500">رقم المرجع:</span> {selectedClaim.payment_reference}</div>
                  <div><span className="text-gray-500">المبلغ المدفوع:</span> {formatCurrency(selectedClaim.approved_amount)}</div>
                </div>
              </div>
            )}

            <div className="flex justify-end">
              <Button variant="secondary" onClick={() => setShowDetailsModal(false)}>
                إغلاق
              </Button>
            </div>
          </div>
        )}
      </Modal>

      {/* Action Modal */}
      <Modal
        isOpen={showActionModal}
        onClose={() => setShowActionModal(false)}
        title={getActionConfig().title}
        size="sm"
      >
        <div className="space-y-4">
          <p className="text-gray-600">{getActionConfig().message}</p>

          {getActionConfig().showAmount && (
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">
                المبلغ المعتمد
              </label>
              <input
                type="number"
                value={actionData.amount}
                onChange={(e) => setActionData({ ...actionData, amount: e.target.value })}
                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                min="0"
              />
            </div>
          )}

          {getActionConfig().showNotes && (
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">
                {getActionConfig().notesLabel}
                {getActionConfig().notesRequired && <span className="text-red-500">*</span>}
              </label>
              <textarea
                value={actionData.notes}
                onChange={(e) => setActionData({ ...actionData, notes: e.target.value })}
                rows={3}
                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                required={getActionConfig().notesRequired}
              />
            </div>
          )}

          <div className="flex justify-end gap-3 pt-4">
            <Button
              variant="secondary"
              onClick={() => setShowActionModal(false)}
            >
              إلغاء
            </Button>
            <Button
              variant={actionType === 'reject' ? 'danger' : 'primary'}
              onClick={handleAction}
              disabled={saving || (getActionConfig().notesRequired && !actionData.notes)}
            >
              {saving ? 'جاري التنفيذ...' : getActionConfig().confirmText}
            </Button>
          </div>
        </div>
      </Modal>
    </div>
  );
}
