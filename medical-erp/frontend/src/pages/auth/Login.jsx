import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useAuth } from '../../contexts/AuthContext';
import toast from 'react-hot-toast';

export default function Login() {
  const [employeeNumber, setEmployeeNumber] = useState('');
  const [phone, setPhone] = useState('');
  const [submitting, setSubmitting] = useState(false);
  const { login } = useAuth();
  const navigate = useNavigate();

  async function handleSubmit(e) {
    e.preventDefault();
    setSubmitting(true);

    try {
      const data = await login(employeeNumber, phone);
      toast.success(data.message || 'تم تسجيل الدخول بنجاح');
      navigate('/', { replace: true });
    } catch (error) {
      let msg;
      if (error.response) {
        msg =
          error.response.data?.message ||
          error.response.data?.errors?.employee_number?.[0] ||
          error.response.data?.errors?.phone?.[0] ||
          `خطأ من الخادم (${error.response.status})`;
      } else if (error.request) {
        msg = 'لا يمكن الاتصال بالخادم. تحقق من اتصال الإنترنت.';
      } else {
        msg = 'حدث خطأ أثناء تسجيل الدخول';
      }
      toast.error(msg);
    } finally {
      setSubmitting(false);
    }
  }

  return (
    <div className="min-h-screen flex items-center justify-center bg-gradient-to-br from-teal-50 to-emerald-100 px-4" dir="rtl">
      <div className="w-full max-w-md">
        {/* Logo / Header */}
        <div className="text-center mb-8">
          <div className="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-gradient-to-br from-teal-800 to-emerald-500 mb-4">
            <svg className="w-8 h-8 text-white" fill="none" stroke="currentColor" strokeWidth={2.5} strokeLinecap="round" strokeLinejoin="round" viewBox="0 0 24 24">
              <path d="M5 20 L12 4 L19 20" />
              <line x1="8" y1="14" x2="16" y2="14" />
              <path d="M8 8 L12 2 L16 8" />
            </svg>
          </div>
          <h1 className="text-2xl font-bold text-gray-800">إنماء</h1>
          <p className="text-sm font-semibold text-teal-700 tracking-widest mb-1">ENMA</p>
          <p className="text-gray-500 mt-1">نظام إدارة الموارد البشرية</p>
        </div>

        {/* Login Card */}
        <div className="bg-white rounded-2xl shadow-xl p-8">
          <h2 className="text-xl font-semibold text-gray-700 mb-6 text-center">تسجيل الدخول</h2>

          <form onSubmit={handleSubmit} className="space-y-5">
            {/* Employee Number */}
            <div>
              <label htmlFor="employee_number" className="block text-sm font-medium text-gray-700 mb-1">
                الرقم الوظيفي
              </label>
              <div className="relative">
                <span className="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400">
                  <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                  </svg>
                </span>
                <input
                  id="employee_number"
                  type="text"
                  inputMode="numeric"
                  value={employeeNumber}
                  onChange={(e) => setEmployeeNumber(e.target.value)}
                  placeholder="أدخل الرقم الوظيفي"
                  required
                  className="w-full pr-10 pl-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-teal-500 focus:border-teal-500 outline-none transition-all text-gray-700 placeholder-gray-400"
                />
              </div>
            </div>

            {/* Phone */}
            <div>
              <label htmlFor="phone" className="block text-sm font-medium text-gray-700 mb-1">
                رقم الهاتف
              </label>
              <div className="relative">
                <span className="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400">
                  <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                  </svg>
                </span>
                <input
                  id="phone"
                  type="tel"
                  inputMode="tel"
                  value={phone}
                  onChange={(e) => setPhone(e.target.value)}
                  placeholder="05XXXXXXXX"
                  required
                  dir="ltr"
                  className="w-full pr-10 pl-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-teal-500 focus:border-teal-500 outline-none transition-all text-gray-700 placeholder-gray-400 text-left"
                />
              </div>
            </div>

            {/* Submit */}
            <button
              type="submit"
              disabled={submitting}
              className="w-full py-3 px-4 bg-teal-600 hover:bg-teal-700 disabled:bg-teal-400 text-white font-medium rounded-xl transition-colors duration-200 flex items-center justify-center gap-2"
            >
              {submitting ? (
                <>
                  <svg className="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
                    <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                  </svg>
                  جاري تسجيل الدخول...
                </>
              ) : (
                'تسجيل الدخول'
              )}
            </button>
          </form>

          {/* Test accounts hint */}
          <div className="mt-6 p-4 bg-gray-50 rounded-xl">
            <p className="text-xs text-gray-500 text-center mb-2">حسابات تجريبية</p>
            <div className="space-y-1 text-xs text-gray-600">
              <div className="flex justify-between">
                <span>مدير عام</span>
                <span dir="ltr">1001 / 0512345001</span>
              </div>
              <div className="flex justify-between">
                <span>مدير موارد بشرية</span>
                <span dir="ltr">1002 / 0512345002</span>
              </div>
              <div className="flex justify-between">
                <span>طبيب</span>
                <span dir="ltr">2001 / 0512345003</span>
              </div>
              <div className="flex justify-between">
                <span>ممرض</span>
                <span dir="ltr">3001 / 0512345004</span>
              </div>
            </div>
          </div>
        </div>

        <p className="text-center text-xs text-gray-400 mt-6">
          ENMA &copy; {new Date().getFullYear()}
        </p>
      </div>
    </div>
  );
}
