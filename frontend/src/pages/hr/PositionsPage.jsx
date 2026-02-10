import React, { useState, useEffect, useCallback } from 'react';
import toast from 'react-hot-toast';
import { positionsApi, departmentsApi } from '../../services/hrApi';
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
  HiBriefcase,
  HiSearch,
  HiRefresh,
  HiFilter,
} from 'react-icons/hi';

/**
 * صفحة إدارة المناصب
 * Positions Management Page
 */
export default function PositionsPage() {
  const [positions, setPositions] = useState([]);
  const [departments, setDepartments] = useState([]);
  const [loading, setLoading] = useState(true);
  const [searchTerm, setSearchTerm] = useState('');
  const [departmentFilter, setDepartmentFilter] = useState('');
  const [showModal, setShowModal] = useState(false);
  const [showDeleteModal, setShowDeleteModal] = useState(false);
  const [selectedPosition, setSelectedPosition] = useState(null);
  const [formData, setFormData] = useState({
    name_ar: '',
    name_en: '',
    code: '',
    department_id: '',
    grade: '',
    min_salary: '',
    max_salary: '',
    description: '',
    requirements: '',
    is_active: true,
  });
  const [formErrors, setFormErrors] = useState({});
  const [submitting, setSubmitting] = useState(false);

  // Load positions
  const loadPositions = useCallback(async () => {
    try {
      setLoading(true);
      const response = await positionsApi.getAll({
        search: searchTerm,
        department_id: departmentFilter,
      });
      setPositions(response.data?.data || response.data || []);
    } catch (error) {
      toast.error('فشل في تحميل المناصب');
      console.error('Error loading positions:', error);
    } finally {
      setLoading(false);
    }
  }, [searchTerm, departmentFilter]);

  // Load departments for filter and form
  const loadDepartments = useCallback(async () => {
    try {
      const response = await departmentsApi.getActive();
      setDepartments(response.data?.data || response.data || []);
    } catch (error) {
      console.error('Error loading departments:', error);
    }
  }, []);

  useEffect(() => {
    loadDepartments();
  }, [loadDepartments]);

  useEffect(() => {
    loadPositions();
  }, [loadPositions]);

  // Reset form
  const resetForm = () => {
    setFormData({
      name_ar: '',
      name_en: '',
      code: '',
      department_id: '',
      grade: '',
      min_salary: '',
      max_salary: '',
      description: '',
      requirements: '',
      is_active: true,
    });
    setFormErrors({});
    setSelectedPosition(null);
  };

  // Open create modal
  const handleCreate = () => {
    resetForm();
    setShowModal(true);
  };

  // Open edit modal
  const handleEdit = (position) => {
    setSelectedPosition(position);
    setFormData({
      name_ar: position.name_ar || '',
      name_en: position.name_en || '',
      code: position.code || '',
      department_id: position.department_id || '',
      grade: position.grade || '',
      min_salary: position.min_salary || '',
      max_salary: position.max_salary || '',
      description: position.description || '',
      requirements: position.requirements || '',
      is_active: position.is_active ?? true,
    });
    setFormErrors({});
    setShowModal(true);
  };

  // Open delete confirmation
  const handleDeleteClick = (position) => {
    setSelectedPosition(position);
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
      errors.code = 'رمز المنصب مطلوب';
    }
    if (!formData.department_id) {
      errors.department_id = 'القسم مطلوب';
    }
    if (formData.min_salary && formData.max_salary) {
      if (Number(formData.min_salary) > Number(formData.max_salary)) {
        errors.max_salary = 'الحد الأقصى يجب أن يكون أكبر من الحد الأدنى';
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
        min_salary: formData.min_salary ? Number(formData.min_salary) : null,
        max_salary: formData.max_salary ? Number(formData.max_salary) : null,
      };

      if (selectedPosition) {
        await positionsApi.update(selectedPosition.id, data);
        toast.success('تم تحديث المنصب بنجاح');
      } else {
        await positionsApi.create(data);
        toast.success('تم إنشاء المنصب بنجاح');
      }
      setShowModal(false);
      resetForm();
      loadPositions();
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

  // Delete position
  const handleDelete = async () => {
    if (!selectedPosition) return;

    try {
      setSubmitting(true);
      await positionsApi.delete(selectedPosition.id);
      toast.success('تم حذف المنصب بنجاح');
      setShowDeleteModal(false);
      setSelectedPosition(null);
      loadPositions();
    } catch (error) {
      const message = error.response?.data?.message || 'فشل في حذف المنصب';
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

  // Format salary
  const formatSalary = (value) => {
    if (!value) return '-';
    return new Intl.NumberFormat('ar-SA', {
      style: 'currency',
      currency: 'SAR',
      minimumFractionDigits: 0,
    }).format(value);
  };

  return (
    <div className="space-y-6" dir="rtl">
      {/* Header */}
      <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">إدارة المناصب</h1>
          <p className="text-gray-600 mt-1">إدارة المسميات الوظيفية والدرجات</p>
        </div>
        <Button icon={HiPlus} onClick={handleCreate}>
          إضافة منصب
        </Button>
      </div>

      {/* Search & Filters */}
      <Card>
        <div className="p-4">
          <div className="flex flex-col sm:flex-row gap-4">
            <div className="flex-1">
              <Input
                placeholder="بحث بالاسم أو الرمز..."
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
            <Button variant="secondary" icon={HiRefresh} onClick={loadPositions}>
              تحديث
            </Button>
          </div>
        </div>
      </Card>

      {/* Positions Table */}
      <Card>
        <CardHeader
          title="قائمة المناصب"
          subtitle={`${positions.length} منصب`}
          icon={HiBriefcase}
        />
        <Table>
          <TableHead>
            <TableRow>
              <TableHeader>الرمز</TableHeader>
              <TableHeader>الاسم بالعربية</TableHeader>
              <TableHeader>القسم</TableHeader>
              <TableHeader>الدرجة</TableHeader>
              <TableHeader>نطاق الراتب</TableHeader>
              <TableHeader>الحالة</TableHeader>
              <TableHeader>الإجراءات</TableHeader>
            </TableRow>
          </TableHead>
          <TableBody>
            {loading ? (
              <TableLoading colSpan={7} />
            ) : positions.length === 0 ? (
              <TableEmpty
                colSpan={7}
                icon={HiBriefcase}
                message="لا توجد مناصب"
                actionLabel="إضافة منصب جديد"
                onAction={handleCreate}
              />
            ) : (
              positions.map((position) => (
                <TableRow key={position.id}>
                  <TableCell className="font-mono text-sm">
                    {position.code}
                  </TableCell>
                  <TableCell className="font-medium">{position.name_ar}</TableCell>
                  <TableCell>{position.department?.name_ar || '-'}</TableCell>
                  <TableCell>
                    {position.grade ? (
                      <Badge variant="info">{position.grade}</Badge>
                    ) : (
                      '-'
                    )}
                  </TableCell>
                  <TableCell className="text-sm">
                    {position.min_salary || position.max_salary ? (
                      <span>
                        {formatSalary(position.min_salary)} - {formatSalary(position.max_salary)}
                      </span>
                    ) : (
                      '-'
                    )}
                  </TableCell>
                  <TableCell>
                    <Badge variant={position.is_active ? 'success' : 'gray'}>
                      {position.is_active ? 'نشط' : 'غير نشط'}
                    </Badge>
                  </TableCell>
                  <TableCell>
                    <div className="flex items-center gap-2">
                      <Button
                        variant="ghost"
                        size="sm"
                        icon={HiPencil}
                        onClick={() => handleEdit(position)}
                        title="تعديل"
                      />
                      <Button
                        variant="ghost"
                        size="sm"
                        icon={HiTrash}
                        onClick={() => handleDeleteClick(position)}
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
        title={selectedPosition ? 'تعديل المنصب' : 'إضافة منصب جديد'}
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
              placeholder="مثال: مدير الموارد البشرية"
            />
            <Input
              label="الاسم بالإنجليزية"
              name="name_en"
              value={formData.name_en}
              onChange={handleInputChange}
              error={formErrors.name_en}
              required
              placeholder="e.g. HR Manager"
              dir="ltr"
            />
          </div>

          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <Input
              label="رمز المنصب"
              name="code"
              value={formData.code}
              onChange={handleInputChange}
              error={formErrors.code}
              required
              placeholder="مثال: HR-MGR"
              dir="ltr"
            />
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
          </div>

          <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
            <Input
              label="الدرجة الوظيفية"
              name="grade"
              value={formData.grade}
              onChange={handleInputChange}
              placeholder="مثال: A1"
            />
            <Input
              label="الحد الأدنى للراتب"
              name="min_salary"
              type="number"
              value={formData.min_salary}
              onChange={handleInputChange}
              error={formErrors.min_salary}
              placeholder="0"
              dir="ltr"
            />
            <Input
              label="الحد الأقصى للراتب"
              name="max_salary"
              type="number"
              value={formData.max_salary}
              onChange={handleInputChange}
              error={formErrors.max_salary}
              placeholder="0"
              dir="ltr"
            />
          </div>

          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
              الوصف الوظيفي
            </label>
            <textarea
              name="description"
              value={formData.description}
              onChange={handleInputChange}
              rows={3}
              className="w-full rounded-lg border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
              placeholder="وصف المهام والمسؤوليات..."
            />
          </div>

          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
              المتطلبات
            </label>
            <textarea
              name="requirements"
              value={formData.requirements}
              onChange={handleInputChange}
              rows={3}
              className="w-full rounded-lg border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
              placeholder="المؤهلات والخبرات المطلوبة..."
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
              المنصب نشط
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
              {selectedPosition ? 'تحديث' : 'إضافة'}
            </Button>
          </div>
        </form>
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
            هل أنت متأكد من حذف المنصب{' '}
            <span className="font-bold text-gray-900">
              {selectedPosition?.name_ar}
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
