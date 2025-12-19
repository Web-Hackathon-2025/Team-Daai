import { useState, useEffect } from 'react';
import { getAdminStats } from '../../services/api';

const AdminDashboard = () => {
  const [stats, setStats] = useState({
    total_users: 0,
    total_providers: 0,
    total_requests: 0,
    completed_requests: 0,
    pending_requests: 0,
  });
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    loadStats();
  }, []);

  const loadStats = async () => {
    setLoading(true);
    try {
      const response = await getAdminStats();
      setStats(response.data.data);
    } catch (err: unknown) {
      console.error('Failed to load stats', err);
      // Use mock data for now
      setStats({
        total_users: 150,
        total_providers: 50,
        total_requests: 300,
        completed_requests: 250,
        pending_requests: 50,
      });
    } finally {
      setLoading(false);
    }
  };

  if (loading) {
    return (
      <div className="min-h-screen bg-gray-50 flex items-center justify-center">
        <div className="text-center">
          <div className="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
          <p className="mt-4 text-gray-600">Loading...</p>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gray-50">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <h1 className="text-3xl font-bold text-gray-900 mb-6">Admin Dashboard</h1>

        {/* Stats Cards */}
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 mb-8">
          <div className="bg-white rounded-lg shadow p-6">
            <h3 className="text-sm font-medium text-gray-500 mb-2">Total Users</h3>
            <p className="text-3xl font-bold text-gray-900">{stats.total_users}</p>
          </div>
          <div className="bg-white rounded-lg shadow p-6">
            <h3 className="text-sm font-medium text-gray-500 mb-2">Service Providers</h3>
            <p className="text-3xl font-bold text-blue-600">{stats.total_providers}</p>
          </div>
          <div className="bg-white rounded-lg shadow p-6">
            <h3 className="text-sm font-medium text-gray-500 mb-2">Total Requests</h3>
            <p className="text-3xl font-bold text-indigo-600">{stats.total_requests}</p>
          </div>
          <div className="bg-white rounded-lg shadow p-6">
            <h3 className="text-sm font-medium text-gray-500 mb-2">Completed</h3>
            <p className="text-3xl font-bold text-green-600">{stats.completed_requests}</p>
          </div>
          <div className="bg-white rounded-lg shadow p-6">
            <h3 className="text-sm font-medium text-gray-500 mb-2">Pending</h3>
            <p className="text-3xl font-bold text-yellow-600">{stats.pending_requests}</p>
          </div>
        </div>

        {/* Quick Links */}
        <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
          <a
            href="/admin/users"
            className="bg-white rounded-lg shadow p-6 hover:shadow-lg transition-shadow block"
          >
            <h3 className="text-xl font-semibold text-gray-900 mb-2">Manage Users</h3>
            <p className="text-gray-600 text-sm">View, approve, suspend, or remove users</p>
          </a>
          <a
            href="/admin/services"
            className="bg-white rounded-lg shadow p-6 hover:shadow-lg transition-shadow block"
          >
            <h3 className="text-xl font-semibold text-gray-900 mb-2">Service Listings</h3>
            <p className="text-gray-600 text-sm">Monitor and manage service listings</p>
          </a>
          <a
            href="/admin/reviews"
            className="bg-white rounded-lg shadow p-6 hover:shadow-lg transition-shadow block"
          >
            <h3 className="text-xl font-semibold text-gray-900 mb-2">Reviews & Moderation</h3>
            <p className="text-gray-600 text-sm">Moderate reviews and ratings</p>
          </a>
        </div>
      </div>
    </div>
  );
};

export default AdminDashboard;

