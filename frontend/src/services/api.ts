import axios from 'axios';

// API Base URL - update in .env file
const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || 'http://localhost/CosmoCon/api';

const api = axios.create({
  baseURL: API_BASE_URL,
  headers: {
    'Content-Type': 'application/json',
  },
});

// Add token to requests
api.interceptors.request.use((config) => {
  const token = localStorage.getItem('token');
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
      localStorage.removeItem('token');
      localStorage.removeItem('user');
      window.location.href = '/login';
    }
    return Promise.reject(error);
  }
);

// ==================== AUTH APIs ====================
export const login = (email: string, password: string) =>
  api.post('/auth/login', { email, password });

export const register = (userData: {
  name: string;
  email: string;
  password: string;
  role: 'customer' | 'service_provider';
}) => api.post('/auth/register', userData);

export const logout = () => api.post('/auth/logout');

// ==================== PROVIDER APIs ====================
export const getProviders = (filters?: {
  category?: string;
  location?: string;
  search?: string;
  page?: number;
  limit?: number;
}) => api.get('/providers', { params: filters });

export const getProvider = (id: number) => api.get(`/providers/${id}`);

export const getMyProviderProfile = () => api.get('/providers/me');

export const updateProviderProfile = (data: {
  name?: string;
  phone?: string;
  category?: string;
  location?: string;
  availability?: string;
  services?: Array<{ name: string; price: number; description?: string }>;
}) => api.put('/providers/me', data);

// ==================== REQUEST APIs ====================
export const createRequest = (requestData: {
  provider_id: number;
  service_id: number;
  requested_date: string;
  requested_time: string;
  description?: string;
  address: string;
}) => api.post('/requests', requestData);

export const getRequests = (status?: string) =>
  api.get('/requests', { params: { status } });

export const getRequest = (id: number) => api.get(`/requests/${id}`);

export const acceptRequest = (id: number) =>
  api.put(`/requests/${id}/accept`);

export const rejectRequest = (id: number, reason?: string) =>
  api.put(`/requests/${id}/reject`, { reason });

export const rescheduleRequest = (
  id: number,
  data: { new_date: string; new_time: string; reason?: string }
) => api.put(`/requests/${id}/reschedule`, data);

export const completeRequest = (id: number) =>
  api.put(`/requests/${id}/complete`);

export const cancelRequest = (id: number) =>
  api.put(`/requests/${id}/cancel`);

// ==================== REVIEW APIs ====================
export const createReview = (reviewData: {
  request_id: number;
  provider_id: number;
  rating: number;
  comment: string;
}) => api.post('/reviews', reviewData);

export const getProviderReviews = (providerId: number) =>
  api.get(`/reviews/provider/${providerId}`);

// ==================== ADMIN APIs (Optional) ====================
export const getAdminUsers = () => api.get('/admin/users');

export const approveProvider = (userId: number) =>
  api.put(`/admin/users/${userId}/approve`);

export const suspendUser = (userId: number) =>
  api.put(`/admin/users/${userId}/suspend`);

export const deleteUser = (userId: number) =>
  api.delete(`/admin/users/${userId}`);

export const getAdminStats = () => api.get('/admin/stats');

export default api;

