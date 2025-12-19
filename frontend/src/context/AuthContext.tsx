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
    const response = await loginAPI(email, password);
    const { access_token, user: userData } = response.data.data;
    localStorage.setItem('access_token', access_token);
    localStorage.setItem('user', JSON.stringify(userData));
    setUser(userData);
    return response;
  };

  const register = async (newUserData: {
    name: string;
    email: string;
    password: string;
    role: 'customer' | 'service_provider';
    phone?: string;
  }) => {
    const response = await registerAPI(newUserData);
    const { access_token, user: newUser } = response.data.data;
    localStorage.setItem('access_token', access_token);
    localStorage.setItem('user', JSON.stringify(newUser));
    setUser(newUser);
    return response;
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

