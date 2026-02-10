import React, { useState, useEffect, useCallback } from 'react';
import toast from 'react-hot-toast';
import { contractsApi, employeesApi } from '../../services/hrApi';
import {
  Button,
  Input,
  Select,
  Modal,
  Card,
  CardHeader,
  Table,
  TableHead,
  TableBody,
  TableRow,
  TableHeader,
  TableCell,
  TableEmpty,
  TableLoading,
  Badge,
} from '../../components/ui';
import {
  HiPlus,
  HiPencil,
  HiTrash,
  HiDocumentText,
  HiSearch,
  HiRefresh,
  HiEye,
  HiCheck,
  HiX,
  HiClock,
  HiExclamation,
} from 'react-icons/hi';

// Contract types
const CONTRACT_TYPES = {
  full_time: { label: 'دوام كامل', variant: 'success' },
  part_time: { label: 'دوام جزئي', variant: 'info' },
  tamheer: { label: 'تمهير', variant: 'warning' },
  percentage: { label: 'نسبة', variant: 'purple' },
  locum: { label: 'مناوب', variant: 'gray' },
};

// Contract status
const CONTRACT_STATUS = {
  draft: { label: 'مسودة', variant: 'gray', icon: HiDocumentText },
  active: { label: 'نشط', variant: 'success', icon: HiCheck },
  expired: { label: 'منتهي', variant: 'danger', icon: HiX },
  terminated: { label: 'ملغي', variant: 'danger', icon: HiX },
  renewed: { label: 'مجدد', variant: 'info', icon: HiRefresh },
};

/**
 * صفحة إدارة العقود
 * Contracts Management Page
 */
