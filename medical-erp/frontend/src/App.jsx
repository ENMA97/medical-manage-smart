import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom';
import { Toaster } from 'react-hot-toast';
import { AuthProvider } from './contexts/AuthContext';
import ProtectedRoute from './components/common/ProtectedRoute';
import InstallPrompt from './components/common/InstallPrompt';
import MainLayout from './layouts/MainLayout';
import Login from './pages/auth/Login';
import Dashboard from './pages/Dashboard';
import ImportEmployees from './pages/ImportEmployees';
import Employees from './pages/Employees';
import EmployeeDetails from './pages/EmployeeDetails';
import EmployeeForm from './pages/EmployeeForm';
import Departments from './pages/Departments';
import Contracts from './pages/Contracts';
import LeaveRequests from './pages/LeaveRequests';
import Payroll from './pages/Payroll';
import PayrollDetail from './pages/PayrollDetail';
import Custody from './pages/Custody';
import Loans from './pages/Loans';
import Letters from './pages/Letters';
import Resignations from './pages/Resignations';
import Disciplinary from './pages/Disciplinary';
import Settings from './pages/Settings';
import NotFound from './pages/NotFound';

export default function App() {
  return (
    <BrowserRouter>
      <AuthProvider>
        <Routes>
          {/* Public */}
          <Route path="/login" element={<Login />} />

          {/* Protected */}
          <Route
            element={
              <ProtectedRoute>
                <MainLayout />
              </ProtectedRoute>
            }
          >
            <Route path="/" element={<Dashboard />} />
            <Route path="/employees" element={<Employees />} />
            <Route path="/employees/new" element={<EmployeeForm />} />
            <Route path="/employees/:id" element={<EmployeeDetails />} />
            <Route path="/employees/:id/edit" element={<EmployeeForm />} />
            <Route path="/departments" element={<Departments />} />
            <Route path="/contracts" element={<Contracts />} />
            <Route path="/leave-requests" element={<LeaveRequests />} />
            <Route path="/payroll" element={<Payroll />} />
            <Route path="/payroll/:id" element={<PayrollDetail />} />
            <Route path="/custody" element={<Custody />} />
            <Route path="/loans" element={<Loans />} />
            <Route path="/letters" element={<Letters />} />
            <Route path="/resignations" element={<Resignations />} />
            <Route path="/disciplinary" element={<Disciplinary />} />
            <Route path="/settings" element={<Settings />} />
            <Route path="/import" element={<ImportEmployees />} />
          </Route>

          {/* Catch-all */}
          <Route path="*" element={<NotFound />} />
        </Routes>

        <InstallPrompt />
        <Toaster
          position="top-center"
          toastOptions={{
            duration: 3000,
            style: {
              fontFamily: 'system-ui, sans-serif',
              direction: 'rtl',
            },
          }}
        />
      </AuthProvider>
    </BrowserRouter>
  );
}
