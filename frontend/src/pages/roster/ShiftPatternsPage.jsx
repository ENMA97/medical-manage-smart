import React, { useState, useEffect, useCallback } from 'react';
import toast from 'react-hot-toast';
import { shiftPatternsApi } from '../../services/rosterApi';
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
  HiClock,
  HiSearch,
  HiRefresh,
  HiSun,
  HiMoon,
  HiOutlineSun,
  HiDuplicate,
} from 'react-icons/hi';

/**
 * صفحة أنماط الورديات
 * Shift Patterns Management Page
 */
export default function ShiftPatternsPage() {
  const [patterns, setPatterns] = useState([]);
  const [departments, setDepartments] = useState([]);
  const [loading, setLoading] = useState(true);
  const [searchTerm, setSearchTerm] = useState('');
  const [typeFilter, setTypeFilter] = useState('');
  const [showModal, setShowModal] = useState(false);
  const [showDeleteModal, setShowDeleteModal] = useState(false);
  const [selectedPattern, setSelectedPattern] = useState(null);
  const [formData, setFormData] = useState({
    name_ar: '',
    name_en: '',
    code: '',
    type: 'split',
    color: '#3B82F6',
    // First period
    start_time_1: '',
    end_time_1: '',
    // Second period (for split shifts)
    start_time_2: '',
    end_time_2: '',
    // Break time
    break_duration: 0,
    // Working hours
    total_hours: '',
    // Flags
    is_overnight: false,
    is_split: false,
    is_off_day: false,
    is_active: true,
    // Departments (optional)
    department_ids: [],
    notes: '',
  });
  const [formErrors, setFormErrors] = useState({});
  const [submitting, setSubmitting] = useState(false);

  // Shift types based on the analyzed schedule
  const shiftTypes = [
    { value: 'split', label: 'فترتين', icon: HiDuplicate, color: 'info' },
    { value: 'morning', label: 'صباحي', icon: HiSun, color: 'warning' },
    { value: 'evening', label: 'مسائي', icon: HiOutlineSun, color: 'purple' },
    { value: 'night', label: 'ليلي', icon: HiMoon, color: 'gray' },
    { value: 'full_day', label: 'يوم كامل', icon: HiClock, color: 'success' },
    { value: 'off', label: 'إجازة', icon: HiClock, color: 'danger' },
  ];

  // Predefined colors
  const colorOptions = [
    { value: '#3B82F6', label: 'أزرق' },
    { value: '#10B981', label: 'أخضر' },
    { value: '#F59E0B', label: 'برتقالي' },
    { value: '#EF4444', label: 'أحمر' },
    { value: '#8B5CF6', label: 'بنفسجي' },
    { value: '#EC4899', label: 'وردي' },
    { value: '#6B7280', label: 'رمادي' },
    { value: '#14B8A6', label: 'فيروزي' },
  ];

  // Load patterns
  const loadPatterns = useCallback(async () => {
    try {
      setLoading(true);
      const response = await shiftPatternsApi.getAll({
        search: searchTerm,
        type: typeFilter,
      });
      setPatterns(response.data?.data || response.data || []);
    } catch (error) {
      toast.error('فشل في تحميل أنماط الورديات');
      console.error('Error loading patterns:', error);
    } finally {
      setLoading(false);
    }
  }, [searchTerm, typeFilter]);

  // Load departments
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
    loadPatterns();
  }, [loadPatterns]);

  // Reset form
  const resetForm = () => {
    setFormData({
      name_ar: '',
      name_en: '',
      code: '',
      type: 'split',
      color: '#3B82F6',
      start_time_1: '',
      end_time_1: '',
      start_time_2: '',
      end_time_2: '',
      break_duration: 0,
      total_hours: '',
      is_overnight: false,
      is_split: false,
      is_off_day: false,
      is_active: true,
      department_ids: [],
      notes: '',
    });
    setFormErrors({});
    setSelectedPattern(null);
  };

  // Open create modal
  const handleCreate = () => {
    resetForm();
    setShowModal(true);
  };

  // Open edit modal
  const handleEdit = (pattern) => {
    setSelectedPattern(pattern);
    setFormData({
      name_ar: pattern.name_ar || '',
      name_en: pattern.name_en || '',
      code: pattern.code || '',
      type: pattern.type || 'split',
      color: pattern.color || '#3B82F6',
      start_time_1: pattern.start_time_1 || '',
      end_time_1: pattern.end_time_1 || '',
      start_time_2: pattern.start_time_2 || '',
      end_time_2: pattern.end_time_2 || '',
      break_duration: pattern.break_duration || 0,
      total_hours: pattern.total_hours || '',
      is_overnight: pattern.is_overnight ?? false,
      is_split: pattern.is_split ?? false,
      is_off_day: pattern.is_off_day ?? false,
      is_active: pattern.is_active ?? true,
      department_ids: pattern.department_ids || [],
      notes: pattern.notes || '',
    });
    setFormErrors({});
    setShowModal(true);
  };

  // Open delete confirmation
  const handleDeleteClick = (pattern) => {
    setSelectedPattern(pattern);
    setShowDeleteModal(true);
  };

  // Handle type change
  const handleTypeChange = (type) => {
    setFormData((prev) => ({
      ...prev,
      type,
      is_split: type === 'split',
      is_off_day: type === 'off',
      is_overnight: type === 'night',
    }));
  };

  // Validate form
  const validateForm = () => {
    const errors = {};
    if (!formData.name_ar?.trim()) {
      errors.name_ar = 'الاسم بالعربية مطلوب';
    }
    if (!formData.code?.trim()) {
      errors.code = 'رمز الوردية مطلوب';
    }
    if (!formData.type) {
      errors.type = 'نوع الوردية مطلوب';
    }
    if (formData.type !== 'off') {
      if (!formData.start_time_1) {
        errors.start_time_1 = 'وقت البداية مطلوب';
      }
      if (!formData.end_time_1) {
        errors.end_time_1 = 'وقت النهاية مطلوب';
      }
      if (formData.is_split || formData.type === 'split') {
        if (!formData.start_time_2) {
          errors.start_time_2 = 'وقت بداية الفترة الثانية مطلوب';
        }
        if (!formData.end_time_2) {
          errors.end_time_2 = 'وقت نهاية الفترة الثانية مطلوب';
        }
      }
    }
    setFormErrors(errors);
    return Object.keys(errors).length === 0;
  };

  // Calculate total hours
  const calculateTotalHours = () => {
    if (formData.type === 'off') return 0;

    let totalMinutes = 0;

    if (formData.start_time_1 && formData.end_time_1) {
      const [sh1, sm1] = formData.start_time_1.split(':').map(Number);
      const [eh1, em1] = formData.end_time_1.split(':').map(Number);
      let minutes1 = (eh1 * 60 + em1) - (sh1 * 60 + sm1);
      if (minutes1 < 0) minutes1 += 24 * 60; // Overnight
      totalMinutes += minutes1;
    }

    if ((formData.is_split || formData.type === 'split') && formData.start_time_2 && formData.end_time_2) {
      const [sh2, sm2] = formData.start_time_2.split(':').map(Number);
      const [eh2, em2] = formData.end_time_2.split(':').map(Number);
      let minutes2 = (eh2 * 60 + em2) - (sh2 * 60 + sm2);
      if (minutes2 < 0) minutes2 += 24 * 60;
      totalMinutes += minutes2;
    }

    totalMinutes -= (formData.break_duration || 0);
    return (totalMinutes / 60).toFixed(1);
  };

  // Submit form
  const handleSubmit = async (e) => {
    e.preventDefault();
    if (!validateForm()) return;

    try {
      setSubmitting(true);
      const data = {
        ...formData,
        total_hours: calculateTotalHours(),
        is_split: formData.type === 'split' || formData.is_split,
        is_off_day: formData.type === 'off',
      };

      if (selectedPattern) {
        await shiftPatternsApi.update(selectedPattern.id, data);
        toast.success('تم تحديث نمط الوردية بنجاح');
      } else {
        await shiftPatternsApi.create(data);
        toast.success('تم إنشاء نمط الوردية بنجاح');
      }
      setShowModal(false);
      resetForm();
      loadPatterns();
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

  // Delete pattern
  const handleDelete = async () => {
    if (!selectedPattern) return;

    try {
      setSubmitting(true);
      await shiftPatternsApi.delete(selectedPattern.id);
      toast.success('تم حذف نمط الوردية بنجاح');
      setShowDeleteModal(false);
      setSelectedPattern(null);
      loadPatterns();
    } catch (error) {
      const message = error.response?.data?.message || 'فشل في حذف نمط الوردية';
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

  // Get type info
  const getTypeInfo = (type) => {
    const found = shiftTypes.find((t) => t.value === type);
    return found || { label: type, color: 'gray' };
  };

  // Format time range
  const formatTimeRange = (pattern) => {
    if (pattern.is_off_day || pattern.type === 'off') {
      return 'إجازة';
    }

    let range = `${pattern.start_time_1} - ${pattern.end_time_1}`;
    if (pattern.is_split && pattern.start_time_2 && pattern.end_time_2) {
      range += ` + ${pattern.start_time_2} - ${pattern.end_time_2}`;
    }
    return range;
  };

  return (
    <div className="space-y-6" dir="rtl">
      {/* Header */}
      <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">أنماط الورديات</h1>
          <p className="text-gray-600 mt-1">إدارة أنماط وجداول الورديات المختلفة</p>
        </div>
        <Button icon={HiPlus} onClick={handleCreate}>
          إضافة نمط جديد
        </Button>
      </div>

      {/* Quick Stats */}
      <div className="grid grid-cols-2 sm:grid-cols-4 gap-4">
        {shiftTypes.slice(0, 4).map((type) => {
          const count = patterns.filter((p) => p.type === type.value).length;
          const Icon = type.icon;
          return (
            <Card key={type.value}>
              <div className="p-4">
                <div className="flex items-center justify-between">
                  <div>
                    <p className="text-sm text-gray-500">{type.label}</p>
                    <p className="text-2xl font-bold">{count}</p>
                  </div>
                  <Icon className="w-8 h-8 text-gray-400" />
                </div>
              </div>
            </Card>
          );
        })}
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
                value={typeFilter}
                onChange={(e) => setTypeFilter(e.target.value)}
                options={[
                  { value: '', label: 'جميع الأنواع' },
                  ...shiftTypes.map((t) => ({ value: t.value, label: t.label })),
                ]}
              />
            </div>
            <Button variant="secondary" icon={HiRefresh} onClick={loadPatterns}>
              تحديث
            </Button>
          </div>
        </div>
      </Card>

      {/* Patterns Table */}
      <Card>
        <CardHeader
          title="قائمة أنماط الورديات"
          subtitle={`${patterns.length} نمط`}
          icon={HiClock}
        />
        <Table>
          <TableHead>
            <TableRow>
              <TableHeader>الرمز</TableHeader>
              <TableHeader>الاسم</TableHeader>
              <TableHeader>النوع</TableHeader>
              <TableHeader>التوقيت</TableHeader>
              <TableHeader>الساعات</TableHeader>
              <TableHeader>الحالة</TableHeader>
              <TableHeader>الإجراءات</TableHeader>
            </TableRow>
          </TableHead>
          <TableBody>
            {loading ? (
              <TableLoading colSpan={7} />
            ) : patterns.length === 0 ? (
              <TableEmpty
                colSpan={7}
                icon={HiClock}
                message="لا توجد أنماط ورديات"
                actionLabel="إضافة نمط جديد"
                onAction={handleCreate}
              />
            ) : (
              patterns.map((pattern) => {
                const typeInfo = getTypeInfo(pattern.type);
                return (
                  <TableRow key={pattern.id}>
                    <TableCell>
                      <div className="flex items-center gap-2">
                        <div
                          className="w-4 h-4 rounded"
                          style={{ backgroundColor: pattern.color || '#3B82F6' }}
                        />
                        <span className="font-mono">{pattern.code}</span>
                      </div>
                    </TableCell>
                    <TableCell>
                      <div>
                        <span className="font-medium">{pattern.name_ar}</span>
                        {pattern.name_en && (
                          <span className="block text-sm text-gray-500" dir="ltr">
                            {pattern.name_en}
                          </span>
                        )}
                      </div>
                    </TableCell>
                    <TableCell>
                      <Badge variant={typeInfo.color}>{typeInfo.label}</Badge>
                    </TableCell>
                    <TableCell className="text-sm" dir="ltr">
                      {formatTimeRange(pattern)}
                    </TableCell>
                    <TableCell>
                      {pattern.is_off_day ? '-' : `${pattern.total_hours || 0} ساعة`}
                    </TableCell>
                    <TableCell>
                      <Badge variant={pattern.is_active ? 'success' : 'gray'}>
                        {pattern.is_active ? 'نشط' : 'غير نشط'}
                      </Badge>
                    </TableCell>
                    <TableCell>
                      <div className="flex items-center gap-2">
                        <Button
                          variant="ghost"
                          size="sm"
                          icon={HiPencil}
                          onClick={() => handleEdit(pattern)}
                          title="تعديل"
                        />
                        <Button
                          variant="ghost"
                          size="sm"
                          icon={HiTrash}
                          onClick={() => handleDeleteClick(pattern)}
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
        title={selectedPattern ? 'تعديل نمط الوردية' : 'إضافة نمط وردية جديد'}
        size="lg"
      >
        <form onSubmit={handleSubmit} className="space-y-4">
          {/* Basic Info */}
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <Input
              label="الاسم بالعربية"
              name="name_ar"
              value={formData.name_ar}
              onChange={handleInputChange}
              error={formErrors.name_ar}
              required
              placeholder="مثال: وردية فترتين صباحي-مسائي"
            />
            <Input
              label="الاسم بالإنجليزية"
              name="name_en"
              value={formData.name_en}
              onChange={handleInputChange}
              placeholder="e.g. Split Morning-Evening"
              dir="ltr"
            />
          </div>

          <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
            <Input
              label="الرمز"
              name="code"
              value={formData.code}
              onChange={handleInputChange}
              error={formErrors.code}
              required
              placeholder="SPL-01"
              dir="ltr"
            />
            <Select
              label="نوع الوردية"
              name="type"
              value={formData.type}
              onChange={(e) => handleTypeChange(e.target.value)}
              error={formErrors.type}
              required
              options={shiftTypes}
            />
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">
                اللون
              </label>
              <div className="flex items-center gap-2">
                <input
                  type="color"
                  name="color"
                  value={formData.color}
                  onChange={handleInputChange}
                  className="w-10 h-10 rounded border cursor-pointer"
                />
                <Select
                  value={formData.color}
                  onChange={(e) => setFormData((prev) => ({ ...prev, color: e.target.value }))}
                  options={colorOptions}
                  className="flex-1"
                />
              </div>
            </div>
          </div>

          {/* Time Settings - Only show if not off day */}
          {formData.type !== 'off' && (
            <>
              <div className="border-t pt-4">
                <h3 className="font-medium mb-3">
                  {formData.type === 'split' ? 'الفترة الأولى' : 'وقت الوردية'}
                </h3>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <Input
                    label="وقت البداية"
                    name="start_time_1"
                    type="time"
                    value={formData.start_time_1}
                    onChange={handleInputChange}
                    error={formErrors.start_time_1}
                    required
                  />
                  <Input
                    label="وقت النهاية"
                    name="end_time_1"
                    type="time"
                    value={formData.end_time_1}
                    onChange={handleInputChange}
                    error={formErrors.end_time_1}
                    required
                  />
                </div>
              </div>

              {/* Second Period for Split Shifts */}
              {(formData.type === 'split' || formData.is_split) && (
                <div className="border-t pt-4">
                  <h3 className="font-medium mb-3">الفترة الثانية</h3>
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <Input
                      label="وقت البداية"
                      name="start_time_2"
                      type="time"
                      value={formData.start_time_2}
                      onChange={handleInputChange}
                      error={formErrors.start_time_2}
                      required
                    />
                    <Input
                      label="وقت النهاية"
                      name="end_time_2"
                      type="time"
                      value={formData.end_time_2}
                      onChange={handleInputChange}
                      error={formErrors.end_time_2}
                      required
                    />
                  </div>
                </div>
              )}

              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <Input
                  label="مدة الاستراحة (دقيقة)"
                  name="break_duration"
                  type="number"
                  value={formData.break_duration}
                  onChange={handleInputChange}
                  placeholder="0"
                  min="0"
                  dir="ltr"
                />
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">
                    إجمالي الساعات (تلقائي)
                  </label>
                  <div className="px-3 py-2 bg-gray-100 rounded-lg font-medium">
                    {calculateTotalHours()} ساعة
                  </div>
                </div>
              </div>
            </>
          )}

          {/* Options */}
          <div className="border-t pt-4">
            <div className="flex flex-wrap gap-6">
              {formData.type !== 'off' && formData.type !== 'split' && (
                <div className="flex items-center gap-2">
                  <input
                    type="checkbox"
                    id="is_split"
                    name="is_split"
                    checked={formData.is_split}
                    onChange={handleInputChange}
                    className="w-4 h-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                  />
                  <label htmlFor="is_split" className="text-sm text-gray-700">
                    وردية مقسمة (فترتين)
                  </label>
                </div>
              )}
              <div className="flex items-center gap-2">
                <input
                  type="checkbox"
                  id="is_overnight"
                  name="is_overnight"
                  checked={formData.is_overnight}
                  onChange={handleInputChange}
                  className="w-4 h-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                />
                <label htmlFor="is_overnight" className="text-sm text-gray-700">
                  وردية ليلية (تتجاوز منتصف الليل)
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
                  النمط نشط
                </label>
              </div>
            </div>
          </div>

          {/* Notes */}
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
              {selectedPattern ? 'تحديث' : 'إضافة'}
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
            هل أنت متأكد من حذف نمط الوردية{' '}
            <span className="font-bold text-gray-900">
              {selectedPattern?.name_ar}
            </span>
            ؟
          </p>
          <p className="text-sm text-yellow-600">
            تحذير: قد يؤثر هذا على الجداول المرتبطة بهذا النمط
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
