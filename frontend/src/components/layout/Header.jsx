import React, { useState, useRef, useEffect, useCallback } from 'react';
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
  const profileButtonRef = useRef(null);
  const notificationsButtonRef = useRef(null);

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

  // Handle keyboard navigation for dropdowns
  const handleDropdownKeyDown = useCallback((e, closeDropdown, buttonRef) => {
    if (e.key === 'Escape') {
      closeDropdown();
      buttonRef.current?.focus();
    }
  }, []);

  // Handle keyboard navigation within dropdown
  const handleMenuKeyDown = useCallback((e) => {
    const menuItems = e.currentTarget.querySelectorAll('[role="menuitem"]');
    const currentIndex = Array.from(menuItems).indexOf(document.activeElement);

    switch (e.key) {
      case 'ArrowDown':
        e.preventDefault();
        if (currentIndex < menuItems.length - 1) {
          menuItems[currentIndex + 1].focus();
        } else {
          menuItems[0].focus();
        }
        break;
      case 'ArrowUp':
        e.preventDefault();
        if (currentIndex > 0) {
          menuItems[currentIndex - 1].focus();
        } else {
          menuItems[menuItems.length - 1].focus();
        }
        break;
      case 'Home':
        e.preventDefault();
        menuItems[0].focus();
        break;
      case 'End':
        e.preventDefault();
        menuItems[menuItems.length - 1].focus();
        break;
      default:
        break;
    }
  }, []);

  const handleLogout = async () => {
    setIsProfileOpen(false);
    try {
      await logout();
    } catch (error) {
      console.error('Logout error:', error);
    }
    navigate('/login');
  };

  const toggleProfile = () => {
    setIsProfileOpen(!isProfileOpen);
    setIsNotificationsOpen(false);
  };

  const toggleNotifications = () => {
    setIsNotificationsOpen(!isNotificationsOpen);
    setIsProfileOpen(false);
  };

  return (
    <header
      className={clsx(
        'fixed top-0 end-0 z-20 h-16 bg-white border-b border-gray-200 transition-all duration-300',
        isSidebarCollapsed ? 'start-16' : 'start-64'
      )}
      role="banner"
    >
      <div className="flex items-center justify-between h-full px-4">
        {/* Left side */}
        <div className="flex items-center gap-4">
          <button
            onClick={onMenuClick}
            className="p-2 rounded-lg text-gray-500 hover:bg-gray-100 lg:hidden focus:outline-none focus:ring-2 focus:ring-primary-500"
            aria-label="فتح القائمة الجانبية"
            aria-expanded={!isSidebarCollapsed}
          >
            <HiMenu className="w-5 h-5" aria-hidden="true" />
          </button>
        </div>

        {/* Right side */}
        <div className="flex items-center gap-2">
          {/* Language Toggle */}
          <button
            onClick={toggleLocale}
            className="flex items-center gap-2 px-3 py-2 rounded-lg text-sm text-gray-700 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-primary-500"
            aria-label={locale === 'ar' ? 'Switch to English' : 'التبديل للعربية'}
          >
            <HiTranslate className="w-5 h-5" aria-hidden="true" />
            <span className="hidden sm:inline">{locale === 'ar' ? 'EN' : 'عربي'}</span>
          </button>

          {/* Notifications */}
          <div className="relative" ref={notificationsRef}>
            <button
              ref={notificationsButtonRef}
              onClick={toggleNotifications}
              className="relative p-2 rounded-lg text-gray-500 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-primary-500"
              aria-label="الإشعارات"
              aria-haspopup="true"
              aria-expanded={isNotificationsOpen}
              aria-controls="notifications-menu"
            >
              <HiBell className="w-5 h-5" aria-hidden="true" />
              <span
                className="absolute top-1 end-1 w-2 h-2 bg-red-500 rounded-full"
                aria-label="يوجد إشعارات جديدة"
              />
            </button>

            {isNotificationsOpen && (
              <div
                id="notifications-menu"
                role="menu"
                aria-orientation="vertical"
                aria-labelledby="notifications-button"
                onKeyDown={(e) => handleDropdownKeyDown(e, () => setIsNotificationsOpen(false), notificationsButtonRef)}
                className={clsx(
                  'absolute top-full mt-2 w-80 bg-white rounded-lg shadow-lg border border-gray-200 overflow-hidden',
                  isRTL ? 'left-0' : 'right-0'
                )}
              >
                <div className="px-4 py-3 border-b border-gray-200 bg-gray-50">
                  <h3 className="font-semibold text-gray-900">الإشعارات</h3>
                </div>
                <div className="max-h-96 overflow-y-auto">
                  <div className="px-4 py-8 text-center text-gray-500" role="status">
                    <HiBell className="w-8 h-8 mx-auto mb-2 text-gray-400" aria-hidden="true" />
                    <p>لا توجد إشعارات جديدة</p>
                  </div>
                </div>
              </div>
            )}
          </div>

          {/* Profile Dropdown */}
          <div className="relative" ref={profileRef}>
            <button
              ref={profileButtonRef}
              onClick={toggleProfile}
              className="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-primary-500"
              aria-label="قائمة الملف الشخصي"
              aria-haspopup="menu"
              aria-expanded={isProfileOpen}
              aria-controls="profile-menu"
            >
              <div className="w-8 h-8 rounded-full bg-primary-100 flex items-center justify-center" aria-hidden="true">
                <HiUser className="w-4 h-4 text-primary-600" />
              </div>
              <div className="hidden sm:block text-start">
                <p className="text-sm font-medium text-gray-900">
                  {user?.name || 'مستخدم'}
                </p>
                <p className="text-xs text-gray-500">{user?.email}</p>
              </div>
              <HiChevronDown
                className={clsx(
                  'w-4 h-4 text-gray-400 transition-transform',
                  isProfileOpen && 'rotate-180'
                )}
                aria-hidden="true"
              />
            </button>

            {isProfileOpen && (
              <div
                id="profile-menu"
                role="menu"
                aria-orientation="vertical"
                aria-labelledby="profile-button"
                onKeyDown={(e) => {
                  handleDropdownKeyDown(e, () => setIsProfileOpen(false), profileButtonRef);
                  handleMenuKeyDown(e);
                }}
                className={clsx(
                  'absolute top-full mt-2 w-56 bg-white rounded-lg shadow-lg border border-gray-200 overflow-hidden',
                  isRTL ? 'left-0' : 'right-0'
                )}
              >
                <div className="px-4 py-3 border-b border-gray-200 bg-gray-50">
                  <p className="text-sm font-medium text-gray-900">{user?.name}</p>
                  <p className="text-xs text-gray-500">{user?.email}</p>
                </div>
                <div className="py-1" role="none">
                  <button
                    role="menuitem"
                    onClick={() => {
                      setIsProfileOpen(false);
                      navigate('/profile');
                    }}
                    className="flex items-center gap-3 w-full px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 focus:bg-gray-100 focus:outline-none"
                    tabIndex={0}
                  >
                    <HiUser className="w-4 h-4" aria-hidden="true" />
                    <span>الملف الشخصي</span>
                  </button>
                  <button
                    role="menuitem"
                    onClick={() => {
                      setIsProfileOpen(false);
                      navigate('/system/settings');
                    }}
                    className="flex items-center gap-3 w-full px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 focus:bg-gray-100 focus:outline-none"
                    tabIndex={0}
                  >
                    <HiCog className="w-4 h-4" aria-hidden="true" />
                    <span>الإعدادات</span>
                  </button>
                  <hr className="my-1" role="separator" />
                  <button
                    role="menuitem"
                    onClick={handleLogout}
                    className="flex items-center gap-3 w-full px-4 py-2 text-sm text-red-600 hover:bg-red-50 focus:bg-red-50 focus:outline-none"
                    tabIndex={0}
                  >
                    <HiLogout className="w-4 h-4" aria-hidden="true" />
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
