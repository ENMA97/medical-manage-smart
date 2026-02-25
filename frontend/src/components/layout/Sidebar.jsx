import React, { useState, useCallback } from 'react';
import { NavLink, useLocation } from 'react-router-dom';
import clsx from 'clsx';
import { useLocale } from '../../contexts/LocaleContext';
import { useAuth } from '../../contexts/AuthContext';
import {
  HiHome,
  HiUserGroup,
  HiCalendar,
  HiCurrencyDollar,
  HiCube,
  HiClock,
  HiChartBar,
  HiCog,
  HiChevronDown,
  HiChevronLeft,
  HiChevronRight,
} from 'react-icons/hi';

const navigation = [
  {
    name: 'nav.dashboard',
    href: '/',
    icon: HiHome,
  },
  {
    name: 'nav.hr',
    icon: HiUserGroup,
    children: [
      { name: 'nav.employees', href: '/hr/employees' },
      { name: 'nav.departments', href: '/hr/departments' },
      { name: 'nav.positions', href: '/hr/positions' },
      { name: 'nav.contracts', href: '/hr/contracts' },
      { name: 'nav.custodies', href: '/hr/custodies' },
      { name: 'nav.clearance', href: '/hr/clearance' },
    ],
  },
  {
    name: 'nav.leaves',
    icon: HiCalendar,
    children: [
      { name: 'nav.leaveRequests', href: '/leaves/requests' },
      { name: 'nav.leaveDecisions', href: '/leaves/decisions' },
      { name: 'nav.leaveBalances', href: '/leaves/balances' },
      { name: 'nav.leaveTypes', href: '/leaves/types' },
    ],
  },
  {
    name: 'nav.payroll',
    icon: HiCurrencyDollar,
    children: [
      { name: 'nav.payrollList', href: '/payroll' },
      { name: 'nav.loans', href: '/payroll/loans' },
      { name: 'nav.payrollSettings', href: '/payroll/settings' },
    ],
  },
  {
    name: 'nav.inventory',
    icon: HiCube,
    children: [
      { name: 'nav.warehouses', href: '/inventory/warehouses' },
      { name: 'nav.items', href: '/inventory/items' },
      { name: 'nav.movements', href: '/inventory/movements' },
      { name: 'nav.quotas', href: '/inventory/quotas' },
      { name: 'nav.purchases', href: '/inventory/purchases' },
    ],
  },
  {
    name: 'nav.roster',
    icon: HiClock,
    children: [
      { name: 'nav.shifts', href: '/roster/shifts' },
      { name: 'nav.attendance', href: '/roster/attendance' },
      { name: 'nav.swaps', href: '/roster/swaps' },
    ],
  },
  {
    name: 'nav.finance',
    icon: HiChartBar,
    children: [
      { name: 'nav.costCenters', href: '/finance/cost-centers' },
      { name: 'nav.doctors', href: '/finance/doctors' },
      { name: 'nav.services', href: '/finance/services' },
      { name: 'nav.claims', href: '/finance/claims' },
      { name: 'nav.reports', href: '/finance/reports' },
    ],
  },
  {
    name: 'nav.system',
    icon: HiCog,
    children: [
      { name: 'nav.users', href: '/system/users' },
      { name: 'nav.roles', href: '/system/roles' },
      { name: 'nav.permissions', href: '/system/permissions' },
      { name: 'nav.auditLogs', href: '/system/audit-logs' },
      { name: 'nav.settings', href: '/system/settings' },
    ],
  },
];

