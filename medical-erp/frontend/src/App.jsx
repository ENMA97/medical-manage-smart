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
import Departments from './pages/Departments';
import Contracts from './pages/Contracts';
import LeaveRequests from './pages/LeaveRequests';
import Payroll from './pages/Payroll';
import Custody from './pages/Custody';
import Resignations from './pages/Resignations';
import Settings from './pages/Settings';

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
            <Route path="/employees/:id" element={<EmployeeDetails />} />
            <Route path="/departments" element={<Departments />} />
            <Route path="/contracts" element={<Contracts />} />
            <Route path="/leave-requests" element={<LeaveRequests />} />
            <Route path="/payroll" element={<Payroll />} />
            <Route path="/custody" element={<Custody />} />
            <Route path="/resignations" element={<Resignations />} />
            <Route path="/settings" element={<Settings />} />
            <Route path="/import" element={<ImportEmployees />} />
          </Route>

          {/* Catch-all */}
          <Route path="*" element={<Navigate to="/" replace />} />
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
