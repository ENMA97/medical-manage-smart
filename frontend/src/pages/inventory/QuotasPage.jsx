import React from 'react';
import { Card, CardHeader, Button } from '../../components/ui';
import { HiPlus, HiChartPie } from 'react-icons/hi';

export default function QuotasPage() {
  return (
    <div className="space-y-6" dir="rtl">
      <div className="flex justify-between items-center">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">إدارة الحصص</h1>
          <p className="text-gray-600 mt-1">إدارة حصص الأقسام من المخزون</p>
        </div>
        <Button icon={HiPlus}>إضافة حصة</Button>
      </div>

      <Card>
        <CardHeader title="قائمة الحصص" />
        <div className="text-center py-12 text-gray-500">
          <HiChartPie className="w-12 h-12 mx-auto mb-4 text-gray-400" />
          <p>سيتم تنفيذ هذه الصفحة قريباً</p>
        </div>
      </Card>
    </div>
  );
}
