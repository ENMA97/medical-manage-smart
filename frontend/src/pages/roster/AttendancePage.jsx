import React from 'react';
import { Card, CardHeader, Button } from '../../components/ui';
import { HiRefresh, HiUserGroup } from 'react-icons/hi';

export default function AttendancePage() {
  return (
    <div className="space-y-6" dir="rtl">
      <div className="flex justify-between items-center">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">الحضور والانصراف</h1>
          <p className="text-gray-600 mt-1">سجلات الحضور والانصراف اليومية</p>
        </div>
        <Button icon={HiRefresh}>مزامنة البيانات</Button>
      </div>

      <Card>
        <CardHeader title="سجل الحضور اليوم" />
        <div className="text-center py-12 text-gray-500">
          <HiUserGroup className="w-12 h-12 mx-auto mb-4 text-gray-400" />
          <p>سيتم تنفيذ هذه الصفحة قريباً</p>
        </div>
      </Card>
    </div>
  );
}
