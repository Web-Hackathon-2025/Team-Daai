import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom';
import { AuthProvider, useAuth } from './context/AuthContext';
import { ProtectedRoute } from './components/ProtectedRoute';
import Header from './components/layout/Header';
import Login from './pages/auth/Login';
import Register from './pages/auth/Register';
import CustomerDashboard from './pages/customer/Dashboard';
import ProviderDetail from './pages/customer/ProviderDetail';
import MyRequests from './pages/customer/MyRequests';
import ProviderDashboard from './pages/provider/Dashboard';
import ProviderProfile from './pages/provider/Profile';

function AppRoutes() {
  const { user } = useAuth();

  return (
    <>
      <Header />
      <Routes>
        {/* Auth Routes - redirect if already logged in */}
        <Route
          path="/login"
          element={!user ? <Login /> : <Navigate to="/" replace />}
        />
        <Route
          path="/register"
          element={!user ? <Register /> : <Navigate to="/" replace />}
        />

        {/* Customer Routes */}
        <Route
          path="/"
          element={
            <ProtectedRoute allowedRoles={['customer']}>
              <CustomerDashboard />
            </ProtectedRoute>
          }
        />
        <Route
          path="/provider/:id"
          element={
            <ProtectedRoute allowedRoles={['customer']}>
              <ProviderDetail />
            </ProtectedRoute>
          }
        />
        <Route
          path="/my-requests"
          element={
            <ProtectedRoute allowedRoles={['customer']}>
              <MyRequests />
            </ProtectedRoute>
          }
        />

      {/* Service Provider Routes */}
      <Route
        path="/provider"
        element={
          <ProtectedRoute allowedRoles={['service_provider']}>
            <ProviderDashboard />
          </ProtectedRoute>
        }
      />
      <Route
        path="/provider/profile"
        element={
          <ProtectedRoute allowedRoles={['service_provider']}>
            <ProviderProfile />
          </ProtectedRoute>
        }
      />

      {/* Admin Routes (Optional) */}
      <Route
        path="/admin"
        element={
          <ProtectedRoute allowedRoles={['admin']}>
            <div className="p-8">Admin Dashboard (Create this)</div>
          </ProtectedRoute>
        }
      />

        {/* Catch all - redirect to home or login */}
        <Route
          path="*"
          element={<Navigate to={user ? '/' : '/login'} replace />}
        />
      </Routes>
    </>
  );
}

function App() {
  return (
    <AuthProvider>
      <BrowserRouter>
        <AppRoutes />
      </BrowserRouter>
    </AuthProvider>
  );
}

export default App;
