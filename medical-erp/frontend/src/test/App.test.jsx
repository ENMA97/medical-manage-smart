import { describe, it, expect, vi } from 'vitest';
import { render, screen } from '@testing-library/react';
import { MemoryRouter } from 'react-router-dom';
import NotFound from '../pages/NotFound';

// Mock AuthContext
vi.mock('../contexts/AuthContext', () => ({
  AuthProvider: ({ children }) => children,
  useAuth: () => ({
    user: null,
    loading: false,
    isAuthenticated: false,
    login: vi.fn(),
    logout: vi.fn(),
  }),
}));

// Mock ProtectedRoute to just redirect
vi.mock('../components/common/ProtectedRoute', () => ({
  default: ({ children }) => {
    // Simulate redirect to login for unauthenticated
    const { Navigate } = require('react-router-dom');
    return <Navigate to="/login" replace />;
  },
}));

describe('NotFound Page (standalone)', () => {
  it('renders 404 with correct message', () => {
    render(
      <MemoryRouter>
        <NotFound />
      </MemoryRouter>
    );
    expect(screen.getByText('404')).toBeInTheDocument();
    expect(screen.getByText('الصفحة غير موجودة')).toBeInTheDocument();
  });

  it('has a link to home page', () => {
    render(
      <MemoryRouter>
        <NotFound />
      </MemoryRouter>
    );
    const link = screen.getByText('العودة للرئيسية');
    expect(link.closest('a')).toHaveAttribute('href', '/');
  });

  it('displays RTL direction', () => {
    render(
      <MemoryRouter>
        <NotFound />
      </MemoryRouter>
    );
    const container = screen.getByText('404').closest('[dir]');
    expect(container).toHaveAttribute('dir', 'rtl');
  });
});
