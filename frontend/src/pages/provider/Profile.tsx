import { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { getMyProviderProfile, updateProviderProfile } from '../../services/api';
import { getErrorMessage } from '../../utils/errorHandler';

interface Service {
  id?: number;
  name: string;
  price: number;
  description: string;
}

const Profile = () => {
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [formData, setFormData] = useState({
    name: '',
    phone: '',
    category: '',
    location: '',
    availability: '',
    services: [] as Service[],
  });
  const navigate = useNavigate();

  const loadProfile = async () => {
    setLoading(true);
    try {
      const response = await getMyProviderProfile();
      const profile = response.data.data.provider;
      setFormData({
        name: profile.name || '',
        phone: profile.phone || '',
        category: profile.category || '',
        location: profile.location || '',
        availability: profile.availability || '',
        services: profile.services || [],
      });
    } catch (err: unknown) {
      console.error('Failed to load profile', err);
      // Initialize with empty form if profile doesn't exist
      setFormData({
        name: '',
        phone: '',
        category: '',
        location: '',
        availability: '',
        services: [],
      });
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    loadProfile();
  }, []);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setSaving(true);
    try {
      await updateProviderProfile(formData);
      alert('Profile updated successfully!');
      navigate('/provider');
    } catch (err: unknown) {
      alert(getErrorMessage(err, 'Failed to update profile'));
    } finally {
      setSaving(false);
    }
  };

  const handleServiceChange = (index: number, field: keyof Service, value: string | number) => {
    const newServices = [...formData.services];
    newServices[index] = { ...newServices[index], [field]: value };
    setFormData({ ...formData, services: newServices });
  };

  const addService = () => {
    setFormData({
      ...formData,
      services: [...formData.services, { name: '', price: 0, description: '' }],
    });
  };

  const removeService = (index: number) => {
    setFormData({
      ...formData,
      services: formData.services.filter((_, i) => i !== index),
    });
  };

  const categories = [
    'plumber',
    'electrician',
    'tutor',
    'cleaner',
    'technician',
    'carpenter',
    'painter',
    'mechanic',
  ];

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
      <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div className="flex justify-between items-center mb-6">
          <h1 className="text-3xl font-bold text-gray-900">Manage Profile</h1>
          <button
            onClick={() => navigate('/provider')}
            className="text-indigo-600 hover:text-indigo-700"
          >
            ← Back to Dashboard
          </button>
        </div>

        <form onSubmit={handleSubmit} className="bg-white rounded-lg shadow-lg p-8 space-y-6">
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-2">
              Business/Service Name *
            </label>
            <input
              type="text"
              value={formData.name}
              onChange={(e) => setFormData({ ...formData, name: e.target.value })}
              required
              className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent outline-none"
              placeholder="e.g., ABC Plumbing Services"
            />
          </div>

          <div>
            <label className="block text-sm font-medium text-gray-700 mb-2">
              Phone Number *
            </label>
            <input
              type="tel"
              value={formData.phone}
              onChange={(e) => setFormData({ ...formData, phone: e.target.value })}
              required
              className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent outline-none"
              placeholder="0300-1234567"
            />
          </div>

          <div>
            <label className="block text-sm font-medium text-gray-700 mb-2">
              Category *
            </label>
            <select
              value={formData.category}
              onChange={(e) => setFormData({ ...formData, category: e.target.value })}
              required
              className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent outline-none"
            >
              <option value="">Select Category</option>
              {categories.map((cat) => (
                <option key={cat} value={cat}>
                  {cat.charAt(0).toUpperCase() + cat.slice(1)}
                </option>
              ))}
            </select>
          </div>

          <div>
            <label className="block text-sm font-medium text-gray-700 mb-2">
              Location *
            </label>
            <input
              type="text"
              value={formData.location}
              onChange={(e) => setFormData({ ...formData, location: e.target.value })}
              required
              className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent outline-none"
              placeholder="e.g., Karachi, Lahore"
            />
          </div>

          <div>
            <label className="block text-sm font-medium text-gray-700 mb-2">
              Availability *
            </label>
            <select
              value={formData.availability}
              onChange={(e) => setFormData({ ...formData, availability: e.target.value })}
              required
              className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent outline-none"
            >
              <option value="">Select Availability</option>
              <option value="Mon-Fri 9AM-6PM">Mon-Fri 9AM-6PM</option>
              <option value="Mon-Fri 8AM-5PM">Mon-Fri 8AM-5PM</option>
              <option value="Mon-Fri 10AM-7PM">Mon-Fri 10AM-7PM</option>
              <option value="Mon-Sat 9AM-6PM">Mon-Sat 9AM-6PM</option>
              <option value="Mon-Sat 8AM-7PM">Mon-Sat 8AM-7PM</option>
              <option value="Mon-Sun 9AM-6PM">Mon-Sun 9AM-6PM (All Week)</option>
              <option value="Mon-Sun 8AM-8PM">Mon-Sun 8AM-8PM (All Week Extended)</option>
              <option value="Mon-Wed 9AM-5PM">Mon-Wed 9AM-5PM</option>
              <option value="Mon-Thu 9AM-6PM">Mon-Thu 9AM-6PM</option>
            </select>
            <p className="mt-1 text-xs text-gray-500">
              Select your working days and hours
            </p>
          </div>

          <div>
            <div className="flex justify-between items-center mb-4">
              <label className="block text-sm font-medium text-gray-700">
                Services Offered *
              </label>
              <button
                type="button"
                onClick={addService}
                className="text-indigo-600 hover:text-indigo-700 text-sm font-medium"
              >
                + Add Service
              </button>
            </div>

            <div className="space-y-4">
              {formData.services.map((service, index) => (
                <div key={index} className="border-2 border-gray-200 rounded-lg p-4">
                  <div className="flex justify-between items-center mb-3">
                    <h4 className="font-medium text-gray-900">Service {index + 1}</h4>
                    <button
                      type="button"
                      onClick={() => removeService(index)}
                      className="text-red-600 hover:text-red-700 text-sm"
                    >
                      Remove
                    </button>
                  </div>

                  <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                      <label className="block text-xs font-medium text-gray-600 mb-1">
                        Service Name *
                      </label>
                      <input
                        type="text"
                        value={service.name}
                        onChange={(e) =>
                          handleServiceChange(index, 'name', e.target.value)
                        }
                        required
                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent outline-none text-sm"
                        placeholder="e.g., Pipe Repair"
                      />
                    </div>

                    <div>
                      <label className="block text-xs font-medium text-gray-600 mb-1">
                        Price (PKR) *
                      </label>
                      <input
                        type="number"
                        value={service.price}
                        onChange={(e) =>
                          handleServiceChange(index, 'price', parseFloat(e.target.value) || 0)
                        }
                        required
                        min="0"
                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent outline-none text-sm"
                        placeholder="1500"
                      />
                    </div>
                  </div>

                  <div className="mt-3">
                    <label className="block text-xs font-medium text-gray-600 mb-1">
                      Description
                    </label>
                    <textarea
                      value={service.description}
                      onChange={(e) =>
                        handleServiceChange(index, 'description', e.target.value)
                      }
                      rows={2}
                      className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent outline-none text-sm"
                      placeholder="Brief description of the service..."
                    />
                  </div>
                </div>
              ))}

              {formData.services.length === 0 && (
                <div className="text-center py-8 border-2 border-dashed border-gray-300 rounded-lg">
                  <p className="text-gray-500 mb-2">No services added yet</p>
                  <button
                    type="button"
                    onClick={addService}
                    className="text-indigo-600 hover:text-indigo-700 font-medium"
                  >
                    Add your first service
                  </button>
                </div>
              )}
            </div>
          </div>

          <div className="flex space-x-4 pt-4">
            <button
              type="submit"
              disabled={saving || formData.services.length === 0}
              className="flex-1 bg-indigo-600 text-white py-3 px-6 rounded-lg hover:bg-indigo-700 font-medium disabled:opacity-50 disabled:cursor-not-allowed"
            >
              {saving ? 'Saving...' : 'Save Profile'}
            </button>
            <button
              type="button"
              onClick={() => navigate('/provider')}
              className="px-6 py-3 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 font-medium"
            >
              Cancel
            </button>
          </div>
        </form>
      </div>
    </div>
  );
};

export default Profile;

