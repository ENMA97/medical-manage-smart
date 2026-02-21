import React from 'react';

/**
 * Error Boundary Component
 * مكون لالتقاط الأخطاء ومنع تعطل التطبيق بالكامل
 */
class ErrorBoundary extends React.Component {
  constructor(props) {
    super(props);
    this.state = {
      hasError: false,
      error: null,
      errorInfo: null
    };
  }

  static getDerivedStateFromError(error) {
    // تحديث الحالة لعرض واجهة الخطأ
    return { hasError: true, error };
  }

  componentDidCatch(error, errorInfo) {
    // يمكن إرسال الخطأ إلى خدمة تتبع الأخطاء هنا
    console.error('Error Boundary caught an error:', error, errorInfo);
    this.setState({ errorInfo });

    // إرسال إلى خدمة التتبع (Sentry, LogRocket, etc.)
    // if (window.Sentry) {
    //   window.Sentry.captureException(error, { extra: errorInfo });
    // }
  }

  handleReset = () => {
    this.setState({ hasError: false, error: null, errorInfo: null });
  };

  handleReload = () => {
    window.location.reload();
  };

  handleGoHome = () => {
    window.location.href = '/';
  };

  render() {
    if (this.state.hasError) {
      // عرض واجهة بديلة عند حدوث خطأ
      return (
        <div className="min-h-screen bg-gray-50 flex items-center justify-center p-4" dir="rtl">
          <div className="bg-white rounded-xl shadow-lg max-w-lg w-full p-8 text-center">
            {/* أيقونة الخطأ */}
            <div className="w-20 h-20 mx-auto mb-6 bg-red-100 rounded-full flex items-center justify-center">
              <svg className="w-10 h-10 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2}
                  d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
              </svg>
            </div>

            {/* العنوان */}
            <h1 className="text-2xl font-bold text-gray-900 mb-2">
              حدث خطأ غير متوقع
            </h1>
            <p className="text-gray-600 mb-6">
              نعتذر عن هذا الخطأ. يمكنك المحاولة مرة أخرى أو العودة للصفحة الرئيسية.
            </p>

            {/* تفاصيل الخطأ (للتطوير فقط) */}
            {import.meta.env.DEV && this.state.error && (
              <details className="mb-6 text-right">
                <summary className="cursor-pointer text-sm text-gray-500 hover:text-gray-700">
                  تفاصيل الخطأ (للمطورين)
                </summary>
                <div className="mt-2 p-3 bg-gray-100 rounded-lg text-xs text-left font-mono overflow-auto max-h-40">
                  <p className="text-red-600 font-bold">{this.state.error.toString()}</p>
                  {this.state.errorInfo && (
                    <pre className="mt-2 text-gray-600 whitespace-pre-wrap">
                      {this.state.errorInfo.componentStack}
                    </pre>
                  )}
                </div>
              </details>
            )}

            {/* أزرار الإجراءات */}
            <div className="flex flex-col sm:flex-row gap-3 justify-center">
              <button
                onClick={this.handleReset}
                className="px-6 py-2.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium"
              >
                إعادة المحاولة
              </button>
              <button
                onClick={this.handleGoHome}
                className="px-6 py-2.5 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors font-medium"
              >
                الصفحة الرئيسية
              </button>
              <button
                onClick={this.handleReload}
                className="px-6 py-2.5 text-gray-500 hover:text-gray-700 transition-colors font-medium"
              >
                إعادة تحميل الصفحة
              </button>
            </div>

            {/* معلومات الدعم */}
            <p className="mt-8 text-xs text-gray-400">
              إذا استمرت المشكلة، يرجى التواصل مع الدعم الفني
            </p>
          </div>
        </div>
      );
    }

    return this.props.children;
  }
}

/**
 * Page-level Error Boundary with reset on navigation
 * مكون Error Boundary على مستوى الصفحة مع إعادة التعيين عند التنقل
 */
export class PageErrorBoundary extends React.Component {
  constructor(props) {
    super(props);
    this.state = { hasError: false, error: null };
  }

  static getDerivedStateFromError(error) {
    return { hasError: true, error };
  }

  componentDidCatch(error, errorInfo) {
    console.error('Page Error:', error, errorInfo);
  }

  componentDidUpdate(prevProps) {
    // إعادة التعيين عند تغيير المسار
    if (this.props.resetKey !== prevProps.resetKey && this.state.hasError) {
      this.setState({ hasError: false, error: null });
    }
  }

  render() {
    if (this.state.hasError) {
      return (
        <div className="p-8 text-center" dir="rtl">
          <div className="bg-red-50 border border-red-200 rounded-lg p-6 max-w-md mx-auto">
            <div className="w-12 h-12 mx-auto mb-4 bg-red-100 rounded-full flex items-center justify-center">
              <svg className="w-6 h-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
              </svg>
            </div>
            <h3 className="text-lg font-bold text-red-800 mb-2">
              خطأ في تحميل الصفحة
            </h3>
            <p className="text-sm text-red-600 mb-4">
              {this.state.error?.message || 'حدث خطأ غير متوقع'}
            </p>
            <button
              onClick={() => this.setState({ hasError: false, error: null })}
              className="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 text-sm"
            >
              إعادة المحاولة
            </button>
          </div>
        </div>
      );
    }

    return this.props.children;
  }
}

export default ErrorBoundary;
