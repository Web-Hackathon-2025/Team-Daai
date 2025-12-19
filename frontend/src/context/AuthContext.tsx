/* eslint-disable react-refresh/only-export-components */
import { createContext, useContext, useState } from 'react';
import type { ReactNode } from 'react';
import { login as loginAPI, register as registerAPI } from '../services/api';

interface User {
  id: number;
  name: string;
  email: string;
  role: 'customer' | 'service_provider' | 'admin';
}

interface AuthContextType {
  user: User | null;
  loading: boolean;
  login: (email: string, password: string) => Promise<unknown>;
  register: (userData: {
    name: string;
    email: string;
    password: string;
    role: 'customer' | 'service_provider';
  }) => Promise<unknown>;
  logout: () => void;
}

const AuthContext = createContext<AuthContextType | undefined>(undefined);

export const AuthProvider = ({ children }: { children: ReactNode }) => {
  // Initialize user from localStorage
  const getUserFromStorage = (): User | null => {
    const token = localStorage.getItem('access_token');
    const userData = localStorage.getItem('user');
    if (token && userData) {
      try {
        return JSON.parse(userData);
      } catch (error) {
        console.error('Error parsing user data:', error);
        localStorage.removeItem('access_token');
        localStorage.removeItem('user');
      }
    }
    return null;
  };

  const [user, setUser] = useState<User | null>(getUserFromStorage);
  const [loading] = useState(false); // Loading is false since we initialize synchronously

  const login = async (email: string, password: string) => {
    try {
      const response = await loginAPI(email, password);
      // API response structure: { status, message, data: { user, token } }
      // So we need response.data.data
      const responseData = response.data?.data || response.data;
      // API actually returns 'token' not 'access_token' (even though docs say access_token)
      const token = responseData.token || responseData.access_token;
      const userData = responseData.user;
      
      if (!token || !userData) {
        console.error('Missing token or user data:', { token: !!token, userData: !!userData, responseData });
        throw new Error('Invalid response from server: missing token or user data');
      }
      
      // Ensure user has required fields
      const user: User = {
        id: userData.id,
        name: userData.name || `${userData.first_name || ''} ${userData.last_name || ''}`.trim() || userData.email,
        email: userData.email,
        role: userData.role,
      };
      
      localStorage.setItem('access_token', token); // Store as access_token for consistency
      localStorage.setItem('user', JSON.stringify(user));
      setUser(user);
      return response;
    } catch (error) {
      console.error('Login error:', error);
      throw error;
    }
  };

  const register = async (newUserData: {
    name: string;
    email: string;
    password: string;
    role: 'customer' | 'service_provider';
    phone?: string;
  }) => {
    try {
      const response = await registerAPI(newUserData);
      // Handle both response structures: response.data.data or response.data
      const responseData = response.data?.data || response.data;
      // API returns 'token' not 'access_token' (or 'access_token' for compatibility)
      const token = responseData.token || responseData.access_token;
      const userData = responseData.user;
      
      if (!token || !userData) {
        throw new Error('Invalid response from server');
      }
      
      // Ensure user has required fields
      const user: User = {
        id: userData.id,
        name: userData.name || `${userData.first_name || ''} ${userData.last_name || ''}`.trim() || userData.email,
        email: userData.email,
        role: userData.role,
      };
      
      localStorage.setItem('access_token', token); // Store as access_token for consistency
      localStorage.setItem('user', JSON.stringify(user));
      setUser(user);
      return response;
    } catch (error) {
      console.error('Registration error:', error);
      throw error;
    }
  };

  const logout = () => {
    localStorage.removeItem('access_token');
    localStorage.removeItem('user');
    setUser(null);
  };

  return (
    <AuthContext.Provider value={{ user, login, register, logout, loading }}>
      {children}
    </AuthContext.Provider>
  );
};

export const useAuth = () => {
  const context = useContext(AuthContext);
  if (context === undefined) {
    throw new Error('useAuth must be used within an AuthProvider');
  }
  return context;
};

