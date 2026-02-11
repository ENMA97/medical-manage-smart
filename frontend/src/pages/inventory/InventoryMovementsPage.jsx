import React, { useState, useEffect, useCallback } from 'react';
import toast from 'react-hot-toast';
import {
  inventoryMovementsApi,
  inventoryItemsApi,
  warehousesApi,
} from '../../services/inventoryApi';
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
  HiSwitchHorizontal,
  HiSearch,
  HiRefresh,
  HiEye,
  HiDownload,
  HiArrowDown,
  HiArrowUp,
  HiArrowsExpand,
  HiAdjustments,
  HiReply,
} from 'react-icons/hi';

/**
 * صفحة حركات المخزون
 * Inventory Movements Page
 */
export default function InventoryMovementsPage() {
  const [movements, setMovements] = useState([]);
  const [warehouses, setWarehouses] = useState([]);
  const [items, setItems] = useState([]);
  const [loading, setLoading] = useState(true);
  const [warehouseFilter, setWarehouseFilter] = useState('');
  const [typeFilter, setTypeFilter] = useState('');
  const [dateFrom, setDateFrom] = useState('');
  const [dateTo, setDateTo] = useState('');
  const [showModal, setShowModal] = useState(false);
  const [showViewModal, setShowViewModal] = useState(false);
  const [selectedMovement, setSelectedMovement] = useState(null);
  const [movementType, setMovementType] = useState('receive');
  const [formData, setFormData] = useState({
    item_id: '',
    warehouse_id: '',
    to_warehouse_id: '',
    quantity: '',
    unit_cost: '',
    batch_number: '',
    expiry_date: '',
    reference_number: '',
    notes: '',
    reason: '',
  });
  const [formErrors, setFormErrors] = useState({});
  const [submitting, setSubmitting] = useState(false);

  // Movement types
  const movementTypes = [
    { value: 'receive', label: 'استلام', icon: HiArrowDown, color: 'success' },
    { value: 'issue', label: 'صرف', icon: HiArrowUp, color: 'danger' },
    { value: 'transfer', label: 'تحويل', icon: HiArrowsExpand, color: 'info' },
    { value: 'adjust', label: 'تسوية', icon: HiAdjustments, color: 'warning' },
    { value: 'return', label: 'إرجاع', icon: HiReply, color: 'gray' },
  ];

  // Movement type options for filter
  const typeFilterOptions = [
    { value: '', label: 'جميع الأنواع' },
    ...movementTypes.map((t) => ({ value: t.value, label: t.label })),
  ];

  // Load movements
  const loadMovements = useCallback(async () => {
    try {
      setLoading(true);
      const params = {
        type: typeFilter,
        date_from: dateFrom,
        date_to: dateTo,
      };

      let response;
      if (warehouseFilter) {
        response = await inventoryMovementsApi.getByWarehouse(warehouseFilter, params);
      } else {
        response = await inventoryMovementsApi.getAll(params);
      }

      setMovements(response.data?.data || response.data || []);
    } catch (error) {
      toast.error('فشل في تحميل الحركات');
      console.error('Error loading movements:', error);
    } finally {
      setLoading(false);
    }
  }, [warehouseFilter, typeFilter, dateFrom, dateTo]);

  // Load warehouses
  const loadWarehouses = useCallback(async () => {
    try {
      const response = await warehousesApi.getActive();
      setWarehouses(response.data?.data || response.data || []);
    } catch (error) {
      console.error('Error loading warehouses:', error);
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
    loadWarehouses();
    loadItems();
  }, [loadWarehouses, loadItems]);

  useEffect(() => {
    loadMovements();
  }, [loadMovements]);

  // Reset form
  const resetForm = () => {
    setFormData({
      item_id: '',
      warehouse_id: '',
      to_warehouse_id: '',
      quantity: '',
      unit_cost: '',
      batch_number: '',
      expiry_date: '',
      reference_number: '',
      notes: '',
      reason: '',
    });
    setFormErrors({});
    setSelectedMovement(null);
  };

  // Open create modal with type
  const handleCreate = (type) => {
    resetForm();
    setMovementType(type);
    setShowModal(true);
  };

  // Open view modal
  const handleView = (movement) => {
    setSelectedMovement(movement);
    setShowViewModal(true);
  };

  // Validate form
  const validateForm = () => {
    const errors = {};
    if (!formData.item_id) {
      errors.item_id = 'الصنف مطلوب';
    }
    if (!formData.warehouse_id) {
      errors.warehouse_id = 'المستودع مطلوب';
    }
    if (movementType === 'transfer' && !formData.to_warehouse_id) {
      errors.to_warehouse_id = 'المستودع الوجهة مطلوب';
    }
    if (movementType === 'transfer' && formData.warehouse_id === formData.to_warehouse_id) {
      errors.to_warehouse_id = 'لا يمكن التحويل لنفس المستودع';
    }
    if (!formData.quantity || Number(formData.quantity) <= 0) {
      errors.quantity = 'الكمية مطلوبة وأكبر من صفر';
    }
    if (movementType === 'receive' && !formData.unit_cost) {
      errors.unit_cost = 'تكلفة الوحدة مطلوبة للاستلام';
    }
    if (movementType === 'adjust' && !formData.reason) {
      errors.reason = 'سبب التسوية مطلوب';
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
        item_id: formData.item_id,
        warehouse_id: formData.warehouse_id,
        quantity: Number(formData.quantity),
        unit_cost: formData.unit_cost ? Number(formData.unit_cost) : null,
        batch_number: formData.batch_number || null,
        expiry_date: formData.expiry_date || null,
        reference_number: formData.reference_number || null,
        notes: formData.notes || null,
        reason: formData.reason || null,
      };

      if (movementType === 'transfer') {
        data.to_warehouse_id = formData.to_warehouse_id;
      }

      // Call the appropriate API based on movement type
      switch (movementType) {
        case 'receive':
          await inventoryMovementsApi.receive(data);
          toast.success('تم تسجيل الاستلام بنجاح');
          break;
        case 'issue':
          await inventoryMovementsApi.issue(data);
          toast.success('تم تسجيل الصرف بنجاح');
          break;
        case 'transfer':
          await inventoryMovementsApi.transfer(data);
          toast.success('تم تسجيل التحويل بنجاح');
          break;
        case 'adjust':
          await inventoryMovementsApi.adjust(data);
          toast.success('تم تسجيل التسوية بنجاح');
          break;
        case 'return':
          await inventoryMovementsApi.return(data);
          toast.success('تم تسجيل الإرجاع بنجاح');
          break;
        default:
          await inventoryMovementsApi.create({ ...data, type: movementType });
          toast.success('تم تسجيل الحركة بنجاح');
      }

      setShowModal(false);
      resetForm();
      loadMovements();
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

  // Handle export
  const handleExport = async () => {
    try {
      const response = await inventoryMovementsApi.exportList({
        warehouse_id: warehouseFilter,
        type: typeFilter,
        date_from: dateFrom,
        date_to: dateTo,
      });
      const url = window.URL.createObjectURL(new Blob([response.data]));
      const link = document.createElement('a');
      link.href = url;
      link.setAttribute('download', 'inventory-movements.xlsx');
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

  // Format date
  const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('ar-SA', {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
    });
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

  // Get movement type info
  const getMovementTypeInfo = (type) => {
    const found = movementTypes.find((t) => t.value === type);
    return found || { label: type, color: 'gray' };
  };

  // Get movement icon
  const getMovementIcon = (type) => {
    const typeInfo = getMovementTypeInfo(type);
    const Icon = typeInfo.icon || HiSwitchHorizontal;
    return <Icon className="w-4 h-4" />;
  };

  return (
    <div className="space-y-6" dir="rtl">
      {/* Header */}
      <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">حركات المخزون</h1>
          <p className="text-gray-600 mt-1">تتبع حركات الإدخال والإخراج والتحويل</p>
        </div>
        <div className="flex flex-wrap gap-2">
          <Button variant="secondary" icon={HiDownload} onClick={handleExport}>
            تصدير
          </Button>
          <div className="relative group">
            <Button icon={HiPlus}>
              إضافة حركة
            </Button>
            <div className="absolute left-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-200 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all z-10">
              {movementTypes.map((type) => {
                const Icon = type.icon;
                return (
                  <button
                    key={type.value}
                    onClick={() => handleCreate(type.value)}
                    className="w-full px-4 py-2 text-right hover:bg-gray-50 flex items-center gap-2 first:rounded-t-lg last:rounded-b-lg"
                  >
                    <Icon className="w-4 h-4" />
                    {type.label}
                  </button>
                );
              })}
            </div>
          </div>
        </div>
      </div>

      {/* Quick Actions */}
      <div className="grid grid-cols-2 sm:grid-cols-5 gap-3">
        {movementTypes.map((type) => {
          const Icon = type.icon;
          return (
            <button
              key={type.value}
              onClick={() => handleCreate(type.value)}
              className={`p-4 rounded-lg border-2 border-dashed hover:border-solid transition-all flex flex-col items-center gap-2 text-center ${
                type.color === 'success'
                  ? 'border-green-300 hover:border-green-500 hover:bg-green-50'
                  : type.color === 'danger'
                  ? 'border-red-300 hover:border-red-500 hover:bg-red-50'
                  : type.color === 'info'
                  ? 'border-blue-300 hover:border-blue-500 hover:bg-blue-50'
                  : type.color === 'warning'
                  ? 'border-yellow-300 hover:border-yellow-500 hover:bg-yellow-50'
                  : 'border-gray-300 hover:border-gray-500 hover:bg-gray-50'
              }`}
            >
              <Icon className="w-6 h-6" />
              <span className="text-sm font-medium">{type.label}</span>
            </button>
          );
        })}
      </div>

      {/* Filters */}
      <Card>
        <div className="p-4">
          <div className="flex flex-col sm:flex-row gap-4">
            <div className="w-full sm:w-48">
              <Select
                value={warehouseFilter}
                onChange={(e) => setWarehouseFilter(e.target.value)}
                options={[
                  { value: '', label: 'جميع المستودعات' },
                  ...warehouses.map((w) => ({ value: w.id, label: w.name_ar })),
                ]}
              />
            </div>
            <div className="w-full sm:w-40">
              <Select
                value={typeFilter}
                onChange={(e) => setTypeFilter(e.target.value)}
                options={typeFilterOptions}
              />
            </div>
            <div className="w-full sm:w-40">
              <Input
                type="date"
                value={dateFrom}
                onChange={(e) => setDateFrom(e.target.value)}
                placeholder="من تاريخ"
              />
            </div>
            <div className="w-full sm:w-40">
              <Input
                type="date"
                value={dateTo}
                onChange={(e) => setDateTo(e.target.value)}
                placeholder="إلى تاريخ"
              />
            </div>
            <Button variant="secondary" icon={HiRefresh} onClick={loadMovements}>
              تحديث
            </Button>
          </div>
        </div>
      </Card>

      {/* Movements Table */}
      <Card>
        <CardHeader
          title="سجل الحركات"
          subtitle={`${movements.length} حركة`}
          icon={HiSwitchHorizontal}
        />
        <Table>
          <TableHead>
            <TableRow>
              <TableHeader>الرقم</TableHeader>
              <TableHeader>النوع</TableHeader>
              <TableHeader>الصنف</TableHeader>
              <TableHeader>المستودع</TableHeader>
              <TableHeader>الكمية</TableHeader>
              <TableHeader>التكلفة</TableHeader>
              <TableHeader>التاريخ</TableHeader>
              <TableHeader>الإجراءات</TableHeader>
            </TableRow>
          </TableHead>
          <TableBody>
            {loading ? (
              <TableLoading colSpan={8} />
            ) : movements.length === 0 ? (
              <TableEmpty
                colSpan={8}
                icon={HiSwitchHorizontal}
                message="لا توجد حركات"
                actionLabel="إضافة حركة جديدة"
                onAction={() => handleCreate('receive')}
              />
            ) : (
              movements.map((movement) => {
                const typeInfo = getMovementTypeInfo(movement.type);
                return (
                  <TableRow key={movement.id}>
                    <TableCell className="font-mono text-sm">
                      {movement.reference_number || `#${movement.id}`}
                    </TableCell>
                    <TableCell>
                      <Badge variant={typeInfo.color}>
                        <span className="flex items-center gap-1">
                          {getMovementIcon(movement.type)}
                          {typeInfo.label}
                        </span>
                      </Badge>
                    </TableCell>
                    <TableCell>
                      <div>
                        <span className="font-medium">{movement.item?.name_ar}</span>
                        <span className="block text-xs text-gray-500">
                          {movement.item?.code}
                        </span>
                      </div>
                    </TableCell>
                    <TableCell>
                      <div>
                        <span>{movement.warehouse?.name_ar}</span>
                        {movement.type === 'transfer' && movement.to_warehouse && (
                          <span className="block text-xs text-gray-500">
                            → {movement.to_warehouse.name_ar}
                          </span>
                        )}
                      </div>
                    </TableCell>
                    <TableCell>
                      <span
                        className={`font-bold ${
                          movement.type === 'receive' || movement.type === 'return'
                            ? 'text-green-600'
                            : movement.type === 'issue'
                            ? 'text-red-600'
                            : ''
                        }`}
                      >
                        {movement.type === 'receive' || movement.type === 'return' ? '+' : '-'}
                        {movement.quantity}
                      </span>
                    </TableCell>
                    <TableCell>{formatCurrency(movement.unit_cost)}</TableCell>
                    <TableCell className="text-sm">
                      {formatDate(movement.created_at)}
                    </TableCell>
                    <TableCell>
                      <Button
                        variant="ghost"
                        size="sm"
                        icon={HiEye}
                        onClick={() => handleView(movement)}
                        title="عرض التفاصيل"
                      />
                    </TableCell>
                  </TableRow>
                );
              })
            )}
          </TableBody>
        </Table>
      </Card>

      {/* Create Movement Modal */}
      <Modal
        isOpen={showModal}
        onClose={() => setShowModal(false)}
        title={`${getMovementTypeInfo(movementType).label} مخزون`}
        size="lg"
      >
        <form onSubmit={handleSubmit} className="space-y-4">
          {/* Movement Type Tabs */}
          <div className="flex gap-2 border-b pb-3 overflow-x-auto">
            {movementTypes.map((type) => {
              const Icon = type.icon;
              return (
                <button
                  key={type.value}
                  type="button"
                  onClick={() => setMovementType(type.value)}
                  className={`px-4 py-2 rounded-lg flex items-center gap-2 whitespace-nowrap ${
                    movementType === type.value
                      ? 'bg-primary-100 text-primary-700 font-medium'
                      : 'text-gray-600 hover:bg-gray-100'
                  }`}
                >
                  <Icon className="w-4 h-4" />
                  {type.label}
                </button>
              );
            })}
          </div>

          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
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
            <Select
              label={movementType === 'transfer' ? 'من المستودع' : 'المستودع'}
              name="warehouse_id"
              value={formData.warehouse_id}
              onChange={handleInputChange}
              error={formErrors.warehouse_id}
              required
              placeholder="اختر المستودع"
              options={warehouses.map((w) => ({ value: w.id, label: w.name_ar }))}
            />
          </div>

          {movementType === 'transfer' && (
            <Select
              label="إلى المستودع"
              name="to_warehouse_id"
              value={formData.to_warehouse_id}
              onChange={handleInputChange}
              error={formErrors.to_warehouse_id}
              required
              placeholder="اختر المستودع الوجهة"
              options={warehouses
                .filter((w) => w.id !== formData.warehouse_id)
                .map((w) => ({ value: w.id, label: w.name_ar }))}
            />
          )}

          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <Input
              label="الكمية"
              name="quantity"
              type="number"
              value={formData.quantity}
              onChange={handleInputChange}
              error={formErrors.quantity}
              required
              placeholder="0"
              min="1"
              dir="ltr"
            />
            {movementType === 'receive' && (
              <Input
                label="تكلفة الوحدة"
                name="unit_cost"
                type="number"
                step="0.01"
                value={formData.unit_cost}
                onChange={handleInputChange}
                error={formErrors.unit_cost}
                required
                placeholder="0.00"
                dir="ltr"
              />
            )}
          </div>

          {movementType === 'receive' && (
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              <Input
                label="رقم الدفعة"
                name="batch_number"
                value={formData.batch_number}
                onChange={handleInputChange}
                placeholder="BATCH-001"
                dir="ltr"
              />
              <Input
                label="تاريخ انتهاء الصلاحية"
                name="expiry_date"
                type="date"
                value={formData.expiry_date}
                onChange={handleInputChange}
              />
            </div>
          )}

          {movementType === 'adjust' && (
            <Select
              label="سبب التسوية"
              name="reason"
              value={formData.reason}
              onChange={handleInputChange}
              error={formErrors.reason}
              required
              placeholder="اختر السبب"
              options={[
                { value: 'physical_count', label: 'جرد فعلي' },
                { value: 'damage', label: 'تلف' },
                { value: 'expiry', label: 'انتهاء صلاحية' },
                { value: 'theft', label: 'فقدان/سرقة' },
                { value: 'correction', label: 'تصحيح خطأ' },
                { value: 'other', label: 'أخرى' },
              ]}
            />
          )}

          <Input
            label="رقم المرجع"
            name="reference_number"
            value={formData.reference_number}
            onChange={handleInputChange}
            placeholder="رقم الفاتورة أو أمر الشراء"
            dir="ltr"
          />

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
              تسجيل الحركة
            </Button>
          </div>
        </form>
      </Modal>

      {/* View Movement Modal */}
      <Modal
        isOpen={showViewModal}
        onClose={() => setShowViewModal(false)}
        title="تفاصيل الحركة"
        size="md"
      >
        {selectedMovement && (
          <div className="space-y-4">
            <div className="flex items-center gap-2 mb-4">
              <Badge variant={getMovementTypeInfo(selectedMovement.type).color} size="lg">
                <span className="flex items-center gap-1">
                  {getMovementIcon(selectedMovement.type)}
                  {getMovementTypeInfo(selectedMovement.type).label}
                </span>
              </Badge>
              <span className="text-gray-500">
                {selectedMovement.reference_number || `#${selectedMovement.id}`}
              </span>
            </div>

            <div className="grid grid-cols-2 gap-4">
              <div>
                <span className="text-sm text-gray-500">الصنف</span>
                <p className="font-medium">{selectedMovement.item?.name_ar}</p>
                <p className="text-sm text-gray-500">{selectedMovement.item?.code}</p>
              </div>
              <div>
                <span className="text-sm text-gray-500">الكمية</span>
                <p className="font-bold text-lg">
                  {selectedMovement.type === 'receive' || selectedMovement.type === 'return'
                    ? '+'
                    : '-'}
                  {selectedMovement.quantity}
                </p>
              </div>
            </div>

            <div className="grid grid-cols-2 gap-4">
              <div>
                <span className="text-sm text-gray-500">
                  {selectedMovement.type === 'transfer' ? 'من المستودع' : 'المستودع'}
                </span>
                <p>{selectedMovement.warehouse?.name_ar}</p>
              </div>
              {selectedMovement.type === 'transfer' && selectedMovement.to_warehouse && (
                <div>
                  <span className="text-sm text-gray-500">إلى المستودع</span>
                  <p>{selectedMovement.to_warehouse.name_ar}</p>
                </div>
              )}
            </div>

            {selectedMovement.unit_cost && (
              <div className="grid grid-cols-2 gap-4">
                <div>
                  <span className="text-sm text-gray-500">تكلفة الوحدة</span>
                  <p>{formatCurrency(selectedMovement.unit_cost)}</p>
                </div>
                <div>
                  <span className="text-sm text-gray-500">الإجمالي</span>
                  <p className="font-medium">
                    {formatCurrency(selectedMovement.unit_cost * selectedMovement.quantity)}
                  </p>
                </div>
              </div>
            )}

            {selectedMovement.batch_number && (
              <div>
                <span className="text-sm text-gray-500">رقم الدفعة</span>
                <p className="font-mono">{selectedMovement.batch_number}</p>
              </div>
            )}

            {selectedMovement.expiry_date && (
              <div>
                <span className="text-sm text-gray-500">تاريخ انتهاء الصلاحية</span>
                <p>{new Date(selectedMovement.expiry_date).toLocaleDateString('ar-SA')}</p>
              </div>
            )}

            {selectedMovement.reason && (
              <div>
                <span className="text-sm text-gray-500">السبب</span>
                <p>{selectedMovement.reason}</p>
              </div>
            )}

            {selectedMovement.notes && (
              <div>
                <span className="text-sm text-gray-500">ملاحظات</span>
                <p className="text-gray-700">{selectedMovement.notes}</p>
              </div>
            )}

            <div className="pt-4 border-t">
              <div className="grid grid-cols-2 gap-4 text-sm">
                <div>
                  <span className="text-gray-500">تاريخ الإنشاء</span>
                  <p>{formatDate(selectedMovement.created_at)}</p>
                </div>
                <div>
                  <span className="text-gray-500">بواسطة</span>
                  <p>{selectedMovement.created_by?.name || '-'}</p>
                </div>
              </div>
            </div>

            <div className="flex justify-end pt-4 border-t">
              <Button variant="secondary" onClick={() => setShowViewModal(false)}>
                إغلاق
              </Button>
            </div>
          </div>
        )}
      </Modal>
    </div>
  );
}
