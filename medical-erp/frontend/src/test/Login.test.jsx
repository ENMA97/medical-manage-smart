import { describe, it, expect, vi, beforeEach } from 'vitest';
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import { MemoryRouter } from 'react-router-dom';

const mockLogin = vi.fn();
const mockNavigate = vi.fn();

vi.mock('../contexts/AuthContext', () => ({
  AuthProvider: ({ children }) => children,
  useAuth: () => ({
    login: mockLogin,
    user: null,
    loading: false,
    isAuthenticated: false,
  }),
}));

vi.mock('react-router-dom', async () => {
  const actual = await vi.importActual('react-router-dom');
  return {
    ...actual,
    useNavigate: () => mockNavigate,
  };
});

vi.mock('react-hot-toast', () => ({
  default: {
    success: vi.fn(),
    error: vi.fn(),
  },
}));

import Login from '../pages/auth/Login';

describe('Login Page', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('renders login form', () => {
    render(
      <MemoryRouter>
        <Login />
      </MemoryRouter>
    );

    expect(screen.getByLabelText('الرقم الوظيفي')).toBeInTheDocument();
    expect(screen.getByLabelText('رقم الهاتف')).toBeInTheDocument();
    expect(screen.getByRole('button', { name: 'تسجيل الدخول' })).toBeInTheDocument();
  });

  it('renders test account hints', () => {
    render(
      <MemoryRouter>
        <Login />
      </MemoryRouter>
    );

    expect(screen.getByText('حسابات تجريبية')).toBeInTheDocument();
    expect(screen.getByText('مدير عام')).toBeInTheDocument();
  });

  it('renders app title', () => {
    render(
      <MemoryRouter>
        <Login />
      </MemoryRouter>
    );

    expect(screen.getByText('نظام إدارة الموارد البشرية')).toBeInTheDocument();
    expect(screen.getByText('إنماء')).toBeInTheDocument();
  });

  it('submits form with employee number and phone', async () => {
    mockLogin.mockResolvedValue({ message: 'تم تسجيل الدخول بنجاح' });

    render(
      <MemoryRouter>
        <Login />
      </MemoryRouter>
    );

    fireEvent.change(screen.getByLabelText('الرقم الوظيفي'), {
      target: { value: '1001' },
    });
    fireEvent.change(screen.getByLabelText('رقم الهاتف'), {
      target: { value: '0512345001' },
    });
    fireEvent.click(screen.getByRole('button', { name: 'تسجيل الدخول' }));

    await waitFor(() => {
      expect(mockLogin).toHaveBeenCalledWith('1001', '0512345001');
    });
  });

  it('navigates to dashboard on successful login', async () => {
    mockLogin.mockResolvedValue({ message: 'تم تسجيل الدخول بنجاح' });

    render(
      <MemoryRouter>
        <Login />
      </MemoryRouter>
    );

    fireEvent.change(screen.getByLabelText('الرقم الوظيفي'), {
      target: { value: '1001' },
    });
    fireEvent.change(screen.getByLabelText('رقم الهاتف'), {
      target: { value: '0512345001' },
    });
    fireEvent.click(screen.getByRole('button', { name: 'تسجيل الدخول' }));

    await waitFor(() => {
      expect(mockNavigate).toHaveBeenCalledWith('/', { replace: true });
    });
  });
});
