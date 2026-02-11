import React, { useState, useEffect } from 'react';
import { Card, CardHeader, Button, Modal, Badge } from '../../components/ui';
import {
  HiDownload, HiChartBar, HiCurrencyDollar, HiTrendingUp,
  HiTrendingDown, HiUsers, HiClipboardList, HiCalendar,
  HiDocumentReport, HiFilter, HiRefresh, HiChartPie,
  HiOfficeBuilding, HiCash, HiReceiptTax, HiPresentationChartBar
} from 'react-icons/hi';
import { financeReportsApi, serviceProfitabilityApi } from '../../services/financeApi';

// Mock data for reports
const mockDashboard = {
  totalRevenue: 2450000,
  totalExpenses: 1680000,
  netProfit: 770000,
  profitMargin: 31.4,
  pendingClaims: 145,
  pendingClaimsAmount: 385000,
  collectedThisMonth: 890000,
  outstandingBalance: 520000,
  revenueChange: 12.5,
  expenseChange: 8.2,
  patientCount: 3250,
  averageRevenuePerPatient: 754
};

const mockRevenueByService = [
  { service: 'الكشف الطبي', revenue: 450000, count: 3000, percentage: 18.4 },
  { service: 'الأشعة التشخيصية', revenue: 380000, count: 950, percentage: 15.5 },
  { service: 'التحاليل المخبرية', revenue: 320000, count: 4200, percentage: 13.1 },
  { service: 'العمليات الجراحية', revenue: 580000, count: 120, percentage: 23.7 },
  { service: 'طب الأسنان', revenue: 290000, count: 580, percentage: 11.8 },
  { service: 'العلاج الطبيعي', revenue: 180000, count: 600, percentage: 7.3 },
  { service: 'خدمات أخرى', revenue: 250000, count: 850, percentage: 10.2 }
];

const mockRevenueByDoctor = [
  { doctor: 'د. أحمد محمد', specialty: 'الباطنية', revenue: 320000, patients: 420, avgPerPatient: 762 },
  { doctor: 'د. سارة العلي', specialty: 'طب الأطفال', revenue: 280000, patients: 380, avgPerPatient: 737 },
  { doctor: 'د. خالد الشمري', specialty: 'الجراحة العامة', revenue: 450000, patients: 180, avgPerPatient: 2500 },
  { doctor: 'د. فاطمة أحمد', specialty: 'النساء والتوليد', revenue: 380000, patients: 290, avgPerPatient: 1310 },
  { doctor: 'د. محمد العتيبي', specialty: 'العظام', revenue: 340000, patients: 220, avgPerPatient: 1545 }
];

const mockRevenueByInsurance = [
  { company: 'التعاونية', revenue: 680000, claims: 450, collected: 620000, pending: 60000, rejectionRate: 4.2 },
  { company: 'بوبا العربية', revenue: 520000, claims: 320, collected: 480000, pending: 40000, rejectionRate: 3.8 },
  { company: 'ميدغلف', revenue: 420000, claims: 280, collected: 380000, pending: 40000, rejectionRate: 5.1 },
  { company: 'الراجحي تكافل', revenue: 380000, claims: 240, collected: 340000, pending: 40000, rejectionRate: 4.5 },
  { company: 'تكافل الجزيرة', revenue: 290000, claims: 180, collected: 250000, pending: 40000, rejectionRate: 6.2 }
];

const mockExpensesByCostCenter = [
  { costCenter: 'قسم العمليات', budget: 500000, actual: 480000, variance: 20000, utilizationRate: 96 },
  { costCenter: 'قسم الأشعة', budget: 350000, actual: 320000, variance: 30000, utilizationRate: 91.4 },
  { costCenter: 'المختبرات', budget: 280000, actual: 290000, variance: -10000, utilizationRate: 103.6 },
  { costCenter: 'الإدارة العامة', budget: 200000, actual: 185000, variance: 15000, utilizationRate: 92.5 },
  { costCenter: 'قسم الطوارئ', budget: 180000, actual: 175000, variance: 5000, utilizationRate: 97.2 }
];

const mockClaimsAging = [
  { range: '0-30 يوم', count: 85, amount: 125000, percentage: 32.5 },
  { range: '31-60 يوم', count: 42, amount: 98000, percentage: 25.5 },
  { range: '61-90 يوم', count: 28, amount: 72000, percentage: 18.7 },
  { range: '91-120 يوم', count: 18, amount: 48000, percentage: 12.5 },
  { range: 'أكثر من 120 يوم', count: 12, amount: 42000, percentage: 10.8 }
];

const mockCommissionsByDoctor = [
  { doctor: 'د. خالد الشمري', grossCommission: 45000, adjustments: -2000, netCommission: 43000, servicesCount: 180 },
  { doctor: 'د. فاطمة أحمد', grossCommission: 38000, adjustments: -1500, netCommission: 36500, servicesCount: 290 },
  { doctor: 'د. محمد العتيبي', grossCommission: 34000, adjustments: -1000, netCommission: 33000, servicesCount: 220 },
  { doctor: 'د. أحمد محمد', grossCommission: 32000, adjustments: -800, netCommission: 31200, servicesCount: 420 },
  { doctor: 'د. سارة العلي', grossCommission: 28000, adjustments: -500, netCommission: 27500, servicesCount: 380 }
];

