import React, { useState } from 'react';
import { NavLink, Outlet } from 'react-router-dom';

const navItems = [
  {
    label: 'لوحة التحكم',
    labelEn: 'Dashboard',
    path: '/dashboard',
    icon: '📊',
  },
  {
    label: 'الموارد البشرية',
    labelEn: 'HR',
    children: [
      { label: 'الموظفون', labelEn: 'Employees', path: '/hr/employees' },
      { label: 'العقود', labelEn: 'Contracts', path: '/hr/contracts' },
      { label: 'العهد', labelEn: 'Custody', path: '/hr/custody' },
      { label: 'إخلاء الطرف', labelEn: 'Clearance', path: '/hr/clearance' },
    ],
  },
  {
    label: 'المستودعات',
    labelEn: 'Inventory',
    children: [
      { label: 'المستودعات', labelEn: 'Warehouses', path: '/inventory/warehouses' },
      { label: 'الأصناف', labelEn: 'Items', path: '/inventory/items' },
    ],
  },
  {
    label: 'المالية',
    labelEn: 'Finance',
    children: [
      { label: 'مراكز التكلفة', labelEn: 'Cost Centers', path: '/finance/cost-centers' },
      { label: 'المطالبات', labelEn: 'Claims', path: '/finance/claims' },
    ],
  },
];

export default function MainLayout() {
  const [sidebarOpen, setSidebarOpen] = useState(true);
  const [expandedGroups, setExpandedGroups] = useState(['الموارد البشرية']);

  const toggleGroup = (label) => {
    setExpandedGroups((prev) =>
      prev.includes(label) ? prev.filter((l) => l !== label) : [...prev, label]
    );
  };

  const linkClass = ({ isActive }) =>
    `block px-4 py-2 rounded-md text-sm transition-colors ${
      isActive
        ? 'bg-blue-100 text-blue-800 font-semibold'
        : 'text-gray-700 hover:bg-gray-100'
    }`;

  return (
    <div className="flex min-h-screen bg-gray-50" dir="rtl">
      {/* Sidebar */}
      <aside
        className={`${
          sidebarOpen ? 'w-64' : 'w-0 overflow-hidden'
        } bg-white border-l border-gray-200 shadow-sm transition-all duration-200 flex-shrink-0`}
      >
        <div className="p-4 border-b border-gray-200">
          <h1 className="text-lg font-bold text-blue-700">Smart Medical ERP</h1>
          <p className="text-xs text-gray-500">نظام تخطيط الموارد الطبية</p>
        </div>

        <nav className="p-3 space-y-1 overflow-y-auto" style={{ maxHeight: 'calc(100vh - 80px)' }}>
          {navItems.map((item) =>
            item.children ? (
              <div key={item.label}>
                <button
                  onClick={() => toggleGroup(item.label)}
                  className="w-full flex items-center justify-between px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50 rounded-md"
                >
                  <span>{item.label}</span>
                  <span className="text-xs">{expandedGroups.includes(item.label) ? '▼' : '◀'}</span>
                </button>
                {expandedGroups.includes(item.label) && (
                  <div className="mr-3 space-y-0.5">
                    {item.children.map((child) => (
                      <NavLink key={child.path} to={child.path} className={linkClass}>
                        {child.label}
                      </NavLink>
                    ))}
                  </div>
                )}
              </div>
            ) : (
              <NavLink key={item.path} to={item.path} className={linkClass}>
                {item.label}
              </NavLink>
            )
          )}
        </nav>
      </aside>

      {/* Main content */}
      <div className="flex-1 flex flex-col">
        <header className="bg-white border-b border-gray-200 px-6 py-3 flex items-center justify-between shadow-sm">
          <button
            onClick={() => setSidebarOpen(!sidebarOpen)}
            className="text-gray-500 hover:text-gray-700 text-xl"
          >
            ☰
          </button>
          <div className="text-sm text-gray-500">Medical ERP v1.0</div>
        </header>

        <main className="flex-1 p-6 overflow-auto">
          <Outlet />
        </main>
      </div>
    </div>
  );
}
