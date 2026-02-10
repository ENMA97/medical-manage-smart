import React, { useState, useEffect, useCallback } from 'react';
import toast from 'react-hot-toast';
import { employeesApi, departmentsApi, positionsApi } from '../../services/hrApi';
import {
  Button,
  Input,
  Select,
  Modal,
  Card,
  CardHeader,
  CardBody,
  Table,
  TableHead,
  TableBody,
  TableRow,
  TableHeader,
  TableCell,
  TableEmpty,
  TableLoading,
  Badge,
  StatusBadge,
} from '../../components/ui';
import {
  HiPlus,
  HiPencil,
  HiTrash,
  HiUserGroup,
  HiSearch,
  HiRefresh,
  HiEye,
  HiDownload,
  HiMail,
  HiPhone,
  HiIdentification,
  HiCalendar,
  HiOfficeBuilding,
  HiBriefcase,
} from 'react-icons/hi';

// Contract type labels
const CONTRACT_TYPES = {
  full_time: { label: 'دوام كامل', variant: 'success' },
  part_time: { label: 'دوام جزئي', variant: 'info' },
  tamheer: { label: 'تمهير', variant: 'warning' },
  percentage: { label: 'نسبة', variant: 'purple' },
  locum: { label: 'مناوب', variant: 'gray' },
};

// Employee status labels
const EMPLOYEE_STATUS = {
  active: { label: 'نشط', variant: 'success' },
  inactive: { label: 'غير نشط', variant: 'gray' },
  on_leave: { label: 'في إجازة', variant: 'warning' },
  terminated: { label: 'منتهي', variant: 'danger' },
};

/**
 * صفحة إدارة الموظفين
 * Employees Management Page
 */