function NavItem({ item, isCollapsed, index }) {
  const { t, isRTL } = useLocale();
  const location = useLocation();
  const [isOpen, setIsOpen] = useState(false);
  const menuId = `nav-menu-${item.name.replace('.', '-')}`;

  const isActive = item.href
    ? location.pathname === item.href
    : item.children?.some((child) => location.pathname.startsWith(child.href));

  // Check if has children that are active
  const hasActiveChild = item.children?.some((child) =>
    location.pathname.startsWith(child.href)
  );

  // Auto-expand if has active child
  React.useEffect(() => {
    if (hasActiveChild) {
      setIsOpen(true);
    }
  }, [hasActiveChild]);

  const handleKeyDown = useCallback((e) => {
    if (e.key === 'Enter' || e.key === ' ') {
      e.preventDefault();
      setIsOpen((prev) => !prev);
    }
  }, []);

  if (item.href) {
    return (
      <NavLink
        to={item.href}
        className={({ isActive }) =>
          clsx(
            'flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors',
            isActive
              ? 'bg-primary-50 text-primary-700'
              : 'text-gray-700 hover:bg-gray-100'
          )
        }
        title={isCollapsed ? t(item.name) : undefined}
        aria-current={location.pathname === item.href ? 'page' : undefined}
      >
        <item.icon className="w-5 h-5 flex-shrink-0" aria-hidden="true" />
        {!isCollapsed && <span>{t(item.name)}</span>}
      </NavLink>
    );
  }

  return (
    <div role="none">
      <button
        onClick={() => setIsOpen(!isOpen)}
        onKeyDown={handleKeyDown}
        className={clsx(
          'flex items-center justify-between w-full px-3 py-2 rounded-lg text-sm font-medium transition-colors',
          isActive || hasActiveChild
            ? 'bg-primary-50 text-primary-700'
            : 'text-gray-700 hover:bg-gray-100'
        )}
        title={isCollapsed ? t(item.name) : undefined}
        aria-expanded={isOpen}
        aria-controls={menuId}
        aria-haspopup="true"
      >
        <div className="flex items-center gap-3">
          <item.icon className="w-5 h-5 flex-shrink-0" aria-hidden="true" />
          {!isCollapsed && <span>{t(item.name)}</span>}
        </div>
        {!isCollapsed && (
          <HiChevronDown
            className={clsx(
              'w-4 h-4 transition-transform',
              isOpen && 'rotate-180'
            )}
            aria-hidden="true"
          />
        )}
      </button>
      {!isCollapsed && item.children && (
        <div
          id={menuId}
          className={clsx(
            'mt-1 ms-4 ps-4 border-s border-gray-200 space-y-1',
            !isOpen && 'hidden'
          )}
          role="menu"
          aria-label={t(item.name)}
        >
          {item.children.map((child) => (
            <NavLink
              key={child.href}
              to={child.href}
              className={({ isActive }) =>
                clsx(
                  'block px-3 py-2 rounded-lg text-sm transition-colors',
                  isActive
                    ? 'bg-primary-50 text-primary-700 font-medium'
                    : 'text-gray-600 hover:bg-gray-100'
                )
              }
              role="menuitem"
              aria-current={location.pathname === child.href ? 'page' : undefined}
            >
              {t(child.name)}
            </NavLink>
          ))}
        </div>
      )}
    </div>
  );
}

export default function Sidebar({ isCollapsed, onToggle }) {
  const { t, isRTL, locale } = useLocale();
  const ChevronIcon = isRTL
    ? isCollapsed ? HiChevronLeft : HiChevronRight
    : isCollapsed ? HiChevronRight : HiChevronLeft;

  const toggleLabel = locale === 'ar'
    ? isCollapsed ? 'توسيع القائمة' : 'تصغير القائمة'
    : isCollapsed ? 'Expand sidebar' : 'Collapse sidebar';

  return (
    <aside
      className={clsx(
        'fixed inset-y-0 start-0 z-30 flex flex-col bg-white border-e border-gray-200 transition-all duration-300',
        isCollapsed ? 'w-16' : 'w-64'
      )}
      role="navigation"
      aria-label={locale === 'ar' ? 'القائمة الرئيسية' : 'Main navigation'}
    >
      {/* Logo */}
      <div className="flex items-center justify-between h-16 px-4 border-b border-gray-200">
        {!isCollapsed && (
          <span className="text-lg font-bold text-primary-600">
            {t('app.shortName')}
          </span>
        )}
        <button
          onClick={onToggle}
          className="p-2 rounded-lg text-gray-500 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2"
          aria-label={toggleLabel}
          aria-expanded={!isCollapsed}
        >
          <ChevronIcon className="w-5 h-5" aria-hidden="true" />
        </button>
      </div>

      {/* Navigation */}
      <nav
        className="flex-1 overflow-y-auto p-4 space-y-1 scrollbar-thin"
        aria-label={locale === 'ar' ? 'قائمة التنقل' : 'Navigation menu'}
      >
        {navigation.map((item, index) => (
          <NavItem key={item.name} item={item} isCollapsed={isCollapsed} index={index} />
        ))}
      </nav>

      {/* Version */}
      {!isCollapsed && (
        <div className="p-4 border-t border-gray-200 text-xs text-gray-500 text-center" aria-label="Version">
          v1.0.0
        </div>
      )}
    </aside>
  );
}
