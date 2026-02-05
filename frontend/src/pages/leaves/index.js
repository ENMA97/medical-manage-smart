/**
 * Leave Module Pages Index
 * فهرس صفحات وحدة الإجازات
 */

export { default as LeaveRequestsPage } from './LeaveRequestsPage';
export { default as LeaveBalancesPage } from './LeaveBalancesPage';
export { default as LeaveDecisionsPage } from './LeaveDecisionsPage';
export { default as LeaveTypesPage } from './LeaveTypesPage';

// Page routes configuration
export const leaveRoutes = [
  {
    path: '/leaves/requests',
    component: 'LeaveRequestsPage',
    title: 'طلبات الإجازة',
    titleEn: 'Leave Requests',
    icon: 'DocumentTextIcon',
    permissions: ['leave.requests.view'],
  },
  {
    path: '/leaves/balances',
    component: 'LeaveBalancesPage',
    title: 'أرصدة الإجازات',
    titleEn: 'Leave Balances',
    icon: 'CalculatorIcon',
    permissions: ['leave.balances.view'],
  },
  {
    path: '/leaves/decisions',
    component: 'LeaveDecisionsPage',
    title: 'قرارات الإجازة',
    titleEn: 'Leave Decisions',
    icon: 'ClipboardCheckIcon',
    permissions: ['leave.decisions.view'],
  },
  {
    path: '/leaves/types',
    component: 'LeaveTypesPage',
    title: 'أنواع الإجازات',
    titleEn: 'Leave Types',
    icon: 'TagIcon',
    permissions: ['leave.types.view'],
  },
];

// Navigation menu configuration
export const leaveNavigation = {
  title: 'الإجازات',
  titleEn: 'Leave Management',
  icon: 'CalendarIcon',
  items: leaveRoutes,
};
