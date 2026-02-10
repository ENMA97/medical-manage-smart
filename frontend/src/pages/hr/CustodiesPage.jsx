import React, { useState, useEffect, useCallback } from 'react';
import toast from 'react-hot-toast';
import { custodiesApi, employeesApi } from '../../services/hrApi';
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
  HiKey,
  HiSearch,
  HiRefresh,
  HiEye,
  HiCheck,
  HiArrowLeft,
} from 'react-icons/hi';

// Custody status
const CUSTODY_STATUS = {
  pending: { label: 'معلقة', variant: 'warning' },
  assigned: { label: 'مسلمة', variant: 'success' },
  returned: { label: 'مستردة', variant: 'info' },
  damaged: { label: 'تالفة', variant: 'danger' },
  lost: { label: 'مفقودة', variant: 'danger' },
};

// Custody categories
const CUSTODY_CATEGORIES = [
  { value: 'equipment', label: 'معدات' },
  { value: 'vehicle', label: 'مركبة' },
  { value: 'device', label: 'جهاز' },
  { value: 'key', label: 'مفتاح' },
  { value: 'card', label: 'بطاقة' },
  { value: 'uniform', label: 'زي موحد' },
  { value: 'tool', label: 'أداة' },
  { value: 'other', label: 'أخرى' },
];

/**
 * صفحة إدارة العهد
 * Custodies Management Page
 */
