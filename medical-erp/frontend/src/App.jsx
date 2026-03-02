import React from 'react';
import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { Toaster } from 'react-hot-toast';
import { AuthProvider } from './contexts/AuthContext';
import { LocaleProvider } from './contexts/LocaleContext';
import ProtectedRoute from './components/common/ProtectedRoute';
import MainLayout from './layouts/MainLayout';

// Lazy load modules for code splitting
const Dashboard = React.lazy(() => import('./pages/Dashboard'));

// HR Module
const EmployeeList = React.lazy(() => import('./pages/hr/EmployeeList'));
const EmployeeForm = React.lazy(() => import('./pages/hr/EmployeeForm'));
const ContractList = React.lazy(() => import('./pages/hr/ContractList'));
const CustodyList = React.lazy(() => import('./pages/hr/CustodyList'));
const ClearanceList = React.lazy(() => import('./pages/hr/ClearanceList'));

// Payroll Module
const PayrollList = React.lazy(() => import('./pages/payroll/PayrollList'));
const PayrollProcess = React.lazy(() => import('./pages/payroll/PayrollProcess'));
const LoanList = React.lazy(() => import('./pages/payroll/LoanList'));

// Inventory Module
const WarehouseList = React.lazy(() => import('./pages/inventory/WarehouseList'));
const InventoryItems = React.lazy(() => import('./pages/inventory/InventoryItems'));
const StockMovements = React.lazy(() => import('./pages/inventory/StockMovements'));
const QuotaManagement = React.lazy(() => import('./pages/inventory/QuotaManagement'));
const CrashCart = React.lazy(() => import('./pages/inventory/CrashCart'));
const PurchaseRequests = React.lazy(() => import('./pages/inventory/PurchaseRequests'));

// Roster Module
const RosterCalendar = React.lazy(() => import('./pages/roster/RosterCalendar'));
const ShiftPatterns = React.lazy(() => import('./pages/roster/ShiftPatterns'));
const AttendanceList = React.lazy(() => import('./pages/roster/AttendanceList'));
const SwapRequests = React.lazy(() => import('./pages/roster/SwapRequests'));

// Finance Module
const CostCenters = React.lazy(() => import('./pages/finance/CostCenters'));
const ServiceProfitability = React.lazy(() => import('./pages/finance/ServiceProfitability'));
const InsuranceClaims = React.lazy(() => import('./pages/finance/InsuranceClaims'));
const AgingReport = React.lazy(() => import('./pages/finance/AgingReport'));
const ClawbackList = React.lazy(() => import('./pages/finance/ClawbackList'));

// Settings
const SystemSettings = React.lazy(() => import('./pages/settings/SystemSettings'));
const UserManagement = React.lazy(() => import('./pages/settings/UserManagement'));
const RolePermissions = React.lazy(() => import('./pages/settings/RolePermissions'));
const Integrations = React.lazy(() => import('./pages/settings/Integrations'));
const AuditLogs = React.lazy(() => import('./pages/settings/AuditLogs'));

// Auth
const Login = React.lazy(() => import('./pages/auth/Login'));

const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      staleTime: 5 * 60 * 1000, // 5 minutes
      retry: 1,
    },
  },
});

function App() {
  return (
    <QueryClientProvider client={queryClient}>
      <LocaleProvider>
        <AuthProvider>
          <BrowserRouter>
            <React.Suspense fallback={<LoadingScreen />}>
              <Routes>
                {/* Public Routes */}
                <Route path="/login" element={<Login />} />

                {/* Protected Routes */}
                <Route element={<ProtectedRoute><MainLayout /></ProtectedRoute>}>
                  <Route path="/" element={<Navigate to="/dashboard" replace />} />
                  <Route path="/dashboard" element={<Dashboard />} />

                  {/* HR Routes */}
                  <Route path="/hr/employees" element={<EmployeeList />} />
                  <Route path="/hr/employees/new" element={<EmployeeForm />} />
                  <Route path="/hr/employees/:id" element={<EmployeeForm />} />
                  <Route path="/hr/contracts" element={<ContractList />} />
                  <Route path="/hr/custody" element={<CustodyList />} />
                  <Route path="/hr/clearance" element={<ClearanceList />} />

                  {/* Payroll Routes */}
                  <Route path="/payroll" element={<PayrollList />} />
                  <Route path="/payroll/process/:id" element={<PayrollProcess />} />
                  <Route path="/payroll/loans" element={<LoanList />} />

                  {/* Inventory Routes */}
                  <Route path="/inventory/warehouses" element={<WarehouseList />} />
                  <Route path="/inventory/items" element={<InventoryItems />} />
                  <Route path="/inventory/movements" element={<StockMovements />} />
                  <Route path="/inventory/quotas" element={<QuotaManagement />} />
                  <Route path="/inventory/crash-cart" element={<CrashCart />} />
                  <Route path="/inventory/purchase-requests" element={<PurchaseRequests />} />

                  {/* Roster Routes */}
                  <Route path="/roster/calendar" element={<RosterCalendar />} />
                  <Route path="/roster/patterns" element={<ShiftPatterns />} />
                  <Route path="/roster/attendance" element={<AttendanceList />} />
                  <Route path="/roster/swaps" element={<SwapRequests />} />

                  {/* Finance Routes */}
                  <Route path="/finance/cost-centers" element={<CostCenters />} />
                  <Route path="/finance/profitability" element={<ServiceProfitability />} />
                  <Route path="/finance/claims" element={<InsuranceClaims />} />
                  <Route path="/finance/aging" element={<AgingReport />} />
                  <Route path="/finance/clawback" element={<ClawbackList />} />

                  {/* Settings Routes */}
                  <Route path="/settings" element={<SystemSettings />} />
                  <Route path="/settings/users" element={<UserManagement />} />
                  <Route path="/settings/roles" element={<RolePermissions />} />
                  <Route path="/settings/integrations" element={<Integrations />} />
                  <Route path="/settings/audit" element={<AuditLogs />} />
                </Route>

                {/* 404 */}
                <Route path="*" element={<NotFound />} />
              </Routes>
            </React.Suspense>
          </BrowserRouter>
          <Toaster position="top-left" reverseOrder={false} />
        </AuthProvider>
      </LocaleProvider>
    </QueryClientProvider>
  );
}

function LoadingScreen() {
  return (
    <div className="flex items-center justify-center min-h-screen bg-gray-100">
      <div className="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-primary-600"></div>
    </div>
  );
}

function NotFound() {
  return (
    <div className="flex flex-col items-center justify-center min-h-screen bg-gray-100">
      <h1 className="text-6xl font-bold text-gray-800">404</h1>
      <p className="text-xl text-gray-600 mt-4">الصفحة غير موجودة</p>
    </div>
  );
}

export default App;
