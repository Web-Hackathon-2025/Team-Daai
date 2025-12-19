import { useState, useEffect } from 'react';
import { useParams, Link } from 'react-router-dom';
import { getProvider, createRequest } from '../../services/api';
import Header from '../../components/layout/Header';

interface Service {
  id: number;
  name: string;
  price: number;
  description?: string;
}

interface Review {
  id: number;
  customer_name: string;
  rating: number;
  comment: string;
  created_at: string;
}

interface Provider {
  id: number;
  name: string;
  email: string;
  phone: string;
  category: string;
  location: string;
  rating: number;
  total_reviews: number;
  services: Service[];
  availability: string;
  reviews: Review[];
}

const ProviderDetail = () => {
  const { id } = useParams<{ id: string }>();
  const [provider, setProvider] = useState<Provider | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [selectedService, setSelectedService] = useState<number | null>(null);
  const [showRequestForm, setShowRequestForm] = useState(false);
  const [requestData, setRequestData] = useState({
    requested_date: '',
    requested_time: '',
    description: '',
    address: '',
  });
  const [submitting, setSubmitting] = useState(false);

  useEffect(() => {
    if (id) {
      loadProvider();
    }
  }, [id]);

  const loadProvider = async () => {
    setLoading(true);
    setError('');
    try {
      const response = await getProvider(parseInt(id!));
      setProvider(response.data.data.provider);
      if (response.data.data.provider.services.length > 0) {
        setSelectedService(response.data.data.provider.services[0].id);
      }
    } catch (err: any) {
      setError('Failed to load provider details');
      console.error(err);
    } finally {
      setLoading(false);
    }
  };

  const handleSubmitRequest = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!selectedService || !id) return;

    setSubmitting(true);
    try {
      await createRequest({
        provider_id: parseInt(id),
        service_id: selectedService,
        requested_date: requestData.requested_date,
        requested_time: requestData.requested_time,
        description: requestData.description,
        address: requestData.address,
      });
      alert('Service request submitted successfully!');
      setShowRequestForm(false);
      setRequestData({
        requested_date: '',
        requested_time: '',
        description: '',
        address: '',
      });
    } catch (err: any) {
      alert(err.response?.data?.error || 'Failed to submit request');
    } finally {
      setSubmitting(false);
    }
  };

  if (loading) {
    return (
      <>
        <Header />
        <div className="min-h-screen bg-gray-50 flex items-center justify-center">
          <div className="text-center">
            <div className="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
            <p className="mt-4 text-gray-600">Loading...</p>
          </div>
        </div>
      </>
    );
  }

  if (error || !provider) {
    return (
      <>
        <Header />
        <div className="min-h-screen bg-gray-50 flex items-center justify-center">
          <div className="text-center">
            <p className="text-red-600">{error || 'Provider not found'}</p>
            <Link to="/" className="mt-4 text-indigo-600 hover:underline">
              Back to Browse
            </Link>
          </div>
        </div>
      </>
    );
  }

  return (
    <div className="min-h-screen bg-gray-50">
      <Header />

      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <Link to="/" className="text-indigo-600 hover:text-indigo-700 mb-4 inline-block">
          ← Back to Browse
        </Link>

        <div className="bg-white rounded-lg shadow-lg p-8 mb-6">
          <div className="flex justify-between items-start mb-6">
            <div>
              <h1 className="text-3xl font-bold text-gray-900 mb-2">{provider.name}</h1>
              <div className="flex items-center space-x-4 text-gray-600">
                <span>{provider.category}</span>
                <span>•</span>
                <span>{provider.location}</span>
                <span>•</span>
                <div className="flex items-center">
                  <span className="text-yellow-500">★</span>
                  <span className="ml-1 font-medium">{provider.rating.toFixed(1)}</span>
                  <span className="ml-1 text-sm">({provider.total_reviews} reviews)</span>
                </div>
              </div>
            </div>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
            <div>
              <h2 className="text-xl font-semibold mb-4">Contact Information</h2>
              <div className="space-y-2 text-gray-600">
                <p><span className="font-medium">Email:</span> {provider.email}</p>
                <p><span className="font-medium">Phone:</span> {provider.phone}</p>
                <p><span className="font-medium">Availability:</span> {provider.availability}</p>
              </div>
            </div>

            <div>
              <h2 className="text-xl font-semibold mb-4">Services Offered</h2>
              <div className="space-y-4">
                {provider.services.map((service) => (
                  <div
                    key={service.id}
                    className={`border-2 rounded-lg p-4 cursor-pointer transition-colors ${
                      selectedService === service.id
                        ? 'border-indigo-600 bg-indigo-50'
                        : 'border-gray-200 hover:border-indigo-300'
                    }`}
                    onClick={() => setSelectedService(service.id)}
                  >
                    <div className="flex justify-between items-start">
                      <div>
                        <h3 className="font-semibold text-gray-900">{service.name}</h3>
                        {service.description && (
                          <p className="text-sm text-gray-600 mt-1">{service.description}</p>
                        )}
                      </div>
                      <span className="text-lg font-bold text-indigo-600">₨{service.price}</span>
                    </div>
                  </div>
                ))}
              </div>
            </div>
          </div>

          <div className="mt-8">
            <button
              onClick={() => setShowRequestForm(!showRequestForm)}
              className="bg-indigo-600 text-white px-6 py-3 rounded-lg hover:bg-indigo-700 font-medium"
            >
              {showRequestForm ? 'Cancel Request' : 'Request Service'}
            </button>
          </div>

          {showRequestForm && (
            <div className="mt-6 border-t pt-6">
              <h3 className="text-xl font-semibold mb-4">Request Service</h3>
              <form onSubmit={handleSubmitRequest} className="space-y-4">
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-2">
                    Selected Service
                  </label>
                  <p className="text-gray-900 font-medium">
                    {provider.services.find((s) => s.id === selectedService)?.name}
                  </p>
                </div>

                <div className="grid grid-cols-2 gap-4">
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-2">
                      Date
                    </label>
                    <input
                      type="date"
                      value={requestData.requested_date}
                      onChange={(e) =>
                        setRequestData({ ...requestData, requested_date: e.target.value })
                      }
                      required
                      min={new Date().toISOString().split('T')[0]}
                      className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent outline-none"
                    />
                  </div>

                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-2">
                      Time
                    </label>
                    <input
                      type="time"
                      value={requestData.requested_time}
                      onChange={(e) =>
                        setRequestData({ ...requestData, requested_time: e.target.value })
                      }
                      required
                      className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent outline-none"
                    />
                  </div>
                </div>

                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-2">
                    Address
                  </label>
                  <textarea
                    value={requestData.address}
                    onChange={(e) =>
                      setRequestData({ ...requestData, address: e.target.value })
                    }
                    required
                    rows={3}
                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent outline-none"
                    placeholder="Enter service address..."
                  />
                </div>

                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-2">
                    Description (Optional)
                  </label>
                  <textarea
                    value={requestData.description}
                    onChange={(e) =>
                      setRequestData({ ...requestData, description: e.target.value })
                    }
                    rows={3}
                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent outline-none"
                    placeholder="Additional details about your service request..."
                  />
                </div>

                <button
                  type="submit"
                  disabled={submitting}
                  className="bg-indigo-600 text-white px-6 py-3 rounded-lg hover:bg-indigo-700 font-medium disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  {submitting ? 'Submitting...' : 'Submit Request'}
                </button>
              </form>
            </div>
          )}
        </div>

        {/* Reviews Section */}
        {provider.reviews && provider.reviews.length > 0 && (
          <div className="bg-white rounded-lg shadow-lg p-8">
            <h2 className="text-2xl font-semibold mb-6">Reviews</h2>
            <div className="space-y-4">
              {provider.reviews.map((review) => (
                <div key={review.id} className="border-b pb-4 last:border-0">
                  <div className="flex justify-between items-start mb-2">
                    <h4 className="font-semibold text-gray-900">{review.customer_name}</h4>
                    <div className="flex items-center">
                      {[...Array(5)].map((_, i) => (
                        <span
                          key={i}
                          className={i < review.rating ? 'text-yellow-500' : 'text-gray-300'}
                        >
                          ★
                        </span>
                      ))}
                    </div>
                  </div>
                  <p className="text-gray-600">{review.comment}</p>
                  <p className="text-sm text-gray-500 mt-2">{review.created_at}</p>
                </div>
              ))}
            </div>
          </div>
        )}
      </div>
    </div>
  );
};

export default ProviderDetail;

