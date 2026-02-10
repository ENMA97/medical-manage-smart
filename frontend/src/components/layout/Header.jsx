import React, { useState, useRef, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import clsx from 'clsx';
import { useLocale } from '../../contexts/LocaleContext';
import { useAuth } from '../../contexts/AuthContext';
import {
  HiMenu,
  HiBell,
  HiUser,
  HiLogout,
  HiCog,
  HiTranslate,
  HiChevronDown,
} from 'react-icons/hi';

export default function Header({ onMenuClick, isSidebarCollapsed }) {
  const { t, locale, toggleLocale, isRTL } = useLocale();
  const { user, logout } = useAuth();
  const navigate = useNavigate();
  const [isProfileOpen, setIsProfileOpen] = useState(false);
  const [isNotificationsOpen, setIsNotificationsOpen] = useState(false);
  const profileRef = useRef(null);
  const notificationsRef = useRef(null);

  // Close dropdowns when clicking outside
  useEffect(() => {
    const handleClickOutside = (event) => {
      if (profileRef.current && !profileRef.current.contains(event.target)) {
        setIsProfileOpen(false);
      }
      if (notificationsRef.current && !notificationsRef.current.contains(event.target)) {
        setIsNotificationsOpen(false);
      }
    };

    document.addEventListener('mousedown', handleClickOutside);
    return () => document.removeEventListener('mousedown', handleClickOutside);
  }, []);

  const handleLogout = async () => {
    await logout();
    navigate('/login');
  };

  return (
    <header
      className={clsx(
        'fixed top-0 end-0 z-20 h-16 bg-white border-b border-gray-200 transition-all duration-300',
        isSidebarCollapsed ? 'start-16' : 'start-64'
      )}
    >
      <div className="flex items-center justify-between h-full px-4">
        {/* Left side */}
        <div className="flex items-center gap-4">
          <button
            onClick={onMenuClick}
            className="p-2 rounded-lg text-gray-500 hover:bg-gray-100 lg:hidden"
          >
            <HiMenu className="w-5 h-5" />
          </button>
        </div>

        {/* Right side */}
        <div className="flex items-center gap-2">
          {/* Language Toggle */}
          <button
            onClick={toggleLocale}
            className="flex items-center gap-2 px-3 py-2 rounded-lg text-sm text-gray-700 hover:bg-gray-100"
            title={locale === 'ar' ? 'Switch to English' : 'التبديل للعربية'}
          >
            <HiTranslate className="w-5 h-5" />
            <span className="hidden sm:inline">{locale === 'ar' ? 'EN' : 'عربي'}</span>
          </button>

          {/* Notifications */}
          <div className="relative" ref={notificationsRef}>
            <button
              onClick={() => setIsNotificationsOpen(!isNotificationsOpen)}
              className="relative p-2 rounded-lg text-gray-500 hover:bg-gray-100"
            >
              <HiBell className="w-5 h-5" />
              <span className="absolute top-1 end-1 w-2 h-2 bg-red-500 rounded-full" />
            </button>

            {isNotificationsOpen && (
              <div
                className={clsx(
                  'absolute top-full mt-2 w-80 bg-white rounded-lg shadow-lg border border-gray-200 overflow-hidden',
                  isRTL ? 'left-0' : 'right-0'
                )}
              >
                <div className="px-4 py-3 border-b border-gray-200 bg-gray-50">
                  <h3 className="font-semibold text-gray-900">الإشعارات</h3>
                </div>
                <div className="max-h-96 overflow-y-auto">
                  <div className="px-4 py-8 text-center text-gray-500">
                    <HiBell className="w-8 h-8 mx-auto mb-2 text-gray-400" />
                    <p>لا توجد إشعارات جديدة</p>
                  </div>
                </div>
              </div>
            )}
          </div>

          {/* Profile Dropdown */}
          <div className="relative" ref={profileRef}>
            <button
              onClick={() => setIsProfileOpen(!isProfileOpen)}
              className="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-gray-100"
            >
              <div className="w-8 h-8 rounded-full bg-primary-100 flex items-center justify-center">
                <HiUser className="w-4 h-4 text-primary-600" />
              </div>
              <div className="hidden sm:block text-start">
                <p className="text-sm font-medium text-gray-900">
                  {user?.name || 'مستخدم'}
                </p>
                <p className="text-xs text-gray-500">{user?.email}</p>
              </div>
              <HiChevronDown className="w-4 h-4 text-gray-400" />
            </button>

            {isProfileOpen && (
              <div
                className={clsx(
                  'absolute top-full mt-2 w-56 bg-white rounded-lg shadow-lg border border-gray-200 overflow-hidden',
                  isRTL ? 'left-0' : 'right-0'
                )}
              >
                <div className="px-4 py-3 border-b border-gray-200 bg-gray-50">
                  <p className="text-sm font-medium text-gray-900">{user?.name}</p>
                  <p className="text-xs text-gray-500">{user?.email}</p>
                </div>
                <div className="py-1">
                  <button
                    onClick={() => {
                      setIsProfileOpen(false);
                      navigate('/profile');
                    }}
                    className="flex items-center gap-3 w-full px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                  >
                    <HiUser className="w-4 h-4" />
                    <span>الملف الشخصي</span>
                  </button>
                  <button
                    onClick={() => {
                      setIsProfileOpen(false);
                      navigate('/system/settings');
                    }}
                    className="flex items-center gap-3 w-full px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                  >
                    <HiCog className="w-4 h-4" />
                    <span>الإعدادات</span>
                  </button>
                  <hr className="my-1" />
                  <button
                    onClick={handleLogout}
                    className="flex items-center gap-3 w-full px-4 py-2 text-sm text-red-600 hover:bg-red-50"
                  >
                    <HiLogout className="w-4 h-4" />
                    <span>{t('auth.logout')}</span>
                  </button>
                </div>
              </div>
            )}
          </div>
        </div>
      </div>
    </header>
  );
}
