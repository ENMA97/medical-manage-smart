import React, { useState } from 'react';
import { Outlet } from 'react-router-dom';
import clsx from 'clsx';
import Sidebar from './Sidebar';
import Header from './Header';

/**
 * التخطيط الرئيسي للتطبيق
 * Main Layout Component
 */
export default function MainLayout() {
  const [isSidebarCollapsed, setIsSidebarCollapsed] = useState(false);
  const [isMobileSidebarOpen, setIsMobileSidebarOpen] = useState(false);

  const toggleSidebar = () => {
    setIsSidebarCollapsed(!isSidebarCollapsed);
  };

  const toggleMobileSidebar = () => {
    setIsMobileSidebarOpen(!isMobileSidebarOpen);
  };

  return (
    <div className="min-h-screen bg-gray-50">
      {/* Mobile overlay */}
      {isMobileSidebarOpen && (
        <div
          className="fixed inset-0 z-20 bg-black/50 lg:hidden"
          onClick={toggleMobileSidebar}
        />
      )}

      {/* Sidebar */}
      <div
        className={clsx(
          'lg:block',
          isMobileSidebarOpen ? 'block' : 'hidden'
        )}
      >
        <Sidebar isCollapsed={isSidebarCollapsed} onToggle={toggleSidebar} />
      </div>

      {/* Header */}
      <Header
        onMenuClick={toggleMobileSidebar}
        isSidebarCollapsed={isSidebarCollapsed}
      />

      {/* Main content */}
      <main
        className={clsx(
          'pt-16 min-h-screen transition-all duration-300',
          isSidebarCollapsed ? 'lg:ps-16' : 'lg:ps-64'
        )}
      >
        <div className="p-6">
          <Outlet />
        </div>
      </main>
    </div>
  );
}