export default function CustodiesPage() {
  const [custodies, setCustodies] = useState([]);
  const [employees, setEmployees] = useState([]);
  const [loading, setLoading] = useState(true);
  const [showModal, setShowModal] = useState(false);
  const [showViewModal, setShowViewModal] = useState(false);
  const [showDeleteModal, setShowDeleteModal] = useState(false);
  const [showReturnModal, setShowReturnModal] = useState(false);
  const [selectedCustody, setSelectedCustody] = useState(null);
  const [submitting, setSubmitting] = useState(false);

  // Filters
  const [filters, setFilters] = useState({
    search: '',
    status: '',
    category: '',
    employee_id: '',
  });

  // Form data
  const [formData, setFormData] = useState({
    employee_id: '',
    name: '',
    description: '',
    category: 'equipment',
    serial_number: '',
    value: '',
    assigned_date: '',
    notes: '',
  });
  const [formErrors, setFormErrors] = useState({});

  // Return data
  const [returnData, setReturnData] = useState({
    return_date: '',
    return_condition: 'good',
    return_notes: '',
  });

  // Load custodies
  const loadCustodies = useCallback(async () => {
    try {
      setLoading(true);
      const params = {};
      if (filters.search) params.search = filters.search;
      if (filters.status) params.status = filters.status;
      if (filters.category) params.category = filters.category;
      if (filters.employee_id) params.employee_id = filters.employee_id;

      const response = await custodiesApi.getAll(params);
      setCustodies(response.data?.data || response.data || []);
    } catch (error) {
      toast.error('فشل في تحميل العهد');
      console.error('Error loading custodies:', error);
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
    loadCustodies();
  }, [loadCustodies]);

  // Reset form
  const resetForm = () => {
    setFormData({
      employee_id: '',
      name: '',
      description: '',
      category: 'equipment',
      serial_number: '',
      value: '',
      assigned_date: new Date().toISOString().split('T')[0],
      notes: '',
    });
    setFormErrors({});
    setSelectedCustody(null);
  };

  // Open create modal
  const handleCreate = () => {
    resetForm();
    setShowModal(true);
  };

  // Open edit modal
  const handleEdit = (custody) => {
    setSelectedCustody(custody);
    setFormData({
      employee_id: custody.employee_id || '',
      name: custody.name || '',
      description: custody.description || '',
      category: custody.category || 'equipment',
      serial_number: custody.serial_number || '',
      value: custody.value || '',
      assigned_date: custody.assigned_date || '',
      notes: custody.notes || '',
    });
    setFormErrors({});
    setShowModal(true);
  };

  // View custody details
  const handleView = (custody) => {
    setSelectedCustody(custody);
    setShowViewModal(true);
  };

  // Open delete confirmation
  const handleDeleteClick = (custody) => {
    setSelectedCustody(custody);
    setShowDeleteModal(true);
  };

  // Open return modal
  const handleReturnClick = (custody) => {
    setSelectedCustody(custody);
    setReturnData({
      return_date: new Date().toISOString().split('T')[0],
      return_condition: 'good',
      return_notes: '',
    });
    setShowReturnModal(true);
  };

  // Validate form
  const validateForm = () => {
    const errors = {};
    if (!formData.employee_id) errors.employee_id = 'الموظف مطلوب';
    if (!formData.name?.trim()) errors.name = 'اسم العهدة مطلوب';
    if (!formData.category) errors.category = 'التصنيف مطلوب';
    if (!formData.assigned_date) errors.assigned_date = 'تاريخ التسليم مطلوب';

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
        value: formData.value ? Number(formData.value) : null,
      };

      if (selectedCustody) {
        await custodiesApi.update(selectedCustody.id, data);
        toast.success('تم تحديث العهدة بنجاح');
      } else {
        await custodiesApi.create(data);
        toast.success('تم إضافة العهدة بنجاح');
      }
      setShowModal(false);
      resetForm();
      loadCustodies();
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

  // Delete custody
  const handleDelete = async () => {
    if (!selectedCustody) return;

    try {
      setSubmitting(true);
      await custodiesApi.delete(selectedCustody.id);
      toast.success('تم حذف العهدة بنجاح');
      setShowDeleteModal(false);
      setSelectedCustody(null);
      loadCustodies();
    } catch (error) {
      const message = error.response?.data?.message || 'فشل في حذف العهدة';
      toast.error(message);
    } finally {
      setSubmitting(false);
    }
  };

  // Return custody
  const handleReturn = async () => {
    if (!selectedCustody) return;

    try {
      setSubmitting(true);
      await custodiesApi.return(selectedCustody.id, returnData);
      toast.success('تم استرداد العهدة بنجاح');
      setShowReturnModal(false);
      setSelectedCustody(null);
      loadCustodies();
    } catch (error) {
      const message = error.response?.data?.message || 'فشل في استرداد العهدة';
      toast.error(message);
    } finally {
      setSubmitting(false);
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

  // Get category label
  const getCategoryLabel = (category) => {
    return CUSTODY_CATEGORIES.find((c) => c.value === category)?.label || category;
  };

  // Calculate statistics
  const stats = {
    total: custodies.length,
    assigned: custodies.filter((c) => c.status === 'assigned').length,
    returned: custodies.filter((c) => c.status === 'returned').length,
    totalValue: custodies
      .filter((c) => c.status === 'assigned')
      .reduce((sum, c) => sum + (c.value || 0), 0),
  };

  return (
    <div className="space-y-6" dir="rtl">
      {/* Header */}
      <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">إدارة العهد</h1>
          <p className="text-gray-600 mt-1">تتبع وإدارة عهد الموظفين</p>
        </div>
        <Button icon={HiPlus} onClick={handleCreate}>
          إضافة عهدة
        </Button>
      </div>

      {/* Statistics */}
      <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
        <Card className="p-4">
          <div className="text-sm text-gray-500">إجمالي العهد</div>
          <div className="text-2xl font-bold text-gray-900">{stats.total}</div>
        </Card>
        <Card className="p-4">
          <div className="text-sm text-gray-500">عهد مسلمة</div>
          <div className="text-2xl font-bold text-green-600">{stats.assigned}</div>
        </Card>
        <Card className="p-4">
          <div className="text-sm text-gray-500">عهد مستردة</div>
          <div className="text-2xl font-bold text-blue-600">{stats.returned}</div>
        </Card>
        <Card className="p-4">
          <div className="text-sm text-gray-500">قيمة العهد المسلمة</div>
          <div className="text-2xl font-bold text-primary-600">{formatCurrency(stats.totalValue)}</div>
        </Card>
      </div>

      {/* Filters */}
      <Card>
        <div className="p-4">
          <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
            <Input
              placeholder="بحث..."
              name="search"
              value={filters.search}
              onChange={(e) => setFilters({ ...filters, search: e.target.value })}
              icon={HiSearch}
            />
            <Select
              value={filters.employee_id}
              onChange={(e) => setFilters({ ...filters, employee_id: e.target.value })}
              options={[
                { value: '', label: 'جميع الموظفين' },
                ...employees.map((e) => ({ value: e.id, label: e.name_ar })),
              ]}
            />
            <Select
              value={filters.category}
              onChange={(e) => setFilters({ ...filters, category: e.target.value })}
              options={[
                { value: '', label: 'جميع التصنيفات' },
                ...CUSTODY_CATEGORIES,
              ]}
            />
            <Select
              value={filters.status}
              onChange={(e) => setFilters({ ...filters, status: e.target.value })}
              options={[
                { value: '', label: 'جميع الحالات' },
                ...Object.entries(CUSTODY_STATUS).map(([value, { label }]) => ({
                  value,
                  label,
                })),
              ]}
            />
            <div className="flex gap-2">
              <Button variant="secondary" icon={HiRefresh} onClick={loadCustodies} className="flex-1">
                تحديث
              </Button>
            </div>
          </div>
        </div>
      </Card>

      {/* Custodies Table */}
      <Card>
        <CardHeader
          title="قائمة العهد"
          subtitle={`${custodies.length} عهدة`}
          icon={HiKey}
        />
        <Table>
          <TableHead>
            <TableRow>
              <TableHeader>العهدة</TableHeader>
              <TableHeader>الموظف</TableHeader>
              <TableHeader>التصنيف</TableHeader>
              <TableHeader>الرقم التسلسلي</TableHeader>
              <TableHeader>القيمة</TableHeader>
              <TableHeader>تاريخ التسليم</TableHeader>
              <TableHeader>الحالة</TableHeader>
              <TableHeader>الإجراءات</TableHeader>
            </TableRow>
          </TableHead>
          <TableBody>
            {loading ? (
              <TableLoading colSpan={8} />
            ) : custodies.length === 0 ? (
              <TableEmpty
                colSpan={8}
                icon={HiKey}
                message="لا توجد عهد"
                actionLabel="إضافة عهدة جديدة"
                onAction={handleCreate}
              />
            ) : (
              custodies.map((custody) => (
                <TableRow key={custody.id}>
                  <TableCell>
                    <div>
                      <div className="font-medium">{custody.name}</div>
                      {custody.description && (
                        <div className="text-sm text-gray-500 truncate max-w-xs">
                          {custody.description}
                        </div>
                      )}
                    </div>
                  </TableCell>
                  <TableCell>{custody.employee?.name_ar || '-'}</TableCell>
                  <TableCell>
                    <Badge variant="info">{getCategoryLabel(custody.category)}</Badge>
                  </TableCell>
                  <TableCell className="font-mono text-sm">
                    {custody.serial_number || '-'}
                  </TableCell>
                  <TableCell>{formatCurrency(custody.value)}</TableCell>
                  <TableCell>{custody.assigned_date || '-'}</TableCell>
                  <TableCell>
                    <Badge variant={CUSTODY_STATUS[custody.status]?.variant || 'gray'}>
                      {CUSTODY_STATUS[custody.status]?.label || custody.status}
                    </Badge>
                  </TableCell>
                  <TableCell>
                    <div className="flex items-center gap-1">
                      <Button
                        variant="ghost"
                        size="sm"
                        icon={HiEye}
                        onClick={() => handleView(custody)}
                        title="عرض"
                      />
                      {custody.status === 'assigned' && (
                        <Button
                          variant="ghost"
                          size="sm"
                          icon={HiArrowLeft}
                          onClick={() => handleReturnClick(custody)}
                          className="text-blue-600 hover:text-blue-700 hover:bg-blue-50"
                          title="استرداد"
                        />
                      )}
                      {custody.status !== 'returned' && (
                        <Button
                          variant="ghost"
                          size="sm"
                          icon={HiPencil}
                          onClick={() => handleEdit(custody)}
                          title="تعديل"
                        />
                      )}
                      <Button
                        variant="ghost"
                        size="sm"
                        icon={HiTrash}
                        onClick={() => handleDeleteClick(custody)}
                        className="text-red-600 hover:text-red-700 hover:bg-red-50"
                        title="حذف"
                      />
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
        title={selectedCustody ? 'تعديل العهدة' : 'إضافة عهدة جديدة'}
        size="md"
      >
        <form onSubmit={handleSubmit} className="space-y-4">
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

          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <Input
              label="اسم العهدة"
              name="name"
              value={formData.name}
              onChange={(e) => setFormData({ ...formData, name: e.target.value })}
              error={formErrors.name}
              required
              placeholder="مثال: لابتوب Dell"
            />
            <Select
              label="التصنيف"
              name="category"
              value={formData.category}
              onChange={(e) => setFormData({ ...formData, category: e.target.value })}
              error={formErrors.category}
              required
              options={CUSTODY_CATEGORIES}
            />
          </div>

          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <Input
              label="الرقم التسلسلي"
              name="serial_number"
              value={formData.serial_number}
              onChange={(e) => setFormData({ ...formData, serial_number: e.target.value })}
              placeholder="SN-XXXXXX"
              dir="ltr"
            />
            <Input
              label="القيمة"
              name="value"
              type="number"
              value={formData.value}
              onChange={(e) => setFormData({ ...formData, value: e.target.value })}
              placeholder="0"
              dir="ltr"
            />
          </div>

          <Input
            label="تاريخ التسليم"
            name="assigned_date"
            type="date"
            value={formData.assigned_date}
            onChange={(e) => setFormData({ ...formData, assigned_date: e.target.value })}
            error={formErrors.assigned_date}
            required
            dir="ltr"
          />

          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">الوصف</label>
            <textarea
              name="description"
              value={formData.description}
              onChange={(e) => setFormData({ ...formData, description: e.target.value })}
              rows={2}
              className="w-full rounded-lg border border-gray-300 px-3 py-2"
              placeholder="وصف العهدة..."
            />
          </div>

          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">ملاحظات</label>
            <textarea
              name="notes"
              value={formData.notes}
              onChange={(e) => setFormData({ ...formData, notes: e.target.value })}
              rows={2}
              className="w-full rounded-lg border border-gray-300 px-3 py-2"
              placeholder="أي ملاحظات إضافية..."
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
              {selectedCustody ? 'تحديث' : 'إضافة'}
            </Button>
          </div>
        </form>
      </Modal>

      {/* View Modal */}
      <Modal
        isOpen={showViewModal}
        onClose={() => setShowViewModal(false)}
        title="تفاصيل العهدة"
        size="md"
      >
        {selectedCustody && (
          <div className="space-y-4">
            <div className="flex items-center justify-between pb-4 border-b">
              <div>
                <h3 className="text-lg font-bold text-gray-900">{selectedCustody.name}</h3>
                <p className="text-gray-500">{getCategoryLabel(selectedCustody.category)}</p>
              </div>
              <Badge
                variant={CUSTODY_STATUS[selectedCustody.status]?.variant || 'gray'}
                size="lg"
              >
                {CUSTODY_STATUS[selectedCustody.status]?.label || selectedCustody.status}
              </Badge>
            </div>

            <div className="bg-gray-50 rounded-lg p-4 space-y-3">
              <div className="flex justify-between">
                <span className="text-gray-500">الموظف</span>
                <span className="font-medium">{selectedCustody.employee?.name_ar}</span>
              </div>
              <div className="flex justify-between">
                <span className="text-gray-500">الرقم التسلسلي</span>
                <span className="font-mono">{selectedCustody.serial_number || '-'}</span>
              </div>
              <div className="flex justify-between">
                <span className="text-gray-500">القيمة</span>
                <span className="font-medium">{formatCurrency(selectedCustody.value)}</span>
              </div>
              <div className="flex justify-between">
                <span className="text-gray-500">تاريخ التسليم</span>
                <span className="font-medium">{selectedCustody.assigned_date}</span>
              </div>
              {selectedCustody.return_date && (
                <div className="flex justify-between">
                  <span className="text-gray-500">تاريخ الاسترداد</span>
                  <span className="font-medium">{selectedCustody.return_date}</span>
                </div>
              )}
            </div>

            {selectedCustody.description && (
              <div>
                <h4 className="font-medium text-gray-900 mb-1">الوصف</h4>
                <p className="text-gray-600">{selectedCustody.description}</p>
              </div>
            )}

            {selectedCustody.notes && (
              <div>
                <h4 className="font-medium text-gray-900 mb-1">ملاحظات</h4>
                <p className="text-gray-600">{selectedCustody.notes}</p>
              </div>
            )}

            <div className="flex justify-end pt-4 border-t">
              <Button variant="secondary" onClick={() => setShowViewModal(false)}>
                إغلاق
              </Button>
            </div>
          </div>
        )}
      </Modal>

      {/* Return Modal */}
      <Modal
        isOpen={showReturnModal}
        onClose={() => setShowReturnModal(false)}
        title="استرداد العهدة"
        size="sm"
      >
        <div className="space-y-4">
          <p className="text-gray-600">
            استرداد العهدة: <span className="font-bold">{selectedCustody?.name}</span>
          </p>

          <Input
            label="تاريخ الاسترداد"
            type="date"
            value={returnData.return_date}
            onChange={(e) => setReturnData({ ...returnData, return_date: e.target.value })}
            required
            dir="ltr"
          />

          <Select
            label="حالة العهدة"
            value={returnData.return_condition}
            onChange={(e) => setReturnData({ ...returnData, return_condition: e.target.value })}
            options={[
              { value: 'good', label: 'جيدة' },
              { value: 'damaged', label: 'تالفة' },
              { value: 'needs_repair', label: 'تحتاج صيانة' },
            ]}
          />

          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">ملاحظات</label>
            <textarea
              value={returnData.return_notes}
              onChange={(e) => setReturnData({ ...returnData, return_notes: e.target.value })}
              rows={2}
              className="w-full rounded-lg border border-gray-300 px-3 py-2"
              placeholder="أي ملاحظات عن حالة العهدة..."
            />
          </div>

          <div className="flex justify-end gap-3 pt-4 border-t">
            <Button
              variant="secondary"
              onClick={() => setShowReturnModal(false)}
              disabled={submitting}
            >
              إلغاء
            </Button>
            <Button onClick={handleReturn} loading={submitting} icon={HiCheck}>
              تأكيد الاسترداد
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
            هل أنت متأكد من حذف العهدة{' '}
            <span className="font-bold text-gray-900">{selectedCustody?.name}</span>؟
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
