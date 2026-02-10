import React from 'react';
import { Card, CardHeader, Button } from '../../components/ui';
import { HiDownload, HiChartBar } from 'react-icons/hi';

export default function FinanceReportsPage() {
  return (
    <div className="space-y-6" dir="rtl">
      <div className="flex justify-between items-center">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">التقارير المالية</h1>
          <p className="text-gray-600 mt-1">تقارير الربحية والتحليل المالي</p>
        </div>
        <Button icon={HiDownload}>تصدير التقرير</Button>
      </div>

      <Card>
        <CardHeader title="التقارير المتاحة" />
        <div className="text-center py-12 text-gray-500">
          <HiChartBar className="w-12 h-12 mx-auto mb-4 text-gray-400" />
          <p>سيتم تنفيذ هذه الصفحة قريباً</p>
        </div>
      </Card>
    </div>
  );
}
