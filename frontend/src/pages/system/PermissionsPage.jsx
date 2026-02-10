import React from 'react';
import { Card, CardHeader } from '../../components/ui';
import { HiLockClosed } from 'react-icons/hi';

export default function PermissionsPage() {
  return (
    <div className="space-y-6" dir="rtl">
      <div className="flex justify-between items-center">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">إدارة الصلاحيات</h1>
          <p className="text-gray-600 mt-1">عرض الصلاحيات المتاحة في النظام</p>
        </div>
      </div>

      <Card>
        <CardHeader title="قائمة الصلاحيات" />
        <div className="text-center py-12 text-gray-500">
          <HiLockClosed className="w-12 h-12 mx-auto mb-4 text-gray-400" />
          <p>سيتم تنفيذ هذه الصفحة قريباً</p>
        </div>
      </Card>
    </div>
  );
}
