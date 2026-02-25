import React, { Suspense, lazy } from 'react';
import { Routes, Route, Navigate } from 'react-router-dom';
import { useAuth } from './contexts/AuthContext';
import ProtectedRoute from './components/ProtectedRoute';
import { MainLayout } from './components/layout';
import { LoadingSpinner } from './components/ui';

// Lazy load pages for better performance
// Auth
const LoginPage = lazy(() => import('./pages/auth/LoginPage'));

// Dashboard
const DashboardPage = lazy(() => import('./pages/DashboardPage'));

// Leaves
const LeaveRequestsPage = lazy(() => import('./pages/leaves/LeaveRequestsPage'));
const LeaveDecisionsPage = lazy(() => import('./pages/leaves/LeaveDecisionsPage'));
const LeaveBalancesPage = lazy(() => import('./pages/leaves/LeaveBalancesPage'));
const LeaveTypesPage = lazy(() => import('./pages/leaves/LeaveTypesPage'));

// Payroll
const PayrollListPage = lazy(() => import('./pages/payroll/PayrollListPage'));
const LoansPage = lazy(() => import('./pages/payroll/LoansPage'));
const PayrollSettingsPage = lazy(() => import('./pages/payroll/PayrollSettingsPage'));

// HR - Placeholder pages (to be implemented)
const EmployeesPage = lazy(() => import('./pages/hr/EmployeesPage'));
const DepartmentsPage = lazy(() => import('./pages/hr/DepartmentsPage'));
const PositionsPage = lazy(() => import('./pages/hr/PositionsPage'));
const ContractsPage = lazy(() => import('./pages/hr/ContractsPage'));
const CustodiesPage = lazy(() => import('./pages/hr/CustodiesPage'));
const ClearancePage = lazy(() => import('./pages/hr/ClearancePage'));

// Inventory - Placeholder pages
const WarehousesPage = lazy(() => import('./pages/inventory/WarehousesPage'));
const InventoryItemsPage = lazy(() => import('./pages/inventory/InventoryItemsPage'));
const InventoryMovementsPage = lazy(() => import('./pages/inventory/InventoryMovementsPage'));
const QuotasPage = lazy(() => import('./pages/inventory/QuotasPage'));
const PurchaseRequestsPage = lazy(() => import('./pages/inventory/PurchaseRequestsPage'));

// Roster - Placeholder pages
const ShiftPatternsPage = lazy(() => import('./pages/roster/ShiftPatternsPage'));
const RostersPage = lazy(() => import('./pages/roster/RostersPage'));
const AttendancePage = lazy(() => import('./pages/roster/AttendancePage'));
const ShiftSwapsPage = lazy(() => import('./pages/roster/ShiftSwapsPage'));

// Finance - Placeholder pages
const CostCentersPage = lazy(() => import('./pages/finance/CostCentersPage'));
const DoctorsPage = lazy(() => import('./pages/finance/DoctorsPage'));
const MedicalServicesPage = lazy(() => import('./pages/finance/MedicalServicesPage'));
const InsuranceClaimsPage = lazy(() => import('./pages/finance/InsuranceClaimsPage'));
const FinanceReportsPage = lazy(() => import('./pages/finance/FinanceReportsPage'));

// System - Placeholder pages
const UsersPage = lazy(() => import('./pages/system/UsersPage'));
const RolesPage = lazy(() => import('./pages/system/RolesPage'));
const PermissionsPage = lazy(() => import('./pages/system/PermissionsPage'));
const AuditLogsPage = lazy(() => import('./pages/system/AuditLogsPage'));
const SystemSettingsPage = lazy(() => import('./pages/system/SystemSettingsPage'));

// 404 & Error Pages
const NotFoundPage = lazy(() => import('./pages/NotFoundPage'));
const UnauthorizedPage = lazy(() => import('./pages/UnauthorizedPage'));

// Loading fallback component
function PageLoader() {
  return (
    <div className="flex items-center justify-center min-h-[400px]">
      <LoadingSpinner size="lg" />
    </div>
  );
}

/**
 * التطبيق الرئيسي
 * Main Application Component
 */
export default function App() {
  const { isAuthenticated, loading } = useAuth();

  if (loading) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-gray-50">
        <LoadingSpinner size="xl" />
      </div>
    );
  }

  return (
    <Suspense fallback={<PageLoader />}>
      <Routes>
        {/* Public Routes */}
        <Route
          path="/login"
          element={
            isAuthenticated ? <Navigate to="/" replace /> : <LoginPage />
          }
        />

        {/* Protected Routes */}
        <Route
          element={
            <ProtectedRoute>
              <MainLayout />
            </ProtectedRoute>
          }
        >
          {/* Dashboard */}
          <Route path="/" element={<DashboardPage />} />

          {/* HR Module */}
          <Route path="/hr">
            <Route path="employees" element={<EmployeesPage />} />
            <Route path="departments" element={<DepartmentsPage />} />
            <Route path="positions" element={<PositionsPage />} />
            <Route path="contracts" element={<ContractsPage />} />
            <Route path="custodies" element={<CustodiesPage />} />
            <Route path="clearance" element={<ClearancePage />} />
          </Route>

          {/* Leaves Module */}
          <Route path="/leaves">
            <Route path="requests" element={<LeaveRequestsPage />} />
            <Route path="decisions" element={<LeaveDecisionsPage />} />
            <Route path="balances" element={<LeaveBalancesPage />} />
            <Route path="types" element={<LeaveTypesPage />} />
          </Route>

          {/* Payroll Module */}
          <Route path="/payroll">
            <Route index element={<PayrollListPage />} />
            <Route path="loans" element={<LoansPage />} />
            <Route path="settings" element={<PayrollSettingsPage />} />
          </Route>

          {/* Inventory Module */}
          <Route path="/inventory">
            <Route path="warehouses" element={<WarehousesPage />} />
            <Route path="items" element={<InventoryItemsPage />} />
            <Route path="movements" element={<InventoryMovementsPage />} />
            <Route path="quotas" element={<QuotasPage />} />
            <Route path="purchases" element={<PurchaseRequestsPage />} />
          </Route>

          {/* Roster Module */}
          <Route path="/roster">
            <Route path="shifts" element={<ShiftPatternsPage />} />
            <Route path="schedule" element={<RostersPage />} />
            <Route path="attendance" element={<AttendancePage />} />
            <Route path="swaps" element={<ShiftSwapsPage />} />
          </Route>

          {/* Finance Module */}
          <Route path="/finance">
            <Route path="cost-centers" element={<CostCentersPage />} />
            <Route path="doctors" element={<DoctorsPage />} />
            <Route path="services" element={<MedicalServicesPage />} />
            <Route path="claims" element={<InsuranceClaimsPage />} />
            <Route path="reports" element={<FinanceReportsPage />} />
          </Route>

          {/* System Module */}
          <Route path="/system">
            <Route path="users" element={<UsersPage />} />
            <Route path="roles" element={<RolesPage />} />
            <Route path="permissions" element={<PermissionsPage />} />
            <Route path="audit-logs" element={<AuditLogsPage />} />
            <Route path="settings" element={<SystemSettingsPage />} />
          </Route>
        </Route>

        {/* Unauthorized Access */}
        <Route path="/unauthorized" element={<UnauthorizedPage />} />

        {/* 404 - Not Found */}
        <Route path="*" element={<NotFoundPage />} />
      </Routes>
    </Suspense>
  );
}
