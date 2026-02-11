import React, { useState, useEffect, useMemo } from 'react';
import { medicalServicesApi } from '../../services/financeApi';
import { Button, LoadingSpinner, Modal, EmptyState } from '../../components/ui';

/**
 * صفحة الخدمات الطبية
 * Medical Services Page - Service catalog with pricing management
 */
export default function MedicalServicesPage() {
  // State
  const [services, setServices] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [searchQuery, setSearchQuery] = useState('');
  const [filterCategory, setFilterCategory] = useState('all');
  const [filterStatus, setFilterStatus] = useState('all');

  // Modal states
  const [showFormModal, setShowFormModal] = useState(false);
  const [showDeleteModal, setShowDeleteModal] = useState(false);
  const [showPricingModal, setShowPricingModal] = useState(false);
  const [selectedService, setSelectedService] = useState(null);
  const [saving, setSaving] = useState(false);

  // Form state
  const [formData, setFormData] = useState({
    code: '',
    name_ar: '',
    name_en: '',
    category: '',
    description: '',
    base_price: '',
    cost: '',
    duration_minutes: '',
    requires_doctor: true,
    is_active: true
  });

  // Categories
  const categories = [
    { value: 'consultation', label: 'استشارة', label_en: 'Consultation' },
    { value: 'laboratory', label: 'مختبر', label_en: 'Laboratory' },
    { value: 'radiology', label: 'أشعة', label_en: 'Radiology' },
    { value: 'procedure', label: 'إجراء', label_en: 'Procedure' },
    { value: 'surgery', label: 'جراحة', label_en: 'Surgery' },
    { value: 'pharmacy', label: 'صيدلية', label_en: 'Pharmacy' },
    { value: 'nursing', label: 'تمريض', label_en: 'Nursing' },
    { value: 'dental', label: 'أسنان', label_en: 'Dental' },
    { value: 'physiotherapy', label: 'علاج طبيعي', label_en: 'Physiotherapy' },
    { value: 'other', label: 'أخرى', label_en: 'Other' }
  ];

  // Mock data
  const mockServices = [
    {
      id: 1,
      code: 'SRV001',
      name_ar: 'كشف طبيب باطنية',
      name_en: 'Internal Medicine Consultation',
      category: 'consultation',
      description: 'كشف أولي أو متابعة لدى طبيب الباطنية',
      base_price: 150,
      cost: 45,
      profit_margin: 70,
      duration_minutes: 20,
      requires_doctor: true,
      is_active: true,
      usage_count: 1250,
      total_revenue: 187500,
      insurance_prices: [
        { insurance_id: 1, insurance_name: 'التعاونية', price: 120 },
        { insurance_id: 2, insurance_name: 'بوبا', price: 130 }
      ]
    },
    {
      id: 2,
      code: 'SRV002',
      name_ar: 'تحليل CBC',
      name_en: 'Complete Blood Count',
      category: 'laboratory',
      description: 'تحليل صورة الدم الكاملة',
      base_price: 80,
      cost: 15,
      profit_margin: 81.25,
      duration_minutes: 30,
      requires_doctor: false,
      is_active: true,
      usage_count: 3500,
      total_revenue: 280000,
      insurance_prices: [
        { insurance_id: 1, insurance_name: 'التعاونية', price: 60 },
        { insurance_id: 2, insurance_name: 'بوبا', price: 65 }
      ]
    },
    {
      id: 3,
      code: 'SRV003',
      name_ar: 'أشعة صدر',
      name_en: 'Chest X-Ray',
      category: 'radiology',
      description: 'أشعة سينية للصدر',
      base_price: 200,
      cost: 50,
      profit_margin: 75,
      duration_minutes: 15,
      requires_doctor: false,
      is_active: true,
      usage_count: 850,
      total_revenue: 170000,
      insurance_prices: [
        { insurance_id: 1, insurance_name: 'التعاونية', price: 160 },
        { insurance_id: 2, insurance_name: 'بوبا', price: 170 }
      ]
    },
    {
      id: 4,
      code: 'SRV004',
      name_ar: 'جلسة علاج طبيعي',
      name_en: 'Physiotherapy Session',
      category: 'physiotherapy',
      description: 'جلسة علاج طبيعي كاملة',
      base_price: 180,
      cost: 40,
      profit_margin: 77.8,
      duration_minutes: 45,
      requires_doctor: false,
      is_active: true,
      usage_count: 620,
      total_revenue: 111600,
      insurance_prices: []
    },
    {
      id: 5,
      code: 'SRV005',
      name_ar: 'خلع ضرس',
      name_en: 'Tooth Extraction',
      category: 'dental',
      description: 'خلع ضرس بسيط',
      base_price: 250,
      cost: 60,
      profit_margin: 76,
      duration_minutes: 30,
      requires_doctor: true,
      is_active: true,
      usage_count: 380,
      total_revenue: 95000,
      insurance_prices: []
    },
    {
      id: 6,
      code: 'SRV006',
      name_ar: 'تخطيط قلب',
      name_en: 'ECG',
      category: 'procedure',
      description: 'تخطيط كهربائي للقلب',
      base_price: 120,
      cost: 25,
      profit_margin: 79.2,
      duration_minutes: 15,
      requires_doctor: false,
      is_active: false,
      usage_count: 450,
      total_revenue: 54000,
      insurance_prices: []
    }
  ];

  // Load services
  useEffect(() => {
    loadServices();
  }, []);

  const loadServices = async () => {
    try {
      setLoading(true);
      setError(null);

      setTimeout(() => {
        setServices(mockServices);
        setLoading(false);
      }, 500);
    } catch (err) {
      setError('فشل في تحميل الخدمات');
      setLoading(false);
    }
  };

  // Filter services
  const filteredServices = useMemo(() => {
    return services.filter(service => {
      const matchesSearch = !searchQuery ||
        service.name_ar.toLowerCase().includes(searchQuery.toLowerCase()) ||
        service.name_en.toLowerCase().includes(searchQuery.toLowerCase()) ||
        service.code.toLowerCase().includes(searchQuery.toLowerCase());
      const matchesCategory = filterCategory === 'all' || service.category === filterCategory;
      const matchesStatus = filterStatus === 'all' ||
        (filterStatus === 'active' && service.is_active) ||
        (filterStatus === 'inactive' && !service.is_active);

      return matchesSearch && matchesCategory && matchesStatus;
    });
  }, [services, searchQuery, filterCategory, filterStatus]);

  // Statistics
  const stats = useMemo(() => {
    const total = services.length;
    const active = services.filter(s => s.is_active).length;
    const totalRevenue = services.reduce((sum, s) => sum + (s.total_revenue || 0), 0);
    const avgMargin = services.reduce((sum, s) => sum + (s.profit_margin || 0), 0) / (services.length || 1);

    return { total, active, totalRevenue, avgMargin };
  }, [services]);

  // Handle form submit
  const handleSubmit = async (e) => {
    e.preventDefault();
    try {
      setSaving(true);

      if (selectedService) {
        setTimeout(() => {
          const basePrice = parseFloat(formData.base_price) || 0;
          const cost = parseFloat(formData.cost) || 0;
          const profitMargin = basePrice > 0 ? ((basePrice - cost) / basePrice) * 100 : 0;

          setServices(services.map(s =>
            s.id === selectedService.id
              ? { ...s, ...formData, base_price: basePrice, cost, profit_margin: profitMargin }
              : s
          ));
          closeFormModal();
        }, 500);
      } else {
        setTimeout(() => {
          const basePrice = parseFloat(formData.base_price) || 0;
          const cost = parseFloat(formData.cost) || 0;
          const profitMargin = basePrice > 0 ? ((basePrice - cost) / basePrice) * 100 : 0;

          const newService = {
            id: services.length + 1,
            ...formData,
            base_price: basePrice,
            cost,
            profit_margin: profitMargin,
            duration_minutes: parseInt(formData.duration_minutes) || 0,
            usage_count: 0,
            total_revenue: 0,
            insurance_prices: []
          };
          setServices([newService, ...services]);
          closeFormModal();
        }, 500);
      }
    } catch (err) {
      setError('فشل في حفظ الخدمة');
      setSaving(false);
    }
  };

  // Handle delete
  const handleDelete = async () => {
    if (!selectedService) return;

    try {
      setSaving(true);
      setTimeout(() => {
        setServices(services.filter(s => s.id !== selectedService.id));
        setShowDeleteModal(false);
        setSelectedService(null);
        setSaving(false);
      }, 500);
    } catch (err) {
      setError('فشل في حذف الخدمة');
      setSaving(false);
    }
  };

  // Open form modal
  const openFormModal = (service = null) => {
    if (service) {
      setSelectedService(service);
      setFormData({
        code: service.code,
        name_ar: service.name_ar,
        name_en: service.name_en,
        category: service.category,
        description: service.description || '',
        base_price: service.base_price?.toString() || '',
        cost: service.cost?.toString() || '',
        duration_minutes: service.duration_minutes?.toString() || '',
        requires_doctor: service.requires_doctor,
        is_active: service.is_active
      });
    } else {
      setSelectedService(null);
      setFormData({
        code: '',
        name_ar: '',
        name_en: '',
        category: '',
        description: '',
        base_price: '',
        cost: '',
        duration_minutes: '',
        requires_doctor: true,
        is_active: true
      });
    }
    setShowFormModal(true);
  };

  // Close form modal
  const closeFormModal = () => {
    setShowFormModal(false);
    setSelectedService(null);
    setSaving(false);
  };

  // Get category label
  const getCategoryLabel = (category) => {
    return categories.find(c => c.value === category)?.label || category;
  };

  // Get profit margin color
  const getProfitMarginColor = (margin) => {
    if (margin >= 70) return 'text-green-600 bg-green-100';
    if (margin >= 50) return 'text-yellow-600 bg-yellow-100';
    return 'text-red-600 bg-red-100';
  };

  // Format currency
  const formatCurrency = (amount) => {
    return new Intl.NumberFormat('ar-SA', {
      style: 'currency',
      currency: 'SAR',
      minimumFractionDigits: 0
    }).format(amount || 0);
  };

  if (loading && services.length === 0) {
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
          <h1 className="text-2xl font-bold text-gray-900">الخدمات الطبية</h1>
          <p className="text-gray-600 mt-1">إدارة الخدمات والأسعار وتحليل الربحية</p>
        </div>
        <div className="flex gap-2">
          <Button variant="secondary" onClick={() => {/* Export */}}>
            📥 تصدير
          </Button>
          <Button onClick={() => openFormModal()}>
            + إضافة خدمة
          </Button>
        </div>
      </div>

      {/* Statistics Cards */}
      <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div className="bg-white rounded-lg shadow p-4 text-center">
          <div className="text-3xl font-bold text-gray-900">{stats.total}</div>
          <div className="text-sm text-gray-600">إجمالي الخدمات</div>
        </div>
        <div className="bg-white rounded-lg shadow p-4 text-center">
          <div className="text-3xl font-bold text-green-600">{stats.active}</div>
          <div className="text-sm text-gray-600">خدمات نشطة</div>
        </div>
        <div className="bg-white rounded-lg shadow p-4 text-center">
          <div className="text-2xl font-bold text-blue-600">{formatCurrency(stats.totalRevenue)}</div>
          <div className="text-sm text-gray-600">إجمالي الإيرادات</div>
        </div>
        <div className="bg-white rounded-lg shadow p-4 text-center">
          <div className="text-3xl font-bold text-purple-600">{stats.avgMargin.toFixed(1)}%</div>
          <div className="text-sm text-gray-600">متوسط هامش الربح</div>
        </div>
      </div>

      {/* Filters */}
      <div className="bg-white rounded-lg shadow p-4">
        <div className="flex flex-col md:flex-row gap-4">
          <input
            type="text"
            placeholder="بحث بالاسم أو الكود..."
            value={searchQuery}
            onChange={(e) => setSearchQuery(e.target.value)}
            className="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
          />
          <select
            value={filterCategory}
            onChange={(e) => setFilterCategory(e.target.value)}
            className="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
          >
            <option value="all">جميع الفئات</option>
            {categories.map(cat => (
              <option key={cat.value} value={cat.value}>{cat.label}</option>
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

      {/* Services Table */}
      <div className="bg-white rounded-lg shadow overflow-hidden">
        {filteredServices.length === 0 ? (
          <EmptyState
            title="لا توجد خدمات"
            description="لم يتم العثور على خدمات مطابقة للبحث"
            icon="🏥"
          />
        ) : (
          <div className="overflow-x-auto">
            <table className="min-w-full divide-y divide-gray-200">
              <thead className="bg-gray-50">
                <tr>
                  <th className="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">الكود</th>
                  <th className="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">الخدمة</th>
                  <th className="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">الفئة</th>
                  <th className="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">السعر</th>
                  <th className="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">التكلفة</th>
                  <th className="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">هامش الربح</th>
                  <th className="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">الاستخدام</th>
                  <th className="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">الحالة</th>
                  <th className="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">إجراءات</th>
                </tr>
              </thead>
              <tbody className="bg-white divide-y divide-gray-200">
                {filteredServices.map((service) => (
                  <tr key={service.id} className="hover:bg-gray-50">
                    <td className="px-4 py-4 whitespace-nowrap">
                      <span className="font-mono text-sm bg-gray-100 px-2 py-1 rounded">
                        {service.code}
                      </span>
                    </td>
                    <td className="px-4 py-4 whitespace-nowrap">
                      <div>
                        <div className="font-medium text-gray-900">{service.name_ar}</div>
                        <div className="text-sm text-gray-500">{service.name_en}</div>
                      </div>
                    </td>
                    <td className="px-4 py-4 whitespace-nowrap">
                      <span className="px-2 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800">
                        {getCategoryLabel(service.category)}
                      </span>
                    </td>
                    <td className="px-4 py-4 whitespace-nowrap">
                      <div className="font-medium text-green-600">{formatCurrency(service.base_price)}</div>
                      {service.insurance_prices?.length > 0 && (
                        <div className="text-xs text-gray-500">
                          {service.insurance_prices.length} أسعار تأمين
                        </div>
                      )}
                    </td>
                    <td className="px-4 py-4 whitespace-nowrap text-sm">
                      {formatCurrency(service.cost)}
                    </td>
                    <td className="px-4 py-4 whitespace-nowrap text-center">
                      <span className={`px-2 py-1 text-xs font-medium rounded-full ${getProfitMarginColor(service.profit_margin)}`}>
                        {service.profit_margin?.toFixed(1)}%
                      </span>
                    </td>
                    <td className="px-4 py-4 whitespace-nowrap text-center">
                      <div className="text-sm">{service.usage_count?.toLocaleString()}</div>
                      <div className="text-xs text-gray-500">{formatCurrency(service.total_revenue)}</div>
                    </td>
                    <td className="px-4 py-4 whitespace-nowrap text-center">
                      <span className={`px-2 py-1 text-xs font-medium rounded-full ${
                        service.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'
                      }`}>
                        {service.is_active ? 'نشط' : 'غير نشط'}
                      </span>
                    </td>
                    <td className="px-4 py-4 whitespace-nowrap text-center">
                      <div className="flex justify-center gap-2">
                        <Button
                          variant="ghost"
                          size="sm"
                          onClick={() => {
                            setSelectedService(service);
                            setShowPricingModal(true);
                          }}
                        >
                          💰
                        </Button>
                        <Button
                          variant="ghost"
                          size="sm"
                          onClick={() => openFormModal(service)}
                        >
                          ✏️
                        </Button>
                        <Button
                          variant="ghost"
                          size="sm"
                          onClick={() => {
                            setSelectedService(service);
                            setShowDeleteModal(true);
                          }}
                        >
                          🗑️
                        </Button>
                      </div>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </div>

      {/* Form Modal */}
      <Modal
        isOpen={showFormModal}
        onClose={closeFormModal}
        title={selectedService ? 'تعديل الخدمة' : 'إضافة خدمة جديدة'}
        size="lg"
      >
        <form onSubmit={handleSubmit} className="space-y-4">
          <div className="grid grid-cols-2 gap-4">
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">
                كود الخدمة <span className="text-red-500">*</span>
              </label>
              <input
                type="text"
                value={formData.code}
                onChange={(e) => setFormData({ ...formData, code: e.target.value })}
                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                required
              />
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">
                الفئة <span className="text-red-500">*</span>
              </label>
              <select
                value={formData.category}
                onChange={(e) => setFormData({ ...formData, category: e.target.value })}
                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                required
              >
                <option value="">اختر الفئة</option>
                {categories.map(cat => (
                  <option key={cat.value} value={cat.value}>{cat.label}</option>
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
              الوصف
            </label>
            <textarea
              value={formData.description}
              onChange={(e) => setFormData({ ...formData, description: e.target.value })}
              rows={2}
              className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
            />
          </div>

          <div className="grid grid-cols-3 gap-4">
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">
                السعر الأساسي <span className="text-red-500">*</span>
              </label>
              <input
                type="number"
                value={formData.base_price}
                onChange={(e) => setFormData({ ...formData, base_price: e.target.value })}
                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                min="0"
                step="0.01"
                required
              />
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">
                التكلفة
              </label>
              <input
                type="number"
                value={formData.cost}
                onChange={(e) => setFormData({ ...formData, cost: e.target.value })}
                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                min="0"
                step="0.01"
              />
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">
                المدة (دقيقة)
              </label>
              <input
                type="number"
                value={formData.duration_minutes}
                onChange={(e) => setFormData({ ...formData, duration_minutes: e.target.value })}
                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                min="0"
              />
            </div>
          </div>

          <div className="flex gap-4">
            <label className="flex items-center gap-2">
              <input
                type="checkbox"
                checked={formData.requires_doctor}
                onChange={(e) => setFormData({ ...formData, requires_doctor: e.target.checked })}
                className="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
              />
              <span className="text-sm font-medium text-gray-700">تتطلب طبيب</span>
            </label>
            <label className="flex items-center gap-2">
              <input
                type="checkbox"
                checked={formData.is_active}
                onChange={(e) => setFormData({ ...formData, is_active: e.target.checked })}
                className="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
              />
              <span className="text-sm font-medium text-gray-700">خدمة نشطة</span>
            </label>
          </div>

          <div className="flex justify-end gap-3 pt-4">
            <Button type="button" variant="secondary" onClick={closeFormModal}>
              إلغاء
            </Button>
            <Button type="submit" disabled={saving}>
              {saving ? 'جاري الحفظ...' : selectedService ? 'تحديث' : 'إضافة'}
            </Button>
          </div>
        </form>
      </Modal>

      {/* Delete Confirmation Modal */}
      <Modal
        isOpen={showDeleteModal}
        onClose={() => setShowDeleteModal(false)}
        title="حذف الخدمة"
        size="sm"
      >
        <div className="space-y-4">
          <p className="text-gray-600">
            هل أنت متأكد من حذف الخدمة <strong>{selectedService?.name_ar}</strong>؟
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

      {/* Pricing Modal */}
      <Modal
        isOpen={showPricingModal}
        onClose={() => setShowPricingModal(false)}
        title={`تسعير ${selectedService?.name_ar}`}
        size="lg"
      >
        {selectedService && (
          <div className="space-y-6">
            {/* Base Price */}
            <div className="bg-blue-50 p-4 rounded-lg">
              <div className="flex justify-between items-center">
                <div>
                  <div className="text-sm text-gray-600">السعر الأساسي</div>
                  <div className="text-2xl font-bold text-blue-600">{formatCurrency(selectedService.base_price)}</div>
                </div>
                <div className="text-left">
                  <div className="text-sm text-gray-600">التكلفة</div>
                  <div className="text-lg font-medium">{formatCurrency(selectedService.cost)}</div>
                </div>
                <div className="text-left">
                  <div className="text-sm text-gray-600">هامش الربح</div>
                  <div className={`text-lg font-bold ${selectedService.profit_margin >= 70 ? 'text-green-600' : 'text-orange-600'}`}>
                    {selectedService.profit_margin?.toFixed(1)}%
                  </div>
                </div>
              </div>
            </div>

            {/* Insurance Prices */}
            <div>
              <h4 className="font-medium text-gray-900 mb-3">أسعار التأمين</h4>
              {selectedService.insurance_prices?.length > 0 ? (
                <div className="space-y-2">
                  {selectedService.insurance_prices.map((ip, index) => (
                    <div key={index} className="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                      <span className="font-medium">{ip.insurance_name}</span>
                      <span className="text-green-600 font-medium">{formatCurrency(ip.price)}</span>
                    </div>
                  ))}
                </div>
              ) : (
                <div className="text-center text-gray-500 py-4">
                  لا توجد أسعار تأمين محددة
                </div>
              )}
            </div>

            <div className="flex justify-end">
              <Button variant="secondary" onClick={() => setShowPricingModal(false)}>
                إغلاق
              </Button>
            </div>
          </div>
        )}
      </Modal>
    </div>
  );
}
