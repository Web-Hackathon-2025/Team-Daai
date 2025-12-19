import axios from 'axios';
import { mockProviders, mockRequests, delay } from '../utils/mockData';

// Enable mock mode for UI testing (set to false when backend is ready)
const USE_MOCK_DATA = true;

// API Base URL - update in .env file
const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || 'https://thinklikeacoder.com/api';

const api = axios.create({
  baseURL: API_BASE_URL,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
});

// Add token to requests
api.interceptors.request.use((config) => {
  const token = localStorage.getItem('access_token');
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

// Handle response errors
api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      // Unauthorized - clear token and redirect to login
      localStorage.removeItem('access_token');
      localStorage.removeItem('user');
      window.location.href = '/login';
    }
    return Promise.reject(error);
  }
);

// ==================== AUTH APIs ====================
export const login = async (email: string, password: string) => {
  if (USE_MOCK_DATA) {
    await delay(500); // Simulate API delay
    
    // Determine role based on email pattern for easy testing
    let role: 'customer' | 'service_provider' | 'admin' = 'customer';
    if (email.includes('provider') || email.includes('service')) {
      role = 'service_provider';
    } else if (email.includes('admin')) {
      role = 'admin';
    }
    
    const nameParts = email.split('@')[0].replace(/[._]/g, ' ').split(' ');
    const user = {
      id: 1,
      first_name: nameParts[0] || 'User',
      last_name: nameParts.slice(1).join(' ') || '',
      name: email.split('@')[0].replace(/[._]/g, ' '),
      email,
      role,
    };
    const access_token = 'mock_token_' + Date.now();
    return {
      data: {
        status: 'success',
        message: 'Login successful',
        data: { 
          user, 
          access_token,
          token_type: 'bearer',
          expires_in: 3600
        },
      },
    };
  }
  // API expects 'login' field which can be email, username, or phone
  return api.post('/auth/login', { login: email, password });
};

export const register = async (userData: {
  name: string;
  email: string;
  password: string;
  role: 'customer' | 'service_provider';
  phone?: string;
}) => {
  if (USE_MOCK_DATA) {
    await delay(500);
    const nameParts = userData.name.split(' ');
    const user = { 
      id: Date.now(),
      first_name: nameParts[0] || userData.name,
      last_name: nameParts.slice(1).join(' ') || '',
      name: userData.name,
      email: userData.email,
      phone: userData.phone || '+923001234567',
      role: userData.role,
    };
    const access_token = 'mock_token_' + Date.now();
    return {
      data: {
        status: 'success',
        message: 'User registered successfully',
        data: { 
          user, 
          access_token,
          token_type: 'bearer',
          expires_in: 3600
        },
      },
    };
  }
  // API expects first_name, last_name, phone, password_confirmation
  const nameParts = userData.name.split(' ');
  const registerData = {
    first_name: nameParts[0] || userData.name,
    last_name: nameParts.slice(1).join(' ') || '',
    email: userData.email,
    password: userData.password,
    password_confirmation: userData.password,
    phone: userData.phone || '+923001234567',
    role: userData.role,
  };
  return api.post('/auth/register', registerData);
};

export const logout = () => {
  if (USE_MOCK_DATA) {
    return Promise.resolve({ data: { success: true } });
  }
  return api.post('/auth/logout');
};

// ==================== PROVIDER APIs ====================
export const getProviders = async (filters?: {
  category?: string;
  location?: string;
  search?: string;
  page?: number;
  limit?: number;
}) => {
  if (USE_MOCK_DATA) {
    await delay(300);
    let filtered = [...mockProviders];
    
    if (filters?.category) {
      filtered = filtered.filter((p) => p.category === filters.category);
    }
    if (filters?.location) {
      filtered = filtered.filter((p) =>
        p.location.toLowerCase().includes(filters.location!.toLowerCase())
      );
    }
    if (filters?.search) {
      const searchLower = filters.search.toLowerCase();
      filtered = filtered.filter(
        (p) =>
          p.name.toLowerCase().includes(searchLower) ||
          p.category.toLowerCase().includes(searchLower)
      );
    }
    
    return {
      data: {
        success: true,
        data: {
          providers: filtered,
          total: filtered.length,
          page: filters?.page || 1,
          limit: filters?.limit || 10,
        },
      },
    };
  }
  return api.get('/providers', { params: filters });
};

export const getProvider = async (id: number) => {
  if (USE_MOCK_DATA) {
    await delay(300);
    const provider = mockProviders.find((p) => p.id === id);
    if (!provider) {
      throw new Error('Provider not found');
    }
    return {
      data: {
        success: true,
        data: { provider },
      },
    };
  }
  return api.get(`/providers/${id}`);
};

export const getMyProviderProfile = async () => {
  if (USE_MOCK_DATA) {
    await delay(300);
    return {
      data: {
        success: true,
        data: {
          provider: {
            id: 1,
            name: 'My Business',
            email: 'business@example.com',
            phone: '0300-1234567',
            category: 'plumber',
            location: 'Karachi',
            availability: 'Mon-Fri 9AM-6PM',
            services: [
              { id: 1, name: 'Service 1', price: 1500, description: 'Description' },
            ],
          },
        },
      },
    };
  }
  return api.get('/providers/me');
};

export const updateProviderProfile = async (data: {
  name?: string;
  phone?: string;
  category?: string;
  location?: string;
  availability?: string;
  services?: Array<{ name: string; price: number; description?: string }>;
}) => {
  if (USE_MOCK_DATA) {
    await delay(500);
    return {
      data: {
        success: true,
        data: { message: 'Profile updated successfully' },
      },
    };
  }
  return api.put('/providers/me', data);
};

