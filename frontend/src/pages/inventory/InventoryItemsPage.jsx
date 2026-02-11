import React, { useState, useEffect, useCallback } from 'react';
import toast from 'react-hot-toast';
import { inventoryItemsApi, itemCategoriesApi, warehousesApi } from '../../services/inventoryApi';
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
  HiCube,
  HiSearch,
  HiRefresh,
  HiEye,
  HiExclamation,
  HiDownload,
  HiQrcode,
} from 'react-icons/hi';

/**
 * صفحة إدارة الأصناف
 * Inventory Items Management Page
 */
export default function InventoryItemsPage() {
  const [items, setItems] = useState([]);
  const [categories, setCategories] = useState([]);
  const [warehouses, setWarehouses] = useState([]);
  const [loading, setLoading] = useState(true);
  const [searchTerm, setSearchTerm] = useState('');
  const [categoryFilter, setCategoryFilter] = useState('');
  const [stockFilter, setStockFilter] = useState('');
  const [showModal, setShowModal] = useState(false);
  const [showDeleteModal, setShowDeleteModal] = useState(false);
  const [showViewModal, setShowViewModal] = useState(false);
  const [selectedItem, setSelectedItem] = useState(null);
  const [stockLevels, setStockLevels] = useState([]);
  const [formData, setFormData] = useState({
    name_ar: '',
    name_en: '',
    code: '',
    barcode: '',
    category_id: '',
    unit: '',
    description: '',
    min_stock_level: '',
    max_stock_level: '',
    reorder_point: '',
    unit_cost: '',
    unit_price: '',
    is_controlled: false,
    requires_prescription: false,
    is_active: true,
  });
  const [formErrors, setFormErrors] = useState({});
  const [submitting, setSubmitting] = useState(false);

  // Units options
  const unitOptions = [
    { value: 'piece', label: 'قطعة' },
    { value: 'box', label: 'علبة' },
    { value: 'pack', label: 'عبوة' },
    { value: 'bottle', label: 'زجاجة' },
    { value: 'vial', label: 'قارورة' },
    { value: 'ampule', label: 'أمبولة' },
    { value: 'tube', label: 'أنبوب' },
    { value: 'roll', label: 'لفة' },
    { value: 'kg', label: 'كيلوجرام' },
    { value: 'liter', label: 'لتر' },
    { value: 'ml', label: 'ملليلتر' },
    { value: 'mg', label: 'ملليجرام' },
  ];

  // Stock filter options
  const stockFilterOptions = [
    { value: '', label: 'جميع المستويات' },
    { value: 'low', label: 'مخزون منخفض' },
    { value: 'out', label: 'نفذ المخزون' },
    { value: 'expiring', label: 'قارب على الانتهاء' },
  ];

  // Load items
  const loadItems = useCallback(async () => {
    try {
      setLoading(true);
      let response;

      if (stockFilter === 'low') {
        response = await inventoryItemsApi.getLowStock();
      } else if (stockFilter === 'expiring') {
        response = await inventoryItemsApi.getExpiring(30);
      } else {
        response = await inventoryItemsApi.getAll({
          search: searchTerm,
          category_id: categoryFilter,
        });
      }

      setItems(response.data?.data || response.data || []);
    } catch (error) {
      toast.error('فشل في تحميل الأصناف');
      console.error('Error loading items:', error);
    } finally {
      setLoading(false);
    }
  }, [searchTerm, categoryFilter, stockFilter]);

  // Load categories
  const loadCategories = useCallback(async () => {
    try {
      const response = await itemCategoriesApi.getActive();
      setCategories(response.data?.data || response.data || []);
    } catch (error) {
      console.error('Error loading categories:', error);
    }
  }, []);

  // Load warehouses
  const loadWarehouses = useCallback(async () => {
    try {
      const response = await warehousesApi.getActive();
      setWarehouses(response.data?.data || response.data || []);
    } catch (error) {
      console.error('Error loading warehouses:', error);
    }
  }, []);

  useEffect(() => {
    loadCategories();
    loadWarehouses();
  }, [loadCategories, loadWarehouses]);

  useEffect(() => {
    loadItems();
  }, [loadItems]);

  // Reset form
  const resetForm = () => {
    setFormData({
      name_ar: '',
      name_en: '',
      code: '',
      barcode: '',
      category_id: '',
      unit: '',
      description: '',
      min_stock_level: '',
      max_stock_level: '',
      reorder_point: '',
      unit_cost: '',
      unit_price: '',
      is_controlled: false,
      requires_prescription: false,
      is_active: true,
    });
    setFormErrors({});
    setSelectedItem(null);
  };

  // Open create modal
  const handleCreate = () => {
    resetForm();
    setShowModal(true);
  };

  // Open edit modal
  const handleEdit = (item) => {
    setSelectedItem(item);
    setFormData({
      name_ar: item.name_ar || '',
      name_en: item.name_en || '',
      code: item.code || '',
      barcode: item.barcode || '',
      category_id: item.category_id || '',
      unit: item.unit || '',
      description: item.description || '',
      min_stock_level: item.min_stock_level || '',
      max_stock_level: item.max_stock_level || '',
      reorder_point: item.reorder_point || '',
      unit_cost: item.unit_cost || '',
      unit_price: item.unit_price || '',
      is_controlled: item.is_controlled ?? false,
      requires_prescription: item.requires_prescription ?? false,
      is_active: item.is_active ?? true,
    });
    setFormErrors({});
    setShowModal(true);
  };

  // Open view modal
  const handleView = async (item) => {
    setSelectedItem(item);
    try {
      const response = await inventoryItemsApi.getStockLevels(item.id);
      setStockLevels(response.data?.data || response.data || []);
    } catch (error) {
      console.error('Error loading stock levels:', error);
      setStockLevels([]);
    }
    setShowViewModal(true);
  };

  // Open delete confirmation
  const handleDeleteClick = (item) => {
    setSelectedItem(item);
    setShowDeleteModal(true);
  };

  // Validate form
  const validateForm = () => {
    const errors = {};
    if (!formData.name_ar?.trim()) {
      errors.name_ar = 'الاسم بالعربية مطلوب';
    }
    if (!formData.name_en?.trim()) {
      errors.name_en = 'الاسم بالإنجليزية مطلوب';
    }
    if (!formData.code?.trim()) {
      errors.code = 'رمز الصنف مطلوب';
    }
    if (!formData.category_id) {
      errors.category_id = 'التصنيف مطلوب';
    }
    if (!formData.unit) {
      errors.unit = 'الوحدة مطلوبة';
    }
    if (formData.min_stock_level && formData.max_stock_level) {
      if (Number(formData.min_stock_level) > Number(formData.max_stock_level)) {
        errors.max_stock_level = 'الحد الأقصى يجب أن يكون أكبر من الحد الأدنى';
      }
    }
    if (formData.unit_cost && formData.unit_price) {
      if (Number(formData.unit_cost) > Number(formData.unit_price)) {
        errors.unit_price = 'سعر البيع يجب أن يكون أكبر من التكلفة';
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
        min_stock_level: formData.min_stock_level ? Number(formData.min_stock_level) : null,
        max_stock_level: formData.max_stock_level ? Number(formData.max_stock_level) : null,
        reorder_point: formData.reorder_point ? Number(formData.reorder_point) : null,
        unit_cost: formData.unit_cost ? Number(formData.unit_cost) : null,
        unit_price: formData.unit_price ? Number(formData.unit_price) : null,
      };

      if (selectedItem) {
        await inventoryItemsApi.update(selectedItem.id, data);
        toast.success('تم تحديث الصنف بنجاح');
      } else {
        await inventoryItemsApi.create(data);
        toast.success('تم إنشاء الصنف بنجاح');
      }
      setShowModal(false);
      resetForm();
      loadItems();
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

  // Delete item
  const handleDelete = async () => {
    if (!selectedItem) return;

    try {
      setSubmitting(true);
      await inventoryItemsApi.delete(selectedItem.id);
      toast.success('تم حذف الصنف بنجاح');
      setShowDeleteModal(false);
      setSelectedItem(null);
      loadItems();
    } catch (error) {
      const message = error.response?.data?.message || 'فشل في حذف الصنف';
      toast.error(message);
    } finally {
      setSubmitting(false);
    }
  };

  // Handle export
  const handleExport = async () => {
    try {
      const response = await inventoryItemsApi.exportList({
        search: searchTerm,
        category_id: categoryFilter,
      });
      const url = window.URL.createObjectURL(new Blob([response.data]));
      const link = document.createElement('a');
      link.href = url;
      link.setAttribute('download', 'inventory-items.xlsx');
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
    const { name, value, type, checked } = e.target;
    setFormData((prev) => ({
      ...prev,
      [name]: type === 'checkbox' ? checked : value,
    }));
    if (formErrors[name]) {
      setFormErrors((prev) => ({ ...prev, [name]: null }));
    }
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

  // Get stock status
  const getStockStatus = (item) => {
    const totalStock = item.total_stock || 0;
    const minLevel = item.min_stock_level || 0;
    const reorderPoint = item.reorder_point || minLevel;

    if (totalStock === 0) {
      return { variant: 'danger', label: 'نفذ' };
    } else if (totalStock <= minLevel) {
      return { variant: 'danger', label: 'منخفض جداً' };
    } else if (totalStock <= reorderPoint) {
      return { variant: 'warning', label: 'منخفض' };
    }
    return { variant: 'success', label: 'متوفر' };
  };

  // Get unit label
  const getUnitLabel = (unit) => {
    const found = unitOptions.find((u) => u.value === unit);
    return found ? found.label : unit;
  };

  return (
    <div className="space-y-6" dir="rtl">
      {/* Header */}
      <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">إدارة الأصناف</h1>
          <p className="text-gray-600 mt-1">إدارة أصناف المخزون والمستلزمات الطبية</p>
        </div>
        <div className="flex gap-2">
          <Button variant="secondary" icon={HiDownload} onClick={handleExport}>
            تصدير
          </Button>
          <Button icon={HiPlus} onClick={handleCreate}>
            إضافة صنف
          </Button>
        </div>
      </div>

      {/* Search & Filters */}
      <Card>
        <div className="p-4">
          <div className="flex flex-col sm:flex-row gap-4">
            <div className="flex-1">
              <Input
                placeholder="بحث بالاسم أو الرمز أو الباركود..."
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
                icon={HiSearch}
              />
            </div>
            <div className="w-full sm:w-48">
              <Select
                value={categoryFilter}
                onChange={(e) => setCategoryFilter(e.target.value)}
                options={[
                  { value: '', label: 'جميع التصنيفات' },
                  ...categories.map((c) => ({ value: c.id, label: c.name_ar })),
                ]}
              />
            </div>
            <div className="w-full sm:w-48">
              <Select
                value={stockFilter}
                onChange={(e) => setStockFilter(e.target.value)}
                options={stockFilterOptions}
              />
            </div>
            <Button variant="secondary" icon={HiRefresh} onClick={loadItems}>
              تحديث
            </Button>
          </div>
        </div>
      </Card>

      {/* Items Table */}
      <Card>
        <CardHeader
          title="قائمة الأصناف"
          subtitle={`${items.length} صنف`}
          icon={HiCube}
        />
        <Table>
          <TableHead>
            <TableRow>
              <TableHeader>الرمز</TableHeader>
              <TableHeader>الاسم</TableHeader>
              <TableHeader>التصنيف</TableHeader>
              <TableHeader>الوحدة</TableHeader>
              <TableHeader>المخزون</TableHeader>
              <TableHeader>التكلفة</TableHeader>
              <TableHeader>الحالة</TableHeader>
              <TableHeader>الإجراءات</TableHeader>
            </TableRow>
          </TableHead>
          <TableBody>
            {loading ? (
              <TableLoading colSpan={8} />
            ) : items.length === 0 ? (
              <TableEmpty
                colSpan={8}
                icon={HiCube}
                message="لا توجد أصناف"
                actionLabel="إضافة صنف جديد"
                onAction={handleCreate}
              />
            ) : (
              items.map((item) => {
                const stockStatus = getStockStatus(item);
                return (
                  <TableRow key={item.id}>
                    <TableCell className="font-mono text-sm">
                      {item.code}
                      {item.barcode && (
                        <span className="block text-xs text-gray-400">
                          <HiQrcode className="inline w-3 h-3 ml-1" />
                          {item.barcode}
                        </span>
                      )}
                    </TableCell>
                    <TableCell>
                      <div>
                        <span className="font-medium">{item.name_ar}</span>
                        <span className="block text-sm text-gray-500" dir="ltr">
                          {item.name_en}
                        </span>
                      </div>
                      {item.is_controlled && (
                        <Badge variant="danger\" size="sm" className="mt-1">
                          مادة خاضعة للرقابة
                        </Badge>
                      )}
                    </TableCell>
                    <TableCell>{item.category?.name_ar || '-'}</TableCell>
                    <TableCell>{getUnitLabel(item.unit)}</TableCell>
                    <TableCell>
                      <div className="flex items-center gap-2">
                        <span className="font-medium">{item.total_stock || 0}</span>
                        {(item.total_stock || 0) <= (item.min_stock_level || 0) && (
                          <HiExclamation className="w-4 h-4 text-red-500" />
                        )}
                      </div>
                      <span className="text-xs text-gray-500">
                        الحد الأدنى: {item.min_stock_level || '-'}
                      </span>
                    </TableCell>
                    <TableCell>
                      <div className="text-sm">
                        <div>التكلفة: {formatCurrency(item.unit_cost)}</div>
                        <div className="text-gray-500">السعر: {formatCurrency(item.unit_price)}</div>
                      </div>
                    </TableCell>
                    <TableCell>
                      <div className="space-y-1">
                        <Badge variant={stockStatus.variant}>{stockStatus.label}</Badge>
                        {!item.is_active && (
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
                          onClick={() => handleView(item)}
                          title="عرض التفاصيل"
                        />
                        <Button
                          variant="ghost"
                          size="sm"
                          icon={HiPencil}
                          onClick={() => handleEdit(item)}
                          title="تعديل"
                        />
                        <Button
                          variant="ghost"
                          size="sm"
                          icon={HiTrash}
                          onClick={() => handleDeleteClick(item)}
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
        title={selectedItem ? 'تعديل الصنف' : 'إضافة صنف جديد'}
        size="lg"
      >
        <form onSubmit={handleSubmit} className="space-y-4">
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <Input
              label="الاسم بالعربية"
              name="name_ar"
              value={formData.name_ar}
              onChange={handleInputChange}
              error={formErrors.name_ar}
              required
              placeholder="مثال: قفازات طبية"
            />
            <Input
              label="الاسم بالإنجليزية"
              name="name_en"
              value={formData.name_en}
              onChange={handleInputChange}
              error={formErrors.name_en}
              required
              placeholder="e.g. Medical Gloves"
              dir="ltr"
            />
          </div>

          <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
            <Input
              label="رمز الصنف"
              name="code"
              value={formData.code}
              onChange={handleInputChange}
              error={formErrors.code}
              required
              placeholder="MED-001"
              dir="ltr"
            />
            <Input
              label="الباركود"
              name="barcode"
              value={formData.barcode}
              onChange={handleInputChange}
              placeholder="1234567890123"
              dir="ltr"
            />
            <Select
              label="التصنيف"
              name="category_id"
              value={formData.category_id}
              onChange={handleInputChange}
              error={formErrors.category_id}
              required
              placeholder="اختر التصنيف"
              options={categories.map((c) => ({ value: c.id, label: c.name_ar }))}
            />
          </div>

          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <Select
              label="الوحدة"
              name="unit"
              value={formData.unit}
              onChange={handleInputChange}
              error={formErrors.unit}
              required
              placeholder="اختر الوحدة"
              options={unitOptions}
            />
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">
                الوصف
              </label>
              <textarea
                name="description"
                value={formData.description}
                onChange={handleInputChange}
                rows={2}
                className="w-full rounded-lg border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                placeholder="وصف مختصر للصنف..."
              />
            </div>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
            <Input
              label="الحد الأدنى للمخزون"
              name="min_stock_level"
              type="number"
              value={formData.min_stock_level}
              onChange={handleInputChange}
              placeholder="0"
              dir="ltr"
            />
            <Input
              label="الحد الأقصى للمخزون"
              name="max_stock_level"
              type="number"
              value={formData.max_stock_level}
              onChange={handleInputChange}
              error={formErrors.max_stock_level}
              placeholder="0"
              dir="ltr"
            />
            <Input
              label="نقطة إعادة الطلب"
              name="reorder_point"
              type="number"
              value={formData.reorder_point}
              onChange={handleInputChange}
              placeholder="0"
              dir="ltr"
            />
          </div>

          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <Input
              label="تكلفة الوحدة"
              name="unit_cost"
              type="number"
              step="0.01"
              value={formData.unit_cost}
              onChange={handleInputChange}
              placeholder="0.00"
              dir="ltr"
            />
            <Input
              label="سعر البيع"
              name="unit_price"
              type="number"
              step="0.01"
              value={formData.unit_price}
              onChange={handleInputChange}
              error={formErrors.unit_price}
              placeholder="0.00"
              dir="ltr"
            />
          </div>

          <div className="flex flex-wrap gap-6">
            <div className="flex items-center gap-2">
              <input
                type="checkbox"
                id="is_controlled"
                name="is_controlled"
                checked={formData.is_controlled}
                onChange={handleInputChange}
                className="w-4 h-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500"
              />
              <label htmlFor="is_controlled" className="text-sm text-gray-700">
                مادة خاضعة للرقابة
              </label>
            </div>
            <div className="flex items-center gap-2">
              <input
                type="checkbox"
                id="requires_prescription"
                name="requires_prescription"
                checked={formData.requires_prescription}
                onChange={handleInputChange}
                className="w-4 h-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500"
              />
              <label htmlFor="requires_prescription" className="text-sm text-gray-700">
                يتطلب وصفة طبية
              </label>
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
                الصنف نشط
              </label>
            </div>
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
              {selectedItem ? 'تحديث' : 'إضافة'}
            </Button>
          </div>
        </form>
      </Modal>

      {/* View Details Modal */}
      <Modal
        isOpen={showViewModal}
        onClose={() => setShowViewModal(false)}
        title="تفاصيل الصنف"
        size="lg"
      >
        {selectedItem && (
          <div className="space-y-6">
            {/* Item Info */}
            <div className="grid grid-cols-2 gap-4">
              <div>
                <span className="text-sm text-gray-500">الرمز</span>
                <p className="font-mono">{selectedItem.code}</p>
              </div>
              <div>
                <span className="text-sm text-gray-500">الباركود</span>
                <p className="font-mono">{selectedItem.barcode || '-'}</p>
              </div>
              <div>
                <span className="text-sm text-gray-500">الاسم بالعربية</span>
                <p className="font-medium">{selectedItem.name_ar}</p>
              </div>
              <div>
                <span className="text-sm text-gray-500">الاسم بالإنجليزية</span>
                <p dir="ltr">{selectedItem.name_en}</p>
              </div>
              <div>
                <span className="text-sm text-gray-500">التصنيف</span>
                <p>{selectedItem.category?.name_ar || '-'}</p>
              </div>
              <div>
                <span className="text-sm text-gray-500">الوحدة</span>
                <p>{getUnitLabel(selectedItem.unit)}</p>
              </div>
            </div>

            {/* Stock Levels by Warehouse */}
            <div>
              <h3 className="text-lg font-medium mb-3">مستويات المخزون</h3>
              {stockLevels.length > 0 ? (
                <div className="border rounded-lg divide-y">
                  {stockLevels.map((stock) => (
                    <div key={stock.warehouse_id} className="p-3 flex justify-between items-center">
                      <div>
                        <span className="font-medium">{stock.warehouse?.name_ar}</span>
                        <span className="text-sm text-gray-500 mr-2">
                          ({stock.warehouse?.type})
                        </span>
                      </div>
                      <div className="text-left">
                        <span className="font-bold text-lg">{stock.quantity}</span>
                        <span className="text-sm text-gray-500 mr-1">{getUnitLabel(selectedItem.unit)}</span>
                      </div>
                    </div>
                  ))}
                  <div className="p-3 bg-gray-50 flex justify-between items-center">
                    <span className="font-medium">الإجمالي</span>
                    <span className="font-bold text-lg text-primary-600">
                      {stockLevels.reduce((sum, s) => sum + (s.quantity || 0), 0)}
                    </span>
                  </div>
                </div>
              ) : (
                <p className="text-gray-500 text-center py-4">لا يوجد مخزون في أي مستودع</p>
              )}
            </div>

            {/* Price Info */}
            <div className="grid grid-cols-2 gap-4 p-4 bg-gray-50 rounded-lg">
              <div>
                <span className="text-sm text-gray-500">تكلفة الوحدة</span>
                <p className="font-medium text-lg">{formatCurrency(selectedItem.unit_cost)}</p>
              </div>
              <div>
                <span className="text-sm text-gray-500">سعر البيع</span>
                <p className="font-medium text-lg">{formatCurrency(selectedItem.unit_price)}</p>
              </div>
            </div>

            {/* Flags */}
            <div className="flex gap-2 flex-wrap">
              {selectedItem.is_controlled && (
                <Badge variant="danger">مادة خاضعة للرقابة</Badge>
              )}
              {selectedItem.requires_prescription && (
                <Badge variant="warning">يتطلب وصفة طبية</Badge>
              )}
              <Badge variant={selectedItem.is_active ? 'success' : 'gray'}>
                {selectedItem.is_active ? 'نشط' : 'غير نشط'}
              </Badge>
            </div>

            <div className="flex justify-end pt-4 border-t">
              <Button variant="secondary" onClick={() => setShowViewModal(false)}>
                إغلاق
              </Button>
            </div>
          </div>
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
            هل أنت متأكد من حذف الصنف{' '}
            <span className="font-bold text-gray-900">
              {selectedItem?.name_ar}
            </span>
            ؟
          </p>
          {(selectedItem?.total_stock || 0) > 0 && (
            <div className="p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
              <p className="text-sm text-yellow-800">
                <HiExclamation className="inline w-4 h-4 ml-1" />
                تحذير: يوجد مخزون متبقي ({selectedItem?.total_stock}) من هذا الصنف
              </p>
            </div>
          )}
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
