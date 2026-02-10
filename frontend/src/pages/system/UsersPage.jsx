import React from 'react';
import { Card, CardHeader, Button } from '../../components/ui';
import { HiPlus, HiUsers } from 'react-icons/hi';

export default function UsersPage() {
  return (
    <div className="space-y-6" dir="rtl">
      <div className="flex justify-between items-center">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">إدارة المستخدمين</h1>
          <p className="text-gray-600 mt-1">إدارة حسابات المستخدمين</p>
        </div>
        <Button icon={HiPlus}>إضافة مستخدم</Button>
      </div>

      <Card>
        <CardHeader title="قائمة المستخدمين" />
        <div className="text-center py-12 text-gray-500">
          <HiUsers className="w-12 h-12 mx-auto mb-4 text-gray-400" />
          <p>سيتم تنفيذ هذه الصفحة قريباً</p>
        </div>
      </Card>
    </div>
  );
}
