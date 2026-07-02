/**
 * Demo Authentication Service
 * يوفر بيانات تجريبية للدخول عندما لا يكون الباك اند متاح
 * Provides demo login when backend API is not reachable
 */

const DEMO_EMPLOYEES = {
  '1001': {
    id: 'demo-user-1001',
    username: '1001',
    email: 'gm@medical-erp.com',
    full_name: 'Abdullah Al-Rashid',
    full_name_ar: 'عبدالله الراشد',
    avatar: null,
    user_type: 'super_admin',
    preferred_language: 'ar',
    receive_notifications: true,
    employee: {
      id: 'demo-emp-1001',
      employee_number: '1001',
      phone: '0512345001',
      department: { id: 'demo-dept-admin', name: 'Administration', name_ar: 'الإدارة العامة' },
      position: { id: 'demo-pos-gm', title: 'General Manager', title_ar: 'المدير العام' },
      status: 'active',
      hire_date: '2020-01-01',
      photo: null,
    },
  },
  '1002': {
    id: 'demo-user-1002',
    username: '1002',
    email: 'hr@medical-erp.com',
    full_name: 'Nora Al-Fahd',
    full_name_ar: 'نورة الفهد',
    avatar: null,
    user_type: 'hr_manager',
    preferred_language: 'ar',
    receive_notifications: true,
    employee: {
      id: 'demo-emp-1002',
      employee_number: '1002',
      phone: '0512345002',
      department: { id: 'demo-dept-hr', name: 'Human Resources', name_ar: 'الموارد البشرية' },
      position: { id: 'demo-pos-hrm', title: 'HR Manager', title_ar: 'مدير الموارد البشرية' },
      status: 'active',
      hire_date: '2021-03-01',
      photo: null,
    },
  },
  '2001': {
    id: 'demo-user-2001',
    username: '2001',
    email: 'dr.khalid@medical-erp.com',
    full_name: 'Khalid Al-Otaibi',
    full_name_ar: 'خالد العتيبي',
    avatar: null,
    user_type: 'employee',
    preferred_language: 'ar',
    receive_notifications: true,
    employee: {
      id: 'demo-emp-2001',
      employee_number: '2001',
      phone: '0512345003',
      department: { id: 'demo-dept-med', name: 'Medical Department', name_ar: 'القسم الطبي' },
      position: { id: 'demo-pos-doc', title: 'Doctor', title_ar: 'طبيب' },
      status: 'active',
      hire_date: '2022-06-15',
      photo: null,
    },
  },
  '3001': {
    id: 'demo-user-3001',
    username: '3001',
    email: 'maria.santos@medical-erp.com',
    full_name: 'Maria Santos',
    full_name_ar: 'ماريا سانتوس',
    avatar: null,
    user_type: 'employee',
    preferred_language: 'en',
    receive_notifications: true,
    employee: {
      id: 'demo-emp-3001',
      employee_number: '3001',
      phone: '0512345004',
      department: { id: 'demo-dept-nurs', name: 'Nursing Department', name_ar: 'قسم التمريض' },
      position: { id: 'demo-pos-nrs', title: 'Nurse', title_ar: 'ممرض/ة' },
      status: 'active',
      hire_date: '2023-01-10',
      photo: null,
    },
  },
};

const DEMO_PHONES = {
  '1001': '0512345001',
  '1002': '0512345002',
  '2001': '0512345003',
  '3001': '0512345004',
};

function normalizePhone(phone) {
  let p = phone.replace(/[\s\-()]/g, '');
  if (p.startsWith('+966')) p = p.slice(4);
  else if (p.startsWith('00966')) p = p.slice(5);
  else if (p.startsWith('0')) p = p.slice(1);
  return p;
}

/**
 * Attempt demo login with employee_number + phone
 * Returns { success, data, message } or throws an error object
 */
export function demoLogin(employeeNumber, phone) {
  const employee = DEMO_EMPLOYEES[employeeNumber];
  if (!employee) {
    return {
      success: false,
      error: 'employee_not_found',
      message: 'الرقم الوظيفي غير موجود',
    };
  }

  const expectedPhone = DEMO_PHONES[employeeNumber];
  if (normalizePhone(phone) !== normalizePhone(expectedPhone)) {
    return {
      success: false,
      error: 'invalid_phone',
      message: 'رقم الهاتف غير صحيح',
    };
  }

  const token = `demo-token-${employeeNumber}-${Date.now()}`;

  return {
    success: true,
    message: 'تم تسجيل الدخول بنجاح (وضع تجريبي)',
    data: {
      token,
      user: employee,
      expires_at: new Date(Date.now() + 30 * 24 * 60 * 60 * 1000).toISOString(),
    },
  };
}

/**
 * Get demo user profile by token
 */
export function getDemoUser() {
  const token = localStorage.getItem('auth_token');
  if (!token || !token.startsWith('demo-token-')) return null;

  const empNumber = token.split('-')[2];
  return DEMO_EMPLOYEES[empNumber] || null;
}

/**
 * Check if currently in demo mode
 */
export function isDemoMode() {
  const token = localStorage.getItem('auth_token');
  return token && token.startsWith('demo-token-');
}
