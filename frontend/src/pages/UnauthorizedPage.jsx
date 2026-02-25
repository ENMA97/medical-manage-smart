import React from 'react';
import { Link, useNavigate, useLocation } from 'react-router-dom';
import { ShieldOff, Home, ArrowRight, LogIn } from 'lucide-react';
import { useLocale } from '../contexts/LocaleContext';
import { useAuth } from '../contexts/AuthContext';

/**
 * صفحة عدم الصلاحية
 * Unauthorized Access Page
 */
export default function UnauthorizedPage() {
  const { t, locale } = useLocale();
  const { isAuthenticated, user } = useAuth();
  const navigate = useNavigate();
  const location = useLocation();
  const isRTL = locale === 'ar';

  const messages = {
    ar: {
      title: 'غير مصرح بالوصول',
      subtitle: 'ليس لديك الصلاحية للوصول إلى هذه الصفحة',
      description: 'إذا كنت تعتقد أن هذا خطأ، يرجى التواصل مع مسؤول النظام.',
      backHome: 'العودة للرئيسية',
      goBack: 'رجوع',
      login: 'تسجيل الدخول',
      currentUser: 'المستخدم الحالي',
      requestedPage: 'الصفحة المطلوبة',
      contactAdmin: 'تواصل مع المسؤول',
    },
    en: {
      title: 'Unauthorized Access',
      subtitle: 'You do not have permission to access this page',
      description: 'If you believe this is an error, please contact your system administrator.',
      backHome: 'Back to Home',
      goBack: 'Go Back',
      login: 'Login',
      currentUser: 'Current User',
      requestedPage: 'Requested Page',
      contactAdmin: 'Contact Admin',
    },
  };

  const msg = messages[locale] || messages.ar;

  const handleGoBack = () => {
    if (window.history.length > 2) {
      navigate(-1);
    } else {
      navigate('/');
    }
  };

  return (
    <div
      className="min-h-screen bg-gradient-to-br from-red-50 via-white to-orange-50 flex items-center justify-center p-4"
      dir={isRTL ? 'rtl' : 'ltr'}
    >
      <div className="max-w-lg w-full">
        {/* Icon */}
        <div className="text-center mb-8">
          <div className="inline-flex items-center justify-center w-24 h-24 rounded-full bg-red-100 mb-6">
            <ShieldOff className="w-12 h-12 text-red-600" />
          </div>

          {/* Title */}
          <h1 className="text-3xl font-bold text-gray-900 mb-2">{msg.title}</h1>
          <p className="text-lg text-gray-600 mb-4">{msg.subtitle}</p>
          <p className="text-sm text-gray-500">{msg.description}</p>
        </div>

        {/* Info Card */}
        <div className="bg-white rounded-xl shadow-lg border border-gray-200 p-6 mb-6">
          {isAuthenticated && user && (
            <div className="mb-4 pb-4 border-b border-gray-100">
              <p className="text-sm text-gray-500 mb-1">{msg.currentUser}</p>
              <p className="font-medium text-gray-900">
                {user.name || user.email}
              </p>
              {user.role && (
                <span className="inline-block mt-1 px-2 py-0.5 bg-blue-100 text-blue-700 text-xs rounded-full">
                  {user.role}
                </span>
              )}
            </div>
          )}

          <div>
            <p className="text-sm text-gray-500 mb-1">{msg.requestedPage}</p>
            <code className="block bg-gray-50 text-gray-700 px-3 py-2 rounded-lg text-sm font-mono break-all">
              {location.pathname}
            </code>
          </div>
        </div>

        {/* Actions */}
        <div className="flex flex-col sm:flex-row gap-3">
          {isAuthenticated ? (
            <>
              <Link
                to="/"
                className="flex-1 inline-flex items-center justify-center gap-2 px-6 py-3 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition-colors"
              >
                <Home className="w-5 h-5" />
                {msg.backHome}
              </Link>
              <button
                onClick={handleGoBack}
                className="flex-1 inline-flex items-center justify-center gap-2 px-6 py-3 bg-gray-100 text-gray-700 font-medium rounded-lg hover:bg-gray-200 transition-colors"
              >
                <ArrowRight className={`w-5 h-5 ${isRTL ? '' : 'rotate-180'}`} />
                {msg.goBack}
              </button>
            </>
          ) : (
            <>
              <Link
                to="/login"
                state={{ from: location }}
                className="flex-1 inline-flex items-center justify-center gap-2 px-6 py-3 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition-colors"
              >
                <LogIn className="w-5 h-5" />
                {msg.login}
              </Link>
              <Link
                to="/"
                className="flex-1 inline-flex items-center justify-center gap-2 px-6 py-3 bg-gray-100 text-gray-700 font-medium rounded-lg hover:bg-gray-200 transition-colors"
              >
                <Home className="w-5 h-5" />
                {msg.backHome}
              </Link>
            </>
          )}
        </div>

        {/* Help text */}
        <p className="text-center text-sm text-gray-500 mt-6">
          <span className="text-gray-400">Error Code: </span>
          <span className="font-mono">403 Forbidden</span>
        </p>
      </div>
    </div>
  );
}
