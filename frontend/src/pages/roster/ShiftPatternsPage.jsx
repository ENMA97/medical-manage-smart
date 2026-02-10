import React from 'react';
import { Card, CardHeader, Button } from '../../components/ui';
import { HiPlus, HiClock } from 'react-icons/hi';

export default function ShiftPatternsPage() {
  return (
    <div className="space-y-6" dir="rtl">
      <div className="flex justify-between items-center">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">أنماط الورديات</h1>
          <p className="text-gray-600 mt-1">إدارة أنماط وجداول الورديات</p>
        </div>
        <Button icon={HiPlus}>إضافة نمط</Button>
      </div>

      <Card>
        <CardHeader title="قائمة أنماط الورديات" />
        <div className="text-center py-12 text-gray-500">
          <HiClock className="w-12 h-12 mx-auto mb-4 text-gray-400" />
          <p>سيتم تنفيذ هذه الصفحة قريباً</p>
        </div>
      </Card>
    </div>
  );
}
