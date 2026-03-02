import { useAuth } from '../contexts/AuthContext';

export default function Dashboard() {
  const { user } = useAuth();

  return (
    <div>
      <div className="mb-8">
        <h1 className="text-2xl font-bold text-gray-800">
          مرحباً، {user?.full_name}
        </h1>
        <p className="text-gray-500 mt-1">لوحة التحكم الرئيسية</p>
      </div>

      {/* Quick Stats */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <StatCard
          title="القسم"
          value={user?.employee?.department?.name_ar || '—'}
          icon={
            <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
            </svg>
          }
          color="blue"
        />
        <StatCard
          title="المسمى الوظيفي"
          value={user?.employee?.position?.name_ar || '—'}
          icon={
            <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
            </svg>
          }
          color="green"
        />
        <StatCard
          title="الرقم الوظيفي"
          value={user?.employee?.employee_number || '—'}
          icon={
            <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2" />
            </svg>
          }
          color="purple"
        />
        <StatCard
          title="نوع الحساب"
          value={getUserTypeAr(user?.user_type)}
          icon={
            <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
            </svg>
          }
          color="amber"
        />
      </div>

      {/* Profile Card */}
      <div className="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
        <h2 className="text-lg font-semibold text-gray-800 mb-4">معلومات الملف الشخصي</h2>
        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          <InfoRow label="الاسم الكامل" value={user?.full_name} />
          <InfoRow label="اسم المستخدم" value={user?.username} />
          <InfoRow label="البريد الإلكتروني" value={user?.email || '—'} />
          <InfoRow label="الهاتف" value={user?.phone} dir="ltr" />
          <InfoRow label="اللغة المفضلة" value={user?.preferred_language === 'ar' ? 'العربية' : 'English'} />
          <InfoRow label="تاريخ آخر دخول" value={user?.last_login_at ? new Date(user.last_login_at).toLocaleString('ar-SA') : '—'} />
        </div>
      </div>
    </div>
  );
}

function StatCard({ title, value, icon, color }) {
  const colors = {
    blue: 'bg-blue-50 text-blue-600',
    green: 'bg-green-50 text-green-600',
    purple: 'bg-purple-50 text-purple-600',
    amber: 'bg-amber-50 text-amber-600',
  };

  return (
    <div className="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
      <div className="flex items-center gap-3 mb-3">
        <div className={`w-10 h-10 rounded-xl flex items-center justify-center ${colors[color]}`}>
          {icon}
        </div>
        <span className="text-sm text-gray-500">{title}</span>
      </div>
      <p className="text-lg font-semibold text-gray-800">{value}</p>
    </div>
  );
}

function InfoRow({ label, value, dir }) {
  return (
    <div className="flex justify-between items-center py-2 border-b border-gray-50">
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
