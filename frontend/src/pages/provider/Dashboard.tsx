import { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import { getRequests } from '../../services/api';
import Header from '../../components/layout/Header';

interface Request {
  id: number;
  customer_name: string;
  service_name: string;
  status: 'requested' | 'confirmed' | 'completed' | 'cancelled';
  requested_date: string;
  requested_time: string;
  address: string;
  description?: string;
  created_at: string;
}

const ProviderDashboard = () => {
  const [requests, setRequests] = useState<Request[]>([]);
  const [loading, setLoading] = useState(true);
  const [statusFilter, setStatusFilter] = useState<string>('');

  useEffect(() => {
    loadRequests();
  }, [statusFilter]);

  const loadRequests = async () => {
    setLoading(true);
    try {
      const response = await getRequests(statusFilter || undefined);
      setRequests(response.data.data.requests || []);
    } catch (err: any) {
      console.error('Failed to load requests', err);
      setRequests([]);
    } finally {
      setLoading(false);
    }
  };

  const handleAccept = async (id: number) => {
    try {
      const { acceptRequest } = await import('../../services/api');
      await acceptRequest(id);
      loadRequests();
      alert('Request accepted successfully!');
    } catch (err: any) {
      alert(err.response?.data?.error || 'Failed to accept request');
    }
  };

  const handleReject = async (id: number) => {
    if (!confirm('Are you sure you want to reject this request?')) return;
    try {
      const { rejectRequest } = await import('../../services/api');
      await rejectRequest(id);
      loadRequests();
      alert('Request rejected');
    } catch (err: any) {
      alert(err.response?.data?.error || 'Failed to reject request');
    }
  };

  const handleComplete = async (id: number) => {
    try {
      const { completeRequest } = await import('../../services/api');
      await completeRequest(id);
      loadRequests();
      alert('Request marked as completed!');
    } catch (err: any) {
      alert(err.response?.data?.error || 'Failed to complete request');
    }
  };

  const getStatusColor = (status: string) => {
    switch (status) {
      case 'requested':
        return 'bg-yellow-100 text-yellow-800';
      case 'confirmed':
        return 'bg-blue-100 text-blue-800';
      case 'completed':
        return 'bg-green-100 text-green-800';
      case 'cancelled':
        return 'bg-red-100 text-red-800';
      default:
        return 'bg-gray-100 text-gray-800';
    }
  };

  const pendingRequests = requests.filter((r) => r.status === 'requested');
  const confirmedRequests = requests.filter((r) => r.status === 'confirmed');

  return (
    <div className="min-h-screen bg-gray-50">
      <Header />

      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div className="flex justify-between items-center mb-6">
          <h1 className="text-3xl font-bold text-gray-900">Provider Dashboard</h1>
          <Link
            to="/provider/profile"
            className="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700"
          >
            Manage Profile
          </Link>
        </div>

        {/* Stats */}
        <div className="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
          <div className="bg-white rounded-lg shadow p-6">
            <h3 className="text-sm font-medium text-gray-500 mb-2">Pending Requests</h3>
            <p className="text-3xl font-bold text-yellow-600">{pendingRequests.length}</p>
          </div>
          <div className="bg-white rounded-lg shadow p-6">
            <h3 className="text-sm font-medium text-gray-500 mb-2">Confirmed</h3>
            <p className="text-3xl font-bold text-blue-600">{confirmedRequests.length}</p>
          </div>
          <div className="bg-white rounded-lg shadow p-6">
            <h3 className="text-sm font-medium text-gray-500 mb-2">Total Requests</h3>
            <p className="text-3xl font-bold text-gray-900">{requests.length}</p>
          </div>
        </div>

        {/* Filter */}
        <div className="bg-white p-4 rounded-lg shadow mb-6">
          <label className="block text-sm font-medium text-gray-700 mb-2">
            Filter by Status
          </label>
          <select
            value={statusFilter}
            onChange={(e) => setStatusFilter(e.target.value)}
            className="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent outline-none"
          >
            <option value="">All Statuses</option>
            <option value="requested">Requested</option>
            <option value="confirmed">Confirmed</option>
            <option value="completed">Completed</option>
            <option value="cancelled">Cancelled</option>
          </select>
        </div>

        {/* Requests List */}
        {loading ? (
          <div className="text-center py-12">
            <div className="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
            <p className="mt-4 text-gray-600">Loading requests...</p>
          </div>
        ) : requests.length === 0 ? (
          <div className="bg-white rounded-lg shadow p-8 text-center">
            <p className="text-gray-600">No service requests found.</p>
          </div>
        ) : (
          <div className="space-y-4">
            {requests.map((request) => (
              <div
                key={request.id}
                className="bg-white rounded-lg shadow p-6 hover:shadow-lg transition-shadow"
              >
                <div className="flex justify-between items-start mb-4">
                  <div>
                    <h3 className="text-xl font-semibold text-gray-900 mb-2">
                      {request.service_name}
                    </h3>
                    <p className="text-gray-600">
                      Customer: <span className="font-medium">{request.customer_name}</span>
                    </p>
                    {request.description && (
                      <p className="text-gray-600 mt-2">{request.description}</p>
                    )}
                  </div>
                  <span
                    className={`px-3 py-1 rounded-full text-sm font-medium ${getStatusColor(
                      request.status
                    )}`}
                  >
                    {request.status.charAt(0).toUpperCase() + request.status.slice(1)}
                  </span>
                </div>

                <div className="grid grid-cols-2 md:grid-cols-3 gap-4 text-sm mb-4">
                  <div>
                    <p className="text-gray-500">Requested Date</p>
                    <p className="font-medium text-gray-900">{request.requested_date}</p>
                  </div>
                  <div>
                    <p className="text-gray-500">Requested Time</p>
                    <p className="font-medium text-gray-900">{request.requested_time}</p>
                  </div>
                  <div>
                    <p className="text-gray-500">Address</p>
                    <p className="font-medium text-gray-900">{request.address}</p>
                  </div>
                </div>

                {/* Action Buttons */}
                <div className="flex space-x-2">
                  {request.status === 'requested' && (
                    <>
                      <button
                        onClick={() => handleAccept(request.id)}
                        className="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 font-medium"
                      >
                        Accept
                      </button>
                      <button
                        onClick={() => handleReject(request.id)}
                        className="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 font-medium"
                      >
                        Reject
                      </button>
                    </>
                  )}
                  {request.status === 'confirmed' && (
                    <button
                      onClick={() => handleComplete(request.id)}
                      className="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 font-medium"
                    >
                      Mark as Completed
                    </button>
                  )}
                </div>
              </div>
            ))}
          </div>
        )}
      </div>
    </div>
  );
};

export default ProviderDashboard;

