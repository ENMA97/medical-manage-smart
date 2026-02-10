import React, { useState, useEffect, useCallback } from 'react';
import toast from 'react-hot-toast';
import { departmentsApi } from '../../services/hrApi';
import {
  Button,
  Input,
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
  HiUsers,
} from 'react-icons/hi';

/**
 * صفحة إدارة الأقسام
 * Departments Management Page
 */
export default function DepartmentsPage() {
  const [departments, setDepartments] = useState([]);
  const [loading, setLoading] = useState(true);
  const [searchTerm, setSearchTerm] = useState('');
  const [showModal, setShowModal] = useState(false);
  const [showDeleteModal, setShowDeleteModal] = useState(false);
  const [selectedDepartment, setSelectedDepartment] = useState(null);
  const [formData, setFormData] = useState({
    name_ar: '',
    name_en: '',
    code: '',
    parent_id: null,
    manager_id: null,
    description: '',
    is_active: true,
  });
  const [formErrors, setFormErrors] = useState({});
  const [submitting, setSubmitting] = useState(false);

  // Load departments
  const loadDepartments = useCallback(async () => {
    try {
      setLoading(true);
      const response = await departmentsApi.getAll({ search: searchTerm });
      setDepartments(response.data?.data || response.data || []);
    } catch (error) {
      toast.error('فشل في تحميل الأقسام');
      console.error('Error loading departments:', error);
    } finally {
      setLoading(false);
    }
  }, [searchTerm]);

  useEffect(() => {
    loadDepartments();
  }, [loadDepartments]);

  // Reset form
  const resetForm = () => {
    setFormData({
      name_ar: '',
      name_en: '',
      code: '',
      parent_id: null,
      manager_id: null,
      description: '',
      is_active: true,
    });
    setFormErrors({});
    setSelectedDepartment(null);
  };

  // Open create modal
  const handleCreate = () => {
    resetForm();
    setShowModal(true);
  };

  // Open edit modal
  const handleEdit = (department) => {
    setSelectedDepartment(department);
    setFormData({
      name_ar: department.name_ar || '',
      name_en: department.name_en || '',
      code: department.code || '',
      parent_id: department.parent_id || null,
      manager_id: department.manager_id || null,
      description: department.description || '',
      is_active: department.is_active ?? true,
    });
    setFormErrors({});
    setShowModal(true);
  };

  // Open delete confirmation
  const handleDeleteClick = (department) => {
    setSelectedDepartment(department);
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
      errors.code = 'رمز القسم مطلوب';
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
      if (selectedDepartment) {
        await departmentsApi.update(selectedDepartment.id, formData);
        toast.success('تم تحديث القسم بنجاح');
      } else {
        await departmentsApi.create(formData);
        toast.success('تم إنشاء القسم بنجاح');
      }
      setShowModal(false);
      resetForm();
      loadDepartments();
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

  // Delete department
  const handleDelete = async () => {
    if (!selectedDepartment) return;

    try {
      setSubmitting(true);
      await departmentsApi.delete(selectedDepartment.id);
      toast.success('تم حذف القسم بنجاح');
      setShowDeleteModal(false);
      setSelectedDepartment(null);
      loadDepartments();
    } catch (error) {
      const message = error.response?.data?.message || 'فشل في حذف القسم';
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
    // Clear error when user starts typing
    if (formErrors[name]) {
      setFormErrors((prev) => ({ ...prev, [name]: null }));
    }
  };

  return (
    <div className="space-y-6" dir="rtl">
      {/* Header */}
      <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">إدارة الأقسام</h1>
          <p className="text-gray-600 mt-1">إدارة الهيكل التنظيمي وأقسام المنشأة</p>
        </div>
        <Button icon={HiPlus} onClick={handleCreate}>
          إضافة قسم
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
            <Button variant="secondary" icon={HiRefresh} onClick={loadDepartments}>
              تحديث
            </Button>
          </div>
        </div>
      </Card>

      {/* Departments Table */}
      <Card>
        <CardHeader
          title="قائمة الأقسام"
          subtitle={`${departments.length} قسم`}
          icon={HiOfficeBuilding}
        />
        <Table>
          <TableHead>
            <TableRow>
              <TableHeader>الرمز</TableHeader>
              <TableHeader>الاسم بالعربية</TableHeader>
              <TableHeader>الاسم بالإنجليزية</TableHeader>
              <TableHeader>القسم الرئيسي</TableHeader>
              <TableHeader>عدد الموظفين</TableHeader>
              <TableHeader>الحالة</TableHeader>
              <TableHeader>الإجراءات</TableHeader>
            </TableRow>
          </TableHead>
          <TableBody>
            {loading ? (
              <TableLoading colSpan={7} />
            ) : departments.length === 0 ? (
              <TableEmpty
                colSpan={7}
                icon={HiOfficeBuilding}
                message="لا توجد أقسام"
                actionLabel="إضافة قسم جديد"
                onAction={handleCreate}
              />
            ) : (
              departments.map((department) => (
                <TableRow key={department.id}>
                  <TableCell className="font-mono text-sm">
                    {department.code}
                  </TableCell>
                  <TableCell className="font-medium">{department.name_ar}</TableCell>
                  <TableCell>{department.name_en}</TableCell>
                  <TableCell>{department.parent?.name_ar || '-'}</TableCell>
                  <TableCell>
                    <span className="inline-flex items-center gap-1 text-gray-600">
                      <HiUsers className="w-4 h-4" />
                      {department.employees_count || 0}
                    </span>
                  </TableCell>
                  <TableCell>
                    <Badge variant={department.is_active ? 'success' : 'gray'}>
                      {department.is_active ? 'نشط' : 'غير نشط'}
                    </Badge>
                  </TableCell>
                  <TableCell>
                    <div className="flex items-center gap-2">
                      <Button
                        variant="ghost"
                        size="sm"
                        icon={HiPencil}
                        onClick={() => handleEdit(department)}
                        title="تعديل"
                      />
                      <Button
                        variant="ghost"
                        size="sm"
                        icon={HiTrash}
                        onClick={() => handleDeleteClick(department)}
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
        title={selectedDepartment ? 'تعديل القسم' : 'إضافة قسم جديد'}
        size="md"
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
              placeholder="مثال: قسم الموارد البشرية"
            />
            <Input
              label="الاسم بالإنجليزية"
              name="name_en"
              value={formData.name_en}
              onChange={handleInputChange}
              error={formErrors.name_en}
              required
              placeholder="e.g. Human Resources"
              dir="ltr"
            />
          </div>

          <Input
            label="رمز القسم"
            name="code"
            value={formData.code}
            onChange={handleInputChange}
            error={formErrors.code}
            required
            placeholder="مثال: HR"
            dir="ltr"
            hint="رمز فريد للقسم (حروف وأرقام فقط)"
          />

          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
              القسم الرئيسي
            </label>
            <select
              name="parent_id"
              value={formData.parent_id || ''}
              onChange={handleInputChange}
              className="w-full rounded-lg border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
            >
              <option value="">بدون قسم رئيسي</option>
              {departments
                .filter((d) => d.id !== selectedDepartment?.id)
                .map((dept) => (
                  <option key={dept.id} value={dept.id}>
                    {dept.name_ar}
                  </option>
                ))}
            </select>
          </div>

          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
              الوصف
            </label>
            <textarea
              name="description"
              value={formData.description}
              onChange={handleInputChange}
              rows={3}
              className="w-full rounded-lg border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
              placeholder="وصف مختصر عن القسم ومهامه..."
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
              القسم نشط
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
              {selectedDepartment ? 'تحديث' : 'إضافة'}
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
            هل أنت متأكد من حذف القسم{' '}
            <span className="font-bold text-gray-900">
              {selectedDepartment?.name_ar}
            </span>
            ؟
          </p>
          <p className="text-sm text-red-600">
            تحذير: سيتم حذف جميع البيانات المرتبطة بهذا القسم.
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
