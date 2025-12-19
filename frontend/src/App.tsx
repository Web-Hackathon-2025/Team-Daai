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
import AdminDashboard from './pages/admin/Dashboard';
import AdminUsers from './pages/admin/Users';
import AdminServices from './pages/admin/Services';
import AdminReviews from './pages/admin/Reviews';
import Landing from './pages/Landing';

function AppRoutes() {
  const { user } = useAuth();

  return (
    <Routes>
      {/* Landing Page - no header */}
      <Route
        path="/"
        element={!user ? <Landing /> : <Navigate to={user.role === 'customer' ? '/customer' : user.role === 'service_provider' ? '/provider' : '/admin'} replace />}
      />

      {/* Auth Routes - no header */}
      <Route
        path="/login"
        element={!user ? <Login /> : <Navigate to={user.role === 'customer' ? '/customer' : user.role === 'service_provider' ? '/provider' : '/admin'} replace />}
      />
      <Route
        path="/register"
        element={!user ? <Register /> : <Navigate to={user.role === 'customer' ? '/customer' : user.role === 'service_provider' ? '/provider' : '/admin'} replace />}
      />

      {/* All other routes with Header */}
      <Route
        path="/*"
        element={
          <>
            <Header />
            <Routes>
              {/* Customer Routes */}
              <Route
                path="/customer"
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

              {/* Admin Routes */}
              <Route
                path="/admin"
                element={
                  <ProtectedRoute allowedRoles={['admin']}>
                    <AdminDashboard />
                  </ProtectedRoute>
                }
              />
              <Route
                path="/admin/users"
                element={
                  <ProtectedRoute allowedRoles={['admin']}>
                    <AdminUsers />
                  </ProtectedRoute>
                }
              />
              <Route
                path="/admin/services"
                element={
                  <ProtectedRoute allowedRoles={['admin']}>
                    <AdminServices />
                  </ProtectedRoute>
                }
              />
              <Route
                path="/admin/reviews"
                element={
                  <ProtectedRoute allowedRoles={['admin']}>
                    <AdminReviews />
                  </ProtectedRoute>
                }
              />

              {/* Catch all - redirect to role-specific dashboard */}
              <Route
                path="*"
                element={<Navigate to={user ? (user.role === 'customer' ? '/customer' : user.role === 'service_provider' ? '/provider' : '/admin') : '/'} replace />}
              />
            </Routes>
          </>
        }
      />
    </Routes>
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