// ==================== REQUEST APIs ====================
export const createRequest = async (requestData: {
  provider_id: number;
  service_id: number;
  request_date: string;
  request_time: string;
  description?: string;
  location: string;
  estimated_hours?: number;
}) => {
  if (USE_MOCK_DATA) {
    await delay(500);
    return {
      data: {
        status: 'success',
        message: 'Service request created successfully',
        data: {
          id: Date.now(),
          customer_id: 1,
          provider_id: requestData.provider_id,
          service_id: requestData.service_id,
          status: 'requested',
          request_date: requestData.request_date,
          request_time: requestData.request_time,
          location: requestData.location,
          description: requestData.description || '',
          estimated_hours: requestData.estimated_hours || null,
          created_at: new Date().toISOString(),
        },
      },
    };
  }
  return api.post('/requests', requestData);
};

export const getRequests = async (status?: string) => {
  if (USE_MOCK_DATA) {
    await delay(300);
    let filtered = [...mockRequests];
    if (status) {
      filtered = filtered.filter((r) => r.status === status);
    }
    return {
      data: {
        success: true,
        data: { requests: filtered },
      },
    };
  }
  return api.get('/requests', { params: { status } });
};

export const getRequest = (id: number) => api.get(`/requests/${id}`);

export const acceptRequest = async (id: number) => {
  if (USE_MOCK_DATA) {
    await delay(300);
    return { data: { success: true, data: { message: 'Request accepted' } } };
  }
  return api.put(`/requests/${id}/accept`);
};

export const rejectRequest = async (id: number, reason?: string) => {
  if (USE_MOCK_DATA) {
    await delay(300);
    return { data: { success: true, data: { message: 'Request rejected' } } };
  }
  return api.put(`/requests/${id}/reject`, { reason });
};

export const rescheduleRequest = async (
  id: number,
  data: { new_date: string; new_time: string; reason?: string }
) => {
  if (USE_MOCK_DATA) {
    await delay(300);
    return { data: { success: true, data: { message: 'Request rescheduled' } } };
  }
  return api.put(`/requests/${id}/reschedule`, data);
};

export const completeRequest = async (id: number) => {
  if (USE_MOCK_DATA) {
    await delay(300);
    return { data: { success: true, data: { message: 'Request completed' } } };
  }
  return api.put(`/requests/${id}/complete`);
};

export const cancelRequest = async (id: number) => {
  if (USE_MOCK_DATA) {
    await delay(300);
    return { data: { success: true, data: { message: 'Request cancelled' } } };
  }
  return api.put(`/requests/${id}/cancel`);
};

// ==================== REVIEW APIs ====================
export const createReview = async (reviewData: {
  service_request_id: number;
  provider_id: number;
  rating: number;
  comment: string;
}) => {
  if (USE_MOCK_DATA) {
    await delay(500);
    return {
      data: {
        status: 'success',
        message: 'Review submitted successfully',
        data: {
          id: Date.now(),
          service_request_id: reviewData.service_request_id,
          customer_id: 1,
          provider_id: reviewData.provider_id,
          rating: reviewData.rating,
          comment: reviewData.comment,
          created_at: new Date().toISOString(),
        },
      },
    };
  }
  return api.post('/reviews', reviewData);
};

export const getProviderReviews = async (providerId: number) => {
  if (USE_MOCK_DATA) {
    await delay(300);
    const provider = mockProviders.find((p) => p.id === providerId);
    return {
      data: {
        success: true,
        data: {
          reviews: provider?.reviews || [],
          average_rating: provider?.rating || 0,
          total_reviews: provider?.total_reviews || 0,
        },
      },
    };
  }
  return api.get(`/reviews/provider/${providerId}`);
};

// ==================== ADMIN APIs (Optional) ====================
export const getAdminUsers = async () => {
  if (USE_MOCK_DATA) {
    await delay(300);
    return {
      data: {
        success: true,
        data: {
          users: [
            {
              id: 1,
              name: 'John Customer',
              email: 'john@example.com',
              role: 'customer',
              status: 'active',
              created_at: '2024-01-10T10:00:00Z',
            },
            {
              id: 2,
              name: 'ABC Plumbing',
              email: 'abc@example.com',
              role: 'service_provider',
              status: 'pending',
              created_at: '2024-01-12T14:00:00Z',
            },
            {
              id: 3,
              name: 'Quick Electric',
              email: 'quick@example.com',
              role: 'service_provider',
              status: 'active',
              created_at: '2024-01-08T09:00:00Z',
            },
            {
              id: 4,
              name: 'Jane Doe',
              email: 'jane@example.com',
              role: 'customer',
              status: 'active',
              created_at: '2024-01-11T11:00:00Z',
            },
          ],
        },
      },
    };
  }
  return api.get('/admin/users');
};

export const approveProvider = async (userId: number) => {
  if (USE_MOCK_DATA) {
    await delay(300);
    return { data: { success: true, data: { message: 'Provider approved' } } };
  }
  return api.put(`/admin/users/${userId}/approve`);
};

export const suspendUser = async (userId: number) => {
  if (USE_MOCK_DATA) {
    await delay(300);
    return { data: { success: true, data: { message: 'User suspended' } } };
  }
  return api.put(`/admin/users/${userId}/suspend`);
};

export const deleteUser = async (userId: number) => {
  if (USE_MOCK_DATA) {
    await delay(300);
    return { data: { success: true, data: { message: 'User deleted' } } };
  }
  return api.delete(`/admin/users/${userId}`);
};

export const getAdminStats = async () => {
  if (USE_MOCK_DATA) {
    await delay(300);
    return {
      data: {
        success: true,
        data: {
          total_users: 150,
          total_providers: 50,
          total_requests: 300,
          completed_requests: 250,
          pending_requests: 50,
        },
      },
    };
  }
  return api.get('/admin/stats');
};

export default api;

