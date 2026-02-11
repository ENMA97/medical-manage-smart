import React, { useState, useEffect, useCallback } from 'react';
import toast from 'react-hot-toast';
import { warehousesApi } from '../../services/inventoryApi';
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
  HiOfficeBuilding,
  HiSearch,
  HiRefresh,
  HiEye,
  HiLocationMarker,
  HiCube,
  HiExclamation,
} from 'react-icons/hi';

// Warehouse types
const WAREHOUSE_TYPES = {
  main: { label: 'رئيسي', variant: 'primary' },
  sub: { label: 'فرعي', variant: 'info' },
  pharmacy: { label: 'صيدلية', variant: 'success' },
  lab: { label: 'مختبر', variant: 'purple' },
  emergency: { label: 'طوارئ', variant: 'danger' },
  crash_cart: { label: 'عربة إسعاف', variant: 'warning' },
  cold_storage: { label: 'تخزين بارد', variant: 'info' },
};

/**
 * صفحة إدارة المستودعات
 * Warehouses Management Page
 */
export default function WarehousesPage() {
  const [warehouses, setWarehouses] = useState([]);
  const [loading, setLoading] = useState(true);
  const [showModal, setShowModal] = useState(false);
  const [showViewModal, setShowViewModal] = useState(false);
  const [showDeleteModal, setShowDeleteModal] = useState(false);
  const [selectedWarehouse, setSelectedWarehouse] = useState(null);
  const [submitting, setSubmitting] = useState(false);

  // Filters
  const [filters, setFilters] = useState({
    search: '',
    type: '',
  });

  // Form data
  const [formData, setFormData] = useState({
    name_ar: '',
    name_en: '',
    code: '',
    type: 'main',
    location: '',
    manager_name: '',
    phone: '',
    is_active: true,
  });
  const [formErrors, setFormErrors] = useState({});

  // Load warehouses
  const loadWarehouses = useCallback(async () => {
    try {
      setLoading(true);
      const params = {};
      if (filters.search) params.search = filters.search;
      if (filters.type) params.type = filters.type;

      const response = await warehousesApi.getAll(params);
      setWarehouses(response.data?.data || response.data || []);
    } catch (error) {
      toast.error('فشل في تحميل المستودعات');
      console.error('Error loading warehouses:', error);
    } finally {
      setLoading(false);
    }
  }, [filters]);

  useEffect(() => {
    loadWarehouses();
  }, [loadWarehouses]);

  // Reset form
  const resetForm = () => {
    setFormData({
      name_ar: '',
      name_en: '',
      code: '',
      type: 'main',
      location: '',
      manager_name: '',
      phone: '',
      is_active: true,
    });
    setFormErrors({});
    setSelectedWarehouse(null);
  };

  // Open create modal
  const handleCreate = () => {
    resetForm();
    setShowModal(true);
  };

  // Open edit modal
  const handleEdit = (warehouse) => {
    setSelectedWarehouse(warehouse);
    setFormData({
      name_ar: warehouse.name_ar || '',
      name_en: warehouse.name_en || '',
      code: warehouse.code || '',
      type: warehouse.type || 'main',
      location: warehouse.location || '',
      manager_name: warehouse.manager_name || '',
      phone: warehouse.phone || '',
      is_active: warehouse.is_active ?? true,
    });
    setFormErrors({});
    setShowModal(true);
  };

  // View warehouse details
  const handleView = (warehouse) => {
    setSelectedWarehouse(warehouse);
    setShowViewModal(true);
  };

  // Open delete confirmation
  const handleDeleteClick = (warehouse) => {
    setSelectedWarehouse(warehouse);
    setShowDeleteModal(true);
  };

  // Validate form
  const validateForm = () => {
    const errors = {};
    if (!formData.name_ar?.trim()) errors.name_ar = 'الاسم بالعربية مطلوب';
    if (!formData.name_en?.trim()) errors.name_en = 'الاسم بالإنجليزية مطلوب';
    if (!formData.code?.trim()) errors.code = 'رمز المستودع مطلوب';
    if (!formData.type) errors.type = 'نوع المستودع مطلوب';

    setFormErrors(errors);
    return Object.keys(errors).length === 0;
  };

  // Submit form
  const handleSubmit = async (e) => {
    e.preventDefault();
    if (!validateForm()) return;

    try {
      setSubmitting(true);
      if (selectedWarehouse) {
        await warehousesApi.update(selectedWarehouse.id, formData);
        toast.success('تم تحديث المستودع بنجاح');
      } else {
        await warehousesApi.create(formData);
        toast.success('تم إضافة المستودع بنجاح');
      }
      setShowModal(false);
      resetForm();
      loadWarehouses();
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

  // Delete warehouse
  const handleDelete = async () => {
    if (!selectedWarehouse) return;

    try {
      setSubmitting(true);
      await warehousesApi.delete(selectedWarehouse.id);
      toast.success('تم حذف المستودع بنجاح');
      setShowDeleteModal(false);
      setSelectedWarehouse(null);
      loadWarehouses();
    } catch (error) {
      const message = error.response?.data?.message || 'فشل في حذف المستودع';
      toast.error(message);
    } finally {
      setSubmitting(false);
    }
  };

  // Calculate statistics
  const stats = {
    total: warehouses.length,
    active: warehouses.filter((w) => w.is_active).length,
    lowStock: warehouses.filter((w) => w.low_stock_count > 0).length,
    totalItems: warehouses.reduce((sum, w) => sum + (w.items_count || 0), 0),
  };

  return (
    <div className="space-y-6" dir="rtl">
      {/* Header */}
      <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">إدارة المستودعات</h1>
          <p className="text-gray-600 mt-1">إدارة المستودعات ومواقع التخزين</p>
        </div>
        <Button icon={HiPlus} onClick={handleCreate}>
          إضافة مستودع
        </Button>
      </div>

      {/* Statistics */}
      <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
        <Card className="p-4">
          <div className="text-sm text-gray-500">إجمالي المستودعات</div>
          <div className="text-2xl font-bold text-gray-900">{stats.total}</div>
        </Card>
        <Card className="p-4">
          <div className="text-sm text-gray-500">مستودعات نشطة</div>
          <div className="text-2xl font-bold text-green-600">{stats.active}</div>
        </Card>
        <Card className="p-4">
          <div className="text-sm text-gray-500">نقص مخزون</div>
          <div className="text-2xl font-bold text-red-600">{stats.lowStock}</div>
        </Card>
        <Card className="p-4">
          <div className="text-sm text-gray-500">إجمالي الأصناف</div>
          <div className="text-2xl font-bold text-primary-600">{stats.totalItems}</div>
        </Card>
      </div>

      {/* Filters */}
      <Card>
        <div className="p-4">
          <div className="flex flex-col sm:flex-row gap-4">
            <div className="flex-1">
              <Input
                placeholder="بحث بالاسم أو الرمز..."
                value={filters.search}
                onChange={(e) => setFilters({ ...filters, search: e.target.value })}
                icon={HiSearch}
              />
            </div>
            <Select
              value={filters.type}
              onChange={(e) => setFilters({ ...filters, type: e.target.value })}
              options={[
                { value: '', label: 'جميع الأنواع' },
                ...Object.entries(WAREHOUSE_TYPES).map(([value, { label }]) => ({
                  value,
                  label,
                })),
              ]}
              className="w-full sm:w-48"
            />
            <Button variant="secondary" icon={HiRefresh} onClick={loadWarehouses}>
              تحديث
            </Button>
          </div>
        </div>
      </Card>

      {/* Warehouses Table */}
      <Card>
        <CardHeader
          title="قائمة المستودعات"
          subtitle={`${warehouses.length} مستودع`}
          icon={HiOfficeBuilding}
        />
        <Table>
          <TableHead>
            <TableRow>
              <TableHeader>الرمز</TableHeader>
              <TableHeader>الاسم</TableHeader>
              <TableHeader>النوع</TableHeader>
              <TableHeader>الموقع</TableHeader>
              <TableHeader>عدد الأصناف</TableHeader>
              <TableHeader>الحالة</TableHeader>
              <TableHeader>الإجراءات</TableHeader>
            </TableRow>
          </TableHead>
          <TableBody>
            {loading ? (
              <TableLoading colSpan={7} />
            ) : warehouses.length === 0 ? (
              <TableEmpty
                colSpan={7}
                icon={HiOfficeBuilding}
                message="لا توجد مستودعات"
                actionLabel="إضافة مستودع جديد"
                onAction={handleCreate}
              />
            ) : (
              warehouses.map((warehouse) => (
                <TableRow key={warehouse.id}>
                  <TableCell className="font-mono text-sm">{warehouse.code}</TableCell>
                  <TableCell>
                    <div>
                      <div className="font-medium">{warehouse.name_ar}</div>
                      <div className="text-sm text-gray-500">{warehouse.name_en}</div>
                    </div>
                  </TableCell>
                  <TableCell>
                    <Badge variant={WAREHOUSE_TYPES[warehouse.type]?.variant || 'gray'}>
                      {WAREHOUSE_TYPES[warehouse.type]?.label || warehouse.type}
                    </Badge>
                  </TableCell>
                  <TableCell>
                    {warehouse.location ? (
                      <span className="flex items-center gap-1 text-gray-600">
                        <HiLocationMarker className="w-4 h-4" />
                        {warehouse.location}
                      </span>
                    ) : (
                      '-'
                    )}
                  </TableCell>
                  <TableCell>
                    <span className="flex items-center gap-2">
                      <HiCube className="w-4 h-4 text-gray-400" />
                      {warehouse.items_count || 0}
                      {warehouse.low_stock_count > 0 && (
                        <Badge variant="danger" className="text-xs">
                          <HiExclamation className="w-3 h-3" />
                          {warehouse.low_stock_count}
                        </Badge>
                      )}
                    </span>
                  </TableCell>
                  <TableCell>
                    <Badge variant={warehouse.is_active ? 'success' : 'gray'}>
                      {warehouse.is_active ? 'نشط' : 'غير نشط'}
                    </Badge>
                  </TableCell>
                  <TableCell>
                    <div className="flex items-center gap-1">
                      <Button
                        variant="ghost"
                        size="sm"
                        icon={HiEye}
                        onClick={() => handleView(warehouse)}
                        title="عرض"
                      />
                      <Button
                        variant="ghost"
                        size="sm"
                        icon={HiPencil}
                        onClick={() => handleEdit(warehouse)}
                        title="تعديل"
                      />
                      <Button
                        variant="ghost"
                        size="sm"
                        icon={HiTrash}
                        onClick={() => handleDeleteClick(warehouse)}
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
        title={selectedWarehouse ? 'تعديل المستودع' : 'إضافة مستودع جديد'}
        size="md"
      >
        <form onSubmit={handleSubmit} className="space-y-4">
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <Input
              label="الاسم بالعربية"
              name="name_ar"
              value={formData.name_ar}
              onChange={(e) => setFormData({ ...formData, name_ar: e.target.value })}
              error={formErrors.name_ar}
              required
              placeholder="مثال: المستودع الرئيسي"
            />
            <Input
              label="الاسم بالإنجليزية"
              name="name_en"
              value={formData.name_en}
              onChange={(e) => setFormData({ ...formData, name_en: e.target.value })}
              error={formErrors.name_en}
              required
              placeholder="e.g. Main Warehouse"
              dir="ltr"
            />
          </div>

          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <Input
              label="رمز المستودع"
              name="code"
              value={formData.code}
              onChange={(e) => setFormData({ ...formData, code: e.target.value })}
              error={formErrors.code}
              required
              placeholder="WH-001"
              dir="ltr"
            />
            <Select
              label="نوع المستودع"
              name="type"
              value={formData.type}
              onChange={(e) => setFormData({ ...formData, type: e.target.value })}
              error={formErrors.type}
              required
              options={Object.entries(WAREHOUSE_TYPES).map(([value, { label }]) => ({
                value,
                label,
              }))}
            />
          </div>

          <Input
            label="الموقع"
            name="location"
            value={formData.location}
            onChange={(e) => setFormData({ ...formData, location: e.target.value })}
            placeholder="الطابق الأرضي - المبنى الرئيسي"
            icon={HiLocationMarker}
          />

          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <Input
              label="اسم المسؤول"
              name="manager_name"
              value={formData.manager_name}
              onChange={(e) => setFormData({ ...formData, manager_name: e.target.value })}
              placeholder="محمد أحمد"
            />
            <Input
              label="رقم التواصل"
              name="phone"
              value={formData.phone}
              onChange={(e) => setFormData({ ...formData, phone: e.target.value })}
              placeholder="05xxxxxxxx"
              dir="ltr"
            />
          </div>

          <div className="flex items-center gap-2">
            <input
              type="checkbox"
              id="is_active"
              checked={formData.is_active}
              onChange={(e) => setFormData({ ...formData, is_active: e.target.checked })}
              className="w-4 h-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500"
            />
            <label htmlFor="is_active" className="text-sm text-gray-700">
              المستودع نشط
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
              {selectedWarehouse ? 'تحديث' : 'إضافة'}
            </Button>
          </div>
        </form>
      </Modal>

      {/* View Modal */}
      <Modal
        isOpen={showViewModal}
        onClose={() => setShowViewModal(false)}
        title="تفاصيل المستودع"
        size="md"
      >
        {selectedWarehouse && (
          <div className="space-y-4">
            <div className="flex items-center justify-between pb-4 border-b">
              <div>
                <h3 className="text-lg font-bold text-gray-900">{selectedWarehouse.name_ar}</h3>
                <p className="text-gray-500">{selectedWarehouse.name_en}</p>
              </div>
              <Badge
                variant={WAREHOUSE_TYPES[selectedWarehouse.type]?.variant || 'gray'}
                size="lg"
              >
                {WAREHOUSE_TYPES[selectedWarehouse.type]?.label}
              </Badge>
            </div>

            <div className="bg-gray-50 rounded-lg p-4 space-y-3">
              <div className="flex justify-between">
                <span className="text-gray-500">الرمز</span>
                <span className="font-mono">{selectedWarehouse.code}</span>
              </div>
              <div className="flex justify-between">
                <span className="text-gray-500">الموقع</span>
                <span>{selectedWarehouse.location || '-'}</span>
              </div>
              <div className="flex justify-between">
                <span className="text-gray-500">المسؤول</span>
                <span>{selectedWarehouse.manager_name || '-'}</span>
              </div>
              <div className="flex justify-between">
                <span className="text-gray-500">رقم التواصل</span>
                <span dir="ltr">{selectedWarehouse.phone || '-'}</span>
              </div>
              <div className="flex justify-between">
                <span className="text-gray-500">عدد الأصناف</span>
                <span className="font-bold">{selectedWarehouse.items_count || 0}</span>
              </div>
              <div className="flex justify-between">
                <span className="text-gray-500">الحالة</span>
                <Badge variant={selectedWarehouse.is_active ? 'success' : 'gray'}>
                  {selectedWarehouse.is_active ? 'نشط' : 'غير نشط'}
                </Badge>
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

      {/* Delete Modal */}
      <Modal
        isOpen={showDeleteModal}
        onClose={() => setShowDeleteModal(false)}
        title="تأكيد الحذف"
        size="sm"
      >
        <div className="space-y-4">
          <p className="text-gray-600">
            هل أنت متأكد من حذف المستودع{' '}
            <span className="font-bold text-gray-900">{selectedWarehouse?.name_ar}</span>؟
          </p>
          {selectedWarehouse?.items_count > 0 && (
            <p className="text-sm text-red-600">
              تحذير: يحتوي هذا المستودع على {selectedWarehouse.items_count} صنف.
            </p>
          )}
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