const mockProfitabilityByService = [
  { service: 'العمليات الجراحية', revenue: 580000, cost: 320000, profit: 260000, margin: 44.8 },
  { service: 'الأشعة التشخيصية', revenue: 380000, cost: 180000, profit: 200000, margin: 52.6 },
  { service: 'الكشف الطبي', revenue: 450000, cost: 220000, profit: 230000, margin: 51.1 },
  { service: 'التحاليل المخبرية', revenue: 320000, cost: 160000, profit: 160000, margin: 50.0 },
  { service: 'طب الأسنان', revenue: 290000, cost: 170000, profit: 120000, margin: 41.4 }
];

const reportTypes = [
  { id: 'dashboard', name: 'لوحة المعلومات', icon: HiPresentationChartBar, description: 'نظرة عامة على الأداء المالي' },
  { id: 'revenue', name: 'تقارير الإيرادات', icon: HiTrendingUp, description: 'تحليل الإيرادات حسب الخدمة والطبيب والتأمين' },
  { id: 'expenses', name: 'تقارير المصروفات', icon: HiTrendingDown, description: 'تحليل المصروفات حسب مركز التكلفة' },
  { id: 'insurance', name: 'تقارير التأمين', icon: HiReceiptTax, description: 'أداء شركات التأمين وتقادم المطالبات' },
  { id: 'commissions', name: 'تقارير العمولات', icon: HiCash, description: 'عمولات الأطباء والتسويات' },
  { id: 'profitability', name: 'تقارير الربحية', icon: HiChartPie, description: 'تحليل ربحية الخدمات والأقسام' }
];

