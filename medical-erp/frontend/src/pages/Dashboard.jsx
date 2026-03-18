import { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import { useAuth } from '../contexts/AuthContext';
import dashboardService from '../services/dashboardService';
import toast from 'react-hot-toast';

export default function Dashboard() {
  const { user } = useAuth();
  const [summary, setSummary] = useState(null);
  const [alerts, setAlerts] = useState([]);
  const [leaveStats, setLeaveStats] = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    async function loadDashboard() {
      try {
        const [summaryRes, alertsRes, leaveRes] = await Promise.allSettled([
          dashboardService.getSummary(),
          dashboardService.getAlerts(),
          dashboardService.getLeaveStats(),
        ]);
        if (summaryRes.status === 'fulfilled') setSummary(summaryRes.value.data.data);
        if (alertsRes.status === 'fulfilled') setAlerts(alertsRes.value.data.data || []);
        if (leaveRes.status === 'fulfilled') setLeaveStats(leaveRes.value.data.data);
      } catch {
        toast.error('حدث خطأ في تحميل البيانات');
      } finally {
        setLoading(false);
      }
    }
    loadDashboard();
  }, []);

  return (
    <div className="space-y-4 sm:space-y-6">
      {/* Welcome */}
      <div>
        <h1 className="text-xl sm:text-2xl font-bold text-gray-800">
          مرحباً، {user?.full_name}
        </h1>
        <p className="text-gray-500 mt-1 text-sm">لوحة التحكم الرئيسية</p>
      </div>

      {/* Stats Grid */}
      <div className="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
        <StatCard
          title="الموظفون"
          value={loading ? '...' : summary?.total_employees ?? '—'}
          subtitle={summary?.active_employees != null ? `${summary.active_employees} نشط` : null}
          icon={<path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />}
          color="blue"
          link="/employees"
        />
        <StatCard
          title="الأقسام"
          value={loading ? '...' : summary?.total_departments ?? '—'}
          icon={<path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />}
          color="green"
          link="/departments"
        />
        <StatCard
          title="طلبات الإجازة"
          value={loading ? '...' : leaveStats?.pending_requests ?? '—'}
          subtitle="قيد الانتظار"
          icon={<path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />}
          color="amber"
          link="/leave-requests"
        />
        <StatCard
          title="العقود المنتهية"
          value={loading ? '...' : summary?.expiring_contracts ?? '—'}
          subtitle="خلال 30 يوم"
          icon={<path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />}
          color="red"
          link="/contracts"
        />
      </div>

      {/* Quick Info + Alerts */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-4">
        {/* Profile Card */}
        <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-4 sm:p-6">
          <h2 className="text-base font-semibold text-gray-800 mb-3">معلوماتي</h2>
          <div className="space-y-0">
            <InfoRow label="الرقم الوظيفي" value={user?.employee?.employee_number} />
            <InfoRow label="القسم" value={user?.employee?.department?.name_ar} />
            <InfoRow label="المسمى" value={user?.employee?.position?.name_ar || user?.employee?.position?.title_ar} />
            <InfoRow label="الهاتف" value={user?.phone} dir="ltr" />
            <InfoRow label="نوع الحساب" value={getUserTypeAr(user?.user_type)} />
          </div>
        </div>

        {/* Alerts */}
        <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-4 sm:p-6">
          <h2 className="text-base font-semibold text-gray-800 mb-3">التنبيهات</h2>
          {loading ? (
            <div className="flex items-center justify-center py-8">
              <div className="w-6 h-6 border-3 border-teal-200 border-t-teal-600 rounded-full animate-spin" />
            </div>
          ) : alerts.length === 0 ? (
            <div className="text-center py-8 text-gray-400 text-sm">لا توجد تنبيهات حالياً</div>
          ) : (
            <div className="space-y-2 max-h-64 overflow-y-auto">
              {alerts.slice(0, 8).map((alert, i) => (
                <div key={alert.id || i} className={`flex items-start gap-2 p-2.5 rounded-lg text-sm ${
                  alert.type === 'danger' ? 'bg-red-50 text-red-700' :
                  alert.type === 'warning' ? 'bg-amber-50 text-amber-700' :
                  'bg-teal-50 text-teal-700'
                }`}>
                  <svg className="w-4 h-4 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                  <span>{alert.message}</span>
                </div>
              ))}
            </div>
          )}
        </div>
      </div>

      {/* Quick Actions */}
      <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-4 sm:p-6">
        <h2 className="text-base font-semibold text-gray-800 mb-3">إجراءات سريعة</h2>
        <div className="grid grid-cols-2 sm:grid-cols-4 gap-2 sm:gap-3">
          <QuickAction to="/employees" label="إدارة الموظفين" color="blue" />
          <QuickAction to="/leave-requests" label="طلبات الإجازة" color="green" />
          <QuickAction to="/payroll" label="مسيرات الرواتب" color="purple" />
          <QuickAction to="/import" label="استيراد بيانات" color="amber" />
        </div>
      </div>
    </div>
  );
}

function StatCard({ title, value, subtitle, icon, color, link }) {
  const colors = {
    blue: 'bg-teal-50 text-teal-600',
    green: 'bg-green-50 text-green-600',
    amber: 'bg-amber-50 text-amber-600',
    red: 'bg-red-50 text-red-600',
    purple: 'bg-purple-50 text-purple-600',
  };

  const content = (
    <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-3 sm:p-4 hover:shadow-md transition-shadow">
      <div className="flex items-center gap-2 mb-2">
        <div className={`w-8 h-8 rounded-lg flex items-center justify-center ${colors[color]}`}>
          <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">{icon}</svg>
        </div>
        <span className="text-xs text-gray-500">{title}</span>
      </div>
      <p className="text-lg sm:text-xl font-bold text-gray-800">{value}</p>
      {subtitle && <p className="text-xs text-gray-400 mt-0.5">{subtitle}</p>}
    </div>
  );

  return link ? <Link to={link}>{content}</Link> : content;
}

function QuickAction({ to, label, color }) {
  const colors = {
    blue: 'bg-teal-50 text-teal-700 hover:bg-teal-100',
    green: 'bg-green-50 text-green-700 hover:bg-green-100',
    purple: 'bg-purple-50 text-purple-700 hover:bg-purple-100',
    amber: 'bg-amber-50 text-amber-700 hover:bg-amber-100',
  };

  return (
    <Link to={to} className={`px-3 py-3 rounded-lg text-sm font-medium text-center transition-colors ${colors[color]}`}>
      {label}
    </Link>
  );
}

function InfoRow({ label, value, dir }) {
  return (
    <div className="flex justify-between items-center py-2.5 border-b border-gray-50 last:border-0">
      <span className="text-sm text-gray-500">{label}</span>
      <span className="text-sm font-medium text-gray-800" dir={dir}>{value || '—'}</span>
    </div>
  );
}

function getUserTypeAr(type) {
  const map = {
    super_admin: 'مدير النظام',
    hr_manager: 'مدير الموارد البشرية',
    department_manager: 'مدير قسم',
    employee: 'موظف',
  };
  return map[type] || type || '—';
}