export default function ContractsPage() {
  const [contracts, setContracts] = useState([]);
  const [employees, setEmployees] = useState([]);
  const [loading, setLoading] = useState(true);
  const [showModal, setShowModal] = useState(false);
  const [showViewModal, setShowViewModal] = useState(false);
  const [showDeleteModal, setShowDeleteModal] = useState(false);
  const [showTerminateModal, setShowTerminateModal] = useState(false);
  const [selectedContract, setSelectedContract] = useState(null);
  const [submitting, setSubmitting] = useState(false);
  const [expiringCount, setExpiringCount] = useState(0);

  // Filters
  const [filters, setFilters] = useState({
    search: '',
    contract_type: '',
    status: '',
  });

  // Form data
  const [formData, setFormData] = useState({
    employee_id: '',
    contract_type: 'full_time',
    start_date: '',
    end_date: '',
    basic_salary: '',
    housing_allowance: '',
    transportation_allowance: '',
    other_allowances: '',
    probation_period: '90',
    notice_period: '30',
    terms: '',
  });
  const [formErrors, setFormErrors] = useState({});

  // Termination data
  const [terminationData, setTerminationData] = useState({
    termination_date: '',
    termination_reason: '',
    final_settlement: '',
  });

  // Load contracts
  const loadContracts = useCallback(async () => {
    try {
      setLoading(true);
      const params = {};
      if (filters.search) params.search = filters.search;
      if (filters.contract_type) params.contract_type = filters.contract_type;
      if (filters.status) params.status = filters.status;

      const [contractsRes, expiringRes] = await Promise.all([
        contractsApi.getAll(params),
        contractsApi.getExpiring(30),
      ]);

      setContracts(contractsRes.data?.data || contractsRes.data || []);
      setExpiringCount((expiringRes.data?.data || expiringRes.data || []).length);
    } catch (error) {
      toast.error('فشل في تحميل العقود');
      console.error('Error loading contracts:', error);
    } finally {
      setLoading(false);
    }
  }, [filters]);

  // Load employees
  const loadEmployees = useCallback(async () => {
    try {
      const response = await employeesApi.getActive();
      setEmployees(response.data?.data || response.data || []);
    } catch (error) {
      console.error('Error loading employees:', error);
    }
  }, []);

  useEffect(() => {
    loadEmployees();
  }, [loadEmployees]);

  useEffect(() => {
    loadContracts();
  }, [loadContracts]);

  // Reset form
  const resetForm = () => {
    setFormData({
      employee_id: '',
      contract_type: 'full_time',
      start_date: '',
      end_date: '',
      basic_salary: '',
      housing_allowance: '',
      transportation_allowance: '',
      other_allowances: '',
      probation_period: '90',
      notice_period: '30',
      terms: '',
    });
    setFormErrors({});
    setSelectedContract(null);
  };

  // Open create modal
  const handleCreate = () => {
    resetForm();
    setShowModal(true);
  };

  // Open edit modal
  const handleEdit = (contract) => {
    setSelectedContract(contract);
    setFormData({
      employee_id: contract.employee_id || '',
      contract_type: contract.contract_type || 'full_time',
      start_date: contract.start_date || '',
      end_date: contract.end_date || '',
      basic_salary: contract.basic_salary || '',
      housing_allowance: contract.housing_allowance || '',
      transportation_allowance: contract.transportation_allowance || '',
      other_allowances: contract.other_allowances || '',
      probation_period: contract.probation_period || '90',
      notice_period: contract.notice_period || '30',
      terms: contract.terms || '',
    });
    setFormErrors({});
    setShowModal(true);
  };

  // View contract details
  const handleView = (contract) => {
    setSelectedContract(contract);
    setShowViewModal(true);
  };

  // Open delete confirmation
  const handleDeleteClick = (contract) => {
    setSelectedContract(contract);
    setShowDeleteModal(true);
  };

  // Open terminate modal
  const handleTerminateClick = (contract) => {
    setSelectedContract(contract);
    setTerminationData({
      termination_date: new Date().toISOString().split('T')[0],
      termination_reason: '',
      final_settlement: '',
    });
    setShowTerminateModal(true);
  };

  // Validate form
  const validateForm = () => {
    const errors = {};
    if (!formData.employee_id) errors.employee_id = 'الموظف مطلوب';
    if (!formData.contract_type) errors.contract_type = 'نوع العقد مطلوب';
    if (!formData.start_date) errors.start_date = 'تاريخ البداية مطلوب';
    if (!formData.basic_salary) errors.basic_salary = 'الراتب الأساسي مطلوب';

    if (formData.start_date && formData.end_date) {
      if (new Date(formData.end_date) <= new Date(formData.start_date)) {
        errors.end_date = 'تاريخ النهاية يجب أن يكون بعد تاريخ البداية';
      }
    }

    setFormErrors(errors);
    return Object.keys(errors).length === 0;
  };

  // Submit form
  const handleSubmit = async (e) => {
    e.preventDefault();
    if (!validateForm()) return;

    try {
      setSubmitting(true);
      const data = {
        ...formData,
        basic_salary: Number(formData.basic_salary) || 0,
        housing_allowance: Number(formData.housing_allowance) || 0,
        transportation_allowance: Number(formData.transportation_allowance) || 0,
        other_allowances: Number(formData.other_allowances) || 0,
        probation_period: Number(formData.probation_period) || 90,
        notice_period: Number(formData.notice_period) || 30,
      };

      if (selectedContract) {
        await contractsApi.update(selectedContract.id, data);
        toast.success('تم تحديث العقد بنجاح');
      } else {
        await contractsApi.create(data);
        toast.success('تم إنشاء العقد بنجاح');
      }
      setShowModal(false);
      resetForm();
      loadContracts();
    } catch (error) {
      const message = error.response?.data?.message || 'حدث خطأ أثناء الحفظ';
      toast.error(message);
      if (error.response?.data?.errors) {
        setFormErrors(error.response.data.errors);
      }
    } finally {
      setSubmitting(false);
    }
  };

  // Delete contract
  const handleDelete = async () => {
    if (!selectedContract) return;

    try {
      setSubmitting(true);
      await contractsApi.delete(selectedContract.id);
      toast.success('تم حذف العقد بنجاح');
      setShowDeleteModal(false);
      setSelectedContract(null);
      loadContracts();
    } catch (error) {
      const message = error.response?.data?.message || 'فشل في حذف العقد';
      toast.error(message);
    } finally {
      setSubmitting(false);
    }
  };

  // Terminate contract
  const handleTerminate = async () => {
    if (!selectedContract) return;

    try {
      setSubmitting(true);
      await contractsApi.terminate(selectedContract.id, terminationData);
      toast.success('تم إنهاء العقد بنجاح');
      setShowTerminateModal(false);
      setSelectedContract(null);
      loadContracts();
    } catch (error) {
      const message = error.response?.data?.message || 'فشل في إنهاء العقد';
      toast.error(message);
    } finally {
      setSubmitting(false);
    }
  };

  // Activate contract
  const handleActivate = async (contract) => {
    try {
      await contractsApi.activate(contract.id);
      toast.success('تم تفعيل العقد بنجاح');
      loadContracts();
    } catch (error) {
      toast.error('فشل في تفعيل العقد');
    }
  };

  // Format currency
  const formatCurrency = (value) => {
    if (!value) return '-';
    return new Intl.NumberFormat('ar-SA', {
      style: 'currency',
      currency: 'SAR',
      minimumFractionDigits: 0,
    }).format(value);
  };

  // Calculate total salary
  const calculateTotalSalary = () => {
    const basic = Number(formData.basic_salary) || 0;
    const housing = Number(formData.housing_allowance) || 0;
    const transport = Number(formData.transportation_allowance) || 0;
    const other = Number(formData.other_allowances) || 0;
    return basic + housing + transport + other;
  };

  // Check if contract is expiring soon
  const isExpiringSoon = (endDate) => {
    if (!endDate) return false;
    const end = new Date(endDate);
    const today = new Date();
    const diffDays = Math.ceil((end - today) / (1000 * 60 * 60 * 24));
    return diffDays > 0 && diffDays <= 30;
  };

  return (
    <div className="space-y-6" dir="rtl">
      {/* Header */}
      <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">إدارة العقود</h1>
          <p className="text-gray-600 mt-1">إدارة عقود الموظفين والتجديدات</p>
        </div>
        <div className="flex items-center gap-3">
          {expiringCount > 0 && (
            <Badge variant="warning" className="flex items-center gap-1">
              <HiExclamation className="w-4 h-4" />
              {expiringCount} عقد ينتهي قريباً
            </Badge>
          )}
          <Button icon={HiPlus} onClick={handleCreate}>
            إضافة عقد
          </Button>
        </div>
      </div>

      {/* Filters */}
      <Card>
        <div className="p-4">
          <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <Input
              placeholder="بحث بالموظف..."
              name="search"
              value={filters.search}
              onChange={(e) => setFilters({ ...filters, search: e.target.value })}
              icon={HiSearch}
            />
            <Select
              value={filters.contract_type}
              onChange={(e) => setFilters({ ...filters, contract_type: e.target.value })}
              options={[
                { value: '', label: 'جميع أنواع العقود' },
                ...Object.entries(CONTRACT_TYPES).map(([value, { label }]) => ({
                  value,
                  label,
                })),
              ]}
            />
            <Select
              value={filters.status}
              onChange={(e) => setFilters({ ...filters, status: e.target.value })}
              options={[
                { value: '', label: 'جميع الحالات' },
                ...Object.entries(CONTRACT_STATUS).map(([value, { label }]) => ({
                  value,
                  label,
                })),
              ]}
            />
            <div className="flex gap-2">
              <Button variant="secondary" icon={HiRefresh} onClick={loadContracts} className="flex-1">
                تحديث
              </Button>
              <Button
                variant="ghost"
                onClick={() => setFilters({ search: '', contract_type: '', status: '' })}
              >
                مسح
              </Button>
            </div>
          </div>
        </div>
      </Card>

      {/* Contracts Table */}
      <Card>
        <CardHeader
          title="قائمة العقود"
          subtitle={`${contracts.length} عقد`}
          icon={HiDocumentText}
        />
        <Table>
          <TableHead>
            <TableRow>
              <TableHeader>رقم العقد</TableHeader>
              <TableHeader>الموظف</TableHeader>
              <TableHeader>نوع العقد</TableHeader>
              <TableHeader>تاريخ البداية</TableHeader>
              <TableHeader>تاريخ النهاية</TableHeader>
              <TableHeader>الراتب الكلي</TableHeader>
              <TableHeader>الحالة</TableHeader>
              <TableHeader>الإجراءات</TableHeader>
            </TableRow>
          </TableHead>
          <TableBody>
            {loading ? (
              <TableLoading colSpan={8} />
            ) : contracts.length === 0 ? (
              <TableEmpty
                colSpan={8}
                icon={HiDocumentText}
                message="لا توجد عقود"
                actionLabel="إضافة عقد جديد"
                onAction={handleCreate}
              />
            ) : (
              contracts.map((contract) => (
                <TableRow key={contract.id}>
                  <TableCell className="font-mono text-sm">
                    {contract.contract_number || `C-${contract.id?.slice(0, 8)}`}
                  </TableCell>
                  <TableCell>
                    <div>
                      <div className="font-medium">{contract.employee?.name_ar || '-'}</div>
                      <div className="text-sm text-gray-500">{contract.employee?.employee_number}</div>
                    </div>
                  </TableCell>
                  <TableCell>
                    <Badge variant={CONTRACT_TYPES[contract.contract_type]?.variant || 'gray'}>
                      {CONTRACT_TYPES[contract.contract_type]?.label || contract.contract_type}
                    </Badge>
                  </TableCell>
                  <TableCell>{contract.start_date || '-'}</TableCell>
                  <TableCell>
                    <div className="flex items-center gap-2">
                      {contract.end_date || 'غير محدد'}
                      {isExpiringSoon(contract.end_date) && (
                        <HiClock className="w-4 h-4 text-yellow-500" title="ينتهي قريباً" />
                      )}
                    </div>
                  </TableCell>
                  <TableCell>{formatCurrency(contract.total_salary)}</TableCell>
                  <TableCell>
                    <Badge variant={CONTRACT_STATUS[contract.status]?.variant || 'gray'}>
                      {CONTRACT_STATUS[contract.status]?.label || contract.status}
                    </Badge>
                  </TableCell>
                  <TableCell>
                    <div className="flex items-center gap-1">
                      <Button
                        variant="ghost"
                        size="sm"
                        icon={HiEye}
                        onClick={() => handleView(contract)}
                        title="عرض"
                      />
                      {contract.status === 'draft' && (
                        <>
                          <Button
                            variant="ghost"
                            size="sm"
                            icon={HiCheck}
                            onClick={() => handleActivate(contract)}
                            className="text-green-600 hover:text-green-700 hover:bg-green-50"
                            title="تفعيل"
                          />
                          <Button
                            variant="ghost"
                            size="sm"
                            icon={HiPencil}
                            onClick={() => handleEdit(contract)}
                            title="تعديل"
                          />
                        </>
                      )}
                      {contract.status === 'active' && (
                        <Button
                          variant="ghost"
                          size="sm"
                          icon={HiX}
                          onClick={() => handleTerminateClick(contract)}
                          className="text-red-600 hover:text-red-700 hover:bg-red-50"
                          title="إنهاء"
                        />
                      )}
                      {contract.status === 'draft' && (
                        <Button
                          variant="ghost"
                          size="sm"
                          icon={HiTrash}
                          onClick={() => handleDeleteClick(contract)}
                          className="text-red-600 hover:text-red-700 hover:bg-red-50"
                          title="حذف"
                        />
                      )}
                    </div>
                  </TableCell>
                </TableRow>
              ))
            )}
          </TableBody>
        </Table>
      </Card>

      {/* Create/Edit Modal */}
      <Modal
        isOpen={showModal}
        onClose={() => setShowModal(false)}
        title={selectedContract ? 'تعديل العقد' : 'إضافة عقد جديد'}
        size="lg"
      >
        <form onSubmit={handleSubmit} className="space-y-6">
          {/* Employee & Type */}
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <Select
              label="الموظف"
              name="employee_id"
              value={formData.employee_id}
              onChange={(e) => setFormData({ ...formData, employee_id: e.target.value })}
              error={formErrors.employee_id}
              required
              placeholder="اختر الموظف"
              options={employees.map((e) => ({
                value: e.id,
                label: `${e.name_ar} (${e.employee_number})`,
              }))}
            />
            <Select
              label="نوع العقد"
              name="contract_type"
              value={formData.contract_type}
              onChange={(e) => setFormData({ ...formData, contract_type: e.target.value })}
              error={formErrors.contract_type}
              required
              options={Object.entries(CONTRACT_TYPES).map(([value, { label }]) => ({
                value,
                label,
              }))}
            />
          </div>

          {/* Dates */}
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <Input
              label="تاريخ البداية"
              name="start_date"
              type="date"
              value={formData.start_date}
              onChange={(e) => setFormData({ ...formData, start_date: e.target.value })}
              error={formErrors.start_date}
              required
              dir="ltr"
            />
            <Input
              label="تاريخ النهاية"
              name="end_date"
              type="date"
              value={formData.end_date}
              onChange={(e) => setFormData({ ...formData, end_date: e.target.value })}
              error={formErrors.end_date}
              hint="اتركه فارغاً للعقود المفتوحة"
              dir="ltr"
            />
          </div>

          {/* Salary */}
          <div>
            <h4 className="font-medium text-gray-900 mb-3">تفاصيل الراتب</h4>
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
              <Input
                label="الراتب الأساسي"
                name="basic_salary"
                type="number"
                value={formData.basic_salary}
                onChange={(e) => setFormData({ ...formData, basic_salary: e.target.value })}
                error={formErrors.basic_salary}
                required
                placeholder="0"
                dir="ltr"
              />
              <Input
                label="بدل السكن"
                name="housing_allowance"
                type="number"
                value={formData.housing_allowance}
                onChange={(e) => setFormData({ ...formData, housing_allowance: e.target.value })}
                placeholder="0"
                dir="ltr"
              />
              <Input
                label="بدل المواصلات"
                name="transportation_allowance"
                type="number"
                value={formData.transportation_allowance}
                onChange={(e) => setFormData({ ...formData, transportation_allowance: e.target.value })}
                placeholder="0"
                dir="ltr"
              />
              <Input
                label="بدلات أخرى"
                name="other_allowances"
                type="number"
                value={formData.other_allowances}
                onChange={(e) => setFormData({ ...formData, other_allowances: e.target.value })}
                placeholder="0"
                dir="ltr"
              />
            </div>
            <div className="mt-3 p-3 bg-primary-50 rounded-lg">
              <div className="flex justify-between items-center">
                <span className="font-medium text-primary-900">إجمالي الراتب الشهري</span>
                <span className="text-lg font-bold text-primary-600">
                  {formatCurrency(calculateTotalSalary())}
                </span>
              </div>
            </div>
          </div>

          {/* Periods */}
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <Input
              label="فترة التجربة (يوم)"
              name="probation_period"
              type="number"
              value={formData.probation_period}
              onChange={(e) => setFormData({ ...formData, probation_period: e.target.value })}
              placeholder="90"
              dir="ltr"
            />
            <Input
              label="فترة الإشعار (يوم)"
              name="notice_period"
              type="number"
              value={formData.notice_period}
              onChange={(e) => setFormData({ ...formData, notice_period: e.target.value })}
              placeholder="30"
              dir="ltr"
            />
          </div>

          {/* Terms */}
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
              شروط وأحكام إضافية
            </label>
            <textarea
              name="terms"
              value={formData.terms}
              onChange={(e) => setFormData({ ...formData, terms: e.target.value })}
              rows={3}
              className="w-full rounded-lg border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
              placeholder="أي شروط أو أحكام إضافية..."
            />
          </div>

          <div className="flex justify-end gap-3 pt-4 border-t">
            <Button
              type="button"
              variant="secondary"
              onClick={() => setShowModal(false)}
              disabled={submitting}
            >
              إلغاء
            </Button>
            <Button type="submit" loading={submitting}>
              {selectedContract ? 'تحديث' : 'إضافة'}
            </Button>
          </div>
        </form>
      </Modal>

      {/* View Modal */}
      <Modal
        isOpen={showViewModal}
        onClose={() => setShowViewModal(false)}
        title="تفاصيل العقد"
        size="lg"
      >
        {selectedContract && (
          <div className="space-y-6">
            {/* Header */}
            <div className="flex items-center justify-between pb-4 border-b">
              <div>
                <h3 className="text-xl font-bold text-gray-900">
                  {selectedContract.employee?.name_ar}
                </h3>
                <p className="text-gray-500">{selectedContract.employee?.employee_number}</p>
              </div>
              <Badge
                variant={CONTRACT_STATUS[selectedContract.status]?.variant || 'gray'}
                size="lg"
              >
                {CONTRACT_STATUS[selectedContract.status]?.label || selectedContract.status}
              </Badge>
            </div>

            {/* Details */}
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div className="space-y-4">
                <h4 className="font-medium text-gray-900">معلومات العقد</h4>
                <div className="bg-gray-50 rounded-lg p-4 space-y-3">
                  <div className="flex justify-between">
                    <span className="text-gray-500">نوع العقد</span>
                    <Badge variant={CONTRACT_TYPES[selectedContract.contract_type]?.variant}>
                      {CONTRACT_TYPES[selectedContract.contract_type]?.label}
                    </Badge>
                  </div>
                  <div className="flex justify-between">
                    <span className="text-gray-500">تاريخ البداية</span>
                    <span className="font-medium">{selectedContract.start_date}</span>
                  </div>
                  <div className="flex justify-between">
                    <span className="text-gray-500">تاريخ النهاية</span>
                    <span className="font-medium">{selectedContract.end_date || 'غير محدد'}</span>
                  </div>
                  <div className="flex justify-between">
                    <span className="text-gray-500">فترة التجربة</span>
                    <span className="font-medium">{selectedContract.probation_period} يوم</span>
                  </div>
                </div>
              </div>

              <div className="space-y-4">
                <h4 className="font-medium text-gray-900">تفاصيل الراتب</h4>
                <div className="bg-gray-50 rounded-lg p-4 space-y-3">
                  <div className="flex justify-between">
                    <span className="text-gray-500">الراتب الأساسي</span>
                    <span className="font-medium">{formatCurrency(selectedContract.basic_salary)}</span>
                  </div>
                  <div className="flex justify-between">
                    <span className="text-gray-500">بدل السكن</span>
                    <span className="font-medium">{formatCurrency(selectedContract.housing_allowance)}</span>
                  </div>
                  <div className="flex justify-between">
                    <span className="text-gray-500">بدل المواصلات</span>
                    <span className="font-medium">{formatCurrency(selectedContract.transportation_allowance)}</span>
                  </div>
                  <div className="flex justify-between">
                    <span className="text-gray-500">بدلات أخرى</span>
                    <span className="font-medium">{formatCurrency(selectedContract.other_allowances)}</span>
                  </div>
                  <div className="flex justify-between pt-2 border-t font-bold">
                    <span>الإجمالي</span>
                    <span className="text-primary-600">{formatCurrency(selectedContract.total_salary)}</span>
                  </div>
                </div>
              </div>
            </div>

            {selectedContract.terms && (
              <div>
                <h4 className="font-medium text-gray-900 mb-2">شروط وأحكام</h4>
                <div className="bg-gray-50 rounded-lg p-4">
                  <p className="text-gray-600 whitespace-pre-wrap">{selectedContract.terms}</p>
                </div>
              </div>
            )}

            <div className="flex justify-end gap-3 pt-4 border-t">
              <Button variant="secondary" onClick={() => setShowViewModal(false)}>
                إغلاق
              </Button>
            </div>
          </div>
        )}
      </Modal>

      {/* Terminate Modal */}
      <Modal
        isOpen={showTerminateModal}
        onClose={() => setShowTerminateModal(false)}
        title="إنهاء العقد"
        size="md"
      >
        <div className="space-y-4">
          <p className="text-gray-600">
            سيتم إنهاء عقد الموظف{' '}
            <span className="font-bold text-gray-900">{selectedContract?.employee?.name_ar}</span>
          </p>

          <Input
            label="تاريخ الإنهاء"
            type="date"
            value={terminationData.termination_date}
            onChange={(e) =>
              setTerminationData({ ...terminationData, termination_date: e.target.value })
            }
            required
            dir="ltr"
          />

          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">سبب الإنهاء</label>
            <textarea
              value={terminationData.termination_reason}
              onChange={(e) =>
                setTerminationData({ ...terminationData, termination_reason: e.target.value })
              }
              rows={3}
              className="w-full rounded-lg border border-gray-300 px-3 py-2"
              placeholder="اذكر سبب إنهاء العقد..."
            />
          </div>

          <Input
            label="مبلغ التسوية النهائية"
            type="number"
            value={terminationData.final_settlement}
            onChange={(e) =>
              setTerminationData({ ...terminationData, final_settlement: e.target.value })
            }
            placeholder="0"
            dir="ltr"
          />

          <div className="flex justify-end gap-3 pt-4 border-t">
            <Button
              variant="secondary"
              onClick={() => setShowTerminateModal(false)}
              disabled={submitting}
            >
              إلغاء
            </Button>
            <Button variant="danger" onClick={handleTerminate} loading={submitting}>
              إنهاء العقد
            </Button>
          </div>
        </div>
      </Modal>

      {/* Delete Modal */}
      <Modal
        isOpen={showDeleteModal}
        onClose={() => setShowDeleteModal(false)}
        title="تأكيد الحذف"
        size="sm"
      >
        <div className="space-y-4">
          <p className="text-gray-600">
            هل أنت متأكد من حذف هذا العقد؟
          </p>
          <div className="flex justify-end gap-3 pt-4 border-t">
            <Button
              variant="secondary"
              onClick={() => setShowDeleteModal(false)}
              disabled={submitting}
            >
              إلغاء
            </Button>
            <Button variant="danger" onClick={handleDelete} loading={submitting} icon={HiTrash}>
              حذف
            </Button>
          </div>
        </div>
      </Modal>
    </div>
  );
}
