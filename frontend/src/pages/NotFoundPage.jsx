import React from 'react';
import { Link } from 'react-router-dom';
import { useLocale } from '../contexts/LocaleContext';
import { Button } from '../components/ui';
import { HiHome } from 'react-icons/hi';

/**
 * صفحة 404 - غير موجود
 * 404 Not Found Page
 */
export default function NotFoundPage() {
  const { t } = useLocale();

  return (
    <div className="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4">
      <div className="max-w-md w-full text-center">
        <div className="mb-8">
          <h1 className="text-9xl font-bold text-primary-600">404</h1>
          <div className="h-2 w-32 bg-primary-600 mx-auto rounded-full mt-4" />
        </div>

        <h2 className="text-2xl font-bold text-gray-900 mb-2">
          الصفحة غير موجودة
        </h2>
        <p className="text-gray-600 mb-8">
          عذراً، الصفحة التي تبحث عنها غير موجودة أو تم نقلها.
        </p>

        <Link to="/">
          <Button icon={HiHome} size="lg">
            العودة للرئيسية
          </Button>
        </Link>
      </div>
    </div>
  );
}
