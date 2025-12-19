import { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import { getProviders } from '../../services/api';

interface Provider {
  id: number;
  name: string;
  email: string;
  phone: string;
  category: string;
  location: string;
  rating: number;
  total_reviews: number;
  services: Array<{
    id: number;
    name: string;
    price: number;
    description?: string;
  }>;
}

const Dashboard = () => {
  const [providers, setProviders] = useState<Provider[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [filters, setFilters] = useState({
    category: '',
    location: '',
    search: '',
  });

  const loadProviders = async () => {
    setLoading(true);
    setError('');
    try {
      const response = await getProviders(filters);
      // API returns paginated structure: response.data.data.data (array of providers)
      const providersData = response.data?.data?.data || response.data?.data?.providers || [];
      
      // Map API response to our Provider interface
      const mappedProviders: Provider[] = providersData.map((p: {
        id: number;
        user?: { name?: string; first_name?: string; last_name?: string; email?: string; phone?: string };
        name?: string;
        email?: string;
        phone?: string;
        category?: string;
        location?: string;
        average_rating?: number;
        rating?: number;
        total_reviews?: number;
        services?: Array<{ id: number; name: string; price: number; description?: string }>;
      }) => ({
        id: p.id,
        name: p.user?.name || p.name || `${p.user?.first_name || ''} ${p.user?.last_name || ''}`.trim() || 'Unknown',
        email: p.user?.email || p.email || '',
        phone: p.phone || p.user?.phone || '',
        category: p.category || '',
        location: p.location || '',
        rating: p.average_rating || p.rating || 0,
        total_reviews: p.total_reviews || 0,
        services: p.services || [],
      }));
      
      setProviders(mappedProviders);
    } catch (err: unknown) {
      setError('Failed to load service providers');
      console.error('Provider loading error:', err);
      setProviders([]);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    loadProviders();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [filters]);

  const categories = ['plumber', 'electrician', 'tutor', 'cleaner', 'technician', 'carpenter', 'painter'];

  return (
    <div className="min-h-screen bg-gray-50">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <h1 className="text-3xl font-bold text-gray-900 mb-6">Find Service Providers</h1>

        {/* Filters */}
        <div className="bg-white p-6 rounded-lg shadow mb-6">
          <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-2">
                Search
              </label>
              <input
                type="text"
                value={filters.search}
                onChange={(e) => setFilters({ ...filters, search: e.target.value })}
                placeholder="Search by name or service..."
                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent outline-none"
              />
            </div>

            <div>
              <label className="block text-sm font-medium text-gray-700 mb-2">
                Category
              </label>
              <select
                value={filters.category}
                onChange={(e) => setFilters({ ...filters, category: e.target.value })}
                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent outline-none"
              >
                <option value="">All Categories</option>
                {categories.map((cat) => (
                  <option key={cat} value={cat}>
                    {cat.charAt(0).toUpperCase() + cat.slice(1)}
                  </option>
                ))}
              </select>
            </div>

            <div>
              <label className="block text-sm font-medium text-gray-700 mb-2">
                Location
              </label>
              <input
                type="text"
                value={filters.location}
                onChange={(e) => setFilters({ ...filters, location: e.target.value })}
                placeholder="Enter location..."
                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent outline-none"
              />
            </div>
          </div>
        </div>

        {/* Providers List */}
        {loading ? (
          <div className="text-center py-12">
            <div className="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
            <p className="mt-4 text-gray-600">Loading providers...</p>
          </div>
        ) : error && providers.length === 0 ? (
          <div className="bg-yellow-50 border border-yellow-200 rounded-lg p-6 text-center">
            <p className="text-yellow-800">{error}</p>
            <p className="text-sm text-yellow-600 mt-2">Waiting for backend API...</p>
          </div>
        ) : providers.length === 0 ? (
          <div className="bg-white rounded-lg shadow p-8 text-center">
            <p className="text-gray-600">No service providers found. Try different filters.</p>
          </div>
        ) : (
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            {providers.map((provider) => (
              <Link
                key={provider.id}
                to={`/provider/${provider.id}`}
                className="bg-white rounded-lg shadow hover:shadow-lg transition-shadow p-6"
              >
                <div className="flex justify-between items-start mb-4">
                  <h3 className="text-xl font-semibold text-gray-900">{provider.name}</h3>
                  <div className="flex items-center">
                    <span className="text-yellow-500">★</span>
                    <span className="ml-1 text-gray-700 font-medium">
                      {provider.rating.toFixed(1)}
                    </span>
                    <span className="ml-1 text-gray-500 text-sm">
                      ({provider.total_reviews})
                    </span>
                  </div>
                </div>

                <div className="space-y-2 mb-4">
                  <p className="text-sm text-gray-600">
                    <span className="font-medium">Category:</span> {provider.category}
                  </p>
                  <p className="text-sm text-gray-600">
                    <span className="font-medium">Location:</span> {provider.location}
                  </p>
                  <p className="text-sm text-gray-600">
                    <span className="font-medium">Services:</span>{' '}
                    {provider.services.length} service(s)
                  </p>
                </div>

                {provider.services.length > 0 && (
                  <div className="border-t pt-4">
                    <p className="text-xs text-gray-500 mb-2">Starting from:</p>
                    <p className="text-lg font-bold text-indigo-600">
                      ₨{Math.min(...provider.services.map((s) => s.price))}
                    </p>
                  </div>
                )}

                <div className="mt-4 text-indigo-600 text-sm font-medium">
                  View Profile →
                </div>
              </Link>
            ))}
          </div>
        )}
      </div>
    </div>
  );
};

export default Dashboard;

