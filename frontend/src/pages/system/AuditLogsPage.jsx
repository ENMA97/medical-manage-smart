import React from 'react';
import { Card, CardHeader, Button } from '../../components/ui';
import { HiDownload, HiClipboardList } from 'react-icons/hi';

export default function AuditLogsPage() {
  return (
    <div className="space-y-6" dir="rtl">
      <div className="flex justify-between items-center">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">سجل المراجعة</h1>
          <p className="text-gray-600 mt-1">سجل جميع العمليات في النظام</p>
        </div>
        <Button icon={HiDownload} variant="secondary">تصدير السجل</Button>
      </div>

      <Card>
        <CardHeader title="سجل الأحداث" />
        <div className="text-center py-12 text-gray-500">
          <HiClipboardList className="w-12 h-12 mx-auto mb-4 text-gray-400" />
          <p>سيتم تنفيذ هذه الصفحة قريباً</p>
        </div>
      </Card>
    </div>
  );
}
