import { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import { getRequests } from '../../services/api';
import ReviewForm from '../../components/common/ReviewForm';
import SuccessMessage from '../../components/common/SuccessMessage';

interface Request {
  id: number;
  provider_id?: number;
  provider_name: string;
  service_name: string;
  status: 'requested' | 'confirmed' | 'completed' | 'cancelled';
  requested_date: string;
  requested_time: string;
  address: string;
  created_at: string;
  has_review?: boolean;
}

const MyRequests = () => {
  const [requests, setRequests] = useState<Request[]>([]);
  const [loading, setLoading] = useState(true);
  const [statusFilter, setStatusFilter] = useState<string>('');
  const [showReviewForm, setShowReviewForm] = useState<number | null>(null);
  const [successMessage, setSuccessMessage] = useState('');

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

  const handleReviewSuccess = () => {
    setShowReviewForm(null);
    setSuccessMessage('Review submitted successfully!');
    setTimeout(() => setSuccessMessage(''), 5000);
    loadRequests(); // Reload to update has_review status
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

  return (
    <div className="min-h-screen bg-gray-50">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div className="flex justify-between items-center mb-6">
          <h1 className="text-3xl font-bold text-gray-900">My Service Requests</h1>
          <Link
            to="/"
            className="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700"
          >
            Browse Services
          </Link>
        </div>

        {successMessage && (
          <SuccessMessage
            message={successMessage}
            onClose={() => setSuccessMessage('')}
          />
        )}

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
            <p className="text-gray-600 mb-4">No service requests found.</p>
            <Link
              to="/"
              className="text-indigo-600 hover:text-indigo-700 font-medium"
            >
              Browse Services
            </Link>
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
                      Service Provider: <span className="font-medium">{request.provider_name}</span>
                    </p>
                  </div>
                  <span
                    className={`px-3 py-1 rounded-full text-sm font-medium ${getStatusColor(
                      request.status
                    )}`}
                  >
                    {request.status.charAt(0).toUpperCase() + request.status.slice(1)}
                  </span>
                </div>

                <div className="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm mb-4">
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
                  <div>
                    <p className="text-gray-500">Submitted</p>
                    <p className="font-medium text-gray-900">
                      {new Date(request.created_at).toLocaleDateString()}
                    </p>
                  </div>
                </div>

                {/* Review Section for Completed Requests */}
                {request.status === 'completed' && request.provider_id && (
                  <div className="border-t pt-4 mt-4">
                    {showReviewForm === request.id ? (
                      <ReviewForm
                        requestId={request.id}
                        providerId={request.provider_id}
                        onSuccess={handleReviewSuccess}
                        onCancel={() => setShowReviewForm(null)}
                      />
                    ) : (
                      <div className="flex items-center justify-between">
                        <p className="text-gray-600">
                          {request.has_review
                            ? 'You have already reviewed this service'
                            : 'Share your experience with this service'}
                        </p>
                        {!request.has_review && (
                          <button
                            onClick={() => setShowReviewForm(request.id)}
                            className="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 font-medium text-sm"
                          >
                            Write a Review
                          </button>
                        )}
                      </div>
                    )}
                  </div>
                )}
              </div>
            ))}
          </div>
        )}
      </div>
    </div>
  );
};

export default MyRequests;

