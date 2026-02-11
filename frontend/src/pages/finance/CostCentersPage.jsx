import React, { useState, useEffect, useMemo } from 'react';
import { costCentersApi } from '../../services/financeApi';
import { Button, LoadingSpinner, Modal, EmptyState } from '../../components/ui';

/**
 * صفحة مراكز التكلفة
 * Cost Centers Page - ABC Costing and cost allocation management
 */
export default function CostCentersPage() {
  // State
  const [costCenters, setCostCenters] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [searchQuery, setSearchQuery] = useState('');
  const [filterType, setFilterType] = useState('all');
  const [filterStatus, setFilterStatus] = useState('all');
  const [viewMode, setViewMode] = useState('list'); // list, hierarchy

  // Modal states
  const [showFormModal, setShowFormModal] = useState(false);
  const [showDeleteModal, setShowDeleteModal] = useState(false);
  const [showBudgetModal, setShowBudgetModal] = useState(false);
  const [selectedCenter, setSelectedCenter] = useState(null);
  const [saving, setSaving] = useState(false);

  // Form state
  const [formData, setFormData] = useState({
    code: '',
    name_ar: '',
    name_en: '',
    type: 'department',
    parent_id: null,
    description: '',
    budget_year: new Date().getFullYear(),
    annual_budget: '',
    is_active: true
  });

  // Cost center types
  const centerTypes = [
    { value: 'department', label: 'قسم', label_en: 'Department' },
    { value: 'clinic', label: 'عيادة', label_en: 'Clinic' },
    { value: 'laboratory', label: 'مختبر', label_en: 'Laboratory' },
    { value: 'radiology', label: 'أشعة', label_en: 'Radiology' },
    { value: 'pharmacy', label: 'صيدلية', label_en: 'Pharmacy' },
    { value: 'administrative', label: 'إداري', label_en: 'Administrative' },
    { value: 'support', label: 'دعم', label_en: 'Support' }
  ];

  // Mock data
  const mockCostCenters = [
    {
      id: 1,
      code: 'CC001',
      name_ar: 'العيادات الخارجية',
      name_en: 'Outpatient Clinics',
      type: 'clinic',
      parent_id: null,
      description: 'مركز تكلفة العيادات الخارجية',
      annual_budget: 500000,
      spent_budget: 320000,
      remaining_budget: 180000,
      budget_utilization: 64,
      is_active: true,
      children_count: 5,
      created_at: '2024-01-01'
    },
    {
      id: 2,
      code: 'CC002',
      name_ar: 'المختبر',
      name_en: 'Laboratory',
      type: 'laboratory',
      parent_id: null,
      description: 'مركز تكلفة المختبر',
      annual_budget: 300000,
      spent_budget: 280000,
      remaining_budget: 20000,
      budget_utilization: 93,
      is_active: true,
      children_count: 0,
      created_at: '2024-01-01'
    },
    {
      id: 3,
      code: 'CC003',
      name_ar: 'الأشعة',
      name_en: 'Radiology',
      type: 'radiology',
      parent_id: null,
      description: 'مركز تكلفة قسم الأشعة',
      annual_budget: 400000,
      spent_budget: 150000,
      remaining_budget: 250000,
      budget_utilization: 37.5,
      is_active: true,
      children_count: 2,
      created_at: '2024-01-01'
    },
    {
      id: 4,
      code: 'CC004',
      name_ar: 'الصيدلية',
      name_en: 'Pharmacy',
      type: 'pharmacy',
      parent_id: null,
      description: 'مركز تكلفة الصيدلية',
      annual_budget: 200000,
      spent_budget: 180000,
      remaining_budget: 20000,
      budget_utilization: 90,
      is_active: true,
      children_count: 0,
      created_at: '2024-01-01'
    },
    {
      id: 5,
      code: 'CC005',
      name_ar: 'الإدارة العامة',
      name_en: 'General Administration',
      type: 'administrative',
      parent_id: null,
      description: 'مركز تكلفة الإدارة',
      annual_budget: 150000,
      spent_budget: 100000,
      remaining_budget: 50000,
      budget_utilization: 66.7,
      is_active: true,
      children_count: 3,
      created_at: '2024-01-01'
    },
    {
      id: 6,
      code: 'CC006',
      name_ar: 'التمريض',
      name_en: 'Nursing',
      type: 'department',
      parent_id: null,
      description: 'مركز تكلفة قسم التمريض',
      annual_budget: 600000,
      spent_budget: 450000,
      remaining_budget: 150000,
      budget_utilization: 75,
      is_active: true,
      children_count: 0,
      created_at: '2024-01-01'
    },
    {
      id: 7,
      code: 'CC007',
      name_ar: 'الصيانة',
      name_en: 'Maintenance',
      type: 'support',
      parent_id: null,
      description: 'مركز تكلفة الصيانة والدعم الفني',
      annual_budget: 100000,
      spent_budget: 85000,
      remaining_budget: 15000,
      budget_utilization: 85,
      is_active: false,
      children_count: 0,
      created_at: '2024-01-01'
    }
  ];

  // Load cost centers
  useEffect(() => {
    loadCostCenters();
  }, []);

  const loadCostCenters = async () => {
    try {
      setLoading(true);
      setError(null);
      // const response = await costCentersApi.getAll();
      // setCostCenters(response.data);

      // Using mock data
      setTimeout(() => {
        setCostCenters(mockCostCenters);
        setLoading(false);
      }, 500);
    } catch (err) {
      setError('فشل في تحميل مراكز التكلفة');
      setLoading(false);
    }
  };

  // Filter cost centers
  const filteredCenters = useMemo(() => {
    return costCenters.filter(center => {
      const matchesSearch = !searchQuery ||
        center.name_ar.toLowerCase().includes(searchQuery.toLowerCase()) ||
        center.name_en.toLowerCase().includes(searchQuery.toLowerCase()) ||
        center.code.toLowerCase().includes(searchQuery.toLowerCase());
      const matchesType = filterType === 'all' || center.type === filterType;
      const matchesStatus = filterStatus === 'all' ||
        (filterStatus === 'active' && center.is_active) ||
        (filterStatus === 'inactive' && !center.is_active);

      return matchesSearch && matchesType && matchesStatus;
    });
  }, [costCenters, searchQuery, filterType, filterStatus]);

  // Statistics
  const stats = useMemo(() => {
    const total = costCenters.length;
    const active = costCenters.filter(c => c.is_active).length;
    const totalBudget = costCenters.reduce((sum, c) => sum + (c.annual_budget || 0), 0);
    const totalSpent = costCenters.reduce((sum, c) => sum + (c.spent_budget || 0), 0);
    const overBudget = costCenters.filter(c => c.budget_utilization > 90).length;

    return { total, active, totalBudget, totalSpent, overBudget };
  }, [costCenters]);

  // Handle form submit
  const handleSubmit = async (e) => {
    e.preventDefault();
    try {
      setSaving(true);

      if (selectedCenter) {
        // Update
        // await costCentersApi.update(selectedCenter.id, formData);
        setTimeout(() => {
          setCostCenters(costCenters.map(c =>
            c.id === selectedCenter.id
              ? { ...c, ...formData, annual_budget: parseFloat(formData.annual_budget) || 0 }
              : c
          ));
          closeFormModal();
        }, 500);
      } else {
        // Create
        // await costCentersApi.create(formData);
        setTimeout(() => {
          const newCenter = {
            id: costCenters.length + 1,
            ...formData,
            annual_budget: parseFloat(formData.annual_budget) || 0,
            spent_budget: 0,
            remaining_budget: parseFloat(formData.annual_budget) || 0,
            budget_utilization: 0,
            children_count: 0,
            created_at: new Date().toISOString()
          };
          setCostCenters([newCenter, ...costCenters]);
          closeFormModal();
        }, 500);
      }
    } catch (err) {
      setError('فشل في حفظ مركز التكلفة');
      setSaving(false);
    }
  };

  // Handle delete
  const handleDelete = async () => {
    if (!selectedCenter) return;

    try {
      setSaving(true);
      // await costCentersApi.delete(selectedCenter.id);

      setTimeout(() => {
        setCostCenters(costCenters.filter(c => c.id !== selectedCenter.id));
        setShowDeleteModal(false);
        setSelectedCenter(null);
        setSaving(false);
      }, 500);
    } catch (err) {
      setError('فشل في حذف مركز التكلفة');
      setSaving(false);
    }
  };

  // Open form modal
  const openFormModal = (center = null) => {
    if (center) {
      setSelectedCenter(center);
      setFormData({
        code: center.code,
        name_ar: center.name_ar,
        name_en: center.name_en,
        type: center.type,
        parent_id: center.parent_id,
        description: center.description || '',
        budget_year: new Date().getFullYear(),
        annual_budget: center.annual_budget?.toString() || '',
        is_active: center.is_active
      });
    } else {
      setSelectedCenter(null);
      setFormData({
        code: '',
        name_ar: '',
        name_en: '',
        type: 'department',
        parent_id: null,
        description: '',
        budget_year: new Date().getFullYear(),
        annual_budget: '',
        is_active: true
      });
    }
    setShowFormModal(true);
  };

  // Close form modal
  const closeFormModal = () => {
    setShowFormModal(false);
    setSelectedCenter(null);
    setSaving(false);
  };

  // Get type label
  const getTypeLabel = (type) => {
    return centerTypes.find(t => t.value === type)?.label || type;
  };

  // Get budget status color
  const getBudgetStatusColor = (utilization) => {
    if (utilization >= 90) return 'text-red-600 bg-red-100';
    if (utilization >= 70) return 'text-orange-600 bg-orange-100';
    return 'text-green-600 bg-green-100';
  };

  // Format currency
  const formatCurrency = (amount) => {
    return new Intl.NumberFormat('ar-SA', {
      style: 'currency',
      currency: 'SAR',
      minimumFractionDigits: 0
    }).format(amount || 0);
  };

  if (loading && costCenters.length === 0) {
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
          <h1 className="text-2xl font-bold text-gray-900">مراكز التكلفة</h1>
          <p className="text-gray-600 mt-1">إدارة مراكز التكلفة وتوزيع الميزانيات (ABC Costing)</p>
        </div>
        <div className="flex gap-2">
          <Button variant="secondary" onClick={() => {/* Export */}}>
            📥 تصدير
          </Button>
          <Button onClick={() => openFormModal()}>
            + إضافة مركز
          </Button>
        </div>
      </div>

      {/* Statistics Cards */}
      <div className="grid grid-cols-2 md:grid-cols-5 gap-4">
        <div className="bg-white rounded-lg shadow p-4 text-center">
          <div className="text-3xl font-bold text-gray-900">{stats.total}</div>
          <div className="text-sm text-gray-600">إجمالي المراكز</div>
        </div>
        <div className="bg-white rounded-lg shadow p-4 text-center">
          <div className="text-3xl font-bold text-green-600">{stats.active}</div>
          <div className="text-sm text-gray-600">مراكز نشطة</div>
        </div>
        <div className="bg-white rounded-lg shadow p-4 text-center">
          <div className="text-2xl font-bold text-blue-600">{formatCurrency(stats.totalBudget)}</div>
          <div className="text-sm text-gray-600">إجمالي الميزانية</div>
        </div>
        <div className="bg-white rounded-lg shadow p-4 text-center">
          <div className="text-2xl font-bold text-purple-600">{formatCurrency(stats.totalSpent)}</div>
          <div className="text-sm text-gray-600">إجمالي المصروف</div>
        </div>
        <div className="bg-white rounded-lg shadow p-4 text-center">
          <div className="text-3xl font-bold text-red-600">{stats.overBudget}</div>
          <div className="text-sm text-gray-600">تجاوز الميزانية</div>
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
            value={filterType}
            onChange={(e) => setFilterType(e.target.value)}
            className="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
          >
            <option value="all">جميع الأنواع</option>
            {centerTypes.map(type => (
              <option key={type.value} value={type.value}>{type.label}</option>
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
          <div className="flex gap-2">
            <Button
              variant={viewMode === 'list' ? 'primary' : 'secondary'}
              size="sm"
              onClick={() => setViewMode('list')}
            >
              قائمة
            </Button>
            <Button
              variant={viewMode === 'hierarchy' ? 'primary' : 'secondary'}
              size="sm"
              onClick={() => setViewMode('hierarchy')}
            >
              هرمي
            </Button>
          </div>
        </div>
      </div>

      {/* Error Message */}
      {error && (
        <div className="bg-red-50 text-red-600 p-4 rounded-lg">
          {error}
        </div>
      )}

      {/* Cost Centers Table */}
      <div className="bg-white rounded-lg shadow overflow-hidden">
        {filteredCenters.length === 0 ? (
          <EmptyState
            title="لا توجد مراكز تكلفة"
            description="لم يتم العثور على مراكز تكلفة مطابقة للبحث"
            icon="📊"
          />
        ) : (
          <div className="overflow-x-auto">
            <table className="min-w-full divide-y divide-gray-200">
              <thead className="bg-gray-50">
                <tr>
                  <th className="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">الكود</th>
                  <th className="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">الاسم</th>
                  <th className="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">النوع</th>
                  <th className="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">الميزانية السنوية</th>
                  <th className="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">المصروف</th>
                  <th className="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">نسبة الاستخدام</th>
                  <th className="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">الحالة</th>
                  <th className="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">إجراءات</th>
                </tr>
              </thead>
              <tbody className="bg-white divide-y divide-gray-200">
                {filteredCenters.map((center) => (
                  <tr key={center.id} className="hover:bg-gray-50">
                    <td className="px-4 py-4 whitespace-nowrap">
                      <span className="font-mono text-sm bg-gray-100 px-2 py-1 rounded">
                        {center.code}
                      </span>
                    </td>
                    <td className="px-4 py-4 whitespace-nowrap">
                      <div>
                        <div className="font-medium text-gray-900">{center.name_ar}</div>
                        <div className="text-sm text-gray-500">{center.name_en}</div>
                      </div>
                    </td>
                    <td className="px-4 py-4 whitespace-nowrap">
                      <span className="px-2 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800">
                        {getTypeLabel(center.type)}
                      </span>
                    </td>
                    <td className="px-4 py-4 whitespace-nowrap text-sm">
                      {formatCurrency(center.annual_budget)}
                    </td>
                    <td className="px-4 py-4 whitespace-nowrap text-sm">
                      <div>{formatCurrency(center.spent_budget)}</div>
                      <div className="text-xs text-gray-500">
                        متبقي: {formatCurrency(center.remaining_budget)}
                      </div>
                    </td>
                    <td className="px-4 py-4 whitespace-nowrap text-center">
                      <div className="flex flex-col items-center">
                        <span className={`px-2 py-1 text-xs font-medium rounded-full ${getBudgetStatusColor(center.budget_utilization)}`}>
                          {center.budget_utilization?.toFixed(1)}%
                        </span>
                        <div className="w-20 h-2 bg-gray-200 rounded-full mt-1">
                          <div
                            className={`h-full rounded-full ${
                              center.budget_utilization >= 90 ? 'bg-red-500' :
                              center.budget_utilization >= 70 ? 'bg-orange-500' : 'bg-green-500'
                            }`}
                            style={{ width: `${Math.min(center.budget_utilization, 100)}%` }}
                          />
                        </div>
                      </div>
                    </td>
                    <td className="px-4 py-4 whitespace-nowrap text-center">
                      <span className={`px-2 py-1 text-xs font-medium rounded-full ${
                        center.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'
                      }`}>
                        {center.is_active ? 'نشط' : 'غير نشط'}
                      </span>
                    </td>
                    <td className="px-4 py-4 whitespace-nowrap text-center">
                      <div className="flex justify-center gap-2">
                        <Button
                          variant="ghost"
                          size="sm"
                          onClick={() => {
                            setSelectedCenter(center);
                            setShowBudgetModal(true);
                          }}
                        >
                          📊
                        </Button>
                        <Button
                          variant="ghost"
                          size="sm"
                          onClick={() => openFormModal(center)}
                        >
                          ✏️
                        </Button>
                        <Button
                          variant="ghost"
                          size="sm"
                          onClick={() => {
                            setSelectedCenter(center);
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
        title={selectedCenter ? 'تعديل مركز التكلفة' : 'إضافة مركز تكلفة جديد'}
        size="lg"
      >
        <form onSubmit={handleSubmit} className="space-y-4">
          <div className="grid grid-cols-2 gap-4">
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">
                الكود <span className="text-red-500">*</span>
              </label>
              <input
                type="text"
                value={formData.code}
                onChange={(e) => setFormData({ ...formData, code: e.target.value })}
                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                placeholder="CC001"
                required
              />
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">
                النوع <span className="text-red-500">*</span>
              </label>
              <select
                value={formData.type}
                onChange={(e) => setFormData({ ...formData, type: e.target.value })}
                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                required
              >
                {centerTypes.map(type => (
                  <option key={type.value} value={type.value}>{type.label}</option>
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
                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
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
                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                dir="ltr"
                required
              />
            </div>
          </div>

          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
              مركز التكلفة الأب
            </label>
            <select
              value={formData.parent_id || ''}
              onChange={(e) => setFormData({ ...formData, parent_id: e.target.value || null })}
              className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            >
              <option value="">بدون (مركز رئيسي)</option>
              {costCenters
                .filter(c => c.id !== selectedCenter?.id)
                .map(center => (
                  <option key={center.id} value={center.id}>
                    {center.code} - {center.name_ar}
                  </option>
                ))}
            </select>
          </div>

          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
              الميزانية السنوية (ريال)
            </label>
            <input
              type="number"
              value={formData.annual_budget}
              onChange={(e) => setFormData({ ...formData, annual_budget: e.target.value })}
              className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              placeholder="0.00"
              min="0"
              step="0.01"
            />
          </div>

          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
              الوصف
            </label>
            <textarea
              value={formData.description}
              onChange={(e) => setFormData({ ...formData, description: e.target.value })}
              rows={3}
              className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            />
          </div>

          <div>
            <label className="flex items-center gap-2">
              <input
                type="checkbox"
                checked={formData.is_active}
                onChange={(e) => setFormData({ ...formData, is_active: e.target.checked })}
                className="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
              />
              <span className="text-sm font-medium text-gray-700">مركز نشط</span>
            </label>
          </div>

          <div className="flex justify-end gap-3 pt-4">
            <Button type="button" variant="secondary" onClick={closeFormModal}>
              إلغاء
            </Button>
            <Button type="submit" disabled={saving}>
              {saving ? 'جاري الحفظ...' : selectedCenter ? 'تحديث' : 'إضافة'}
            </Button>
          </div>
        </form>
      </Modal>

      {/* Delete Confirmation Modal */}
      <Modal
        isOpen={showDeleteModal}
        onClose={() => setShowDeleteModal(false)}
        title="حذف مركز التكلفة"
        size="sm"
      >
        <div className="space-y-4">
          <p className="text-gray-600">
            هل أنت متأكد من حذف مركز التكلفة <strong>{selectedCenter?.name_ar}</strong>؟
          </p>
          {selectedCenter?.children_count > 0 && (
            <div className="bg-yellow-50 text-yellow-800 p-3 rounded-lg text-sm">
              ⚠️ هذا المركز يحتوي على {selectedCenter.children_count} مراكز فرعية
            </div>
          )}
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

      {/* Budget Details Modal */}
      <Modal
        isOpen={showBudgetModal}
        onClose={() => setShowBudgetModal(false)}
        title={`تفاصيل ميزانية ${selectedCenter?.name_ar}`}
        size="lg"
      >
        {selectedCenter && (
          <div className="space-y-6">
            {/* Budget Overview */}
            <div className="grid grid-cols-3 gap-4">
              <div className="bg-blue-50 p-4 rounded-lg text-center">
                <div className="text-2xl font-bold text-blue-600">
                  {formatCurrency(selectedCenter.annual_budget)}
                </div>
                <div className="text-sm text-gray-600">الميزانية السنوية</div>
              </div>
              <div className="bg-purple-50 p-4 rounded-lg text-center">
                <div className="text-2xl font-bold text-purple-600">
                  {formatCurrency(selectedCenter.spent_budget)}
                </div>
                <div className="text-sm text-gray-600">المصروف</div>
              </div>
              <div className="bg-green-50 p-4 rounded-lg text-center">
                <div className="text-2xl font-bold text-green-600">
                  {formatCurrency(selectedCenter.remaining_budget)}
                </div>
                <div className="text-sm text-gray-600">المتبقي</div>
              </div>
            </div>

            {/* Progress Bar */}
            <div>
              <div className="flex justify-between mb-2">
                <span className="text-sm text-gray-600">نسبة الاستخدام</span>
                <span className={`text-sm font-medium ${
                  selectedCenter.budget_utilization >= 90 ? 'text-red-600' :
                  selectedCenter.budget_utilization >= 70 ? 'text-orange-600' : 'text-green-600'
                }`}>
                  {selectedCenter.budget_utilization?.toFixed(1)}%
                </span>
              </div>
              <div className="w-full h-4 bg-gray-200 rounded-full">
                <div
                  className={`h-full rounded-full transition-all ${
                    selectedCenter.budget_utilization >= 90 ? 'bg-red-500' :
                    selectedCenter.budget_utilization >= 70 ? 'bg-orange-500' : 'bg-green-500'
                  }`}
                  style={{ width: `${Math.min(selectedCenter.budget_utilization, 100)}%` }}
                />
              </div>
            </div>

            {/* Monthly Breakdown (Mock) */}
            <div>
              <h4 className="font-medium text-gray-900 mb-3">التوزيع الشهري</h4>
              <div className="grid grid-cols-6 gap-2 text-center text-xs">
                {['يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو'].map((month, i) => (
                  <div key={month} className="bg-gray-50 p-2 rounded">
                    <div className="text-gray-500">{month}</div>
                    <div className="font-medium">{formatCurrency((selectedCenter.spent_budget / 6) * (Math.random() * 0.5 + 0.75))}</div>
                  </div>
                ))}
              </div>
            </div>

            <div className="flex justify-end">
              <Button variant="secondary" onClick={() => setShowBudgetModal(false)}>
                إغلاق
              </Button>
            </div>
          </div>
        )}
      </Modal>
    </div>
  );
}
