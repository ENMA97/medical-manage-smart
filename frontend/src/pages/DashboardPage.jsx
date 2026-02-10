import React from 'react';
import { useLocale } from '../contexts/LocaleContext';
import { useAuth } from '../contexts/AuthContext';
import { Card, CardHeader } from '../components/ui';
import {
  HiUserGroup,
  HiCalendar,
  HiCurrencyDollar,
  HiCube,
  HiClock,
  HiChartBar,
  HiClipboardCheck,
  HiExclamationCircle,
} from 'react-icons/hi';

/**
 * لوحة التحكم الرئيسية
 * Dashboard Page
 */
export default function DashboardPage() {
  const { t } = useLocale();
  const { user } = useAuth();

  const stats = [
    {
      name: 'إجمالي الموظفين',
      value: '156',
      change: '+12%',
      changeType: 'positive',
      icon: HiUserGroup,
      color: 'bg-blue-500',
    },
    {
      name: 'طلبات الإجازة المعلقة',
      value: '8',
      change: '-3',
      changeType: 'neutral',
      icon: HiCalendar,
      color: 'bg-yellow-500',
    },
    {
      name: 'إجمالي الرواتب الشهرية',
      value: '543,250',
      suffix: 'ر.س',
      change: '+5.2%',
      changeType: 'positive',
      icon: HiCurrencyDollar,
      color: 'bg-green-500',
    },
    {
      name: 'أصناف تحت الحد الأدنى',
      value: '12',
      change: '+4',
      changeType: 'negative',
      icon: HiCube,
      color: 'bg-red-500',
    },
  ];

  const quickActions = [
    { name: 'طلب إجازة جديد', href: '/leaves/requests', icon: HiCalendar },
    { name: 'عرض الحضور', href: '/roster/attendance', icon: HiClock },
    { name: 'مراجعة المطالبات', href: '/finance/claims', icon: HiClipboardCheck },
    { name: 'التقارير المالية', href: '/finance/reports', icon: HiChartBar },
  ];

  const pendingApprovals = [
    { type: 'leave', title: 'طلب إجازة - أحمد محمد', date: '2024-01-15', status: 'pending_supervisor' },
    { type: 'leave', title: 'طلب إجازة - سارة أحمد', date: '2024-01-14', status: 'pending_hr' },
    { type: 'purchase', title: 'طلب شراء - مستلزمات طبية', date: '2024-01-13', status: 'pending' },
    { type: 'claim', title: 'مطالبة تأمين - #12345', date: '2024-01-12', status: 'submitted' },
  ];

  return (
    <div className="space-y-6" dir="rtl">
      {/* Welcome Header */}
      <div className="bg-gradient-to-l from-primary-600 to-primary-700 rounded-xl p-6 text-white">
        <h1 className="text-2xl font-bold">
          مرحباً، {user?.name || 'مستخدم'}
        </h1>
        <p className="mt-1 text-primary-100">
          {t('nav.dashboard')} - {new Date().toLocaleDateString('ar-SA', {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric',
          })}
        </p>
      </div>

      {/* Stats Grid */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        {stats.map((stat) => (
          <Card key={stat.name} className="p-6">
            <div className="flex items-center gap-4">
              <div className={`p-3 rounded-lg ${stat.color}`}>
                <stat.icon className="w-6 h-6 text-white" />
              </div>
              <div>
                <p className="text-sm text-gray-600">{stat.name}</p>
                <div className="flex items-baseline gap-2">
                  <span className="text-2xl font-bold text-gray-900">
                    {stat.value}
                  </span>
                  {stat.suffix && (
                    <span className="text-sm text-gray-500">{stat.suffix}</span>
                  )}
                </div>
                <span
                  className={`text-xs ${
                    stat.changeType === 'positive'
                      ? 'text-green-600'
                      : stat.changeType === 'negative'
                      ? 'text-red-600'
                      : 'text-gray-500'
                  }`}
                >
                  {stat.change} من الشهر الماضي
                </span>
              </div>
            </div>
          </Card>
        ))}
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {/* Quick Actions */}
        <Card className="lg:col-span-1">
          <CardHeader title="إجراءات سريعة" />
          <div className="space-y-2">
            {quickActions.map((action) => (
              <a
                key={action.name}
                href={action.href}
                className="flex items-center gap-3 p-3 rounded-lg hover:bg-gray-50 transition-colors"
              >
                <div className="p-2 bg-primary-50 rounded-lg">
                  <action.icon className="w-5 h-5 text-primary-600" />
                </div>
                <span className="text-sm font-medium text-gray-700">
                  {action.name}
                </span>
              </a>
            ))}
          </div>
        </Card>

        {/* Pending Approvals */}
        <Card className="lg:col-span-2">
          <CardHeader
            title="الموافقات المعلقة"
            subtitle="العناصر التي تحتاج لمراجعتك"
            action={
              <a
                href="/approvals"
                className="text-sm text-primary-600 hover:text-primary-700"
              >
                عرض الكل
              </a>
            }
          />
          <div className="space-y-3">
            {pendingApprovals.map((item, index) => (
              <div
                key={index}
                className="flex items-center justify-between p-3 rounded-lg bg-gray-50"
              >
                <div className="flex items-center gap-3">
                  <div className="p-2 bg-yellow-100 rounded-lg">
                    <HiExclamationCircle className="w-5 h-5 text-yellow-600" />
                  </div>
                  <div>
                    <p className="text-sm font-medium text-gray-900">
                      {item.title}
                    </p>
                    <p className="text-xs text-gray-500">{item.date}</p>
                  </div>
                </div>
                <button className="text-sm text-primary-600 hover:text-primary-700 font-medium">
                  مراجعة
                </button>
              </div>
            ))}
          </div>
        </Card>
      </div>

      {/* Alerts */}
      <Card className="border-yellow-200 bg-yellow-50">
        <div className="flex items-start gap-4">
          <div className="p-2 bg-yellow-100 rounded-lg">
            <HiExclamationCircle className="w-6 h-6 text-yellow-600" />
          </div>
          <div>
            <h3 className="font-semibold text-yellow-800">تنبيهات المخزون</h3>
            <p className="text-sm text-yellow-700 mt-1">
              يوجد 12 صنف تحت الحد الأدنى للمخزون. يرجى مراجعة طلبات الشراء المعلقة.
            </p>
            <a
              href="/inventory/items?filter=low_stock"
              className="inline-block mt-2 text-sm font-medium text-yellow-800 hover:text-yellow-900"
            >
              عرض الأصناف &larr;
            </a>
          </div>
        </div>
      </Card>
    </div>
  );
}
