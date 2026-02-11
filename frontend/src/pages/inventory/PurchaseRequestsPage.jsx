import React, { useState, useEffect, useCallback } from 'react';
import toast from 'react-hot-toast';
import { purchaseRequestsApi, inventoryItemsApi } from '../../services/inventoryApi';
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
  HiShoppingCart,
  HiSearch,
  HiRefresh,
  HiEye,
  HiCheck,
  HiX,
  HiClipboardList,
  HiClock,
  HiCheckCircle,
  HiXCircle,
  HiDownload,
} from 'react-icons/hi';

/**
 * صفحة طلبات الشراء
 * Purchase Requests Page
 */
export default function PurchaseRequestsPage() {
  const [requests, setRequests] = useState([]);
  const [departments, setDepartments] = useState([]);
  const [items, setItems] = useState([]);
  const [loading, setLoading] = useState(true);
  const [searchTerm, setSearchTerm] = useState('');
  const [statusFilter, setStatusFilter] = useState('');
  const [showModal, setShowModal] = useState(false);
  const [showViewModal, setShowViewModal] = useState(false);
  const [showDeleteModal, setShowDeleteModal] = useState(false);
  const [showApproveModal, setShowApproveModal] = useState(false);
  const [showRejectModal, setShowRejectModal] = useState(false);
  const [selectedRequest, setSelectedRequest] = useState(null);
  const [approvalAction, setApprovalAction] = useState(null);
  const [formData, setFormData] = useState({
    title: '',
    department_id: '',
    priority: 'normal',
    required_date: '',
    notes: '',
    items: [],
  });
  const [newItem, setNewItem] = useState({
    item_id: '',
    quantity: '',
    unit_price: '',
    notes: '',
  });
  const [rejectReason, setRejectReason] = useState('');
  const [approvalNotes, setApprovalNotes] = useState('');
  const [formErrors, setFormErrors] = useState({});
  const [submitting, setSubmitting] = useState(false);

  // Status types
  const statusTypes = [
    { value: 'draft', label: 'مسودة', color: 'gray', icon: HiClipboardList },
    { value: 'pending', label: 'قيد الانتظار', color: 'warning', icon: HiClock },
    { value: 'manager_approved', label: 'موافقة المدير', color: 'info', icon: HiCheck },
    { value: 'finance_approved', label: 'موافقة المالية', color: 'info', icon: HiCheck },
    { value: 'ceo_approved', label: 'موافقة المدير العام', color: 'success', icon: HiCheckCircle },
    { value: 'rejected', label: 'مرفوض', color: 'danger', icon: HiXCircle },
    { value: 'completed', label: 'مكتمل', color: 'success', icon: HiCheckCircle },
  ];

  // Priority types
  const priorityTypes = [
    { value: 'low', label: 'منخفضة', color: 'gray' },
    { value: 'normal', label: 'عادية', color: 'info' },
    { value: 'high', label: 'عالية', color: 'warning' },
    { value: 'urgent', label: 'عاجلة', color: 'danger' },
  ];

  // Status filter options
  const statusFilterOptions = [
    { value: '', label: 'جميع الحالات' },
    ...statusTypes.map((s) => ({ value: s.value, label: s.label })),
  ];

  // Load requests
  const loadRequests = useCallback(async () => {
    try {
      setLoading(true);
      const response = await purchaseRequestsApi.getAll({
        search: searchTerm,
        status: statusFilter,
      });
      setRequests(response.data?.data || response.data || []);
    } catch (error) {
      toast.error('فشل في تحميل طلبات الشراء');
      console.error('Error loading requests:', error);
    } finally {
      setLoading(false);
    }
  }, [searchTerm, statusFilter]);

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
    loadRequests();
  }, [loadRequests]);

  // Reset form
  const resetForm = () => {
    setFormData({
      title: '',
      department_id: '',
      priority: 'normal',
      required_date: '',
      notes: '',
      items: [],
    });
    setNewItem({
      item_id: '',
      quantity: '',
      unit_price: '',
      notes: '',
    });
    setFormErrors({});
    setSelectedRequest(null);
  };

  // Open create modal
  const handleCreate = () => {
    resetForm();
    setShowModal(true);
  };

  // Open edit modal
  const handleEdit = (request) => {
    setSelectedRequest(request);
    setFormData({
      title: request.title || '',
      department_id: request.department_id || '',
      priority: request.priority || 'normal',
      required_date: request.required_date?.split('T')[0] || '',
      notes: request.notes || '',
      items: request.items || [],
    });
    setFormErrors({});
    setShowModal(true);
  };

  // Open view modal
  const handleView = (request) => {
    setSelectedRequest(request);
    setShowViewModal(true);
  };

  // Open delete confirmation
  const handleDeleteClick = (request) => {
    setSelectedRequest(request);
    setShowDeleteModal(true);
  };

  // Open approve modal
  const handleApproveClick = (request, action) => {
    setSelectedRequest(request);
    setApprovalAction(action);
    setApprovalNotes('');
    setShowApproveModal(true);
  };

  // Open reject modal
  const handleRejectClick = (request) => {
    setSelectedRequest(request);
    setRejectReason('');
    setShowRejectModal(true);
  };

  // Add item to list
  const handleAddItem = () => {
    if (!newItem.item_id || !newItem.quantity) {
      setFormErrors({ item: 'الصنف والكمية مطلوبان' });
      return;
    }
    const item = items.find((i) => i.id === newItem.item_id);
    if (!item) return;

    setFormData((prev) => ({
      ...prev,
      items: [
        ...prev.items,
        {
          ...newItem,
          item_name: item.name_ar,
          item_code: item.code,
          quantity: Number(newItem.quantity),
          unit_price: newItem.unit_price ? Number(newItem.unit_price) : item.unit_cost || 0,
        },
      ],
    }));
    setNewItem({
      item_id: '',
      quantity: '',
      unit_price: '',
      notes: '',
    });
    setFormErrors({});
  };

  // Remove item from list
  const handleRemoveItem = (index) => {
    setFormData((prev) => ({
      ...prev,
      items: prev.items.filter((_, i) => i !== index),
    }));
  };

  // Validate form
  const validateForm = () => {
    const errors = {};
    if (!formData.title?.trim()) {
      errors.title = 'عنوان الطلب مطلوب';
    }
    if (!formData.department_id) {
      errors.department_id = 'القسم مطلوب';
    }
    if (formData.items.length === 0) {
      errors.items = 'أضف صنف واحد على الأقل';
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
        required_date: formData.required_date || null,
      };

      if (selectedRequest) {
        await purchaseRequestsApi.update(selectedRequest.id, data);
        toast.success('تم تحديث الطلب بنجاح');
      } else {
        await purchaseRequestsApi.create(data);
        toast.success('تم إنشاء الطلب بنجاح');
      }
      setShowModal(false);
      resetForm();
      loadRequests();
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

  // Submit request
  const handleSubmitRequest = async (request) => {
    try {
      setSubmitting(true);
      await purchaseRequestsApi.submit(request.id);
      toast.success('تم تقديم الطلب بنجاح');
      loadRequests();
    } catch (error) {
      toast.error(error.response?.data?.message || 'فشل في تقديم الطلب');
    } finally {
      setSubmitting(false);
    }
  };

  // Approve request
  const handleApprove = async () => {
    if (!selectedRequest || !approvalAction) return;

    try {
      setSubmitting(true);
      const data = { notes: approvalNotes };

      switch (approvalAction) {
        case 'manager':
          await purchaseRequestsApi.approveManager(selectedRequest.id, data);
          toast.success('تمت موافقة المدير');
          break;
        case 'finance':
          await purchaseRequestsApi.approveFinance(selectedRequest.id, data);
          toast.success('تمت موافقة المالية');
          break;
        case 'ceo':
          await purchaseRequestsApi.approveCEO(selectedRequest.id, data);
          toast.success('تمت موافقة المدير العام');
          break;
        case 'complete':
          await purchaseRequestsApi.complete(selectedRequest.id, data);
          toast.success('تم إكمال الطلب');
          break;
      }

      setShowApproveModal(false);
      setApprovalAction(null);
      setApprovalNotes('');
      loadRequests();
    } catch (error) {
      toast.error(error.response?.data?.message || 'فشل في الموافقة');
    } finally {
      setSubmitting(false);
    }
  };

  // Reject request
  const handleReject = async () => {
    if (!selectedRequest) return;
    if (!rejectReason.trim()) {
      setFormErrors({ reject: 'سبب الرفض مطلوب' });
      return;
    }

    try {
      setSubmitting(true);
      await purchaseRequestsApi.reject(selectedRequest.id, { reason: rejectReason });
      toast.success('تم رفض الطلب');
      setShowRejectModal(false);
      setRejectReason('');
      loadRequests();
    } catch (error) {
      toast.error(error.response?.data?.message || 'فشل في رفض الطلب');
    } finally {
      setSubmitting(false);
    }
  };

  // Delete request
  const handleDelete = async () => {
    if (!selectedRequest) return;

    try {
      setSubmitting(true);
      await purchaseRequestsApi.delete(selectedRequest.id);
      toast.success('تم حذف الطلب بنجاح');
      setShowDeleteModal(false);
      setSelectedRequest(null);
      loadRequests();
    } catch (error) {
      toast.error(error.response?.data?.message || 'فشل في حذف الطلب');
    } finally {
      setSubmitting(false);
    }
  };

  // Handle export
  const handleExport = async () => {
    try {
      const response = await purchaseRequestsApi.exportList({
        search: searchTerm,
        status: statusFilter,
      });
      const url = window.URL.createObjectURL(new Blob([response.data]));
      const link = document.createElement('a');
      link.href = url;
      link.setAttribute('download', 'purchase-requests.xlsx');
      document.body.appendChild(link);
      link.click();
      link.remove();
      toast.success('تم تصدير البيانات بنجاح');
    } catch (error) {
      toast.error('فشل في تصدير البيانات');
    }
  };

  // Handle input change
  const handleInputChange = (e) => {
    const { name, value } = e.target;
    setFormData((prev) => ({
      ...prev,
      [name]: value,
    }));
    if (formErrors[name]) {
      setFormErrors((prev) => ({ ...prev, [name]: null }));
    }
  };

  // Handle new item input change
  const handleNewItemChange = (e) => {
    const { name, value } = e.target;
    setNewItem((prev) => ({
      ...prev,
      [name]: value,
    }));
  };

  // Get status info
  const getStatusInfo = (status) => {
    const found = statusTypes.find((s) => s.value === status);
    return found || { label: status, color: 'gray' };
  };

  // Get priority info
  const getPriorityInfo = (priority) => {
    const found = priorityTypes.find((p) => p.value === priority);
    return found || { label: priority, color: 'gray' };
  };

  // Calculate total
  const calculateTotal = (itemsList) => {
    return itemsList.reduce((sum, item) => sum + (item.quantity * item.unit_price), 0);
  };

  // Format currency
  const formatCurrency = (value) => {
    if (!value) return '-';
    return new Intl.NumberFormat('ar-SA', {
      style: 'currency',
      currency: 'SAR',
      minimumFractionDigits: 2,
    }).format(value);
  };

  // Format date
  const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('ar-SA');
  };

  // Can edit request
  const canEdit = (request) => {
    return request.status === 'draft';
  };

  // Can submit request
  const canSubmit = (request) => {
    return request.status === 'draft' && request.items?.length > 0;
  };

  // Get next approval action
  const getNextApprovalAction = (request) => {
    switch (request.status) {
      case 'pending':
        return { action: 'manager', label: 'موافقة المدير' };
      case 'manager_approved':
        return { action: 'finance', label: 'موافقة المالية' };
      case 'finance_approved':
        return { action: 'ceo', label: 'موافقة المدير العام' };
      case 'ceo_approved':
        return { action: 'complete', label: 'إكمال الطلب' };
      default:
        return null;
    }
  };

  return (
    <div className="space-y-6" dir="rtl">
      {/* Header */}
      <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">طلبات الشراء</h1>
          <p className="text-gray-600 mt-1">إدارة طلبات الشراء والموافقات</p>
        </div>
        <div className="flex gap-2">
          <Button variant="secondary" icon={HiDownload} onClick={handleExport}>
            تصدير
          </Button>
          <Button icon={HiPlus} onClick={handleCreate}>
            طلب شراء جديد
          </Button>
        </div>
      </div>

      {/* Stats Cards */}
      <div className="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <Card>
          <div className="p-4">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm text-gray-500">قيد الانتظار</p>
                <p className="text-2xl font-bold text-yellow-600">
                  {requests.filter((r) => ['pending', 'manager_approved', 'finance_approved'].includes(r.status)).length}
                </p>
              </div>
              <HiClock className="w-8 h-8 text-yellow-400" />
            </div>
          </div>
        </Card>
        <Card>
          <div className="p-4">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm text-gray-500">معتمدة</p>
                <p className="text-2xl font-bold text-green-600">
                  {requests.filter((r) => r.status === 'ceo_approved').length}
                </p>
              </div>
              <HiCheckCircle className="w-8 h-8 text-green-400" />
            </div>
          </div>
        </Card>
        <Card>
          <div className="p-4">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm text-gray-500">مكتملة</p>
                <p className="text-2xl font-bold text-blue-600">
                  {requests.filter((r) => r.status === 'completed').length}
                </p>
              </div>
              <HiShoppingCart className="w-8 h-8 text-blue-400" />
            </div>
          </div>
        </Card>
        <Card>
          <div className="p-4">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm text-gray-500">مرفوضة</p>
                <p className="text-2xl font-bold text-red-600">
                  {requests.filter((r) => r.status === 'rejected').length}
                </p>
              </div>
              <HiXCircle className="w-8 h-8 text-red-400" />
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
                placeholder="بحث بالعنوان أو الرقم..."
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
                icon={HiSearch}
              />
            </div>
            <div className="w-full sm:w-48">
              <Select
                value={statusFilter}
                onChange={(e) => setStatusFilter(e.target.value)}
                options={statusFilterOptions}
              />
            </div>
            <Button variant="secondary" icon={HiRefresh} onClick={loadRequests}>
              تحديث
            </Button>
          </div>
        </div>
      </Card>

      {/* Requests Table */}
      <Card>
        <CardHeader
          title="قائمة الطلبات"
          subtitle={`${requests.length} طلب`}
          icon={HiShoppingCart}
        />
        <Table>
          <TableHead>
            <TableRow>
              <TableHeader>رقم الطلب</TableHeader>
              <TableHeader>العنوان</TableHeader>
              <TableHeader>القسم</TableHeader>
              <TableHeader>الأولوية</TableHeader>
              <TableHeader>الإجمالي</TableHeader>
              <TableHeader>الحالة</TableHeader>
              <TableHeader>الإجراءات</TableHeader>
            </TableRow>
          </TableHead>
          <TableBody>
            {loading ? (
              <TableLoading colSpan={7} />
            ) : requests.length === 0 ? (
              <TableEmpty
                colSpan={7}
                icon={HiShoppingCart}
                message="لا توجد طلبات شراء"
                actionLabel="إنشاء طلب جديد"
                onAction={handleCreate}
              />
            ) : (
              requests.map((request) => {
                const statusInfo = getStatusInfo(request.status);
                const priorityInfo = getPriorityInfo(request.priority);
                const nextAction = getNextApprovalAction(request);
                return (
                  <TableRow key={request.id}>
                    <TableCell className="font-mono text-sm">
                      #{request.id}
                    </TableCell>
                    <TableCell>
                      <div>
                        <span className="font-medium">{request.title}</span>
                        <span className="block text-xs text-gray-500">
                          {formatDate(request.created_at)}
                        </span>
                      </div>
                    </TableCell>
                    <TableCell>{request.department?.name_ar || '-'}</TableCell>
                    <TableCell>
                      <Badge variant={priorityInfo.color}>{priorityInfo.label}</Badge>
                    </TableCell>
                    <TableCell className="font-medium">
                      {formatCurrency(calculateTotal(request.items || []))}
                    </TableCell>
                    <TableCell>
                      <Badge variant={statusInfo.color}>{statusInfo.label}</Badge>
                    </TableCell>
                    <TableCell>
                      <div className="flex items-center gap-1">
                        <Button
                          variant="ghost"
                          size="sm"
                          icon={HiEye}
                          onClick={() => handleView(request)}
                          title="عرض"
                        />
                        {canEdit(request) && (
                          <Button
                            variant="ghost"
                            size="sm"
                            icon={HiPencil}
                            onClick={() => handleEdit(request)}
                            title="تعديل"
                          />
                        )}
                        {canSubmit(request) && (
                          <Button
                            variant="ghost"
                            size="sm"
                            icon={HiCheck}
                            onClick={() => handleSubmitRequest(request)}
                            title="تقديم"
                            className="text-green-600"
                          />
                        )}
                        {nextAction && (
                          <Button
                            variant="ghost"
                            size="sm"
                            icon={HiCheck}
                            onClick={() => handleApproveClick(request, nextAction.action)}
                            title={nextAction.label}
                            className="text-green-600"
                          />
                        )}
                        {['pending', 'manager_approved', 'finance_approved'].includes(request.status) && (
                          <Button
                            variant="ghost"
                            size="sm"
                            icon={HiX}
                            onClick={() => handleRejectClick(request)}
                            title="رفض"
                            className="text-red-600"
                          />
                        )}
                        {canEdit(request) && (
                          <Button
                            variant="ghost"
                            size="sm"
                            icon={HiTrash}
                            onClick={() => handleDeleteClick(request)}
                            className="text-red-600"
                            title="حذف"
                          />
                        )}
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
        title={selectedRequest ? 'تعديل طلب الشراء' : 'طلب شراء جديد'}
        size="lg"
      >
        <form onSubmit={handleSubmit} className="space-y-4">
          <Input
            label="عنوان الطلب"
            name="title"
            value={formData.title}
            onChange={handleInputChange}
            error={formErrors.title}
            required
            placeholder="مثال: طلب مستلزمات طبية - يناير"
          />

          <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
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
              label="الأولوية"
              name="priority"
              value={formData.priority}
              onChange={handleInputChange}
              options={priorityTypes}
            />
            <Input
              label="التاريخ المطلوب"
              name="required_date"
              type="date"
              value={formData.required_date}
              onChange={handleInputChange}
            />
          </div>

          {/* Items Section */}
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-2">
              الأصناف المطلوبة
            </label>

            {/* Add Item */}
            <div className="p-4 bg-gray-50 rounded-lg mb-4">
              <div className="grid grid-cols-1 md:grid-cols-4 gap-3">
                <Select
                  name="item_id"
                  value={newItem.item_id}
                  onChange={handleNewItemChange}
                  placeholder="اختر الصنف"
                  options={items.map((item) => ({
                    value: item.id,
                    label: `${item.name_ar} (${item.code})`,
                  }))}
                />
                <Input
                  name="quantity"
                  type="number"
                  value={newItem.quantity}
                  onChange={handleNewItemChange}
                  placeholder="الكمية"
                  min="1"
                  dir="ltr"
                />
                <Input
                  name="unit_price"
                  type="number"
                  step="0.01"
                  value={newItem.unit_price}
                  onChange={handleNewItemChange}
                  placeholder="السعر (اختياري)"
                  dir="ltr"
                />
                <Button type="button" onClick={handleAddItem} icon={HiPlus}>
                  إضافة
                </Button>
              </div>
              {formErrors.item && (
                <p className="text-sm text-red-600 mt-1">{formErrors.item}</p>
              )}
            </div>

            {/* Items List */}
            {formData.items.length > 0 ? (
              <div className="border rounded-lg divide-y">
                {formData.items.map((item, index) => (
                  <div key={index} className="p-3 flex justify-between items-center">
                    <div>
                      <span className="font-medium">{item.item_name}</span>
                      <span className="text-sm text-gray-500 mr-2">({item.item_code})</span>
                    </div>
                    <div className="flex items-center gap-4">
                      <span>الكمية: {item.quantity}</span>
                      <span>{formatCurrency(item.unit_price)}</span>
                      <span className="font-medium">
                        {formatCurrency(item.quantity * item.unit_price)}
                      </span>
                      <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        icon={HiTrash}
                        onClick={() => handleRemoveItem(index)}
                        className="text-red-600"
                      />
                    </div>
                  </div>
                ))}
                <div className="p-3 bg-gray-50 flex justify-between items-center">
                  <span className="font-medium">الإجمالي</span>
                  <span className="font-bold text-lg text-primary-600">
                    {formatCurrency(calculateTotal(formData.items))}
                  </span>
                </div>
              </div>
            ) : (
              <p className="text-center text-gray-500 py-4">
                لم يتم إضافة أصناف بعد
              </p>
            )}
            {formErrors.items && (
              <p className="text-sm text-red-600 mt-1">{formErrors.items}</p>
            )}
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
              {selectedRequest ? 'تحديث' : 'حفظ كمسودة'}
            </Button>
          </div>
        </form>
      </Modal>

      {/* View Modal */}
      <Modal
        isOpen={showViewModal}
        onClose={() => setShowViewModal(false)}
        title="تفاصيل طلب الشراء"
        size="lg"
      >
        {selectedRequest && (
          <div className="space-y-6">
            {/* Header Info */}
            <div className="flex justify-between items-start">
              <div>
                <h3 className="text-lg font-medium">{selectedRequest.title}</h3>
                <p className="text-sm text-gray-500">طلب رقم #{selectedRequest.id}</p>
              </div>
              <Badge variant={getStatusInfo(selectedRequest.status).color} size="lg">
                {getStatusInfo(selectedRequest.status).label}
              </Badge>
            </div>

            {/* Details */}
            <div className="grid grid-cols-2 gap-4">
              <div>
                <span className="text-sm text-gray-500">القسم</span>
                <p>{selectedRequest.department?.name_ar}</p>
              </div>
              <div>
                <span className="text-sm text-gray-500">الأولوية</span>
                <p>
                  <Badge variant={getPriorityInfo(selectedRequest.priority).color}>
                    {getPriorityInfo(selectedRequest.priority).label}
                  </Badge>
                </p>
              </div>
              <div>
                <span className="text-sm text-gray-500">تاريخ الإنشاء</span>
                <p>{formatDate(selectedRequest.created_at)}</p>
              </div>
              <div>
                <span className="text-sm text-gray-500">التاريخ المطلوب</span>
                <p>{formatDate(selectedRequest.required_date)}</p>
              </div>
            </div>

            {/* Items */}
            <div>
              <h4 className="font-medium mb-3">الأصناف المطلوبة</h4>
              <div className="border rounded-lg divide-y">
                {(selectedRequest.items || []).map((item, index) => (
                  <div key={index} className="p-3 flex justify-between items-center">
                    <div>
                      <span className="font-medium">{item.item_name || item.item?.name_ar}</span>
                      <span className="text-sm text-gray-500 mr-2">
                        ({item.item_code || item.item?.code})
                      </span>
                    </div>
                    <div className="flex items-center gap-4 text-sm">
                      <span>الكمية: {item.quantity}</span>
                      <span>{formatCurrency(item.unit_price)}</span>
                      <span className="font-medium">
                        {formatCurrency(item.quantity * item.unit_price)}
                      </span>
                    </div>
                  </div>
                ))}
                <div className="p-3 bg-gray-50 flex justify-between items-center">
                  <span className="font-medium">الإجمالي</span>
                  <span className="font-bold text-lg text-primary-600">
                    {formatCurrency(calculateTotal(selectedRequest.items || []))}
                  </span>
                </div>
              </div>
            </div>

            {/* Notes */}
            {selectedRequest.notes && (
              <div>
                <span className="text-sm text-gray-500">ملاحظات</span>
                <p className="text-gray-700">{selectedRequest.notes}</p>
              </div>
            )}

            {/* Workflow History */}
            {selectedRequest.workflow_logs && selectedRequest.workflow_logs.length > 0 && (
              <div>
                <h4 className="font-medium mb-3">سجل الموافقات</h4>
                <div className="border rounded-lg divide-y">
                  {selectedRequest.workflow_logs.map((log, index) => (
                    <div key={index} className="p-3">
                      <div className="flex justify-between items-center">
                        <div>
                          <span className="font-medium">{log.action}</span>
                          <span className="text-sm text-gray-500 mr-2">
                            بواسطة {log.user?.name}
                          </span>
                        </div>
                        <span className="text-sm text-gray-500">
                          {formatDate(log.created_at)}
                        </span>
                      </div>
                      {log.notes && (
                        <p className="text-sm text-gray-600 mt-1">{log.notes}</p>
                      )}
                    </div>
                  ))}
                </div>
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

      {/* Approve Modal */}
      <Modal
        isOpen={showApproveModal}
        onClose={() => setShowApproveModal(false)}
        title="تأكيد الموافقة"
        size="sm"
      >
        <div className="space-y-4">
          <p className="text-gray-600">
            هل أنت متأكد من الموافقة على الطلب{' '}
            <span className="font-bold text-gray-900">
              {selectedRequest?.title}
            </span>
            ؟
          </p>
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
              ملاحظات (اختياري)
            </label>
            <textarea
              value={approvalNotes}
              onChange={(e) => setApprovalNotes(e.target.value)}
              rows={2}
              className="w-full rounded-lg border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
              placeholder="أي ملاحظات..."
            />
          </div>
          <div className="flex justify-end gap-3 pt-4 border-t">
            <Button
              variant="secondary"
              onClick={() => setShowApproveModal(false)}
              disabled={submitting}
            >
              إلغاء
            </Button>
            <Button onClick={handleApprove} loading={submitting} icon={HiCheck}>
              موافقة
            </Button>
          </div>
        </div>
      </Modal>

      {/* Reject Modal */}
      <Modal
        isOpen={showRejectModal}
        onClose={() => setShowRejectModal(false)}
        title="رفض الطلب"
        size="sm"
      >
        <div className="space-y-4">
          <p className="text-gray-600">
            سيتم رفض الطلب{' '}
            <span className="font-bold text-gray-900">
              {selectedRequest?.title}
            </span>
          </p>
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
              سبب الرفض <span className="text-red-500">*</span>
            </label>
            <textarea
              value={rejectReason}
              onChange={(e) => setRejectReason(e.target.value)}
              rows={3}
              className="w-full rounded-lg border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
              placeholder="أدخل سبب الرفض..."
              required
            />
            {formErrors.reject && (
              <p className="text-sm text-red-600 mt-1">{formErrors.reject}</p>
            )}
          </div>
          <div className="flex justify-end gap-3 pt-4 border-t">
            <Button
              variant="secondary"
              onClick={() => setShowRejectModal(false)}
              disabled={submitting}
            >
              إلغاء
            </Button>
            <Button
              variant="danger"
              onClick={handleReject}
              loading={submitting}
              icon={HiX}
            >
              رفض
            </Button>
          </div>
        </div>
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
            هل أنت متأكد من حذف الطلب{' '}
            <span className="font-bold text-gray-900">
              {selectedRequest?.title}
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
