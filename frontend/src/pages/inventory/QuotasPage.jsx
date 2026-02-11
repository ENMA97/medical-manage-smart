import React, { useState, useEffect, useCallback } from 'react';
import toast from 'react-hot-toast';
import { itemQuotasApi, inventoryItemsApi } from '../../services/inventoryApi';
import { departmentsApi } from '../../services/hrApi';
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
  HiChartPie,
  HiSearch,
  HiRefresh,
  HiEye,
  HiClipboardCheck,
  HiTrendingUp,
  HiExclamation,
} from 'react-icons/hi';

/**
 * صفحة إدارة الحصص
 * Quotas Management Page
 */
export default function QuotasPage() {
  const [quotas, setQuotas] = useState([]);
  const [departments, setDepartments] = useState([]);
  const [items, setItems] = useState([]);
  const [loading, setLoading] = useState(true);
  const [searchTerm, setSearchTerm] = useState('');
  const [departmentFilter, setDepartmentFilter] = useState('');
  const [periodFilter, setPeriodFilter] = useState('');
  const [showModal, setShowModal] = useState(false);
  const [showDeleteModal, setShowDeleteModal] = useState(false);
  const [showViewModal, setShowViewModal] = useState(false);
  const [showConsumeModal, setShowConsumeModal] = useState(false);
  const [selectedQuota, setSelectedQuota] = useState(null);
  const [consumptionHistory, setConsumptionHistory] = useState([]);
  const [formData, setFormData] = useState({
    department_id: '',
    item_id: '',
    quota_amount: '',
    period_type: 'monthly',
    start_date: '',
    end_date: '',
    notes: '',
    is_active: true,
  });
  const [consumeData, setConsumeData] = useState({
    quantity: '',
    notes: '',
  });
  const [formErrors, setFormErrors] = useState({});
  const [submitting, setSubmitting] = useState(false);

  // Period types
  const periodTypes = [
    { value: 'daily', label: 'يومي' },
    { value: 'weekly', label: 'أسبوعي' },
    { value: 'monthly', label: 'شهري' },
    { value: 'quarterly', label: 'ربع سنوي' },
    { value: 'yearly', label: 'سنوي' },
  ];

  // Period filter options
  const periodFilterOptions = [
    { value: '', label: 'جميع الفترات' },
    ...periodTypes,
  ];

  // Load quotas
  const loadQuotas = useCallback(async () => {
    try {
      setLoading(true);
      let response;

      if (departmentFilter) {
        response = await itemQuotasApi.getByDepartment(departmentFilter);
      } else {
        response = await itemQuotasApi.getAll({
          search: searchTerm,
          period_type: periodFilter,
        });
      }

      setQuotas(response.data?.data || response.data || []);
    } catch (error) {
      toast.error('فشل في تحميل الحصص');
      console.error('Error loading quotas:', error);
    } finally {
      setLoading(false);
    }
  }, [searchTerm, departmentFilter, periodFilter]);

  // Load departments
  const loadDepartments = useCallback(async () => {
    try {
      const response = await departmentsApi.getActive();
      setDepartments(response.data?.data || response.data || []);
    } catch (error) {
      console.error('Error loading departments:', error);
    }
  }, []);

  // Load items
  const loadItems = useCallback(async () => {
    try {
      const response = await inventoryItemsApi.getActive();
      setItems(response.data?.data || response.data || []);
    } catch (error) {
      console.error('Error loading items:', error);
    }
  }, []);

  useEffect(() => {
    loadDepartments();
    loadItems();
  }, [loadDepartments, loadItems]);

  useEffect(() => {
    loadQuotas();
  }, [loadQuotas]);

  // Reset form
  const resetForm = () => {
    setFormData({
      department_id: '',
      item_id: '',
      quota_amount: '',
      period_type: 'monthly',
      start_date: '',
      end_date: '',
      notes: '',
      is_active: true,
    });
    setFormErrors({});
    setSelectedQuota(null);
  };

  // Open create modal
  const handleCreate = () => {
    resetForm();
    setShowModal(true);
  };

  // Open edit modal
  const handleEdit = (quota) => {
    setSelectedQuota(quota);
    setFormData({
      department_id: quota.department_id || '',
      item_id: quota.item_id || '',
      quota_amount: quota.quota_amount || '',
      period_type: quota.period_type || 'monthly',
      start_date: quota.start_date?.split('T')[0] || '',
      end_date: quota.end_date?.split('T')[0] || '',
      notes: quota.notes || '',
      is_active: quota.is_active ?? true,
    });
    setFormErrors({});
    setShowModal(true);
  };

  // Open view modal
  const handleView = async (quota) => {
    setSelectedQuota(quota);
    try {
      const response = await itemQuotasApi.getConsumption(quota.id);
      setConsumptionHistory(response.data?.data || response.data || []);
    } catch (error) {
      console.error('Error loading consumption:', error);
      setConsumptionHistory([]);
    }
    setShowViewModal(true);
  };

  // Open consume modal
  const handleConsumeClick = (quota) => {
    setSelectedQuota(quota);
    setConsumeData({ quantity: '', notes: '' });
    setFormErrors({});
    setShowConsumeModal(true);
  };

  // Open delete confirmation
  const handleDeleteClick = (quota) => {
    setSelectedQuota(quota);
    setShowDeleteModal(true);
  };

  // Validate form
  const validateForm = () => {
    const errors = {};
    if (!formData.department_id) {
      errors.department_id = 'القسم مطلوب';
    }
    if (!formData.item_id) {
      errors.item_id = 'الصنف مطلوب';
    }
    if (!formData.quota_amount || Number(formData.quota_amount) <= 0) {
      errors.quota_amount = 'كمية الحصة مطلوبة وأكبر من صفر';
    }
    if (!formData.period_type) {
      errors.period_type = 'نوع الفترة مطلوب';
    }
    if (formData.start_date && formData.end_date) {
      if (new Date(formData.start_date) > new Date(formData.end_date)) {
        errors.end_date = 'تاريخ الانتهاء يجب أن يكون بعد تاريخ البداية';
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
        quota_amount: Number(formData.quota_amount),
        start_date: formData.start_date || null,
        end_date: formData.end_date || null,
      };

      if (selectedQuota) {
        await itemQuotasApi.update(selectedQuota.id, data);
        toast.success('تم تحديث الحصة بنجاح');
      } else {
        await itemQuotasApi.create(data);
        toast.success('تم إنشاء الحصة بنجاح');
      }
      setShowModal(false);
      resetForm();
      loadQuotas();
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

  // Record consumption
  const handleConsume = async (e) => {
    e.preventDefault();
    if (!consumeData.quantity || Number(consumeData.quantity) <= 0) {
      setFormErrors({ quantity: 'الكمية مطلوبة وأكبر من صفر' });
      return;
    }

    try {
      setSubmitting(true);
      await itemQuotasApi.recordConsumption(selectedQuota.id, {
        quantity: Number(consumeData.quantity),
        notes: consumeData.notes || null,
      });
      toast.success('تم تسجيل الاستهلاك بنجاح');
      setShowConsumeModal(false);
      setConsumeData({ quantity: '', notes: '' });
      loadQuotas();
    } catch (error) {
      const message = error.response?.data?.message || 'فشل في تسجيل الاستهلاك';
      toast.error(message);
    } finally {
      setSubmitting(false);
    }
  };

  // Delete quota
  const handleDelete = async () => {
    if (!selectedQuota) return;

    try {
      setSubmitting(true);
      await itemQuotasApi.delete(selectedQuota.id);
      toast.success('تم حذف الحصة بنجاح');
      setShowDeleteModal(false);
      setSelectedQuota(null);
      loadQuotas();
    } catch (error) {
      const message = error.response?.data?.message || 'فشل في حذف الحصة';
      toast.error(message);
    } finally {
      setSubmitting(false);
    }
  };

  // Handle input change
  const handleInputChange = (e) => {
    const { name, value, type, checked } = e.target;
    setFormData((prev) => ({
      ...prev,
      [name]: type === 'checkbox' ? checked : value,
    }));
    if (formErrors[name]) {
      setFormErrors((prev) => ({ ...prev, [name]: null }));
    }
  };

  // Handle consume input change
  const handleConsumeInputChange = (e) => {
    const { name, value } = e.target;
    setConsumeData((prev) => ({
      ...prev,
      [name]: value,
    }));
    if (formErrors[name]) {
      setFormErrors((prev) => ({ ...prev, [name]: null }));
    }
  };

  // Get period label
  const getPeriodLabel = (periodType) => {
    const found = periodTypes.find((p) => p.value === periodType);
    return found ? found.label : periodType;
  };

  // Calculate usage percentage
  const getUsagePercentage = (quota) => {
    const consumed = quota.consumed_amount || 0;
    const total = quota.quota_amount || 1;
    return Math.round((consumed / total) * 100);
  };

  // Get usage status
  const getUsageStatus = (quota) => {
    const percentage = getUsagePercentage(quota);
    if (percentage >= 100) {
      return { variant: 'danger', label: 'مستنفذ' };
    } else if (percentage >= 80) {
      return { variant: 'warning', label: 'قارب على النفاذ' };
    } else if (percentage >= 50) {
      return { variant: 'info', label: 'متوسط' };
    }
    return { variant: 'success', label: 'متاح' };
  };

  // Format date
  const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('ar-SA');
  };

  return (
    <div className="space-y-6" dir="rtl">
      {/* Header */}
      <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">إدارة الحصص</h1>
          <p className="text-gray-600 mt-1">إدارة حصص الأقسام من المخزون والاستهلاك</p>
        </div>
        <Button icon={HiPlus} onClick={handleCreate}>
          إضافة حصة
        </Button>
      </div>

      {/* Stats Cards */}
      <div className="grid grid-cols-1 sm:grid-cols-4 gap-4">
        <Card>
          <div className="p-4">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm text-gray-500">إجمالي الحصص</p>
                <p className="text-2xl font-bold">{quotas.length}</p>
              </div>
              <div className="p-3 bg-primary-100 rounded-lg">
                <HiChartPie className="w-6 h-6 text-primary-600" />
              </div>
            </div>
          </div>
        </Card>
        <Card>
          <div className="p-4">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm text-gray-500">حصص نشطة</p>
                <p className="text-2xl font-bold text-green-600">
                  {quotas.filter((q) => q.is_active).length}
                </p>
              </div>
              <div className="p-3 bg-green-100 rounded-lg">
                <HiClipboardCheck className="w-6 h-6 text-green-600" />
              </div>
            </div>
          </div>
        </Card>
        <Card>
          <div className="p-4">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm text-gray-500">قاربت على النفاذ</p>
                <p className="text-2xl font-bold text-yellow-600">
                  {quotas.filter((q) => getUsagePercentage(q) >= 80 && getUsagePercentage(q) < 100).length}
                </p>
              </div>
              <div className="p-3 bg-yellow-100 rounded-lg">
                <HiTrendingUp className="w-6 h-6 text-yellow-600" />
              </div>
            </div>
          </div>
        </Card>
        <Card>
          <div className="p-4">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm text-gray-500">مستنفذة</p>
                <p className="text-2xl font-bold text-red-600">
                  {quotas.filter((q) => getUsagePercentage(q) >= 100).length}
                </p>
              </div>
              <div className="p-3 bg-red-100 rounded-lg">
                <HiExclamation className="w-6 h-6 text-red-600" />
              </div>
            </div>
          </div>
        </Card>
      </div>

      {/* Search & Filters */}
      <Card>
        <div className="p-4">
          <div className="flex flex-col sm:flex-row gap-4">
            <div className="flex-1">
              <Input
                placeholder="بحث..."
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
                icon={HiSearch}
              />
            </div>
            <div className="w-full sm:w-48">
              <Select
                value={departmentFilter}
                onChange={(e) => setDepartmentFilter(e.target.value)}
                options={[
                  { value: '', label: 'جميع الأقسام' },
                  ...departments.map((d) => ({ value: d.id, label: d.name_ar })),
                ]}
              />
            </div>
            <div className="w-full sm:w-40">
              <Select
                value={periodFilter}
                onChange={(e) => setPeriodFilter(e.target.value)}
                options={periodFilterOptions}
              />
            </div>
            <Button variant="secondary" icon={HiRefresh} onClick={loadQuotas}>
              تحديث
            </Button>
          </div>
        </div>
      </Card>

      {/* Quotas Table */}
      <Card>
        <CardHeader
          title="قائمة الحصص"
          subtitle={`${quotas.length} حصة`}
          icon={HiChartPie}
        />
        <Table>
          <TableHead>
            <TableRow>
              <TableHeader>القسم</TableHeader>
              <TableHeader>الصنف</TableHeader>
              <TableHeader>الحصة</TableHeader>
              <TableHeader>الاستهلاك</TableHeader>
              <TableHeader>الفترة</TableHeader>
              <TableHeader>الحالة</TableHeader>
              <TableHeader>الإجراءات</TableHeader>
            </TableRow>
          </TableHead>
          <TableBody>
            {loading ? (
              <TableLoading colSpan={7} />
            ) : quotas.length === 0 ? (
              <TableEmpty
                colSpan={7}
                icon={HiChartPie}
                message="لا توجد حصص"
                actionLabel="إضافة حصة جديدة"
                onAction={handleCreate}
              />
            ) : (
              quotas.map((quota) => {
                const usagePercentage = getUsagePercentage(quota);
                const usageStatus = getUsageStatus(quota);
                return (
                  <TableRow key={quota.id}>
                    <TableCell className="font-medium">
                      {quota.department?.name_ar || '-'}
                    </TableCell>
                    <TableCell>
                      <div>
                        <span>{quota.item?.name_ar}</span>
                        <span className="block text-xs text-gray-500">
                          {quota.item?.code}
                        </span>
                      </div>
                    </TableCell>
                    <TableCell className="font-bold">
                      {quota.quota_amount}
                    </TableCell>
                    <TableCell>
                      <div className="w-32">
                        <div className="flex justify-between text-sm mb-1">
                          <span>{quota.consumed_amount || 0}</span>
                          <span>{usagePercentage}%</span>
                        </div>
                        <div className="w-full bg-gray-200 rounded-full h-2">
                          <div
                            className={`h-2 rounded-full ${
                              usagePercentage >= 100
                                ? 'bg-red-500'
                                : usagePercentage >= 80
                                ? 'bg-yellow-500'
                                : usagePercentage >= 50
                                ? 'bg-blue-500'
                                : 'bg-green-500'
                            }`}
                            style={{ width: `${Math.min(usagePercentage, 100)}%` }}
                          />
                        </div>
                      </div>
                    </TableCell>
                    <TableCell>
                      <Badge variant="info">{getPeriodLabel(quota.period_type)}</Badge>
                    </TableCell>
                    <TableCell>
                      <div className="space-y-1">
                        <Badge variant={usageStatus.variant}>{usageStatus.label}</Badge>
                        {!quota.is_active && (
                          <Badge variant="gray">غير نشط</Badge>
                        )}
                      </div>
                    </TableCell>
                    <TableCell>
                      <div className="flex items-center gap-2">
                        <Button
                          variant="ghost"
                          size="sm"
                          icon={HiEye}
                          onClick={() => handleView(quota)}
                          title="عرض التفاصيل"
                        />
                        <Button
                          variant="ghost"
                          size="sm"
                          icon={HiClipboardCheck}
                          onClick={() => handleConsumeClick(quota)}
                          title="تسجيل استهلاك"
                          disabled={!quota.is_active || usagePercentage >= 100}
                        />
                        <Button
                          variant="ghost"
                          size="sm"
                          icon={HiPencil}
                          onClick={() => handleEdit(quota)}
                          title="تعديل"
                        />
                        <Button
                          variant="ghost"
                          size="sm"
                          icon={HiTrash}
                          onClick={() => handleDeleteClick(quota)}
                          className="text-red-600 hover:text-red-700 hover:bg-red-50"
                          title="حذف"
                        />
                      </div>
                    </TableCell>
                  </TableRow>
                );
              })
            )}
          </TableBody>
        </Table>
      </Card>

      {/* Create/Edit Modal */}
      <Modal
        isOpen={showModal}
        onClose={() => setShowModal(false)}
        title={selectedQuota ? 'تعديل الحصة' : 'إضافة حصة جديدة'}
        size="md"
      >
        <form onSubmit={handleSubmit} className="space-y-4">
          <Select
            label="القسم"
            name="department_id"
            value={formData.department_id}
            onChange={handleInputChange}
            error={formErrors.department_id}
            required
            placeholder="اختر القسم"
            options={departments.map((d) => ({ value: d.id, label: d.name_ar }))}
          />

          <Select
            label="الصنف"
            name="item_id"
            value={formData.item_id}
            onChange={handleInputChange}
            error={formErrors.item_id}
            required
            placeholder="اختر الصنف"
            options={items.map((item) => ({
              value: item.id,
              label: `${item.name_ar} (${item.code})`,
            }))}
          />

          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <Input
              label="كمية الحصة"
              name="quota_amount"
              type="number"
              value={formData.quota_amount}
              onChange={handleInputChange}
              error={formErrors.quota_amount}
              required
              placeholder="0"
              min="1"
              dir="ltr"
            />
            <Select
              label="نوع الفترة"
              name="period_type"
              value={formData.period_type}
              onChange={handleInputChange}
              error={formErrors.period_type}
              required
              options={periodTypes}
            />
          </div>

          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <Input
              label="تاريخ البداية"
              name="start_date"
              type="date"
              value={formData.start_date}
              onChange={handleInputChange}
            />
            <Input
              label="تاريخ الانتهاء"
              name="end_date"
              type="date"
              value={formData.end_date}
              onChange={handleInputChange}
              error={formErrors.end_date}
            />
          </div>

          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
              ملاحظات
            </label>
            <textarea
              name="notes"
              value={formData.notes}
              onChange={handleInputChange}
              rows={2}
              className="w-full rounded-lg border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
              placeholder="ملاحظات إضافية..."
            />
          </div>

          <div className="flex items-center gap-2">
            <input
              type="checkbox"
              id="is_active"
              name="is_active"
              checked={formData.is_active}
              onChange={handleInputChange}
              className="w-4 h-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500"
            />
            <label htmlFor="is_active" className="text-sm text-gray-700">
              الحصة نشطة
            </label>
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
              {selectedQuota ? 'تحديث' : 'إضافة'}
            </Button>
          </div>
        </form>
      </Modal>

      {/* View Details Modal */}
      <Modal
        isOpen={showViewModal}
        onClose={() => setShowViewModal(false)}
        title="تفاصيل الحصة"
        size="lg"
      >
        {selectedQuota && (
          <div className="space-y-6">
            {/* Quota Info */}
            <div className="grid grid-cols-2 gap-4">
              <div>
                <span className="text-sm text-gray-500">القسم</span>
                <p className="font-medium">{selectedQuota.department?.name_ar}</p>
              </div>
              <div>
                <span className="text-sm text-gray-500">الصنف</span>
                <p className="font-medium">{selectedQuota.item?.name_ar}</p>
                <p className="text-sm text-gray-500">{selectedQuota.item?.code}</p>
              </div>
            </div>

            {/* Usage Progress */}
            <div className="p-4 bg-gray-50 rounded-lg">
              <div className="flex justify-between mb-2">
                <span className="font-medium">الاستهلاك</span>
                <span>
                  {selectedQuota.consumed_amount || 0} / {selectedQuota.quota_amount}
                </span>
              </div>
              <div className="w-full bg-gray-200 rounded-full h-4">
                <div
                  className={`h-4 rounded-full ${
                    getUsagePercentage(selectedQuota) >= 100
                      ? 'bg-red-500'
                      : getUsagePercentage(selectedQuota) >= 80
                      ? 'bg-yellow-500'
                      : 'bg-green-500'
                  }`}
                  style={{ width: `${Math.min(getUsagePercentage(selectedQuota), 100)}%` }}
                />
              </div>
              <div className="flex justify-between mt-2 text-sm text-gray-500">
                <span>المتبقي: {(selectedQuota.quota_amount || 0) - (selectedQuota.consumed_amount || 0)}</span>
                <span>{getUsagePercentage(selectedQuota)}%</span>
              </div>
            </div>

            {/* Period Info */}
            <div className="grid grid-cols-3 gap-4">
              <div>
                <span className="text-sm text-gray-500">نوع الفترة</span>
                <p>
                  <Badge variant="info">{getPeriodLabel(selectedQuota.period_type)}</Badge>
                </p>
              </div>
              <div>
                <span className="text-sm text-gray-500">تاريخ البداية</span>
                <p>{formatDate(selectedQuota.start_date)}</p>
              </div>
              <div>
                <span className="text-sm text-gray-500">تاريخ الانتهاء</span>
                <p>{formatDate(selectedQuota.end_date)}</p>
              </div>
            </div>

            {/* Consumption History */}
            <div>
              <h3 className="text-lg font-medium mb-3">سجل الاستهلاك</h3>
              {consumptionHistory.length > 0 ? (
                <div className="border rounded-lg divide-y max-h-60 overflow-y-auto">
                  {consumptionHistory.map((consumption, index) => (
                    <div key={index} className="p-3 flex justify-between items-center">
                      <div>
                        <span className="font-medium">{consumption.quantity}</span>
                        {consumption.notes && (
                          <span className="block text-sm text-gray-500">
                            {consumption.notes}
                          </span>
                        )}
                      </div>
                      <div className="text-sm text-gray-500">
                        {formatDate(consumption.created_at)}
                      </div>
                    </div>
                  ))}
                </div>
              ) : (
                <p className="text-gray-500 text-center py-4">لا يوجد سجل استهلاك</p>
              )}
            </div>

            <div className="flex justify-end pt-4 border-t">
              <Button variant="secondary" onClick={() => setShowViewModal(false)}>
                إغلاق
              </Button>
            </div>
          </div>
        )}
      </Modal>

      {/* Consume Modal */}
      <Modal
        isOpen={showConsumeModal}
        onClose={() => setShowConsumeModal(false)}
        title="تسجيل استهلاك"
        size="sm"
      >
        {selectedQuota && (
          <form onSubmit={handleConsume} className="space-y-4">
            <div className="p-3 bg-gray-50 rounded-lg">
              <p className="text-sm text-gray-500">الصنف</p>
              <p className="font-medium">{selectedQuota.item?.name_ar}</p>
              <p className="text-sm text-gray-500 mt-2">
                المتبقي: {(selectedQuota.quota_amount || 0) - (selectedQuota.consumed_amount || 0)} من {selectedQuota.quota_amount}
              </p>
            </div>

            <Input
              label="الكمية"
              name="quantity"
              type="number"
              value={consumeData.quantity}
              onChange={handleConsumeInputChange}
              error={formErrors.quantity}
              required
              placeholder="0"
              min="1"
              max={(selectedQuota.quota_amount || 0) - (selectedQuota.consumed_amount || 0)}
              dir="ltr"
            />

            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">
                ملاحظات
              </label>
              <textarea
                name="notes"
                value={consumeData.notes}
                onChange={handleConsumeInputChange}
                rows={2}
                className="w-full rounded-lg border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                placeholder="سبب الاستهلاك..."
              />
            </div>

            <div className="flex justify-end gap-3 pt-4 border-t">
              <Button
                type="button"
                variant="secondary"
                onClick={() => setShowConsumeModal(false)}
                disabled={submitting}
              >
                إلغاء
              </Button>
              <Button type="submit" loading={submitting}>
                تسجيل
              </Button>
            </div>
          </form>
        )}
      </Modal>

      {/* Delete Confirmation Modal */}
      <Modal
        isOpen={showDeleteModal}
        onClose={() => setShowDeleteModal(false)}
        title="تأكيد الحذف"
        size="sm"
      >
        <div className="space-y-4">
          <p className="text-gray-600">
            هل أنت متأكد من حذف حصة{' '}
            <span className="font-bold text-gray-900">
              {selectedQuota?.item?.name_ar}
            </span>{' '}
            للقسم{' '}
            <span className="font-bold text-gray-900">
              {selectedQuota?.department?.name_ar}
            </span>
            ؟
          </p>
          <div className="flex justify-end gap-3 pt-4 border-t">
            <Button
              variant="secondary"
              onClick={() => setShowDeleteModal(false)}
              disabled={submitting}
            >
              إلغاء
            </Button>
            <Button
              variant="danger"
              onClick={handleDelete}
              loading={submitting}
              icon={HiTrash}
            >
              حذف
            </Button>
          </div>
        </div>
      </Modal>
    </div>
  );
}
