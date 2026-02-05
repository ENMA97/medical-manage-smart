import React, { useState, useEffect } from 'react';
import { leaveTypesApi } from '../../services/leaveApi';

/**
 * صفحة أنواع الإجازات
 * Leave Types Page
 */
export default function LeaveTypesPage() {
  const [leaveTypes, setLeaveTypes] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  useEffect(() => {
    loadData();
  }, []);

  const loadData = async () => {
    try {
      setLoading(true);
      const response = await leaveTypesApi.getAll();
      setLeaveTypes(response.data || []);
    } catch (err) {
      setError(err.message);
    } finally {
      setLoading(false);
    }
  };

  const getCategoryLabel = (category) => {
    const categories = {
      annual: 'سنوية',
      sick: 'مرضية',
      emergency: 'طارئة',
      unpaid: 'بدون راتب',
      maternity: 'أمومة',
      paternity: 'أبوة',
      hajj: 'حج',
      marriage: 'زواج',
      bereavement: 'وفاة',
      study: 'دراسية',
      compensatory: 'تعويضية',
      other: 'أخرى',
    };
    return categories[category] || category;
  };

  if (loading) {
    return (
      <div className="flex items-center justify-center h-64">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="bg-red-50 border border-red-200 rounded-lg p-4 text-red-700">
        <h3 className="font-bold">خطأ</h3>
        <p>{error}</p>
        <button onClick={loadData} className="mt-2 px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">
          إعادة المحاولة
        </button>
      </div>
    );
  }

  return (
    <div className="space-y-6" dir="rtl">
      {/* Header */}
      <div className="flex justify-between items-center">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">أنواع الإجازات</h1>
          <p className="text-gray-600 mt-1">إدارة وتكوين أنواع الإجازات المختلفة</p>
        </div>
        <button className="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 flex items-center gap-2">
          <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 4v16m8-8H4" />
          </svg>
          إضافة نوع جديد
        </button>
      </div>

      {/* Leave Types Grid */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        {leaveTypes.map((type) => (
          <div
            key={type.id}
            className="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow"
          >
            {/* Header with color */}
            <div
              className="h-2"
              style={{ backgroundColor: type.color_code || '#6B7280' }}
            ></div>

            <div className="p-6">
              {/* Title and Status */}
              <div className="flex justify-between items-start mb-4">
                <div>
                  <h3 className="text-lg font-semibold text-gray-900">{type.name_ar}</h3>
                  <p className="text-sm text-gray-500">{type.name_en}</p>
                </div>
                <span
                  className={`px-2 py-1 rounded-full text-xs font-medium ${
                    type.is_active
                      ? 'bg-green-100 text-green-800'
                      : 'bg-gray-100 text-gray-800'
                  }`}
                >
                  {type.is_active ? 'نشط' : 'غير نشط'}
                </span>
              </div>

              {/* Category Badge */}
              <div className="mb-4">
                <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                  {getCategoryLabel(type.category)}
                </span>
                {type.is_paid ? (
                  <span className="mr-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                    مدفوعة
                  </span>
                ) : (
                  <span className="mr-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                    غير مدفوعة
                  </span>
                )}
              </div>

              {/* Details */}
              <div className="space-y-2 text-sm">
                <div className="flex justify-between">
                  <span className="text-gray-600">الأيام الافتراضية:</span>
                  <span className="font-medium">{type.default_days} يوم</span>
                </div>
                {type.max_days_per_request && (
                  <div className="flex justify-between">
                    <span className="text-gray-600">الحد الأقصى للطلب:</span>
                    <span className="font-medium">{type.max_days_per_request} يوم</span>
                  </div>
                )}
                {type.advance_notice_days > 0 && (
                  <div className="flex justify-between">
                    <span className="text-gray-600">إشعار مسبق:</span>
                    <span className="font-medium">{type.advance_notice_days} يوم</span>
                  </div>
                )}
              </div>

              {/* Features */}
              <div className="mt-4 flex flex-wrap gap-2">
                {type.requires_attachment && (
                  <span className="text-xs bg-yellow-50 text-yellow-700 px-2 py-1 rounded">
                    يتطلب مرفق
                  </span>
                )}
                {type.requires_medical_certificate && (
                  <span className="text-xs bg-red-50 text-red-700 px-2 py-1 rounded">
                    يتطلب تقرير طبي
                  </span>
                )}
                {type.can_be_carried_over && (
                  <span className="text-xs bg-blue-50 text-blue-700 px-2 py-1 rounded">
                    قابل للترحيل
                  </span>
                )}
              </div>

              {/* Actions */}
              <div className="mt-4 pt-4 border-t border-gray-200 flex justify-end gap-2">
                <button className="text-sm text-blue-600 hover:text-blue-800">
                  تعديل
                </button>
                <button className="text-sm text-red-600 hover:text-red-800">
                  حذف
                </button>
              </div>
            </div>
          </div>
        ))}
      </div>

      {leaveTypes.length === 0 && (
        <div className="text-center py-12 bg-white rounded-lg shadow">
          <svg
            className="mx-auto h-12 w-12 text-gray-400"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              strokeLinecap="round"
              strokeLinejoin="round"
              strokeWidth={2}
              d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"
            />
          </svg>
          <h3 className="mt-2 text-sm font-medium text-gray-900">لا توجد أنواع إجازات</h3>
          <p className="mt-1 text-sm text-gray-500">ابدأ بإضافة نوع إجازة جديد</p>
        </div>
      )}
    </div>
  );
}
