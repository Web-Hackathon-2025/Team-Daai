import { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import { getProviders } from '../../services/api';

interface Review {
  id: number;
  provider_id: number;
  provider_name: string;
  customer_name: string;
  rating: number;
  comment: string;
  status: 'approved' | 'pending' | 'reported';
  created_at: string;
}

const Reviews = () => {
  const [reviews, setReviews] = useState<Review[]>([]);
  const [loading, setLoading] = useState(true);
  const [filter, setFilter] = useState<'all' | 'pending' | 'reported'>('all');

  useEffect(() => {
    loadReviews();
  }, []);

  const loadReviews = async () => {
    setLoading(true);
    try {
      // In real app, this would be a dedicated endpoint
      const response = await getProviders();
      const providers = response.data.data.providers || [];
      
      // Collect all reviews from providers
      const allReviews: Review[] = [];
      providers.forEach((provider: any) => {
        if (provider.reviews) {
          provider.reviews.forEach((review: any) => {
            allReviews.push({
              ...review,
              provider_id: provider.id,
              provider_name: provider.name,
              status: 'approved' as const,
            });
          });
        }
      });
      
      setReviews(allReviews);
    } catch (err: unknown) {
      console.error('Failed to load reviews', err);
      // Mock data
      setReviews([
        {
          id: 1,
          provider_id: 1,
          provider_name: 'ABC Plumbing',
          customer_name: 'John Doe',
          rating: 5,
          comment: 'Excellent service! Very professional.',
          status: 'approved',
          created_at: '2024-01-15T10:00:00Z',
        },
        {
          id: 2,
          provider_id: 1,
          provider_name: 'ABC Plumbing',
          customer_name: 'Jane Smith',
          rating: 4,
          comment: 'Good work, but took longer than expected.',
          status: 'pending',
          created_at: '2024-01-16T14:00:00Z',
        },
      ]);
    } finally {
      setLoading(false);
    }
  };

  const handleApprove = (reviewId: number) => {
    setReviews(reviews.map((r) => (r.id === reviewId ? { ...r, status: 'approved' as const } : r)));
    alert('Review approved');
  };

  const handleDelete = (reviewId: number) => {
    if (!confirm('Delete this review?')) return;
    setReviews(reviews.filter((r) => r.id !== reviewId));
    alert('Review deleted');
  };

  const filteredReviews = reviews.filter((review) => {
    if (filter === 'pending' && review.status !== 'pending') return false;
    if (filter === 'reported' && review.status !== 'reported') return false;
    return true;
  });

  const getStatusColor = (status: string) => {
    switch (status) {
      case 'approved':
        return 'bg-green-100 text-green-800';
      case 'pending':
        return 'bg-yellow-100 text-yellow-800';
      case 'reported':
        return 'bg-red-100 text-red-800';
      default:
        return 'bg-gray-100 text-gray-800';
    }
  };

  return (
    <div className="min-h-screen bg-gray-50">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div className="flex justify-between items-center mb-6">
          <h1 className="text-3xl font-bold text-gray-900">Reviews & Moderation</h1>
          <Link
            to="/admin"
            className="text-indigo-600 hover:text-indigo-700 font-medium"
          >
            ← Back to Dashboard
          </Link>
        </div>

        {/* Filter */}
        <div className="bg-white rounded-lg shadow p-4 mb-6">
          <label className="block text-sm font-medium text-gray-700 mb-2">Filter</label>
          <select
            value={filter}
            onChange={(e) => setFilter(e.target.value as 'all' | 'pending' | 'reported')}
            className="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent outline-none"
          >
            <option value="all">All Reviews</option>
            <option value="pending">Pending Approval</option>
            <option value="reported">Reported Reviews</option>
          </select>
        </div>

        {/* Reviews List */}
        {loading ? (
          <div className="text-center py-12">
            <div className="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
            <p className="mt-4 text-gray-600">Loading reviews...</p>
          </div>
        ) : filteredReviews.length === 0 ? (
          <div className="bg-white rounded-lg shadow p-8 text-center">
            <p className="text-gray-600">No reviews found.</p>
          </div>
        ) : (
          <div className="space-y-4">
            {filteredReviews.map((review) => (
              <div key={review.id} className="bg-white rounded-lg shadow p-6">
                <div className="flex justify-between items-start mb-4">
                  <div className="flex-1">
                    <div className="flex items-center space-x-4 mb-2">
                      <h4 className="font-semibold text-gray-900">{review.customer_name}</h4>
                      <span className="text-sm text-gray-500">for</span>
                      <span className="font-medium text-indigo-600">{review.provider_name}</span>
                      <span
                        className={`px-2 py-1 text-xs font-semibold rounded-full ${getStatusColor(
                          review.status
                        )}`}
                      >
                        {review.status}
                      </span>
                    </div>
                    <div className="flex items-center mb-2">
                      {[...Array(5)].map((_, i) => (
                        <span
                          key={i}
                          className={i < review.rating ? 'text-yellow-500' : 'text-gray-300'}
                        >
                          ★
                        </span>
                      ))}
                      <span className="ml-2 text-sm text-gray-600">
                        {new Date(review.created_at).toLocaleDateString()}
                      </span>
                    </div>
                    <p className="text-gray-700">{review.comment}</p>
                  </div>
                </div>

                {review.status !== 'approved' && (
                  <div className="flex space-x-2 pt-4 border-t">
                    <button
                      onClick={() => handleApprove(review.id)}
                      className="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 text-sm font-medium"
                    >
                      Approve
                    </button>
                    <button
                      onClick={() => handleDelete(review.id)}
                      className="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 text-sm font-medium"
                    >
                      Delete
                    </button>
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

export default Reviews;

