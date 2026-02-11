import React, { useState, useEffect, useMemo } from 'react';
import { doctorsApi } from '../../services/financeApi';
import { Button, LoadingSpinner, Modal, EmptyState } from '../../components/ui';

/**
 * صفحة إدارة الأطباء
 * Doctors Management Page - Doctor profiles, commissions, and performance
 */
export default function DoctorsPage() {
  // State
  const [doctors, setDoctors] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [searchQuery, setSearchQuery] = useState('');
  const [filterSpecialty, setFilterSpecialty] = useState('all');
  const [filterStatus, setFilterStatus] = useState('all');

  // Modal states
  const [showFormModal, setShowFormModal] = useState(false);
  const [showDeleteModal, setShowDeleteModal] = useState(false);
  const [showPerformanceModal, setShowPerformanceModal] = useState(false);
  const [showCommissionModal, setShowCommissionModal] = useState(false);
  const [selectedDoctor, setSelectedDoctor] = useState(null);
  const [saving, setSaving] = useState(false);

  // Form state
  const [formData, setFormData] = useState({
    employee_number: '',
    name_ar: '',
    name_en: '',
    specialty: '',
    sub_specialty: '',
    license_number: '',
    license_expiry: '',
    phone: '',
    email: '',
    contract_type: 'full_time',
    commission_rate: '',
    fixed_salary: '',
    department_id: '',
    is_active: true
  });

  // Specialties
  const specialties = [
    { value: 'general', label: 'طب عام', label_en: 'General Practice' },
    { value: 'internal', label: 'باطنية', label_en: 'Internal Medicine' },
    { value: 'pediatrics', label: 'أطفال', label_en: 'Pediatrics' },
    { value: 'gynecology', label: 'نساء وولادة', label_en: 'OB/GYN' },
    { value: 'surgery', label: 'جراحة', label_en: 'Surgery' },
    { value: 'orthopedics', label: 'عظام', label_en: 'Orthopedics' },
    { value: 'cardiology', label: 'قلب', label_en: 'Cardiology' },
    { value: 'dermatology', label: 'جلدية', label_en: 'Dermatology' },
    { value: 'ent', label: 'أنف وأذن وحنجرة', label_en: 'ENT' },
    { value: 'ophthalmology', label: 'عيون', label_en: 'Ophthalmology' },
    { value: 'dentistry', label: 'أسنان', label_en: 'Dentistry' },
    { value: 'radiology', label: 'أشعة', label_en: 'Radiology' },
    { value: 'laboratory', label: 'مختبرات', label_en: 'Laboratory' }
  ];

  // Contract types
  const contractTypes = [
    { value: 'full_time', label: 'دوام كامل' },
    { value: 'part_time', label: 'دوام جزئي' },
    { value: 'percentage', label: 'نسبة من الإيرادات' },
    { value: 'locum', label: 'استشاري زائر' }
  ];

  // Mock data
  const mockDoctors = [
    {
      id: 1,
      employee_number: 'DR001',
      name_ar: 'د. أحمد محمد الشمري',
      name_en: 'Dr. Ahmed Mohammed Al-Shammari',
      specialty: 'internal',
      sub_specialty: 'أمراض الجهاز الهضمي',
      license_number: 'MOH-12345',
      license_expiry: '2025-12-31',
      phone: '0501234567',
      email: 'ahmed@hospital.com',
      contract_type: 'full_time',
      commission_rate: 30,
      fixed_salary: 25000,
      department_id: 1,
      is_active: true,
      total_revenue: 150000,
      total_commission: 45000,
      patients_count: 320,
      services_count: 450,
      avg_rating: 4.8
    },
    {
      id: 2,
      employee_number: 'DR002',
      name_ar: 'د. فاطمة علي الحربي',
      name_en: 'Dr. Fatima Ali Al-Harbi',
      specialty: 'pediatrics',
      sub_specialty: 'حديثي الولادة',
      license_number: 'MOH-23456',
      license_expiry: '2025-06-30',
      phone: '0502345678',
      email: 'fatima@hospital.com',
      contract_type: 'full_time',
      commission_rate: 25,
      fixed_salary: 22000,
      department_id: 2,
      is_active: true,
      total_revenue: 120000,
      total_commission: 30000,
      patients_count: 450,
      services_count: 520,
      avg_rating: 4.9
    },
    {
      id: 3,
      employee_number: 'DR003',
      name_ar: 'د. خالد سعود العتيبي',
      name_en: 'Dr. Khalid Saud Al-Otaibi',
      specialty: 'surgery',
      sub_specialty: 'جراحة عامة',
      license_number: 'MOH-34567',
      license_expiry: '2024-03-15',
      phone: '0503456789',
      email: 'khalid@hospital.com',
      contract_type: 'percentage',
      commission_rate: 40,
      fixed_salary: 0,
      department_id: 3,
      is_active: true,
      total_revenue: 280000,
      total_commission: 112000,
      patients_count: 180,
      services_count: 200,
      avg_rating: 4.7
    },
    {
      id: 4,
      employee_number: 'DR004',
      name_ar: 'د. نورة عبدالله القحطاني',
      name_en: 'Dr. Noura Abdullah Al-Qahtani',
      specialty: 'gynecology',
      sub_specialty: '',
      license_number: 'MOH-45678',
      license_expiry: '2025-09-20',
      phone: '0504567890',
      email: 'noura@hospital.com',
      contract_type: 'full_time',
      commission_rate: 28,
      fixed_salary: 24000,
      department_id: 4,
      is_active: true,
      total_revenue: 95000,
      total_commission: 26600,
      patients_count: 280,
      services_count: 310,
      avg_rating: 4.6
    },
    {
      id: 5,
      employee_number: 'DR005',
      name_ar: 'د. محمد عبدالرحمن السبيعي',
      name_en: 'Dr. Mohammed Abdulrahman Al-Subaie',
      specialty: 'cardiology',
      sub_specialty: 'قسطرة القلب',
      license_number: 'MOH-56789',
      license_expiry: '2024-01-10',
      phone: '0505678901',
      email: 'mohammed@hospital.com',
      contract_type: 'locum',
      commission_rate: 50,
      fixed_salary: 0,
      department_id: 5,
      is_active: false,
      total_revenue: 350000,
      total_commission: 175000,
      patients_count: 120,
      services_count: 150,
      avg_rating: 4.9
    }
  ];

  // Load doctors
  useEffect(() => {
    loadDoctors();
  }, []);

  const loadDoctors = async () => {
    try {
      setLoading(true);
      setError(null);
      // const response = await doctorsApi.getAll();
      // setDoctors(response.data);

      setTimeout(() => {
        setDoctors(mockDoctors);
        setLoading(false);
      }, 500);
    } catch (err) {
      setError('فشل في تحميل بيانات الأطباء');
      setLoading(false);
    }
  };

  // Filter doctors
  const filteredDoctors = useMemo(() => {
    return doctors.filter(doctor => {
      const matchesSearch = !searchQuery ||
        doctor.name_ar.toLowerCase().includes(searchQuery.toLowerCase()) ||
        doctor.name_en.toLowerCase().includes(searchQuery.toLowerCase()) ||
        doctor.employee_number.toLowerCase().includes(searchQuery.toLowerCase());
      const matchesSpecialty = filterSpecialty === 'all' || doctor.specialty === filterSpecialty;
      const matchesStatus = filterStatus === 'all' ||
        (filterStatus === 'active' && doctor.is_active) ||
        (filterStatus === 'inactive' && !doctor.is_active);

      return matchesSearch && matchesSpecialty && matchesStatus;
    });
  }, [doctors, searchQuery, filterSpecialty, filterStatus]);

  // Statistics
  const stats = useMemo(() => {
    const total = doctors.length;
    const active = doctors.filter(d => d.is_active).length;
    const totalRevenue = doctors.reduce((sum, d) => sum + (d.total_revenue || 0), 0);
    const totalCommission = doctors.reduce((sum, d) => sum + (d.total_commission || 0), 0);
    const expiringLicenses = doctors.filter(d => {
      const expiry = new Date(d.license_expiry);
      const today = new Date();
      const diffDays = Math.ceil((expiry - today) / (1000 * 60 * 60 * 24));
      return diffDays <= 90 && diffDays > 0;
    }).length;

    return { total, active, totalRevenue, totalCommission, expiringLicenses };
  }, [doctors]);

  // Handle form submit
  const handleSubmit = async (e) => {
    e.preventDefault();
    try {
      setSaving(true);

      if (selectedDoctor) {
        setTimeout(() => {
          setDoctors(doctors.map(d =>
            d.id === selectedDoctor.id
              ? { ...d, ...formData, commission_rate: parseFloat(formData.commission_rate) || 0 }
              : d
          ));
          closeFormModal();
        }, 500);
      } else {
        setTimeout(() => {
          const newDoctor = {
            id: doctors.length + 1,
            ...formData,
            commission_rate: parseFloat(formData.commission_rate) || 0,
            fixed_salary: parseFloat(formData.fixed_salary) || 0,
            total_revenue: 0,
            total_commission: 0,
            patients_count: 0,
            services_count: 0,
            avg_rating: 0
          };
          setDoctors([newDoctor, ...doctors]);
          closeFormModal();
        }, 500);
      }
    } catch (err) {
      setError('فشل في حفظ بيانات الطبيب');
      setSaving(false);
    }
  };

  // Handle delete
  const handleDelete = async () => {
    if (!selectedDoctor) return;

    try {
      setSaving(true);
      setTimeout(() => {
        setDoctors(doctors.filter(d => d.id !== selectedDoctor.id));
        setShowDeleteModal(false);
        setSelectedDoctor(null);
        setSaving(false);
      }, 500);
    } catch (err) {
      setError('فشل في حذف الطبيب');
      setSaving(false);
    }
  };

  // Open form modal
  const openFormModal = (doctor = null) => {
    if (doctor) {
      setSelectedDoctor(doctor);
      setFormData({
        employee_number: doctor.employee_number,
        name_ar: doctor.name_ar,
        name_en: doctor.name_en,
        specialty: doctor.specialty,
        sub_specialty: doctor.sub_specialty || '',
        license_number: doctor.license_number,
        license_expiry: doctor.license_expiry,
        phone: doctor.phone || '',
        email: doctor.email || '',
        contract_type: doctor.contract_type,
        commission_rate: doctor.commission_rate?.toString() || '',
        fixed_salary: doctor.fixed_salary?.toString() || '',
        department_id: doctor.department_id?.toString() || '',
        is_active: doctor.is_active
      });
    } else {
      setSelectedDoctor(null);
      setFormData({
        employee_number: '',
        name_ar: '',
        name_en: '',
        specialty: '',
        sub_specialty: '',
        license_number: '',
        license_expiry: '',
        phone: '',
        email: '',
        contract_type: 'full_time',
        commission_rate: '',
        fixed_salary: '',
        department_id: '',
        is_active: true
      });
    }
    setShowFormModal(true);
  };

  // Close form modal
  const closeFormModal = () => {
    setShowFormModal(false);
    setSelectedDoctor(null);
    setSaving(false);
  };

  // Get specialty label
  const getSpecialtyLabel = (specialty) => {
    return specialties.find(s => s.value === specialty)?.label || specialty;
  };

  // Get contract type label
  const getContractLabel = (type) => {
    return contractTypes.find(t => t.value === type)?.label || type;
  };

  // Check license status
  const getLicenseStatus = (expiryDate) => {
    const expiry = new Date(expiryDate);
    const today = new Date();
    const diffDays = Math.ceil((expiry - today) / (1000 * 60 * 60 * 24));

    if (diffDays < 0) return { label: 'منتهية', color: 'bg-red-100 text-red-800' };
    if (diffDays <= 30) return { label: 'تنتهي قريباً', color: 'bg-orange-100 text-orange-800' };
    if (diffDays <= 90) return { label: 'تحتاج تجديد', color: 'bg-yellow-100 text-yellow-800' };
    return { label: 'سارية', color: 'bg-green-100 text-green-800' };
  };

  // Format currency
  const formatCurrency = (amount) => {
    return new Intl.NumberFormat('ar-SA', {
      style: 'currency',
      currency: 'SAR',
      minimumFractionDigits: 0
    }).format(amount || 0);
  };

  if (loading && doctors.length === 0) {
    return (
      <div className="flex items-center justify-center min-h-[400px]">
        <LoadingSpinner size="lg" />
      </div>
    );
  }

  return (
    <div className="space-y-6" dir="rtl">
      {/* Header */}
      <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">إدارة الأطباء</h1>
          <p className="text-gray-600 mt-1">إدارة بيانات الأطباء والعمولات والأداء</p>
        </div>
        <div className="flex gap-2">
          <Button variant="secondary" onClick={() => {/* Export */}}>
            📥 تصدير
          </Button>
          <Button onClick={() => openFormModal()}>
            + إضافة طبيب
          </Button>
        </div>
      </div>

      {/* Statistics Cards */}
      <div className="grid grid-cols-2 md:grid-cols-5 gap-4">
        <div className="bg-white rounded-lg shadow p-4 text-center">
          <div className="text-3xl font-bold text-gray-900">{stats.total}</div>
          <div className="text-sm text-gray-600">إجمالي الأطباء</div>
        </div>
        <div className="bg-white rounded-lg shadow p-4 text-center">
          <div className="text-3xl font-bold text-green-600">{stats.active}</div>
          <div className="text-sm text-gray-600">أطباء نشطون</div>
        </div>
        <div className="bg-white rounded-lg shadow p-4 text-center">
          <div className="text-2xl font-bold text-blue-600">{formatCurrency(stats.totalRevenue)}</div>
          <div className="text-sm text-gray-600">إجمالي الإيرادات</div>
        </div>
        <div className="bg-white rounded-lg shadow p-4 text-center">
          <div className="text-2xl font-bold text-purple-600">{formatCurrency(stats.totalCommission)}</div>
          <div className="text-sm text-gray-600">إجمالي العمولات</div>
        </div>
        <div className="bg-white rounded-lg shadow p-4 text-center">
          <div className="text-3xl font-bold text-orange-600">{stats.expiringLicenses}</div>
          <div className="text-sm text-gray-600">رخص تنتهي قريباً</div>
        </div>
      </div>

      {/* Filters */}
      <div className="bg-white rounded-lg shadow p-4">
        <div className="flex flex-col md:flex-row gap-4">
          <input
            type="text"
            placeholder="بحث بالاسم أو الرقم الوظيفي..."
            value={searchQuery}
            onChange={(e) => setSearchQuery(e.target.value)}
            className="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
          />
          <select
            value={filterSpecialty}
            onChange={(e) => setFilterSpecialty(e.target.value)}
            className="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
          >
            <option value="all">جميع التخصصات</option>
            {specialties.map(spec => (
              <option key={spec.value} value={spec.value}>{spec.label}</option>
            ))}
          </select>
          <select
            value={filterStatus}
            onChange={(e) => setFilterStatus(e.target.value)}
            className="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
          >
            <option value="all">جميع الحالات</option>
            <option value="active">نشط</option>
            <option value="inactive">غير نشط</option>
          </select>
        </div>
      </div>

      {/* Error Message */}
      {error && (
        <div className="bg-red-50 text-red-600 p-4 rounded-lg">
          {error}
        </div>
      )}

      {/* Doctors Table */}
      <div className="bg-white rounded-lg shadow overflow-hidden">
        {filteredDoctors.length === 0 ? (
          <EmptyState
            title="لا يوجد أطباء"
            description="لم يتم العثور على أطباء مطابقين للبحث"
            icon="👨‍⚕️"
          />
        ) : (
          <div className="overflow-x-auto">
            <table className="min-w-full divide-y divide-gray-200">
              <thead className="bg-gray-50">
                <tr>
                  <th className="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">الطبيب</th>
                  <th className="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">التخصص</th>
                  <th className="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">نوع العقد</th>
                  <th className="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">الرخصة</th>
                  <th className="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">الإيرادات</th>
                  <th className="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">التقييم</th>
                  <th className="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">الحالة</th>
                  <th className="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">إجراءات</th>
                </tr>
              </thead>
              <tbody className="bg-white divide-y divide-gray-200">
                {filteredDoctors.map((doctor) => {
                  const licenseStatus = getLicenseStatus(doctor.license_expiry);
                  return (
                    <tr key={doctor.id} className="hover:bg-gray-50">
                      <td className="px-4 py-4 whitespace-nowrap">
                        <div className="flex items-center gap-3">
                          <div className="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-bold">
                            {doctor.name_ar.charAt(3)}
                          </div>
                          <div>
                            <div className="font-medium text-gray-900">{doctor.name_ar}</div>
                            <div className="text-sm text-gray-500">{doctor.employee_number}</div>
                          </div>
                        </div>
                      </td>
                      <td className="px-4 py-4 whitespace-nowrap">
                        <div>
                          <div className="font-medium text-gray-900">{getSpecialtyLabel(doctor.specialty)}</div>
                          {doctor.sub_specialty && (
                            <div className="text-sm text-gray-500">{doctor.sub_specialty}</div>
                          )}
                        </div>
                      </td>
                      <td className="px-4 py-4 whitespace-nowrap">
                        <div>
                          <div className="text-sm">{getContractLabel(doctor.contract_type)}</div>
                          <div className="text-xs text-gray-500">
                            {doctor.commission_rate}% عمولة
                          </div>
                        </div>
                      </td>
                      <td className="px-4 py-4 whitespace-nowrap">
                        <div>
                          <div className="text-sm">{doctor.license_number}</div>
                          <span className={`px-2 py-0.5 text-xs font-medium rounded-full ${licenseStatus.color}`}>
                            {licenseStatus.label}
                          </span>
                        </div>
                      </td>
                      <td className="px-4 py-4 whitespace-nowrap">
                        <div>
                          <div className="font-medium text-green-600">{formatCurrency(doctor.total_revenue)}</div>
                          <div className="text-xs text-gray-500">
                            عمولة: {formatCurrency(doctor.total_commission)}
                          </div>
                        </div>
                      </td>
                      <td className="px-4 py-4 whitespace-nowrap text-center">
                        <div className="flex items-center justify-center gap-1">
                          <span className="text-yellow-500">⭐</span>
                          <span className="font-medium">{doctor.avg_rating?.toFixed(1) || '-'}</span>
                        </div>
                        <div className="text-xs text-gray-500">{doctor.patients_count} مريض</div>
                      </td>
                      <td className="px-4 py-4 whitespace-nowrap text-center">
                        <span className={`px-2 py-1 text-xs font-medium rounded-full ${
                          doctor.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'
                        }`}>
                          {doctor.is_active ? 'نشط' : 'غير نشط'}
                        </span>
                      </td>
                      <td className="px-4 py-4 whitespace-nowrap text-center">
                        <div className="flex justify-center gap-2">
                          <Button
                            variant="ghost"
                            size="sm"
                            onClick={() => {
                              setSelectedDoctor(doctor);
                              setShowPerformanceModal(true);
                            }}
                          >
                            📊
                          </Button>
                          <Button
                            variant="ghost"
                            size="sm"
                            onClick={() => {
                              setSelectedDoctor(doctor);
                              setShowCommissionModal(true);
                            }}
                          >
                            💰
                          </Button>
                          <Button
                            variant="ghost"
                            size="sm"
                            onClick={() => openFormModal(doctor)}
                          >
                            ✏️
                          </Button>
                          <Button
                            variant="ghost"
                            size="sm"
                            onClick={() => {
                              setSelectedDoctor(doctor);
                              setShowDeleteModal(true);
                            }}
                          >
                            🗑️
                          </Button>
                        </div>
                      </td>
                    </tr>
                  );
                })}
              </tbody>
            </table>
          </div>
        )}
      </div>

      {/* Form Modal */}
      <Modal
        isOpen={showFormModal}
        onClose={closeFormModal}
        title={selectedDoctor ? 'تعديل بيانات الطبيب' : 'إضافة طبيب جديد'}
        size="lg"
      >
        <form onSubmit={handleSubmit} className="space-y-4">
          <div className="grid grid-cols-2 gap-4">
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">
                الرقم الوظيفي <span className="text-red-500">*</span>
              </label>
              <input
                type="text"
                value={formData.employee_number}
                onChange={(e) => setFormData({ ...formData, employee_number: e.target.value })}
                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                required
              />
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">
                التخصص <span className="text-red-500">*</span>
              </label>
              <select
                value={formData.specialty}
                onChange={(e) => setFormData({ ...formData, specialty: e.target.value })}
                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                required
              >
                <option value="">اختر التخصص</option>
                {specialties.map(spec => (
                  <option key={spec.value} value={spec.value}>{spec.label}</option>
                ))}
              </select>
            </div>
          </div>

          <div className="grid grid-cols-2 gap-4">
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">
                الاسم بالعربية <span className="text-red-500">*</span>
              </label>
              <input
                type="text"
                value={formData.name_ar}
                onChange={(e) => setFormData({ ...formData, name_ar: e.target.value })}
                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                required
              />
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">
                الاسم بالإنجليزية <span className="text-red-500">*</span>
              </label>
              <input
                type="text"
                value={formData.name_en}
                onChange={(e) => setFormData({ ...formData, name_en: e.target.value })}
                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                dir="ltr"
                required
              />
            </div>
          </div>

          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
              التخصص الدقيق
            </label>
            <input
              type="text"
              value={formData.sub_specialty}
              onChange={(e) => setFormData({ ...formData, sub_specialty: e.target.value })}
              className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
            />
          </div>

          <div className="grid grid-cols-2 gap-4">
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">
                رقم الرخصة <span className="text-red-500">*</span>
              </label>
              <input
                type="text"
                value={formData.license_number}
                onChange={(e) => setFormData({ ...formData, license_number: e.target.value })}
                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                required
              />
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">
                تاريخ انتهاء الرخصة <span className="text-red-500">*</span>
              </label>
              <input
                type="date"
                value={formData.license_expiry}
                onChange={(e) => setFormData({ ...formData, license_expiry: e.target.value })}
                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                required
              />
            </div>
          </div>

          <div className="grid grid-cols-2 gap-4">
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">
                الهاتف
              </label>
              <input
                type="tel"
                value={formData.phone}
                onChange={(e) => setFormData({ ...formData, phone: e.target.value })}
                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                dir="ltr"
              />
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">
                البريد الإلكتروني
              </label>
              <input
                type="email"
                value={formData.email}
                onChange={(e) => setFormData({ ...formData, email: e.target.value })}
                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                dir="ltr"
              />
            </div>
          </div>

          <div className="grid grid-cols-3 gap-4">
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">
                نوع العقد <span className="text-red-500">*</span>
              </label>
              <select
                value={formData.contract_type}
                onChange={(e) => setFormData({ ...formData, contract_type: e.target.value })}
                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                required
              >
                {contractTypes.map(type => (
                  <option key={type.value} value={type.value}>{type.label}</option>
                ))}
              </select>
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">
                نسبة العمولة %
              </label>
              <input
                type="number"
                value={formData.commission_rate}
                onChange={(e) => setFormData({ ...formData, commission_rate: e.target.value })}
                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                min="0"
                max="100"
              />
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">
                الراتب الثابت
              </label>
              <input
                type="number"
                value={formData.fixed_salary}
                onChange={(e) => setFormData({ ...formData, fixed_salary: e.target.value })}
                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                min="0"
              />
            </div>
          </div>

          <div>
            <label className="flex items-center gap-2">
              <input
                type="checkbox"
                checked={formData.is_active}
                onChange={(e) => setFormData({ ...formData, is_active: e.target.checked })}
                className="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
              />
              <span className="text-sm font-medium text-gray-700">طبيب نشط</span>
            </label>
          </div>

          <div className="flex justify-end gap-3 pt-4">
            <Button type="button" variant="secondary" onClick={closeFormModal}>
              إلغاء
            </Button>
            <Button type="submit" disabled={saving}>
              {saving ? 'جاري الحفظ...' : selectedDoctor ? 'تحديث' : 'إضافة'}
            </Button>
          </div>
        </form>
      </Modal>

      {/* Delete Confirmation Modal */}
      <Modal
        isOpen={showDeleteModal}
        onClose={() => setShowDeleteModal(false)}
        title="حذف الطبيب"
        size="sm"
      >
        <div className="space-y-4">
          <p className="text-gray-600">
            هل أنت متأكد من حذف الطبيب <strong>{selectedDoctor?.name_ar}</strong>؟
          </p>
          <div className="flex justify-end gap-3">
            <Button variant="secondary" onClick={() => setShowDeleteModal(false)}>
              إلغاء
            </Button>
            <Button variant="danger" onClick={handleDelete} disabled={saving}>
              {saving ? 'جاري الحذف...' : 'حذف'}
            </Button>
          </div>
        </div>
      </Modal>

      {/* Performance Modal */}
      <Modal
        isOpen={showPerformanceModal}
        onClose={() => setShowPerformanceModal(false)}
        title={`أداء ${selectedDoctor?.name_ar}`}
        size="lg"
      >
        {selectedDoctor && (
          <div className="space-y-6">
            <div className="grid grid-cols-4 gap-4">
              <div className="bg-blue-50 p-4 rounded-lg text-center">
                <div className="text-2xl font-bold text-blue-600">{selectedDoctor.patients_count}</div>
                <div className="text-sm text-gray-600">مريض</div>
              </div>
              <div className="bg-green-50 p-4 rounded-lg text-center">
                <div className="text-2xl font-bold text-green-600">{selectedDoctor.services_count}</div>
                <div className="text-sm text-gray-600">خدمة</div>
              </div>
              <div className="bg-purple-50 p-4 rounded-lg text-center">
                <div className="text-2xl font-bold text-purple-600">{formatCurrency(selectedDoctor.total_revenue)}</div>
                <div className="text-sm text-gray-600">إيرادات</div>
              </div>
              <div className="bg-yellow-50 p-4 rounded-lg text-center">
                <div className="text-2xl font-bold text-yellow-600">⭐ {selectedDoctor.avg_rating?.toFixed(1)}</div>
                <div className="text-sm text-gray-600">تقييم</div>
              </div>
            </div>

            <div className="flex justify-end">
              <Button variant="secondary" onClick={() => setShowPerformanceModal(false)}>
                إغلاق
              </Button>
            </div>
          </div>
        )}
      </Modal>

      {/* Commission Modal */}
      <Modal
        isOpen={showCommissionModal}
        onClose={() => setShowCommissionModal(false)}
        title={`عمولات ${selectedDoctor?.name_ar}`}
        size="lg"
      >
        {selectedDoctor && (
          <div className="space-y-6">
            <div className="grid grid-cols-3 gap-4">
              <div className="bg-green-50 p-4 rounded-lg text-center">
                <div className="text-2xl font-bold text-green-600">{formatCurrency(selectedDoctor.total_revenue)}</div>
                <div className="text-sm text-gray-600">إجمالي الإيرادات</div>
              </div>
              <div className="bg-blue-50 p-4 rounded-lg text-center">
                <div className="text-2xl font-bold text-blue-600">{selectedDoctor.commission_rate}%</div>
                <div className="text-sm text-gray-600">نسبة العمولة</div>
              </div>
              <div className="bg-purple-50 p-4 rounded-lg text-center">
                <div className="text-2xl font-bold text-purple-600">{formatCurrency(selectedDoctor.total_commission)}</div>
                <div className="text-sm text-gray-600">إجمالي العمولات</div>
              </div>
            </div>

            <div className="flex justify-end">
              <Button variant="secondary" onClick={() => setShowCommissionModal(false)}>
                إغلاق
              </Button>
            </div>
          </div>
        )}
      </Modal>
    </div>
  );
}