export default function FinanceReportsPage() {
  const [loading, setLoading] = useState(false);
  const [activeReport, setActiveReport] = useState('dashboard');
  const [dateRange, setDateRange] = useState({ from: '', to: '' });
  const [showExportModal, setShowExportModal] = useState(false);
  const [exportFormat, setExportFormat] = useState('excel');

  // KPI Card Component
  const KPICard = ({ title, value, change, changeType, icon: Icon, color }) => (
    <Card className="p-4">
      <div className="flex items-center justify-between">
        <div>
          <p className="text-sm text-gray-500">{title}</p>
          <p className="text-2xl font-bold mt-1">{value}</p>
          {change !== undefined && (
            <div className={`flex items-center mt-2 text-sm ${changeType === 'positive' ? 'text-green-600' : 'text-red-600'}`}>
              {changeType === 'positive' ? <HiTrendingUp className="w-4 h-4 ml-1" /> : <HiTrendingDown className="w-4 h-4 ml-1" />}
              <span>{change}% من الشهر السابق</span>
            </div>
          )}
        </div>
        <div className={`p-3 rounded-full ${color}`}>
          <Icon className="w-6 h-6 text-white" />
        </div>
      </div>
    </Card>
  );

  // Progress Bar Component
  const ProgressBar = ({ value, max, color = 'bg-blue-500' }) => {
    const percentage = Math.min((value / max) * 100, 100);
    return (
      <div className="w-full bg-gray-200 rounded-full h-2">
        <div className={`${color} h-2 rounded-full`} style={{ width: `${percentage}%` }} />
      </div>
    );
  };

  // Format currency
  const formatCurrency = (amount) => {
    return new Intl.NumberFormat('ar-SA', {
      style: 'currency',
      currency: 'SAR',
      minimumFractionDigits: 0
    }).format(amount);
  };

  // Dashboard View
  const DashboardView = () => (
    <div className="space-y-6">
      {/* KPI Cards */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <KPICard
          title="إجمالي الإيرادات"
          value={formatCurrency(mockDashboard.totalRevenue)}
          change={mockDashboard.revenueChange}
          changeType="positive"
          icon={HiCurrencyDollar}
          color="bg-green-500"
        />
        <KPICard
          title="إجمالي المصروفات"
          value={formatCurrency(mockDashboard.totalExpenses)}
          change={mockDashboard.expenseChange}
          changeType="negative"
          icon={HiTrendingDown}
          color="bg-red-500"
        />
        <KPICard
          title="صافي الربح"
          value={formatCurrency(mockDashboard.netProfit)}
          icon={HiChartBar}
          color="bg-blue-500"
        />
        <KPICard
          title="هامش الربح"
          value={`${mockDashboard.profitMargin}%`}
          icon={HiChartPie}
          color="bg-purple-500"
        />
      </div>

      {/* Secondary KPIs */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <Card className="p-4">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm text-gray-500">مطالبات معلقة</p>
              <p className="text-xl font-bold mt-1">{mockDashboard.pendingClaims}</p>
              <p className="text-sm text-gray-400 mt-1">{formatCurrency(mockDashboard.pendingClaimsAmount)}</p>
            </div>
            <HiClipboardList className="w-8 h-8 text-yellow-500" />
          </div>
        </Card>
        <Card className="p-4">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm text-gray-500">المحصل هذا الشهر</p>
              <p className="text-xl font-bold mt-1">{formatCurrency(mockDashboard.collectedThisMonth)}</p>
            </div>
            <HiCash className="w-8 h-8 text-green-500" />
          </div>
        </Card>
        <Card className="p-4">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm text-gray-500">الأرصدة المستحقة</p>
              <p className="text-xl font-bold mt-1">{formatCurrency(mockDashboard.outstandingBalance)}</p>
            </div>
            <HiReceiptTax className="w-8 h-8 text-orange-500" />
          </div>
        </Card>
        <Card className="p-4">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm text-gray-500">عدد المرضى</p>
              <p className="text-xl font-bold mt-1">{mockDashboard.patientCount.toLocaleString('ar-SA')}</p>
              <p className="text-sm text-gray-400 mt-1">{formatCurrency(mockDashboard.averageRevenuePerPatient)} / مريض</p>
            </div>
            <HiUsers className="w-8 h-8 text-blue-500" />
          </div>
        </Card>
      </div>

      {/* Charts Section */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {/* Revenue by Service */}
        <Card>
          <CardHeader title="الإيرادات حسب الخدمة" />
          <div className="p-4 space-y-4">
            {mockRevenueByService.slice(0, 5).map((item, index) => (
              <div key={index}>
                <div className="flex justify-between text-sm mb-1">
                  <span>{item.service}</span>
                  <span className="font-medium">{formatCurrency(item.revenue)}</span>
                </div>
                <ProgressBar value={item.percentage} max={25} color="bg-blue-500" />
              </div>
            ))}
          </div>
        </Card>

        {/* Claims Aging */}
        <Card>
          <CardHeader title="تقادم المطالبات" />
          <div className="p-4 space-y-4">
            {mockClaimsAging.map((item, index) => (
              <div key={index}>
                <div className="flex justify-between text-sm mb-1">
                  <span>{item.range}</span>
                  <span className="font-medium">{formatCurrency(item.amount)} ({item.count} مطالبة)</span>
                </div>
                <ProgressBar
                  value={item.percentage}
                  max={35}
                  color={index <= 1 ? 'bg-green-500' : index <= 2 ? 'bg-yellow-500' : 'bg-red-500'}
                />
              </div>
            ))}
          </div>
        </Card>
      </div>

      {/* Top Performers */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {/* Top Doctors */}
        <Card>
          <CardHeader title="أعلى الأطباء إيراداً" />
          <div className="overflow-x-auto">
            <table className="min-w-full divide-y divide-gray-200">
              <thead className="bg-gray-50">
                <tr>
                  <th className="px-4 py-3 text-right text-xs font-medium text-gray-500">الطبيب</th>
                  <th className="px-4 py-3 text-right text-xs font-medium text-gray-500">التخصص</th>
                  <th className="px-4 py-3 text-right text-xs font-medium text-gray-500">الإيرادات</th>
                </tr>
              </thead>
              <tbody className="bg-white divide-y divide-gray-200">
                {mockRevenueByDoctor.slice(0, 5).map((doctor, index) => (
                  <tr key={index} className="hover:bg-gray-50">
                    <td className="px-4 py-3 text-sm font-medium text-gray-900">{doctor.doctor}</td>
                    <td className="px-4 py-3 text-sm text-gray-500">{doctor.specialty}</td>
                    <td className="px-4 py-3 text-sm font-medium text-green-600">{formatCurrency(doctor.revenue)}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </Card>

        {/* Top Insurance Companies */}
        <Card>
          <CardHeader title="أداء شركات التأمين" />
          <div className="overflow-x-auto">
            <table className="min-w-full divide-y divide-gray-200">
              <thead className="bg-gray-50">
                <tr>
                  <th className="px-4 py-3 text-right text-xs font-medium text-gray-500">الشركة</th>
                  <th className="px-4 py-3 text-right text-xs font-medium text-gray-500">الإيرادات</th>
                  <th className="px-4 py-3 text-right text-xs font-medium text-gray-500">نسبة الرفض</th>
                </tr>
              </thead>
              <tbody className="bg-white divide-y divide-gray-200">
                {mockRevenueByInsurance.slice(0, 5).map((company, index) => (
                  <tr key={index} className="hover:bg-gray-50">
                    <td className="px-4 py-3 text-sm font-medium text-gray-900">{company.company}</td>
                    <td className="px-4 py-3 text-sm font-medium text-green-600">{formatCurrency(company.revenue)}</td>
                    <td className="px-4 py-3 text-sm">
                      <Badge variant={company.rejectionRate < 5 ? 'success' : company.rejectionRate < 6 ? 'warning' : 'danger'}>
                        {company.rejectionRate}%
                      </Badge>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </Card>
      </div>
    </div>
  );

  // Revenue Reports View
  const RevenueReportsView = () => (
    <div className="space-y-6">
      {/* Revenue by Service */}
      <Card>
        <CardHeader title="الإيرادات حسب الخدمة" />
        <div className="overflow-x-auto">
          <table className="min-w-full divide-y divide-gray-200">
            <thead className="bg-gray-50">
              <tr>
                <th className="px-6 py-3 text-right text-xs font-medium text-gray-500">الخدمة</th>
                <th className="px-6 py-3 text-right text-xs font-medium text-gray-500">الإيرادات</th>
                <th className="px-6 py-3 text-right text-xs font-medium text-gray-500">عدد الخدمات</th>
                <th className="px-6 py-3 text-right text-xs font-medium text-gray-500">النسبة</th>
              </tr>
            </thead>
            <tbody className="bg-white divide-y divide-gray-200">
              {mockRevenueByService.map((item, index) => (
                <tr key={index} className="hover:bg-gray-50">
                  <td className="px-6 py-4 text-sm font-medium text-gray-900">{item.service}</td>
                  <td className="px-6 py-4 text-sm font-medium text-green-600">{formatCurrency(item.revenue)}</td>
                  <td className="px-6 py-4 text-sm text-gray-500">{item.count.toLocaleString('ar-SA')}</td>
                  <td className="px-6 py-4 text-sm">
                    <div className="flex items-center">
                      <span className="ml-2">{item.percentage}%</span>
                      <div className="w-24">
                        <ProgressBar value={item.percentage} max={25} color="bg-blue-500" />
                      </div>
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
            <tfoot className="bg-gray-100">
              <tr>
                <td className="px-6 py-4 text-sm font-bold text-gray-900">الإجمالي</td>
                <td className="px-6 py-4 text-sm font-bold text-green-600">
                  {formatCurrency(mockRevenueByService.reduce((sum, item) => sum + item.revenue, 0))}
                </td>
                <td className="px-6 py-4 text-sm font-bold text-gray-900">
                  {mockRevenueByService.reduce((sum, item) => sum + item.count, 0).toLocaleString('ar-SA')}
                </td>
                <td className="px-6 py-4 text-sm font-bold text-gray-900">100%</td>
              </tr>
            </tfoot>
          </table>
        </div>
      </Card>

      {/* Revenue by Doctor */}
      <Card>
        <CardHeader title="الإيرادات حسب الطبيب" />
        <div className="overflow-x-auto">
          <table className="min-w-full divide-y divide-gray-200">
            <thead className="bg-gray-50">
              <tr>
                <th className="px-6 py-3 text-right text-xs font-medium text-gray-500">الطبيب</th>
                <th className="px-6 py-3 text-right text-xs font-medium text-gray-500">التخصص</th>
                <th className="px-6 py-3 text-right text-xs font-medium text-gray-500">الإيرادات</th>
                <th className="px-6 py-3 text-right text-xs font-medium text-gray-500">عدد المرضى</th>
                <th className="px-6 py-3 text-right text-xs font-medium text-gray-500">متوسط / مريض</th>
              </tr>
            </thead>
            <tbody className="bg-white divide-y divide-gray-200">
              {mockRevenueByDoctor.map((doctor, index) => (
                <tr key={index} className="hover:bg-gray-50">
                  <td className="px-6 py-4 text-sm font-medium text-gray-900">{doctor.doctor}</td>
                  <td className="px-6 py-4 text-sm text-gray-500">{doctor.specialty}</td>
                  <td className="px-6 py-4 text-sm font-medium text-green-600">{formatCurrency(doctor.revenue)}</td>
                  <td className="px-6 py-4 text-sm text-gray-500">{doctor.patients.toLocaleString('ar-SA')}</td>
                  <td className="px-6 py-4 text-sm text-blue-600">{formatCurrency(doctor.avgPerPatient)}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </Card>

      {/* Revenue by Insurance */}
      <Card>
        <CardHeader title="الإيرادات حسب شركة التأمين" />
        <div className="overflow-x-auto">
          <table className="min-w-full divide-y divide-gray-200">
            <thead className="bg-gray-50">
              <tr>
                <th className="px-6 py-3 text-right text-xs font-medium text-gray-500">الشركة</th>
                <th className="px-6 py-3 text-right text-xs font-medium text-gray-500">الإيرادات</th>
                <th className="px-6 py-3 text-right text-xs font-medium text-gray-500">عدد المطالبات</th>
                <th className="px-6 py-3 text-right text-xs font-medium text-gray-500">المحصل</th>
                <th className="px-6 py-3 text-right text-xs font-medium text-gray-500">المعلق</th>
              </tr>
            </thead>
            <tbody className="bg-white divide-y divide-gray-200">
              {mockRevenueByInsurance.map((company, index) => (
                <tr key={index} className="hover:bg-gray-50">
                  <td className="px-6 py-4 text-sm font-medium text-gray-900">{company.company}</td>
                  <td className="px-6 py-4 text-sm font-medium text-green-600">{formatCurrency(company.revenue)}</td>
                  <td className="px-6 py-4 text-sm text-gray-500">{company.claims.toLocaleString('ar-SA')}</td>
                  <td className="px-6 py-4 text-sm text-green-600">{formatCurrency(company.collected)}</td>
                  <td className="px-6 py-4 text-sm text-yellow-600">{formatCurrency(company.pending)}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </Card>
    </div>
  );

  // Expenses Reports View
  const ExpensesReportsView = () => (
    <div className="space-y-6">
      {/* Budget vs Actual */}
      <Card>
        <CardHeader title="الميزانية مقابل الفعلي حسب مركز التكلفة" />
        <div className="overflow-x-auto">
          <table className="min-w-full divide-y divide-gray-200">
            <thead className="bg-gray-50">
              <tr>
                <th className="px-6 py-3 text-right text-xs font-medium text-gray-500">مركز التكلفة</th>
                <th className="px-6 py-3 text-right text-xs font-medium text-gray-500">الميزانية</th>
                <th className="px-6 py-3 text-right text-xs font-medium text-gray-500">الفعلي</th>
                <th className="px-6 py-3 text-right text-xs font-medium text-gray-500">الفرق</th>
                <th className="px-6 py-3 text-right text-xs font-medium text-gray-500">نسبة الاستخدام</th>
              </tr>
            </thead>
            <tbody className="bg-white divide-y divide-gray-200">
              {mockExpensesByCostCenter.map((item, index) => (
                <tr key={index} className="hover:bg-gray-50">
                  <td className="px-6 py-4 text-sm font-medium text-gray-900">{item.costCenter}</td>
                  <td className="px-6 py-4 text-sm text-gray-500">{formatCurrency(item.budget)}</td>
                  <td className="px-6 py-4 text-sm text-gray-900">{formatCurrency(item.actual)}</td>
                  <td className="px-6 py-4 text-sm">
                    <span className={item.variance >= 0 ? 'text-green-600' : 'text-red-600'}>
                      {item.variance >= 0 ? '+' : ''}{formatCurrency(item.variance)}
                    </span>
                  </td>
                  <td className="px-6 py-4">
                    <div className="flex items-center">
                      <span className={`ml-2 text-sm font-medium ${item.utilizationRate > 100 ? 'text-red-600' : 'text-gray-900'}`}>
                        {item.utilizationRate}%
                      </span>
                      <div className="w-24">
                        <ProgressBar
                          value={item.utilizationRate}
                          max={110}
                          color={item.utilizationRate > 100 ? 'bg-red-500' : item.utilizationRate > 90 ? 'bg-yellow-500' : 'bg-green-500'}
                        />
                      </div>
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
            <tfoot className="bg-gray-100">
              <tr>
                <td className="px-6 py-4 text-sm font-bold text-gray-900">الإجمالي</td>
                <td className="px-6 py-4 text-sm font-bold text-gray-500">
                  {formatCurrency(mockExpensesByCostCenter.reduce((sum, item) => sum + item.budget, 0))}
                </td>
                <td className="px-6 py-4 text-sm font-bold text-gray-900">
                  {formatCurrency(mockExpensesByCostCenter.reduce((sum, item) => sum + item.actual, 0))}
                </td>
                <td className="px-6 py-4 text-sm font-bold text-green-600">
                  {formatCurrency(mockExpensesByCostCenter.reduce((sum, item) => sum + item.variance, 0))}
                </td>
                <td className="px-6 py-4 text-sm font-bold text-gray-900">-</td>
              </tr>
            </tfoot>
          </table>
        </div>
      </Card>

      {/* Expense Analysis Summary */}
      <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
        <Card className="p-4">
          <div className="text-center">
            <HiOfficeBuilding className="w-8 h-8 mx-auto text-blue-500 mb-2" />
            <p className="text-sm text-gray-500">إجمالي مراكز التكلفة</p>
            <p className="text-2xl font-bold">{mockExpensesByCostCenter.length}</p>
          </div>
        </Card>
        <Card className="p-4">
          <div className="text-center">
            <HiTrendingDown className="w-8 h-8 mx-auto text-green-500 mb-2" />
            <p className="text-sm text-gray-500">تحت الميزانية</p>
            <p className="text-2xl font-bold text-green-600">
              {mockExpensesByCostCenter.filter(c => c.variance > 0).length}
            </p>
          </div>
        </Card>
        <Card className="p-4">
          <div className="text-center">
            <HiTrendingUp className="w-8 h-8 mx-auto text-red-500 mb-2" />
            <p className="text-sm text-gray-500">فوق الميزانية</p>
            <p className="text-2xl font-bold text-red-600">
              {mockExpensesByCostCenter.filter(c => c.variance < 0).length}
            </p>
          </div>
        </Card>
      </div>
    </div>
  );

  // Insurance Reports View
  const InsuranceReportsView = () => (
    <div className="space-y-6">
      {/* Insurance Performance */}
      <Card>
        <CardHeader title="أداء شركات التأمين" />
        <div className="overflow-x-auto">
          <table className="min-w-full divide-y divide-gray-200">
            <thead className="bg-gray-50">
              <tr>
                <th className="px-6 py-3 text-right text-xs font-medium text-gray-500">شركة التأمين</th>
                <th className="px-6 py-3 text-right text-xs font-medium text-gray-500">إجمالي المطالبات</th>
                <th className="px-6 py-3 text-right text-xs font-medium text-gray-500">المحصل</th>
                <th className="px-6 py-3 text-right text-xs font-medium text-gray-500">المعلق</th>
                <th className="px-6 py-3 text-right text-xs font-medium text-gray-500">نسبة الرفض</th>
                <th className="px-6 py-3 text-right text-xs font-medium text-gray-500">الأداء</th>
              </tr>
            </thead>
            <tbody className="bg-white divide-y divide-gray-200">
              {mockRevenueByInsurance.map((company, index) => (
                <tr key={index} className="hover:bg-gray-50">
                  <td className="px-6 py-4 text-sm font-medium text-gray-900">{company.company}</td>
                  <td className="px-6 py-4 text-sm text-gray-900">{formatCurrency(company.revenue)}</td>
                  <td className="px-6 py-4 text-sm text-green-600">{formatCurrency(company.collected)}</td>
                  <td className="px-6 py-4 text-sm text-yellow-600">{formatCurrency(company.pending)}</td>
                  <td className="px-6 py-4 text-sm">
                    <Badge variant={company.rejectionRate < 5 ? 'success' : company.rejectionRate < 6 ? 'warning' : 'danger'}>
                      {company.rejectionRate}%
                    </Badge>
                  </td>
                  <td className="px-6 py-4">
                    <div className="w-24">
                      <ProgressBar
                        value={100 - company.rejectionRate}
                        max={100}
                        color={company.rejectionRate < 5 ? 'bg-green-500' : company.rejectionRate < 6 ? 'bg-yellow-500' : 'bg-red-500'}
                      />
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </Card>

      {/* Claims Aging Report */}
      <Card>
        <CardHeader title="تقرير تقادم المطالبات" />
        <div className="overflow-x-auto">
          <table className="min-w-full divide-y divide-gray-200">
            <thead className="bg-gray-50">
              <tr>
                <th className="px-6 py-3 text-right text-xs font-medium text-gray-500">الفترة</th>
                <th className="px-6 py-3 text-right text-xs font-medium text-gray-500">عدد المطالبات</th>
                <th className="px-6 py-3 text-right text-xs font-medium text-gray-500">المبلغ</th>
                <th className="px-6 py-3 text-right text-xs font-medium text-gray-500">النسبة</th>
                <th className="px-6 py-3 text-right text-xs font-medium text-gray-500">الحالة</th>
              </tr>
            </thead>
            <tbody className="bg-white divide-y divide-gray-200">
              {mockClaimsAging.map((item, index) => (
                <tr key={index} className="hover:bg-gray-50">
                  <td className="px-6 py-4 text-sm font-medium text-gray-900">{item.range}</td>
                  <td className="px-6 py-4 text-sm text-gray-500">{item.count}</td>
                  <td className="px-6 py-4 text-sm text-gray-900">{formatCurrency(item.amount)}</td>
                  <td className="px-6 py-4 text-sm text-gray-500">{item.percentage}%</td>
                  <td className="px-6 py-4">
                    <Badge variant={index <= 1 ? 'success' : index <= 2 ? 'warning' : 'danger'}>
                      {index <= 1 ? 'جيد' : index <= 2 ? 'تحذير' : 'حرج'}
                    </Badge>
                  </td>
                </tr>
              ))}
            </tbody>
            <tfoot className="bg-gray-100">
              <tr>
                <td className="px-6 py-4 text-sm font-bold text-gray-900">الإجمالي</td>
                <td className="px-6 py-4 text-sm font-bold text-gray-900">
                  {mockClaimsAging.reduce((sum, item) => sum + item.count, 0)}
                </td>
                <td className="px-6 py-4 text-sm font-bold text-gray-900">
                  {formatCurrency(mockClaimsAging.reduce((sum, item) => sum + item.amount, 0))}
                </td>
                <td className="px-6 py-4 text-sm font-bold text-gray-900">100%</td>
                <td className="px-6 py-4">-</td>
              </tr>
            </tfoot>
          </table>
        </div>
      </Card>
    </div>
  );

  // Commissions Reports View
  const CommissionsReportsView = () => (
    <div className="space-y-6">
      <Card>
        <CardHeader title="تقرير عمولات الأطباء" />
        <div className="overflow-x-auto">
          <table className="min-w-full divide-y divide-gray-200">
            <thead className="bg-gray-50">
              <tr>
                <th className="px-6 py-3 text-right text-xs font-medium text-gray-500">الطبيب</th>
                <th className="px-6 py-3 text-right text-xs font-medium text-gray-500">إجمالي العمولة</th>
                <th className="px-6 py-3 text-right text-xs font-medium text-gray-500">التسويات</th>
                <th className="px-6 py-3 text-right text-xs font-medium text-gray-500">صافي العمولة</th>
                <th className="px-6 py-3 text-right text-xs font-medium text-gray-500">عدد الخدمات</th>
              </tr>
            </thead>
            <tbody className="bg-white divide-y divide-gray-200">
              {mockCommissionsByDoctor.map((doctor, index) => (
                <tr key={index} className="hover:bg-gray-50">
                  <td className="px-6 py-4 text-sm font-medium text-gray-900">{doctor.doctor}</td>
                  <td className="px-6 py-4 text-sm text-gray-900">{formatCurrency(doctor.grossCommission)}</td>
                  <td className="px-6 py-4 text-sm text-red-600">{formatCurrency(doctor.adjustments)}</td>
                  <td className="px-6 py-4 text-sm font-medium text-green-600">{formatCurrency(doctor.netCommission)}</td>
                  <td className="px-6 py-4 text-sm text-gray-500">{doctor.servicesCount}</td>
                </tr>
              ))}
            </tbody>
            <tfoot className="bg-gray-100">
              <tr>
                <td className="px-6 py-4 text-sm font-bold text-gray-900">الإجمالي</td>
                <td className="px-6 py-4 text-sm font-bold text-gray-900">
                  {formatCurrency(mockCommissionsByDoctor.reduce((sum, d) => sum + d.grossCommission, 0))}
                </td>
                <td className="px-6 py-4 text-sm font-bold text-red-600">
                  {formatCurrency(mockCommissionsByDoctor.reduce((sum, d) => sum + d.adjustments, 0))}
                </td>
                <td className="px-6 py-4 text-sm font-bold text-green-600">
                  {formatCurrency(mockCommissionsByDoctor.reduce((sum, d) => sum + d.netCommission, 0))}
                </td>
                <td className="px-6 py-4 text-sm font-bold text-gray-900">
                  {mockCommissionsByDoctor.reduce((sum, d) => sum + d.servicesCount, 0)}
                </td>
              </tr>
            </tfoot>
          </table>
        </div>
      </Card>

      {/* Commission Summary Cards */}
      <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
        <Card className="p-4">
          <div className="text-center">
            <HiCash className="w-8 h-8 mx-auto text-green-500 mb-2" />
            <p className="text-sm text-gray-500">إجمالي العمولات</p>
            <p className="text-2xl font-bold text-green-600">
              {formatCurrency(mockCommissionsByDoctor.reduce((sum, d) => sum + d.grossCommission, 0))}
            </p>
          </div>
        </Card>
        <Card className="p-4">
          <div className="text-center">
            <HiTrendingDown className="w-8 h-8 mx-auto text-red-500 mb-2" />
            <p className="text-sm text-gray-500">إجمالي التسويات (Clawback)</p>
            <p className="text-2xl font-bold text-red-600">
              {formatCurrency(Math.abs(mockCommissionsByDoctor.reduce((sum, d) => sum + d.adjustments, 0)))}
            </p>
          </div>
        </Card>
        <Card className="p-4">
          <div className="text-center">
            <HiCurrencyDollar className="w-8 h-8 mx-auto text-blue-500 mb-2" />
            <p className="text-sm text-gray-500">صافي العمولات المستحقة</p>
            <p className="text-2xl font-bold text-blue-600">
              {formatCurrency(mockCommissionsByDoctor.reduce((sum, d) => sum + d.netCommission, 0))}
            </p>
          </div>
        </Card>
      </div>
    </div>
  );

  // Profitability Reports View
  const ProfitabilityReportsView = () => (
    <div className="space-y-6">
      <Card>
        <CardHeader title="تحليل ربحية الخدمات" />
        <div className="overflow-x-auto">
          <table className="min-w-full divide-y divide-gray-200">
            <thead className="bg-gray-50">
              <tr>
                <th className="px-6 py-3 text-right text-xs font-medium text-gray-500">الخدمة</th>
                <th className="px-6 py-3 text-right text-xs font-medium text-gray-500">الإيرادات</th>
                <th className="px-6 py-3 text-right text-xs font-medium text-gray-500">التكلفة</th>
                <th className="px-6 py-3 text-right text-xs font-medium text-gray-500">الربح</th>
                <th className="px-6 py-3 text-right text-xs font-medium text-gray-500">هامش الربح</th>
              </tr>
            </thead>
            <tbody className="bg-white divide-y divide-gray-200">
              {mockProfitabilityByService.map((service, index) => (
                <tr key={index} className="hover:bg-gray-50">
                  <td className="px-6 py-4 text-sm font-medium text-gray-900">{service.service}</td>
                  <td className="px-6 py-4 text-sm text-gray-900">{formatCurrency(service.revenue)}</td>
                  <td className="px-6 py-4 text-sm text-red-600">{formatCurrency(service.cost)}</td>
                  <td className="px-6 py-4 text-sm font-medium text-green-600">{formatCurrency(service.profit)}</td>
                  <td className="px-6 py-4">
                    <div className="flex items-center">
                      <Badge variant={service.margin >= 50 ? 'success' : service.margin >= 40 ? 'warning' : 'danger'}>
                        {service.margin}%
                      </Badge>
                      <div className="w-20 mr-2">
                        <ProgressBar
                          value={service.margin}
                          max={60}
                          color={service.margin >= 50 ? 'bg-green-500' : service.margin >= 40 ? 'bg-yellow-500' : 'bg-red-500'}
                        />
                      </div>
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
            <tfoot className="bg-gray-100">
              <tr>
                <td className="px-6 py-4 text-sm font-bold text-gray-900">الإجمالي</td>
                <td className="px-6 py-4 text-sm font-bold text-gray-900">
                  {formatCurrency(mockProfitabilityByService.reduce((sum, s) => sum + s.revenue, 0))}
                </td>
                <td className="px-6 py-4 text-sm font-bold text-red-600">
                  {formatCurrency(mockProfitabilityByService.reduce((sum, s) => sum + s.cost, 0))}
                </td>
                <td className="px-6 py-4 text-sm font-bold text-green-600">
                  {formatCurrency(mockProfitabilityByService.reduce((sum, s) => sum + s.profit, 0))}
                </td>
                <td className="px-6 py-4 text-sm font-bold">
                  <Badge variant="info">
                    {(mockProfitabilityByService.reduce((sum, s) => sum + s.profit, 0) /
                      mockProfitabilityByService.reduce((sum, s) => sum + s.revenue, 0) * 100).toFixed(1)}%
                  </Badge>
                </td>
              </tr>
            </tfoot>
          </table>
        </div>
      </Card>

      {/* Profitability Summary */}
      <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
        <Card className="p-4">
          <div className="text-center">
            <HiChartBar className="w-8 h-8 mx-auto text-blue-500 mb-2" />
            <p className="text-sm text-gray-500">إجمالي الإيرادات</p>
            <p className="text-xl font-bold">
              {formatCurrency(mockProfitabilityByService.reduce((sum, s) => sum + s.revenue, 0))}
            </p>
          </div>
        </Card>
        <Card className="p-4">
          <div className="text-center">
            <HiTrendingDown className="w-8 h-8 mx-auto text-red-500 mb-2" />
            <p className="text-sm text-gray-500">إجمالي التكاليف</p>
            <p className="text-xl font-bold text-red-600">
              {formatCurrency(mockProfitabilityByService.reduce((sum, s) => sum + s.cost, 0))}
            </p>
          </div>
        </Card>
        <Card className="p-4">
          <div className="text-center">
            <HiTrendingUp className="w-8 h-8 mx-auto text-green-500 mb-2" />
            <p className="text-sm text-gray-500">صافي الربح</p>
            <p className="text-xl font-bold text-green-600">
              {formatCurrency(mockProfitabilityByService.reduce((sum, s) => sum + s.profit, 0))}
            </p>
          </div>
        </Card>
        <Card className="p-4">
          <div className="text-center">
            <HiChartPie className="w-8 h-8 mx-auto text-purple-500 mb-2" />
            <p className="text-sm text-gray-500">متوسط هامش الربح</p>
            <p className="text-xl font-bold text-purple-600">
              {(mockProfitabilityByService.reduce((sum, s) => sum + s.margin, 0) / mockProfitabilityByService.length).toFixed(1)}%
            </p>
          </div>
        </Card>
      </div>
    </div>
  );

  // Render active report
  const renderActiveReport = () => {
    switch (activeReport) {
      case 'dashboard':
        return <DashboardView />;
      case 'revenue':
        return <RevenueReportsView />;
      case 'expenses':
        return <ExpensesReportsView />;
      case 'insurance':
        return <InsuranceReportsView />;
      case 'commissions':
        return <CommissionsReportsView />;
      case 'profitability':
        return <ProfitabilityReportsView />;
      default:
        return <DashboardView />;
    }
  };

  // Handle export
  const handleExport = () => {
    console.log('Exporting report:', activeReport, 'Format:', exportFormat);
    setShowExportModal(false);
  };

  return (
    <div className="space-y-6" dir="rtl">
      {/* Header */}
      <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">التقارير المالية</h1>
          <p className="text-gray-600 mt-1">تقارير الربحية والتحليل المالي</p>
        </div>
        <div className="flex gap-2">
          <Button variant="outline" icon={HiRefresh} onClick={() => setLoading(true)}>
            تحديث
          </Button>
          <Button icon={HiDownload} onClick={() => setShowExportModal(true)}>
            تصدير التقرير
          </Button>
        </div>
      </div>

      {/* Date Range Filter */}
      <Card className="p-4">
        <div className="flex flex-wrap items-center gap-4">
          <div className="flex items-center gap-2">
            <HiCalendar className="w-5 h-5 text-gray-400" />
            <span className="text-sm text-gray-600">الفترة:</span>
          </div>
          <div className="flex items-center gap-2">
            <input
              type="date"
              value={dateRange.from}
              onChange={(e) => setDateRange({ ...dateRange, from: e.target.value })}
              className="px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-blue-500"
            />
            <span className="text-gray-400">إلى</span>
            <input
              type="date"
              value={dateRange.to}
              onChange={(e) => setDateRange({ ...dateRange, to: e.target.value })}
              className="px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-blue-500"
            />
          </div>
          <Button variant="outline" size="sm" icon={HiFilter}>
            تطبيق الفلتر
          </Button>
        </div>
      </Card>

      {/* Report Type Tabs */}
      <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
        {reportTypes.map((report) => (
          <button
            key={report.id}
            onClick={() => setActiveReport(report.id)}
            className={`p-4 rounded-lg border-2 transition-all text-right ${
              activeReport === report.id
                ? 'border-blue-500 bg-blue-50 text-blue-700'
                : 'border-gray-200 hover:border-gray-300 hover:bg-gray-50'
            }`}
          >
            <report.icon className={`w-6 h-6 mb-2 ${activeReport === report.id ? 'text-blue-500' : 'text-gray-400'}`} />
            <h3 className="font-medium text-sm">{report.name}</h3>
            <p className="text-xs text-gray-500 mt-1 line-clamp-2">{report.description}</p>
          </button>
        ))}
      </div>

      {/* Active Report Content */}
      {renderActiveReport()}

      {/* Export Modal */}
      <Modal
        isOpen={showExportModal}
        onClose={() => setShowExportModal(false)}
        title="تصدير التقرير"
      >
        <div className="space-y-4">
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-2">
              صيغة التصدير
            </label>
            <div className="grid grid-cols-3 gap-3">
              {[
                { id: 'excel', label: 'Excel', ext: '.xlsx' },
                { id: 'pdf', label: 'PDF', ext: '.pdf' },
                { id: 'csv', label: 'CSV', ext: '.csv' }
              ].map((format) => (
                <button
                  key={format.id}
                  onClick={() => setExportFormat(format.id)}
                  className={`p-3 rounded-lg border-2 text-center ${
                    exportFormat === format.id
                      ? 'border-blue-500 bg-blue-50'
                      : 'border-gray-200 hover:border-gray-300'
                  }`}
                >
                  <span className="font-medium">{format.label}</span>
                  <span className="block text-xs text-gray-500">{format.ext}</span>
                </button>
              ))}
            </div>
          </div>
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-2">
              التقرير المحدد
            </label>
            <p className="text-gray-600 bg-gray-50 p-3 rounded-lg">
              {reportTypes.find(r => r.id === activeReport)?.name}
            </p>
          </div>
          <div className="flex justify-end gap-3 pt-4">
            <Button variant="outline" onClick={() => setShowExportModal(false)}>
              إلغاء
            </Button>
            <Button icon={HiDownload} onClick={handleExport}>
              تصدير
            </Button>
          </div>
        </div>
      </Modal>
    </div>
  );
}