export default function EmployeesPage() {
  const [employees, setEmployees] = useState([]);
  const [departments, setDepartments] = useState([]);
  const [positions, setPositions] = useState([]);
  const [loading, setLoading] = useState(true);
  const [showModal, setShowModal] = useState(false);
  const [showViewModal, setShowViewModal] = useState(false);
  const [showDeleteModal, setShowDeleteModal] = useState(false);
  const [selectedEmployee, setSelectedEmployee] = useState(null);
  const [submitting, setSubmitting] = useState(false);

  // Filters
  const [filters, setFilters] = useState({
    search: '',
    department_id: '',
    status: '',
    contract_type: '',
  });

  // Form data
  const [formData, setFormData] = useState({
    employee_number: '',
    name_ar: '',
    name_en: '',
    email: '',
    phone: '',
    national_id: '',
    nationality: 'SA',
    gender: 'male',
    birth_date: '',
    hire_date: '',
    department_id: '',
    position_id: '',
    contract_type: 'full_time',
    basic_salary: '',
    status: 'active',
  });
  const [formErrors, setFormErrors] = useState({});

  // Load employees
  const loadEmployees = useCallback(async () => {
    try {
      setLoading(true);
      const params = {};
      if (filters.search) params.search = filters.search;
      if (filters.department_id) params.department_id = filters.department_id;
      if (filters.status) params.status = filters.status;
      if (filters.contract_type) params.contract_type = filters.contract_type;

      const response = await employeesApi.getAll(params);
      setEmployees(response.data?.data || response.data || []);
    } catch (error) {
      toast.error('فشل في تحميل الموظفين');
      console.error('Error loading employees:', error);
    } finally {
      setLoading(false);
    }
  }, [filters]);

  // Load lookup data
  const loadLookups = useCallback(async () => {
    try {
      const [deptRes, posRes] = await Promise.all([
        departmentsApi.getActive(),
        positionsApi.getActive(),
      ]);
      setDepartments(deptRes.data?.data || deptRes.data || []);
      setPositions(posRes.data?.data || posRes.data || []);
    } catch (error) {
      console.error('Error loading lookups:', error);
    }
  }, []);

  useEffect(() => {
    loadLookups();
  }, [loadLookups]);

  useEffect(() => {
    loadEmployees();
  }, [loadEmployees]);

  // Reset form
  const resetForm = () => {
    setFormData({
      employee_number: '',
      name_ar: '',
      name_en: '',
      email: '',
      phone: '',
      national_id: '',
      nationality: 'SA',
      gender: 'male',
      birth_date: '',
      hire_date: '',
      department_id: '',
      position_id: '',
      contract_type: 'full_time',
      basic_salary: '',
      status: 'active',
    });
    setFormErrors({});
    setSelectedEmployee(null);
  };

  // Open create modal
  const handleCreate = () => {
    resetForm();
    setShowModal(true);
  };

  // Open edit modal
  const handleEdit = (employee) => {
    setSelectedEmployee(employee);
    setFormData({
      employee_number: employee.employee_number || '',
      name_ar: employee.name_ar || '',
      name_en: employee.name_en || '',
      email: employee.email || '',
      phone: employee.phone || '',
      national_id: employee.national_id || '',
      nationality: employee.nationality || 'SA',
      gender: employee.gender || 'male',
      birth_date: employee.birth_date || '',
      hire_date: employee.hire_date || '',
      department_id: employee.department_id || '',
      position_id: employee.position_id || '',
      contract_type: employee.contract_type || 'full_time',
      basic_salary: employee.basic_salary || '',
      status: employee.status || 'active',
    });
    setFormErrors({});
    setShowModal(true);
  };

  // View employee details
  const handleView = (employee) => {
    setSelectedEmployee(employee);
    setShowViewModal(true);
  };

  // Open delete confirmation
  const handleDeleteClick = (employee) => {
    setSelectedEmployee(employee);
    setShowDeleteModal(true);
  };

  // Validate form
  const validateForm = () => {
    const errors = {};
    if (!formData.employee_number?.trim()) errors.employee_number = 'الرقم الوظيفي مطلوب';
    if (!formData.name_ar?.trim()) errors.name_ar = 'الاسم بالعربية مطلوب';
    if (!formData.name_en?.trim()) errors.name_en = 'الاسم بالإنجليزية مطلوب';
    if (!formData.email?.trim()) errors.email = 'البريد الإلكتروني مطلوب';
    if (!formData.national_id?.trim()) errors.national_id = 'رقم الهوية مطلوب';
    if (!formData.department_id) errors.department_id = 'القسم مطلوب';
    if (!formData.position_id) errors.position_id = 'المنصب مطلوب';
    if (!formData.hire_date) errors.hire_date = 'تاريخ التعيين مطلوب';

    // Email validation
    if (formData.email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(formData.email)) {
      errors.email = 'البريد الإلكتروني غير صالح';
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
        basic_salary: formData.basic_salary ? Number(formData.basic_salary) : null,
      };

      if (selectedEmployee) {
        await employeesApi.update(selectedEmployee.id, data);
        toast.success('تم تحديث بيانات الموظف بنجاح');
      } else {
        await employeesApi.create(data);
        toast.success('تم إضافة الموظف بنجاح');
      }
      setShowModal(false);
      resetForm();
      loadEmployees();
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

  // Delete employee
  const handleDelete = async () => {
    if (!selectedEmployee) return;

    try {
      setSubmitting(true);
      await employeesApi.delete(selectedEmployee.id);
      toast.success('تم حذف الموظف بنجاح');
      setShowDeleteModal(false);
      setSelectedEmployee(null);
      loadEmployees();
    } catch (error) {
      const message = error.response?.data?.message || 'فشل في حذف الموظف';
      toast.error(message);
    } finally {
      setSubmitting(false);
    }
  };

  // Handle input change
  const handleInputChange = (e) => {
    const { name, value } = e.target;
    setFormData((prev) => ({ ...prev, [name]: value }));
    if (formErrors[name]) {
      setFormErrors((prev) => ({ ...prev, [name]: null }));
    }
  };

  // Handle filter change
  const handleFilterChange = (e) => {
    const { name, value } = e.target;
    setFilters((prev) => ({ ...prev, [name]: value }));
  };

  // Clear filters
  const clearFilters = () => {
    setFilters({ search: '', department_id: '', status: '', contract_type: '' });
  };

  // Export employees
  const handleExport = async () => {
    try {
      const response = await employeesApi.exportList(filters);
      const url = window.URL.createObjectURL(new Blob([response.data]));
      const link = document.createElement('a');
      link.href = url;
      link.setAttribute('download', `employees_${new Date().toISOString().split('T')[0]}.xlsx`);
      document.body.appendChild(link);
      link.click();
      link.remove();
      toast.success('تم تصدير البيانات بنجاح');
    } catch (error) {
      toast.error('فشل في تصدير البيانات');
    }
  };

  // Get filtered positions based on selected department
  const filteredPositions = formData.department_id
    ? positions.filter((p) => p.department_id === formData.department_id)
    : positions;

  return (
    <div className="space-y-6" dir="rtl">
      {/* Header */}
      <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">إدارة الموظفين</h1>
          <p className="text-gray-600 mt-1">عرض وإدارة بيانات الموظفين</p>
        </div>
        <div className="flex gap-2">
          <Button variant="secondary" icon={HiDownload} onClick={handleExport}>
            تصدير
          </Button>
          <Button icon={HiPlus} onClick={handleCreate}>
            إضافة موظف
          </Button>
        </div>
      </div>

      {/* Filters */}
      <Card>
        <div className="p-4">
          <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
            <Input
              placeholder="بحث بالاسم أو الرقم..."
              name="search"
              value={filters.search}
              onChange={handleFilterChange}
              icon={HiSearch}
            />
            <Select
              name="department_id"
              value={filters.department_id}
              onChange={handleFilterChange}
              options={[
                { value: '', label: 'جميع الأقسام' },
                ...departments.map((d) => ({ value: d.id, label: d.name_ar })),
              ]}
            />
            <Select
              name="status"
              value={filters.status}
              onChange={handleFilterChange}
              options={[
                { value: '', label: 'جميع الحالات' },
                { value: 'active', label: 'نشط' },
                { value: 'inactive', label: 'غير نشط' },
                { value: 'on_leave', label: 'في إجازة' },
                { value: 'terminated', label: 'منتهي' },
              ]}
            />
            <Select
              name="contract_type"
              value={filters.contract_type}
              onChange={handleFilterChange}
              options={[
                { value: '', label: 'جميع العقود' },
                { value: 'full_time', label: 'دوام كامل' },
                { value: 'part_time', label: 'دوام جزئي' },
                { value: 'tamheer', label: 'تمهير' },
                { value: 'percentage', label: 'نسبة' },
                { value: 'locum', label: 'مناوب' },
              ]}
            />
            <div className="flex gap-2">
              <Button variant="secondary" icon={HiRefresh} onClick={loadEmployees} className="flex-1">
                تحديث
              </Button>
              <Button variant="ghost" onClick={clearFilters}>
                مسح
              </Button>
            </div>
          </div>
        </div>
      </Card>

      {/* Employees Table */}
      <Card>
        <CardHeader
          title="قائمة الموظفين"
          subtitle={`${employees.length} موظف`}
          icon={HiUserGroup}
        />
        <Table>
          <TableHead>
            <TableRow>
              <TableHeader>الرقم الوظيفي</TableHeader>
              <TableHeader>الاسم</TableHeader>
              <TableHeader>القسم</TableHeader>
              <TableHeader>المنصب</TableHeader>
              <TableHeader>نوع العقد</TableHeader>
              <TableHeader>تاريخ التعيين</TableHeader>
              <TableHeader>الحالة</TableHeader>
              <TableHeader>الإجراءات</TableHeader>
            </TableRow>
          </TableHead>
          <TableBody>
            {loading ? (
              <TableLoading colSpan={8} />
            ) : employees.length === 0 ? (
              <TableEmpty
                colSpan={8}
                icon={HiUserGroup}
                message="لا يوجد موظفين"
                actionLabel="إضافة موظف جديد"
                onAction={handleCreate}
              />
            ) : (
              employees.map((employee) => (
                <TableRow key={employee.id}>
                  <TableCell className="font-mono text-sm">
                    {employee.employee_number}
                  </TableCell>
                  <TableCell>
                    <div>
                      <div className="font-medium">{employee.name_ar}</div>
                      <div className="text-sm text-gray-500">{employee.name_en}</div>
                    </div>
                  </TableCell>
                  <TableCell>{employee.department?.name_ar || '-'}</TableCell>
                  <TableCell>{employee.position?.name_ar || '-'}</TableCell>
                  <TableCell>
                    <Badge variant={CONTRACT_TYPES[employee.contract_type]?.variant || 'gray'}>
                      {CONTRACT_TYPES[employee.contract_type]?.label || employee.contract_type}
                    </Badge>
                  </TableCell>
                  <TableCell>{employee.hire_date || '-'}</TableCell>
                  <TableCell>
                    <Badge variant={EMPLOYEE_STATUS[employee.status]?.variant || 'gray'}>
                      {EMPLOYEE_STATUS[employee.status]?.label || employee.status}
                    </Badge>
                  </TableCell>
                  <TableCell>
                    <div className="flex items-center gap-1">
                      <Button
                        variant="ghost"
                        size="sm"
                        icon={HiEye}
                        onClick={() => handleView(employee)}
                        title="عرض"
                      />
                      <Button
                        variant="ghost"
                        size="sm"
                        icon={HiPencil}
                        onClick={() => handleEdit(employee)}
                        title="تعديل"
                      />
                      <Button
                        variant="ghost"
                        size="sm"
                        icon={HiTrash}
                        onClick={() => handleDeleteClick(employee)}
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
        title={selectedEmployee ? 'تعديل بيانات الموظف' : 'إضافة موظف جديد'}
        size="xl"
      >
        <form onSubmit={handleSubmit} className="space-y-6">
          {/* Basic Info */}
          <div>
            <h3 className="text-lg font-medium text-gray-900 mb-4">البيانات الأساسية</h3>
            <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
              <Input
                label="الرقم الوظيفي"
                name="employee_number"
                value={formData.employee_number}
                onChange={handleInputChange}
                error={formErrors.employee_number}
                required
                placeholder="EMP-001"
                dir="ltr"
              />
              <Input
                label="الاسم بالعربية"
                name="name_ar"
                value={formData.name_ar}
                onChange={handleInputChange}
                error={formErrors.name_ar}
                required
                placeholder="محمد أحمد"
              />
              <Input
                label="الاسم بالإنجليزية"
                name="name_en"
                value={formData.name_en}
                onChange={handleInputChange}
                error={formErrors.name_en}
                required
                placeholder="Mohammed Ahmed"
                dir="ltr"
              />
            </div>
          </div>

          {/* Contact Info */}
          <div>
            <h3 className="text-lg font-medium text-gray-900 mb-4">بيانات التواصل</h3>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              <Input
                label="البريد الإلكتروني"
                name="email"
                type="email"
                value={formData.email}
                onChange={handleInputChange}
                error={formErrors.email}
                required
                placeholder="email@example.com"
                dir="ltr"
              />
              <Input
                label="رقم الجوال"
                name="phone"
                value={formData.phone}
                onChange={handleInputChange}
                error={formErrors.phone}
                placeholder="05xxxxxxxx"
                dir="ltr"
              />
            </div>
          </div>

          {/* Identity Info */}
          <div>
            <h3 className="text-lg font-medium text-gray-900 mb-4">بيانات الهوية</h3>
            <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
              <Input
                label="رقم الهوية"
                name="national_id"
                value={formData.national_id}
                onChange={handleInputChange}
                error={formErrors.national_id}
                required
                placeholder="1xxxxxxxxx"
                dir="ltr"
              />
              <Select
                label="الجنسية"
                name="nationality"
                value={formData.nationality}
                onChange={handleInputChange}
                options={[
                  { value: 'SA', label: 'سعودي' },
                  { value: 'OTHER', label: 'أخرى' },
                ]}
              />
              <Select
                label="الجنس"
                name="gender"
                value={formData.gender}
                onChange={handleInputChange}
                options={[
                  { value: 'male', label: 'ذكر' },
                  { value: 'female', label: 'أنثى' },
                ]}
              />
              <Input
                label="تاريخ الميلاد"
                name="birth_date"
                type="date"
                value={formData.birth_date}
                onChange={handleInputChange}
                dir="ltr"
              />
            </div>
          </div>

          {/* Employment Info */}
          <div>
            <h3 className="text-lg font-medium text-gray-900 mb-4">بيانات التوظيف</h3>
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
                label="المنصب"
                name="position_id"
                value={formData.position_id}
                onChange={handleInputChange}
                error={formErrors.position_id}
                required
                placeholder="اختر المنصب"
                options={filteredPositions.map((p) => ({ value: p.id, label: p.name_ar }))}
              />
              <Select
                label="نوع العقد"
                name="contract_type"
                value={formData.contract_type}
                onChange={handleInputChange}
                options={Object.entries(CONTRACT_TYPES).map(([value, { label }]) => ({
                  value,
                  label,
                }))}
              />
            </div>
            <div className="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
              <Input
                label="تاريخ التعيين"
                name="hire_date"
                type="date"
                value={formData.hire_date}
                onChange={handleInputChange}
                error={formErrors.hire_date}
                required
                dir="ltr"
              />
              <Input
                label="الراتب الأساسي"
                name="basic_salary"
                type="number"
                value={formData.basic_salary}
                onChange={handleInputChange}
                placeholder="0"
                dir="ltr"
              />
              <Select
                label="الحالة"
                name="status"
                value={formData.status}
                onChange={handleInputChange}
                options={Object.entries(EMPLOYEE_STATUS).map(([value, { label }]) => ({
                  value,
                  label,
                }))}
              />
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
              {selectedEmployee ? 'تحديث' : 'إضافة'}
            </Button>
          </div>
        </form>
      </Modal>

      {/* View Modal */}
      <Modal
        isOpen={showViewModal}
        onClose={() => setShowViewModal(false)}
        title="تفاصيل الموظف"
        size="lg"
      >
        {selectedEmployee && (
          <div className="space-y-6">
            {/* Header */}
            <div className="flex items-center gap-4 pb-4 border-b">
              <div className="w-16 h-16 bg-primary-100 rounded-full flex items-center justify-center">
                <span className="text-2xl font-bold text-primary-600">
                  {selectedEmployee.name_ar?.charAt(0)}
                </span>
              </div>
              <div>
                <h3 className="text-xl font-bold text-gray-900">{selectedEmployee.name_ar}</h3>
                <p className="text-gray-500">{selectedEmployee.name_en}</p>
                <p className="text-sm text-gray-400">#{selectedEmployee.employee_number}</p>
              </div>
              <div className="mr-auto">
                <Badge variant={EMPLOYEE_STATUS[selectedEmployee.status]?.variant || 'gray'} size="lg">
                  {EMPLOYEE_STATUS[selectedEmployee.status]?.label || selectedEmployee.status}
                </Badge>
              </div>
            </div>

            {/* Details Grid */}
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              {/* Contact Info */}
              <div className="space-y-3">
                <h4 className="font-medium text-gray-900 flex items-center gap-2">
                  <HiMail className="w-5 h-5 text-gray-400" />
                  بيانات التواصل
                </h4>
                <div className="bg-gray-50 rounded-lg p-4 space-y-2">
                  <div className="flex justify-between">
                    <span className="text-gray-500">البريد الإلكتروني</span>
                    <span className="font-medium">{selectedEmployee.email || '-'}</span>
                  </div>
                  <div className="flex justify-between">
                    <span className="text-gray-500">الجوال</span>
                    <span className="font-medium" dir="ltr">{selectedEmployee.phone || '-'}</span>
                  </div>
                </div>
              </div>

              {/* Identity Info */}
              <div className="space-y-3">
                <h4 className="font-medium text-gray-900 flex items-center gap-2">
                  <HiIdentification className="w-5 h-5 text-gray-400" />
                  بيانات الهوية
                </h4>
                <div className="bg-gray-50 rounded-lg p-4 space-y-2">
                  <div className="flex justify-between">
                    <span className="text-gray-500">رقم الهوية</span>
                    <span className="font-medium" dir="ltr">{selectedEmployee.national_id || '-'}</span>
                  </div>
                  <div className="flex justify-between">
                    <span className="text-gray-500">الجنسية</span>
                    <span className="font-medium">{selectedEmployee.nationality === 'SA' ? 'سعودي' : 'أخرى'}</span>
                  </div>
                  <div className="flex justify-between">
                    <span className="text-gray-500">الجنس</span>
                    <span className="font-medium">{selectedEmployee.gender === 'male' ? 'ذكر' : 'أنثى'}</span>
                  </div>
                </div>
              </div>

              {/* Employment Info */}
              <div className="space-y-3">
                <h4 className="font-medium text-gray-900 flex items-center gap-2">
                  <HiOfficeBuilding className="w-5 h-5 text-gray-400" />
                  بيانات التوظيف
                </h4>
                <div className="bg-gray-50 rounded-lg p-4 space-y-2">
                  <div className="flex justify-between">
                    <span className="text-gray-500">القسم</span>
                    <span className="font-medium">{selectedEmployee.department?.name_ar || '-'}</span>
                  </div>
                  <div className="flex justify-between">
                    <span className="text-gray-500">المنصب</span>
                    <span className="font-medium">{selectedEmployee.position?.name_ar || '-'}</span>
                  </div>
                  <div className="flex justify-between">
                    <span className="text-gray-500">نوع العقد</span>
                    <Badge variant={CONTRACT_TYPES[selectedEmployee.contract_type]?.variant || 'gray'}>
                      {CONTRACT_TYPES[selectedEmployee.contract_type]?.label || selectedEmployee.contract_type}
                    </Badge>
                  </div>
                </div>
              </div>

              {/* Dates */}
              <div className="space-y-3">
                <h4 className="font-medium text-gray-900 flex items-center gap-2">
                  <HiCalendar className="w-5 h-5 text-gray-400" />
                  التواريخ
                </h4>
                <div className="bg-gray-50 rounded-lg p-4 space-y-2">
                  <div className="flex justify-between">
                    <span className="text-gray-500">تاريخ التعيين</span>
                    <span className="font-medium">{selectedEmployee.hire_date || '-'}</span>
                  </div>
                  <div className="flex justify-between">
                    <span className="text-gray-500">تاريخ الميلاد</span>
                    <span className="font-medium">{selectedEmployee.birth_date || '-'}</span>
                  </div>
                </div>
              </div>
            </div>

            <div className="flex justify-end gap-3 pt-4 border-t">
              <Button variant="secondary" onClick={() => setShowViewModal(false)}>
                إغلاق
              </Button>
              <Button
                icon={HiPencil}
                onClick={() => {
                  setShowViewModal(false);
                  handleEdit(selectedEmployee);
                }}
              >
                تعديل
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
            هل أنت متأكد من حذف الموظف{' '}
            <span className="font-bold text-gray-900">{selectedEmployee?.name_ar}</span>؟
          </p>
          <p className="text-sm text-red-600">
            تحذير: سيتم حذف جميع البيانات المرتبطة بهذا الموظف.
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
